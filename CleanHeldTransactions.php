<?php

namespace App\Console\Commands;

use App\Models\HeldTransaction;
use Illuminate\Console\Command;

class CleanHeldTransactions extends Command
{
    protected $signature   = 'pos:clean-held';
    protected $description = 'Cancel and delete held transactions that have expired';

    public function handle(): int
    {
        $expired = HeldTransaction::where('expires_at', '<', now())->count();
        HeldTransaction::where('expires_at', '<', now())->delete();
        $this->info("Cleaned {$expired} expired held transactions.");
        return Command::SUCCESS;
    }
}
