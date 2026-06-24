<?php

namespace App\Console\Commands;

use App\Domain\Orders\Services\RecoveryRuleExecutor;
use Illuminate\Console\Command;

class ProcessRecoveryRules extends Command
{

    protected $signature = 'app:process-recovery-rules';


    protected $description = 'Process enabled abandoned-order recovery stages.';


    public function handle(RecoveryRuleExecutor $executor): int
    {
        $summary = $executor->process();

        $this->info(sprintf(
            'Processed %d rules. Eligible: %d, sent: %d, skipped: %d, failed: %d.',
            $summary['rules'],
            $summary['eligible'],
            $summary['sent'],
            $summary['skipped'],
            $summary['failed'],
        ));

        return self::SUCCESS;
    }
}
