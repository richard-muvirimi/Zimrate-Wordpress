<?php
/**
 * WC Multi Currency integration
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
 * WC Multi Currency integration class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class WcMultiCurrency extends BasePluginIntegration
{
    /**
     * {@inheritdoc}
     */
    public function get_plugin_slug(): string
    {
        return 'wc-multi-currency/wcmilticurrency.php';
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
        return '1.5.7';
    }

    /**
     * {@inheritdoc}
     */
    public function get_supported_from(): string
    {
        return '1.5';
    }

    /**
     * {@inheritdoc}
     */
    public function register_hooks(): void
    {
        Zimrate::instance()->add_filter('http_response', $this, 'inject_http_response', 10, 3);
    }

    /**
     * Inject Zimrate currency into WC Multi Currency API response
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
        
        switch ($host) {
            case 'www.alphavantage.co':
                $params = Functions::url_params($url);

                if (count(array_intersect([$params['from_currency'], $params['to_currency']], Functions::get_isos())) > 0) {
                    $rate = $this->convert_currency(
                        $params['from_currency'],
                        $params['to_currency'],
                        $parsed_args,
                        $url
                    );

                    $rates = json_decode(wp_remote_retrieve_body($response), true);

					$rates['Realtime Currency Exchange Rate']['5. Exchange Rate'] = $rate;
					$response['body'] = json_encode($rates);
                }
                break;

            case 'api.exchangerate-api.com':
                $rates = json_decode(wp_remote_retrieve_body($response), true);

				$currency = get_option('zimrate-currencies', 'RBZ');
					
				$rates['rates'][Functions::get_iso()] = Functions::apply_cushion(Functions::get_rate($currency));
				
				$response['body'] = json_encode($rates);
                break;
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function get_usd_rate(string $currency, ...$args): float
    {
        [$parsed_args, $url] = $args + [0 => [], 1 => ''];

        $params = Functions::url_params($url);
        $params['from_currency'] = $currency;
        $params['to_currency'] = 'USD';

        $url = add_query_arg($params, $url);

		sleep(1); // To respect API rate limits

        $response = wp_remote_get($url, $parsed_args);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        return floatval($body['Realtime Currency Exchange Rate']['5. Exchange Rate'] ?? 1.0);
    }
}
