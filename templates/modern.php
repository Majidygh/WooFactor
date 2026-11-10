<?php
/**
 * Template: Modern Standard (قالب مدرن و استاندارد)
 * Professional, clean corporate & e-commerce invoice.
 * Anti-slop: zero gratuitous gradients, zero glowing pill badges, print & ink friendly.
 */
defined('ABSPATH') || exit;

$opts = $data['options'] ?? (function_exists('woo_factor_options') ? woo_factor_options() : []);
$brand = !empty($opts['brand_color']) ? sanitize_hex_color($opts['brand_color']) : '#0f766e';
if (!$brand) {
    $brand = '#0f766e';
}

$seller = $data['seller'] ?? [];
$buyer = $data['buyer'] ?? [];
$items = $data['items'] ?? [];
$totals = $data['totals'] ?? [];
$currency = $totals['currency'] ?? 'تومان';

$is_proforma = !empty($data['is_proforma']) || (!empty($data['payment_status']) && $data['payment_status'] === 'proforma');
$invoice_title = !empty($data['invoice_title']) ? $data['invoice_title'] : ($is_proforma ? 'پیش‌فاکتور خرید' : 'فاکتور فروش کالا و خدمات');

$logo_url = !empty($seller['logo_url']) ? $seller['logo_url'] : (function_exists('woo_factor_get_logo_url') ? woo_factor_get_logo_url() : '');
$stamp_url = !empty($data['stamp_url']) ? $data['stamp_url'] : (function_exists('woo_factor_get_stamp_url') ? woo_factor_get_stamp_url() : '');

$has_sku = false;
$has_discount = !empty($totals['discount']) && (float)$totals['discount'] > 0;
foreach ($items as $it) {
    if (!empty($it['sku']) && trim($it['sku']) !== '' && trim($it['sku']) !== '-') {
        $has_sku = true;
    }
    if (!empty($it['discount']) && (float)$it['discount'] > 0) {
        $has_discount = true;
    }
}

