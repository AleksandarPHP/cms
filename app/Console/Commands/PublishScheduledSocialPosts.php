<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Services\MetaPublisherService;
use Illuminate\Console\Command;

class PublishScheduledSocialPosts extends Command
{
    protected $signature = 'social:publish-due';

    protected $description = 'Publish social posts whose scheduled time has arrived';

    public function handle(MetaPublisherService $publisher): int
    {
        SocialPost::query()
            ->due()
            ->orderBy('id')
            ->each(function (SocialPost $post) use ($publisher) {
                $claimed = SocialPost::query()
                    ->whereKey($post->id)
                    ->where('status', SocialPost::STATUS_SCHEDULED)
                    ->update(['status' => SocialPost::STATUS_PROCESSING]);

                if ($claimed === 0) {
                    return;
                }

                $publisher->publishPost($post->fresh());
            });

        return self::SUCCESS;
    }
}
