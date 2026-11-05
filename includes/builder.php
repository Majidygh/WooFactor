<?php
/**
 * Data Builder - Normalizes WooCommerce Order or Manual data into unified Invoice Model
 */
defined('ABSPATH') || exit;

class Woo_Factor_Invoice_Builder {

    public static function build_from_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $opts = woo_factor_options();

        // Invoice Number logic
        $custom_inv_num = $order->get_meta('_woo_factor_invoice_number');
        if (empty($custom_inv_num)) {
            $prefix = !empty($opts['invoice_prefix']) ? $opts['invoice_prefix'] : '';
            $custom_inv_num = $prefix . $order->get_order_number();
            $order->update_meta_data('_woo_factor_invoice_number', $custom_inv_num);
            $order->save();
        }

        // Date logic
        $date_created = $order->get_date_created();
        $timestamp = $date_created ? $date_created->getTimestamp() : current_time('timestamp');
        $jalali_date = woo_factor_jdate($timestamp, false);
        $jalali_time = woo_factor_jdate($timestamp, true);

        // Buyer Customer Type (Natural / Legal)
        $customer_type = $order->get_meta('_billing_customer_type') 
            ?: $order->get_meta('billing_customer_type') 
            ?: (!empty($order->get_billing_company()) ? 'legal' : 'natural');

        $national_code = $order->get_meta('_billing_national_code') 
            ?: $order->get_meta('billing_national_code') 
            ?: $order->get_meta('national_code') 
            ?: $order->get_meta('_national_code') 
            ?: $order->get_meta('billing_melli_code') 
            ?: '';

        $economic_code = $order->get_meta('_billing_economic_code') 
            ?: $order->get_meta('billing_economic_code') 
            ?: $order->get_meta('economic_code')
            ?: $order->get_meta('_economic_code')
            ?: '';

        $registration_no = $order->get_meta('_billing_registration_no')
            ?: $order->get_meta('billing_registration_no')
            ?: '';

        $buyer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        if (empty($buyer_name)) {
            $buyer_name = $order->get_formatted_billing_full_name();
        }

