<?php

/**
 * Template: Confirm Booking
 **/
if (empty($check_in) || empty($rooms)) {
    echo "<p>Không có dữ liệu đặt phòng. Vui lòng quay lại trang tìm phòng.</p>";
    return ob_get_clean();
}
$current_lang = pll_current_language();
?>
<?php
if ($check_in) {
    $check_in_formatted = date_i18n('F j, Y', strtotime($check_in));
    $check_in_time_raw = get_option('mphb_check_in_time_day_use', '09:00');
    $check_in_time = date_i18n('g:i A', strtotime($check_in_time_raw));
}
if ($check_out) {
    $check_out_formatted = date_i18n('F j, Y', strtotime($check_out));
    $check_out_time_raw = get_option('mphb_check_out_time_day_use', '18:00');
    $check_out_time = date_i18n('g:i A', strtotime($check_out_time_raw));
}
?>
<section class="mphb-booking-details mphb-checkout-section mphb_sc_checkout-form">
    <h3 class="mphb-booking-details-title"><?php echo esc_html(pll__('Booking Details')); ?></h3>
    <p class="mphb-check-in-date"><span><?php echo esc_html(pll__('Check-in:')); ?></span> <strong><?= esc_html($check_in_formatted) ?></strong>,
        <?php echo esc_html(pll__('from')); ?> <?= esc_html($check_in_time) ?>
    </p>
    <p class="mphb-check-out-date"><span><?php echo esc_html(pll__('Check-out:')); ?></span> <strong><?= esc_html($check_out_formatted) ?></strong>,
        <?php echo esc_html(pll__('until')); ?> <?= esc_html($check_out_time) ?>
    </p>
    <?php
    ob_start();
    $selected_rooms = [];
    // --- Loop mỗi room_type
    foreach ($rooms as $room_type_id => $data) {
        $qty = intval($data['qty'] ?? 0);
        $room_ids = isset($data['room_ids']) ? array_map('intval', (array) $data['room_ids']) : [];
        $rate_id = get_rate_id_by_booking_type($room_type->ID, $booking_type = 'day-use');
        if ($qty <= 0 || count(value: $room_ids) < $qty) {
            echo "<p>Lỗi: Không đủ phòng khả dụng hoặc dữ liệu không hợp lệ cho loại phòng {$room_type_id}.</p>";
            return ob_get_clean();
        }
        $room_post = get_post($room_type_id);
        if (!$room_post) {
            echo "<p>Lỗi: Loại phòng {$room_type_id} không tồn tại.</p>";
            return ob_get_clean();
        }
        $rate_id = get_rate_id_by_booking_type($room_post->ID, $booking_type = 'day-use');
        $rate_title = get_the_title($rate_id);
        $roomType = MPHB()->getRoomTypeRepository()->findById($room_type_id);
        $price = get_dayuse_price_for_room_type($room_post->ID, $check_in, 'day-use');
        $max_adults = get_post_meta($room_type_id, 'mphb_adults_capacity', true) ?: 2;
        $max_children = get_post_meta($room_type_id, 'mphb_children_capacity', true) ?: 1;

        echo "<div class='mphb-room-block'>";
        echo "<h3>{$room_post->post_title} × {$qty}</h3>";
        echo '<p><strong>' . esc_html(pll__('Rate')) . " ({$rate_title}):</strong> "
            . number_format($price, 0, ',', '.') . '₫ / ' . esc_html(pll__('Accommodation')) . '</p>';
        // --- Loop từng phòng instance
        for ($i = 1; $i <= $qty; $i++) {
            $room_index = count($selected_rooms);
            $room_id = isset($room_ids[$i - 1]) ? $room_ids[$i - 1] : 0;
            if ($room_id === 0) {
                echo "<p>Lỗi: Không có room_id hợp lệ cho phòng {$i} của loại phòng {$room_type_id}.</p>";
                return ob_get_clean();
            }
            echo "<div class='mphb-room-instance'>";
            echo '<h4>' . esc_html(pll__('Accommodation')) . " #{$i}</h4>";
            // --- Services per room (đa ngôn ngữ)
            $service_ids = maybe_unserialize(get_post_meta($room_type_id, 'mphb_services', true));
            $services = [];

            if (!empty($service_ids) && is_array($service_ids)) {

                foreach ($service_ids as $sv_id) {
                    // Lấy ID bản dịch của service hiện tại theo ngôn ngữ
                    $translated_id = function_exists('pll_get_post') ? pll_get_post($sv_id, $current_lang) : false;

                    // Nếu có bản dịch thì dùng, không có thì dùng gốc
                    $target_id = $translated_id ?: $sv_id;

                    // Lấy object service đúng ngôn ngữ
                    $service = MPHB()->getServiceRepository()->findById($target_id);

                    if ($service) {
                        $services[] = $service;
                    }
                }
            }
            $default_adults = esc_html(pll__('— Select —'));
            $default_children = esc_html(pll__('— Select —'));

            $room_unique_id = $room_type_id . '-' . $i;
            echo "<p class='mphb-adults-chooser'><label>" . esc_html(pll__('Adults')) . " *<select name='rooms[{$room_type_id}][{$room_index}][adults]' class='room-adult-select mphb_sc_checkout-guests-chooser mphb_checkout-guests-chooser' data-room-uid='{$room_unique_id}' required>";
            echo "<option value='' selected disabled>$default_adults</option>";
            for ($a = 1; $a <= $max_adults; $a++)
                echo "<option value='{$a}' " . ($a == $default_adults ? 'selected' : '') . ">{$a}</option>";
            echo "</select></label></p>";

            echo "<p class='mphb-children-chooser'><label>" . esc_html(pll__('Children')) . " *<select name='rooms[{$room_type_id}][{$room_index}][children]' class='room-child-select mphb_sc_checkout-guests-chooser mphb_checkout-guests-chooser' data-room-uid='{$room_unique_id}' required>";
            echo "<option value='' selected disabled>$default_children</option>";
            for ($c = 0; $c <= $max_children; $c++)
                echo "<option value='{$c}' " . ($c == $default_children ? 'selected' : '') . ">{$c}</option>";
            echo "</select></label></p>";

            echo "<p><label>" . esc_html(pll__('Full Guest Name')) . "<input type='text' name='rooms[{$room_type_id}][{$room_index}][guest_name]' required></label></p>";
            echo "<input type='hidden' name='rooms[{$room_type_id}][{$room_index}][room_id]' value='{$room_id}'>";
            echo "<input type='hidden' name='rooms[{$room_type_id}][{$room_index}][rate_id]' value='{$rate_id}'>";

            echo "</div>"; // end room instance

            $booking = new \MPHB\Entities\Booking([
                'check_in_date' => new DateTime($check_in),
                'check_out_date' => new DateTime($check_out),
            ]);
            $services = apply_filters('mphb_sc_checkout_allowed_services', $services, $roomType, $booking);

            if (!empty($services)) {
                echo "<section class='mphb-services-details mphb-checkout-item-section'>";
                echo "<h4>" . esc_html(pll__('Choose Additional Services')) . "</h4><ul class='mphb_checkout-services-list'>";

            foreach ($services as $service) {
                $sv_id_original = $service->getOriginalId();
                // Lấy ID bản dịch
                $sv_id_for_lang = function_exists('pll_get_post') ? pll_get_post($sv_id_original, $current_lang) : $sv_id_original;
                // Nếu không có bản dịch, quay lại dùng ID gốc
                $target_id = $sv_id_for_lang ?: $sv_id_original;
                $sv_title = esc_html($service->getTitle());
                $sv_price = floatval(get_post_meta($target_id, 'mphb_price', true));
                $sv_price_formatted = number_format($sv_price, 0, ',', '.') . "₫";
                echo "<li>
                    <label>
                        <input type='checkbox' 
                            class='mphb-service-checkbox' 
                            data-price='{$sv_price}' 
                            data-room-index='{$room_index}' 
                            data-room-type-id='{$room_type_id}' 
                            name='rooms[{$room_type_id}][{$room_index}][services][]' 
                            value='{$target_id}'>
                        {$sv_title} ({$sv_price_formatted})
                    </label>
                </li>";
            }
                echo "</ul>";
            } else {
                echo "<p><em>Không có dịch vụ nào khả dụng cho phòng này.</em></p>";
            }
            echo "</section>";
            $selected_rooms[] = [
                'id' => $room_type_id,
                'room_id' => $room_id,
                'rate_id' => $rate_id,
                'title' => $room_post->post_title,
                'instance' => $i,
                'base' => $price,
                'adults' => $default_adults,
                'children' => $default_children,
                'index' => $room_index,
                'service_ids' => [],
            ];
        }

        echo "</div>";
    }
    ?>
