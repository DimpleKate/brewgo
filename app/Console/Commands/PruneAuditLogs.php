<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('audit:prune {--days= : Override the configured retention period} {--dry-run : Count old entries without deleting them}')]
#[Description('Delete audit log entries older than the configured retention period.')]
class PruneAuditLogs extends Command
{
    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('ordering.reports.audit_retention_days', 365));
        $days = max(30, $days);

        $cutoff = now()->subDays($days);
        $query = AuditLog::query()->where('created_at', '<', $cutoff);
        $count = $query->count();

        if ($this->option('dry-run')) {
            $this->info('Found '.$count.' audit log entr'.($count === 1 ? 'y' : 'ies').' older than '.$days.' days.');

            return self::SUCCESS;
        }

        $query->delete();

        $this->info('Pruned '.$count.' audit log entr'.($count === 1 ? 'y' : 'ies').' older than '.$days.' days.');

        return self::SUCCESS;
    }
}
