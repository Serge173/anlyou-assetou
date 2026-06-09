<?php

declare(strict_types=1);

$labels = ['Premiere rencontre', 'Fiancailles', 'Venise', 'Anniversaire', 'Ensemble', 'Portrait'];
$bgs = ['#3d3d3d', '#4a4a4a', '#353535', '#404040', '#383838', '#454545'];
$accents = ['#d4a5a5', '#c9a962', '#e8d5a3', '#d4a5a5', '#c9a962', '#e8d5a3'];

$dir = __DIR__ . '/../public/assets/images/gallery';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

for ($i = 1; $i <= 6; $i++) {
    $label = $labels[$i - 1];
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600">
  <rect fill="{$bgs[$i-1]}" width="800" height="600"/>
  <text x="400" y="280" text-anchor="middle" fill="{$accents[$i-1]}" font-family="Georgia,serif" font-size="48">&#9829;</text>
  <text x="400" y="340" text-anchor="middle" fill="{$accents[$i-1]}" font-family="Georgia,serif" font-size="22">{$label}</text>
</svg>
SVG;
    file_put_contents("{$dir}/story-{$i}.svg", $svg);
}

echo "Gallery images created.\n";
