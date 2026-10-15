<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebSearchService
{
    public function summarize(string $query): string
    {
        $query = trim($query);

        if ($query === '') {
            return '';
        }

        $serperKey = config('social.search.serper_key');
        $tavilyKey = config('social.search.tavily_key');

        try {
            if (is_string($serperKey) && $serperKey !== '') {
                return $this->serper($query, $serperKey);
            }

            if (is_string($tavilyKey) && $tavilyKey !== '') {
                return $this->tavily($query, $tavilyKey);
            }

            Log::warning('Web search was requested without SERPER_API_KEY or TAVILY_API_KEY.');

            return '';
        } catch (Throwable $e) {
            Log::error('Web search failed.', [
                'message' => $e->getMessage(),
            ]);

            return '';
        }
    }

    private function serper(string $query, string $key): string
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'X-API-KEY' => $key,
                'Content-Type' => 'application/json',
            ])
            ->post('https://google.serper.dev/search', [
                'q' => $query,
                'num' => 5,
            ]);

        if (! $response->successful()) {
            Log::error('Serper search failed.', [
                'status' => $response->status(),
            ]);

            return '';
        }

        $lines = [];

        foreach (array_slice($response->json('organic') ?? [], 0, 5) as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $snippet = trim((string) ($item['snippet'] ?? ''));
            $link = trim((string) ($item['link'] ?? ''));

            if ($title === '' && $snippet === '') {
                continue;
            }

            $lines[] = trim($title.($snippet !== '' ? ': '.$snippet : '').($link !== '' ? ' ('.$link.')' : ''));
        }

        return implode("\n", $lines);
    }

    private function tavily(string $query, string $key): string
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->post('https://api.tavily.com/search', [
                'api_key' => $key,
                'query' => $query,
                'max_results' => 5,
                'include_answer' => true,
            ]);

        if (! $response->successful()) {
            Log::error('Tavily search failed.', [
                'status' => $response->status(),
            ]);

            return '';
        }

        $lines = [];
        $answer = trim((string) $response->json('answer'));

        if ($answer !== '') {
            $lines[] = $answer;
        }

        foreach (array_slice($response->json('results') ?? [], 0, 5) as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $snippet = trim((string) ($item['content'] ?? ''));

            if ($title === '' && $snippet === '') {
                continue;
            }

            $lines[] = trim($title.($snippet !== '' ? ': '.$snippet : ''));
        }

        return implode("\n", array_slice($lines, 0, 5));
    }
}
