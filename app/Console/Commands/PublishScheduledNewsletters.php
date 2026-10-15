<?php

namespace App\Console\Commands;

use App\Services\NewsletterService;
use Illuminate\Console\Command;

class PublishScheduledNewsletters extends Command
{
    protected $signature = 'newsletter:publish-due';

    protected $description = 'Publish newsletters whose scheduled time has arrived';

    public function handle(NewsletterService $newsletters): int
    {
        $count = $newsletters->publishDue();
        $this->info('Published '.$count.' newsletters.');

        return self::SUCCESS;
    }
}
