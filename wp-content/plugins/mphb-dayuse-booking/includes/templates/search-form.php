<?php
// Trang kết quả tìm kiếm
$search_url = mphb_get_dayuse_page_url('mphb_dayuse_search_results_page', 'search-results-dayuse');

?>
<div class="mphb_sc_search-wrapper">
    <form method="get" action="<?php echo esc_url($search_url); ?>" class="mphb-dayuse-search-form mphb_sc_search-form"
        style="margin-bottom:20px;"
        onsubmit="document.getElementById('check_out').value = document.getElementById('check_in').value;">

        <p class="mphb-required-fields-tip">
            <small>Required fields are followed by <abbr title="required">*</abbr></small>
        </p>
        <p class="mphb_sc_search-check-in-date">
            <label><strong><?php echo esc_html(pll__('Date of stay')); ?></strong> *</label><br>
            <input type="text" id="check_in" name="check_in" class="mphb-datepick mphb-datepick-check-in" autocomplete="off"
                placeholder="<?php echo esc_html(pll__('Date of stay')); ?>" value="<?php echo esc_attr($check_in ?? ''); ?>" required><br><br>
        </p>
        <input type="hidden" id="check_out" name="check_out" value="<?php echo esc_attr($check_in ?? ''); ?>">

        <p class="mphb_sc_search-submit-button-wrapper">
            <input type="submit" class="button button-primary" value="<?php echo esc_html(pll__('SEARCH')); ?>">
        </p>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.datepick) {
            // Lấy ngày mai làm ngày tối thiểu
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);

            jQuery('#check_in').datepick({
                dateFormat: 'yyyy-mm-dd',
                minDate: tomorrow,
                onSelect: function(date) {
                    const selectedDate = jQuery.datepick.formatDate('yyyy-mm-dd', date[0]);
                    document.getElementById('check_out').value = selectedDate;
                }
            });
        } else {
            console.warn('MPHB datepicker not loaded yet.');
        }
    });
</script>