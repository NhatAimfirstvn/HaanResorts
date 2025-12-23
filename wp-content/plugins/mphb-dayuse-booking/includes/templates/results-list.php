<?php
// Trang checkout / confirm
$checkout_url = mphb_get_dayuse_page_url('mphb_dayuse_checkout_page', 'booking-confirmation-dayuse');
$room_count = is_array($rooms) ? count($rooms) : 0;
if ($check_in_val) {
    $check_in_formatted = date_i18n('F j, Y', strtotime($check_in_val));
}
?>
<p class="mphb_sc_search_results-info">
    <?php echo esc_html($room_count); ?> <?php echo esc_html(pll__('accommodations found for')); ?>
    <?php echo esc_html($check_in_formatted); ?>.
</p>
<!-- Summary -->
<div id="selected-summary" style="margin-top:20px;">
    <p class="mphb-cart-message">0 <?php echo esc_html(pll__('accommodations selected')); ?>.</p>
    <p class="mphb-cart-total-price">
        <span class="mphb-cart-total-price-title"><?php echo esc_html(pll__('Total')); ?>:</span>
        <span class="mphb-cart-total-price-value">
            <span class="mphb-price">
                <span class="mphb-currency">₫</span>
                <span id="mphb-total-amount">0</span>
            </span>
        </span>
    </p>
    <button id="confirm-all" class="button button-primary"
        style="margin-top:10px; display:none;"><?php echo esc_html(pll__('CONFIRM')); ?></button>
    <a href="#" id="remove-all"
        style="margin-top:10px; display:none; background-color: none; border: none;"><?php echo esc_html(pll__('REMOVE ALL')); ?></a>
