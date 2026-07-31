<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan ESS</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .title { font-size: 16px; font-weight: bold; text-align: center; margin-bottom: 5px; }
        .subtitle { font-size: 12px; text-align: center; margin-bottom: 20px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #0F172A; color: #ffffff; font-weight: bold; padding: 8px; border: 1px solid #cbd5e1; text-align: left; }
        td { padding: 6px 8px; border: 1px solid #cbd5e1; vertical-align: top; }
        .bg-section { background-color: #f1f5f9; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { font-weight: bold; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
    </style>
</head>
<body>

    <div class="title">LAPORAN REKAPITULASI & DETAIL PENGAJUAN ESS</div>
    <div class="subtitle">Sistem Employee Self Service (ESS) - EDU Management | Periode: {{ $period_label }} | Dibuat: {{ $generated_at }}</div>

    <!-- 1. RINGKASAN REKAPITULASI DIVISI -->
    <h3 style="margin-bottom: 8px; color: #1e293b;">I. REKAPITULASI PER DIVISI</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 40px;" class="text-center">No</th>
                <th>Nama Divisi</th>
                <th class="text-center">Total Pengajuan</th>
                <th class="text-right">Reimbursement (Rp)</th>
                <th class="text-right">Operasional (Rp)</th>
                <th class="text-center">Cuti (Hari)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($divisionSummary as $idx => $div)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><b>{{ $div['division_name'] }}</b></td>
                    <td class="text-center">{{ $div['total_requests'] }} Transaksi</td>
                    <td class="text-right">Rp {{ number_format($div['reimbursement_sum'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($div['operational_sum'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ $div['leave_days_sum'] }} Hari</td>
                </tr>
            @endforeach
            <tr class="bg-section">
                <td colspan="2" class="text-right"><b>GRAND TOTAL PERUSAHAAN:</b></td>
                <td class="text-center"><b>{{ $stats['grand_total_count'] }} Transaksi</b></td>
                <td class="text-right"><b>Rp {{ number_format($stats['total_reimbursement_amount'], 0, ',', '.') }}</b></td>
                <td class="text-right"><b>Rp {{ number_format($stats['total_operational_amount'], 0, ',', '.') }}</b></td>
                <td class="text-center"><b>{{ $stats['total_leave_days'] }} Hari</b></td>
            </tr>
        </tbody>
    </table>

    <br>

    <!-- 2. DAFTAR DETAIL LIST PENGAJUAN -->
    <h3 style="margin-bottom: 8px; color: #1e293b;">II. DAFTAR DETAIL LIST PENGAJUAN</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th>No. Pengajuan</th>
                <th>Tanggal</th>
                <th>NIK</th>
                <th>Nama Karyawan</th>
                <th>Divisi</th>
                <th>Layanan</th>
                <th>Kategori</th>
                <th class="text-right">Nominal / Durasi</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detailList as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><b>{{ $item['request_number'] }}</b></td>
                    <td>{{ $item['date'] }}</td>
                    <td>{{ $item['applicant_nik'] }}</td>
                    <td>{{ $item['applicant_name'] }}</td>
                    <td>{{ $item['division_name'] }}</td>
                    <td>{{ $item['type_label'] }}</td>
                    <td>{{ $item['category'] }}</td>
                    <td class="text-right"><b>{{ $item['amount_formatted'] }}</b></td>
                    <td class="text-center">{{ $item['status_label'] }}</td>
                </tr>
            @endforeach
            @if(count($detailList) === 0)
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #94a3b8;">
                        Tidak ada data transaksi pengajuan pada filter periode ini.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

</body>
</html>
