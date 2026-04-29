{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Hoá đơn {{ $payment->code }}</title>

    <style type="text/css">
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
            page-break-after: auto
        }

        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div style="border-bottom:1px dashed black; text-align:center">
        <img src="{{ asset('vendor\modules\product\images\xgnd.png') }}" alt="">
        <br>
        <span style="font-size:medium">18 Hoa Hồng, P.2 Q.Phú Nhuận, Tp.HCM</span>
        <br>
        <span style="font-size:medium">Hotline : 0919.803.825</span>
    </div>
    <div style="padding:10px 0 0; text-align:center"><strong style="font-size:12px">HÓA ĐƠN BÁN HÀNG</strong></div>
    <table style="width:100%">
        <tbody>
            <tr>
                <td style="font-size:11px; text-align:center">Số HĐ: {{ $payment->code }}</td>
            </tr>
            <tr>
                <td style="font-size:11px; text-align:center">Ngày {{ core_format_date($payment->created_at, "d") }} tháng {{ core_format_date($payment->created_at, "m") }} năm {{ core_format_date($payment->created_at, "y") }} </td>
            </tr>
        </tbody>
    </table>

    <table style="margin:10px 0 15px; width:100%">
        <tbody>
            <tr>
                <td style="font-size:11px">Khách hàng: {{ $payment->customer->name }}</td>
            </tr>
            @if ($payment->customer_id)
                <tr>
                    <td style="font-size:11px">SĐT: {{ $payment->customer->phone }}</td>
                </tr>
                <tr>
                    <td style="font-size:11px">Địa chỉ: {{ $payment->customer->address }}</td>
                </tr>
            @endif
        </tbody>
    </table> --}}

    <table cellpadding="3" style="width:100%">
        <tbody>
            <tr>
                <td style="border-bottom:1px solid black; border-top:1px solid black; width:35%"><strong><span style="font-size:11px">Đơn giá</span></strong></td>
                <td style="border-bottom:1px solid black; border-top:1px solid black; text-align:right; width:30%"><strong><span style="font-size:11px">SL</span></strong></td>
                <td style="border-bottom:1px solid black; border-top:1px solid black; text-align:right"><strong><span style="font-size:11px">Thành tiền</span></strong></td>
            </tr>
            @if ($products?->count() > 0)
                @foreach ($products as $item)
                    <tr>
                        <td colspan="3" style="padding-top:3px"><span style="font-size:12px">{{ $item->product?->name ?? 'N/A' }}</span></td>
                    </tr>
                    <tr>
                        <td style="border-bottom:1px dashed black">
                            <span style="font-size:11px">
                                @if ($item->discount_value > 0)
                                    {{ core_number_format($item->value - $item->discount_value) }}
                                    <span style="text-decoration: line-through;">{{ core_number_format($item->value) }}</span>
                                @else
                                    {{ core_number_format($item->value) }}
                                @endif
                            </span>
                        </td>
                        <td style="border-bottom:1px dashed black; text-align:right"><span style="font-size:11px">{{ core_number_format($item->amount) }}</span></td>
                        <td style="border-bottom:1px dashed black; text-align:right"><span style="font-size:11px">{{ core_number_format($item->total) }}</span></td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    {{-- <table border="0" cellpadding="3" cellspacing="0" style="border-collapse:collapse; margin-top:20px; width:100%">
        <tfoot>
            <tr>
                <td style="font-size:11px; font-weight:bold; text-align:right; white-space:nowrap">Tổng tiền hàng:</td>
                <td style="font-size:11px; font-weight:bold; text-align:right">{{ core_number_format($payment->total) }}</td>
            </tr>
            @if ($payment->discount)
                <tr>
                    <td style="font-size:11px; font-weight:bold; text-align:right; white-space:nowrap">Chiết khấu {{ $payment->discount_type == "%" ? ("{$payment->discount}%") : mb_strtoupper($payment->discount_type) }} :</td>
                    <td style="font-size:11px; font-weight:bold; text-align:right">
                        @if ($payment->discount_type == "%")
                            {{ core_number_format($payment->total * ($payment->discount / 100)) }}
                        @else
                            {{ core_number_format($payment->discount) }}
                        @endif
                    </td>
                </tr>
            @endif
            <tr>
                <td style="font-size:11px; font-weight:bold; text-align:right; white-space:nowrap">Tổng thanh toán:</td>
                <td style="font-size:11px; font-weight:bold; text-align:right">{{ core_number_format($payment->pay) }}</td>
            </tr>
            @if ($payment->note)
                <tr>
                    <td colspan="2" style="font-size:11px; font-style:italic; text-align:left">
                        <p>{{ $payment->note }}</p>
                    </td>
                </tr>
            @endif
        </tfoot>
    </table>

    <table style="margin-top:20px; width:100%">
        <tbody>
            <tr>
                <td style="font-size:11px; font-style:italic; text-align:center">Cảm ơn và hẹn gặp lại!</td>
            </tr>
            <tr>
                <td style="font-size:9px; font-style:italic; text-align:center">Powered by ETOY.</td>
            </tr>
        </tbody>
    </table>
</body>
</html> --}}