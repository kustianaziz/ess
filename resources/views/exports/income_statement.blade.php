<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .format2-table {
            width: 100%;
            border-collapse: collapse;
            border: 0;
            font-size: 10px;
        }
        .format2-table th,
        .format2-table td {
            border: 0;
            padding: 4px 5px;
            vertical-align: top;
        }
        .format2-table thead th {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .section-row td {
            padding-top: 10px;
            font-weight: bold;
        }
        .subtotal-row td {
            border-top: 1px solid #333;
            border-bottom: 1px solid #333;
            font-weight: bold;
        }
        .amount {
            text-align: right;
            white-space: nowrap;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        h4 { margin: 2px 0; }
    </style>
</head>
<body>

    <h4 class="text-center font-bold">LAPORAN LABA RUGI</h4>
    <h4 class="text-center font-bold">PERIODE {{ \Carbon\Carbon::parse($filters['start_date'])->translatedFormat('d F Y') }} - {{ \Carbon\Carbon::parse($filters['end_date'])->translatedFormat('d F Y') }}</h4>
    <br>

    <table class="format2-table">
        <thead>
            <tr>
                <th style="width:70%;text-align:left;">AKUN / URAIAN</th>
                <th style="width:30%;text-align:right;">SALDO</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-row"><td colspan="2">PENDAPATAN OPERASIONAL</td></tr>
            @foreach($revenues['items'] as $item)
                @if(!$filters['show_zero'] && $item['balance'] == 0) @continue @endif
                <tr @if($item['is_header']) class="font-bold" @endif>
                    <td style="padding-left: {{ $item['level'] * 15 }}px;">
                        {{ $filters['show_code'] ? $item['code'] . ' - ' : '' }}{{ $item['name'] }}
                    </td>
                    <td class="amount">{{ $item['is_header'] ? '' : number_format($item['balance'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td>TOTAL PENDAPATAN OPERASIONAL</td>
                <td class="amount">{{ number_format($revenues['total'], 2, ',', '.') }}</td>
            </tr>

            <tr class="section-row"><td colspan="2">BEBAN OPERASIONAL</td></tr>
            @foreach($expenses['items'] as $item)
                @if(!$filters['show_zero'] && $item['balance'] == 0) @continue @endif
                <tr @if($item['is_header']) class="font-bold" @endif>
                    <td style="padding-left: {{ $item['level'] * 15 }}px;">
                        {{ $filters['show_code'] ? $item['code'] . ' - ' : '' }}{{ $item['name'] }}
                    </td>
                    <td class="amount">{{ $item['is_header'] ? '' : number_format($item['balance'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td>TOTAL BEBAN OPERASIONAL</td>
                <td class="amount">{{ number_format($expenses['total'], 2, ',', '.') }}</td>
            </tr>
            <tr class="subtotal-row" style="background-color: #f1f1f1;">
                <td style="padding-top: 10px; padding-bottom: 10px;">LABA (RUGI) KOTOR</td>
                <td class="amount" style="padding-top: 10px; padding-bottom: 10px;">{{ number_format($grossProfit, 2, ',', '.') }}</td>
            </tr>

            <tr class="section-row"><td colspan="2">PENDAPATAN LAIN-LAIN</td></tr>
            @foreach($otherRevenues['items'] as $item)
                @if(!$filters['show_zero'] && $item['balance'] == 0) @continue @endif
                <tr @if($item['is_header']) class="font-bold" @endif>
                    <td style="padding-left: {{ $item['level'] * 15 }}px;">
                        {{ $filters['show_code'] ? $item['code'] . ' - ' : '' }}{{ $item['name'] }}
                    </td>
                    <td class="amount">{{ $item['is_header'] ? '' : number_format($item['balance'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td>TOTAL PENDAPATAN LAIN-LAIN</td>
                <td class="amount">{{ number_format($otherRevenues['total'], 2, ',', '.') }}</td>
            </tr>

            <tr class="section-row"><td colspan="2">BEBAN LAIN-LAIN</td></tr>
            @foreach($otherExpenses['items'] as $item)
                @if(!$filters['show_zero'] && $item['balance'] == 0) @continue @endif
                <tr @if($item['is_header']) class="font-bold" @endif>
                    <td style="padding-left: {{ $item['level'] * 15 }}px;">
                        {{ $filters['show_code'] ? $item['code'] . ' - ' : '' }}{{ $item['name'] }}
                    </td>
                    <td class="amount">{{ $item['is_header'] ? '' : number_format($item['balance'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td>TOTAL BEBAN LAIN-LAIN</td>
                <td class="amount">{{ number_format($otherExpenses['total'], 2, ',', '.') }}</td>
            </tr>

            <tr class="subtotal-row" style="background-color: #e0f2fe;">
                <td style="padding-top: 10px; padding-bottom: 10px;">LABA (RUGI) SEBELUM PAJAK</td>
                <td class="amount" style="padding-top: 10px; padding-bottom: 10px;">{{ number_format($operatingProfit, 2, ',', '.') }}</td>
            </tr>
            
            <tr class="section-row"><td colspan="2">PAJAK PENGHASILAN</td></tr>
            @foreach($taxes['items'] as $item)
                @if(!$filters['show_zero'] && $item['balance'] == 0) @continue @endif
                <tr @if($item['is_header']) class="font-bold" @endif>
                    <td style="padding-left: {{ $item['level'] * 15 }}px;">
                        {{ $filters['show_code'] ? $item['code'] . ' - ' : '' }}{{ $item['name'] }}
                    </td>
                    <td class="amount">{{ $item['is_header'] ? '' : number_format($item['balance'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td>TOTAL BEBAN PAJAK</td>
                <td class="amount">{{ number_format($taxes['total'], 2, ',', '.') }}</td>
            </tr>

            <tr class="subtotal-row" style="background-color: #cffafe;">
                <td style="padding-top: 10px; padding-bottom: 10px;">LABA (RUGI) BERSIH</td>
                <td class="amount" style="padding-top: 10px; padding-bottom: 10px;">{{ number_format($netProfit, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
