<?php

/**
 * The plugins specific functionality of the plugin.
 *
 * @package    Zimrate
 * @subpackage Zimrate/plugins
 *
 * @link       https://tyganeutronics.com
 * @since      1.0.0
 */

/**
 * The plugins functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the plugins stylesheet and JavaScript.
 *
 * @package    Zimrate
 * @subpackage Zimrate/public
 *
 * @author     Richard Muvirimi <tygalive@gmail.com>
 */
class Zimrate_Plugins
{
    /**
     * The ID of this plugin.
     *
     * @access   private
     * @var string $plugin_name The ID of this plugin.
     * @since    1.0.0
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @access   private
     * @var string $version The current version of this plugin.
     * @since    1.0.0
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Filters the HTTP API response immediately before the response is returned.
     *
     * @since    1.0.0
     * @param array  $response    HTTP response.
     * @param array  $parsed_args HTTP request arguments.
     * @param string $url         The request URL.
     * @return array|WP_Error The response or WP_Error on failure.
     */
    public function inject_http_response($response, $parsed_args, $url)
    {
        switch (zimrate_url_host($url)) {
            case 'api.villatheme.com':
                $params = $parsed_args['body'];

                if (
                    in_array($params['from'], zimrate_get_isos()) ||
                    in_array($params['to'], zimrate_get_isos())
                ) {
                    $response['body'] = json_encode([
                        $params['to'] => $this->convert_to_currency(
                            $params['from'],
                            $params['to'],
                            $parsed_args,
                            $url
                        ),
                    ]);
                }

                break;
            case 'www.alphavantage.co':
                $params = zimrate_url_params($url);

                if (
                    in_array($params['from_currency'], zimrate_get_isos()) ||
                    in_array($params['to_currency'], zimrate_get_isos())
                ) {
                    $rate = $this->convert_to_currency(
                        $params['from_currency'],
                        $params['to_currency'],
                        $parsed_args,
                        $url
                    );
                    $rates = json_decode($response['body'], true);

                    $rates['Realtime Currency Exchange Rate']['5. Exchange Rate'] = $rate;

                    $response['body'] = json_encode($rates);
                }

                break;
            case 'api.exchangerate.guru':
                $rates = json_decode($response['body'], true);

                foreach ($rates as $data) {
                    $data['rates'][zimrate_get_iso()] = zimrate_get_rate();
                }

                $response['body'] = json_encode($rates);

                break;
        }

        return $response;
    }

    /**
     * Convert requested currency
     *
     * @since 1.0.0
     * @param string $from
     * @param string $to
     * @param array  $parsed_args HTTP request arguments.
     * @param string $url         The request URL.
     * @return float
     */
    private function convert_to_currency($from, $to, $parsed_args, $url)
    {
        $currency = get_option('zimrate-currencies', 'RBZ');
        $rate = 1;

        if (in_array($from, zimrate_get_isos())) {
            if ($to == 'USD') {
                $rate = pow(zimrate_get_rate($currency), -1);
            } else {
                //first change to usd
                $rate = pow(
                    zimrate_get_rate($currency) *
                        $this->request_rate_to_usd($to, $parsed_args, $url),
                    -1
                );
            }
        } else {
            if ($from == 'USD') {
                //to = iso
                $rate = zimrate_get_rate($currency);
            } else {
                //first change to usd
                $rate =
                    zimrate_get_rate($currency) *
                    $this->request_rate_to_usd($from, $parsed_args, $url);
            }
        }

        return zimrate_apply_cushion($rate);
    }

    /**
     * Remote fetch content
     *
     * @since 1.0.0
     * @param string $base
     * @param array  $parsed_args HTTP request arguments.
     * @param string $url         The request URL.
     * @return array|WP_Error The response or WP_Error on failure.
     */
    private function remote_fetch($base, $parsed_args, $url)
    {
        switch (zimrate_url_host($url)) {
            case 'api.villatheme.com':
                $parsed_args['body']['from'] = $base;
                $parsed_args['body']['to'] = 'USD';

                break;
            case 'www.alphavantage.co':
                $args = zimrate_url_params($url);

                $args['from_currency'] = $base;
                $args['to_currency'] = 'USD';

                $url =
                    $this->get_base_url($url) . '?' . http_build_query($args);

                break;
        }

        if (strtoupper($parsed_args['method']) == 'GET') {
            return wp_remote_get($url, $parsed_args);
        } else {
            return wp_remote_post($url, $parsed_args);
        }
    }

