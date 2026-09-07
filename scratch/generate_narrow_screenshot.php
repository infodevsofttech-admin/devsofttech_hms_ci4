<?php
$width = 750;
$height = 1334;

$img = imagecreatetruecolor($width, $height);
$bg = imagecolorallocate($img, 15, 23, 42); // Dark Slate Blue
imagefill($img, 0, 0, $bg);

// Draw hospital card / logo inside
$srcPath = __DIR__ . '/../public/assets/img/logo.png';
if (file_exists($srcPath)) {
    $src = imagecreatefrompng($srcPath);
    $srcW = imagesx($src);
    $srcH = imagesy($src);

    $scale = min(($width * 0.7) / $srcW, ($height * 0.4) / $srcH);
    $newW = (int)($srcW * $scale);
    $newH = (int)($srcH * $scale);
    $dstX = (int)(($width - $newW) / 2);
    $dstY = (int)(($height - $newH) / 2);

    imagecopyresampled($img, $src, $dstX, $dstY, 0, 0, $newW, $newH, $srcW, $srcH);
    imagedestroy($src);
}

$outPath = __DIR__ . '/../public/assets/img/screenshot-narrow.png';
imagepng($img, $outPath);
imagedestroy($img);
echo "Created narrow screenshot 750x1334 at $outPath\n";