</section>
<section class="mphb-booking-details mphb-checkout-section">
    <?php
    // --- PRICE BREAKDOWN ---
    if ($selected_rooms) {
        echo '<section id="mphb-price-details" class="mphb-room-price-breakdown-wrapper">';
        echo '<h4 class="mphb-price-breakdown-title">' . esc_html(pll__('Price Breakdown')) . '</h4>';
        echo '<table class="mphb-price-breakdown" cellspacing="0"><tbody>';

        foreach ($selected_rooms as $index => $room) {
            $room_title = esc_html($room['title']);
            $instance = $room['instance'];
            $base = $room['base'];
            $adults = $room['adults'];
            $children = $room['children'];

            echo '<tr class="mphb-price-breakdown-booking mphb-price-breakdown-group ">';
            echo '<td colspan="2">';
            echo "<a href='#' class='mphb-price-breakdown-toggle' data-room-index='{$index}'>
                    <span class='toggle-show mphb-inner-icon '>+</span><span class='toggle-hide mphb-inner-icon ' style='display:none'>−</span>
                    #{$instance} {$room_title} — <span class='room-total'>" . number_format($base, 0, ',', '.') . "₫</span>
                  </a>";

            echo '<div class="mphb-price-breakdown-details" data-room-index="' . $index . '" style="display:none; padding:10px 0;">';
            $room_uid = $room['id'] . '-' . $room['instance'];
            echo "<p>Adults: <span class='room-adults' data-room-uid='{$room_uid}'>{$adults}</span></p>";
            echo "<p>Children: <span class='room-children' data-room-uid='{$room_uid}'>{$children}</span></p>";
            echo "<p>Subtotal: <span class='room-subtotal' data-base='{$base}' data-room-index='{$index}'>" . number_format($base, 0, ',', '.') . "₫</span></p>";
            echo '</div>';
            echo '</td></tr>';
        }

        echo '</tbody>';
        echo '<tfoot><tr class="mphb-price-breakdown-total">';
        echo '<th colspan="2">' . esc_html(pll__('Total')) . '</th>';
        echo '<th><span class="mphb-price" id="grand-total">' . number_format(array_sum(array_column($selected_rooms, 'base')), 0, ',', '.') . '₫</span></th>';
        echo '</tr></tfoot>';
        echo '</table></section>';
    }
    // --- JS Realtime ---
    ?>
