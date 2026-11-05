<?php
/**
 * Meta Boxes on WooCommerce Single Order Admin Page
 */
defined('ABSPATH') || exit;

add_action('add_meta_boxes', function () {
    $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')
        && wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id('shop-order')
        : 'shop_order';

    add_meta_box(
        'woo_factor_order_invoice_box',
        __('فاکتور و رسید پستی', 'woo-factor'),
        'woo_factor_render_order_meta_box',
        $screen,
        'side',
        'high'
    );
});

function woo_factor_render_order_meta_box($post_or_order) {
    $order = ($post_or_order instanceof WC_Order) ? $post_or_order : wc_get_order($post_or_order->ID);
    if (!$order) return;

    $order_id = $order->get_id();
    $inv_url = wp_nonce_url(admin_url('admin-ajax.php?action=woo_factor_view_invoice&order_id=' . $order_id), 'woo_factor_view_' . $order_id);
    $label_url = wp_nonce_url(admin_url('admin-ajax.php?action=woo_factor_view_label&order_id=' . $order_id), 'woo_factor_label_' . $order_id);
    $customer_invoice_url = woo_factor_invoice_url($order);
    $custom_inv_num = $order->get_meta('_woo_factor_invoice_number') ?: $order->get_order_number();
    $timestamp = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : current_time('timestamp');
    ?>
    <style>
        .wf-order-box { direction: rtl; text-align: right; font-family: Vazirmatn, Tahoma, sans-serif; }
        .wf-order-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; }
        .wf-order-meta div { background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 6px; padding: 8px; min-width: 0; }
        .wf-order-meta span { display: block; color: #50575e; font-size: 11px; }
        .wf-order-meta strong { display: block; color: #1d2327; overflow-wrap: anywhere; }
        .wf-order-actions { display: grid; gap: 8px; }
        .wf-order-actions .button { text-align: center; min-height: 36px; line-height: 34px; margin: 0; }
        .wf-order-actions .button-primary { background: #0f766e; border-color: #0f766e; }
        .wf-order-actions .button-primary:hover, .wf-order-actions .button-primary:focus { background: #115e59; border-color: #115e59; }
        .wf-order-templates { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
        .wf-order-templates .button { padding: 0 4px; font-size: 12px; }
        .wf-order-help { color: #50575e; font-size: 12px; margin: 10px 0 0; }
    </style>
    <div class="wf-order-box">
        <div class="wf-order-meta">
            <div><span><?php esc_html_e('شماره فاکتور', 'woo-factor'); ?></span><strong><?php echo esc_html(woo_factor_fa_digits($custom_inv_num)); ?></strong></div>
            <div><span><?php esc_html_e('تاریخ', 'woo-factor'); ?></span><strong><?php echo esc_html(woo_factor_fa_digits(woo_factor_jdate($timestamp, false))); ?></strong></div>
        </div>

        <div class="wf-order-actions">
            <a href="<?php echo esc_url($inv_url); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e('چاپ فاکتور', 'woo-factor'); ?></a>
            <div class="wf-order-templates" role="group" aria-label="<?php esc_attr_e('قالب فاکتور', 'woo-factor'); ?>">
                <a href="<?php echo esc_url($inv_url . '&template=classic'); ?>" target="_blank" rel="noopener" class="button"><?php esc_html_e('رسمی', 'woo-factor'); ?></a>
                <a href="<?php echo esc_url($inv_url . '&template=modern'); ?>" target="_blank" rel="noopener" class="button"><?php esc_html_e('مدرن', 'woo-factor'); ?></a>
                <a href="<?php echo esc_url($inv_url . '&template=thermal'); ?>" target="_blank" rel="noopener" class="button"><?php esc_html_e('حرارتی', 'woo-factor'); ?></a>
            </div>
            <a href="<?php echo esc_url($label_url); ?>" target="_blank" rel="noopener" class="button"><?php esc_html_e('چاپ رسید پستی', 'woo-factor'); ?></a>
            <?php if ($customer_invoice_url): ?>
                <button type="button" class="button wf-copy-link" data-url="<?php echo esc_url($customer_invoice_url); ?>"><?php esc_html_e('کپی لینک فاکتور مشتری', 'woo-factor'); ?></button>
            <?php endif; ?>
        </div>
        <p class="wf-order-help" role="status" aria-live="polite"></p>
    </div>
    <script>
    document.querySelectorAll('.wf-copy-link').forEach(function (button) {
        button.addEventListener('click', function () {
            var status = button.closest('.wf-order-box').querySelector('.wf-order-help');
            navigator.clipboard.writeText(button.dataset.url).then(function () {
                status.textContent = <?php echo wp_json_encode(__('لینک کپی شد.', 'woo-factor')); ?>;
            }, function () {
                status.textContent = <?php echo wp_json_encode(__('کپی انجام نشد؛ لینک را دستی از آدرس صفحه چاپ بردارید.', 'woo-factor')); ?>;
            });
        });
    });
    </script>
    <?php
}