</div>
<!-- Form tổng -->
<form id="mphb-multi-room-form" class="mphb_sc_search_results-wrapper" method="get"
    action="<?php echo esc_url($checkout_url); ?>">
    <input type="hidden" name="check_in" value="<?php echo esc_attr($check_in_val); ?>">
    <input type="hidden" name="check_out" value="<?php echo esc_attr($check_out_val); ?>">
    <?php foreach ($rooms as $room): ?>
        <div class="mphb-room-type post-<?php echo $room['id']; ?> mphb_room_type" data-room-id="<?php echo $room['id']; ?>"
            data-room-title="<?php echo esc_attr($room['title']); ?>" data-price="<?php echo $room['price']; ?>"
            data-rate-id="<?php echo $room['rate_id']; ?>"
            data-available-rooms="<?php echo esc_attr(json_encode($room['available_room_ids'])); ?>">
            <?php if (!empty($room['gallery'])): ?>
                <div class="mphb-room-type-images">
                    <div class="slider mphb-gallery-main-slider"
                        data-flickity-options='{"cellAlign":"center","wrapAround":true,"autoPlay":false,"prevNextButtons":true,"pageDots":false,"adaptiveHeight":true}'>
                        <?php foreach ($room['gallery'] as $img_url): ?>
                            <div class="slider-slide">
                                <a href="<?php echo esc_url($img_url); ?>" data-fancybox="room-<?php echo $room['id']; ?>">
                                    <img src="<?php echo esc_url($img_url); ?>" loading="lazy" alt="">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <h2 class="mphb-room-type-title entry-title"><a
                    href="<?php echo $room['permalink']; ?>"><?php echo $room['title']; ?></a></h2>
            <p><?php echo $room['excerpt']; ?></p>
            <h3 class="mphb-room-type-details-title"><?php echo esc_html(pll__('Details')); ?></h3>
            <ul class="mphb-loop-room-type-attributes">
                <li class="mphb-room-type-adults-capacity"><strong><?php echo esc_html(pll__('Adults')); ?>: </strong>&nbsp;
                    <?php echo $room['adults']; ?>
                </li>
                <li class="mphb-room-type-children-capacity"><strong><?php echo esc_html(pll__('Children')); ?>:
                    </strong>&nbsp; <?php echo $room['children']; ?>
                </li>
                <li class="mphb-room-type-size"><strong><?php echo esc_html(pll__('Size')); ?>: </strong>&nbsp;
                    <?php echo $room['size']; ?>
                </li>
                <li class="mphb-room-type-bed-type"><strong><?php echo esc_html(pll__('Bed Type')); ?>:</strong>&nbsp;
                    <?php echo $room['bed_type']; ?>
                </li>
            </ul>
            <p class="mphb-regular-price">
                <strong><?php echo esc_html(pll__('Prices start at')); ?>:</strong>
                <span class="mphb-price">
                    <span class="mphb-currency">₫</span>
                    <?php echo number_format($room['price'], 0, ',', '.'); ?>
                </span>
                <span class="mphb-price-period" title="<?php echo esc_attr(pll__('Based on your search parameters')); ?>">
                    <?php echo esc_html(pll__('per day')); ?>
                </span>
            </p>
            <div class="mphb-reserve-room-section">
                <p class="'mphb-rooms-quantity-wrapper">
                    <select class="room-qty mphb-rooms-quantity" name="rooms[<?php echo $room['id']; ?>][qty]">
                        <?php for ($i = 1; $i <= $room['available_count']; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    <span class="mphb-available-rooms-count">
                        <?php echo esc_html(pll__('of')); ?>&nbsp;<?php echo esc_html($room['available_count']); ?>&nbsp;<?php echo esc_html(pll__('accommodations available')); ?>.
                    </span>
                </p>
                <div class="selected-room-list"></div>
                <!-- Hidden inputs -->
                <div class="room-ids" style="display:none;">
                    <input type="hidden" name="rooms[<?php echo $room['id']; ?>][rate_id]"
                        value="<?php echo $room['rate_id']; ?>">
                    <?php foreach ($room['available_room_ids'] as $index => $room_id): ?>
                        <input type="hidden" name="rooms[<?php echo $room['id']; ?>][room_ids][]"
                            value="<?php echo $room_id; ?>" data-index="<?php echo $index; ?>">
                    <?php endforeach; ?>
                </div>
                <p>
                    <button type="button" class="button add-to-booking"><?php echo esc_html(pll__('BOOKING')); ?></button>
                    <button class="button confirm-single button-primary"><?php echo esc_html(pll__('CONFIRM')); ?></button>
                </p>
            </div>
        </div>
    <?php endforeach; ?>

</form>

<script>
    /**
     * MPHB Dayuse Results JS
     */
    document.addEventListener("DOMContentLoaded", function() {
        const totalText = document.querySelector('.mphb-cart-message');
        const totalAmount = document.getElementById('mphb-total-amount');
        const confirmBtn = document.getElementById('confirm-all');
        const confirmSingleBtns = document.querySelectorAll('.confirm-single');
        const form = document.getElementById('mphb-multi-room-form');
        const checkIn = form.querySelector('input[name="check_in"]')?.value || '';
        const checkOut = form.querySelector('input[name="check_out"]')?.value || checkIn;
        let selectedRooms = JSON.parse(sessionStorage.getItem("selectedRooms") || "{}");
        const removeAllBtn = document.getElementById('remove-all');

        function updateSummary() {
            let totalRooms = 0;
            let totalPrice = 0;

            for (const [id, data] of Object.entries(selectedRooms)) {
                const block = document.querySelector(`.mphb-room-type[data-room-id='${id}']`);
                const price = parseFloat(block?.dataset.price || 0);
                totalRooms += parseInt(data.qty);
                totalPrice += parseInt(data.qty) * price;
            }

            if (totalText) {
                totalText.textContent = totalRooms > 0 ?
                    `${totalRooms} <?php echo esc_html(pll__('accommodations selected')); ?>.` :
                    '0 <?php echo esc_html(pll__('accommodations selected')); ?>.';
            }

            if (totalAmount) {
                totalAmount.textContent = totalPrice.toLocaleString();
            }

            if (confirmBtn) {
                confirmBtn.style.display = totalRooms > 0 ? 'inline-block' : 'none';
            }
            if (removeAllBtn) {
                removeAllBtn.style.display = totalRooms > 0 ? 'inline-block' : 'none';
            }
        }

        function renderSelectedList() {
            document.querySelectorAll(".mphb-room-type").forEach(container => {
                const id = container.dataset.roomId;
                const list = container.querySelector(".selected-room-list");
                const confirmSingleBtn = container.querySelector(".confirm-single");
                list.innerHTML = "";

                if (selectedRooms[id]) {
                    // --- Hiện danh sách phòng đã chọn ---
                    const item = document.createElement("div");
                    item.classList.add("mphb-rooms-reservation-message");
                    item.innerHTML = `
                <span class="mphb-rooms-reservation-message">${selectedRooms[id].qty} × “${selectedRooms[id].title}” <?php echo esc_html(pll__('has been added to your reservation')); ?>.</span>
                <a href="#" class="remove-room mphb-remove-from-reservation" data-room-id="${id}"> <?php echo esc_html(pll__('Remove')); ?></a>
            `;
                    list.appendChild(item);

                    if (confirmSingleBtn) confirmSingleBtn.style.display = "inline-block";
                } else {
                    if (confirmSingleBtn) confirmSingleBtn.style.display = "none";
                }
            });

            document.querySelectorAll(".remove-room").forEach(btn => {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    const id = this.dataset.roomId;
                    delete selectedRooms[id];
                    sessionStorage.setItem("selectedRooms", JSON.stringify(selectedRooms));

                    const select = document.querySelector(`.mphb-room-type[data-room-id='${id}'] .room-qty`);
                    if (select) select.value = 0;

                    renderSelectedList();
                    updateSummary();
                });
            });
        }

        // Add to booking
        document.querySelectorAll(".add-to-booking").forEach(btn => {
            btn.addEventListener("click", function() {
                const container = btn.closest(".mphb-room-type");
                const id = container.dataset.roomId;
                const qty = parseInt(container.querySelector(".room-qty").value);
                const availableRooms = JSON.parse(container.dataset.availableRooms || '[]');
                const rateId = container.dataset.rateId;
                const title = container.dataset.roomTitle || container.querySelector('h3 a').textContent.trim();
                const selectedRoomIds = qty > 0 ? availableRooms.slice(0, qty) : [];

                if (qty > 0) {
                    selectedRooms[id] = {
                        qty: qty,
                        room_ids: selectedRoomIds,
                        rate_id: rateId,
                        title: title
                    };
                } else {
                    delete selectedRooms[id];
                }

                sessionStorage.setItem("selectedRooms", JSON.stringify(selectedRooms));
                updateSummary();
                renderSelectedList();
            });
        });

        function handleConfirmClick(e) {
            e.preventDefault();

            // Xóa input cũ
            form.querySelectorAll("input[name^='rooms[']").forEach(input => input.remove());

            // Thêm input mới cho tất cả selectedRooms
            for (const [id, data] of Object.entries(selectedRooms)) {
                const inputQty = document.createElement("input");
                inputQty.type = "hidden";
                inputQty.name = `rooms[${id}][qty]`;
                inputQty.value = data.qty;
                form.appendChild(inputQty);

                const inputRateId = document.createElement("input");
                inputRateId.type = "hidden";
                inputRateId.name = `rooms[${id}][rate_id]`;
                inputRateId.value = data.rate_id;
                form.appendChild(inputRateId);

                const inputTitle = document.createElement("input");
                inputTitle.type = "hidden";
                inputTitle.name = `rooms[${id}][title]`;
                inputTitle.value = data.title || '';
                form.appendChild(inputTitle);

                data.room_ids.forEach(room_id => {
                    const inputRoom = document.createElement("input");
                    inputRoom.type = "hidden";
                    inputRoom.name = `rooms[${id}][room_ids][]`;
                    inputRoom.value = room_id;
                    form.appendChild(inputRoom);
                });
            }

            sessionStorage.setItem("selectedRooms", JSON.stringify(selectedRooms));
            form.submit();
        }

        // Gắn sự kiện cho Confirm All
        if (confirmBtn) {
            confirmBtn.addEventListener("click", handleConfirmClick);
        }

        // Gắn sự kiện cho từng Confirm Single
        confirmSingleBtns.forEach(btn => {
            btn.addEventListener("click", handleConfirmClick);
        });
        if (removeAllBtn) {
            removeAllBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Xóa toàn bộ phòng đã chọn
                selectedRooms = {};
                sessionStorage.removeItem('selectedRooms');
                // Reset tất cả select về 0
                document.querySelectorAll('.room-qty').forEach(select => select.value = 0);

                updateSummary();
                renderSelectedList();
            });
        }
        updateSummary();
        renderSelectedList()
    });
    window.addEventListener("beforeunload", function() {
        sessionStorage.removeItem("selectedRooms");
    });
</script>