<?php
if (!defined('ABSPATH')) exit;
/**
 * get action URL đúng với ngôn ngữ hiện tại (polylang).
 *
 * @param string $option_key     Tên option lưu ID mặc định (VD: 'mphb_dayuse_search_results_page')
 * @param string $fallback_slug  Slug fallback khi không có option hoặc bản dịch
 * @return string                URL trang đúng ngôn ngữ hiện tại
 */
function mphb_get_dayuse_page_url($option_key, $fallback_slug = '') {
    $default_page_id = get_option($option_key);
    $page_id = $default_page_id;
    // Nếu Polylang đang bật và có page ID → lấy bản dịch
    if (function_exists('pll_get_post') && $default_page_id) {
        $translated = pll_get_post($default_page_id, pll_current_language());
        if ($translated) {
            $page_id = $translated;
        }
    }
    // Nếu có ID thì trả permalink, ngược lại dùng fallback
    if ($page_id) {
        return get_permalink($page_id);
    }
    // Fallback URL
    return $fallback_slug ? site_url("/{$fallback_slug}") : site_url('/');
}
