<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://tyganeutronics.com
 * @since      1.0.0
 *
 * @package    Zimrate
 * @subpackage Zimrate/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Zimrate
 * @subpackage Zimrate/public
 * @author     Richard Muvirimi <tygalive@gmail.com>
 */
class Zimrate_Public
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Process rate short code
     *
     * @param array $attr
     * @return string
     * @since    1.0.0
     */
    public function currency_shortcode($attr)
    {
        $attributes = shortcode_atts(
            [
                'currency' => zimrate_get_selected_currency(),
                'value' => 1,
                'precision' => 2,
                'format' => 'no',
                'cushion' => 'yes',
            ],
            $attr,
            zimrate_get_shortcode()
        );

        $rate =
            zimrate_get_rate($attributes['currency']) * $attributes['value'];

        if ($attributes['cushion'] == 'yes') {
            $rate = zimrate_apply_cushion($rate);
        }

        if ($attributes['format'] == 'yes') {
            $rate = number_format_i18n($rate, $attributes['precision']);
        } else {
            $rate = round($rate, $attributes['precision']);
        }

        return $rate;
    }
}
