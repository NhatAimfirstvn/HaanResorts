<?php
if (!defined('ABSPATH'))
    exit;

// === 1. UX BUILDER SETUP ===
add_action('ux_builder_setup', function () {
    // Lấy gallery post options
    $posts = get_posts(array(
        'post_type' => 'gallery',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));

    $post_options = array('' => __('-- Select Post --', 'flatsome'));
    if (!empty($posts)) {
        foreach ($posts as $post) {
            $post_options[$post->ID] = $post->post_title;
        }
    }

    // Đăng ký shortcode trong UX Builder
    add_ux_builder_shortcode('ux_gallery_slider', array(
        'name' => __('Gallery Slider', 'flatsome'),
        'category' => __('Content', 'flatsome'),
        'priority' => 1,
        'options' => array(
            'gallery_post' => array(
                'type' => 'select',
                'heading' => __('Select Gallery Post', 'flatsome'),
                'param_name' => 'gallery_post',
                'default' => '',
                'options' => $post_options,
            ),
            'slider_title' => array(
                'type' => 'textfield',
                'heading' => __('Slider Title', 'flatsome'),
                'param_name' => 'slider_title',
                'default' => '',
            ),
            'arrows' => array(
                'type' => 'select',
                'heading' => __('Show Arrows', 'flatsome'),
                'param_name' => 'arrows',
                'default' => 'true',
                'options' => array('true' => 'Yes', 'false' => 'No'),
            ),
            'dots' => array(
                'type' => 'select',
                'heading' => __('Show Dots', 'flatsome'),
                'param_name' => 'dots',
                'default' => 'true',
                'options' => array('true' => 'Yes', 'false' => 'No'),
            ),
            'autoplay' => array(
                'type' => 'select',
                'heading' => __('Autoplay', 'flatsome'),
                'param_name' => 'autoplay',
                'default' => 'false',
                'options' => array('true' => 'Yes', 'false' => 'No'),
            ),
            'autoplay_speed' => array(
                'type' => 'slider',
                'heading' => __('Autoplay Speed (ms)', 'flatsome'),
                'param_name' => 'autoplay_speed',
                'min' => 1000,
                'max' => 10000,
                'step' => 500,
                'default' => 3000,
            ),
            'image_size' => array(
                'type' => 'select',
                'heading' => __('Image Size', 'flatsome'),
                'param_name' => 'image_size',
                'default' => 'large',
                'options' => array(
                    'thumbnail' => 'Thumbnail',
                    'medium' => 'Medium',
                    'large' => 'Large',
                    'full' => 'Full Size',
                ),
            ),
            'height' => array(
                'type' => 'slider',
                'heading' => __('Image Height (%)', 'flatsome'),
                'param_name' => 'height',
                'min' => 30,
                'max' => 150,
                'step' => 5,
                'default' => 75,
                'responsive' => true,
            ),
            'image_limit' => array(
                'type' => 'slider',
                'heading' => __('Limit Number of Images', 'flatsome'),
                'param_name' => 'image_limit',
                'min' => 1,
                'max' => 50,
                'step' => 1,
                'default' => 10,
            ),
            'show_hover_title' => array(
                'type' => 'select',
                'heading' => __('Show Title/Category on Hover', 'flatsome'),
                'param_name' => 'show_hover_title',
                'default' => 'false',
                'options' => array(
                    'true' => __('Yes', 'flatsome'),
                    'false' => __('No', 'flatsome'),
                ),
            ),
            'hover_style' => array(
                'type' => 'select',
                'heading' => __('Hover Text Type', 'flatsome'),
                'param_name' => 'hover_style',
                'default' => 'title',
                'options' => array(
                    'title' => __('Post Title', 'flatsome'),
                    'category' => __('Category Name', 'flatsome'),
                ),
                'conditions' => 'show_hover_title == "true"',
            ),
        ),
    ));
});


