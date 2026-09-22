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

    /**
     * Register the blocks
     *
     * Each block is rendered on the server from the same code as the shortcode
     * and the dashboard widget, the editor bundle only supplies the controls.
     * The bundle is built against the jsx runtime WordPress ships from 6.6, so
     * on anything older the shortcode remains the way in, as it does on a
     * checkout where the bundle has not been built.
     *
     * @since 1.1.6
     * @return void
     */
    public function register_blocks(): void
    {
        if (version_compare(get_bloginfo('version'), '6.6', '<')) {
            return;
        }

        $asset = Template::get_script_path('dist/blocks/index.asset.php');

        if (!file_exists($asset)) {
            return;
        }

        $asset = include $asset;

        wp_register_script(
            Functions::get_plugin_slug('-blocks'),
            Template::get_script_url('dist/blocks/index.js'),
            $asset['dependencies'] ?? array(),
            $asset['version'] ?? Functions::get_plugin_version(),
            true
        );
        wp_set_script_translations(Functions::get_plugin_slug('-blocks'), Functions::get_plugin_slug());

        // the calculator and table share the dashboard widget's assets
        wp_register_style(
            Functions::get_plugin_slug('-calculator'),
            Template::get_style_url('admin-calculator.css'),
            array(),
            Functions::get_plugin_version()
        );
        wp_register_script(
            Functions::get_plugin_slug('-calculator'),
            Template::get_script_url('admin-calculator.js'),
            array(),
            Functions::get_plugin_version(),
            true
        );

        foreach (array('rate', 'calculator', 'rates-table') as $block) {
            register_block_type(
                Template::get_views_path('blocks/' . $block),
                array(
                    'render_callback' => array($this, 'render_' . str_replace('-', '_', $block) . '_block'),
                )
            );
        }
    }

    /**
     * Hand the editor the currencies to offer
     *
     * Kept off init so a front end request never pays for the list.
     *
     * @since 1.1.6
     * @return void
     */
    public function localize_block_editor(): void
    {
        $currencies = array();
        foreach (Functions::supported_currencies() as $code) {
            $currencies[$code] = Functions::currency_label($code);
        }

        wp_localize_script(
            Functions::get_plugin_slug('-blocks'),
            'zimrateBlocks',
            array(
                'currencies' => $currencies,
                'bases' => array('USD' => Functions::currency_label('USD')) + $currencies,
                'defaults' => array(
                    'base' => Functions::default_base(),
                    'currency' => Functions::default_currency(),
                ),
            )
        );
    }

    /**
     * Render the rate block, the shortcode with block wrapper
     *
     * @since 1.1.6
     * @param array $attributes
     * @return string
     */
    public function render_rate_block(array $attributes): string
    {
        $attributes['format'] = !empty($attributes['format']) ? 'yes' : 'no';
        $attributes['cushion'] = !empty($attributes['cushion']) ? 'yes' : 'no';

        return sprintf(
            '<p %s>%s</p>',
            get_block_wrapper_attributes(),
            esc_html($this->currency_shortcode(array_filter($attributes, function ($value) {
                // an unset select falls through to the shortcode's default
                return $value !== '' && $value !== null;
            })))
        );
    }

    /**
     * Render the calculator block
     *
     * @since 1.1.6
     * @param array $attributes
     * @return string
     */
    public function render_calculator_block(array $attributes): string
    {
        return sprintf(
            '<div %s>%s</div>',
            get_block_wrapper_attributes(),
            Template::get_template(
                Functions::get_plugin_slug('-calculator'),
                Functions::calculator(array(
                    'base' => $attributes['base'] ?: Functions::default_base(),
                    'currency' => $attributes['currency'] ?: Functions::default_currency(),
                    'amount' => $attributes['amount'],
                    'precision' => $attributes['precision'],
                    'cushion' => !empty($attributes['cushion']),
                )),
                'calculator.php'
            )
        );
    }

    /**
     * Render the rates table block
     *
     * @since 1.1.6
     * @param array $attributes
     * @return string
     */
    public function render_rates_table_block(array $attributes): string
    {
        return sprintf(
            '<div %s>%s</div>',
            get_block_wrapper_attributes(),
            Template::get_template(
                Functions::get_plugin_slug('-rates-table'),
                Functions::rates_table(
                    $attributes['base'] ?: Functions::default_base(),
                    intval($attributes['precision']),
                    !empty($attributes['cushion']),
                    (array) ($attributes['currencies'] ?? array())
                ),
                'rates-table.php'
            )
        );
    }
}
