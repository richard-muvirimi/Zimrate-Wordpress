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
     * Get the iso code rates are converted to.
     *
     * This is whichever currency the site selected, the api quotes real iso
     * codes so there is nothing to map it through.
     *
     * @version 1.1.6
     * @since 1.0.0
     * @return string
     */
    public static function get_iso(): string
    {
        return apply_filters('zimrate-iso', self::get_selected_currency());
    }

    /**
     * Retired iso codes that should still be recognised as a live currency.
     *
     * Only currencies that actually went through a redenomination need an
     * entry, everything else resolves to just its own code.
     *
     * @since 1.1.6
     * @return array
     */
    public static function currency_aliases(): array
    {
        return apply_filters('zimrate-currency-aliases', [
            'ZWG' => ['ZWL', 'ZWE', 'ZWD'],
        ]);
    }

    /**
     * Get the iso codes that count as the selected currency
     *
     * @version 1.1.6
     * @since 1.0.0
     * @return array
     */
    public static function get_isos(): array
    {
        $iso = self::get_iso();
        $aliases = self::currency_aliases();

        return apply_filters('zimrate-isos', array_merge([$iso], $aliases[$iso] ?? []));
    }

    /**
     * Get the Zimrate GraphQL endpoint
     *
     * @since 1.1.6
     * @return string
     */
    public static function get_api_url(): string
    {
        return apply_filters('zimrate-api-url', 'https://zimrate.tyganeutronics.com/api/graphql');
    }

    /**
     * Currency to use when none is selected or the selected one is unavailable
     *
     * @since 1.1.6
     * @return string
     */
    public static function default_currency(): string
    {
        return apply_filters('zimrate-default-currency', 'ZWG');
    }

    /**
     * Base currency to quote rates against when none is selected
     *
     * @since 1.1.6
     * @return string
     */
    public static function default_base(): string
    {
        return apply_filters('zimrate-default-base', 'USD');
    }

    /**
     * Run a query against the Zimrate GraphQL api.
     *
     * The api answers with a 200 even for failed queries, so the body has to be
     * inspected for an "errors" key before the data can be trusted.
     *
     * @since 1.1.6
     * @param string $query
     * @param array $variables Empty values are dropped, the api rejects an
     *                         explicitly null currency.
     * @return array|false The data payload, false on failure.
     */
    public static function graphql(string $query, array $variables = [])
    {
        $response = wp_remote_post(self::get_api_url(), [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'query' => $query,
                'variables' => (object) array_filter($variables),
            ]),
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($body) || !empty($body['errors']) || !is_array($body['data'] ?? null)) {
            return false;
        }

        return $body['data'];
    }

    /**
     * Read an enum the api defines.
     *
     * Every list the settings page offers comes from the schema rather than
     * from the plugin, so a vocabulary change upstream cannot leave a stale
     * option behind.  Returns [value => value] for use as select options and
     * is cached for an hour.
     *
     * @since 1.1.6
     * @param string $type The enum type name
     * @return array
     */
    public static function fetch_enum(string $type): array
    {
        $key = 'zimrate-enum-' . strtolower($type);

        $cached = get_transient($key);

        if ($cached !== false) {
            return $cached;
        }

        $data = self::graphql(
            'query Enum($type: String!) { __type(name: $type) { enumValues { name } } }',
            ['type' => $type]
        );

        if ($data === false || !isset($data['__type']['enumValues'])) {
            return [];
        }

        $values = [];
        foreach ($data['__type']['enumValues'] as $value) {
            $values[$value['name']] = $value['name'];
        }

        if (!empty($values)) {
            set_transient($key, $values, HOUR_IN_SECONDS);
        }

        return $values;
    }

    /**
     * Fetch the rate sources available for a base and currency.
     *
     * These are the names the api labels each series with, and are what the
     * retired BOND, OMIR, RBZ and RTGS options really selected.  Derived from
     * the data rather than an enum, so it is cached for an hour.
     *
     * @since 1.1.6
     * @return array
     */
    public static function fetch_available_sources(): array
    {
        $base = self::get_base();
        $currency = self::get_selected_currency();

        $key = 'zimrate-sources-' . strtolower($base . '-' . $currency);

        $cached = get_transient($key);

        if ($cached !== false) {
            return $cached;
        }

        $data = self::graphql(
            'query Sources($base: Base, $currency: Currency) {' .
                ' rate(base: $base, currency: $currency) { name }' .
                ' }',
            [
                'base' => $base,
                'currency' => $currency,
            ]
        );

        if ($data === false || !isset($data['rate'])) {
            return [];
        }

        $sources = [];
        foreach ($data['rate'] as $rate) {
            if (!empty($rate['name'])) {
                $sources[$rate['name']] = $rate['name'];
            }
        }

        if (!empty($sources)) {
            set_transient($key, $sources, HOUR_IN_SECONDS);
        }

        return $sources;
    }

    /**
     * Check a cached payload still holds rates.
     *
     * Releases before 1.1.6 cached api error bodies as though they were rates,
     * and those entries have a month long lifetime, so they have to be ignored
     * rather than waited out.
     *
     * @since 1.1.6
     * @param mixed $rates
     * @return bool
     */
    private static function is_rates($rates, string $base): bool
    {
        return is_array($rates) && isset($rates[$base]) && is_array($rates[$base]);
    }

    /**
     * Build the transient key for a rate lookup.
     *
     * Rates vary by base, currency and source, so all three have to be part of
     * the key.  The live key also carries a cache version that is bumped when
     * settings change, which invalidates every combination at once without
     * having to enumerate them.  Backups deliberately leave the version out so
     * they survive a settings save.
     *
     * @since 1.1.6
     * @param string $base
     * @param string|false $currency
     * @param string $source
     * @param bool $backup
     * @return string
     */
    private static function cache_key(string $base, $currency, string $source, bool $backup = false): string
    {
        $key = 'zimrate-' . strtolower($base)
            . ($currency ? '-' . strtolower($currency) : '')
            . ($source ? '-' . substr(md5($source), 0, 8) : '');

        return $backup ? $key . '-backup' : $key . '-v' . self::cache_version();
    }

    /**
     * Current rate cache version
     *
     * @since 1.1.6
     * @return int
     */
    private static function cache_version(): int
    {
        return intval(get_option('zimrate-cache-version', 1));
    }

    /**
     * Get exchange rates
     *
     * @version 1.1.6
     * @since 1.0.0
     * @param string|false $currency
     * @return array
     */
    public static function get_rates($currency = false): array
    {
        $base = self::get_base();
        $source = self::get_selected_source();

        $key = self::cache_key($base, $currency, $source);

        $rates = get_transient($key);

        if (self::is_rates($rates, $base)) {
            return $rates;
        }

        $data = self::query_rates($base, $currency, $source);

        // a source that no longer exists filters every row out, so drop it and
        // take the unfiltered rates rather than showing nothing
        if ($source && is_array($data) && empty($data['rate'])) {
            $source = '';
            $key = self::cache_key($base, $currency, $source);
            $data = self::query_rates($base, $currency, $source);
        }

        if ($data !== false) {
            $rates = apply_filters('zimrate-rates', array(
                $base => $data['rate'] ?? array(),
                "info" => $data['info'] ?? '',
            ));

            set_transient(
                $key,
                $rates,
                get_option('zimrate-interval', MINUTE_IN_SECONDS)
            );

            set_transient(
                self::cache_key($base, $currency, $source, true),
                $rates,
                defined("MONTH_IN_SECONDS") ?  MONTH_IN_SECONDS : DAY_IN_SECONDS * 30
            );

            return $rates;
        }

        // the request failed, fall back on the last response we know was good
        $backup = get_transient(self::cache_key($base, $currency, $source, true));

        if (self::is_rates($backup, $base)) {
            return $backup;
        }

        if ($currency !== false) {
            $backup = get_transient(self::cache_key($base, false, $source, true));

            if (self::is_rates($backup, $base)) {
                foreach ($backup[$base] as $rate) {
                    if ($rate['currency'] == $currency) {
                        return array(
                            $base => array($rate),
                            "info" => $backup['info'] ?? '',
                        );
                    }
                }
            }
        }

        return array(
            $base => array(),
            "info" => __("Cannot load rates at this time", "zimrate")
        );
    }

    /**
     * Ask the api for rates
     *
     * @since 1.1.6
     * @param string $base
     * @param string|false $currency
     * @param string $source
     * @return array|false
     */
    private static function query_rates(string $base, $currency, string $source)
    {
        // prefer aggregates across sources, so it only applies when no single
        // source was asked for.  The api returns nothing at all if both are
        // sent together.
        return self::graphql(
            'query Rates($base: Base, $currency: Currency, $prefer: Prefer, $search: String) {' .
                ' rate(base: $base, currency: $currency, prefer: $prefer, search: $search)' .
                ' { currency name rate last_checked last_updated }' .
                ' info' .
                ' }',
            [
                'base' => $base,
                'currency' => $currency,
                'prefer' => $source ? '' : strtoupper(get_option('zimrate-prefer', 'mean')),
                'search' => $source,
            ]
        );
    }

    /**
     * Get exchange rate for currency
     *
     * @version 1.1.6
     * @since 1.0.0
     * @param string|false $currency
     * @return float
     */
    public static function get_rate($currency = false): float
    {
        $base = self::get_base();

        $currency = $currency ?: self::get_selected_currency();

        $rates = self::get_rates($currency);

        if (isset($rates[$base]) && !empty($rates[$base])) {
            return floatval(array_shift($rates[$base])['rate']);
        }

        $fallback = self::default_currency();

        if ($currency === $fallback) {
            return 1.0;
        }

        return self::get_rate($fallback);
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
        // bumping the version orphans every live rate key at once, the
        // vocabularies are dropped so a changed setting re-reads them
        update_option('zimrate-cache-version', self::cache_version() + 1);

        foreach (['base', 'currency', 'prefer'] as $type) {
            delete_transient('zimrate-enum-' . $type);
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
        return apply_filters('zimrate-currencies', self::fetch_enum('Currency'));
    }

    /**
     * Get the base currencies rates can be quoted against
     *
     * @since 1.1.6
     * @return array
     */
    public static function supported_bases(): array
    {
        return apply_filters('zimrate-bases', self::fetch_enum('Base'));
    }

    /**
     * Get the rate preferences the api offers
     *
     * @since 1.1.6
     * @return array
     */
    public static function supported_prefers(): array
    {
        $prefers = [];
        foreach (self::fetch_enum('Prefer') as $prefer) {
            $prefers[$prefer] = ucfirst(strtolower($prefer));
        }

        return apply_filters('zimrate-prefers', $prefers);
    }

    /**
     * Get the rate sources available for the selected base and currency
     *
     * @since 1.1.6
     * @return array
     */
    public static function supported_sources(): array
    {
        return apply_filters('zimrate-sources', self::fetch_available_sources());
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
        return get_option('zimrate-currencies', self::default_currency());
    }

    /**
     * Get the base currency rates are quoted against
     *
     * @since 1.1.6
     * @return string
     */
    public static function get_base(): string
    {
        return get_option('zimrate-base', self::default_base());
    }

    /**
     * Get the selected rate source, empty when any source will do
     *
     * @since 1.1.6
     * @return string
     */
    public static function get_selected_source(): string
    {
        return strval(get_option('zimrate-source', ''));
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