        $buyer = [
            'name'             => $buyer_name ?: 'مشتری محترم',
            'customer_type'    => $customer_type,
            'company'          => $order->get_billing_company(),
            'national_id'      => $national_code,
            'economic_id'      => $economic_code,
            'registration_no'  => $registration_no,
            'phone'            => $order->get_billing_phone(),
            'email'            => $order->get_billing_email(),
            'state'            => $order->get_billing_state(),
            'city'             => $order->get_billing_city(),
            'postcode'         => $order->get_billing_postcode(),
            'address_1'        => $order->get_billing_address_1(),
            'address_2'        => $order->get_billing_address_2(),
            'full_address'     => self::get_full_address($order, 'billing'),
            'shipping_address' => self::get_full_address($order, 'shipping'),
            'shipping_name'    => trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()) ?: $buyer_name,
            'shipping_phone'   => $order->get_meta('_shipping_phone') ?: $order->get_billing_phone(),
            'shipping_postcode'=> $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
        ];

        // Seller Data
        $seller = [
            'name'            => !empty($opts['shop_name']) ? $opts['shop_name'] : get_bloginfo('name'),
            'phone'           => $opts['shop_phone'] ?? '',
            'sender_mobile'   => $opts['sender_mobile'] ?? '',
            'email'           => !empty($opts['shop_email']) ? $opts['shop_email'] : get_bloginfo('admin_email'),
            'national_id'     => $opts['shop_national_id'] ?? '',
            'economic_code'   => $opts['shop_economic_code'] ?? '',
            'registration_no' => $opts['shop_registration_no'] ?? '',
            'postal_code'     => $opts['shop_postal_code'] ?? '',
            'address'         => $opts['shop_address'] ?? '',
            'website'         => home_url(),
            'logo_url'        => woo_factor_get_logo_url(),
            'stamp_url'       => woo_factor_get_stamp_url(),
        ];

        // Items
        $items = [];
        $index = 1;
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $qty = $item->get_quantity();
            $subtotal = (float)$item->get_subtotal();
            $total = (float)$item->get_total();
            $tax = (float)$item->get_total_tax();
            $unit_price = $qty > 0 ? ($subtotal / $qty) : 0;
            $discount = max(0, $subtotal - $total);

            $sku = $product ? $product->get_sku() : '';
            $product_id = $product ? $product->get_id() : 0;

            // Product thumbnail
            $thumbnail_url = '';
            if ($product) {
                $image_id = $product->get_image_id();
                if ($image_id) {
                    $img_src = wp_get_attachment_image_src($image_id, 'thumbnail');
                    if ($img_src) {
                        $thumbnail_url = $img_src[0];
                    }
                }
            }

            // Variation attributes / item meta
            $meta_strings = [];
            $formatted_meta = $item->get_formatted_meta_data('');
            foreach ($formatted_meta as $m) {
                $meta_strings[] = esc_html($m->display_key) . ': ' . wp_strip_all_tags($m->display_value);
            }
            $meta_text = implode(' | ', $meta_strings);

            if ($unit_price <= 0 && $qty > 0 && $total > 0) {
                $unit_price = $total / $qty;
            }
            if ($unit_price <= 0 && $product) {
                $unit_price = (float)$product->get_price();
            }

            $items[] = [
                'index'         => $index++,
                'id'            => $product_id,
                'sku'           => $sku ?: '',
                'title'         => $item->get_name(),
                'meta'          => $meta_text,
                'thumbnail_url' => $thumbnail_url,
                'qty'           => $qty,
                'price'         => $unit_price,
                'unit_price'    => $unit_price,
                'subtotal'      => $subtotal > 0 ? $subtotal : ($unit_price * $qty),
                'discount'      => $discount,
                'tax'           => $tax,
                'total'         => $total + $tax,
            ];
        }

        // Totals
        $currency_symbol = get_woocommerce_currency_symbol($order->get_currency());
        if (empty($currency_symbol) || $currency_symbol === 'IRR') $currency_symbol = 'ریال';
        if ($currency_symbol === 'IRT' || $order->get_currency() === 'IRT') $currency_symbol = 'تومان';

        $grand_total = (float)$order->get_total();
        $totals = [
            'subtotal'             => (float)$order->get_subtotal(),
            'discount'             => (float)$order->get_discount_total(),
            'shipping'             => (float)$order->get_shipping_total(),
            'shipping_method'      => $order->get_shipping_method() ?: 'پست / پیک',
            'tax'                  => (float)$order->get_total_tax(),
            'grand_total'          => $grand_total,
            'grand_total_words'    => woo_factor_number_to_words($grand_total),
            'currency'             => $currency_symbol,
            'payment_method'       => $order->get_payment_method_title() ?: 'پرداخت آنلاین',
            'transaction_id'       => $order->get_transaction_id() ?: '',
        ];

        // Barcode (Order Number)
        $barcode_svg = Woo_Factor_Barcode_128::get_svg((string)$order->get_order_number(), 42, 1.5);

        // Watermark calculation
        $watermark_text = !empty($opts['watermark_text']) ? $opts['watermark_text'] : '';
        if (empty($watermark_text)) {
            $status = $order->get_status();
            switch ($status) {
                case 'completed':
                case 'processing':
                    $watermark_text = 'پرداخت شد';
                    break;
                case 'on-hold':
                case 'pending':
                    $watermark_text = 'در انتظار پرداخت';
                    break;
                case 'cancelled':
                case 'failed':
                    $watermark_text = 'باطل شد';
                    break;
                case 'refunded':
                    $watermark_text = 'مرجوع شد';
                    break;
                default:
                    $watermark_text = '';
                    break;
            }
        }

        return [
            'type'                 => 'order',
            'order_id'             => $order->get_id(),
            'order_number'         => $order->get_order_number(),
            'invoice_number'       => $custom_inv_num,
            'jalali_date'          => $jalali_date,
            'jalali_time'          => $jalali_time,
            'status'               => $order->get_status(),
            'status_name'          => wc_get_order_status_name($order->get_status()),
            'seller'               => $seller,
            'buyer'                => $buyer,
            'items'                => $items,
            'totals'               => $totals,
            'barcode_svg'          => $barcode_svg,
            'customer_note'        => $order->get_customer_note(),
            'footer_note'          => $opts['footer_note'] ?? 'از خرید و اعتماد شما سپاسگزاریم.',
            'invoice_terms'        => $opts['invoice_terms'] ?? '',
            'signature_stamp'      => $opts['signature_stamp'] ?? 'مهر و امضای فروشگاه',
            'stamp_url'            => $seller['stamp_url'],
            'color'                => woo_factor_normalize_color($opts['color'] ?? ''),
            'watermark_text'       => $watermark_text,
            'show_barcode'         => ($opts['show_barcode'] ?? 'yes') === 'yes',
            'show_product_image'   => ($opts['show_product_image'] ?? 'yes') === 'yes',
            'show_sku'             => ($opts['show_sku'] ?? 'yes') === 'yes',
            'show_tax_column'      => ($opts['show_tax_column'] ?? 'yes') === 'yes',
            'show_discount_column' => ($opts['show_discount_column'] ?? 'yes') === 'yes',
            'show_watermark'       => ($opts['show_watermark'] ?? 'yes') === 'yes',
            'show_signature'       => ($opts['show_signature'] ?? 'yes') === 'yes',
        ];
    }

    private static function get_full_address($order, $type = 'billing') {
        $getter_addr1 = "get_{$type}_address_1";
        $getter_addr2 = "get_{$type}_address_2";
        $getter_city  = "get_{$type}_city";
        $getter_state = "get_{$type}_state";
        $getter_post  = "get_{$type}_postcode";

        $addr1 = $order->$getter_addr1();
        $addr2 = $order->$getter_addr2();
        $city  = $order->$getter_city();
        $state_code = $order->$getter_state();
        $postcode = $order->$getter_post();

        $states = WC()->countries ? WC()->countries->get_states('IR') : [];
        $state_name = isset($states[$state_code]) ? $states[$state_code] : $state_code;

        $parts = array_filter([$state_name, $city, $addr1, $addr2]);
        $full = implode('، ', $parts);
        if ($postcode) {
            $full .= ' (کد پستی: ' . woo_factor_fa_digits($postcode) . ')';
        }
        return $full;
    }
}
