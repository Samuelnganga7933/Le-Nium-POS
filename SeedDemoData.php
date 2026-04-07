<?php

namespace App\Console\Commands;

use App\Models\{Company, Sale, SaleItem, Payment, Customer, Product, Stock, Branch, User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedDemoData extends Command
{
    protected $signature   = 'demo:seed {--company= : Company ID to seed} {--days=30 : Number of days of demo sales}';
    protected $description = 'Seed realistic demo sales data for testing dashboards and reports';

    public function handle(): int
    {
        $companyId = $this->option('company');
        $days      = (int) $this->option('days');

        if ($companyId) {
            $company = Company::findOrFail($companyId);
        } else {
            $company = Company::where('status', 'active')->first();
        }

        if (!$company) {
            $this->error('No company found. Run php artisan db:seed first.');
            return Command::FAILURE;
        }

        $this->info("Seeding {$days} days of demo data for: {$company->name}");

        $branch    = $company->mainBranch();
        $cashier   = $company->users()->where('role', 'cashier')->first()
                  ?? $company->users()->where('role', 'company_owner')->first();
        $products  = Product::where('company_id', $company->id)->get();
        $customers = Customer::where('company_id', $company->id)->get();

        if ($products->isEmpty()) {
            $this->error('No products found. Seed products first.');
            return Command::FAILURE;
        }

        $bar       = $this->output->createProgressBar($days);
        $bar->start();

        for ($d = $days; $d >= 0; $d--) {
            $date        = now()->subDays($d)->format('Y-m-d');
            $txCount     = rand(15, 80);

            for ($t = 0; $t < $txCount; $t++) {
                $itemCount   = rand(1, 6);
                $customer    = rand(0, 3) === 0 ? $customers->random() : null;
                $subtotal    = 0;
                $items       = [];

                for ($i = 0; $i < $itemCount; $i++) {
                    $product   = $products->random();
                    $qty       = rand(1, 5);
                    $lineTotal = $product->selling_price * $qty;
                    $subtotal += $lineTotal;
                    $items[]   = compact('product', 'qty', 'lineTotal');
                }

                $tax   = round($subtotal * 0.16, 2);
                $total = $subtotal + $tax;

                $receiptNum = 'RCP-' . str_replace('-', '', $date) . '-' . strtoupper(Str::random(4));

                $sale = Sale::create([
                    'company_id'         => $company->id,
                    'branch_id'          => $branch->id,
                    'cashier_id'         => $cashier->id,
                    'customer_id'        => $customer?->id,
                    'receipt_number'     => $receiptNum,
                    'subtotal'           => $subtotal,
                    'tax_amount'         => $tax,
                    'total'              => $total,
                    'status'             => 'completed',
                    'loyalty_points_earned' => $customer ? round($subtotal * 1) : 0,
                    'created_at'         => $date . ' ' . sprintf('%02d:%02d:%02d', rand(8, 20), rand(0, 59), rand(0, 59)),
                    'updated_at'         => $date . ' ' . sprintf('%02d:%02d:%02d', rand(8, 20), rand(0, 59), rand(0, 59)),
                ]);

                foreach ($items as $item) {
                    SaleItem::create([
                        'sale_id'      => $sale->id,
                        'product_id'   => $item['product']->id,
                        'product_name' => $item['product']->name,
                        'quantity'     => $item['qty'],
                        'unit_price'   => $item['product']->selling_price,
                        'buying_price' => $item['product']->buying_price,
                        'subtotal'     => $item['lineTotal'],
                    ]);
                }

                $methods = ['cash','mpesa_stk','mpesa_stk','mpesa_stk','mpesa_manual','card'];
                Payment::create([
                    'sale_id'    => $sale->id,
                    'company_id' => $company->id,
                    'method'     => $methods[array_rand($methods)],
                    'amount'     => $total,
                    'status'     => 'confirmed',
                    'confirmed_at' => $sale->created_at,
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Demo data seeded successfully!');
        $this->table(['Metric', 'Value'], [
            ['Days seeded', $days],
            ['Approx transactions', $days * 40],
            ['Company', $company->name],
        ]);

        return Command::SUCCESS;
    }
}
