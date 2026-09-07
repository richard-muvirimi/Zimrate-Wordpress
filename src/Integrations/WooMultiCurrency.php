<?php
/**
 * WooCommerce Multi Currency integration
 *
 * @package    Zimrate
 * @subpackage Zimrate/Integrations
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */

namespace RichardMuvirimi\Zimrate\Integrations;

use ReflectionProperty;
use RichardMuvirimi\Zimrate\Helpers\Functions;
use RichardMuvirimi\Zimrate\Zimrate;

/**
 * WooCommerce Multi Currency integration class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class WooMultiCurrency extends BasePluginIntegration
{
    /**
     * {@inheritdoc}
     */
    public function get_plugin_slug(): string
    {
        return 'woo-multi-currency/woo-multi-currency.php';
    }

    /**
     * {@inheritdoc}
     */
    public function get_required_symbols(): array
    {
        return [
            'wmc_get_exchange',
            'wmc_get_currency_code',
            'WOOMULTI_CURRENCY_F_Data',
            'api.villatheme.com',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_plugin_name_fallback(): string
    {
        return 'Multi Currency for WooCommerce';
    }

    /**
     * {@inheritdoc}
     */
    public function get_tested_version(): string
    {
        return '2.2.9';
    }

    /**
     * {@inheritdoc}
     */
    public function get_supported_from(): string
    {
        return '2.1.5.6';
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void
    {
        Zimrate::instance()->add_filter('http_response', $this, 'inject_http_response', 10, 3);
        Zimrate::instance()->add_filter('wmc_get_exchange', $this, 'get_exchange_rates', 10, 1);
        Zimrate::instance()->add_filter('wmc_get_currency_code', $this, 'get_currency_code', 10, 3);
    }

    /**
     * Inject Zimrate currency into Woo Multi Currency API response
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
        
        if ($host === 'api.villatheme.com') {
            $params = $parsed_args['body'];

            if (count(array_intersect(
                [$params['from'], $params['to']],
                array_keys(Functions::supported_currencies())
            )) > 0) {
                $response['body'] = json_encode([
                    $params['to'] => $this->convert_currency(
                        $params['from'],
                        $params['to'],
                        $parsed_args,
                        $url
                    ),
                ]);
            }
        }

        return $response;
    }

    /**
     * Get exchange rates for Woo Multi Currency
     *
     * Note: We cannot use this filter to inject our rates because the filter
     * does not provide the from/to currency parameters. The rates array only
     * contains the result after the API call, so we cannot determine which
     * currencies were requested to inject our rates appropriately.
     *
     * @since 1.1.4
     * @param array $rates The exchange rates returned from the API
     * @return array The exchange rates (unchanged)
     */
    public function get_exchange_rates($rates): array
    {
        // Cannot inject rates here - no currency context provided by filter
        return $rates;
    }

    /**
     * Get currency code for country
     *
     * @since 1.1.4
     * @param string $currency_code The currency code for the country
     * @param array  $mappings      All country to currency mappings
     * @param string $country_code  The country code
     * @return string
     */
    public function get_currency_code($currency_code, $mappings, $country_code): string
    {
        if ($country_code === 'ZW') {
            return Functions::default_currency();
        }
        
        return $currency_code;
    }

    /**
     * {@inheritdoc}
     */
    protected function get_usd_rate(string $currency, ...$args): float
    {
        [$parsed_args, $url] = $args + [0 => [], 1 => ''];

        $parsed_args['body']['from'] = $currency;
        $parsed_args['body']['to'] = 'USD';

        $response = wp_remote_post($url, $parsed_args);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        return floatval($body['USD'] ?? 1.0);
    }

    /**
     * {@inheritdoc}
     */
    public function on_plugins_loaded(): void
    {
        if ($this->is_active() && class_exists('WOOMULTI_CURRENCY_F_Data', false)) {
            if (!class_exists('RichardMuvirimi\Zimrate\Integrations\WooMultiCurrencyData', false)) {
                return;
            }
            
            $reflection = new ReflectionProperty('WOOMULTI_CURRENCY_F_Data', 'instance');
            $reflection->setAccessible(true);
            $reflection->setValue(null, new WooMultiCurrencyData());
            $reflection->setAccessible(false);
        }
    }
}

/**
 * WooCommerce Multi Currency Data override
 * 
 * This class overrides the base class to provide up to date data
 * Only defined if the parent class exists (when the plugin is active)
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
if (class_exists('WOOMULTI_CURRENCY_F_Data')) {
    class WooMultiCurrencyData extends \WOOMULTI_CURRENCY_F_Data
    {
        /**
         * Get country code by currency
         *
         * @param string $currency_code
         * @return string
         *
         * @author Richard Muvirimi <richard@tyganeutronics.com>
         * @since 1.0.0
         */
        public function get_country_data($currency_code)
        {
            return parent::get_country_data($currency_code);
        }

        /**
         * Get country freebase identifier
         *
         * @param string $country_code
         * @return string
         *
         * @author Richard Muvirimi <richard@tyganeutronics.com>
         * @since 1.0.0
         */
        public function get_country_freebase($country_code)
        {
            // /m/02c1rx is Zimbabwe, the parent has no mapping for it
            if ($country_code === 'ZW') {
                $data = '/m/02c1rx';
            } else {
                $data = parent::get_country_freebase($country_code);
            }

            return $data;
        }
    }
}
