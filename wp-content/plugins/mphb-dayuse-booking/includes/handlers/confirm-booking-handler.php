<?php
if (!defined('ABSPATH'))
    exit;
/**
 * Xử lý form xác nhận đặt phòng (POST)
 */
add_action('init', function () {
    if (empty($_POST['custom_booking_submit']))
        return;
    // === Kiểm tra nonce
    if (!isset($_POST['mphb_custom_booking_nonce']) || !wp_verify_nonce($_POST['mphb_custom_booking_nonce'], 'mphb_custom_booking_action')) {
        wp_die('Nonce không hợp lệ');
    }
    // === Sanitize input data
    $first_name = sanitize_text_field($_POST['mphb_first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['mphb_last_name'] ?? '');
    $email = sanitize_email($_POST['mphb_email'] ?? '');
    $phone = sanitize_text_field($_POST['mphb_phone'] ?? '');
    $check_in = sanitize_text_field($_POST['check_in_date'] ?? '');
    $check_out = sanitize_text_field($_POST['check_out_date'] ?? '');
    $rooms_post = $_POST['rooms'] ?? [];
    //error_log("Customer Details: first_name=$first_name, last_name=$last_name, email=$email, phone=$phone");
    //error_log("Booking Dates: check_in=$check_in, check_out=$check_out");
    //error_log("Rooms Data: " . print_r($rooms_post, true));

    if (empty($check_in) || empty($check_out) || $check_in !== $check_out || empty($rooms_post)) {
        wp_die('Dữ liệu đặt phòng không hợp lệ.');
    }
    // === Initialize breakdown and totals
    $total_price = 0;
    $breakdown = ['rooms' => [], 'total' => 0];
    $booking_rooms = [];
    $booking_services = [];
    // === KIỂM TRA TRÙNG LỊCH TRƯỚC KHI TẠO BOOKING ===
    $locked_data = get_locked_rooms_and_types($check_in, $check_out);
    $locked_room_ids = $locked_data['locked_room_ids'];
    $locked_room_types = $locked_data['locked_room_types'];

    if (!empty($locked_room_ids) || !empty($locked_room_types)) {
        foreach ($rooms_post as $room_type_id => $instances) {
            foreach ($instances as $idx => $instance) {
                $room_id = intval($instance['room_id'] ?? 0);
                if (in_array($room_id, $locked_room_ids, true)) {
                    wp_die("Phòng bạn chọn đã được đặt trong khoảng thời gian này. Vui lòng chọn phòng khác.");
                }
                if (in_array($room_type_id, $locked_room_types, true)) {
                    wp_die("Loại phòng bạn chọn đã full trong khoảng thời gian này. Vui lòng chọn loại khác.");
                }
            }
        }
    }
    // === Create booking
    $booking_id = wp_insert_post([
        'post_type' => 'mphb_booking',
        'post_title' => '',
        'post_status' => 'draft',
        'post_name' => '',
    ]);
    if ($booking_id && !is_wp_error($booking_id)) {
        wp_update_post([
            'ID' => $booking_id,
            'post_name' => $booking_id,
        ]);
    } else {
        wp_die('Lỗi tạo booking');
    }
    // === Process each room type and instance
    foreach ($rooms_post as $room_type_id => $instances) {
        $room_type_post = get_post($room_type_id);
        $room_type_name = $room_type_post ? $room_type_post->post_title : 'Room';
        //error_log("Processing room_type_id=$room_type_id, room_type_name=$room_type_name");
        foreach ($instances as $idx => $instance) {
            $adults = intval($instance['adults'] ?? 1);
            $children = intval($instance['children'] ?? 0);
            $guest_name = sanitize_text_field($instance['guest_name'] ?? '');
            $service_ids = array_map('intval', explode(',', $instance['services'] ?? ''));
            $room_id = intval($instance['room_id'] ?? 0);
            $rate_id = intval($instance['rate_id'] ?? 0);
            //error_log("Room instance index=$idx, room_id=$room_id, rate_id=$rate_id, adults=$adults, children=$children, guest_name=$guest_name, service_ids=" . print_r($service_ids, true));
            if ($room_id === 0) {
                wp_die("Lỗi: Không có room_id hợp lệ cho phòng $idx của loại phòng $room_type_id.");
            }
            if ($rate_id === 0) {
                wp_die("Lỗi: Không có rate_id hợp lệ cho phòng $idx của loại phòng $room_type_id.");
            }
            // === Get rate title
            $rate_post = get_post($rate_id);
            $rate_name = $rate_post ? $rate_post->post_title : 'Unknown Rate';
            // === Get room price
            $room_price = get_dayuse_price_for_room_type($room_type_id, $check_in, 'day-use') ?: 0;
            //error_log("Room price for room_type_id=$room_type_id, check_in=$check_in: $room_price");
            // === Process services
            $services_list = [];
            $services_total = 0;
            foreach ($service_ids as $sv_id) {
                if (!$sv_id) {
                    //error_log("Invalid service ID: $sv_id for room_type_id=$room_type_id, instance=$idx");
                    continue;
                }
                $price = floatval(get_post_meta($sv_id, 'mphb_price', true));
                $service_title = get_the_title($sv_id);
                $services_total += $price;
                $services_list[] = [
                    'title' => $service_title,
                    'details' => htmlspecialchars(
                        '₫' . number_format($price, 0, ',', '.'),
                        ENT_QUOTES
                    ),
                    'total' => $price
                ];
                //error_log("Service ID=$sv_id, title=$service_title, price=$price");
            }
            // === Calculate line total
            $line_total = $room_price + $services_total;
            $total_price += $line_total;
            //error_log("Line total for room_type_id=$room_type_id, instance=$idx: $line_total (room_price=$room_price, services_total=$services_total)");
            // === Add to breakdown
            $breakdown['rooms'][] = [
                'room' => [
                    'type' => $room_type_name,
                    'rate' => $rate_name,
                    'list' => [$check_in => $room_price],
                    'total' => $room_price,
                    'discount' => 0,
                    'discount_total' => $room_price,
                    'adults' => $adults,
                    'children' => $children,
                    'children_capacity' => get_post_meta($room_type_id, 'mphb_children_capacity', true) ?: 1,
                ],
                'services' => [
                    'list' => $services_list,
                    'total' => $services_total,
                    'discount' => 0,
                    'discount_total' => $services_total
                ],
                'fees' => [
                    'list' => [],
                    'total' => 0,
                    'discount' => 0,
                    'discount_total' => 0
                ],
                'taxes' => [
                    'room' => ['list' => [], 'total' => 0],
                    'services' => ['list' => [], 'total' => 0],
                    'fees' => ['list' => [], 'total' => 0]
                ],
                'total' => $line_total,
                'discount' => 0,
                'discount_total' => $line_total,
            ];
            // === Create reserved room
            $reserved_room_id = wp_insert_post([
                'post_type' => 'mphb_reserved_room',
                'post_title' => '',
                'post_name' => $room_id,
                'post_status' => 'publish',
                'post_parent' => $booking_id,
            ]);
            if ($reserved_room_id) {
                //error_log("Created reserved_room_id=$reserved_room_id for room_type_id=$room_type_id");
                update_post_meta($reserved_room_id, '_mphb_room_id', $room_id);
                update_post_meta($reserved_room_id, '_mphb_rate_id', $rate_id);
                update_post_meta($reserved_room_id, '_mphb_adults', $adults);
                update_post_meta($reserved_room_id, '_mphb_children', $children);
                update_post_meta($reserved_room_id, '_mphb_guest_name', $guest_name);
                // === Save services for reserved room
                if (!empty($service_ids)) {

                    $services_meta = [];
                    foreach ($service_ids as $sv_id) {
                        $services_meta[] = [
                            'id' => $sv_id,
                            'adults' => $adults,
                            'quantity' => 1
                        ];
                    }
                    update_post_meta($reserved_room_id, '_mphb_services', $services_meta);
                    //error_log("Saved services for reserved_room_id=$reserved_room_id: " . print_r($services_meta, true));
                } else {
                    //error_log("No services saved for reserved_room_id=$reserved_room_id");
                }
                $booking_rooms[$reserved_room_id] = [
                    'rate_id' => $rate_id,
                    'adults' => $adults,
                    'children' => $children
                ];
            } else {
                wp_die("Lỗi: Không thể tạo reserved_room cho phòng $idx của loại phòng $room_type_id.");
            }
            $booking_services = array_merge($booking_services, $service_ids);
        }
    }
    // === Set breakdown total
    $breakdown['total'] = $total_price;
    //error_log("Final price breakdown: " . print_r($breakdown, true));
    //error_log("Total price: $total_price");
    // === Save booking metadata
    update_post_meta($booking_id, 'mphb_check_in_date', $check_in);
    update_post_meta($booking_id, 'mphb_check_out_date', $check_out);
    update_post_meta($booking_id, 'mphb_first_name', $first_name);
    update_post_meta($booking_id, 'mphb_last_name', $last_name);
    update_post_meta($booking_id, 'mphb_email', $email);
    update_post_meta($booking_id, 'mphb_phone', $phone);
    update_post_meta($booking_id, 'mphb_total_price', $total_price);
    update_post_meta($booking_id, '_mphb_booking_price_breakdown', json_encode($breakdown, JSON_UNESCAPED_UNICODE));
    // === Update reserved room parent
    foreach ($booking_rooms as $reserved_id => $_) {
        wp_update_post(['ID' => $reserved_id, 'post_parent' => $booking_id]);
        //error_log("Updated post_parent for reserved_room_id=$reserved_id to booking_id=$booking_id");
    }
    // === Update status (pending-user) to send mail confirmation
    wp_update_post([
        'ID' => $booking_id,
        'post_status' => 'pending-user'
    ]);
    // === Redirect to success page
    wp_redirect(add_query_arg(['booking_success' => 1, 'bid' => $booking_id], get_permalink()));
    exit;
});