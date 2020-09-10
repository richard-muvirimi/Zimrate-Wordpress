<?php

/**
 * Get Zimbabwean iso code
 *
 * @since 1.0.0
 * @return string
 */
function zimrate_get_iso()
{
    return apply_filters('zimrate-iso', 'ZWL');
}

/**
 * Get Zimbabwean iso codes
 *
 * @since 1.0.0
 * @return array
 */
function zimrate_get_isos()
{
    return apply_filters('zimrate-isos', [zimrate_get_iso(), 'ZWE', 'ZWD']);
}

/**
 * Get exchange rates
 *
 * @version 1.1.2
 * @since 1.0.0
 * @param string $currency
 * @return array
 */
function zimrate_get_rates($currency = false)
{
    $key = 'zimrate' . ($currency ? '-' . $currency : '');

    $rates = get_transient($key);

    if ($rates === false) {
        $url = 'http://zimrate.tyganeutronics.com/api/v1';

        $args = [
            'body' => [
                'prefer' => get_option('zimrate-prefer', 'mean'),
                'currency' => $currency ? $currency : '',
            ],
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            //get fall back rate
            $rates = get_transient($key . '-backup');

            //if fail to get specific rate try to extract from whole response
            if ($rates === false) {
                if ($currency !== false) {
                    $backup = get_transient("zimrate-backup");

                    foreach ($backup['USD'] as $key => $rate) {
                        if ($rate['currency'] == $currency) {
                            $rates["USD"][] = $rate;
                            break;
                        }
                    }
                } else {
                    $rates = array(
                        "USD" => array(),
                        "info" => __("Cannot load rates at this time", "zimrate")
                    );
                }
            }
        } else {
            $rates = apply_filters(
                'zimrate-rates',
                json_decode(wp_remote_retrieve_body($response), true)
            );

            set_transient(
                $key,
                $rates,
                get_option('zimrate-interval', MINUTE_IN_SECONDS)
            );

            //if we fail to retrieve rates we will fall back to this
            set_transient($key . '-backup', $rates, defined("MONTH_IN_SECONDS") ?  MONTH_IN_SECONDS : DAY_IN_SECONDS * 30);
        }
    }

    return $rates;
}

/**
 * get exchange rate for currency
 *
 * @version 1.1.2
 * @since 1.0.0
 * @param string $currency
 * @return float
 */
function zimrate_get_rate($currency = false)
{
    $rates = zimrate_get_rates($currency ?: zimrate_get_selected_currency());

    if ($rates === false) {
        //if cannot retrieve rates completely
        return 1;
    } else {
        if (isset($rates['USD']) && !empty($rates['USD'])) {
            return array_shift($rates['USD'])['rate'];
        } else {
            //fall back to rbz else to one
            if ($currency == 'RBZ') {
                return 1;
            } else {
                return zimrate_get_rate('RBZ');
            }
        }
    }
}

/**
 * Undocumented function
 *
 * @since 1.1.1
 * @param string $value
 * @return string
 */
function zimrate_clear_rate_cache($value)
{

    //clear transients
    delete_transient('zimrate');

    foreach (zimrate_supported_currencies() as $currency => $name) {
        delete_transient('zimrate-' . $currency);
    }

    return $value;
}

/**
 * check if woo multi currency is active
 *
 * @since 1.0.0
 * @return bool
 */
function zimrate_woo_multi_currency_active()
{
    return zimrate_plugin_active('woo-multi-currency/woo-multi-currency.php');
}

/**
 * Check if a plugin is active
 *
 * @since 1.0.0
 * @param string $plugin
 * @return bool
 */
function zimrate_plugin_active($plugin)
{
    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    return is_plugin_active($plugin);
}

/**
 * Get list of known supported plugins
 *
 * @since 1.0.0
 * @version 1.1.0
 * @return array
 */
function zimrate_supported_plugins()
{
    return apply_filters('zimrate-plugins', [
        'woo-multi-currency/woo-multi-currency.php' => [
            'name' => 'Multi Currency for WooCommerce',
            'tested' => '2.1.5.6 ',
        ],
        'wc-multi-currency/wcmilticurrency.php' => [
            'name' => 'Multi Currency for WooCommerce',
            'tested' => '1.5 ',
        ],
        'currencyconverter/plugin.php' => [
            'name' => 'CurrencyConverter',
            'tested' => '0.5.3',
        ],
        'currency-switcher-woocommerce/currency-switcher-woocommerce.php' => [
            'name' => 'Currency Switcher for WooCommerce',
            'tested' => '2.12.3',
        ],
        'currency-exchange-for-woocommerce/woocommerce-currency-exchange.php' => [
            'name' => 'Currency Exchange for WooCommerce',
            'tested' => '3.5.1.5',
        ],
        'woocommerce-currency-switcher/index.php' => [
            'name' => 'WOOCS - WooCommerce Currency Switcher',
            'tested' => '1.3.1.1'
        ]
    ]);
}

/**
 * Get list of currencies we will be directly supporting
 *
 * @since 1.0.0
 * @return array
 */
function zimrate_supported_currencies()
{
    return apply_filters('zimrate-currencies', [
        'BOND' => __('Bond Note Rate', 'zimrate'),
        'OMIR' => __('Old Mutual Implied Rate', 'zimrate'),
        'RBZ' => __('Reserve Bank Rate', 'zimrate'),
        'RTGS' => __('Real Time Gross Settlement Rate', 'zimrate'),
    ]);
}

/**
 * Get zimrate intervals array
 *
 * @since 1.0.0
 * @return array
 */
function zimrate_intervals()
{
    return apply_filters('zimrate-intervals', [
        MINUTE_IN_SECONDS => __('Minutely', 'zimrate'),
        MINUTE_IN_SECONDS * 30 => __('Twice Hourly', 'zimrate'),
        HOUR_IN_SECONDS => __('Hourly', 'zimrate'),
        HOUR_IN_SECONDS * 2 => __('Two Hours', 'zimrate'),
        HOUR_IN_SECONDS * 6 => __('Six Hours', 'zimrate'),
        HOUR_IN_SECONDS * 12 => __('Twice Daily', 'zimrate'),
        DAY_IN_SECONDS => __('Daily', 'zimrate'),
        DAY_IN_SECONDS * 2 => __('Two Days', 'zimrate'),
        WEEK_IN_SECONDS => __('Weekly', 'zimrate'),
    ]);
}

/**
 * get host from url
 *
 * @since 1.0.0
 * @param string $url
 * @return string
 */
function zimrate_url_host($url)
{
    return parse_url($url)['host'];
}

/**
 * Get parameters from url
 *
 * @since 1.0.0
 * @param  string   $url
 * @return string
 */
function zimrate_url_params($url)
{
    $params = [];

    parse_str(parse_url($url)['query'], $params);

    return $params;
}

/**
 * Get selected currency
 *
 * @since 1.0.0
 * @return string
 */
function zimrate_get_selected_currency()
{
    return get_option('zimrate-currencies', 'RBZ');
}

/**
 * Apply rate cushion
 *
 * @since 1.0.0
 * @param  string   $rate
 * @return string
 */
function zimrate_apply_cushion($rate)
{
    $cushion = get_option('zimrate-cushion', 1);

    return apply_filters('zimrate-cushion', $rate + ($cushion * $rate) / 100);
}

/**
 * Get the short code for currency output
 *
 * @since 1.0.0
 * @return string
 */
function zimrate_get_shortcode()
{
    return 'zimrate';
}