</section>
<section class="mphb-checkout-section mphb-customer-details">
    <form id="custom-booking-form" method="POST">
        <?php wp_nonce_field('mphb_custom_booking_action', 'mphb_custom_booking_nonce'); ?>

        <!-- Ẩn các dữ liệu booking đã chọn -->
        <input type="hidden" name="check_in_date" value="<?php echo esc_attr($check_in); ?>">
        <input type="hidden" name="check_out_date" value="<?php echo esc_attr($check_out); ?>">
        <?php foreach ($selected_rooms as $idx => $room): ?>
            <input type="hidden" name="rooms[<?php echo $room['id']; ?>][<?php echo $idx; ?>][adults]"
                value="<?php echo $room['adults']; ?>">
            <input type="hidden" name="rooms[<?php echo $room['id']; ?>][<?php echo $idx; ?>][children]"
                value="<?php echo $room['children']; ?>">
            <input type="hidden" name="rooms[<?php echo $room['id']; ?>][<?php echo $idx; ?>][guest_name]"
                value="<?php echo esc_attr($room['guest_name'] ?? ''); ?>">
            <input type="hidden" name="rooms[<?php echo $room['id']; ?>][<?php echo $idx; ?>][services]" value="">
            <input type="hidden" name="rooms[<?php echo $room['id']; ?>][<?php echo $idx; ?>][room_id]"
                value="<?php echo esc_attr($room['room_id']); ?>">
            <input type="hidden" name="rooms[<?php echo $room['id']; ?>][<?php echo $idx; ?>][rate_id]"
                value="<?php echo esc_attr($room['rate_id']); ?>">
        <?php endforeach; ?>

        <input type="hidden" name="total_price"
            value="<?php echo esc_attr(array_sum(array_column($selected_rooms, 'base'))); ?>">

        <h3><?php echo esc_html(pll__('Your Information')); ?></h3>
        <p class="mphb-required-fields-tip">
            <small>
                Required fields are followed by <abbr title="required">*</abbr> </small>
        </p>
        <p>
            <label><?php echo esc_html(pll__('First Name')); ?> *<br>
                <input type="text" name="mphb_first_name" required>
            </label>
        </p>
        <p>
            <label><?php echo esc_html(pll__('Last Name')); ?> *<br>
                <input type="text" name="mphb_last_name" required>
            </label>
        </p>
        <p>
            <label><?php echo esc_html(pll__('Email')); ?> *<br>
                <input type="email" name="mphb_email" required>
            </label>
        </p>
        <p>
            <label><?php echo esc_html(pll__('Phone')); ?> *<br>
                <input type="text" name="mphb_phone">
            </label>
        </p>
        <p class='mphb-total-price'>
    <?php
    echo '<output>' . esc_html(pll__('Total')) . ': ';
    echo "<span class='mphb-price'>" . number_format(array_sum(array_column($selected_rooms, 'base')), 0, ',', '.') . "₫ </span> </output>"
    ?>
