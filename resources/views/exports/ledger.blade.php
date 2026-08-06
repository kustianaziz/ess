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

    <h4 class="text-center font-bold">BUKU BESAR</h4>
    <h4 class="text-center font-bold">PERIODE {{ \Carbon\Carbon::parse($filters['start_date'])->translatedFormat('d F Y') }} - {{ \Carbon\Carbon::parse($filters['end_date'])->translatedFormat('d F Y') }}</h4>
    <br>

    @if($selectedCoa)
        <h4 style="margin-bottom: 10px;">AKUN: {{ $selectedCoa->code }} - {{ $selectedCoa->name }} (SALDO NORMAL: {{ strtoupper($selectedCoa->normal_balance) }})</h4>
        
        <table class="format2-table">
            <thead>
                <tr>
                    <th style="width:12%;text-align:left;">TANGGAL</th>
                    <th style="width:15%;text-align:left;">REFERENSI</th>
                    <th style="width:28%;text-align:left;">KETERANGAN</th>
                    <th style="width:15%;text-align:right;">DEBIT</th>
                    <th style="width:15%;text-align:right;">KREDIT</th>
                    <th style="width:15%;text-align:right;">SALDO</th>
                </tr>
            </thead>
            <tbody>
                <tr class="subtotal-row">
                    <td colspan="5" style="text-align:right;">SALDO AWAL</td>
                    <td class="amount">{{ number_format($beginningBalance, 2, ',', '.') }}</td>
                </tr>
                @foreach($transactions as $t)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($t['date'])->format('d/m/Y') }}</td>
                    <td>{{ $t['reference'] }}</td>
                    <td>{{ $t['description'] }}</td>
                    <td class="amount">{{ $t['debit'] > 0 ? number_format($t['debit'], 2, ',', '.') : '-' }}</td>
                    <td class="amount">{{ $t['credit'] > 0 ? number_format($t['credit'], 2, ',', '.') : '-' }}</td>
                    <td class="amount">{{ number_format($t['balance'], 2, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="5" style="text-align:right;">SALDO AKHIR</td>
                    <td class="amount">{{ number_format($endingBalance, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <h4 style="margin-bottom: 10px;">DAFTAR SALDO AKUN DETAIL</h4>
        <table class="format2-table">
            <thead>
                <tr>
                    <th style="width:60%;text-align:left;">AKUN / URAIAN</th>
                    <th style="width:20%;text-align:left;">TIPE</th>
                    <th style="width:20%;text-align:right;">SALDO</th>
                </tr>
            </thead>
            <tbody>
                @foreach($coas as $coa)
                    @if(!$filters['show_zero'] && $coa->balance == 0) @continue @endif
                    <tr @if($coa->is_header) class="font-bold" @endif>
                        <td style="padding-left: {{ $coa->level * 15 }}px;">
                            {{ $filters['show_code'] ? $coa->code . ' - ' : '' }}{{ $coa->name }}
                        </td>
                        <td>{{ strtoupper($coa->type) }}</td>
                        <td class="amount">{{ $coa->is_header ? '-' : number_format($coa->balance, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
