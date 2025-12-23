<?php
/*
Plugin Name: MPHB Dayuse Booking
Description: Custom Day-Use booking workflow cho MotoPress Hotel Booking
Version: 1.0
Author: Pham Xuan Nhat
*/

if (!defined('ABSPATH'))
    exit;

// Load languages
foreach (glob(__DIR__ . '/languages/*.php') as $file) {
    require_once $file;
}

// Load helpers
foreach (glob(__DIR__ . '/includes/helpers/*.php') as $file) {
    require_once $file;
}

// Load shortcodes
foreach (glob(__DIR__ . '/includes/shortcodes/*.php') as $file) {
    require_once $file;
}

// Register all shortcode classes
add_action('init', function () {
    if (class_exists('MPHB_Dayuse_Search_Shortcode')) {
        new MPHB_Dayuse_Search_Shortcode();
    }
    if (class_exists('MPHB_Dayuse_Results_Shortcode')) {
        new MPHB_Dayuse_Results_Shortcode();
    }
    if (class_exists('MPHB_Dayuse_Confirm_Shortcode')) {
        new MPHB_Dayuse_Confirm_Shortcode();
    }
});

// Include class
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-results.php';