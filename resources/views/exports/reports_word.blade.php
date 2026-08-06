<!DOCTYPE html>
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <meta charset="utf-8">
    <title>Laporan Resmi ESS</title>
    <!--[if gte mso 9]>
    <xml>
    <w:WordDocument>
    <w:View>Print</w:View>
    <w:Zoom>100</w:Zoom>
    <w:DoNotOptimizeForBrowser/>
    </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11pt; color: #1e293b; line-height: 1.4; }
        .kop-header { text-align: center; border-bottom: 3px double #0F172A; padding-bottom: 12px; margin-bottom: 20px; }
        .kop-header h1 { margin: 0; font-size: 20pt; font-weight: 900; color: #0F172A; letter-spacing: 1px; }
        .kop-header p { margin: 3px 0 0 0; font-size: 10pt; color: #64748b; font-weight: 600; uppercase; }
        
        .doc-title { text-align: center; font-size: 14pt; font-weight: bold; text-decoration: underline; margin-bottom: 4px; uppercase; }
        .doc-meta { text-align: center; font-size: 9pt; color: #64748b; margin-bottom: 25px; }

        .summary-box { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 18px; margin-bottom: 20px; border-radius: 8px; }
        .summary-box table { width: 100%; border: none; }
        .summary-box td { border: none; padding: 4px; font-size: 10pt; }

        h3 { font-size: 12pt; color: #0F172A; margin-top: 25px; margin-bottom: 8px; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #0F172A; color: #ffffff; font-size: 9pt; font-weight: bold; padding: 8px; border: 1px solid #cbd5e1; text-align: left; }
        td { padding: 6px 8px; border: 1px solid #cbd5e1; font-size: 9.5pt; vertical-align: top; }
        .bg-total { background-color: #f1f5f9; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .signature-table { width: 100%; margin-top: 40px; border: none; }
        .signature-table td { border: none; text-align: center; font-size: 10pt; vertical-align: top; width: 50%; }
        .signature-space { height: 70px; }
    </style>
</head>
<body>

    <!-- KOP SURAT / HEADER DOKUMEN RESMI -->
    <div class="kop-header">
        <h1>EDU MANAGEMENT PORTAL</h1>
        <p>LAPORAN EKSEKUTIF EMPLOYEE SELF SERVICE (ESS)</p>
    </div>

    <div class="doc-title">LAPORAN REKAPITULASI & DETAIL PENGAJUAN</div>
    <div class="doc-meta">Periode: {{ $period_label }} | Tanggal Cetak: {{ $generated_at }}</div>

    <!-- EKSEKUTIF SUMMARY BOX -->
    <div class="summary-box">
        <table>
            <tr>
                <td><b>Total Pengajuan Reimbursement:</b> {{ $stats['total_reimbursement_count'] }} Transaksi</td>
                <td><b>Total Nominal Reimbursement:</b> Rp {{ number_format($stats['total_reimbursement_amount'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><b>Total Pengajuan Operasional:</b> {{ $stats['total_operational_count'] }} Transaksi</td>
                <td><b>Total Nominal Operasional:</b> Rp {{ number_format($stats['total_operational_amount'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><b>Total Pengajuan Cuti:</b> {{ $stats['total_leave_count'] }} Transaksi</td>
                <td><b>Total Hari Cuti Disetujui:</b> {{ $stats['total_leave_days'] }} Hari</td>
            </tr>
            <tr>
                <td><b>Total Pengajuan Perjalanan Dinas:</b> {{ $stats['total_business_trip_count'] }} Transaksi</td>
                <td><b>Total Nominal Dinas:</b> Rp {{ number_format($stats['total_business_trip_amount'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- I. REKAPITULASI DIVISI -->
    <h3>I. REKAPITULASI PER DIVISI</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th>Nama Divisi</th>
                <th class="text-center">Total Transaksi</th>
                <th class="text-right">Total Reimbursement</th>
                <th class="text-right">Total Operasional</th>
                <th class="text-center">Cuti (Hari)</th>
                <th class="text-right">Total Dinas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($divisionSummary as $idx => $div)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><b>{{ $div['division_name'] }}</b></td>
                    <td class="text-center">{{ $div['total_requests'] }}</td>
                    <td class="text-right">Rp {{ number_format($div['reimbursement_sum'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($div['operational_sum'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ $div['leave_days_sum'] }} Hari</td>
                    <td class="text-right">Rp {{ number_format($div['business_trip_sum'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="bg-total">
                <td colspan="2" class="text-right">GRAND TOTAL PERUSAHAAN:</td>
                <td class="text-center">{{ $stats['grand_total_count'] }}</td>
                <td class="text-right">Rp {{ number_format($stats['total_reimbursement_amount'], 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($stats['total_operational_amount'], 0, ',', '.') }}</td>
                <td class="text-center">{{ $stats['total_leave_days'] }} Hari</td>
                <td class="text-right">Rp {{ number_format($stats['total_business_trip_amount'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <br>

    <!-- II. DAFTAR DETAIL LIST PENGAJUAN -->
    <h3>II. DAFTAR DETAIL LIST PENGAJUAN</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th>No. Pengajuan</th>
                <th>Tanggal</th>
                <th>Nama Karyawan</th>
                <th>Divisi</th>
                <th>Jenis Layanan</th>
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
                    <td>{{ $item['applicant_name'] }}</td>
                    <td>{{ $item['division_name'] }}</td>
                    <td>{{ $item['type_label'] }} ({{ $item['category'] }})</td>
                    <td class="text-right"><b>{{ $item['amount_formatted'] }}</b></td>
                    <td class="text-center">{{ $item['status_label'] }}</td>
                </tr>
            @endforeach
            @if(count($detailList) === 0)
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px; color: #94a3b8;">
                        Tidak ada transaksi pengajuan pada filter periode ini.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN -->
    <table class="signature-table">
        <tr>
            <td>
                Dibuat Oleh,<br>
                <b>HRD & Finance Department</b>
                <div class="signature-space"></div>
                <b>( _______________________ )</b><br>
                <span>HRD & Finance Specialist</span>
            </td>
            <td>
                Mengetahui & Menyetujui,<br>
                <b>System Administrator / Direksi</b>
                <div class="signature-space"></div>
                <b>( _______________________ )</b><br>
                <span>Executive Management</span>
            </td>
        </tr>
    </table>

</body>
</html>
