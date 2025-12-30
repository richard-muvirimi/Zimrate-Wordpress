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

use RichardMuvirimi\Zimrate\Helpers\Functions;
use RichardMuvirimi\Zimrate\Helpers\Template;

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
                'currency' => Functions::get_selected_currency(),
                'value' => 1,
                'precision' => 2,
                'format' => 'no',
                'cushion' => 'yes',
            ),
            $attr,
            Functions::get_shortcode()
        );

        $rate = Functions::get_rate($attributes['currency']) * floatval($attributes['value']);

        if ($attributes['cushion'] == 'yes') {
            $rate = Functions::apply_cushion($rate);
        }

        if ($attributes['format'] == 'yes') {
            $rate = number_format_i18n($rate, intval($attributes['precision']));
        } else {
            $rate = round($rate, intval($attributes['precision']));
        }

        return (string) $rate;
    }

    /**
     * Enqueue public styles
     *
     * @return void
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public function enqueue_styles(): void
    {
        wp_enqueue_style(
            Functions::get_plugin_slug() . '-public',
            Template::get_style_url('zimrate-public.css'),
            array(),
            Functions::get_plugin_version(),
            'all'
        );
    }

    /**
     * Enqueue public scripts
     *
     * @return void
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public function enqueue_scripts(): void
    {
        wp_enqueue_script(
            Functions::get_plugin_slug() . '-public',
            Template::get_script_url('zimrate-public.js'),
            array('jquery'),
            Functions::get_plugin_version(),
            true
        );
        
        wp_localize_script(
            Functions::get_plugin_slug() . '-public',
            'zimrate',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('zimrate_nonce')
            )
        );
    }
}
