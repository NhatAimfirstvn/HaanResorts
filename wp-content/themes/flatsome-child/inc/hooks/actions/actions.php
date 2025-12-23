<?php
if (!defined('ABSPATH'))
    exit;

/* ==========================================================
1. action cho MPHB
========================================================== */
//
add_action('mphb_sc_search_results_after_room', function () {
    global $checkInDate, $checkOutDate;

    $roomType = MPHB()->getCurrentRoomType();
    $roomTypeId = $roomType->getOriginalId();

    $allRoomIds = array_map('intval', MPHB()->getRoomPersistence()->findAllIdsByType($roomTypeId));
    $lockedRoomIds = isset($GLOBALS['mphb_custom_locked_room_ids']) ? array_map('intval', $GLOBALS['mphb_custom_locked_room_ids']) : [];
    $availableRooms = array_diff($allRoomIds, $lockedRoomIds);
	
    if (!empty($lockedRoomIds)) {
        //error_log("Locked rooms for room type $roomTypeId ({$roomType->getTitle()}): " . implode(', ', $lockedRoomIds));
    }
    //error_log("=== AVAILABLE ROOMS FOR ROOM TYPE $roomTypeId ({$roomType->getTitle()}) ===");
    if (!empty($availableRooms)) {
        foreach ($availableRooms as $roomId) {
            $room = MPHB()->getRoomRepository()->findById($roomId);
            if ($room) {
                //error_log("Room ID: $roomId | Title: " . $room->getTitle());
            }
        }
    } else {
        //error_log("No available rooms for this room type");
    }
});
// ẩn recommendation của MPHB
add_action('mphb_sc_search_results_recommendation_before', function () {
    $lockedRoomTypes = $GLOBALS['mphb_custom_locked_room_types'] ?? [];
    $lockedRoomIds = $GLOBALS['mphb_custom_locked_room_ids'] ?? [];
    if (!empty($lockedRoomTypes) || !empty($lockedRoomIds)) {
        // Ghi log để kiểm tra
        error_log('Recommendation hidden — all rooms locked.');
        // In ra đoạn script nhỏ để ẩn form (phòng trường hợp render xong)
        echo '<style>.mphb-recommendation { display:none !important; }</style>';
    }
}, 5);
/* ==========================================================
2. ENQUEUE TẤT CẢ CSS & JS
========================================================== */
add_action('wp_enqueue_scripts', function () {
    // === Font Awesome ===
	wp_enqueue_style(
		'font-awesome-6-free',
		'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
		[],
		'6.5.0'
	);
    // === Fancybox ===
    wp_enqueue_style(
        'fancybox',
        'https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css',
        [],
        '3.5.7'
    );
    wp_enqueue_script(
        'fancybox',
        'https://cdn.jsdelivr.net/npm/@fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js',
        ['jquery'],
        '3.5.7',
        true
    );
    // === JS nội tuyến: chỉ chọn 1 dịch vụ ===
    wp_add_inline_script('jquery', "
        document.addEventListener('DOMContentLoaded', function() {
            const serviceLists = document.querySelectorAll('.mphb_checkout-services-list');
            serviceLists.forEach(list => {
                const checkboxes = list.querySelectorAll('input[type=\"checkbox\"]');
                checkboxes.forEach(cb => {
                    cb.addEventListener('change', () => {
                        if (cb.checked) {
                            checkboxes.forEach(other => {
                                if (other !== cb) other.checked = false;
                            });
                        }
                    });
                });
            });
        });
    ");
    wp_enqueue_style(handle: 'parent-style', src: get_template_directory_uri() . '/style.css');
    // Thêm file custom_style_mphb.css
    wp_enqueue_style(
        'custom-style-mphb', // Handle
        get_stylesheet_directory_uri() . '/assets/style/custom_style_mphb.css', // Đường dẫn tới file CSS
        array('parent-style'), // File này phụ thuộc parent-style
        filemtime(get_stylesheet_directory() . '/assets/style/custom_style_mphb.css') // Cache busting
    );
});

/* ==========================================================
3. POST-TYPE GALLERY
========================================================== */
//Hiển thị category trong trang Admin gallery
add_action('manage_gallery_posts_custom_column', function ($column, $post_id) {
    if ($column === 'gallery_category') {
        $terms = get_the_terms($post_id, 'gallery_category');
        if (!empty($terms) && !is_wp_error($terms)) {
            echo esc_html(join(', ', wp_list_pluck($terms, 'name')));
        } else {
            echo '<em>Chưa có</em>';
        }
    }
}, 10, 2);
//Đồng bộ ảnh gallery các lang khác khi post default update
add_action('save_post_gallery', function($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!function_exists('pll_get_post_language')) {
        return;
    }
    $lang = pll_get_post_language($post_id);
    if (!$lang) return;
    $default_lang = pll_default_language();
    if ($lang !== $default_lang) return;
    $translations = pll_get_post_translations($post_id);
    if (empty($translations) || !is_array($translations)) return;
    if (!function_exists('get_field') || !function_exists('update_field')) return;
    $gallery = get_field('images', $post_id);
    $field_key = get_post_meta($post_id, '_images', true);
    if (empty($gallery) || empty($field_key)) return;
    foreach ($translations as $tr_lang => $tr_id) {
        if ($tr_lang === $lang || !$tr_id) continue;
        update_post_meta($tr_id, 'images', maybe_unserialize($gallery));
        update_post_meta($tr_id, '_images', $field_key);
        update_field('images', maybe_unserialize($gallery), $tr_id);
    }

}, 20, 3);
// Hook đồng bộ khi tạo bản dịch mới
add_action('pll_translate_post', function($post_id, $lang, $from_post_id) {
    $gallery = get_post_meta($from_post_id, 'images', true);
    $field_key = get_post_meta($from_post_id, '_images', true);
    if (!empty($gallery) && !empty($field_key)) {
        update_post_meta($post_id, 'images', $gallery);
        update_post_meta($post_id, '_images', $field_key);
        if (function_exists('update_field')) {
            update_field('images', maybe_unserialize($gallery), $post_id);
        }
    }

}, 10, 3);
add_action('wp_footer', function () {
    ?>
    <script>
        (function() {
            /**
             * Xử lý hiển thị/ẩn chi tiết giá (Price Breakdown)
             */
            function togglePriceBreakdown() {
                const roomBlocks = document.querySelectorAll('.mphb-room-details');
                const wrapper = document.querySelector('.mphb-room-price-breakdown-wrapper');
                const totalPriceField = document.querySelector('.mphb-total-price-field');      
                if (!wrapper || roomBlocks.length === 0) return;        
                let allValid = true;
                roomBlocks.forEach(block => {
                    const adultSelect = block.querySelector('select[name*="[adults]"]');
                    const childrenSelect = block.querySelector('select[name*="[children]"]');
                    
                    const adultsValue = adultSelect ? adultSelect.value : '';
                    const childrenValue = childrenSelect ? childrenSelect.value : '';

                    if (adultsValue === '' || childrenValue === '') {
                        allValid = false;
                        return;
                    }
                    const adults = parseInt(adultsValue, 10);
                    const children = parseInt(childrenValue, 10);

                    if (adults < 1 || children < 0) {
                        allValid = false;
                    }
                });
                if (allValid) {
                    wrapper.classList.remove('mphb-force-hide');
                } else {
                    wrapper.classList.add('mphb-force-hide');
                }
                if (totalPriceField) {
                    totalPriceField.classList.toggle('mphb-force-hide', !allValid);
                }
            }
            /**
             * Xử lý cấu trúc Layout Room (Wrap text elements)
             */
            function setupRoomLayouts() {
                document.querySelectorAll('.mphb-room-type').forEach(room => {
                    const imgSection = room.querySelector('.mphb-room-type-images');
                    if (!imgSection || room.querySelector('.mphb-room-text')) return;

                    const textElements = [];
                    let next = imgSection.nextElementSibling;
                    while (next) {
                        textElements.push(next);
                        next = next.nextElementSibling;
                    }
                    const wrapper = document.createElement('div');
                    wrapper.classList.add('mphb-room-text');
                    textElements.forEach(el => wrapper.appendChild(el));
                    room.appendChild(wrapper);
                    room.classList.add('mphb-room-flex');
                });
            }
            document.addEventListener('change', function(e) {
                if (
                    e.target.matches('select[name*="[adults]"]') ||
                    e.target.matches('select[name*="[children]"]')
                ) {
                    setTimeout(togglePriceBreakdown, 50);
                }
            });
            const observer = new MutationObserver(() => {
                togglePriceBreakdown();
                setupRoomLayouts();
            });
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            document.addEventListener('DOMContentLoaded', () => {
                togglePriceBreakdown();
                setupRoomLayouts();
            });
        })();
    </script>
    <?php
});
