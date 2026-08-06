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
        .subtotal-row td {
            border-top: 1px dashed #ccc;
        }
        .amount {
            text-align: right;
            white-space: nowrap;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        h4 { margin: 2px 0; }
        .tx-row td {
            padding-top: 2px;
            padding-bottom: 2px;
            color: #555;
            font-size: 9px;
        }
    </style>
</head>
<body>

    <h4 class="text-center font-bold">CATATAN ATAS LAPORAN KEUANGAN (CALK)</h4>
    <h4 class="text-center font-bold">PERIODE {{ \Carbon\Carbon::parse($filters['start_date'])->translatedFormat('d F Y') }} - {{ \Carbon\Carbon::parse($filters['end_date'])->translatedFormat('d F Y') }}</h4>
    <br>

    <table class="format2-table">
        <thead>
            <tr>
                <th style="width:70%;text-align:left;">AKUN / URAIAN TRANSAKSI</th>
                <th style="width:30%;text-align:right;">SALDO / NILAI</th>
            </tr>
        </thead>
        <tbody>
            @foreach($coas as $coa)
                <tr @if($coa->is_header) class="font-bold" @endif>
                    <td style="padding-left: {{ $coa->level * 15 }}px;">
                        {{ $filters['show_code'] ? $coa->code . ' - ' : '' }}{{ $coa->name }}
                    </td>
                    <td class="amount">{{ $coa->is_header ? '' : number_format($coa->balance, 2, ',', '.') }}</td>
                </tr>
                @if(!$coa->is_header && count($coa->transactions) > 0)
                    @foreach($coa->transactions as $t)
                        <tr class="tx-row">
                            <td style="padding-left: {{ ($coa->level * 15) + 20 }}px;">
                                {{ \Carbon\Carbon::parse($t['date'])->format('d/m/Y') }} | [{{ $t['reference'] }}] {{ $t['description'] }}
                            </td>
                            <td class="amount">
                                @if($t['debit'] > 0) D: {{ number_format($t['debit'], 2, ',', '.') }} @endif
                                @if($t['credit'] > 0) K: {{ number_format($t['credit'], 2, ',', '.') }} @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
</body>
</html>
