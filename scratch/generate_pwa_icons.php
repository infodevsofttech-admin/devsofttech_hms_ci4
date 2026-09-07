<?php
$srcPath = __DIR__ . '/../public/assets/img/logo.png';
if (!file_exists($srcPath)) {
    echo "Logo not found\n";
    exit;
}

$src = imagecreatefrompng($srcPath);
$srcW = imagesx($src);
$srcH = imagesy($src);

$sizes = [
    512 => __DIR__ . '/../public/assets/img/pwa-icon-512.png',
    192 => __DIR__ . '/../public/assets/img/pwa-icon-192.png'
];

foreach ($sizes as $size => $outPath) {
    $img = imagecreatetruecolor($size, $size);
    // Dark navy blue background matching header
    $bg = imagecolorallocate($img, 15, 23, 42); 
    imagefill($img, 0, 0, $bg);

    $scale = min(($size * 0.85) / $srcW, ($size * 0.85) / $srcH);
    $newW = (int) ($srcW * $scale);
    $newH = (int) ($srcH * $scale);
    $dstX = (int) (($size - $newW) / 2);
    $dstY = (int) (($size - $newH) / 2);

    imagecopyresampled($img, $src, $dstX, $dstY, 0, 0, $newW, $newH, $srcW, $srcH);
    imagepng($img, $outPath);
    imagedestroy($img);
    echo "Generated $size x $size icon at: $outPath\n";
}
