<?php
/**
 * WooFactor Hooks Integration, Checkout Fields, Bulk Actions & SMS Triggers
 */
defined('ABSPATH') || exit;

// 1. Admin Order Actions & Dedicated Columns (Classic & HPOS)
add_filter('woocommerce_admin_order_actions', function ($actions, $order) {
    $order_id = $order->get_id();
    $invoice_url = wp_nonce_url(admin_url('admin-ajax.php?action=woo_factor_view_invoice&order_id=' . $order_id), 'woo_factor_view_' . $order_id);
    $label_url = wp_nonce_url(admin_url('admin-ajax.php?action=woo_factor_view_label&order_id=' . $order_id), 'woo_factor_label_' . $order_id);

    $actions['woo_factor_invoice'] = [
        'url'    => $invoice_url,
        'name'   => __('چاپ فاکتور', 'woo-factor'),
        'action' => 'woo_factor_invoice',
    ];

    $actions['woo_factor_label'] = [
        'url'    => $label_url,
        'name'   => __('برچسب پستی', 'woo-factor'),
        'action' => 'woo_factor_label',
    ];

    return $actions;
}, 10, 2);

// Dedicated "فاکتور و مرسوله" column in Orders list
function woo_factor_add_order_column($columns) {
    $new = [];
    foreach ($columns as $key => $title) {
        $new[$key] = $title;
        if ($key === 'order_status' || $key === 'order_number') {
            $new['woo_factor_print'] = __('فاکتور و مرسوله', 'woo-factor');
        }
    }
    if (!isset($new['woo_factor_print'])) {
        $new['woo_factor_print'] = __('فاکتور و مرسوله', 'woo-factor');
    }
    return $new;
}
add_filter('manage_edit-shop_order_columns', 'woo_factor_add_order_column', 20);
add_filter('woocommerce_shop_order_list_table_columns', 'woo_factor_add_order_column', 20);

function woo_factor_render_order_column($column, $order) {
    if ($column !== 'woo_factor_print') {
        return;
    }
    if (is_numeric($order)) {
        $order = wc_get_order($order);
    }
    if (!$order) {
        return;
    }
    $order_id = $order->get_id();
    $inv_url = wp_nonce_url(admin_url('admin-ajax.php?action=woo_factor_view_invoice&order_id=' . $order_id), 'woo_factor_view_' . $order_id);
    $label_url = wp_nonce_url(admin_url('admin-ajax.php?action=woo_factor_view_label&order_id=' . $order_id), 'woo_factor_label_' . $order_id);
    ?>
    <div class="wf-order-col-actions" style="display:inline-flex; gap:4px; align-items:center;">
        <a href="<?php echo esc_url($inv_url); ?>" target="_blank" rel="noopener" class="button button-small" title="<?php esc_attr_e('مشاهده و چاپ فاکتور', 'woo-factor'); ?>" style="padding:0 6px; height:24px; line-height:22px; font-size:11px;">
            <span class="dashicons dashicons-media-spreadsheet" style="font-size:14px; width:14px; height:14px; line-height:22px; vertical-align:middle;"></span> فاکتور
        </a>
        <a href="<?php echo esc_url($label_url); ?>" target="_blank" rel="noopener" class="button button-small" title="<?php esc_attr_e('چاپ برچسب پستی مرسوله', 'woo-factor'); ?>" style="padding:0 6px; height:24px; line-height:22px; font-size:11px;">
            <span class="dashicons dashicons-email-alt" style="font-size:14px; width:14px; height:14px; line-height:22px; vertical-align:middle;"></span> مرسوله
        </a>
    </div>
    <?php
}
add_action('manage_shop_order_posts_custom_column', function ($column, $post_id) {
    woo_factor_render_order_column($column, $post_id);
}, 10, 2);
add_action('woocommerce_shop_order_list_table_custom_column', function ($column, $order) {
    woo_factor_render_order_column($column, $order);
}, 10, 2);

// Add custom icon style for admin order actions
add_action('admin_head', function () {
    echo '<style>
        .wc-action-button-woo_factor_invoice::after { content: "\f498" !important; font-family: dashicons !important; }
        .wc-action-button-woo_factor_label::after { content: "\f475" !important; font-family: dashicons !important; }
        .manage-column.column-woo_factor_print { width: 140px; }
    </style>';
});

// 2. Register Bulk Actions for Orders (Classic & HPOS)
add_filter('bulk_actions-edit-shop_order', 'woo_factor_register_bulk_actions');
add_filter('bulk_actions-woocommerce_page_wc-orders', 'woo_factor_register_bulk_actions');

