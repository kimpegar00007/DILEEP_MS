<?php
// =========================================================
// Status badge classes shared by the card page and its quick-view API.
// text-bg-* picks a readable text colour for each background (dark text on
// the yellow/cyan badges), which plain bg-* + white text did not.
// =========================================================

if (!function_exists('statusBadgeClass')) {
    function statusBadgeClass(?string $status): string {
        $classes = [
            'pending'     => 'text-bg-secondary',
            'approved'    => 'text-bg-primary',
            'implemented' => 'text-bg-success',
            'liquidated'  => 'text-bg-warning',
            'monitored'   => 'text-bg-info',
        ];
        return $classes[$status ?? ''] ?? 'text-bg-secondary';
    }
}
