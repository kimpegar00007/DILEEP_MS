<?php
// includes/shared-budget-notice.php
// Non-blocking banner for a Proponent / Beneficiary that belongs to a shared budget.
// Include it on a view or edit page after setting:
//   $sbNoticeBudgetId  int|null  the record's shared_budget_id
//   $sbNoticeAmount    float     the record's own amount (its share)
// It never changes how the page saves; it only explains the amount and warns when the
// shares no longer add up to the approved total.
if (!empty($sbNoticeBudgetId)) {
    require_once __DIR__ . '/../models/SharedBudget.php';
    $sbNotice = (new SharedBudget())->findById((int) $sbNoticeBudgetId);   // province-scoped
    if ($sbNotice):
?>
<div class="alert <?php echo $sbNotice['mismatch'] ? 'alert-warning' : 'alert-info'; ?> d-flex gap-2 mb-3">
    <i class="bi <?php echo $sbNotice['mismatch'] ? 'bi-exclamation-triangle-fill' : 'bi-pie-chart-fill'; ?>"></i>
    <div>
        <strong>Shared budget:</strong>
        <a href="shared-budget-view.php?id=<?php echo (int) $sbNotice['id']; ?>"><?php echo htmlspecialchars($sbNotice['title']); ?></a>.
        The amount here (&#8369;<?php echo number_format((float) ($sbNoticeAmount ?? 0), 2); ?>) is this record's share of the
        &#8369;<?php echo number_format($sbNotice['total_budget'], 2); ?> total budget.
        <?php if ($sbNotice['mismatch']): ?>
        <br><strong>The shares now add up to &#8369;<?php echo number_format($sbNotice['allocated'], 2); ?>, which does not match the total.</strong>
        You can still save here; use the shared budget page to rebalance the shares.
        <?php endif; ?>
    </div>
</div>
<?php
    endif;
}
