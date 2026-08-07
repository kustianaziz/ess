<?php

use App\Models\RenewalRequest;
use App\Models\CashAccount;
use App\Models\InvoicePayment;

$renewals = RenewalRequest::with('invoice.payments')->whereIn('status', ['paid_customer', 'paid_vendor', 'completed'])->get();
foreach($renewals as $r) {
    if($r->invoice && $r->invoice->payments->count() == 0) {
        echo 'Fixing Renewal ' . $r->id . '...' . PHP_EOL;
        $payment = $r->invoice->payments()->create([
            'amount' => $r->invoice->total_amount,
            'payment_date' => $r->updated_at ?? now(),
            'payment_method' => 'transfer',
            'recorded_by' => 1
        ]);
        
        $r->invoice->update([
            'paid_amount' => $r->invoice->total_amount,
            'status' => 'paid'
        ]);
        
        $ca = CashAccount::find(1);
        if ($ca) {
            $ca->increment('current_balance', $r->invoice->total_amount);
            $txNumber = 'KAS/' . date('Y/m/') . 'FX' . rand(1000, 9999) . $r->id;
            $ca->transactions()->create([
                'transaction_number' => $txNumber,
                'type' => 'in',
                'category' => 'lainnya',
                'amount' => $r->invoice->total_amount,
                'transaction_date' => $r->updated_at ?? now(),
                'description' => 'Payment for Invoice #'.$r->invoice->invoice_number.' (Auto-fixed)',
                'source_type' => InvoicePayment::class,
                'source_id' => $payment->id,
                'status' => 'posted',
                'created_by' => 1
            ]);
        }
        echo 'Fixed!' . PHP_EOL;
    }
}
