<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class Captcha extends BaseController
{
    /**
     * Generate an image CAPTCHA, save the code to session, and stream image.
     */
    public function generate(): ResponseInterface
    {
        // 1. Generate random code (unambiguous uppercase characters)
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        $length = 5;
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, $max)];
        }

        // 2. Save code in session
        session()->set('login_captcha', $code);

        // 3. Create GD image
        $width = 140;
        $height = 40;
        $image = imagecreatetruecolor($width, $height);

        // Background color (#f8fafc)
        $bgColor = imagecolorallocate($image, 248, 250, 252);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        // Add subtle background noise dots
        for ($i = 0; $i < 60; $i++) {
            $dotColor = imagecolorallocate($image, random_int(190, 230), random_int(190, 230), random_int(200, 240));
            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $dotColor);
        }

        // Add 3 subtle noise lines
        for ($i = 0; $i < 3; $i++) {
            $lineColor = imagecolorallocate($image, random_int(180, 220), random_int(180, 220), random_int(190, 230));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
        }

        // Color palette for characters
        $colors = [
            imagecolorallocate($image, 15, 23, 42),   // slate-900
            imagecolorallocate($image, 30, 58, 138),  // blue-900
            imagecolorallocate($image, 88, 28, 135),  // purple-900
            imagecolorallocate($image, 19, 78, 74),   // teal-900
            imagecolorallocate($image, 136, 19, 55),  // rose-900
        ];

        // Draw each character with spacing and slight vertical jitter
        $spacing = (int) (($width - 24) / $length);
        for ($i = 0; $i < $length; $i++) {
            $char = $code[$i];
            $charColor = $colors[$i % count($colors)];
            $x = 12 + ($i * $spacing);
            $y = random_int(10, 14);

            imagestring($image, 5, $x, $y, $char, $charColor);
            // Draw 1px horizontal offset for bold readability
            imagestring($image, 5, $x + 1, $y, $char, $charColor);
        }

        // Add a thin border
        $borderColor = imagecolorallocate($image, 203, 213, 225);
        imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

        // Capture PNG stream
        ob_start();
        imagepng($image);
        $imageData = (string) ob_get_clean();
        imagedestroy($image);

        return $this->response
            ->setContentType('image/png')
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody($imageData);
    }
}