function woo_factor_register_bulk_actions($actions) {
    $actions['woo_factor_bulk_invoices'] = __('🖨️ چاپ گروهی فاکتورها (WooFactor)', 'woo-factor');
    $actions['woo_factor_bulk_labels'] = __('📮 چاپ گروهی برچسب‌های پستی (WooFactor)', 'woo-factor');
    return $actions;
}

add_filter('handle_bulk_actions-edit-shop_order', 'woo_factor_handle_bulk_actions', 10, 3);
add_filter('handle_bulk_actions-woocommerce_page_wc-orders', 'woo_factor_handle_bulk_actions', 10, 3);

function woo_factor_handle_bulk_actions($redirect_to, $action, $order_ids) {
    if (!current_user_can('manage_woocommerce')) {
        return $redirect_to;
    }

    if ($action === 'woo_factor_bulk_invoices' && !empty($order_ids)) {
        $ids = implode(',', array_map('absint', $order_ids));
        $url = wp_nonce_url(admin_url('admin-ajax.php?action=woo_factor_bulk_invoices&order_ids=' . $ids), 'woo_factor_bulk_print');
        wp_redirect($url);
        exit;
    }

    if ($action === 'woo_factor_bulk_labels' && !empty($order_ids)) {
        $ids = implode(',', array_map('absint', $order_ids));
        $url = wp_nonce_url(admin_url('admin-ajax.php?action=woo_factor_bulk_labels&order_ids=' . $ids), 'woo_factor_bulk_print');
        wp_redirect($url);
        exit;
    }

    return $redirect_to;
}

// 3. Bulk Printing Handlers
add_action('wp_ajax_woo_factor_bulk_invoices', function () {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('عدم دسترسی.');
    }
    check_admin_referer('woo_factor_bulk_print');

    $ids_str = isset($_GET['order_ids']) ? sanitize_text_field(wp_unslash($_GET['order_ids'])) : '';
    $ids = array_filter(array_map('absint', explode(',', $ids_str)));

    if (empty($ids)) {
        wp_die('هیچ سفارشی انتخاب نشده است.');
    }

    $opts = woo_factor_options();
    $template = $opts['template'] ?? 'classic';

    $output = '';
    foreach ($ids as $order_id) {
        $data = Woo_Factor_Invoice_Builder::build_from_order($order_id);
        if ($data) {
            $output .= '<div class="bulk-page-item">' . Woo_Factor_Renderer::render_html($data, $template) . '</div>';
            $output .= '<div class="page-break" style="page-break-after: always; height: 0; margin: 0; padding: 0;"></div>';
        }
    }

    echo $output;
    exit;
});

add_action('wp_ajax_woo_factor_bulk_labels', function () {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('عدم دسترسی.');
    }
    check_admin_referer('woo_factor_bulk_print');

    $ids_str = isset($_GET['order_ids']) ? sanitize_text_field(wp_unslash($_GET['order_ids'])) : '';
    $ids = array_filter(array_map('absint', explode(',', $ids_str)));

    if (empty($ids)) {
        wp_die('هیچ سفارشی انتخاب نشده است.');
    }

    $output = '';
    foreach ($ids as $order_id) {
        $data = Woo_Factor_Invoice_Builder::build_from_order($order_id);
        if ($data) {
            $output .= '<div class="bulk-label-item">' . Woo_Factor_Renderer::render_shipping_label($data) . '</div>';
            $output .= '<div class="page-break" style="page-break-after: always; height: 0; margin: 0; padding: 0;"></div>';
        }
    }

    echo $output;
    exit;
});

// 4. AJAX Handlers to view single invoice & shipping label
add_action('wp_ajax_woo_factor_view_invoice', 'woo_factor_handle_view_invoice');
add_action('wp_ajax_nopriv_woo_factor_view_invoice', 'woo_factor_handle_view_invoice');

function woo_factor_handle_view_invoice() {
    $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
    if (!$order_id) {
        wp_die('شناسه سفارش نامعتبر است.');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_die('سفارش یافت نشد.');
    }

    $current_user_id = get_current_user_id();
    $is_admin = current_user_can('manage_woocommerce');
    $is_owner = $current_user_id && ($order->get_customer_id() === $current_user_id);
    $order_key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
    $has_valid_key = $order_key && hash_equals((string) $order->get_order_key(), $order_key);

    if (!$is_admin && !$is_owner && !$has_valid_key) {
        wp_die('شما دسترسی مشاهده این فاکتور را ندارید.');
    }

    $tpl = isset($_GET['template']) ? sanitize_text_field($_GET['template']) : null;
    $data = Woo_Factor_Invoice_Builder::build_from_order($order_id);
    if (!$data) {
        wp_die('خطا در بارگذاری اطلاعات فاکتور.');
    }

    echo Woo_Factor_Renderer::render_html($data, $tpl);
    exit;
}