    /**
     * Get url before parameters
     *
     * @since 1.0.0
     * @param string $url
     * @return string
     */
    private function get_base_url($url)
    {
        $url_parts = parse_url($url);
        $constructed_url =
            $url_parts['scheme'] .
            '://' .
            $url_parts['host'] .
            (isset($url_parts['path']) ? $url_parts['path'] : '');

        return $constructed_url;
    }

    /**
     * Convert rate to Usd
     *
     * @since 1.0.0
     * @param string $base
     * @param array  $parsed_args HTTP request arguments.
     * @param string $url         The request URL.
     * @return array
     */
    private function request_rate_to_usd($base, $parsed_args, $url)
    {
        $response = $this->remote_fetch($base, $parsed_args, $url);

        switch (zimrate_url_host($url)) {
            case 'api.villatheme.com':
                $body = json_decode($response['body'], true);

                return $body['USD'];
                break;
            case 'www.alphavantage.co':
                $codes = $response['body'];

                if (!empty($codes)) {
                    $codes = json_decode($codes, true);

                    return $codes['Realtime Currency Exchange Rate']['5. Exchange Rate'] ?:
                        1;
                }

                break;
        }
    }

    /**************************************************************************************************
     * ************************************************************************************************
     *
     * Plugin Filters
     *
     **************************************************************************************************
     **************************************************************************************************
     */

    /**************************************************************************************************
     * woo-multi-currency
     **************************************************************************************************
     */

    /**
     * On plugins loaded
     *
     * @since 1.0.0
     */
    public function plugins_loaded()
    {
        if (zimrate_woo_multi_currency_active()) {
            if (class_exists('WOOMULTI_CURRENCY_F_Data', false)) {
                //change data class instance of this plugin

                include_once plugin_dir_path(__FILE__) .
                    'woo-multi-currency/data.php';

                $reflection = new \ReflectionProperty(
                    'WOOMULTI_CURRENCY_F_Data',
                    'instance'
                );
                $reflection->setAccessible(true);
                $reflection->setValue(
                    null,
                    new Zimrate_Woo_Multi_Currency_Data()
                );
                $reflection->setAccessible(false);
            }
        }
    }

    /**************************************************************************************************
     *currency-switcher-woocommerce
     **************************************************************************************************
     */

    /**
     * Convert rate to specified currency
     *
     * @since 1.0.0
     * @param string $rate
     * @param string $server
     * @param string $from
     * @param string $to
     * @return float
     */
    public function currency_switcher_woocommerce($rate, $server, $from, $to)
    {
        if (
            in_array($from, zimrate_get_isos()) ||
            in_array($to, zimrate_get_isos())
        ) {
            $currency = get_option('zimrate-currencies', 'RBZ');

            if (in_array($from, zimrate_get_isos())) {
                if ($to == 'USD') {
                    $rate = pow(zimrate_get_rate($currency), -1);
                } else {
                    //first change to usd
                    $rate = pow(
                        zimrate_get_rate($currency) *
                            $this->currency_switcher_woocommerce_to_usd(
                                $server,
                                $to
                            ),
                        -1
                    );
                }
            } else {
                if ($from == 'USD') {
                    //to = iso
                    $rate = zimrate_get_rate($currency);
                } else {
                    //first change to usd
                    $rate =
                        zimrate_get_rate($currency) *
                        $this->currency_switcher_woocommerce_to_usd(
                            $server,
                            $from
                        );
                }
            }

            $rate = zimrate_apply_cushion($rate);
        }

        return $rate;
    }

