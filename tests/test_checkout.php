<?php
// Run in separate processes so hook registration reflects the saved option.
define('ABSPATH', __DIR__ . '/');
$hooks = [];
function add_action($name, $callback, ...$args) { $GLOBALS['hooks'][$name][] = $callback; }
function add_filter($name, $callback, ...$args) { add_action($name, $callback); }
$GLOBALS['stored'] = ($argv[1] ?? 'no') === 'yes' ? ['woo_factor_customer_fields_default_off' => '2.2.0'] : [];
function update_option($name, $value) { $GLOBALS['stored'][$name] = $value; return true; }
function get_option($name, $fallback = false) {
    if (array_key_exists($name, $GLOBALS['stored'])) return $GLOBALS['stored'][$name];
    if ($name === 'woo_factor_options') return ['enable_checkout_customer_type' => $GLOBALS['argv'][1] ?? 'no', 'shop_name' => 'Custom sender', 'shop_address' => 'Custom address'];
    return ['woocommerce_store_address' => 'Store street', 'woocommerce_store_address_2' => 'Unit 2', 'woocommerce_store_city' => 'Tehran', 'woocommerce_store_postcode' => '1234567890'][$name] ?? $fallback;
}
function get_bloginfo($name) { return 'Test shop'; }
function wp_parse_args($args, $defaults) { return array_merge($defaults, $args); }
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'https://example.test/'; }
function is_admin() { return false; }
function register_activation_hook(...$args) {}
function register_deactivation_hook(...$args) {}
function add_shortcode(...$args) {}
require dirname(__DIR__) . '/woo-factor.php';
function check($condition, $message) { if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } echo "PASS: $message\n"; }
check(woo_factor_default_options()['enable_checkout_customer_type'] === 'no', 'customer legal fields default off');
check(woo_factor_default_options()['shop_address'] === 'Tehran، Store street، Unit 2', 'sender address defaults from WooCommerce');
check(woo_factor_default_options()['shop_postal_code'] === '1234567890', 'sender postcode defaults from WooCommerce');
check(woo_factor_options()['shop_name'] === 'Custom sender' && woo_factor_options()['shop_address'] === 'Custom address', 'custom sender survives defaults');
function sanitize_text_field($value) { return is_scalar($value) ? strip_tags((string) $value) : ''; }
function sanitize_textarea_field($value) { return sanitize_text_field($value); }
function absint($value) { return abs((int) $value); }
$clean = woo_factor_sanitize_options(['shop_name' => 'Edited sender']);
check($clean['show_barcode'] === 'no', 'unchecked toggles stay disabled');
check($clean['shop_address'] === 'Custom address', 'unsubmitted sender settings survive');
$enabled = ($argv[1] ?? 'no') === 'yes';
check(isset($hooks['woocommerce_billing_fields']) === $enabled, 'billing hook only registered on opt-in');
check(isset($hooks['woocommerce_checkout_create_order']) === $enabled, 'save hook only registered on opt-in');
$legacy_enabled = ['enable_checkout_customer_type' => 'yes', 'shop_name' => 'Custom sender'];
unset($GLOBALS['stored']['woo_factor_customer_fields_default_off']);
$GLOBALS['stored']['woo_factor_options'] = $legacy_enabled;
woo_factor_migrate_customer_fields_default_off();
check(get_option('woo_factor_options')['enable_checkout_customer_type'] === 'no' && get_option('woo_factor_options')['shop_name'] === 'Custom sender', 'legacy default-on is reset once without touching sender');
$GLOBALS['stored']['woo_factor_options']['enable_checkout_customer_type'] = 'yes';
woo_factor_migrate_customer_fields_default_off();
check(get_option('woo_factor_options')['enable_checkout_customer_type'] === 'yes', 'later explicit opt-in is not reset again');
unset($GLOBALS['stored']['woo_factor_options'], $GLOBALS['stored']['woo_factor_customer_fields_default_off']);
