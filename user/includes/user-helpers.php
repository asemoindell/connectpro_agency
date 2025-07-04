<?php
// Helper functions for user pages

// Helper function to safely format currency values
if (!function_exists('formatCurrency')) {
    function formatCurrency($value, $decimals = 2) {
        // Convert to float, defaulting to 0 if null, empty, or non-numeric
        $numericValue = is_numeric($value) ? (float)$value : 0;
        return number_format($numericValue, $decimals);
    }
}
?>
