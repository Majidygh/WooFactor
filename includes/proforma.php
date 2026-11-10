<?php
/**
 * Proforma (پیش‌فاکتور) Support
 */
defined('ABSPATH') || exit;

// Add Proforma Button in Cart
add_action('woocommerce_proceed_to_checkout', function () {
    if (WC()->cart->is_empty()) return;
    $url = wp_nonce_url(admin_url('admin-ajax.php?action=woo_factor_view_proforma'), 'woo_woo_factorforma');
    echo '<a href="' . esc_url($url) . '" target="_blank" class="button alt" style="margin-top: 10px; width: 100%; text-align: center; background: #475569;">' . esc_html__('📄 چاپ پیش‌فاکتور سبد خرید', 'woo-factor') . '</a>';
}, 25);

add_action('wp_ajax_woo_factor_view_proforma', 'woo_factor_handle_view_proforma');
add_action('wp_ajax_nopriv_woo_factor_view_proforma', 'woo_factor_handle_view_proforma');

function woo_factor_handle_view_proforma() {
    // CSRF protection: the button link carries a nonce; without it, die.
    if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), 'woo_woo_factorforma')) {
        wp_die(esc_html__('نشست شما منقضی شده است. لطفاً صفحه سبد خرید را دوباره بارگذاری کنید.', 'woo-factor'));
    }

    if (!WC()->cart || WC()->cart->is_empty()) {
        wp_die('سبد خرید شما در حال حاضر خالی است.');
    }

    $opts = woo_factor_options();
    $current_user = wp_get_current_user();

    $seller = [
        'name'        => !empty($opts['shop_name']) ? $opts['shop_name'] : get_bloginfo('name'),
        'phone'       => $opts['shop_phone'] ?? '',
        'sender_mobile' => $opts['sender_mobile'] ?? '',
        'email'       => !empty($opts['shop_email']) ? $opts['shop_email'] : get_bloginfo('admin_email'),
        'national_id' => $opts['shop_national_id'] ?? '',
        'address'     => $opts['shop_address'] ?? '',
        'website'     => home_url(),
        'logo_url'    => woo_factor_get_logo_url(),
    ];

    $buyer_name = $current_user->exists() ? $current_user->display_name : 'مشتری گرامی (مهمان)';
    $buyer = [
        'name'          => $buyer_name,
        'company'       => '',
        'national_id'   => '',
        'economic_id'   => '',
        'registration_no' => '',
        'postcode'      => '',
        'phone'         => '',
        'email'         => $current_user->exists() ? $current_user->user_email : '',
        'full_address'  => 'ثبت نشده (پیش‌فاکتور استعلام قیمت)',
    ];

    $items = [];
    $index = 1;
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $qty = $cart_item['quantity'];
        $price = (float)$product->get_price();
        $subtotal = $price * $qty;

        $items[] = [
            'index'      => $index++,
            'id'         => $product->get_id(),
            'sku'        => $product->get_sku() ?: '-',
            'title'      => $product->get_name(),
            'qty'        => $qty,
            'price'      => $price,
            'unit_price' => $price,
            'subtotal'   => $subtotal,
            'discount'   => 0,
            'tax'        => 0,
            'total'      => $subtotal,
        ];
    }

    $currency_symbol = get_woocommerce_currency_symbol();
    if (empty($currency_symbol) || $currency_symbol === 'IRR') $currency_symbol = 'ریال';
    if ($currency_symbol === 'IRT') $currency_symbol = 'تومان';

    $totals = [
        'subtotal'        => (float)WC()->cart->get_subtotal(),
        'discount'        => (float)WC()->cart->get_discount_total(),
        'shipping'        => (float)WC()->cart->get_shipping_total(),
        'shipping_method' => 'محاسبه در تسویه حساب',
        'tax'             => (float)WC()->cart->get_total_tax(),
        'grand_total'     => (float)WC()->cart->total,
        'currency'        => $currency_symbol,
        'payment_method'  => 'پیش‌فاکتور (نامشخص)',
    ];

    $totals['grand_total_words'] = woo_factor_number_to_words($totals['grand_total']);
    $totals['transaction_id'] = '';

    $seller['stamp_url'] = woo_factor_get_stamp_url();
    $seller['economic_code'] = $opts['shop_economic_code'] ?? '';
    $seller['registration_no'] = $opts['shop_registration_no'] ?? '';
    $seller['postal_code'] = $opts['shop_postal_code'] ?? '';

    $data = [
        'type'                 => 'proforma',
        'order_id'             => 0,
        'order_number'         => 'پیش‌فاکتور',
        'invoice_number'       => 'PRF-' . rand(1000, 9999),
        'jalali_date'          => woo_factor_jdate(current_time('timestamp'), false),
        'jalali_time'          => woo_factor_jdate(current_time('timestamp'), true),
        'status'               => 'proforma',
        'status_name'          => 'پیش‌فاکتور (استعلام قیمت)',
        'seller'               => $seller,
        'buyer'                => $buyer,
        'items'                => $items,
        'totals'               => $totals,
        'barcode_svg'          => Woo_Factor_Barcode_128::get_svg('PROFORMA', 40, 1.5),
        'customer_note'        => '',
        'footer_note'          => 'این برگه صرفاً پیش‌فاکتور و استعلام قیمت بوده و فاقد ارزش رسمی مالیاتی است.',
        'invoice_terms'        => 'مدت اعتبار این پیش‌فاکتور از تاریخ صدور به مدت ۴۸ ساعت کاری می‌باشد.',
        'signature_stamp'      => $opts['signature_stamp'] ?? 'مهر و امضای فروشگاه',
        'stamp_url'            => $seller['stamp_url'],
        'color'                => $opts['color'] ?? '#475569',
        'watermark_text'       => 'پیش‌فاکتور',
        'show_barcode'         => true,
        'show_product_image'   => true,
        'show_sku'             => true,
        'show_tax_column'      => true,
        'show_discount_column' => true,
        'show_watermark'       => true,
        'show_signature'       => true,
    ];

    echo Woo_Factor_Renderer::render_html($data, $opts['template'] ?? 'modern');
    exit;
}
