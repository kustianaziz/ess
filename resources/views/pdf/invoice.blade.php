<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->customer->name }}</title>
    <style>
        /* Reset & Base */
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; line-height: 1.3; margin: 0; padding: 20px; background: #fff; }
        .container { width: 100%; max-width: 100%; margin: 0; background: #fff; padding: 0; }
        
        /* Helpers */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        
        /* Header Section */
        .header { width: 100%; margin-bottom: 30px; }
        .header td { vertical-align: top; }
        .header-left { width: 45%; } 
        .header-right { width: 55%; text-align: right; }
        
        .logo-img { max-width: 180px; margin-bottom: 15px; display: block; }
        
        .client-box { margin-top: 10px; }
        .client-label { font-size: 11px; color: #555; }
        .client-name { font-size: 14px; font-weight: bold; text-transform: uppercase; margin: 3px 0; }
        .client-address { font-size: 11px; color: #333; line-height: 1.4; max-width: 300px; }

        .company-address { font-size: 10px; color: #555; margin-bottom: 15px; line-height: 1.4; }
        
        /* Summary Box */
        .summary-table { width: 100%; border-collapse: collapse; margin-left: auto; }
        .summary-table th, .summary-table td { border: 1px solid #000; padding: 6px; font-size: 11px; text-align: center; vertical-align: middle; }
        .summary-table th { background-color: #eee; white-space: nowrap; }
        .summary-table td { font-size: 12px; } 
        
        /* Invoice Title */
        .invoice-title { font-size: 24px; font-weight: bold; text-align: left; margin: 30px 0 10px 0; color: #000; letter-spacing: 1px; border-bottom: 2px solid #000; padding-bottom: 5px; width: 100%; }
        .tagihan-label { font-size: 14px; font-weight: bold; margin-bottom: 5px; display: block; }

        /* Main Table */
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 8px 10px; vertical-align: top; }
        .main-table th { background-color: #e1f5fe; text-align: center; font-weight: bold; color: #000; }
        
        .col-no { width: 40px; text-align: center; }
        .col-amount { width: 150px; text-align: right; }
        .col-ket { width: 100px; text-align: center; }
        .total-row td { background-color: #e1f5fe; font-weight: bold; }

        /* Terbilang */
        .terbilang-box { border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; padding: 8px 0; margin-bottom: 30px; font-style: italic; font-weight: bold; font-size: 13px; }

        /* Footer Section */
        .footer-flex { width: 100%; }
        .footer-flex td { vertical-align: top; }
        .payment-info { width: 65%; font-size: 11px; }
        .payment-info h4 { margin: 0 0 5px 0; font-size: 12px; text-decoration: underline; }
        .bank-table td { padding: 2px 10px 2px 0; font-weight: bold; font-size: 11px; border: none !important; text-align: left; }
        
        /* Signature Box */
        .signature-box { 
            width: 35%; 
            text-align: center; 
            position: relative; 
            margin-top: 10px;
        }
        .sign-date { margin-bottom: 5px; font-size: 11px; }
        .sign-company { font-weight: bold; font-size: 12px; margin-bottom: 45px; } /* Ruang untuk ttd jika tidak ada img */
        .sign-name { font-weight: bold; text-decoration: underline; font-size: 12px; margin-top: 5px; }
        .sign-role { font-size: 11px; }

        /* Instructions */
        .instructions { margin-top: 40px; font-size: 10px; color: #444; border-top: 2px solid #000; padding-top: 10px; }
        .instructions ul { margin: 5px 0; padding-left: 15px; }
        .instructions li { margin-bottom: 3px; }
        
        .footer-quote { text-align: center; margin-top: 20px; font-style: italic; color: #009FE3; font-weight: bold; font-size: 11px; }

    </style>
</head>
<body>

<div class="container">
    <table class="header">
        <tr>
            <td class="header-left">
                @php
                    $logo = public_path('logo_emd.png');
                    if(isset($invoice->customer->service->logo) && $invoice->customer->service->logo != '') {
                        $logo = storage_path('app/public/' . $invoice->customer->service->logo);
                    }
                @endphp
                <img src="{{ $logo }}" alt="Logo" class="logo-img" style="max-height: 80px; width: auto;">
                <div class="client-box">
                    <div class="client-label">Kepada:</div>
                    <div class="client-name">{{ $invoice->customer->name }}</div>
                    <div class="client-address">
                        Di Tempat<br>
                        {{ $invoice->customer->address ?: '(Silahkan lengkapi data alamat di master client)' }}
                    </div>
                </div>
            </td>

            <td class="header-right">
                <div class="company-address">
                    <b>Wisma Bumiputera Bandung</b><br>
                    Jl. Asia Afrika No.141-149 Lantai 7 Suite 707, Kebon Pisang, Sumurbandung, Bandung City, West Java 40261<br>
                    Phone: 022-20665633 Mobile: 082258858864
                </div>
                <table class="summary-table" style="float: right; width: auto;">
                    <tr><th>Invoice Number</th><th>Due Date</th><th>Amount Due</th></tr>
                    <tr>
                        <td style="white-space: nowrap;"><b>{{ $invoice->invoice_number }}</b></td>
                        <td style="white-space: nowrap;">{{ \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d F Y') }}</td>
                        <td style="white-space: nowrap;"><b>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }},-</b></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <span class="tagihan-label">Tagihan :</span>
    <div class="invoice-title">INVOICE</div>

    <table class="main-table">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-desc">Deskripsi</th>
                <th class="col-amount">Jumlah</th>
                <th class="col-ket">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $item->description }}<br>Qty: {{ $item->qty }}</td>
                <td class="col-amount">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                <td class="col-ket">-</td>
            </tr>
            @endforeach
            
            @if($invoice->tax_amount > 0)
            <tr>
                <td></td>
                <td class="text-right">PPN</td>
                <td class="col-amount">{{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            @endif

            <tr style="height: 50px;"><td></td><td></td><td></td><td></td></tr>
            <tr class="total-row">
                <td colspan="2" class="text-right">Total Tagihan</td>
                <td class="col-amount">{{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="terbilang-box">Terbilang: # {{ $terbilang }} Rupiah #</div>

    <table class="footer-flex">
        <tr>
            <td class="payment-info">
                <h4>Informasi Pembayaran:</h4>
                @if(isset($invoice->customer->service->bank_credentials) && $invoice->customer->service->bank_credentials != '')
                    <div style="font-weight: bold; font-size: 11px; white-space: pre-wrap;">{{ $invoice->customer->service->bank_credentials }}</div>
                @else
                    <table class="bank-table">
                        <tr><td>Account</td><td>: PT. EDU MEDIA DIGITAL</td></tr>
                        <tr><td>Bank</td><td>: MANDIRI</td></tr>
                        <tr><td>No. Acc</td><td>: 132-00-2333843-8</td></tr>
                    </table>
                @endif
            </td>

            <td class="signature-box">
                <div class="sign-date">Bandung, {{ \Carbon\Carbon::parse($invoice->invoice_date)->translatedFormat('d F Y') }}</div>
                <div class="sign-company">
                    {{ isset($invoice->customer->service) ? $invoice->customer->service->name : 'PT. EDU MEDIA DIGITAL' }}
                </div>
                
                <!-- Signature Area -->
                @if(isset($invoice->customer->service->signature_image) && $invoice->customer->service->signature_image != '')
                    <div style="margin-top: 10px; margin-bottom: 5px;">
                        <img src="{{ storage_path('app/public/' . $invoice->customer->service->signature_image) }}" style="max-height: 80px; width: auto;" alt="Signature">
                    </div>
                @else
                    <br><br><br>
                @endif
                
                <div class="sign-name">
                    {{ isset($invoice->customer->service->signature_name) && $invoice->customer->service->signature_name != '' ? $invoice->customer->service->signature_name : 'Fourizal Novyansyah' }}
                </div>
                <div class="sign-role">Finance</div>
            </td>
        </tr>
    </table>

    <div class="instructions">
        <b>Instruksi Pembayaran:</b>
        <ul>
            <li>Pembayaran dilakukan melalui Transfer Via Rekening Perusahaan yang tercantum dalam invoice.</li>
            <li>Keterlambatan dan ketidakjelasan pembayaran akan mengakibatkan tidak tercatatnya di akunting kami.</li>
            <li>Harap mencantumkan nomor invoice dan email bukti pembayaran.</li>
            <li>Konfirmasi tagihan/pembayaran silahkan email ke: <b>fourizal75@gmail.com</b> atau <b>aisitinuralisah6@gmail.com</b> (finance).</li>
            <li>Kontak Person: Rizal 082118844325 / Siti Nuralisah 081312703928 (whatsapp).</li>
        </ul>
        <div class="footer-quote">We'll Make It Real. There Is No Best Only Better.</div>
    </div>
</div>

</body>
</html>
