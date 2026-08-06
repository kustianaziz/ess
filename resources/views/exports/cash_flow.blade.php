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

    <h4 class="text-center font-bold">LAPORAN ARUS KAS</h4>
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
            <tr class="section-row"><td colspan="2">ARUS KAS DARI AKTIVITAS OPERASI</td></tr>
            @foreach($operatingActivities as $item)
                <tr>
                    <td style="padding-left: 15px;">{{ $item['description'] }}</td>
                    <td class="amount">{{ number_format($item['amount'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td>KAS BERSIH DARI AKTIVITAS OPERASI</td>
                <td class="amount">{{ number_format($operatingTotal, 2, ',', '.') }}</td>
            </tr>

            <tr class="section-row"><td colspan="2">ARUS KAS DARI AKTIVITAS INVESTASI</td></tr>
            @foreach($investingActivities as $item)
                <tr>
                    <td style="padding-left: 15px;">{{ $item['description'] }}</td>
                    <td class="amount">{{ number_format($item['amount'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td>KAS BERSIH DARI AKTIVITAS INVESTASI</td>
                <td class="amount">{{ number_format($investingTotal, 2, ',', '.') }}</td>
            </tr>

            <tr class="section-row"><td colspan="2">ARUS KAS DARI AKTIVITAS PENDANAAN</td></tr>
            @foreach($financingActivities as $item)
                <tr>
                    <td style="padding-left: 15px;">{{ $item['description'] }}</td>
                    <td class="amount">{{ number_format($item['amount'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td>KAS BERSIH DARI AKTIVITAS PENDANAAN</td>
                <td class="amount">{{ number_format($financingTotal, 2, ',', '.') }}</td>
            </tr>

            <tr class="subtotal-row" style="background-color: #f1f1f1;">
                <td style="padding-top: 10px; padding-bottom: 10px;">KENAIKAN (PENURUNAN) KAS BERSIH</td>
                <td class="amount" style="padding-top: 10px; padding-bottom: 10px;">{{ number_format($netIncrease, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>KAS DAN SETARA KAS AWAL PERIODE</td>
                <td class="amount">{{ number_format($beginningCash, 2, ',', '.') }}</td>
            </tr>
            <tr class="subtotal-row" style="background-color: #cffafe;">
                <td style="padding-top: 10px; padding-bottom: 10px;">KAS DAN SETARA KAS AKHIR PERIODE</td>
                <td class="amount" style="padding-top: 10px; padding-bottom: 10px;">{{ number_format($endingCash, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
