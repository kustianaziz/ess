<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice->customer->name }}</title>
    <style>
        @page { margin: 30px 40px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #374151; /* Dark gray */
            line-height: 1.4;
            background: #fff;
        }
        table { width: 100%; border-collapse: collapse; }
        
        /* Header */
        .header-table { margin-bottom: 20px; }
        .header-table td { vertical-align: top; }
        .header-left { width: 60%; }
        .header-right { width: 40%; text-align: right; }
        
        .company-name { font-size: 20px; font-weight: bold; color: #000; margin-bottom: 5px; }
        .invoice-title { font-size: 28px; font-weight: bold; color: #000; margin-bottom: 10px; text-transform: uppercase; }
        
        .header-info-table { width: 100%; font-size: 11px; margin-top: 10px;}
        .header-info-table td { padding: 2px 0; }
        .info-label { color: #555; width: 100px; }
        .info-val { font-weight: bold; text-align: right; }
        
        /* Customer */
        .customer-section { margin-bottom: 25px; }
        .customer-title { font-size: 13px; font-weight: bold; margin-bottom: 5px; background: #F8FAFC; border-bottom: 2px solid #AFABAB; padding: 4px; display: inline-block; min-width: 200px; }
        .customer-details { padding-left: 5px; font-size: 11px; }
        
        /* Items Table */
        .items-table { width: 100%; margin-bottom: 10px; }
        .items-table th {
            background-color: #374151;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 8px 5px;
            border: 1px solid #A6A6A6;
        }
        .items-table td {
            padding: 8px 5px;
            border-bottom: 1px solid #F2F2F2;
            border-left: 1px solid #F2F2F2;
            border-right: 1px solid #F2F2F2;
            vertical-align: top;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        
        /* Summary Section */
        .summary-container { width: 100%; margin-bottom: 30px; }
        .summary-container td { vertical-align: top; }
        
        .terbilang-label { font-weight: bold; font-size: 12px; margin-bottom: 5px; }
        .terbilang-text { font-style: italic; background-color: #F8FAFC; padding: 10px; border-left: 3px solid #374151; font-size: 11px; }
        
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 5px; font-size: 11px; }
        .totals-label { text-align: right; font-weight: bold; width: 60%; }
        .totals-val { text-align: right; font-weight: bold; width: 40%; }
        .totals-table tr.total-row td { background-color: #F2F2F2; border-top: 1px solid #AFABAB; border-bottom: 1px solid #AFABAB; font-size: 12px; }
        
        /* Footer */
        .footer-table { width: 100%; margin-top: 20px; }
        .footer-table td { vertical-align: top; }
        .footer-title { font-weight: bold; font-size: 11px; border-bottom: 1px solid #AFABAB; padding-bottom: 3px; margin-bottom: 5px; }
        .footer-content { font-size: 10px; }
        
        .signature-box { text-align: center; }
        .signature-title { margin-bottom: 10px; }
        .signature-name { font-weight: bold; margin-top: 5px; }
        .signature-role { font-size: 10px; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td class="header-left">
                <table>
                    <tr>
                        <td style="width: 70px;">
                            @php
                                $logo = public_path('logo_emd.png');
                                if(isset($invoice->customer->service->logo) && $invoice->customer->service->logo != '') {
                                    $logo = storage_path('app/public/' . $invoice->customer->service->logo);
                                }
                            @endphp
                            <img src="{{ $logo }}" style="max-height: 60px; max-width: 65px;" alt="Logo">
                        </td>
                        <td style="vertical-align: middle;">
                            <div class="company-name">{{ isset($invoice->customer->service) ? $invoice->customer->service->name : 'Edu Media Digital' }}</div>
                        </td>
                    </tr>
                </table>
                <div style="margin-top: 15px; font-weight: bold; font-size: 12px;">No. {{ $invoice->invoice_number }}</div>
                <div style="margin-top: 5px; font-size: 10px; color: #555;">
                    @if(isset($invoice->customer->service) && $invoice->customer->service->address)
                        {!! nl2br(e($invoice->customer->service->address)) !!}
                    @else
                        Wisma Bumiputera Bandung Lantai VII Suite 707<br>
                        Jl. Asia Afrika No.141-149 Bandung, West Java, 40261<br>
                        0822 5885 8864 : info@edumediadigital.co.id<br>
                        www.edumediadigital.co.id
                    @endif
                </div>
            </td>
            <td class="header-right">
                <div class="invoice-title">INVOICE</div>
                <table class="header-info-table">
                    <tr>
                        <td class="info-label">Diterbitkan di</td>
                        <td style="width: 10px;">:</td>
                        <td class="info-val">Bandung</td>
                    </tr>
                    <tr>
                        <td class="info-label">Tanggal Tagihan</td>
                        <td>:</td>
                        <td class="info-val">{{ \Carbon\Carbon::parse($invoice->invoice_date)->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label" style="color:#CC3300;">Jatuh Tempo</td>
                        <td style="color:#CC3300;">:</td>
                        <td class="info-val" style="color:#CC3300;">{{ \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d F Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <br>

    <!-- CUSTOMER INFO -->
    <div class="customer-section">
        <div class="customer-title">Kepada Yth :</div>
        <div class="customer-details">
            <div style="font-weight: bold; font-size: 12px;">{{ $invoice->customer->name }}</div>
            <div style="margin-top: 3px; max-width: 60%;">
                {!! nl2br(e($invoice->customer->address ?: '-')) !!}
            </div>
            @if($invoice->po_number)
            <div style="margin-top: 5px; font-style: italic;">Ref/PO: {{ $invoice->po_number }}</div>
            @endif
        </div>
    </div>

    <!-- ITEMS -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%; text-align: left;">Deskripsi</th>
                <th style="width: 10%;">Kuantiti</th>
                <th style="width: 20%; text-align: right;">Harga Satuan</th>
                <th style="width: 20%; text-align: right;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{!! nl2br(e($item->description)) !!}</td>
                <td class="text-center">{{ $item->qty }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <br>

    <!-- SUMMARY -->
    <table class="summary-container">
        <tr>
            <td style="width: 55%; padding-right: 20px;">
                <div class="terbilang-label">Terbilang :</div>
                <div class="terbilang-text">
                    {{ $terbilang }} Rupiah
                </div>
            </td>
            <td style="width: 45%;">
                <table class="totals-table">
                    <tr>
                        <td class="totals-label">Subtotal :</td>
                        <td class="totals-val">{{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="totals-label">Diskon :</td>
                        <td class="totals-val">{{ $invoice->discount_amount > 0 ? number_format($invoice->discount_amount, 0, ',', '.') : '-' }}</td>
                    </tr>
                    @if($invoice->tax_amount > 0)
                    <tr>
                        <td class="totals-label">PPN :</td>
                        <td class="totals-val">{{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="totals-label">Total :</td>
                        <td class="totals-val">{{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <br>

    <!-- FOOTER (Payment, Notes, Signature) -->
    <table class="footer-table">
        <tr>
            <td style="width: 33%; padding-right: 15px;">
                <div class="footer-title">Informasi Pembayaran</div>
                <div class="footer-content">
                    @if(isset($invoice->customer->service->bank_credentials) && $invoice->customer->service->bank_credentials != '')
                        {!! nl2br(e($invoice->customer->service->bank_credentials)) !!}
                    @else
                        Bank : Mandiri KCP Bdg Setiabudi<br>
                        No. Rekening : 132 00 2333843 8<br>
                        Atas Nama : PT. Edu Media Digital<br><br>
                        Mohon mencantumkan nomor invoice pada berita/keterangan transfer.
                    @endif
                </div>
            </td>
            <td style="width: 33%; padding-right: 15px;">
                <div class="footer-title">Catatan</div>
                <div class="footer-content">
                    @if($invoice->notes)
                        {!! nl2br(e($invoice->notes)) !!}
                    @else
                        1. Invoice merupakan tagihan atas jasa sesuai kesepakatan.<br>
                        2. Pembayaran dilakukan sesuai tanggal jatuh tempo.<br>
                        3. Bukti pembayaran dapat dikirim ke info@edumediadigital.co.id<br>
                        4. Ketentuan pajak mengikuti ketentuan yang berlaku.
                    @endif
                </div>
            </td>
            <td style="width: 34%;">
                <div class="signature-box">
                    <div class="signature-title">Hormat Kami,</div>
                    <br>
                    
                    <div style="position: relative; height: 70px; margin: 0 auto; width: 100%; text-align: center;">
                        @if(isset($invoice->customer->service->stamp_image) && $invoice->customer->service->stamp_image != '')
                            <img src="{{ storage_path('app/public/' . $invoice->customer->service->stamp_image) }}" style="max-height: 60px; width: auto; position: absolute; left: 10px; top: 0px; opacity: 0.85; z-index: 1;" alt="Stamp">
                        @endif

                        @if(isset($invoice->customer->service->signature_image) && $invoice->customer->service->signature_image != '')
                            <img src="{{ storage_path('app/public/' . $invoice->customer->service->signature_image) }}" style="max-height: 70px; width: auto; position: relative; z-index: 2; margin: 0 auto; display: block;" alt="Signature">
                        @endif
                    </div>
                    
                    <div class="signature-name">
                        {{ isset($invoice->customer->service->signature_name) && $invoice->customer->service->signature_name != '' ? $invoice->customer->service->signature_name : 'Ucu Komarudin' }}
                    </div>
                    <div class="signature-role">
                        {{ isset($invoice->customer->service) && $invoice->customer->service->name != '' ? 'Direktur Utama' : 'Direktur Utama' }}<br>
                        {{ isset($invoice->customer->service) ? $invoice->customer->service->name : 'PT. Edu Media Digital' }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
