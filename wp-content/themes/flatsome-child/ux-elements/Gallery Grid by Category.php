<?php

/**
 * Gallery Grid by Category - ADVANCED VERSION with Fancybox, Filters, Responsive, Hover
 */

if (!defined('ABSPATH')) exit;
// === 1. UX BUILDER SETUP ===
add_action('ux_builder_setup', function () {
    $categories = get_terms([
        'taxonomy' => 'gallery_category',
        'hide_empty' => false,
    ]);
    $cat_options = ['' => '-- Select Category --'];
    if (!is_wp_error($categories) && !empty($categories)) {
        foreach ($categories as $cat) {
            $cat_options[$cat->term_id] = $cat->name;
        }
    }
    add_ux_builder_shortcode('ux_gallery_grid_simple', [
        'name' => __('Gallery Grid Advanced', 'flatsome'),
        'category' => __('Content', 'flatsome'),
        'priority' => 1,
        'options' => [
            // === SOURCE ===
            'gallery_category' => [
                'type' => 'select',
                'heading' => __('Default Category', 'flatsome'),
                'param_name' => 'gallery_category',
                'default' => '',
                'options' => $cat_options,
            ],
            // === FILTER ===
            'filter_section' => [
                'type' => 'group',
                'heading' => __('Filter Settings', 'flatsome'),
                'options' => [
                    'show_category_filter' => [
                        'type' => 'select',
                        'heading' => __('Show Filter', 'flatsome'),
                        'default' => 'false',
                        'options' => [
                            'false' => __('No', 'flatsome'),
                            'true' => __('Yes', 'flatsome'),
                        ],
                    ],
                    'filter_style' => [
                        'type' => 'select',
                        'heading' => __('Filter Style', 'flatsome'),
                        'default' => 'buttons',
                        'options' => [
                            'buttons' => __('Buttons', 'flatsome'),
                            'dropdown' => __('Dropdown', 'flatsome'),
                        ],
                        'conditions' => 'show_category_filter === "true"',
                    ],
                    'filter_show_count' => [
                        'type' => 'select',
                        'heading' => __('Show Count', 'flatsome'),
                        'default' => 'true',
                        'options' => [
                            'true' => __('Yes', 'flatsome'),
                            'false' => __('No', 'flatsome'),
                        ],
                        'conditions' => 'show_category_filter === "true"',
                    ],
                ],
            ],
            // === LAYOUT ===
            'layout_section' => [
                'type' => 'group',
                'heading' => __('Layout', 'flatsome'),
                'options' => [
                    'columns' => [
                        'type' => 'slider',
                        'heading' => __('Columns Desktop', 'flatsome'),
                        'default' => 3,
                        'min' => 1,
                        'max' => 6,
                    ],
                    'columns_tablet' => [
                        'type' => 'slider',
                        'heading' => __('Columns Tablet', 'flatsome'),
                        'default' => 2,
                        'min' => 1,
                        'max' => 4,
                    ],
                    'columns_mobile' => [
                        'type' => 'slider',
                        'heading' => __('Columns Mobile', 'flatsome'),
                        'default' => 1,
                        'min' => 1,
                        'max' => 2,
                    ],
                    'gap' => [
                        'type' => 'slider',
                        'heading' => __('Gap (px)', 'flatsome'),
                        'default' => 20,
                        'min' => 0,
                        'max' => 50,
                    ],
                    'image_height' => [
                        'type' => 'slider',
                        'heading' => __('Image Height (px)', 'flatsome'),
                        'default' => 300,
                        'min' => 150,
                        'max' => 600,
                    ],
                ],
            ],
            // === DISPLAY ===
            'display_section' => [
                'type' => 'group',
                'heading' => __('Display', 'flatsome'),
                'options' => [
                    'show_title' => [
                        'type' => 'select',
                        'heading' => __('Show Title', 'flatsome'),
                        'default' => 'true',
                        'options' => ['true' => 'Yes', 'false' => 'No'],
                    ],
                    'title_position' => [
                        'type' => 'select',
                        'heading' => __('Title Position', 'flatsome'),
                        'default' => 'overlay',
                        'options' => [
                            'overlay' => __('Overlay', 'flatsome'),
                            'below' => __('Below', 'flatsome'),
                        ],
                        'conditions' => 'show_title === "true"',
                    ],
                    'show_image_count' => [
                        'type' => 'select',
                        'heading' => __('Show Image Count', 'flatsome'),
                        'default' => 'true',
                        'options' => ['true' => 'Yes', 'false' => 'No'],
                    ],
                    'posts_per_page' => [
                        'type' => 'slider',
                        'heading' => __('Posts Limit', 'flatsome'),
                        'default' => -1,
                        'min' => -1,
                        'max' => 50,
                    ],
                ],
            ],

            // === HOVER ===
            'hover_section' => [
                'type' => 'group',
                'heading' => __('Hover', 'flatsome'),
                'options' => [
                    'hover_effect' => [
                        'type' => 'select',
                        'heading' => __('Hover Effect', 'flatsome'),
                        'default' => 'zoom',
                        'options' => [
                            'none' => 'None',
                            'zoom' => 'Zoom In',
                            'lift' => 'Lift Up',
                            'blur' => 'Blur',
                        ],
                    ],
                    'overlay_color' => [
                        'type' => 'colorpicker',
                        'heading' => __('Overlay Color', 'flatsome'),
                        'default' => 'rgba(0,0,0,0.4)',
                    ],
                ],
            ],

            // === LIGHTBOX ===
            'lightbox_section' => [
                'type' => 'group',
                'heading' => __('Lightbox', 'flatsome'),
                'options' => [
                    'enable_lightbox' => [
                        'type' => 'select',
                        'heading' => __('Enable Lightbox', 'flatsome'),
                        'default' => 'true',
                        'options' => ['true' => 'Yes', 'false' => 'No'],
                    ],
                    'lightbox_mode' => [
                        'type' => 'select',
                        'heading' => __('Lightbox Scope', 'flatsome'),
                        'default' => 'all',
                        'options' => [
                            'all' => __('All Images in Category', 'flatsome'),
                            'post' => __('Per Post Only', 'flatsome'),
                        ],
                        'conditions' => 'enable_lightbox === "true"',
                    ],
                ],
            ],

        ],
    ]);
});