// === 2. SHORTCODE FRONTEND RENDER ===
add_shortcode('ux_gallery_slider', function ($atts) {
    $atts = shortcode_atts(array(
        'gallery_post' => '',
        'slider_title' => '',
        'arrows' => 'true',
        'dots' => 'true',
        'autoplay' => 'false',
        'autoplay_speed' => 3000,
        'image_size' => 'large',
        'height' => '75',
        'height__sm' => '',
        'height__md' => '',
        'image_limit' => 10,
        'show_hover_title' => 'false',
        'hover_style' => 'title',
    ), $atts);

    // Get gallery post
    $post_id = null;

    if (!empty($atts['gallery_post'])) {
        $post_id = intval($atts['gallery_post']);
    }

    if (!$post_id) {
        return '<div style="padding:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:4px;text-align:center;margin:20px 0;">No gallery found. Please select a post.</div>';
    }

    // Get images field
    $images_data = get_post_meta($post_id, 'images', true);

    if (empty($images_data)) {
        return '<div style="padding:20px;background:#fff3cd;color:#856404;border:1px solid #ffeaa7;border-radius:4px;text-align:center;margin:20px 0;">No images found. Post ID: ' . $post_id . '</div>';
    }

    // Unserialize data
    $image_ids = maybe_unserialize($images_data);

    if (!is_array($image_ids) || empty($image_ids)) {
        return '<div style="padding:20px;background:#fff3cd;color:#856404;border:1px solid #ffeaa7;border-radius:4px;text-align:center;margin:20px 0;">Invalid image data.</div>';
    }

    // Apply limit
    $image_ids = array_slice($image_ids, 0, intval($atts['image_limit']));

    // Generate unique ID
    $slider_id = 'gallery-slider-' . rand(1000, 9999);
    
    // Get hover text
    $hover_text = '';
    if ($atts['show_hover_title'] === 'true') {
        if ($atts['hover_style'] === 'title') {
            $hover_text = get_the_title($post_id);
        } else { // category
            $terms = get_the_terms($post_id, 'gallery_category');
            if (!empty($terms) && !is_wp_error($terms)) {
                $hover_text = $terms[0]->name;
            }
        }
    }

    // Build slider HTML
    $html = '<div class="slider-wrapper relative" id="' . esc_attr($slider_id) . '">';

    if (!empty($atts['slider_title'])) {
        $html .= '<h3 style="text-align:center;margin-bottom:20px;">' . esc_html($atts['slider_title']) . '</h3>';
    }

    $html .= '<div class="slider slider-nav-circle slider-nav-large slider-nav-light slider-style-normal" 
                   data-flickity-options=\'{"cellAlign": "center", "imagesLoaded": true, "lazyLoad": 1, "freeScroll": false, "wrapAround": true, "autoPlay": ' . ($atts['autoplay'] === 'true' ? intval($atts['autoplay_speed']) : 'false') . ', "prevNextButtons": ' . ($atts['arrows'] === 'true' ? 'true' : 'false') . ', "contain": true, "adaptiveHeight": true, "dragThreshold": 10, "percentPosition": true, "pageDots": ' . ($atts['dots'] === 'true' ? 'true' : 'false') . ', "rightToLeft": false, "draggable": true, "selectedAttraction": 0.1, "friction": 0.6}\'>';

    foreach($image_ids as $index => $image_id){
        $image_thumb = wp_get_attachment_image_url($image_id, $atts['image_size']);
        $image_full  = wp_get_attachment_image_url($image_id, 'full');
        $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        
        // Chỉ load eager cho slide đầu tiên
        $loading = ($index === 0) ? 'eager' : 'lazy';
        
        // Sử dụng data-flickity-lazyload cho các ảnh sau
        if ($index === 0) {
            $img_tag = "<img src='" . esc_url($image_thumb) . "' alt='" . esc_attr($alt) . "' loading='eager' fetchpriority='high'/>";
        } else {
            $img_tag = "<img data-flickity-lazyload='" . esc_url($image_thumb) . "' alt='" . esc_attr($alt) . "'/>";
        }

        $html .= "<div class='slider-slide'><div class='img has-hover x md-x lg-x y md-y lg-y'><div class='img-inner dark'>
        <a href='" . esc_url($image_full) . "' data-fancybox='gallery-" . esc_attr($slider_id) . "'>
        {$img_tag}
        " . ($hover_text ? "<div class='hover-text'>" . esc_html($hover_text) . "</div>" : "") . "
        </a></div></div></div>";
    }

    $html .= '</div>'; // .slider
    $html .= '</div>'; // .slider-wrapper

    // Build responsive CSS for height
    $css = '';
    
    // Desktop (default)
    if (!empty($atts['height'])) {
        $css .= '#' . esc_attr($slider_id) . ' .img-inner {
            padding-top: ' . intval($atts['height']) . '% !important;
        }';
    }
    
    // Tablet (md)
    if (!empty($atts['height__md'])) {
        $css .= '@media (max-width: 849px) {
            #' . esc_attr($slider_id) . ' .img-inner {
                padding-top: ' . intval($atts['height__md']) . '% !important;
            }
        }';
    }
    
    // Mobile (sm)
    if (!empty($atts['height__sm'])) {
        $css .= '@media (max-width: 549px) {
            #' . esc_attr($slider_id) . ' .img-inner {
                padding-top: ' . intval($atts['height__sm']) . '% !important;
            }
        }';
    }

    // Add custom styles with responsive height
    $html .= '<style>
    ' . $css . '
    #' . esc_attr($slider_id) . ' .img-inner img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    #' . esc_attr($slider_id) . ' .slider-slide:not(.is-selected) img {
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    #' . esc_attr($slider_id) . ' .slider-slide.is-selected img {
        opacity: 1;
    }
    #' . esc_attr($slider_id) . ' .hover-text {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 15px 10px;
        color: #fff;
        background: rgba(0,0,0,0.7);
        opacity: 0;
        transition: opacity 0.3s ease;
        text-align: center;
        font-size: 16px;
        font-weight: 600;
        z-index: 2;
    }
    #' . esc_attr($slider_id) . ' .img-inner:hover .hover-text {
        opacity: 1;
    }
    </style>';

    // Add Fancybox init script
    $html .= '<script>
    jQuery(document).ready(function($) {
        if (typeof Fancybox !== "undefined") {
            Fancybox.bind("[data-fancybox=\'gallery-' . esc_js($slider_id) . '\']", {
                infinite: true,
                keyboard: true,
                preload: 1,
            });
        }
    });
    </script>';

    return $html;
});