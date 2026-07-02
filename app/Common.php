<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('mpdf_normalize_font_weight_css')) {
	/**
	 * mPDF supports font-weight as `bold|normal` reliably.
	 * Convert numeric CSS weights (100..900) to mPDF-safe values.
	 */
	function mpdf_normalize_font_weight_css(string $html): string
	{
		if ($html === '') {
			return '';
		}

		return (string) preg_replace_callback(
			'/(font-weight\s*:\s*)([1-9]00)(\s*;?)/i',
			static function (array $matches): string {
				$weight = (int) ($matches[2] ?? 400);
				$normalized = $weight >= 500 ? 'bold' : 'normal';

				return (string) ($matches[1] ?? 'font-weight:') . $normalized . (string) ($matches[3] ?? ';');
			},
			$html
		);
	}
}
