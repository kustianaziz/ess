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

    <h4 class="text-center font-bold">LAPORAN POSISI KEUANGAN (NERACA)</h4>
    <h4 class="text-center font-bold">PER TANGGAL {{ \Carbon\Carbon::parse($filters['as_of_date'])->translatedFormat('d F Y') }}</h4>
    <br>

    <table class="format2-table">
        <thead>
            <tr>
                <th style="width:70%;text-align:left;">AKUN / URAIAN</th>
                <th style="width:30%;text-align:right;">SALDO</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-row"><td colspan="2">ASET</td></tr>
            @foreach($assets['items'] as $item)
                @if(!$filters['show_zero'] && $item['balance'] == 0) @continue @endif
                <tr @if($item['is_header']) class="font-bold" @endif>
                    <td style="padding-left: {{ $item['level'] * 15 }}px;">
                        {{ $filters['show_code'] ? $item['code'] . ' - ' : '' }}{{ $item['name'] }}
                    </td>
                    <td class="amount">{{ $item['is_header'] ? '' : number_format($item['balance'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td>TOTAL ASET</td>
                <td class="amount">{{ number_format($assets['total'], 2, ',', '.') }}</td>
            </tr>

            <tr class="section-row"><td colspan="2">LIABILITAS (KEWAJIBAN)</td></tr>
            @foreach($liabilities['items'] as $item)
                @if(!$filters['show_zero'] && $item['balance'] == 0) @continue @endif
                <tr @if($item['is_header']) class="font-bold" @endif>
                    <td style="padding-left: {{ $item['level'] * 15 }}px;">
                        {{ $filters['show_code'] ? $item['code'] . ' - ' : '' }}{{ $item['name'] }}
                    </td>
                    <td class="amount">{{ $item['is_header'] ? '' : number_format($item['balance'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td>TOTAL LIABILITAS</td>
                <td class="amount">{{ number_format($liabilities['total'], 2, ',', '.') }}</td>
            </tr>

            <tr class="section-row"><td colspan="2">EKUITAS (MODAL)</td></tr>
            @foreach($equities['items'] as $item)
                @if(!$filters['show_zero'] && $item['balance'] == 0) @continue @endif
                <tr @if($item['is_header']) class="font-bold" @endif>
                    <td style="padding-left: {{ $item['level'] * 15 }}px;">
                        {{ $filters['show_code'] ? $item['code'] . ' - ' : '' }}{{ $item['name'] }}
                    </td>
                    <td class="amount">{{ $item['is_header'] ? '' : number_format($item['balance'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td>TOTAL EKUITAS</td>
                <td class="amount">{{ number_format($equities['total'], 2, ',', '.') }}</td>
            </tr>
            <tr class="subtotal-row" style="background-color: #f1f1f1;">
                <td style="padding-top: 10px; padding-bottom: 10px;">TOTAL LIABILITAS DAN EKUITAS</td>
                <td class="amount" style="padding-top: 10px; padding-bottom: 10px;">{{ number_format($liabilities['total'] + $equities['total'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
