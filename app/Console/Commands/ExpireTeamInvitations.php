<?php

namespace App\Console\Commands;

use App\Models\TeamInvitation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Expire Team Invitations Command
 *
 * Marks pending team invitations as expired when they pass their expiry date.
 * Can optionally clean up old invitation records to keep the database lean.
 *
 * Usage:
 *   php artisan teams:expire-invitations              # Expire invitations
 *   php artisan teams:expire-invitations --dry-run    # Preview what would expire
 *   php artisan teams:expire-invitations --cleanup    # Also delete old records
 *
 * @package App\Console\Commands
 * @since Phase 6 - Team Invitation System
 */
class ExpireTeamInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:expire-invitations
                            {--dry-run : Show what would be expired without making changes}
                            {--cleanup : Also delete old non-pending invitations (90+ days)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark expired team invitations as expired and optionally clean up old records';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $shouldCleanup = $this->option('cleanup');

        $this->info('Team Invitation Expiration Command');
        $this->line('==================================');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->line('');
        }

        // Step 1: Expire pending invitations
        $expiredCount = $this->expirePendingInvitations($isDryRun);

        // Step 2: Optional cleanup of old records
        if ($shouldCleanup) {
            $this->line('');
            $cleanedCount = $this->cleanupOldInvitations($isDryRun);
        }

        $this->line('');
        $this->info('Command completed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Find and expire pending invitations that have passed their expiry date.
     *
     * @param bool $isDryRun
     * @return int Number of expired invitations
     */
    protected function expirePendingInvitations(bool $isDryRun): int
    {
        // Find all pending invitations that have passed their expiry date
        $expiredQuery = TeamInvitation::where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());

        $count = $expiredQuery->count();

        if ($count === 0) {
            $this->info('No expired invitations found.');
            return 0;
        }

        if ($isDryRun) {
            $this->info("Would expire {$count} invitation(s):");
            $this->line('');

            $expiredQuery->with(['team', 'invitee'])->each(function ($invitation) {
                $teamName = $invitation->team->name ?? 'Unknown Team';
                $inviteeName = $invitation->invitee->display_name ?? $invitation->invitee->username ?? 'Unknown User';
                $expiredAgo = $invitation->expires_at->diffForHumans();

                $this->line("  - #{$invitation->id}: {$inviteeName} to {$teamName} (expired {$expiredAgo})");
            });

            return $count;
        }

        // Update all expired invitations
        $updated = $expiredQuery->update(['status' => 'expired']);

        $this->info("Marked {$updated} invitation(s) as expired.");

        Log::info('ExpireTeamInvitations: Expired pending invitations', [
            'expired_count' => $updated,
            'executed_at' => now()->toDateTimeString(),
        ]);

        return $updated;
    }

    /**
     * Clean up old invitation records that are no longer needed.
     * Deletes non-pending invitations older than 90 days.
     *
     * @param bool $isDryRun
     * @param int $daysOld Days threshold for deletion (default: 90)
     * @return int Number of deleted records
     */
    protected function cleanupOldInvitations(bool $isDryRun, int $daysOld = 90): int
    {
        $this->info("Cleaning up old invitation records (older than {$daysOld} days)...");

        $oldRecordsQuery = TeamInvitation::whereIn('status', ['accepted', 'declined', 'cancelled', 'expired'])
            ->where('updated_at', '<', now()->subDays($daysOld));

        $count = $oldRecordsQuery->count();

        if ($count === 0) {
            $this->info('No old records to clean up.');
            return 0;
        }

        if ($isDryRun) {
            $this->info("Would delete {$count} old invitation record(s):");

            $breakdown = TeamInvitation::whereIn('status', ['accepted', 'declined', 'cancelled', 'expired'])
                ->where('updated_at', '<', now()->subDays($daysOld))
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            foreach ($breakdown as $status => $statusCount) {
                $this->line("  - {$status}: {$statusCount}");
            }

            return $count;
        }

        // Delete old records
        $deleted = $oldRecordsQuery->delete();

        $this->info("Deleted {$deleted} old invitation record(s).");

        Log::info('ExpireTeamInvitations: Cleaned up old invitation records', [
            'deleted_count' => $deleted,
            'older_than_days' => $daysOld,
            'executed_at' => now()->toDateTimeString(),
        ]);

        return $deleted;
    }
}