// === 2. AJAX HANDLER ===
add_action('wp_ajax_load_gallery_cat', 'ajax_load_gallery_cat');
add_action('wp_ajax_nopriv_load_gallery_cat', 'ajax_load_gallery_cat');

function ajax_load_gallery_cat()
{
    check_ajax_referer('gallery_nonce', 'nonce');

    $cat_id = intval($_POST['cat_id']);
    $atts = $_POST['atts'];
    $atts = wp_parse_args($atts, [
        'columns' => 3,
        'columns_tablet' => 2,
        'columns_mobile' => 1,
        'gap' => 20,
        'image_height' => 300,
        'show_title' => 'true',
        'title_position' => 'overlay',
        'show_image_count' => 'true',
        'hover_effect' => 'zoom',
        'overlay_color' => 'rgba(0,0,0,0.4)',
        'enable_lightbox' => 'true',
        'lightbox_mode' => 'all',
        'posts_per_page' => -1,
    ]);

    ob_start();
    render_gallery_items($cat_id, $atts);
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}

// === 3. RENDER GALLERY ITEMS ===
function render_gallery_items($cat_id, $atts)
{
    $posts = get_posts([
        'post_type' => 'gallery',
        'posts_per_page' => intval($atts['posts_per_page']),
        'tax_query' => $cat_id ? [[
            'taxonomy' => 'gallery_category',
            'field' => 'term_id',
            'terms' => $cat_id,
        ]] : [],
    ]);

    if (empty($posts)) {
        echo '<p>' . __('No galleries found', 'flatsome') . '</p>';
        return;
    }

    $gallery_id = 'fancy-' . ($cat_id ?: 'all') . '-' . uniqid();
    $is_lightbox_all = $atts['enable_lightbox'] === 'true' && $atts['lightbox_mode'] === 'all';

    $css = "
    #{$gallery_id}-wrapper .gallery-items {
        display: grid;
        grid-template-columns: repeat({$atts['columns']}, 1fr);
        gap: {$atts['gap']}px;
    }
    .gallery-item {
        padding: 0;
    }
    @media (max-width: 849px) {
        #{$gallery_id}-wrapper .gallery-items {
            grid-template-columns: repeat({$atts['columns_tablet']}, 1fr);
        }
    }
    @media (max-width: 549px) {
        #{$gallery_id}-wrapper .gallery-items {
            grid-template-columns: repeat({$atts['columns_mobile']}, 1fr);
        }
    }
    #{$gallery_id}-wrapper .gallery-item img {
        height: {$atts['image_height']}px;
        object-fit: cover;
        transition: all 0.3s ease;
        width: 100%;
    }
    /* Category Filter Buttons */
    .gallery-filter{
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    .gallery-filter .cat-btn {
        position: relative;
        font-size: 12px;
        color: var(--primary-color, #007bff);
        cursor: pointer;
        padding: 6px 8px;
        margin:0px;
        transition: all 0.3s ease;
        background: transparent;
        border: none;
    }
    .gallery-filter .cat-btn::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 0%;
        height: 2px;
        background-color: var(--primary-color, #007bff);
        transition: width 0.3s ease;
        }
        /* Khi hover hoặc active => có underline */
        .gallery-filter .cat-btn:hover::after,
        .gallery-filter .cat-btn.active::after {
        width: 100%;
    }
    /* Nếu bạn muốn đổi màu text khi active */
    .gallery-filter .cat-btn.active {
        color: var(--primary-color, #007bff);
    }
    .loading .spinner {
        width: 28px;
        height: 28px;
        border: 3px solid #ccc;
        border-top: 3px solid var(--fs-color-secondary);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        display: inline-block;
    }
    @keyframes spin {
    to { transform: rotate(360deg); }
    }";
    // Hover Effects
    if ($atts['hover_effect'] === 'zoom') {
        $css .= "#{$gallery_id}-wrapper .gallery-item:hover img { transform: scale(1.05); }";
    } elseif ($atts['hover_effect'] === 'lift') {
        $css .= "#{$gallery_id}-wrapper .gallery-item:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }";
    } elseif ($atts['hover_effect'] === 'blur') {
        $css .= "#{$gallery_id}-wrapper .gallery-item:hover img { filter: blur(2px); }";
    }
    echo '<style>' . $css . '</style>';
    echo '<div id="' . $gallery_id . '-wrapper">';
    echo '<div class="gallery-items">';
    foreach ($posts as $post) {
        $images = maybe_unserialize(get_post_meta($post->ID, 'images', true));
        if (!is_array($images) || empty($images)) continue;

        $first_id = $images[0];
        $thumb = wp_get_attachment_image_url($first_id, 'large');
        $full = wp_get_attachment_image_url($first_id, 'full');
        if (!$thumb || !$full) continue;

        $item_id = $is_lightbox_all ? $gallery_id : 'post-' . $post->ID;
        $image_count = count($images);
        echo '<div class="gallery-item" style="position:relative;overflow:hidden;">';
        // Lightbox Link
        if ($atts['enable_lightbox'] === 'true') {
            echo '<a href="' . esc_url($full) . '" data-fancybox="' . $item_id . '" data-caption="' . esc_attr($post->post_title) . '">';
        }
        echo '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($post->post_title) . '">';
        if ($atts['enable_lightbox'] === 'true') echo '</a>';
        // Title Overlay
        if ($atts['show_title'] === 'true' && $atts['title_position'] === 'overlay') {
            echo '<div style="position:absolute;bottom:0;left:0;right:0;padding:15px;background:linear-gradient(transparent,rgba(0,0,0,0.9));color:#fff;">';
            echo '<h3 style="color:white;font-size:22px;">' . esc_html($post->post_title) . '</h3>';
            if ($atts['show_image_count'] === 'true') {
                echo '<small>' . $image_count . ' ' . _n('image', 'images', $image_count, 'flatsome') . '</small>';
            }
            echo '</div>';
        }
        // Hidden images for lightbox
        if ($atts['enable_lightbox'] === 'true') {
            for ($i = 1; $i < $image_count; $i++) {
                $url = wp_get_attachment_image_url($images[$i], 'full');
                if ($url) {
                    echo '<a href="' . esc_url($url) . '" data-fancybox="' . $item_id . '" style="display:none;"></a>';
                }
            }
        }
        // Title Below
        if ($atts['show_title'] === 'true' && $atts['title_position'] === 'below') {
            echo '<div style="padding:10px 0;text-align:center;">';
            echo '<h4 style="margin:0;font-size:15px;">' . esc_html($post->post_title) . '</h4>';
            if ($atts['show_image_count'] === 'true') {
                echo '<small style="color:#777;">' . $image_count . ' ' . _n('image', 'images', $image_count, 'flatsome') . '</small>';
            }
            echo '</div>';
        }

        echo '</div>';
    }

    echo '</div></div>';
}
// === 4. SHORTCODE ===
add_shortcode('ux_gallery_grid_simple', function ($atts) {
    $atts = shortcode_atts([
        'gallery_category' => '',
        'show_category_filter' => 'false',
        'filter_style' => 'buttons',
        'filter_show_count' => 'true',
        'columns' => 3,
        'columns_tablet' => 2,
        'columns_mobile' => 1,
        'gap' => 20,
        'image_height' => 300,
        'show_title' => 'true',
        'title_position' => 'overlay',
        'show_image_count' => 'true',
        'posts_per_page' => -1,
        'hover_effect' => 'zoom',
        'overlay_color' => 'rgba(0,0,0,0.4)',
        'enable_lightbox' => 'true',
        'lightbox_mode' => 'all',
    ], $atts);

    $grid_id = 'gallery-' . uniqid();
    $nonce = wp_create_nonce('gallery_nonce');
    $cats = get_terms(['taxonomy' => 'gallery_category', 'hide_empty' => true]);

    ob_start(); ?>

    <div id="<?php echo $grid_id; ?>" class="gallery-wrapper-advanced" data-atts="<?php echo esc_attr(json_encode($atts)); ?>">

        <?php if ($atts['show_category_filter'] === 'true' && !empty($cats)): ?>
            <div class="gallery-filter" style="text-align:center;">
                <?php if ($atts['filter_style'] === 'dropdown'): ?>
                    <select class="cat-select">
                        <option value=""><?php _e('All Categories', 'flatsome'); ?></option>
                        <?php foreach ($cats as $cat):
                            $count = $atts['filter_show_count'] === 'true' ? ' (' . $cat->count . ')' : '';
                        ?>
                            <option value="<?php echo $cat->term_id; ?>"><?php echo $cat->name . $count; ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <?php foreach ($cats as $cat):
                        $count = $atts['filter_show_count'] === 'true' ? ' (' . $cat->count . ')' : '';
                    ?>
                        <button class="cat-btn" data-cat="<?php echo $cat->term_id; ?>">
                            <?php echo $cat->name; ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="loading" style="display:none;text-align:center;margin:20px 0;">
                <div class="spinner"></div>
            </div>
        <?php endif; ?>
        <div class="gallery-content">
            <?php render_gallery_items($atts['gallery_category'], $atts); ?>
        </div>
    </div>

    <script>
        jQuery(function($) {
            var wrapper = $('#<?php echo $grid_id; ?>');
            var content = wrapper.find('.gallery-content');
            var loading = wrapper.find('.loading');
            var atts = wrapper.data('atts');

            function initFancybox() {
                if (typeof Fancybox !== 'undefined') {
                    Fancybox.bind(wrapper[0], '[data-fancybox]', {
                        Thumbs: {
                            autoStart: false
                        },
                        Hash: false,
                        infinite: true,
                    });
                }
            }
            initFancybox();
            function loadCategory(catId) {
                loading.show();
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'load_gallery_cat',
                    cat_id: catId,
                    atts: atts,
                    nonce: '<?php echo $nonce; ?>'
                }, function(res) {
                    if (res.success) {
                        content.html(res.data.html);
                        initFancybox();
                    }
                    loading.hide();
                });
            }
            wrapper.on('click', '.cat-btn', function() {
                var btn = $(this);
                // Xóa class active khỏi tất cả button
                wrapper.find('.cat-btn').removeClass('active');
                // Thêm class active vào nút được click
                btn.addClass('active');
                // Gọi hàm load category
                loadCategory(btn.data('cat'));
            });

            wrapper.on('change', '.cat-select', function() {
                loadCategory($(this).val());
            });
            // Load mặc định nếu có category sẵn
            <?php if ($atts['gallery_category'] && $atts['show_category_filter'] === 'true'): ?>
                var defaultBtn = wrapper.find('.cat-btn[data-cat="<?php echo intval($atts['gallery_category']); ?>"]');
                if (defaultBtn.length) {
                    defaultBtn.addClass('active');
                }
                loadCategory(<?php echo intval($atts['gallery_category']); ?>);
            <?php endif; ?>
        });
    </script>

<?php
    return ob_get_clean();
});
