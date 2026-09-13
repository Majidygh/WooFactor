<?php
/**
 * Template 1: Classic Official Tax Standard (صورتحساب رسمی استاندارد مالیاتی)
 * Conforms to Article 19 of VAT law with clean, minimal, luxury fintech aesthetic.
 */
defined('ABSPATH') || exit;

$theme_color = woo_factor_normalize_color($data['color'] ?? '', '#0F766E');
$seller = $data['seller'] ?? [];
$buyer = $data['buyer'] ?? [];
$totals = $data['totals'] ?? [];
$items = $data['items'] ?? [];
$logo_url = !empty($seller['logo_url']) ? $seller['logo_url'] : '';
$stamp_url = !empty($data['stamp_url']) ? $data['stamp_url'] : '';
$currency = $totals['currency'] ?? 'تومان';
$is_legal_buyer = !empty($buyer['company']) || !empty($buyer['economic_id']) || (($buyer['customer_type'] ?? '') === 'legal');

$has_sku = false;
foreach ($items as $it) {
    if (!empty($it['sku']) && trim($it['sku']) !== '' && trim($it['sku']) !== '-') {
        $has_sku = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>صورتحساب رسمی - <?php echo esc_html($data['invoice_number']); ?></title>
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
            font-family: 'Vazirmatn', Tahoma, 'Segoe UI', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            direction: rtl;
            text-align: right;
            font-size: 10.5px;
            line-height: 1.5;
            padding: 20px 10px;
        }
        .invoice-box {
            position: relative;
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 22px 24px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-28deg);
            font-size: 56px;
            font-weight: 900;
            color: rgba(15, 23, 42, 0.04);
            border: 5px dashed rgba(15, 23, 42, 0.06);
            padding: 12px 36px;
            border-radius: 12px;
            pointer-events: none;
            user-select: none;
            white-space: nowrap;
            z-index: 0;
        }

        /* Top Header */
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 14px;
            gap: 12px;
        }
        .inv-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        .inv-logo img {
            max-height: 52px;
            max-width: 130px;
            object-fit: contain;
            display: block;
        }
        .inv-logo-placeholder {
            width: 50px;
            height: 50px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .inv-shop-title {
            font-size: 14px;
            font-weight: 900;
            color: #0f172a;
        }
        .inv-shop-sub {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .inv-header-center {
            text-align: center;
            flex: 1.3;
        }
        .inv-header-center h1 {
            font-size: 15px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.3px;
            margin-bottom: 3px;
        }
        .inv-header-center h2 {
            font-size: 10.5px;
            font-weight: 600;
            color: <?php echo esc_attr($theme_color); ?>;
        }
        .inv-header-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 4px;
        }
        .meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 10px;
            color: #475569;
            min-width: 155px;
            justify-content: space-between;
        }
        .meta-pill strong {
            color: #0f172a;
        }

        /* Parties: Symmetrical Modern Cards */
        .parties-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }
        .party-card {
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            padding: 10px 12px;
        }
        .party-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 7px;
            margin-bottom: 8px;
        }
        .party-badge {
            background: <?php echo esc_attr($theme_color); ?>;
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 800;
            padding: 3px 12px;
            border-radius: 14px;
            display: inline-block;
        }
        .party-badge-buyer {
            background: #0f172a;
        }
        .entity-tag {
            font-size: 9.5px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .party-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 10px;
            font-size: 10px;
        }
        .party-info-row {
            display: flex;
            gap: 4px;
            align-items: baseline;
        }
        .party-info-row.full-width {
            grid-column: span 2;
        }
        .p-lbl {
            color: #64748b;
            flex-shrink: 0;
        }
        .p-val {
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        /* Products Table with Dark Sleek Header */
        .items-table-wrapper {
            margin-bottom: 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            text-align: center;
        }
        .items-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 800;
            padding: 8px 6px;
            border-left: 1px solid #334155;
            white-space: nowrap;
        }
        .items-table th:last-child {
            border-left: none;
        }
        .items-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
            vertical-align: middle;
            color: #1e293b;
        }
        .items-table td:last-child {
            border-left: none;
        }
        .items-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .items-table td.title-cell {
            text-align: right;
            padding-right: 10px;
            font-weight: 600;
        }
        .items-table td.num-cell {
            text-align: left;
            padding-left: 8px;
            font-feature-settings: "tnum";
        }
        .item-sku {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Totals & Bottom Section */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr 1.2fr;
            gap: 12px;
            margin-bottom: 12px;
            align-items: start;
        }
        .stamp-card {
            border: 1.5px dashed #cbd5e1;
            border-radius: 8px;
            min-height: 145px;
            padding: 8px 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            background: #fbfcfe;
        }
        .stamp-card-title {
            font-size: 10px;
            font-weight: 800;
            color: #475569;
        }
        .stamp-img {
            max-height: 75px;
            max-width: 140px;
            object-fit: contain;
            margin: 4px auto;
        }
        .barcode-middle {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 145px;
            text-align: center;
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }
        .totals-card {
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .totals-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .totals-table td.t-lbl {
            color: #475569;
            width: 50%;
        }
        .totals-table td.t-val {
            text-align: left;
            font-weight: 700;
            color: #0f172a;
            font-feature-settings: "tnum";
        }
        .grand-row td {
            background: <?php echo esc_attr($theme_color); ?>;
            color: #ffffff !important;
            font-size: 11.5px;
            font-weight: 900;
            border-bottom: none;
            padding: 8px 10px;
        }
        .grand-row td.t-val {
            color: #ffffff !important;
            font-size: 12.5px;
            font-weight: 900;
        }

        /* Words Bar */
        .words-bar {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        /* Footer terms */
        .footer-terms {
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 9px;
            color: #64748b;
            text-align: center;
            line-height: 1.5;
        }

        /* Mobile Responsive Screen Styles */
        @media screen and (max-width: 768px) {
            body {
                padding: 6px 4px;
            }
            .invoice-box {
                padding: 12px 10px;
                border-radius: 6px;
                box-shadow: none;
                width: 100%;
                overflow-x: hidden;
                box-sizing: border-box;
            }
            .inv-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                gap: 10px;
            }
            .inv-header-right {
                flex-direction: column;
                text-align: center;
                justify-content: center;
            }
            .inv-header-center h1 {
                font-size: 14px;
            }
            .inv-header-left {
                align-items: stretch;
                width: 100%;
            }
            .meta-pill {
                min-width: unset;
                width: 100%;
                padding: 4px 10px;
                justify-content: space-between;
                box-sizing: border-box;
            }
            .parties-grid {
                grid-template-columns: 1fr !important;
                gap: 10px;
            }
            .party-info-grid {
                grid-template-columns: 1fr !important;
                gap: 4px;
            }
            .party-info-row.full-width {
                grid-column: span 1 !important;
            }
            .items-table-wrapper {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
                width: 100%;
                margin-bottom: 10px;
            }
            .items-table {
                min-width: 520px;
            }
            .bottom-grid {
                grid-template-columns: 1fr !important;
                gap: 10px;
            }
            .watermark {
                font-size: 32px !important;
                padding: 8px 18px !important;
                max-width: 90% !important;
            }
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .invoice-box {
                box-shadow: none !important;
                border: 1.5px solid #cbd5e1 !important;
                border-radius: 0 !important;
                padding: 15px 18px !important;
                max-width: 100% !important;
                width: 100% !important;
                page-break-inside: avoid;
            }
            .no-print {
                display: none !important;
            }
            tr, td, th {
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <?php if (!empty($data['watermark_text'])): ?>
        <div class="watermark"><?php echo esc_html($data['watermark_text']); ?></div>
    <?php endif; ?>

    <!-- Top Header -->
    <header class="inv-header">
        <div class="inv-header-right">
            <?php if ($logo_url): ?>
                <div class="inv-logo">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($seller['name']); ?>">
                </div>
            <?php else: ?>
                <div class="inv-logo-placeholder">🏛️</div>
            <?php endif; ?>
            <div>
                <div class="inv-shop-title"><?php echo esc_html($seller['name']); ?></div>
                <div class="inv-shop-sub">فاکتور رسمی استاندارد دارایی</div>
            </div>
        </div>

        <div class="inv-header-center">
            <h1>صورتحساب رسمی فروش کالا و خدمات</h1>
        </div>

        <div class="inv-header-left">
            <div class="meta-pill">
                <span>شماره فاکتور:</span>
                <strong><?php echo woo_factor_fa_digits($data['invoice_number']); ?></strong>
            </div>
            <div class="meta-pill">
                <span>تاریخ صدور:</span>
                <strong><?php echo woo_factor_fa_digits($data['jalali_date']); ?></strong>
            </div>
            <div class="meta-pill">
                <span>زمان صدور:</span>
                <strong><?php echo woo_factor_fa_digits($data['jalali_time']); ?></strong>
            </div>
        </div>
    </header>

    <!-- Parties Grid: Seller & Buyer -->
    <div class="parties-grid">
        <!-- Seller -->
        <div class="party-card">
            <div class="party-top-bar">
                <span class="party-badge">فروشنده (مودی)</span>
                <span class="entity-tag">شخص حقوقی</span>
            </div>
            <div class="party-info-grid">
                <div class="party-info-row full-width">
                    <span class="p-lbl">نام فروشنده / شرکت:</span>
                    <span class="p-val"><?php echo esc_html($seller['name']); ?></span>
                </div>
                <div class="party-info-row">
                    <span class="p-lbl">شناسه ملی:</span>
                    <span class="p-val"><?php echo woo_factor_fa_digits($seller['national_id'] ?: '-'); ?></span>
                </div>
                <div class="party-info-row">
                    <span class="p-lbl">شماره اقتصادی:</span>
                    <span class="p-val"><?php echo woo_factor_fa_digits($seller['economic_id'] ?: '-'); ?></span>
                </div>
                <div class="party-info-row">
                    <span class="p-lbl">شماره ثبت:</span>
                    <span class="p-val"><?php echo woo_factor_fa_digits($seller['registration_no'] ?: '-'); ?></span>
                </div>
                <div class="party-info-row">
                    <span class="p-lbl">کد پستی:</span>
                    <span class="p-val"><?php echo woo_factor_fa_digits($seller['postal_code'] ?: '-'); ?></span>
                </div>
                <div class="party-info-row full-width">
                    <span class="p-lbl">نشانی:</span>
                    <span class="p-val"><?php echo esc_html($seller['address'] ?: '-'); ?></span>
                </div>
                <div class="party-info-row full-width">
                    <span class="p-lbl">تلفن:</span>
                    <span class="p-val"><?php echo woo_factor_fa_digits($seller['phone'] ?: '-'); ?></span>
                </div>
            </div>
        </div>

        <!-- Buyer -->
        <div class="party-card">
            <div class="party-top-bar">
                <span class="party-badge party-badge-buyer">خریدار</span>
                <span class="entity-tag"><?php echo $is_legal_buyer ? 'شخص حقوقی' : 'شخص حقیقی'; ?></span>
            </div>
            <div class="party-info-grid">
                <div class="party-info-row full-width">
                    <span class="p-lbl">نام خریدار / شرکت:</span>
                    <span class="p-val">
                        <?php echo esc_html(!empty($buyer['company']) ? $buyer['company'] . ' (' . $buyer['name'] . ')' : $buyer['name']); ?>
                    </span>
                </div>
                <div class="party-info-row">
                    <span class="p-lbl">کد ملی / شناسه:</span>
                    <span class="p-val"><?php echo woo_factor_fa_digits($buyer['national_code'] ?: '-'); ?></span>
                </div>
                <div class="party-info-row">
                    <span class="p-lbl">شماره اقتصادی:</span>
                    <span class="p-val"><?php echo woo_factor_fa_digits($buyer['economic_id'] ?: '-'); ?></span>
                </div>
                <div class="party-info-row">
                    <span class="p-lbl">شماره ثبت:</span>
                    <span class="p-val"><?php echo woo_factor_fa_digits($buyer['registration_no'] ?: '-'); ?></span>
                </div>
                <div class="party-info-row">
                    <span class="p-lbl">کد پستی:</span>
                    <span class="p-val"><?php echo woo_factor_fa_digits($buyer['postcode'] ?: '-'); ?></span>
                </div>
                <div class="party-info-row full-width">
                    <span class="p-lbl">نشانی کامل:</span>
                    <span class="p-val"><?php echo esc_html($buyer['full_address'] ?: '-'); ?></span>
                </div>
                <div class="party-info-row full-width">
                    <span class="p-lbl">تلفن همراه:</span>
                    <span class="p-val"><?php echo woo_factor_fa_digits($buyer['phone'] ?: '-'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="items-table-wrapper">
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">ردیف</th>
                    <th style="width: <?php echo $has_sku ? '25%' : '34%'; ?>;">نام کالا یا خدمات</th>
                    <?php if ($has_sku): ?>
                        <th style="width: 10%;">کد کالا</th>
                    <?php endif; ?>
                    <th style="width: 7%;">تعداد</th>
                    <th style="width: 7%;">واحد</th>
                    <th style="width: 14%;">مبلغ واحد (<?php echo esc_html($currency); ?>)</th>
                    <th style="width: 9%;">تخفیف</th>
                    <th style="width: 13%;">مبلغ کل</th>
                    <th style="width: 10%;">مالیات</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($items as $item): 
                    $qty = max(1, (float)($item['qty'] ?? 1));
                    $u_price = !empty($item['unit_price']) ? (float)$item['unit_price'] : (!empty($item['price']) ? (float)$item['price'] : 0);
                    if ($u_price <= 0) {
                        $u_price = (!empty($item['subtotal']) ? (float)$item['subtotal'] : (float)($item['total'] ?? 0)) / $qty;
                    }
                ?>
                    <tr>
                        <td><?php echo woo_factor_fa_digits($i++); ?></td>
                        <td class="title-cell">
                            <?php echo esc_html($item['title']); ?>
                            <?php if (!empty($item['meta'])): ?>
                                <div class="item-sku"><?php echo esc_html($item['meta']); ?></div>
                            <?php endif; ?>
                        </td>
                        <?php if ($has_sku): ?>
                            <td class="num-cell" style="text-align: center;"><?php echo !empty($item['sku']) && $item['sku'] !== '-' ? woo_factor_fa_digits($item['sku']) : '-'; ?></td>
                        <?php endif; ?>
                        <td><?php echo woo_factor_fa_digits($qty); ?></td>
                        <td>عدد</td>
                        <td class="num-cell"><?php echo woo_factor_number_format($u_price); ?></td>
                        <td class="num-cell"><?php echo !empty($item['discount']) ? woo_factor_number_format($item['discount']) : '-'; ?></td>
                        <td class="num-cell"><?php echo woo_factor_number_format($item['total']); ?></td>
                        <td class="num-cell">
                            <?php 
                            if (!empty($item['tax'])) {
                                echo woo_factor_number_format($item['tax']);
                                if (!empty($item['tax_percent'])) {
                                    echo '<div style="font-size: 8.5px; color: #64748b;">(' . woo_factor_fa_digits($item['tax_percent']) . '٪)</div>';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Amount in Persian Words -->
    <?php if (!empty($totals['words'])): ?>
        <div class="words-bar">
            <span>مبلغ کل به حروف:</span>
            <strong><?php echo esc_html($totals['words']); ?> <?php echo esc_html($currency); ?></strong>
        </div>
    <?php endif; ?>

    <!-- Bottom Section: Stamp / Barcode / Totals -->
    <div class="bottom-grid">
        <!-- Stamp / Signature Box -->
        <div class="stamp-card">
            <span class="stamp-card-title">مهر و امضای مجاز فروشنده</span>
            <?php if ($stamp_url): ?>
                <img src="<?php echo esc_url($stamp_url); ?>" alt="مهر فروشگاه" class="stamp-img">
            <?php else: ?>
                <div style="color: #94a3b8; font-size: 9px; margin: auto;">محل درج مهر و امضا</div>
            <?php endif; ?>
            <span style="font-size: 8.5px; color: #64748b;">تاییدیه امور مالی و حسابداری</span>
        </div>

        <!-- Barcode Middle Card -->
        <div class="barcode-middle">
            <?php if (!empty($data['show_barcode']) && !empty($data['barcode_svg'])): ?>
                <?php echo $data['barcode_svg']; ?>
                <div style="font-size: 9px; font-weight: bold; color: #475569; margin-top: 4px;">
                    سفارش: #<?php echo woo_factor_fa_digits($data['order_number']); ?>
                </div>
            <?php endif; ?>
            <div style="font-size: 8.5px; color: #059669; font-weight: bold; margin-top: 6px; background: #ecfdf5; padding: 2px 8px; border-radius: 10px;">
                ✔ صورتحساب معتبر و تایید شده
            </div>
        </div>

        <!-- Totals Card -->
        <div class="totals-card">
            <table class="totals-table">
                <tr>
                    <td class="t-lbl">جمع کل ناخالص:</td>
                    <td class="t-val"><?php echo woo_factor_number_format($totals['subtotal']); ?> <?php echo esc_html($currency); ?></td>
                </tr>
                <?php if ($totals['discount'] > 0): ?>
                    <tr>
                        <td class="t-lbl">مجموع تخفیف:</td>
                        <td class="t-val" style="color: #dc2626;"><?php echo woo_factor_number_format($totals['discount']); ?>- <?php echo esc_html($currency); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($totals['shipping'] > 0): ?>
                    <tr>
                        <td class="t-lbl">هزینه حمل و نقل:</td>
                        <td class="t-val"><?php echo woo_factor_number_format($totals['shipping']); ?> <?php echo esc_html($currency); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($totals['tax'] > 0): ?>
                    <tr>
                        <td class="t-lbl">مالیات بر ارزش افزوده:</td>
                        <td class="t-val"><?php echo woo_factor_number_format($totals['tax']); ?> <?php echo esc_html($currency); ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="grand-row">
                    <td>مبلغ قابل پرداخت:</td>
                    <td class="t-val"><?php echo woo_factor_number_format($totals['grand_total']); ?> <?php echo esc_html($currency); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Footer Note & Terms -->
    <?php if (!empty($data['footer_note']) || !empty($data['invoice_terms'])): ?>
        <footer class="footer-terms">
            <?php if (!empty($data['footer_note'])): ?>
                <div><?php echo esc_html($data['footer_note']); ?></div>
            <?php endif; ?>
            <?php if (!empty($data['invoice_terms'])): ?>
                <div style="margin-top: 3px;"><?php echo nl2br(esc_html($data['invoice_terms'])); ?></div>
            <?php endif; ?>
        </footer>
    <?php endif; ?>
</div>

</body>
</html>
