<?php
// api/org-chart.php
// Org chart API — supports multi-person tiers (max 5 per tier) and province-scoped charts.
// v2.2: Province-aware GET — returns nodes filtered by ?province= param.
//       Falls back to legacy position_order if tier column absent.
//       Phase9: province column added to org_chart.
session_start();
require_once '../config/database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

$db     = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: return org chart nodes, optionally filtered by province ──────────
if ($method === 'GET') {
    try {
        // Detect available columns
        $hasTierCol     = false;
        $hasProvinceCol = false;
        try {
            $db->query("SELECT tier FROM org_chart LIMIT 1");
            $hasTierCol = true;
        } catch (PDOException $e) {
            $hasTierCol = false;
        }
        try {
            $db->query("SELECT province FROM org_chart LIMIT 1");
            $hasProvinceCol = true;
        } catch (PDOException $e) {
            $hasProvinceCol = false;
        }

        // Determine province filter
        // ?province=X for a specific chart; omit for the user's own province (or all for super_admin)
        $requestedProvince = $_GET['province'] ?? null;
        $validProvinces    = ['Negros Occidental', 'Negros Oriental', 'Siquijor'];

        // Sanitise
        if ($requestedProvince !== null && !in_array($requestedProvince, $validProvinces, true)) {
            $requestedProvince = null;
        }

        $params = [];
        $whereClause = '';

        if ($hasProvinceCol) {
            if ($requestedProvince !== null) {
                $whereClause = ' WHERE province = ?';
                $params[]    = $requestedProvince;
            }
            // If no province requested, return ALL rows (about.php renders per-province tabs)
        }

        if ($hasTierCol) {
            $sql  = "SELECT * FROM org_chart" . $whereClause . " ORDER BY province ASC, tier ASC, sort_order ASC, id ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        } else {
            // Legacy fallback
            $stmt = $db->query("SELECT * FROM org_chart ORDER BY position_order ASC");
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $transformed = [];
        foreach ($rows as $row) {
            if ($hasTierCol) {
                $tier      = (int)$row['tier'];
                $sortOrder = (int)$row['sort_order'];
            } else {
                $pos       = (int)$row['position_order'];
                $tier      = min($pos - 1, 3);
                $sortOrder = 0;
            }

            $transformed[] = [
                'id'         => (int)$row['id'],
                'province'   => $hasProvinceCol ? ($row['province'] ?? 'Negros Occidental') : 'Negros Occidental',
                'tier'       => $tier,
                'sort_order' => $sortOrder,
                'name'       => trim($row['person_name'] ?? '') ?: 'Vacant',
                'role_label' => $row['position_title'],
            ];
        }

        echo json_encode(['success' => true, 'data' => $transformed]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// ── POST: add, update, or delete a node (super_admin only) ───────────────
if ($method === 'POST') {
    if (!$auth->isSuperAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {

            case 'add_person':
                // Adds a new person to a tier (province-scoped)
                $tier           = (int)($_POST['tier'] ?? 0);
                $position_title = trim($_POST['position_title'] ?? '');
                $person_name    = trim($_POST['person_name'] ?? '') ?: null;
                $province       = trim($_POST['province'] ?? 'Negros Occidental');
                $validProvinces = ['Negros Occidental', 'Negros Oriental', 'Siquijor'];
                if (!in_array($province, $validProvinces, true)) {
                    $province = 'Negros Occidental';
                }

                if (empty($position_title)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'message' => 'Position title is required']);
                    exit;
                }
                if ($tier < 0 || $tier > 3) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'message' => 'Invalid tier (0-3 allowed)']);
                    exit;
                }

                // Check tier-specific limits
                if ($tier === 0) {
                    // Tier 0 (Regional Director): max 1 total, ignore province
                    $countStmt = $db->prepare("SELECT COUNT(*) FROM org_chart WHERE tier = 0");
                    $countStmt->execute();
                    if ((int)$countStmt->fetchColumn() >= 1) {
                        http_response_code(422);
                        echo json_encode(['success' => false, 'message' => 'Maximum 1 Regional Director allowed (Tier 0 limit reached)']);
                        exit;
                    }
                } elseif ($tier === 3) {
                    // Tier 3: max 3 per province
                    $countStmt = $db->prepare("SELECT COUNT(*) FROM org_chart WHERE tier = ? AND province = ?");
                    $countStmt->execute([$tier, $province]);
                    if ((int)$countStmt->fetchColumn() >= 3) {
                        http_response_code(422);
                        echo json_encode(['success' => false, 'message' => 'Maximum 3 people per tier reached for this province']);
                        exit;
                    }
                } else {
                    // Tier 1-2: max 5 per province
                    $countStmt = $db->prepare("SELECT COUNT(*) FROM org_chart WHERE tier = ? AND province = ?");
                    $countStmt->execute([$tier, $province]);
                    if ((int)$countStmt->fetchColumn() >= 5) {
                        http_response_code(422);
                        echo json_encode(['success' => false, 'message' => 'Maximum 5 people per tier reached for this province']);
                        exit;
                    }
                }

                // Determine next sort_order in tier+province
                $maxStmt = $db->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 FROM org_chart WHERE tier = ? AND province = ?");
                $maxStmt->execute([$tier, $province]);
                $nextSort = (int)$maxStmt->fetchColumn();

                $ins = $db->prepare("INSERT INTO org_chart (province, tier, sort_order, position_title, person_name, position_order)
                                     VALUES (?, ?, ?, ?, ?, ?)");
                $ins->execute([$province, $tier, $nextSort, $position_title, $person_name, ($tier * 10 + $nextSort)]);
                $newId = (int)$db->lastInsertId();

                $auth->logActivity($_SESSION['user_id'], 'create', 'org_chart', $newId,
                    "Added org chart person: {$position_title}" . ($person_name ? " ({$person_name})" : ' (Vacant)'));

                echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Person added successfully']);
                break;

            case 'update_person':
                $id             = (int)($_POST['id'] ?? 0);
                $position_title = trim($_POST['position_title'] ?? '');
                $person_name    = trim($_POST['person_name'] ?? '') ?: null;

                if ($id <= 0 || empty($position_title)) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'message' => 'ID and position title are required']);
                    exit;
                }

                $upd = $db->prepare("UPDATE org_chart SET position_title = ?, person_name = ? WHERE id = ?");
                $upd->execute([$position_title, $person_name, $id]);

                $auth->logActivity($_SESSION['user_id'], 'update', 'org_chart', $id,
                    "Updated org chart entry ID {$id}: {$position_title}");

                echo json_encode(['success' => true, 'message' => 'Updated successfully']);
                break;

            case 'delete_person':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    http_response_code(422);
                    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                    exit;
                }

                // Prevent deleting last entry in any tier (all tiers require at least 1)
                $rowStmt = $db->prepare("SELECT tier, province FROM org_chart WHERE id = ?");
                $rowStmt->execute([$id]);
                $row = $rowStmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    $tier = (int)$row['tier'];
                    
                    if ($tier === 0) {
                        // Tier 0 (Regional Director): check total count (not province-scoped)
                        $cntStmt = $db->prepare("SELECT COUNT(*) FROM org_chart WHERE tier = 0");
                        $cntStmt->execute();
                        if ((int)$cntStmt->fetchColumn() <= 1) {
                            http_response_code(422);
                            echo json_encode(['success' => false, 'message' => 'Cannot delete the only Regional Director (Tier 0 requires at least 1 entry)']);
                            exit;
                        }
                    } else {
                        // Tiers 1-3: check province-scoped count
                        $rowProvince = $row['province'] ?? 'Negros Occidental';
                        $cntStmt = $db->prepare("SELECT COUNT(*) FROM org_chart WHERE tier = ? AND province = ?");
                        $cntStmt->execute([$tier, $rowProvince]);
                        if ((int)$cntStmt->fetchColumn() <= 1) {
                            http_response_code(422);
                            echo json_encode(['success' => false, 'message' => 'Cannot delete the only entry in this tier for this province']);
                            exit;
                        }
                    }
                }

                $del = $db->prepare("DELETE FROM org_chart WHERE id = ?");
                $del->execute([$id]);

                // Re-normalise sort_order in the affected tier
                if ($row) {
                    $rowProvince = $row['province'] ?? 'Negros Occidental';
                    $reorder = $db->prepare(
                        "SELECT id FROM org_chart WHERE tier = ? AND province = ? ORDER BY sort_order ASC, id ASC"
                    );
                    $reorder->execute([$row['tier'], $rowProvince]);
                    $ids = $reorder->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($ids as $idx => $rid) {
                        $db->prepare("UPDATE org_chart SET sort_order = ? WHERE id = ?")->execute([$idx, $rid]);
                    }
                }

                $auth->logActivity($_SESSION['user_id'], 'delete', 'org_chart', $id,
                    "Deleted org chart entry ID {$id}");

                echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } catch (PDOException $e) {
        error_log('[OrgChart API] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
