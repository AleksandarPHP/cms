<?php

namespace Tests\Unit;

use App\Services\BrandService;
use App\Services\NewsletterTemplateService;
use PHPUnit\Framework\TestCase;

class NewsletterPaletteTest extends TestCase
{
    public function test_orange_and_navy_from_a_logo_stay_in_their_roles(): void
    {
        $palette = $this->service()->paletteFromColors(['#e8872a', '#0d2744', '#ffffff']);

        $this->assertSame('#e8872a', $palette['accent']);
        $this->assertSame('#0d2744', $palette['dark']);
        $this->assertSame('#ffffff', $palette['on_accent']);
        $this->assertSame('#0d2744', $palette['link']);
    }

    public function test_a_single_logo_color_gets_a_darker_band(): void
    {
        $palette = $this->service()->paletteFromColors(['#e8872a']);

        $this->assertSame('#e8872a', $palette['accent']);
        $this->assertNotSame($palette['accent'], $palette['dark']);
        $this->assertSame('#41260c', $palette['dark']);
    }

    public function test_missing_logo_colors_use_a_readable_fallback(): void
    {
        $palette = $this->service()->paletteFromColors([]);

        $this->assertSame('#e8872a', $palette['accent']);
        $this->assertSame('#0d2744', $palette['dark']);
    }

    private function service(): NewsletterTemplateService
    {
        return new NewsletterTemplateService($this->createMock(BrandService::class));
    }
}
