<?php

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

app('blade.compiler')->compileString(file_get_contents(__DIR__.'/../../resources/views/cms/blog/form.blade.php'));
echo "blade-ok\n";

$service = app(App\Services\BlogAiService::class);
Illuminate\Support\Facades\Storage::fake('public');

if ($service->usableGeneratedPath('../.env') !== null || $service->usableGeneratedPath('blog/../.env') !== null) {
    fwrite(STDERR, "traversal accepted\n");
    exit(1);
}

if ($service->usableGeneratedPath('blog/ai/missing.jpg') !== null) {
    fwrite(STDERR, "missing accepted\n");
    exit(1);
}

Illuminate\Support\Facades\Storage::disk('public')->put('blog/ai/abc-def.jpg', 'x');

if ($service->usableGeneratedPath('blog/ai/abc-def.jpg') !== 'blog/ai/abc-def.jpg') {
    fwrite(STDERR, "valid path rejected\n");
    exit(1);
}

$manager = new Intervention\Image\ImageManager(new Intervention\Image\Drivers\Gd\Driver());
$jpeg = (string) $manager->create(64, 64)->fill('ff0000')->cover(1920, 863)->toJpeg(85);
$image = $manager->read($jpeg);

if ($image->width() !== 1920 || $image->height() !== 863) {
    fwrite(STDERR, "unexpected size {$image->width()}x{$image->height()}\n");
    exit(1);
}

echo "checks-ok\n";
