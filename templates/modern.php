<?php
/**
 * Template 2: Modern High-Impact Fintech (قالب مدرن و لوکس فاکتورساز ووفاکتور)
 * Visually captivating, vibrant palette, modern card layout with product thumbnails and perfect print support.
 */
defined('ABSPATH') || exit;

$brand = woo_factor_normalize_color($data['color'] ?? '', '#1E40AF');
$seller = $data['seller'] ?? [];
$buyer = $data['buyer'] ?? [];
$totals = $data['totals'] ?? [];
$items = $data['items'] ?? [];
$logo_url = !empty($seller['logo_url']) ? $seller['logo_url'] : '';
$stamp_url = !empty($data['stamp_url']) ? $data['stamp_url'] : '';
$currency = $totals['currency'] ?? 'تومان';

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
    <title>فاکتور خرید - <?php echo esc_html($data['invoice_number']); ?></title>
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
            font-size: 11px;
            line-height: 1.5;
            padding: 20px 10px;
        }
        .invoice-card {
            position: relative;
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px 24px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-28deg);
            font-size: 58px;
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

        /* Top Vibrant Header Banner */
        .mod-header-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, <?php echo esc_attr($brand); ?> 100%);
            color: #ffffff;
            padding: 18px 22px;
            border-radius: 12px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.15);
        }
        .mod-banner-right {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .mod-banner-title {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #ffffff;
        }
        .mod-banner-meta {
            font-size: 11px;
            color: #cbd5e1;
            display: flex;
            gap: 12px;
        }
        .mod-banner-left {
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
        }
        .mod-banner-logo img {
            max-height: 48px;
            max-width: 120px;
            object-fit: contain;
            background: #ffffff;
            padding: 4px 8px;
            border-radius: 8px;
            display: block;
        }
        .mod-banner-store-name {
            font-size: 14px;
            font-weight: 800;
            color: #ffffff;
        }

        /* Horizontal Metadata Ribbon */
        .meta-ribbon {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr 1fr;
            gap: 8px;
            margin-bottom: 14px;
        }
        .ribbon-pill {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 7px 10px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10px;
        }
        .ribbon-pill span.lbl {
            color: #64748b;
            flex-shrink: 0;
        }
        .ribbon-pill strong.val {
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .status-badge-glow {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 2px 8px;
            font-weight: 800;
            font-size: 9.5px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .status-dot-green {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
        }

        /* Dual-Tone Buyer & Seller Cards */
        .parties-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }
        .party-card {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
            background: #ffffff;
            transition: all 0.2s;
        }
        .party-card-seller {
            border-color: #cbd5e1;
        }
        .party-card-buyer {
            border-color: #cbd5e1;
        }
        .party-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 6px;
        }
        .party-header-icon {
            font-size: 14px;
        }
        .party-lines {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 10px;
        }
        .p-row {
            display: flex;
            gap: 4px;
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

        /* Modern Products Table */
        .items-box {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 14px;
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
            padding: 9px 8px;
            white-space: nowrap;
        }
        .items-table td {
            padding: 8px 8px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) td {
            background: #f8fafc;
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
            width: 34px;
            height: 34px;
            border-radius: 6px;
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
            padding-left: 10px;
            font-feature-settings: "tnum";
            font-weight: 600;
        }

        /* Summary Breakdown Line */
        .summary-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 10.5px;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .summary-item {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .summary-item span.s-lbl {
            color: #64748b;
        }
        .summary-item strong.s-val {
            color: #0f172a;
        }

        /* Big Bold Action Pill for Grand Total */
        .grand-action-pill {
            background: linear-gradient(90deg, #0f172a 0%, #1e1b4b 60%, <?php echo esc_attr($brand); ?> 100%);
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 30px;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .pill-right {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 800;
        }
        .pill-total-amount {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: -0.5px;
        }
        .pill-left-badge {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 10.5px;
            font-weight: bold;
        }

        /* Words Strip */
        .words-strip {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        /* Bottom Row: Barcode & Official Stamp */
        .bottom-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            gap: 16px;
        }
        .bottom-barcode-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
        }
        .bottom-stamp-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .stamp-seal-circle {
            width: 75px;
            height: 75px;
            border: 1.5px dashed #cbd5e1;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 8.5px;
            color: #94a3b8;
            padding: 4px;
        }
        .stamp-seal-img {
            max-width: 80px;
            max-height: 65px;
            object-fit: contain;
        }
        .footer-note-text {
            font-size: 9.5px;
            color: #64748b;
            max-width: 320px;
            line-height: 1.5;
        }

        /* Mobile Responsive Screen Styles */
        @media screen and (max-width: 768px) {
            body {
                padding: 6px 4px;
            }
            .invoice-card {
                padding: 12px 10px;
                border-radius: 8px;
                box-shadow: none;
                width: 100%;
                overflow-x: hidden;
                box-sizing: border-box;
            }
            .mod-header-banner {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                gap: 12px;
                padding: 14px 16px;
            }
            .mod-banner-meta {
                flex-direction: column;
                gap: 4px;
                align-items: center;
            }
            .mod-banner-left {
                flex-direction: column-reverse;
                text-align: center;
                align-items: center;
            }
            .meta-ribbon {
                grid-template-columns: 1fr;
                gap: 6px;
            }
            .parties-grid {
                grid-template-columns: 1fr !important;
                gap: 10px;
            }
            .items-box {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
                width: 100%;
                margin-bottom: 10px;
            }
            .items-table {
                min-width: 520px;
            }
            .summary-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 6px;
            }
            .summary-item {
                justify-content: space-between;
                width: 100%;
            }
            .grand-action-pill {
                flex-direction: column;
                gap: 8px;
                text-align: center;
                padding: 12px 16px;
                border-radius: 14px;
            }
            .pill-right {
                flex-direction: column;
                gap: 4px;
                text-align: center;
            }
            .bottom-strip {
                flex-direction: column-reverse;
                gap: 12px;
                align-items: center;
                text-align: center;
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
            .invoice-card {
                box-shadow: none !important;
                border: 1.5px solid #cbd5e1 !important;
                border-radius: 0 !important;
                padding: 14px 18px !important;
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

<div class="invoice-card">
    <?php if (!empty($data['watermark_text'])): ?>
        <div class="watermark"><?php echo esc_html($data['watermark_text']); ?></div>
    <?php endif; ?>

    <!-- Top Vibrant Header Banner -->
    <header class="mod-header-banner">
        <div class="mod-banner-right">
            <div class="mod-banner-title">فاکتور خرید</div>
            <div class="mod-banner-meta">
                <span>شماره فاکتور: <strong><?php echo woo_factor_fa_digits($data['invoice_number']); ?></strong></span>
                <span>•</span>
                <span>تاریخ صدور: <strong><?php echo woo_factor_fa_digits($data['jalali_date']); ?></strong></span>
                <span>•</span>
                <span>زمان: <strong><?php echo woo_factor_fa_digits($data['jalali_time']); ?></strong></span>
            </div>
        </div>

        <div class="mod-banner-left">
            <div style="text-align: left;">
                <div class="mod-banner-store-name"><?php echo esc_html($seller['name']); ?></div>
                <div style="font-size: 9.5px; color: #cbd5e1; margin-top: 2px;">رسید و صورتحساب سفارش</div>
            </div>
            <?php if ($logo_url): ?>
                <div class="mod-banner-logo">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($seller['name']); ?>">
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Horizontal Metadata Ribbon -->
    <div class="meta-ribbon">
        <div class="ribbon-pill">
            <span class="lbl">👤 مشتری:</span>
            <strong class="val"><?php echo esc_html($buyer['name']); ?></strong>
        </div>
        <div class="ribbon-pill">
            <span class="lbl">📱 تماس:</span>
            <strong class="val"><?php echo woo_factor_fa_digits($buyer['phone'] ?: '-'); ?></strong>
        </div>
        <div class="ribbon-pill">
            <span class="lbl">💳 روش پرداخت:</span>
            <strong class="val"><?php echo esc_html($data['payment_method'] ?: 'پرداخت آنلاین'); ?></strong>
        </div>
        <?php 
        $status_title = !empty($data['status_name']) ? $data['status_name'] : (!empty($data['status']) ? $data['status'] : '');
        $status_key = strtolower($data['status'] ?? '');
        $is_paid = in_array($status_key, ['completed', 'processing', 'paid']) || (!empty($data['watermark_text']) && strpos($data['watermark_text'], 'پرداخت شد') !== false);
        $is_pending = in_array($status_key, ['pending', 'on-hold', 'unpaid']);
        ?>
        <?php if (!empty($status_title)): ?>
            <div class="ribbon-pill" style="justify-content: center;">
                <?php if ($is_paid): ?>
                    <span class="status-badge-glow">
                        <span class="status-dot-green"></span>
                        <span><?php echo esc_html($status_title); ?></span>
                    </span>
                <?php elseif ($is_pending): ?>
                    <span class="status-badge-glow" style="background: #fffbeb; color: #b45309; border-color: #fde68a;">
                        <span class="status-dot-green" style="background: #f59e0b;"></span>
                        <span><?php echo esc_html($status_title); ?></span>
                    </span>
                <?php else: ?>
                    <span class="status-badge-glow" style="background: #f1f5f9; color: #475569; border-color: #cbd5e1;">
                        <span class="status-dot-green" style="background: #64748b;"></span>
                        <span><?php echo esc_html($status_title); ?></span>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Dual-Tone Parties Grid -->
    <div class="parties-grid">
        <!-- Seller -->
        <div class="party-card party-card-seller">
            <div class="party-header">
                <span class="party-header-icon">🏬</span>
                <span>اطلاعات فروشنده</span>
            </div>
            <div class="party-lines">
                <div class="p-row">
                    <span class="p-lbl">فروشگاه:</span>
                    <span class="p-val"><?php echo esc_html($seller['name']); ?></span>
                </div>
                <?php if (!empty($seller['national_id'])): ?>
                    <div class="p-row">
                        <span class="p-lbl">شناسه ملی:</span>
                        <span class="p-val"><?php echo woo_factor_fa_digits($seller['national_id']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="p-row">
                    <span class="p-lbl">نشانی:</span>
                    <span class="p-val"><?php echo esc_html($seller['address'] ?: '-'); ?></span>
                </div>
                <?php if (!empty($seller['phone'])): ?>
                    <div class="p-row">
                        <span class="p-lbl">تلفن:</span>
                        <span class="p-val"><?php echo woo_factor_fa_digits($seller['phone']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Buyer -->
        <div class="party-card party-card-buyer">
            <div class="party-header">
                <span class="party-header-icon">👤</span>
                <span>اطلاعات خریدار</span>
            </div>
            <div class="party-lines">
                <div class="p-row">
                    <span class="p-lbl">نام و نشان:</span>
                    <span class="p-val"><?php echo esc_html(!empty($buyer['company']) ? $buyer['company'] . ' (' . $buyer['name'] . ')' : $buyer['name']); ?></span>
                </div>
                <?php if (!empty($buyer['national_code'])): ?>
                    <div class="p-row">
                        <span class="p-lbl">کد ملی:</span>
                        <span class="p-val"><?php echo woo_factor_fa_digits($buyer['national_code']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="p-row">
                    <span class="p-lbl">نشانی تحویل:</span>
                    <span class="p-val"><?php echo esc_html($buyer['full_address'] ?: '-'); ?></span>
                </div>
                <div class="p-row">
                    <span class="p-lbl">شماره همراه:</span>
                    <span class="p-val"><?php echo woo_factor_fa_digits($buyer['phone'] ?: '-'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="items-box">
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: <?php echo $has_sku ? '34%' : '44%'; ?>;">محصول</th>
                    <?php if ($has_sku): ?>
                        <th style="width: 10%;">کد محصول</th>
                    <?php endif; ?>
                    <th style="width: 15%;">قیمت واحد (<?php echo esc_html($currency); ?>)</th>
                    <th style="width: 8%;">تعداد</th>
                    <th style="width: 12%;">تخفیف</th>
                    <th style="width: 16%;">جمع کل</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($items as $item): 
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
                                    <div class="item-text-title"><?php echo esc_html($item['title']); ?></div>
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
                        <td class="num-col"><?php echo !empty($item['discount']) ? woo_factor_number_format($item['discount']) : '-'; ?></td>
                        <td class="num-col"><?php echo woo_factor_number_format($item['total']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Summary Breakdown Bar -->
    <div class="summary-bar">
        <div class="summary-item">
            <span class="s-lbl">جمع اقلام:</span>
            <strong class="s-val"><?php echo woo_factor_number_format($totals['subtotal']); ?> <?php echo esc_html($currency); ?></strong>
        </div>
        <?php if ($totals['discount'] > 0): ?>
            <div class="summary-item">
                <span class="s-lbl">مجموع تخفیف:</span>
                <strong class="s-val" style="color: #dc2626;"><?php echo woo_factor_number_format($totals['discount']); ?>- <?php echo esc_html($currency); ?></strong>
            </div>
        <?php endif; ?>
        <?php if ($totals['shipping'] > 0): ?>
            <div class="summary-item">
                <span class="s-lbl">هزینه ارسال:</span>
                <strong class="s-val"><?php echo woo_factor_number_format($totals['shipping']); ?> <?php echo esc_html($currency); ?></strong>
            </div>
        <?php endif; ?>
        <?php if ($totals['tax'] > 0): ?>
            <div class="summary-item">
                <span class="s-lbl">مالیات بر ارزش افزوده:</span>
                <strong class="s-val"><?php echo woo_factor_number_format($totals['tax']); ?> <?php echo esc_html($currency); ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <!-- Big Bold Grand Total Pill -->
    <div class="grand-action-pill">
        <div class="pill-right">
            <span>🧾 مبلغ کل پرداختی:</span>
            <span class="pill-total-amount"><?php echo woo_factor_number_format($totals['grand_total']); ?> <?php echo esc_html($currency); ?></span>
        </div>
        <div class="pill-left-badge">
            <?php 
            $status_icon = $is_paid ? '✔' : ($is_pending ? '⏳' : '📋');
            $status_label = !empty($data['status_name']) ? $data['status_name'] : (!empty($data['watermark_text']) ? $data['watermark_text'] : 'فاکتور رسمی');
            echo esc_html($status_icon . ' ' . $status_label);
            ?>
        </div>
    </div>

    <!-- Words Strip -->
    <?php if (!empty($totals['words'])): ?>
        <div class="words-strip">
            <span>مبلغ به حروف:</span>
            <strong><?php echo esc_html($totals['words']); ?> <?php echo esc_html($currency); ?></strong>
        </div>
    <?php endif; ?>

    <!-- Bottom Strip: Barcode & Official Stamp -->
    <footer class="bottom-strip">
        <!-- Barcode -->
        <?php if (!empty($data['show_barcode']) && !empty($data['barcode_svg'])): ?>
            <div class="bottom-barcode-wrap">
                <?php echo $data['barcode_svg']; ?>
                <div style="font-size: 9px; font-weight: bold; color: #475569; letter-spacing: 0.5px;">
                    #<?php echo woo_factor_fa_digits($data['order_number']); ?>
                </div>
            </div>
        <?php else: ?>
            <div></div>
        <?php endif; ?>

        <!-- Footer note text -->
        <?php if (!empty($data['footer_note'])): ?>
            <div class="footer-note-text">
                <?php echo esc_html($data['footer_note']); ?>
            </div>
        <?php endif; ?>

        <!-- Stamp Seal -->
        <div class="bottom-stamp-wrap">
            <?php if ($stamp_url): ?>
                <img src="<?php echo esc_url($stamp_url); ?>" alt="مهر فروشگاه" class="stamp-seal-img">
            <?php else: ?>
                <div class="stamp-seal-circle">
                    مهر و امضای<br>فروشگاه
                </div>
            <?php endif; ?>
        </div>
    </footer>
</div>

</body>
</html>
