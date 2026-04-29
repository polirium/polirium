<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu Thu</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        table {
            page-break-inside: auto;
            border-collapse: collapse;
            width: 100%;
        }

        table td {
            word-wrap: break-word;
            word-break: break-word;
            padding: 4px 0;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* Print-specific utility classes */
        .print-value {
            color: #1a1a1a;
            font-size: 12px;
            line-height: 1.6;
            background-color: #ffffff;
            font-weight: 500;
        }

        .print-border-bottom {
            border-bottom: 1px dotted #333;
            padding-bottom: 2px;
            min-height: 20px;
        }

        /* Spacing utilities */
        .mb5 { margin-bottom: 5px; }
        .mb10 { margin-bottom: 10px; }
        .mb25 { margin-bottom: 25px; }

        /* Typography */
        .fs18 { font-size: 18px; }
        .fs-large { font-size: large; }
        .fs-small { font-size: small; }

        /* Center alignment */
        [style*="text-align:center"] {
            text-align: center !important;
        }

        [style*="text-align:right"] {
            text-align: right !important;
        }

        /* Print optimization */
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            .print-border-bottom {
                border-bottom-color: #000;
            }

            a {
                text-decoration: none;
                color: inherit;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Screen preview optimization */
        @media screen {
            body {
                background: #f5f5f5;
                padding: 20px;
            }

            .print-container {
                background: #fff;
                padding: 30px;
                max-width: 210mm;
                margin: 0 auto;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Branch Info -->
        <div class="mb25">
            <span class="fs-small">@{{ store_name }}</span><br>
            <span class="fs-small">{{ __("SĐT") }}: </span><span class="print-value">@{{ store_phone }}</span><br>
            <span class="fs-small">{{ __("Địa chỉ") }}:&nbsp;@{{ store_address }}</span>
        </div>

        <!-- Title -->
        <div class="mb10" style="text-align:center;">
            <strong class="fs18"><span class="fs-large">{{ __("PHIẾU THU") }}</span></strong>
        </div>
        <div class="mb5" style="text-align:center;">
            <strong><span class="fs-small">{{ __("Mã phiếu thu") }}: @{{ code }}</span></strong>
        </div>
        <div class="mb25" style="text-align:center;">
            <em><span class="fs-small">{{ __("Ngày") }}:&nbsp;</span></em>
            <span class="print-value"><em> @{{ full_date }} </em></span>
        </div>

        <!-- Form Fields -->
        <table width="100%">
            <tbody>
                <tr>
                    <td colspan="2">
                        <table width="100%">
                            <tbody>
                                <tr>
                                    <td width="24%"><span class="fs-small">{{ __("Họ tên người nộp tiền") }}:</span></td>
                                    <td class="print-border-bottom">@{{ partner_name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <table width="100%">
                            <tbody>
                                <tr>
                                    <td style="white-space:nowrap;" width="24%"><span class="fs-small">{{ __("Số điện thoại") }}:</span></td>
                                    <td class="print-border-bottom">@{{ partner_phone }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <table width="100%">
                            <tbody>
                                <tr>
                                    <td style="white-space:nowrap;" width="24%"><span class="fs-small">{{ __("Địa chỉ") }}:</span></td>
                                    <td class="print-border-bottom">@{{ partner_address }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <table width="100%">
                            <tbody>
                                <tr>
                                    <td style="white-space:nowrap;" width="24%"><span class="fs-small">{{ __("Lý do nộp") }}:</span></td>
                                    <td class="print-border-bottom">@{{ reason }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" height="20"></td>
                </tr>
                <tr>
                    <td>
                        <span class="fs-small">{{ __("Số tiền") }}: </span>
                        <strong><span class="fs-small">@{{ value }}</span></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p><span class="fs-small">{{ __("Bằng chữ") }}: </span><span class="print-value">@{{ value_in_words }}</span><em>&nbsp;</em></p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" height="10"></td>
                </tr>
            </tbody>
        </table>

        <!-- Signature Section -->
        <div class="mb10" style="text-align:right;">
            <span class="fs-small">{{ __("Ngày") }} .......... {{ __("Tháng") }} .......... {{ __("Năm") }} ...............</span>
        </div>
        <table width="100%">
            <tbody>
                <tr>
                    <td align="center" width="33%"><strong><span class="fs-small">{{ __("Người lập phiếu") }}</span></strong></td>
                    <td align="center" width="33%"><strong><span class="fs-small">{{ __("Người nộp") }}</span></strong></td>
                    <td align="center" width="33%"><strong><span class="fs-small">{{ __("Thủ quỹ") }}</span></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
