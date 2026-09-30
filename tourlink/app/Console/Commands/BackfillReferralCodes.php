<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Referral\ReferralService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

class BackfillReferralCodes extends Command
{
    protected $signature = 'referrals:backfill-codes {--dry-run : Report what would change without writing}';

    protected $description = 'Give every existing user a unique referral code';

    public function handle(ReferralService $referrals): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $missing = User::query()->whereNull('referral_code')->orWhere('referral_code', '')->count();

        $this->line('Users without a referral code: '.$missing);

        if ($missing === 0) {
            $this->info('Every user already has a referral code.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($missing);
        $bar->start();
        $assigned = 0;
        $failed = null;

        User::query()
            ->whereNull('referral_code')
            ->orWhere('referral_code', '')
            ->chunkById(100, function ($users) use ($referrals, $dryRun, $bar, &$assigned, &$failed): void {
                foreach ($users as $user) {
                    if ($failed !== null) {
                        return;
                    }

                    if (! $dryRun) {
                        try {
                            $referrals->ensureCodeFor($user);
                        } catch (QueryException $exception) {
                            // A concurrent writer may have won the unique index
                            // race; re-check before giving up on this user.
                            if (! User::query()->whereKey($user->id)->whereNotNull('referral_code')->exists()) {
                                $failed = 'Failed to assign a code to user '.$user->id.': '.$exception->getMessage();

                                return;
                            }
                        }
                    }

                    $assigned++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        if ($failed !== null) {
            $this->error($failed);

            return self::FAILURE;
        }

        $this->info(($dryRun ? 'Would assign' : 'Assigned').' '.$assigned.' referral code(s).');

        return self::SUCCESS;
    }
}
