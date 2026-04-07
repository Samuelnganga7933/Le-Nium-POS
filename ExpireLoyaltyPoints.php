<?php

namespace App\Console\Commands;

use App\Models\{Customer, Company};
use Illuminate\Console\Command;

class ExpireLoyaltyPoints extends Command
{
    protected $signature   = 'loyalty:expire {--dry-run : Show what would be expired without making changes}';
    protected $description = 'Expire loyalty points inactive for 12+ months and sync customer tiers';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? 'DRY RUN — no changes made' : 'Expiring loyalty points...');

        $cutoff  = now()->subMonths(12);
        $expired = 0;
        $tiered  = 0;

        Customer::where('loyalty_points', '>', 0)
            ->where(fn($q) => $q->where('last_purchase_at', '<', $cutoff)->orWhereNull('last_purchase_at'))
            ->chunk(100, function ($customers) use ($dryRun, &$expired) {
                foreach ($customers as $customer) {
                    $this->line("  Expiring {$customer->loyalty_points} pts — {$customer->name}");
                    if (!$dryRun) {
                        $customer->loyaltyTransactions()->create([
                            'company_id'   => $customer->company_id,
                            'type'         => 'expire',
                            'points'       => -$customer->loyalty_points,
                            'balance_after'=> 0,
                            'description'  => 'Points expired after 12 months of inactivity',
                        ]);
                        $customer->update(['loyalty_points' => 0]);
                    }
                    $expired++;
                }
            });

        // Sync all customer tiers based on total_spent
        $tiers = config('lenium.loyalty.tiers', []);
        Customer::chunk(200, function ($customers) use ($tiers, $dryRun, &$tiered) {
            foreach ($customers as $customer) {
                $newTier = 'bronze';
                foreach ($tiers as $tier => $range) {
                    if ($customer->total_spent >= $range['min'] && ($range['max'] === null || $customer->total_spent <= $range['max'])) {
                        $newTier = $tier;
                    }
                }
                if ($customer->loyalty_tier !== $newTier) {
                    if (!$dryRun) $customer->update(['loyalty_tier' => $newTier]);
                    $tiered++;
                }
            }
        });

        $this->info("Expired points for {$expired} customers. Updated {$tiered} tier changes.");
        return Command::SUCCESS;
    }
}
