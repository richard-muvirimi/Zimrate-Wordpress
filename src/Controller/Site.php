<?php
/**
 * Site/Public controller
 *
 * @package    Zimrate
 * @subpackage Zimrate/Controller
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */

namespace RichardMuvirimi\Zimrate\Controller;

use RichardMuvirimi\Zimrate\Helpers\Arithmetic;
use RichardMuvirimi\Zimrate\Helpers\Functions;

/**
 * Site controller class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class Site extends BaseController
{
    /**
     * Process rate short code
     *
     * @param array|string $attr
     * @return string
     * @since 1.0.0
     */
    public function currency_shortcode($attr): string
    {
        $attributes = shortcode_atts(
            array(
                'base' => Functions::default_base(),
                'currency' => Functions::default_currency(),
                'value' => 1,
                'precision' => 2,
                'format' => 'no',
                'cushion' => 'yes',
            ),
            $attr,
            Functions::get_shortcode()
        );

        $rate = Arithmetic::mul(
            Functions::get_rate($attributes['currency'], $attributes['base']),
            floatval($attributes['value'])
        );

        if ($attributes['cushion'] == 'yes') {
            $rate = Functions::apply_cushion($rate);
        }

        $rate = Arithmetic::round($rate, intval($attributes['precision']));

        if ($attributes['format'] == 'yes') {
            return number_format_i18n(floatval($rate), intval($attributes['precision']));
        }

        // unformatted output never carried trailing zeroes
        return strpos($rate, '.') === false ? $rate : rtrim(rtrim($rate, '0'), '.');
    }
}