    /**
     * Fetch rate in relation to usd
     *
     * @since 1.0.0
     * @param  $server
     * @param  $currency
     * @return float
     */
    private function currency_switcher_woocommerce_to_usd($server, $currency)
    {
        $usd = 1;

        switch ($server) {
            case 'coinmarketcap':
                $usd = alg_wc_cs_get_exchange_rate_coinmarketcap(
                    $currency,
                    'USD'
                );
                break;
            case 'coinbase':
                $usd = alg_wc_cs_get_exchange_rate_coinbase($currency, 'USD');
                break;
            case 'tcmb':
                $usd = wpw_cs_tcmb_get_exchange_rate($currency, 'USD');
                break;
            case 'georgia':
                $usd = alg_wc_cs_get_exchange_rate_georgia($currency, 'USD');
                break;
            case 'boe':
                $usd = wpw_cs_boe_get_exchange_rate($currency, 'USD');
                break;
            case 'free_cur_api':
                $usd = alg_wc_cs_get_exchange_rate_free_currency_api(
                    $currency,
                    'USD'
                );
                break;
            default:
                // 'ecb'
                $usd = alg_wc_cs_get_exchange_rate_ecb($currency, 'USD');
        }

        return $usd;
    }

    /**************************************************************************************************
     * currency_exchange_for_woocommerce
     * ************************************************************************************************
     */

    /**
     * provide exchange rates for plugin
     *
     * @since 1.0.0
     * @param array $rates
     * @return array
     */
    public function currency_exchange_for_woocommerce($rates)
    {
        $rates[zimrate_get_iso()] = zimrate_get_rate();

        return $rates;
    }

    /**************************************************************************************************
     * WOOCS - WooCommerce Currency Switcher
     * ************************************************************************************************
     */

    /**
     * Convert for plugin
     *
     * @since 1.1.0
     * @param float|boolean $rate
     * @param string $from
     * @param string $to
     * @return string
     */
    public function woocs_add_custom_rate($rate, $from, $to)
    {

        if (
            in_array($from, zimrate_get_isos()) ||
            in_array($to, zimrate_get_isos())
        ) {

            global $WOOCS;

            $currency = get_option('zimrate-currencies', 'RBZ');

            if (in_array($from, zimrate_get_isos())) {
                if ($to == 'USD') {
                    $rate = pow(zimrate_get_rate($currency), -1);
                } else {
                    //first change to usd
                    $defaults = $this->woocs_save_defaults($to);

                    $rate = pow(
                        zimrate_get_rate($currency) *
                            $WOOCS->get_rate(),
                        -1
                    );

                    $this->woocs_restore_defaults($defaults);
                }
            } else {
                if ($from == 'USD') {
                    //to = iso
                    $rate = zimrate_get_rate($currency);
                } else {
                    //first change to usd
                    $defaults = $this->woocs_save_defaults($from);

                    $rate =
                        zimrate_get_rate($currency) *
                        $WOOCS->get_rate();

                    $this->woocs_restore_defaults($defaults);
                }
            }

            $rate = zimrate_apply_cushion($rate);
        }

        return $rate;
    }

    /**
     * Save WOOCS values to restore later
     *
     * @since 1.1.0
     * @param string $currency
     * @return array
     */
    private function woocs_save_defaults($currency)
    {
        global $WOOCS;

        $default_currency = $WOOCS->default_currency;
        $request_currency = $_REQUEST['currency_name'];
        $no_ajax = $_REQUEST['no_ajax'] ?: false;

        $_REQUEST['currency_name'] = 'USD';
        $WOOCS->default_currency = $currency;
        $_REQUEST['no_ajax'] = true;

        return compact('default_currency', 'request_currency', 'no_ajax');
    }

    /**
     * Restore WOOCS defaults values
     *
     * @since 1.1.0
     * @param array $defaults
     */
    private function woocs_restore_defaults($defaults)
    {
        global $WOOCS;

        extract($defaults);

        $WOOCS->default_currency = $default_currency;
        $_REQUEST['currency_name'] = $request_currency;
        if ($no_ajax) {
            $_REQUEST['no_ajax'] = $no_ajax;
        } else {
            unset($_REQUEST['no_ajax']);
        }
    }
}
