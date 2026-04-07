<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class GenerateDailyReport extends Command
{
    protected $signature   = 'report:daily {--company= : Specific company ID}';
    protected $description = 'Generate and email daily sales report to business owners';

    public function handle(): int
    {
        $this->info('Generating daily reports...');

        $query = Company::where('status', 'active')
            ->where('approved_by_admin', true);

        if ($this->option('company')) {
            $query->where('id', $this->option('company'));
        }

        $companies = $query->get();
        $sent      = 0;

        foreach ($companies as $company) {
            try {
                $this->generateForCompany($company);
                $sent++;
                $this->line("  ✓ {$company->name}");
            } catch (\Exception $e) {
                Log::error("Daily report failed for {$company->name}: " . $e->getMessage());
                $this->error("  ✗ {$company->name}: " . $e->getMessage());
            }
        }

        $this->info("Done. {$sent}/{$companies->count()} reports sent.");
        return Command::SUCCESS;
    }

    private function generateForCompany(Company $company): void
    {
        $yesterday     = now()->subDay()->toDateString();
        $revenue       = Sale::where('company_id', $company->id)->whereDate('created_at', $yesterday)->sum('total');
        $transactions  = Sale::where('company_id', $company->id)->whereDate('created_at', $yesterday)->count();
        $lowStock      = Stock::where('company_id', $company->id)->whereColumn('quantity', '<=', 'reorder_level')->count();

        $owner = $company->users()->where('role', 'company_owner')->first();
        if (!$owner || !$owner->email) return;

        // Check if owner wants daily reports
        $wantsReport = $company->getSetting('daily_report', '1');
        if (!$wantsReport || $wantsReport === '0') return;

        // Send email (implement Mail class separately)
        // Mail::to($owner->email)->send(new DailyReportMail($company, $revenue, $transactions, $lowStock));

        Log::info("Daily report: {$company->name} | KSh " . number_format($revenue) . " | {$transactions} tx");
    }
}
