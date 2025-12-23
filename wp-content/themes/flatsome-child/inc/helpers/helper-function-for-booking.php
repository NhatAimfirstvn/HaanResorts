<?php
if (!defined('ABSPATH')) exit; 
/**
 * Get locked rooms and locked room types based on search dates 
 */
function get_locked_rooms_and_types($check_in_date_search, $check_out_date_search)
{
    global $wpdb;

    $lockedRoomIds = [];
    $lockedRoomTypes = [];
    $roomTypeBookedCount = [];
    $allRoomIdsByType = [];

    $reserved_rooms = $wpdb->get_results("
        SELECT 
            rr.ID AS reserved_room_id,
            rr.post_parent AS booking_id,
            roommeta.meta_value AS room_id,
            ratemeta.meta_value AS rate_id,
            checkin.meta_value AS check_in_date,
            checkout.meta_value AS check_out_date
        FROM {$wpdb->posts} AS rr
        LEFT JOIN {$wpdb->postmeta} AS roommeta 
            ON rr.ID = roommeta.post_id AND roommeta.meta_key = '_mphb_room_id'
        LEFT JOIN {$wpdb->postmeta} AS ratemeta 
            ON rr.ID = ratemeta.post_id AND ratemeta.meta_key = '_mphb_rate_id'
        LEFT JOIN {$wpdb->postmeta} AS checkin 
            ON rr.post_parent = checkin.post_id AND checkin.meta_key = 'mphb_check_in_date'
        LEFT JOIN {$wpdb->postmeta} AS checkout 
            ON rr.post_parent = checkout.post_id AND checkout.meta_key = 'mphb_check_out_date'
        LEFT JOIN {$wpdb->posts} AS booking 
            ON rr.post_parent = booking.ID
        WHERE rr.post_type = 'mphb_reserved_room'
          AND rr.post_status = 'publish'
          AND booking.post_status NOT IN ('trash', 'abandoned','cancelled')
    ");

    foreach ($reserved_rooms as $r) {
        $room_id = (int) $r->room_id;
        $rate_id = (int) $r->rate_id;
        $booking_id = (int) $r->booking_id;

        $check_in_date = !empty($r->check_in_date) ? $r->check_in_date : get_post_meta($r->reserved_room_id, 'mphb_check_in_date', true);
        $check_out_date = !empty($r->check_out_date) ? $r->check_out_date : get_post_meta($r->reserved_room_id, 'mphb_check_out_date', true);

        if (!$room_id || !$booking_id || empty($check_in_date) || empty($check_out_date)) {
            error_log("SKIP → missing data: room_id=$room_id | booking_id=$booking_id | check_in=$check_in_date | check_out=$check_out_date");
            continue;
        }

        $room_type_id = (int) get_post_meta($room_id, 'mphb_room_type_id', true);
        if (!$room_type_id) {
            error_log("SKIP → missing room_type_id for room_id=$room_id");
            continue;
        }

        // Lấy tất cả room IDs theo type
        if (!isset($allRoomIdsByType[$room_type_id])) {
            $allRoomIdsByType[$room_type_id] = MPHB()->getRoomPersistence()->findAllIdsByType($room_type_id);
        }

        // Kiểm tra day-use hay overnight
        $is_dayuse_booked = ($check_in_date == $check_out_date);
        $is_search_dayuse = ($check_in_date_search == $check_out_date_search);

        $booked_check_in_time = $is_dayuse_booked ? get_option('mphb_check_in_time_day_use', '09:00') : get_option('mphb_check_in_time', '14:00');
        $booked_check_out_time = $is_dayuse_booked ? get_option('mphb_check_out_time_day_use', '18:00') : get_option('mphb_check_out_time', '12:00');

        $search_check_in_time = $is_search_dayuse ? get_option('mphb_check_in_time_day_use', '09:00') : get_option('mphb_check_in_time', '14:00');
        $search_check_out_time = $is_search_dayuse ? get_option('mphb_check_out_time_day_use', '18:00') : get_option('mphb_check_out_time', '12:00');

        $booked_start = strtotime("$check_in_date $booked_check_in_time");
        $booked_end = strtotime("$check_out_date $booked_check_out_time");
        $search_start = strtotime("$check_in_date_search $search_check_in_time");
        $search_end = strtotime("$check_out_date_search $search_check_out_time");

        // Log chi tiết
        //error_log("ROOM ID: $room_id | ROOM TYPE: $room_type_id | BOOKING ID: $booking_id");
        //error_log("Booked start: " . date('Y-m-d H:i', $booked_start) . " | end: " . date('Y-m-d H:i', $booked_end) . " | Day-use booked: " . ($is_dayuse_booked ? 'YES' : 'NO'));
        //error_log("Search start: " . date('Y-m-d H:i', $search_start) . " | end: " . date('Y-m-d H:i', $search_end) . " | Day-use search: " . ($is_search_dayuse ? 'YES' : 'NO'));

        // Kiểm tra overlap
        if ($search_start < $booked_end && $search_end > $booked_start) {
            $lockedRoomIds[] = $room_id;
            $roomTypeBookedCount[$room_type_id][] = $room_id;
            //error_log("→ LOCKED ROOM: $room_id");
        } else {
            //error_log("→ NOT LOCKED: $room_id");
        }
    }

    // Xác định room types full
    foreach ($roomTypeBookedCount as $room_type_id => $bookedRoomIds) {
        if (count(array_unique($bookedRoomIds)) >= count($allRoomIdsByType[$room_type_id])) {
            $lockedRoomTypes[] = $room_type_id;
            //error_log("→ LOCKED ROOM TYPE: $room_type_id (" . count(array_unique($bookedRoomIds)) . "/" . count($allRoomIdsByType[$room_type_id]) . ")");
        }
    }

    //error_log("=== FINAL LOCKED ROOMS === " . implode(',', array_unique($lockedRoomIds)));
    //error_log("=== FINAL LOCKED ROOM TYPES === " . implode(',', array_unique($lockedRoomTypes)));

    return [
        'locked_room_ids' => array_unique($lockedRoomIds),
        'locked_room_types' => array_unique($lockedRoomTypes)
    ];
}

function filter_rates_by_booking_type($allowedRates, $roomType, $booking) {
    $checkIn  = $booking->getCheckInDate()->format('Y-m-d');
    $checkOut = $booking->getCheckOutDate()->format('Y-m-d');

    $currentType = ($checkIn === $checkOut) ? 'DAY-USE' : 'OVER-NIGHT';

    $filtered = array_filter($allowedRates, function ($rate) use ($currentType) {
        $bookingType = get_post_meta($rate->getOriginalId(), 'booking_type', true);
        return strtoupper($bookingType) === $currentType;
    });

    return $filtered;
}
function filter_services_custom($services, $roomType, $booking) {
    $checkIn  = $booking->getCheckInDate();
    $checkOut = $booking->getCheckOutDate();
    $isWeekend = false;
    if ($checkIn->format('Y-m-d') === $checkOut->format('Y-m-d')) {
        $dayNum = (int) $checkIn->format('N');
        $isWeekend = ($dayNum >= 6); // Thứ 6 trở đi là weekend (>=5 nếu muốn)
    } else {
        $period = new DatePeriod($checkIn, new DateInterval('P1D'), $checkOut);
        foreach ($period as $day) {
            $dayNum = (int) $day->format('N');
            if ($dayNum >= 6) {
                $isWeekend = true;
                break;
            }
        }
    }
    $seasonType = $isWeekend ? 'weekend' : 'weekday';
    $filtered = array_filter($services, function($service) use ($seasonType) {
        $metaType = get_post_meta($service->getOriginalId(), 'services-type', true);
        return !$metaType || strtolower($metaType) === $seasonType;
    });
    return $filtered;
}
