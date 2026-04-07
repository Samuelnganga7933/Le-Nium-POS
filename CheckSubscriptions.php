<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class CheckSubscriptions extends Command
{
    protected $signature   = 'subscriptions:check';
    protected $description = 'Check for expired trials and subscriptions, suspend if needed';

    public function handle(): int
    {
        // Expire trials
        $expiredTrials = Company::where('subscription_plan', 'trial')
            ->where('status', 'active')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($expiredTrials as $company) {
            $company->update(['status' => 'pending_approval']);
            $this->line("Trial expired: {$company->name}");
        }

        // Expire paid subscriptions
        $expiredSubs = Company::whereIn('subscription_plan', ['minimart','minimart_pro','supermarket'])
            ->where('status', 'active')
            ->where('subscription_ends_at', '<', now())
            ->get();

        foreach ($expiredSubs as $company) {
            $company->update(['status' => 'suspended']);
            $this->line("Subscription expired: {$company->name}");
        }

        $total = $expiredTrials->count() + $expiredSubs->count();
        $this->info("Processed {$total} subscription changes.");
        return Command::SUCCESS;
    }
}
