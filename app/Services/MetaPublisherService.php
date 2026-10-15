<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MetaPublisherService
{
    public function publishPost(SocialPost $post): SocialPost
    {
        if ($post->status === SocialPost::STATUS_PUBLISHED) {
            return $post;
        }

        $platforms = $post->platforms;
        if (! is_array($platforms) || $platforms === []) {
            $platforms = [SocialAccount::FACEBOOK, SocialAccount::INSTAGRAM];
        }

        $result = $post->meta_response ?? [];
        $errors = [];

        if (in_array(SocialAccount::FACEBOOK, $platforms, true) && empty($result['facebook']['id'])) {
            try {
                $result['facebook'] = $this->publishFacebook($post);
            } catch (Throwable $e) {
                Log::error('Facebook publishing failed.', [
                    'post_id' => $post->id,
                    'message' => $e->getMessage(),
                ]);
                $errors[] = $e->getMessage();
            }
        }

        if (in_array(SocialAccount::INSTAGRAM, $platforms, true) && empty($result['instagram']['id'])) {
            try {
                $result['instagram'] = $this->publishInstagram($post);
            } catch (Throwable $e) {
                Log::error('Instagram publishing failed.', [
                    'post_id' => $post->id,
                    'message' => $e->getMessage(),
                ]);
                $errors[] = $e->getMessage();
            }
        }

        $post->meta_response = $result;

        if ($errors !== []) {
            $published = array_values(array_filter([
                ! empty($result['facebook']['id']) ? 'Facebook' : null,
                ! empty($result['instagram']['id']) ? 'Instagram' : null,
            ]));
            $prefix = $published !== [] ? implode(' and ', $published).' was published. ' : '';
            $post->status = SocialPost::STATUS_FAILED;
            $post->error_message = $prefix.implode(' ', $errors);
        } else {
            $post->status = SocialPost::STATUS_PUBLISHED;
            $post->published_at = now();
            $post->error_message = null;
        }

        $post->save();

        return $post->fresh();
    }

    public function resolveAccount(string $provider): array
    {
        if ($provider === SocialAccount::FACEBOOK) {
            $context = $this->facebookContext(true);

            return [
                'id' => $context['page_id'],
                'name' => $context['name'],
                'token' => $context['token'],
            ];
        }

        $context = $this->instagramContext(true);

        return [
            'id' => $context['ig_id'],
            'name' => $context['name'],
            'token' => $context['token'],
        ];
    }

    private function publishFacebook(SocialPost $post): array
    {
        $context = $this->facebookContext();
        $caption = $this->caption((string) $post->fb_content, $post->hashtags ?? []);
        $absolute = $this->localImage($post);

        if ($absolute) {
            $binary = file_get_contents($absolute);

            if ($binary === false) {
                throw new RuntimeException('The uploaded image could not be read.');
            }

            $response = Http::timeout(45)
                ->attach('source', $binary, basename($absolute))
                ->post($this->endpoint($context['page_id'].'/photos'), [
                    'caption' => $caption,
                    'published' => 'true',
                    'access_token' => $context['token'],
                ]);

            return $this->graphBody($response, $context['page_id'].'/photos');
        }

        $payload = [
            'message' => $caption,
            'access_token' => $context['token'],
        ];

        if (is_string($post->source_url) && preg_match('#^https?://#i', $post->source_url)) {
            $payload['link'] = $post->source_url;
        }

        $response = Http::timeout(30)
            ->asForm()
            ->post($this->endpoint($context['page_id'].'/feed'), $payload);

        return $this->graphBody($response, $context['page_id'].'/feed');
    }

    private function publishInstagram(SocialPost $post): array
    {
        $context = $this->instagramContext();
        $imageUrl = $this->instagramImageUrl($post);

        $container = $this->graph('POST', $context['ig_id'].'/media', [
            'image_url' => $imageUrl,
            'caption' => $this->caption((string) $post->ig_content, $post->hashtags ?? []),
        ], $context['token']);

        $creationId = (string) ($container['id'] ?? '');

        if ($creationId === '') {
            throw new RuntimeException('Instagram did not return a media container.');
        }

        $this->waitForContainer($creationId, $context['token']);

        $published = $this->graph('POST', $context['ig_id'].'/media_publish', [
            'creation_id' => $creationId,
        ], $context['token']);

        return [
            'id' => (string) ($published['id'] ?? ''),
            'creation_id' => $creationId,
        ];
    }

    private function waitForContainer(string $creationId, string $token): void
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $status = $this->graph('GET', $creationId, [
                'fields' => 'status_code',
            ], $token);

            $code = (string) ($status['status_code'] ?? '');

            if ($code === 'FINISHED' || $code === 'PUBLISHED') {
                return;
            }

            if ($code === 'ERROR' || $code === 'EXPIRED') {
                throw new RuntimeException('Instagram could not process the image.');
            }

            sleep(2);
        }
    }

    private function facebookContext(bool $refresh = false): array
    {
        $userToken = $this->token();
        $wanted = (string) config('social.facebook.page_id');
        $stored = SocialAccount::query()->where('provider', SocialAccount::FACEBOOK)->first();

        if (! $refresh && $stored?->access_token && $stored->account_id && ($wanted === '' || $stored->account_id === $wanted)) {
            return [
                'page_id' => $stored->account_id,
                'token' => $stored->access_token,
                'name' => $stored->account_name ?: 'Facebook Page',
            ];
        }

        try {
            $accounts = $this->graph('GET', 'me/accounts', [
                'fields' => 'id,name,access_token',
            ], $userToken);
        } catch (RuntimeException) {
            $accounts = ['data' => []];
        }

        foreach ($accounts['data'] ?? [] as $account) {
            $id = (string) ($account['id'] ?? '');

            if ($id === '' || ($wanted !== '' && $id !== $wanted)) {
                continue;
            }

            return [
                'page_id' => $id,
                'token' => (string) ($account['access_token'] ?? $userToken),
                'name' => (string) ($account['name'] ?? 'Facebook Page'),
            ];
        }

        $me = $this->graph('GET', 'me', [
            'fields' => 'id,name',
        ], $userToken);
        $pageId = (string) ($me['id'] ?? '');

        if ($pageId === '' || ($wanted !== '' && $pageId !== $wanted)) {
            throw new RuntimeException('No Facebook Page was found for this access token. Set FACEBOOK_PAGE_ID or connect the page.');
        }

        return [
            'page_id' => $pageId,
            'token' => $userToken,
            'name' => (string) ($me['name'] ?? 'Facebook Page'),
        ];
    }

    private function instagramContext(bool $refresh = false): array
    {
        $page = $this->facebookContext($refresh);
        $wanted = (string) config('social.instagram.business_account_id');
        $stored = SocialAccount::query()->where('provider', SocialAccount::INSTAGRAM)->first();

        if (! $refresh && $stored?->account_id && ($wanted === '' || $stored->account_id === $wanted)) {
            return [
                'ig_id' => $stored->account_id,
                'token' => $page['token'],
                'name' => $stored->account_name ?: 'Instagram',
            ];
        }

        $igId = $wanted;

        if ($igId === '') {
            $pageInfo = $this->graph('GET', $page['page_id'], [
                'fields' => 'instagram_business_account',
            ], $page['token']);
            $igId = (string) ($pageInfo['instagram_business_account']['id'] ?? '');
        }

        if ($igId === '') {
            throw new RuntimeException('No Instagram business account is linked to this Facebook Page. Set INSTAGRAM_BUSINESS_ACCOUNT_ID.');
        }

        $profile = $this->graph('GET', $igId, [
            'fields' => 'id,username,name',
        ], $page['token']);

        $name = (string) ($profile['username'] ?? $profile['name'] ?? 'Instagram');

        return [
            'ig_id' => $igId,
            'token' => $page['token'],
            'name' => $name,
        ];
    }

    private function graph(string $method, string $path, array $data, string $token): array
    {
        try {
            $request = Http::timeout(30)->acceptJson();
            $payload = array_merge($data, ['access_token' => $token]);
            $response = strtoupper($method) === 'GET'
                ? $request->get($this->endpoint($path), $payload)
                : $request->asForm()->post($this->endpoint($path), $payload);
        } catch (Throwable $e) {
            Log::error('Meta request failed.', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('The social network could not be reached.');
        }

        return $this->graphBody($response, $path);
    }

    private function graphBody(mixed $response, string $path): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            $body = [];
        }

        if ($response->failed() || isset($body['error'])) {
            $message = (string) ($body['error']['message'] ?? 'Meta rejected the request.');
            Log::error('Meta API error.', [
                'path' => $path,
                'status' => $response->status(),
                'message' => $message,
            ]);

            throw new RuntimeException($message);
        }

        return $body;
    }

    private function endpoint(string $path): string
    {
        $version = trim((string) config('social.graph_version'), '/');

        return 'https://graph.facebook.com/'.$version.'/'.ltrim($path, '/');
    }

    private function token(): string
    {
        $token = (string) config('social.access_token');

        if ($token === '') {
            throw new RuntimeException('SOCIAL_MEDIA_ACCESS_TOKEN is not configured.');
        }

        return $token;
    }

    private function caption(string $body, array $tags): string
    {
        $formatted = [];

        foreach ($tags as $tag) {
            $tag = trim(ltrim((string) $tag, '#'));

            if ($tag !== '') {
                $formatted[] = '#'.$tag;
            }
        }

        if ($formatted === []) {
            return trim($body);
        }

        return trim($body."\n\n".implode(' ', $formatted));
    }

    private function localImage(SocialPost $post): ?string
    {
        if (! $post->image_path || ! Storage::disk('public')->exists($post->image_path)) {
            return null;
        }

        return Storage::disk('public')->path($post->image_path);
    }

    private function instagramImageUrl(SocialPost $post): string
    {
        $local = $this->publicImageUrl($post);

        if ($local !== null && $this->isPublicHttps($local)) {
            return $local;
        }

        $absolute = $this->localImage($post);

        if ($absolute === null) {
            throw new RuntimeException('Instagram publishing requires an image.');
        }

        $binary = file_get_contents($absolute);

        if ($binary === false) {
            throw new RuntimeException('The image could not be read for publishing.');
        }

        try {
            $response = Http::timeout(60)
                ->attach('file', $binary, basename($absolute))
                ->post('https://0x0.st');
        } catch (Throwable $e) {
            Log::error('Temporary image upload failed.', [
                'post_id' => $post->id,
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException('Instagram needs a public HTTPS image URL, and the temporary upload failed.');
        }

        $url = trim($response->body());

        if (! $response->successful() || ! str_starts_with($url, 'https://')) {
            throw new RuntimeException('Instagram needs a public HTTPS image URL. Set APP_URL to a public domain before publishing.');
        }

        return $url;
    }

    private function isPublicHttps(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme === 'https' && $host !== '' && ! $this->isLocalHost($host);
    }

    private function publicImageUrl(SocialPost $post): ?string
    {
        if (! $post->image_path || ! Storage::disk('public')->exists($post->image_path)) {
            return null;
        }

        return Storage::disk('public')->url($post->image_path);
    }

    private function isLocalHost(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.local') || str_ends_with($host, '.test');
    }
}