add_action('wp_ajax_woo_factor_view_label', function () {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('عدم دسترسی.');
    }
    $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
    check_admin_referer('woo_factor_label_' . $order_id);

    $data = Woo_Factor_Invoice_Builder::build_from_order($order_id);
    echo Woo_Factor_Renderer::render_shipping_label($data);
    exit;
});

// 5. Customer My Account Orders Page - Add Download Invoice Button
add_filter('woocommerce_my_account_my_orders_actions', function ($actions, $order) {
    $opts = woo_factor_options();
    if (($opts['myaccount_page'] ?? 'yes') === 'yes') {
        $url = woo_factor_invoice_url($order);
        if ($url) {
            $actions['woo_factor_invoice'] = [
                'url'  => $url,
                'name' => __('دریافت فاکتور', 'woo-factor'),
            ];
        }
    }
    return $actions;
}, 10, 2);

// 6. Attach invoice button/link in WooCommerce Customer Emails
add_action('woocommerce_email_after_order_table', function ($order, $sent_to_admin, $plain_text, $email) {
    $opts = woo_factor_options();
    if (($opts['attach_woocommerce'] ?? 'yes') !== 'yes' || $plain_text) {
        return;
    }

    $url = woo_factor_invoice_url($order);
    if (!$url) {
        return;
    }

    echo '<div style="margin: 20px 0; text-align: center;">';
    echo '<a href="' . esc_url($url) . '" target="_blank" style="background-color: #0f766e; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">' . esc_html__('مشاهده و چاپ فاکتور خرید آنلاین', 'woo-factor') . '</a>';
    echo '</div>';
}, 10, 4);

// 7. Natural / Legal Customer Fields in Checkout
if ((woo_factor_options()['enable_checkout_customer_type'] ?? 'no') === 'yes') {
add_filter('woocommerce_billing_fields', function ($fields) {
    $opts = woo_factor_options();

    if (($opts['enable_checkout_customer_type'] ?? 'yes') === 'yes') {
        $fields['billing_customer_type'] = [
            'type'        => 'select',
            'label'       => __('نوع مشتری / خریدار', 'woo-factor'),
            'options'     => [
                'natural' => __('شخص حقیقی', 'woo-factor'),
                'legal'   => __('شخص حقوقی / شرکت', 'woo-factor'),
            ],
            'required'    => false,
            'class'       => ['form-row-wide'],
            'priority'    => 21,
        ];
    }

    $fields['billing_national_code'] = [
        'type'        => 'text',
        'label'       => __('کد ملی / شناسه ملی', 'woo-factor'),
        'placeholder' => __('جهت درج در فاکتور رسمی', 'woo-factor'),
        'required'    => false,
        'class'       => ['form-row-wide'],
        'clear'       => true,
        'priority'    => 22,
    ];

    $fields['billing_economic_code'] = [
        'type'        => 'text',
        'label'       => __('کد اقتصادی', 'woo-factor'),
        'placeholder' => __('ویژه اشخاص حقوقی و شرکت‌ها', 'woo-factor'),
        'required'    => false,
        'class'       => ['form-row-wide'],
        'clear'       => true,
        'priority'    => 23,
    ];

    return $fields;
});

// 8. Persist checkout fields to Order Meta
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (isset($_POST['billing_customer_type'])) {
        $order->update_meta_data('_billing_customer_type', sanitize_text_field(wp_unslash($_POST['billing_customer_type'])));
    }
    if (isset($_POST['billing_national_code'])) {
        $order->update_meta_data('_billing_national_code', sanitize_text_field(wp_unslash($_POST['billing_national_code'])));
    }
    if (isset($_POST['billing_economic_code'])) {
        $order->update_meta_data('_billing_economic_code', sanitize_text_field(wp_unslash($_POST['billing_economic_code'])));
    }
}, 10, 2);

// 9. Automatic SMS Trigger on Order Status Change
}
add_action('woocommerce_order_status_changed', function ($order_id, $old_status, $new_status, $order) {
    $opts = woo_factor_options();
    if (($opts['sms_enabled'] ?? 'no') !== 'yes') {
        return;
    }

    $target_status = str_replace('wc-', '', $opts['sms_trigger_status'] ?? 'completed');
    if ($new_status === $target_status) {
        $sent = WooFactor_SMS::send_invoice_sms($order);
        if ($sent) {
            $order->add_order_note(__('پیامک لینک فاکتور WooFactor با موفقیت به مشتری ارسال شد.', 'woo-factor'));
        }
    }
}, 10, 4);
