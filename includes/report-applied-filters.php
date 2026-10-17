<?php
// "Applied filters" line for report headers. Expects $appliedFilters (label => value) from reports.php.
?>
<p class="report-filters small text-muted mb-0">
    <?php foreach ($appliedFilters as $filterLabel => $filterValue): ?>
    <span class="me-3 d-inline-block"><strong><?php echo htmlspecialchars($filterLabel); ?>:</strong> <?php echo htmlspecialchars($filterValue); ?></span>
    <?php endforeach; ?>
</p>
