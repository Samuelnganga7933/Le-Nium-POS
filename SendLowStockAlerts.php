<?php

namespace App\Console\Commands;

use App\Models\{Company, Stock};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendLowStockAlerts extends Command
{
    protected $signature   = 'stock:alerts';
    protected $description = 'Send low stock SMS/email alerts to business owners';

    public function handle(): int
    {
        $this->info('Checking stock levels...');

        Company::where('status', 'active')->chunk(50, function ($companies) {
            foreach ($companies as $company) {
                $wantsAlerts = $company->getSetting('low_stock_alert', '1');
                if (!$wantsAlerts || $wantsAlerts === '0') continue;

                $threshold = (int) $company->getSetting('low_stock_threshold', 10);

                $lowItems = Stock::where('company_id', $company->id)
                    ->where(fn($q) => $q->where('quantity', '<=', 0)
                        ->orWhereRaw('quantity <= reorder_level'))
                    ->with('product')
                    ->get();

                if ($lowItems->isEmpty()) continue;

                $owner = $company->users()->where('role', 'company_owner')->first();
                if (!$owner || !$owner->phone) continue;

                $names  = $lowItems->take(3)->pluck('product.name')->implode(', ');
                $more   = max(0, $lowItems->count() - 3);
                $msg    = "Le Nium POS: Low stock alert for {$company->name}. {$lowItems->count()} item(s) need restocking: {$names}" . ($more > 0 ? " and {$more} more." : '.');

                // Send via Africa's Talking (implement separately)
                Log::info("Low stock alert: {$company->name} | {$lowItems->count()} items | {$owner->phone}");
                $this->line("  ✓ {$company->name} — {$lowItems->count()} items");
            }
        });

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
