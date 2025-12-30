<?php
/**
 * Helper functions
 *
 * @package    Zimrate
 * @subpackage Zimrate/Helpers
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */

namespace RichardMuvirimi\Zimrate\Helpers;

/**
 * Helper functions class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class Functions
{
    /**
     * Get plugin slug with optional suffix
     *
     * @param string $suffix Optional suffix to append
     * @return string
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public static function get_plugin_slug(string $suffix = ''): string
    {
        return ZIMRATE_SLUG . $suffix;
    }

    /**
     * Get plugin name
     *
     * @return string
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public static function get_plugin_name(): string
    {
        return ZIMRATE_NAME;
    }

    /**
     * Get plugin version
     *
     * @return string
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public static function get_plugin_version(): string
    {
        return ZIMRATE_VERSION;
    }

    /**
     * Get Zimbabwean iso code
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_iso(): string
    {
        return apply_filters('zimrate-iso', 'ZWL');
    }

    /**
     * Get Zimbabwean iso codes
     *
     * @since 1.0.0
     * @return array
     */
    public static function get_isos(): array
    {
        return apply_filters('zimrate-isos', [self::get_iso(), 'ZWE', 'ZWD']);
    }

    /**
     * Get exchange rates
     *
     * @version 1.1.2
     * @since 1.0.0
     * @param string|false $currency
     * @return array
     */
    public static function get_rates($currency = false): array
    {
        $key = 'zimrate' . ($currency ? '-' . $currency : '');

        $rates = get_transient($key);

        if ($rates === false) {
            $url = 'https://zimrate.tyganeutronics.com/api/v1';

            $body = [
                'prefer' => get_option('zimrate-prefer', 'mean'),
            ];
            
            if ($currency) {
                $body['currency'] = $currency;
            }

            $args = [
                'body' => $body,
            ];

            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                $rates = get_transient($key . '-backup');

                if ($rates === false) {
                    if ($currency !== false) {
                        $backup = get_transient("zimrate-backup");

                        if ($backup && isset($backup['USD'])) {
                            foreach ($backup['USD'] as $rate) {
                                if ($rate['currency'] == $currency) {
                                    $rates["USD"][] = $rate;
                                    break;
                                }
                            }
                        }
                    }
                    
                    if ($rates === false) {
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

                set_transient($key . '-backup', $rates, defined("MONTH_IN_SECONDS") ?  MONTH_IN_SECONDS : DAY_IN_SECONDS * 30);
            }
        }

        return $rates;
    }

    /**
     * Get exchange rate for currency
     *
     * @version 1.1.2
     * @since 1.0.0
     * @param string|false $currency
     * @return float
     */
    public static function get_rate($currency = false): float
    {
        $rates = self::get_rates($currency ?: self::get_selected_currency());

        if ($rates === false) {
            return 1.0;
        } else {
            if (isset($rates['USD']) && !empty($rates['USD'])) {
                return floatval(array_shift($rates['USD'])['rate']);
            } else {
                if ($currency == 'RBZ') {
                    return 1.0;
                } else {
                    return self::get_rate('RBZ');
                }
            }
        }
    }

    /**
     * Clear rate cache
     *
     * @since 1.1.1
     * @param string $value
     * @return string
     */
    public static function clear_rate_cache($value): string
    {
        delete_transient('zimrate');

        foreach (self::supported_currencies() as $currency => $name) {
            delete_transient('zimrate-' . $currency);
        }

        return $value;
    }

    /**
     * Check if a plugin is active
     *
     * @since 1.0.0
     * @param string $plugin
     * @return bool
     */
    public static function plugin_active(string $plugin): bool
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
    public static function supported_plugins(): array
    {
        $integrations = [
            new \RichardMuvirimi\Zimrate\Integrations\WooMultiCurrency(),
            new \RichardMuvirimi\Zimrate\Integrations\WcMultiCurrency(),
            new \RichardMuvirimi\Zimrate\Integrations\CurrencyConverter(),
            new \RichardMuvirimi\Zimrate\Integrations\CurrencySwitcherWoocommerce(),
            new \RichardMuvirimi\Zimrate\Integrations\CurrencyExchangeWoocommerce(),
            new \RichardMuvirimi\Zimrate\Integrations\Woocs(),
        ];

        $plugins = [];
        foreach ($integrations as $integration) {
            $plugins[$integration->get_plugin_slug()] = [
                'name' => $integration->get_plugin_name(),
                'tested' => $integration->get_tested_version(),
                'supported_from' => $integration->get_supported_from(),
            ];
        }

        return apply_filters('zimrate-plugins', $plugins);
    }

    /**
     * Get list of currencies we will be directly supporting
     *
     * @since 1.0.0
     * @return array
     */
    public static function supported_currencies(): array
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
    public static function intervals(): array
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
     * Get host from url
     *
     * @since 1.0.0
     * @param string $url
     * @return string
     */
    public static function url_host(string $url): string
    {
        return parse_url($url, PHP_URL_HOST);
    }

    /**
     * Get parameters from url
     *
     * @since 1.0.0
     * @param  string   $url
     * @return array
     */
    public static function url_params(string $url): array
    {
        $params = [];
        $query = parse_url($url, PHP_URL_QUERY);
        
        if ($query) {
            parse_str($query, $params);
        }

        return $params;
    }

    /**
     * Get selected currency
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_selected_currency(): string
    {
        return get_option('zimrate-currencies', 'RBZ');
    }

    /**
     * Apply rate cushion
     *
     * @since 1.0.0
     * @param  float   $rate
     * @return float
     */
    public static function apply_cushion(float $rate): float
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
    public static function get_shortcode(): string
    {
        return 'zimrate';
    }
}