</p>
<p class="mphb_sc_checkout-submit-wrapper">
    <input type="submit" name="custom_booking_submit" value="<?php echo esc_attr(pll__('BOOK NOW')); ?>">
</p>
    </form>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // === 1. Chỉ cho phép chọn 1 service trong cùng một phòng ===
        document.querySelectorAll('.mphb_checkout-services-list').forEach(list => {
            const checkboxes = list.querySelectorAll('.mphb-service-checkbox');
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    const roomIndex = cb.dataset.roomIndex;

                    // Bỏ chọn các checkbox khác cùng phòng
                    checkboxes.forEach(other => {
                        if (other !== cb && other.dataset.roomIndex === roomIndex) {
                            other.checked = false;
                        }
                    });

                    updateRoomPrice(roomIndex);
                });
            });
        });

        // === 2. Cập nhật giá theo phòng ===
function updateRoomPrice(roomIndex) {
    const subtotalEl = document.querySelector('.room-subtotal[data-room-index="' + roomIndex + '"]');
    if (!subtotalEl) return;

    const base = parseFloat(subtotalEl.dataset.base) || 0;
    let serviceTotal = 0;
    const selectedServiceIds = [];

    // Tìm tất cả checkbox của phòng này đang được check
    const checkboxes = document.querySelectorAll('.mphb-service-checkbox[data-room-index="' + roomIndex + '"]:checked');
    
    checkboxes.forEach(s => {
        serviceTotal += parseFloat(s.dataset.price) || 0;
        selectedServiceIds.push(s.value); // Đây là ID đã dịch ($target_id)
    });

    // Cập nhật Subtotal hiển thị
    const newSubtotal = base + serviceTotal;
    subtotalEl.textContent = '₫' + newSubtotal.toLocaleString('vi-VN');

    // Cập nhật tổng trên tiêu đề toggle
    const toggleTotalEl = document.querySelector('.mphb-price-breakdown-toggle[data-room-index="' + roomIndex + '"] .room-total');
    if (toggleTotalEl) toggleTotalEl.textContent = '₫' + newSubtotal.toLocaleString('vi-VN');

    // Cập nhật vào INPUT HIDDEN để gửi đi (PHẦN QUAN TRỌNG)
    if (checkboxes.length > 0) {
        const roomTypeId = checkboxes[0].dataset.roomTypeId;
        const serviceInput = document.querySelector(`input[name="rooms[${roomTypeId}][${roomIndex}][services]"]`);
        if (serviceInput) {
            // Lưu chuỗi ID cách nhau bằng dấu phẩy
            serviceInput.value = selectedServiceIds.join(',');
        }
    } else {
        // Nếu không chọn service nào, xóa giá trị trắng
        const anyCb = document.querySelector(`.mphb-service-checkbox[data-room-index="${roomIndex}"]`);
        if (anyCb) {
            const roomTypeId = anyCb.dataset.roomTypeId;
            const serviceInput = document.querySelector(`input[name="rooms[${roomTypeId}][${roomIndex}][services]"]`);
            if (serviceInput) serviceInput.value = "";
        }
    }

    updateGrandTotal();
}
        // === 3. Cập nhật tổng toàn bộ phòng ===
        function updateGrandTotal() {
            let grand = 0;

            document.querySelectorAll('.room-subtotal').forEach(el => {
                const idx = el.dataset.roomIndex;
                let total = parseFloat(el.dataset.base) || 0;

                // Cộng tất cả service checked của phòng này
                document.querySelectorAll('.mphb-service-checkbox[data-room-index="' + idx + '"]:checked').forEach(s => {
                    total += parseFloat(s.dataset.price) || 0;
                });

                grand += total;
            });

            // Cập nhật tổng trong bảng
            const grandEl = document.getElementById('grand-total');
            if (grandEl) grandEl.textContent = '₫' + grand.toLocaleString('vi-VN');

            // Cập nhật tổng realtime phía dưới form
            const totalPriceEl = document.querySelector('.mphb-total-price .mphb-price');
            if (totalPriceEl) totalPriceEl.textContent = '₫' + grand.toLocaleString('vi-VN');

            // Cập nhật input hidden để submit
            const grandInput = document.querySelector('input[name="total_price"]');
            if (grandInput) grandInput.value = grand;
        }
        // === 4. Toggle hiển thị chi tiết ===
        document.querySelectorAll('.mphb-price-breakdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const index = toggle.dataset.roomIndex;
                const details = document.querySelector('.mphb-price-breakdown-details[data-room-index="' + index + '"]');
                const showIcon = toggle.querySelector('.toggle-show');
                const hideIcon = toggle.querySelector('.toggle-hide');

                if (window.getComputedStyle(details).display === 'none') {
                    details.style.display = 'block';
                    showIcon.style.display = 'none';
                    hideIcon.style.display = 'inline';
                } else {
                    details.style.display = 'none';
                    showIcon.style.display = 'inline';
                    hideIcon.style.display = 'none';
                }
            });
        });
        // === 5. Đồng bộ Adults / Children theo mỗi phòng ===
        document.querySelectorAll('.room-adult-select').forEach(select => {
            select.addEventListener('change', function() {
                const roomUid = this.dataset.roomUid;
                const val = this.value;

                // Cập nhật hiển thị trong bảng Breakdown
                const display = document.querySelector(`.room-adults[data-room-uid="${roomUid}"]`);
                if (display) display.textContent = val;

                // Cập nhật input hidden tương ứng
                const match = this.name.match(/rooms\[(\d+)\]\[(\d+)\]/);
                if (match) {
                    const roomTypeId = match[1];
                    const roomIndex = match[2];
                    const hiddenInput = document.querySelector(`input[name="rooms[${roomTypeId}][${roomIndex}][adults]"]`);
                    if (hiddenInput) hiddenInput.value = val;
                }
            });
        });

        document.querySelectorAll('.room-child-select').forEach(select => {
            select.addEventListener('change', function() {
                const roomUid = this.dataset.roomUid;
                const val = this.value;

                // Cập nhật hiển thị trong bảng Breakdown
                const display = document.querySelector(`.room-children[data-room-uid="${roomUid}"]`);
                if (display) display.textContent = val;

                // Cập nhật input hidden tương ứng
                const match = this.name.match(/rooms\[(\d+)\]\[(\d+)\]/);
                if (match) {
                    const roomTypeId = match[1];
                    const roomIndex = match[2];
                    const hiddenInput = document.querySelector(`input[name="rooms[${roomTypeId}][${roomIndex}][children]"]`);
                    if (hiddenInput) hiddenInput.value = val;
                }
            });
        });

    });
</script>
<?php if (isset($_GET['booking_success']) && $_GET['booking_success'] == 1): ?>
    <style>
        #booking-toast {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: var(--fs-color-secondary);
            color: #fff;
            padding: 14px 18px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease, transform 0.4s ease;
            transform: translateY(20px);
            z-index: 9999;
        }

        #booking-toast h4 {
            color: #fff;
            font-size: 20px;
        }

        #booking-toast p {
            color: #fff;
            font-size: 15px;
        }

        #booking-toast.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
    </style>
    <div id="booking-toast">
        <h4>Reservation submitted</h4><br>
        <p>Details of your reservation have just been sent to you in a confirmation email. Please check your inbox to
            complete booking.</p>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Confirm Booking] Success detected → clearing storage...');
            localStorage.removeItem('selectedRooms');
            sessionStorage.removeItem('selectedRooms');
            const toast = document.getElementById('booking-toast');
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
            setTimeout(() => {
				window.location.href = "<?php echo esc_url( pll_home_url() ); ?>";
            }, 4000);
        });
    </script>
<?php endif; ?>