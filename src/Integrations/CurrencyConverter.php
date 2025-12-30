<?php
/**
 * CurrencyConverter plugin integration
 *
 * @package    Zimrate
 * @subpackage Zimrate/Integrations
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */

namespace RichardMuvirimi\Zimrate\Integrations;

use RichardMuvirimi\Zimrate\Helpers\Functions;
use RichardMuvirimi\Zimrate\Zimrate;

/**
 * CurrencyConverter integration class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class CurrencyConverter extends BasePluginIntegration
{
    /**
     * {@inheritdoc}
     */
    public function get_plugin_slug(): string
    {
        return 'currencyconverter/plugin.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_plugin_name_fallback(): string
    {
        return 'CurrencyConverter';
    }

    /**
     * {@inheritdoc}
     */
    public function get_tested_version(): string
    {
        return '0.5.5';
    }

    /**
     * {@inheritdoc}
     */
    public function get_supported_from(): string
    {
        return '0.5.3';
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void
    {
        Zimrate::instance()->add_filter('http_response', $this, 'inject_http_response', 10, 3);
    }

    /**
     * Inject Zimrate currency into CurrencyConverter API response
     *
     * @since 1.0.0
     * @param array  $response    HTTP response.
     * @param array  $parsed_args HTTP request arguments.
     * @param string $url         The request URL.
     * @return array The response
     */
    public function inject_http_response($response, $parsed_args, $url)
    {
        $host = Functions::url_host($url);
        
        if ($host === 'api.exchangerate.guru') {
            $rates = json_decode(wp_remote_retrieve_body($response), true);

            $rates = array_map(function($data) {
                $data['rates'][Functions::get_iso()] = Functions::apply_cushion(Functions::get_rate());
                return $data;
            }, $rates);

            $response['body'] = json_encode($rates);
        }

        return $response;
    }
}
