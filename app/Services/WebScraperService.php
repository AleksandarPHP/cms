<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebScraperService
{
    public function extract(string $url): string
    {
        $url = trim($url);

        if ($url === '' || ! $this->isPublicHttpUrl($url)) {
            return '';
        }

        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'CMS-AI Social Scraper',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 3,
                        'strict' => true,
                        'referer' => false,
                        'protocols' => ['http', 'https'],
                    ],
                ])
                ->get($url);

            if (! $response->successful()) {
                Log::warning('Web scraper received a non-success response.', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return '';
            }

            return $this->clean(substr($response->body(), 0, 500000));
        } catch (Throwable $e) {
            Log::error('Web scraper failed.', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }

    private function isPublicHttpUrl(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function clean(string $html): string
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $article = $xpath->query('//article');
        $query = ($article !== false && $article->length > 0)
            ? '//article//p|//article//h1|//article//h2|//article//h3'
            : '//p|//h1|//h2|//h3';

        $nodes = $xpath->query($query);
        $chunks = [];

        if ($nodes !== false) {
            foreach ($nodes as $node) {
                $text = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
                if ($text !== '') {
                    $chunks[] = $text;
                }
            }
        }

        $text = html_entity_decode(implode("\n", $chunks), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace("/\n{2,}/", "\n", $text) ?? '');

        if (mb_strlen($text) > 2000) {
            $text = rtrim(mb_substr($text, 0, 2000));
        }

        return $text;
    }
}
