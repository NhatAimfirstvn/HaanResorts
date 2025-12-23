<?php
// Register all plugin strings for Polylang translation
add_action('init', function () {
    if (!function_exists('pll_register_string')) {
        return;
    }

    $strings = [
        'Date of stay',
        'SEARCH',
        'accommodations found for',
        'accommodations selected',
        'Total',
        'Adults',
        'Children',
        'Subtotal',
        'View',
        'Size',
        'Bed Type',
        'Prices start at',
        'BOOKING',
        'of',
        'accommodations available',
        'CONFIRM',
        'REMOVE ALL',
        'Remove',
        'has been added to your reservation',
        'Check-in:',
        'Check-out:',
        'from',
        'until',
        'Select',
        'Rate',
        'Accommodation',
        'Choose Additional Services',
        'Full Guest Name',
        'Price Breakdown',
        'Your Information',
        'First Name',
        'Last Name',
        'Email',
        'Phone',
        'BOOK NOW',
        'Details',
        'Booking Details',
        'per day',
        'Reservation submitted',
        'Details of your reservation have just been sent to you in a confirmation email. Please check your inbox to complete booking.',
        'Adults and Children are required for all rooms.',
    ];

    foreach ($strings as $str) {
        pll_register_string(sanitize_title($str), $str, 'MPHB Dayuse Booking');
    }
});