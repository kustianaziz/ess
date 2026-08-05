<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;

class MigrateSubscriptions extends Command
{
    protected $signature = 'app:migrate-subscriptions';
    protected $description = 'Migrate data from subscriptions table to customers and invoices tables';

    public function handle()
    {
        $this->info('Starting migration from subscriptions to customers and invoices...');
        
        $subscriptions = DB::table('subscriptions')->get();
        $count = 0;
        
        DB::transaction(function () use ($subscriptions, &$count) {
            foreach ($subscriptions as $sub) {
                // 1. Create or Find Customer
                $customer = Customer::firstOrCreate(
                    ['name' => $sub->client_name],
                    [
                        'address' => $sub->address,
                        'phone' => $sub->pic_teknis_phone ?? $sub->pic_finance_phone,
                        'pic_name' => $sub->pic_teknis_name ?? $sub->pic_finance_name,
                        'pic_phone' => $sub->pic_teknis_phone ?? $sub->pic_finance_phone,
                        'status' => strtolower($sub->status) === 'active' ? 'active' : 'inactive',
                    ]
                );

                // 2. Create Invoice if price > 0
                if ($sub->price > 0) {
                    $invoiceDate = $sub->start_date && $sub->start_date !== '0000-00-00' && $sub->start_date !== '1970-01-01' 
                                    ? Carbon::parse($sub->start_date) : Carbon::now();
                    $dueDate = $sub->end_date && $sub->end_date !== '0000-00-00' && $sub->end_date !== '1970-01-01' 
                                    ? Carbon::parse($sub->end_date) : $invoiceDate->copy()->addDays(30);

                    // Generate Invoice Number
                    $invCount = Invoice::whereYear('created_at', date('Y'))->whereMonth('created_at', date('m'))->count() + 1;
                    $invNumber = 'INV/' . date('Y/m/') . str_pad($invCount + $count, 4, '0', STR_PAD_LEFT);

                    $invoice = Invoice::create([
                        'invoice_number' => $invNumber,
                        'customer_id' => $customer->id,
                        'invoice_date' => $invoiceDate->format('Y-m-d'),
                        'due_date' => $dueDate->format('Y-m-d'),
                        'subtotal' => $sub->price,
                        'tax_amount' => 0, // Assuming no tax in legacy data unless specified
                        'total_amount' => $sub->price,
                        'notes' => $sub->notes,
                        'status' => 'sent', // Default to sent so it shows up nicely
                        'created_by' => 1,
                    ]);

                    // 3. Create Invoice Item
                    $desc = $sub->product_name ?: 'Layanan ' . $sub->billing_cycle;
                    if ($sub->description) {
                        $desc .= ' - ' . strip_tags($sub->description);
                    }

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $desc,
                        'qty' => 1,
                        'unit_price' => $sub->price,
                        'subtotal' => $sub->price,
                    ]);
                    
                    $count++;
                }
            }
        });

        $this->info("Successfully migrated {$count} subscriptions to customers and invoices.");
        
        // Optional: drop the old table now that it's migrated
        // \Illuminate\Support\Facades\Schema::dropIfExists('subscriptions');
    }
}
