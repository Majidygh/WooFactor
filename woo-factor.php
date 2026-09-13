<?php
/**
 * Plugin Name: فاکتورساز حرفه ای ووفاکتور | WooFactor
 * Plugin URI:  https://github.com/Majidygh/WooFactor
 * Description: پیشرفته‌ترین افزونه صدور و چاپ فاکتور رسمی دارایی، مدرن و فیش‌پرینتر حرارتی برای ووکامرس — تفکیک اشخاص حقیقی و حقوقی، سامانه پیامک خدماتی، برچسب پستی مرسوله، صدور فاکتور دستی، پیش‌فاکتور سبد خرید و چاپ گروهی سازگار با HPOS.
 * Version:     2.1.4
 * Author:      Majidygh
 * Author URI:  https://github.com/Majidygh
 * Text Domain: woo-factor
 * Domain Path: /languages
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 */

defined('ABSPATH') || exit;

define('WOO_FACTOR_VERSION', '2.1.4');
define('WOO_FACTOR_FILE', __FILE__);
define('WOO_FACTOR_DIR', plugin_dir_path(__FILE__));
define('WOO_FACTOR_URL', plugin_dir_url(__FILE__));

// Declare official compatibility with WooCommerce High-Performance Order Storage (HPOS)
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

require_once WOO_FACTOR_DIR . 'includes/jalali.php';
require_once WOO_FACTOR_DIR . 'includes/persian-numbers.php';
require_once WOO_FACTOR_DIR . 'includes/helpers.php';
require_once WOO_FACTOR_DIR . 'includes/barcode.php';
require_once WOO_FACTOR_DIR . 'includes/sms.php';
require_once WOO_FACTOR_DIR . 'includes/builder.php';
require_once WOO_FACTOR_DIR . 'includes/render.php';
require_once WOO_FACTOR_DIR . 'includes/pdf.php';
require_once WOO_FACTOR_DIR . 'includes/hooks.php';
require_once WOO_FACTOR_DIR . 'includes/proforma.php';
require_once WOO_FACTOR_DIR . 'includes/shortcode.php';

if (is_admin()) {
    require_once WOO_FACTOR_DIR . 'admin/settings.php';
    require_once WOO_FACTOR_DIR . 'admin/meta-boxes.php';
}

register_activation_hook(__FILE__, 'woo_factor_activate');
register_deactivation_hook(__FILE__, 'woo_factor_deactivate');

function woo_factor_activate() {
    $opts = get_option('woo_factor_options', []);
    if (!is_array($opts)) {
        $opts = [];
    }
    $defaults = woo_factor_default_options();
    update_option('woo_factor_options', array_merge($defaults, $opts));

    if (!wp_next_scheduled('woo_factor_daily_retention_cron')) {
        wp_schedule_event(strtotime('tomorrow 03:30'), 'daily', 'woo_factor_daily_retention_cron');
    }
    woo_factor_ensure_invoice_dir();
}

function woo_factor_deactivate() {
    $ts = wp_next_scheduled('woo_factor_daily_retention_cron');
    if ($ts) {
        wp_unschedule_event($ts, 'woo_factor_daily_retention_cron');
    }
}

function woo_factor_default_options() {
    return [
        'template'                     => 'classic',
        'logo_id'                      => 0,
        'stamp_image_id'               => 0,
        'shop_name'                    => get_bloginfo('name'),
        'shop_phone'                   => '',
        'sender_mobile'                => '',
        'shop_email'                   => get_bloginfo('admin_email'),
        'shop_address'                 => '',
        'shop_national_id'             => '',
        'shop_economic_code'           => '',
        'shop_registration_no'         => '',
        'shop_postal_code'             => '',
        'footer_note'                  => 'از خرید و اعتماد شما به فروشگاه ما سپاسگزاریم.',
        'invoice_terms'                => '۱- ارائه اصل یا تصویر این فاکتور جهت دریافت خدمات پشتیبانی الزامی است. ۲- تعویض یا مرجوعی کالا مطابق ضوابط فروشگاه تا ۷ روز امکان‌پذیر است.',
        'color'                        => '#0f766e',
        'auto_send'                    => 'yes',
        'auto_send_status'             => 'wc-completed',
        'attach_woocommerce'           => 'yes',
        'myaccount_page'               => 'yes',
        'show_barcode'                 => 'yes',
        'show_product_image'           => 'yes',
        'show_sku'                     => 'yes',
        'show_tax_column'              => 'yes',
        'show_discount_column'         => 'yes',
        'show_watermark'               => 'yes',
        'watermark_text'               => '',
        'show_signature'               => 'yes',
        'signature_stamp'              => 'مهر و امضای فروشگاه',
        'invoice_prefix'               => '',
        'number_mode'                  => 'auto',
        'enable_checkout_customer_type'=> 'yes',
        'sms_enabled'                  => 'no',
        'sms_gateway'                  => 'ippanel',
        'sms_api_key'                  => '',
        'sms_sender'                   => '',
        'sms_pattern'                  => '',
        'sms_trigger_status'           => 'completed',
    ];
}

add_action('admin_notices', function () {
    if (class_exists('WooCommerce')) {
        return;
    }
    echo '<div class="notice notice-error"><p>';
    echo esc_html__('افزونه «WooFactor» برای کارکرد صحیح به ووکامرس نیاز دارد.', 'woo-factor');
    echo '</p></div>';
});

// Daily retention: purge manual invoice PDFs older than N days (option-controlled)
add_action('woo_factor_daily_retention_cron', function () {
    $opts = woo_factor_options();
    $days = isset($opts['retention_days']) ? (int) $opts['retention_days'] : 90;
    if ($days <= 0 || !class_exists('WP_Filesystem_Direct')) {
        return;
    }
    $dir = woo_factor_ensure_invoice_dir();
    $threshold = time() - ($days * DAY_IN_SECONDS);
    $files = glob($dir . '/*.pdf');
    if (!is_array($files)) {
        return;
    }
    foreach ($files as $file) {
        if (is_file($file) && filemtime($file) < $threshold) {
            @unlink($file);
        }
    }
});
