<head>
    <style>
        @page {
            size: A4;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        table {
            page-break-inside: auto;
            border-collapse: collapse;
        }

        table td {
            word-wrap: break-word;
            word-break: break-all;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div style="border-bottom:1px dashed black; text-align:center">
        {{-- <img src="{{ asset('vendor\modules\product\images\xgnd.png') }}" alt=""> --}}
        @{{ store_logo|raw }}
        <br>
        <span style="font-size:medium">@{{ store_address }}</span>
        <br>
        <span style="font-size:medium">{{ __("Hotline") }}: @{{ store_phone }}</span>
    </div>
    <div style="padding:10px 0 0; text-align:center"><strong style="font-size:12px">{{ __("HÓA ĐƠN BÁN HÀNG") }}</strong></div>
    <table style="width:100%">
        <tbody>
            <tr>
                <td style="font-size:11px; text-align:center">{{ __("Số HĐ") }}: @{{ code }}</td>
            </tr>
            <tr>
                <td style="font-size:11px; text-align:center">{{ __("Ngày") }} @{{ day }} {{ __("tháng") }} @{{ month }} {{ __("năm") }} @{{ year }} </td>
            </tr>
        </tbody>
    </table>

    <table style="margin:10px 0 15px; width:100%">
        <tbody>
            <tr>
                <td style="font-size:11px">{{ __("Khách hàng") }}: @{{ customer_name }}</td>
            </tr>
            <tr>
                <td style="font-size:11px">{{ __("SĐT") }}: @{{ customer_phone }}</td>
            </tr>
            <tr>
                <td style="font-size:11px">{{ __("Địa chỉ") }}: @{{ customer_address }} - @{{ customer_ward }} - @{{ customer_district }}</td>
            </tr>
        </tbody>
    </table>

    @{{ items_table|raw }}

    <table border="0" cellpadding="3" cellspacing="0" style="border-collapse:collapse; margin-top:20px; width:100%">
        <tfoot>
            <tr>
                <td style="font-size:11px; font-weight:bold; text-align:right; white-space:nowrap">{{ __("Tổng tiền hàng") }}:</td>
                <td style="font-size:11px; font-weight:bold; text-align:right">@{{ total_amount }}</td>
            </tr>
            <tr>
                {{-- <td style="font-size:11px; font-weight:bold; text-align:right; white-space:nowrap">Chiết khấu {{ $payment->discount_type == "%" ? ("{$payment->discount}%") : mb_strtoupper($payment->discount_type) }} :</td> --}}
                <td style="font-size:11px; font-weight:bold; text-align:right; white-space:nowrap">{{ __("Chiết khấu") }} @{{ discount_percent }} :</td>
                <td style="font-size:11px; font-weight:bold; text-align:right">
                    {{-- @if ($payment->discount_type == "%")
                        {{ core_number_format($payment->total * ($payment->discount / 100)) }}
                    @else
                        {{ core_number_format($payment->discount) }}
                    @endif --}}
                    @{{ discount_amount }}
                </td>
            </tr>
            <tr>
                <td style="font-size:11px; font-weight:bold; text-align:right; white-space:nowrap">{{ __("Tổng thanh toán") }}:</td>
                <td style="font-size:11px; font-weight:bold; text-align:right">@{{ grand_total }}</td>
            </tr>
            <tr>
                <td colspan="2" style="font-size:11px; font-style:italic; text-align:left">
                    <p>@{{ note }}</p>
                </td>
            </tr>
        </tfoot>
    </table>

    <table style="margin-top:20px; width:100%">
        <tbody>
            <tr>
                <td style="font-size:11px; font-style:italic; text-align:center">{{ __("Cảm ơn và hẹn gặp lại") }}!</td>
            </tr>
            <tr>
                <td style="font-size:9px; font-style:italic; text-align:center">{{ __("Powered by Polirium") }}.</td>
            </tr>
        </tbody>
    </table>
</body>
