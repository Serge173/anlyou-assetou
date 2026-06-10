<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$assets = [
    'public/assets/js/main.js',
    'public/assets/js/experience.js',
    'public/assets/js/protect.js',
    'public/assets/js/admin.js',
    'public/assets/css/style.css',
    'public/assets/css/experience.css',
    'public/assets/css/protect.css',
    'public/assets/css/admin.css',
];

foreach ($assets as $relativePath) {
    $source = $root . '/' . $relativePath;
    if (!file_exists($source)) {
        fwrite(STDERR, "Missing: {$relativePath}\n");
        continue;
    }

    $content = file_get_contents($source);
    $extension = pathinfo($source, PATHINFO_EXTENSION);
    $minified = $extension === 'css' ? minifyCss($content) : minifyJs($content);
    $target = preg_replace('/\.' . preg_quote($extension, '/') . '$/', '.min.' . $extension, $source);

    file_put_contents($target, $minified);
    echo 'Built ' . basename($target) . ' (' . strlen($minified) . " bytes)\n";
}

function minifyCss(string $css): string
{
    $css = preg_replace('!/\*.*?\*/!s', '', $css) ?? $css;
    $css = preg_replace('/\s+/', ' ', $css) ?? $css;

    return trim(str_replace([' {', '{ ', ' }', '; ', ' ;', ': '], ['{', '{', '}', ';', ';', ':'], $css));
}

function minifyJs(string $js): string
{
    $js = preg_replace('#/\*[\s\S]*?\*/#', '', $js) ?? $js;
    $lines = preg_split('/\R/', $js) ?: [];
    $output = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            continue;
        }
        if (str_starts_with($trimmed, '//')) {
            continue;
        }
        $output[] = preg_replace('#//.*$#', '', $line) ?? $line;
    }

    $js = implode("\n", $output);
    $js = preg_replace('/\n{2,}/', "\n", $js) ?? $js;
    $js = preg_replace('/[ \t]+/', ' ', $js) ?? $js;
    $js = preg_replace('/\n\s*/', '', $js) ?? $js;

    return trim($js);
}