$status_title = !empty($data['status_name']) ? $data['status_name'] : (!empty($data['status']) ? $data['status'] : '');
$status_key = strtolower($data['status'] ?? ($data['payment_status'] ?? ''));
$is_paid = in_array($status_key, ['completed', 'processing', 'paid']) || (!empty($data['watermark_text']) && strpos($data['watermark_text'], 'پرداخت شد') !== false);
$is_pending = in_array($status_key, ['pending', 'on-hold', 'unpaid']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html($invoice_title); ?> - <?php echo esc_html($data['invoice_number'] ?? $data['order_number'] ?? ''); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 10mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            direction: rtl;
            text-align: right;
            font-size: 11px;
            line-height: 1.5;
            padding: 24px 12px;
        }
        .invoice-sheet {
            position: relative;
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 24px 28px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 54px;
            font-weight: 800;
            color: rgba(15, 23, 42, 0.035);
            border: 4px dashed rgba(15, 23, 42, 0.05);
            padding: 10px 32px;
            border-radius: 6px;
            pointer-events: none;
            user-select: none;
            white-space: nowrap;
            z-index: 0;
        }

        /* Top Header */
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 16px;
            gap: 16px;
        }
        .inv-brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1.2;
        }
        .inv-logo-wrap img {
            max-height: 52px;
            max-width: 140px;
            object-fit: contain;
            display: block;
        }
        .inv-brand-text h1 {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
            line-height: 1.3;
        }
        .inv-brand-text .subtext {
            font-size: 10px;
            color: #64748b;
        }

        .inv-meta-box {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
            flex: 1;
        }
        .inv-doc-title {
            font-size: 16px;
            font-weight: 800;
            color: <?php echo esc_attr($brand); ?>;
            margin-bottom: 4px;
            letter-spacing: -0.2px;
        }
        .meta-line {
            display: flex;
            gap: 6px;
            font-size: 10.5px;
            color: #475569;
        }
        .meta-line strong {
            color: #0f172a;
            font-weight: 700;
        }
        .status-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 3px;
            margin-top: 2px;
        }
        .status-badge-paid {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .status-badge-pending {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .status-badge-other {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        /* Parties Grid */
        .parties-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .party-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background: #ffffff;
            overflow: hidden;
        }
        .party-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .party-body {
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 10px;
        }
        .party-row {
            display: flex;
            gap: 4px;
            line-height: 1.4;
        }
        .party-label {
            color: #64748b;
            flex-shrink: 0;
            min-width: 75px;
        }
        .party-val {
            color: #0f172a;
            font-weight: 600;
            word-break: break-word;
        }

        /* Items Table */
        .items-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 16px;
            background: #ffffff;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            text-align: center;
        }
        .items-table th {
            background: #1e293b;
            color: #ffffff;
            font-weight: 700;
            padding: 8px 6px;
            white-space: nowrap;
            border-bottom: 1px solid #0f172a;
        }
        .items-table td {
            padding: 7px 6px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .items-table td.product-col {
            text-align: right;
            padding-right: 10px;
        }
        .product-meta-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .item-thumb {
            width: 30px;
            height: 30px;
            border-radius: 3px;
            border: 1px solid #e2e8f0;
            object-fit: cover;
            flex-shrink: 0;
        }
        .item-text-title {
            font-weight: 700;
            color: #0f172a;
        }
        .item-sub-desc {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 1px;
        }
        .items-table td.num-col {
            text-align: left;
            padding-left: 8px;
            font-feature-settings: "tnum";
            font-weight: 600;
        }

        /* Bottom Section: Notes & Totals */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 16px;
            margin-bottom: 12px;
            align-items: start;
        }
        .bottom-left-notes {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .note-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            background: #f8fafc;
            font-size: 9.5px;
            line-height: 1.5;
            color: #334155;
        }
        .note-card-title {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 3px;
        }

        /* Totals Card */
        .totals-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
            background: #ffffff;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .totals-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .totals-table td.t-lbl {
            color: #475569;
            text-align: right;
        }
        .totals-table td.t-val {
            font-weight: 700;
            color: #0f172a;
            text-align: left;
            font-feature-settings: "tnum";
        }
        .totals-table tr.grand-row td {
            background: #0f172a;
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            padding: 8px 10px;
            border-bottom: none;
        }
        .totals-table tr.grand-row td.t-val {
            color: #ffffff;
            font-size: 13px;
        }

        /* Words Strip */
        .words-strip {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 10px;
            color: #334155;
            margin-bottom: 16px;
            display: flex;
            gap: 6px;
        }
        .words-strip strong {
            color: #0f172a;
            font-weight: 700;
        }

        /* Footer Strip: Stamp & Barcode */
        .footer-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            margin-top: 8px;
        }
        .footer-barcode {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .footer-stamp-box {
            text-align: center;
            min-width: 120px;
        }
        .stamp-seal-img {
            max-height: 60px;
            max-width: 120px;
            object-fit: contain;
        }
        .stamp-seal-placeholder {
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 9.5px;
            color: #64748b;
        }

        /* Print Media Styles: Ink-friendly & Strict Margins */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .invoice-sheet {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .items-table th {
                background: #f1f5f9 !important;
                color: #0f172a !important;
                border-bottom: 1.5px solid #0f172a !important;
            }
            .totals-table tr.grand-row td {
                background: #ffffff !important;
                color: #0f172a !important;
                border-top: 2px solid #0f172a !important;
                border-bottom: 2px solid #0f172a !important;
            }
            .totals-table tr.grand-row td.t-val {
                color: #0f172a !important;
            }
            .party-header, .note-card, .words-strip {
                background: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="invoice-sheet">
    <?php if (!empty($data['watermark_text'])): ?>
        <div class="watermark"><?php echo esc_html($data['watermark_text']); ?></div>
    <?php endif; ?>

    <!-- Header -->
    <header class="inv-header">
        <div class="inv-brand-box">
            <?php if ($logo_url): ?>
                <div class="inv-logo-wrap">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($seller['name'] ?? ''); ?>">
                </div>
            <?php endif; ?>
            <div class="inv-brand-text">
                <h1><?php echo esc_html($seller['name'] ?? get_bloginfo('name')); ?></h1>
                <?php if (!empty($seller['registration_no'])): ?>
                    <div class="subtext">شماره ثبت: <?php echo woo_factor_fa_digits($seller['registration_no']); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="inv-meta-box">
            <div class="inv-doc-title"><?php echo esc_html($invoice_title); ?></div>
            <div class="meta-line">
                <span>شماره:</span>
                <strong><?php echo woo_factor_fa_digits($data['invoice_number'] ?? $data['order_number'] ?? '-'); ?></strong>
            </div>
            <div class="meta-line">
                <span>تاریخ:</span>
                <strong><?php echo woo_factor_fa_digits($data['jalali_date'] ?? '-'); ?></strong>
                <?php if (!empty($data['jalali_time'])): ?>
                    <span>- <?php echo woo_factor_fa_digits($data['jalali_time']); ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($status_title)): ?>
                <div>
                    <?php if ($is_paid): ?>
                        <span class="status-badge status-badge-paid">✔ <?php echo esc_html($status_title); ?></span>
                    <?php elseif ($is_pending): ?>
                        <span class="status-badge status-badge-pending">⏳ <?php echo esc_html($status_title); ?></span>
                    <?php else: ?>
                        <span class="status-badge status-badge-other"><?php echo esc_html($status_title); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Parties Grid -->
    <div class="parties-grid">
        <!-- Seller -->
        <div class="party-card">
            <div class="party-header">
                <span>مشخصات فروشنده</span>
            </div>
            <div class="party-body">
                <div class="party-row">
                    <span class="party-label">فروشنده:</span>
                    <span class="party-val"><?php echo esc_html($seller['name'] ?? '-'); ?></span>
                </div>
                <?php if (!empty($seller['national_id'])): ?>
                    <div class="party-row">
                        <span class="party-label">شناسه ملی:</span>
                        <span class="party-val"><?php echo woo_factor_fa_digits($seller['national_id']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($seller['economic_code']) || !empty($seller['economic_id'])): ?>
                    <div class="party-row">
                        <span class="party-label">کد اقتصادی:</span>
                        <span class="party-val"><?php echo woo_factor_fa_digits($seller['economic_code'] ?? $seller['economic_id']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($seller['phone'])): ?>
                    <div class="party-row">
                        <span class="party-label">تلفن:</span>
                        <span class="party-val"><?php echo woo_factor_fa_digits($seller['phone']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($seller['postal_code'])): ?>
                    <div class="party-row">
                        <span class="party-label">کد پستی:</span>
                        <span class="party-val"><?php echo woo_factor_fa_digits($seller['postal_code']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="party-row">
                    <span class="party-label">نشانی:</span>
                    <span class="party-val"><?php echo esc_html($seller['address'] ?? '-'); ?></span>
                </div>
            </div>
        </div>

        <!-- Buyer -->
        <div class="party-card">
            <div class="party-header">
                <span>مشخصات خریدار</span>
            </div>
            <div class="party-body">
                <div class="party-row">
                    <span class="party-label">خریدار:</span>
                    <span class="party-val"><?php echo esc_html(!empty($buyer['company']) ? $buyer['company'] . ' (' . ($buyer['name'] ?? '') . ')' : ($buyer['name'] ?? '-')); ?></span>
                </div>
                <?php if (!empty($buyer['national_id']) || !empty($buyer['national_code'])): ?>
                    <div class="party-row">
                        <span class="party-label">کد ملی / شناسه:</span>
                        <span class="party-val"><?php echo woo_factor_fa_digits($buyer['national_id'] ?? $buyer['national_code']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($buyer['economic_id'])): ?>
                    <div class="party-row">
                        <span class="party-label">کد اقتصادی:</span>
                        <span class="party-val"><?php echo woo_factor_fa_digits($buyer['economic_id']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($buyer['phone'])): ?>
                    <div class="party-row">
                        <span class="party-label">شماره تماس:</span>
                        <span class="party-val"><?php echo woo_factor_fa_digits($buyer['phone']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($buyer['postcode'])): ?>
                    <div class="party-row">
                        <span class="party-label">کد پستی:</span>
                        <span class="party-val"><?php echo woo_factor_fa_digits($buyer['postcode']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="party-row">
                    <span class="party-label">نشانی تحویل:</span>
                    <span class="party-val"><?php echo esc_html($buyer['full_address'] ?? $buyer['address'] ?? '-'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="items-box">
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: <?php echo $has_sku ? '35%' : '45%'; ?>; text-align: right; padding-right: 10px;">شرح کالا یا خدمات</th>
                    <?php if ($has_sku): ?>
                        <th style="width: 10%;">کد کالا (SKU)</th>
                    <?php endif; ?>
                    <th style="width: 15%;">قیمت واحد (<?php echo esc_html($currency); ?>)</th>
                    <th style="width: 8%;">تعداد</th>
                    <?php if ($has_discount): ?>
                        <th style="width: 12%;">تخفیف</th>
                    <?php endif; ?>
                    <th style="width: 15%;">مبلغ کل</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1; 
                foreach ($items as $item): 
                    $qty = max(1, (float)($item['qty'] ?? 1));
                    $u_price = !empty($item['unit_price']) ? (float)$item['unit_price'] : (!empty($item['price']) ? (float)$item['price'] : 0);
                    if ($u_price <= 0) {
                        $u_price = (!empty($item['subtotal']) ? (float)$item['subtotal'] : (float)($item['total'] ?? 0)) / $qty;
                    }
                    $thumb = !empty($item['thumbnail_url']) ? $item['thumbnail_url'] : '';
                ?>
                    <tr>
                        <td><?php echo woo_factor_fa_digits($i++); ?></td>
                        <td class="product-col">
                            <div class="product-meta-wrap">
                                <?php if (!empty($data['show_product_image']) && $thumb): ?>
                                    <img src="<?php echo esc_url($thumb); ?>" alt="" class="item-thumb">
                                <?php endif; ?>
                                <div>
                                    <div class="item-text-title"><?php echo esc_html($item['title'] ?? ''); ?></div>
                                    <?php if (!empty($item['meta'])): ?>
                                        <div class="item-sub-desc"><?php echo esc_html($item['meta']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <?php if ($has_sku): ?>
                            <td class="num-col" style="text-align: center;"><?php echo !empty($item['sku']) && $item['sku'] !== '-' ? woo_factor_fa_digits($item['sku']) : '-'; ?></td>
                        <?php endif; ?>
                        <td class="num-col"><?php echo woo_factor_number_format($u_price); ?></td>
                        <td><?php echo woo_factor_fa_digits($qty); ?></td>
                        <?php if ($has_discount): ?>
                            <td class="num-col"><?php echo !empty($item['discount']) && (float)$item['discount'] > 0 ? woo_factor_number_format($item['discount']) : '-'; ?></td>
                        <?php endif; ?>
                        <td class="num-col"><?php echo woo_factor_number_format($item['total'] ?? ($u_price * $qty)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Bottom Section -->
    <div class="bottom-grid">
        <div class="bottom-left-notes">
            <?php if (!empty($data['payment_method']) || !empty($totals['payment_method'])): ?>
                <div class="note-card">
                    <div class="note-card-title">روش پرداخت:</div>
                    <div><?php echo esc_html($data['payment_method'] ?? $totals['payment_method']); ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($data['customer_note'])): ?>
                <div class="note-card">
                    <div class="note-card-title">یادداشت خریدار:</div>
                    <div><?php echo esc_html($data['customer_note']); ?></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($data['footer_note'])): ?>
                <div class="note-card">
                    <div class="note-card-title">توضیحات و شرایط:</div>
                    <div><?php echo esc_html($data['footer_note']); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="totals-card">
            <table class="totals-table">
                <tbody>
                    <tr>
                        <td class="t-lbl">جمع کل اقلام:</td>
                        <td class="t-val"><?php echo woo_factor_number_format($totals['subtotal'] ?? 0); ?> <?php echo esc_html($currency); ?></td>
                    </tr>
                    <?php if (!empty($totals['discount']) && (float)$totals['discount'] > 0): ?>
                        <tr>
                            <td class="t-lbl">مجموع تخفیف:</td>
                            <td class="t-val" style="color: #b91c1c;">-<?php echo woo_factor_number_format($totals['discount']); ?> <?php echo esc_html($currency); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($totals['shipping']) && (float)$totals['shipping'] > 0): ?>
                        <tr>
                            <td class="t-lbl">هزینه ارسال:</td>
                            <td class="t-val"><?php echo woo_factor_number_format($totals['shipping']); ?> <?php echo esc_html($currency); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($totals['tax']) && (float)$totals['tax'] > 0): ?>
                        <tr>
                            <td class="t-lbl">مالیات بر ارزش افزوده:</td>
                            <td class="t-val"><?php echo woo_factor_number_format($totals['tax']); ?> <?php echo esc_html($currency); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="grand-row">
                        <td class="t-lbl">مبلغ کل پرداختی:</td>
                        <td class="t-val"><?php echo woo_factor_number_format($totals['grand_total'] ?? 0); ?> <?php echo esc_html($currency); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Words Strip -->
    <?php if (!empty($totals['words'])): ?>
        <div class="words-strip">
            <span>مبلغ به حروف:</span>
            <strong><?php echo esc_html($totals['words']); ?> <?php echo esc_html($currency); ?></strong>
        </div>
    <?php endif; ?>

    <!-- Footer Strip: Barcode & Stamp -->
    <footer class="footer-strip">
        <div class="footer-barcode">
            <?php if (!empty($data['show_barcode']) && !empty($data['barcode_svg'])): ?>
                <?php echo $data['barcode_svg']; ?>
                <div style="font-size: 9px; font-weight: 700; color: #64748b; letter-spacing: 0.5px; text-align: center;">
                    #<?php echo woo_factor_fa_digits($data['order_number'] ?? $data['invoice_number'] ?? ''); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer-stamp-box">
            <?php if ($stamp_url): ?>
                <img src="<?php echo esc_url($stamp_url); ?>" alt="مهر فروشگاه" class="stamp-seal-img">
            <?php else: ?>
                <div class="stamp-seal-placeholder">
                    محل مهر و امضای فروشنده
                </div>
            <?php endif; ?>
        </div>
    </footer>
</div>

</body>
</html>
