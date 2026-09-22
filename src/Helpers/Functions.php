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
     * Country a currency belongs to, as a lowercase ISO 3166 code.
     *
     * ISO 4217 codes are prefixed with their country code (ZWG is ZW, ZAR is
     * ZA), which is what keeps this stable when a country replaces its
     * currency: ZWD, ZWL and ZWG all resolve to Zimbabwe with no change here.
     * Only the supranational currencies need spelling out, and those get the
     * same representative country the api's own calculator uses.
     *
     * @since 1.1.6
     * @param string $currency
     * @return string
     */
    public static function country_code(string $currency): string
    {
        $currency = strtoupper($currency);

        $overrides = apply_filters('zimrate-country-overrides', [
            'EUR' => 'eu',
            'XAF' => 'cm',
            'XOF' => 'sn',
            'XCD' => 'ag',
            'XPF' => 'pf',
            'ANG' => 'cw',
        ]);

        return $overrides[$currency] ?? strtolower(substr($currency, 0, 2));
    }

    /**
     * Country names keyed by ISO 3166 code, from country.io.
     *
     * Country names change about as often as countries do, so the list is
     * held for a month and a copy is kept for a year for when the fetch
     * fails.
     *
     * @since 1.1.6
     * @return array
     */
    public static function country_names(): array
    {
        $key = 'zimrate-country-names';

        $names = get_transient($key);

        if (is_array($names)) {
            return $names;
        }

        $response = wp_remote_get(apply_filters('zimrate-country-names-url', 'https://country.io/names.json'));

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $names = json_decode(wp_remote_retrieve_body($response), true);

            if (is_array($names) && !empty($names)) {
                set_transient($key, $names, defined('MONTH_IN_SECONDS') ? MONTH_IN_SECONDS : DAY_IN_SECONDS * 30);
                set_transient($key . '-backup', $names, defined('YEAR_IN_SECONDS') ? YEAR_IN_SECONDS : DAY_IN_SECONDS * 365);

                return $names;
            }
        }

        $backup = get_transient($key . '-backup');

        return is_array($backup) ? $backup : [];
    }

    /**
     * Country name for a currency, or its country code where none resolves.
     *
     * ICU is preferred where the host has intl: its names are localised to the
     * site and kept current with the official forms.  It hands back the region
     * code itself for one it does not know, in which case country.io fills in.
     * Failing both, the country code stands: it needs no lookup, so it is the
     * one answer that is always available.
     *
     * @since 1.1.6
     * @param string $currency
     * @return string
     */
    public static function country_name(string $currency): string
    {
        $region = strtoupper(self::country_code($currency));

        if (class_exists('\Locale')) {
            $name = \Locale::getDisplayRegion('und-' . $region, get_locale());

            if ($name && strcasecmp($name, $region) !== 0) {
                return $name;
            }
        }

        $names = self::country_names();

        return $names[$region] ?? $region;
    }

    /**
     * The label a currency is shown under: "ZAR · South Africa", or just the
     * code where the two would repeat.
     *
     * @since 1.1.6
     * @param string $currency
     * @return string
     */
    public static function currency_label(string $currency): string
    {
        $code = strtoupper($currency);
        $name = self::country_name($currency);

        return strcasecmp($name, $code) === 0 ? $code : $code . ' · ' . $name;
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
     * How to combine the sources behind a rate when none is selected.
     *
     * The median ignores the outliers a handful of scraped sources can throw,
     * which the mean would carry straight into a price.
     *
     * @since 1.1.6
     * @return string
     */
    public static function default_prefer(): string
    {
        return apply_filters('zimrate-default-prefer', 'MEDIAN');
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
     * Only the rate preference needs this, the currencies come with the rates
     * themselves.  Returns [value => value] for use as select options and is
     * cached for an hour.
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
    private static function is_rates($rates): bool
    {
        return is_array($rates) && isset($rates['USD']) && is_array($rates['USD']);
    }

    /**
     * Get exchange rates
     *
     * One request fetches every rate the api has, quoted against USD, and that
     * is the only request the plugin makes for rates.  Every other number,
     * a single currency, another base, is arithmetic on this response.
     *
     * @version 1.1.6
     * @since 1.0.0
     * @return array
     */
    public static function get_rates(): array
    {
        $key = 'zimrate-rates';

        $rates = get_transient($key);

        if (self::is_rates($rates)) {
            return $rates;
        }

        $data = self::graphql(
            'query Rates($prefer: Prefer) {' .
                ' rate(prefer: $prefer) { currency rate last_checked last_updated }' .
                ' info' .
                ' }',
            [
                'prefer' => strtoupper(get_option('zimrate-prefer', self::default_prefer())),
            ]
        );

        if ($data !== false) {
            $rates = apply_filters('zimrate-rates', array(
                "USD" => $data['rate'] ?? array(),
                "info" => $data['info'] ?? '',
            ));

            set_transient(
                $key,
                $rates,
                get_option('zimrate-interval', MINUTE_IN_SECONDS)
            );

            set_transient($key . '-backup', $rates, defined("MONTH_IN_SECONDS") ?  MONTH_IN_SECONDS : DAY_IN_SECONDS * 30);

            return $rates;
        }

        // the request failed, fall back on the last response we know was good
        $backup = get_transient($key . '-backup');

        if (self::is_rates($backup)) {
            return $backup;
        }

        return array(
            "USD" => array(),
            "info" => __("Cannot load rates at this time", "zimrate")
        );
    }

    /**
     * Every rate keyed by currency, quoted against USD, cushion applied.
     *
     * This is the shape the integrations write into a host plugin's table, the
     * hosts keep USD based tables so every currency the api covers gets
     * overridden rather than a chosen one.
     *
     * @since 1.1.6
     * @return array
     */
    public static function get_rates_from_usd(): array
    {
        $map = array();
        foreach (self::get_rates()['USD'] as $rate) {
            $map[$rate['currency']] = floatval(self::apply_cushion($rate['rate']));
        }

        return apply_filters('zimrate-usd-rates', $map);
    }

    /**
     * Get the exchange rate for a currency against a base, without cushion.
     *
     * Rates are held against USD, so any other base is a cross rate worked out
     * here.  A currency the api does not cover yields 1.0.
     *
     * @version 1.1.6
     * @since 1.0.0
     * @param string|false $currency
     * @param string|null $base
     * @return float
     */
    public static function get_rate($currency = false, $base = null): float
    {
        $currency = strtoupper($currency ?: self::default_currency());
        $base = strtoupper($base ?: self::default_base());

        $usd = array('USD' => 1);
        foreach (self::get_rates()['USD'] as $rate) {
            $usd[$rate['currency']] = $rate['rate'];
        }

        if ($currency === $base) {
            return 1.0;
        }

        if (!isset($usd[$currency], $usd[$base]) || $usd[$base] == 0) {
            return 1.0;
        }

        return floatval(Arithmetic::div($usd[$currency], $usd[$base]));
    }

    /**
     * Rates quoted against USD keyed by currency, alphabetical, as the api sent
     * them
     *
     * @since 1.1.6
     * @return array
     */
    public static function get_usd_rates(): array
    {
        $usd = array();
        foreach (self::get_rates()['USD'] as $rate) {
            $usd[$rate['currency']] = $rate['rate'];
        }

        ksort($usd);

        return $usd;
    }

    /**
     * Data for the rates table view
     *
     * Every currency against the base, cushion applied after the cross rate
     * (applying it to both sides beforehand would cancel it out), rounded to
     * the precision shown.
     *
     * @since 1.1.6
     * @param string $base
     * @param int $precision
     * @param bool $cushion
     * @param array $currencies codes to show, none for every currency
     * @return array
     */
    public static function rates_table(string $base, int $precision, bool $cushion, array $currencies = array()): array
    {
        $usd = self::get_usd_rates();

        $base = strtoupper($base);
        if ($base !== 'USD' && !isset($usd[$base])) {
            $base = 'USD';
        }

        $currencies = array_map('strtoupper', $currencies);

        $rows = array();
        foreach ($usd as $code => $rate) {
            if ($currencies && !in_array($code, $currencies, true)) {
                continue;
            }

            if ($code === $base) {
                $rate = '1';
            } elseif ($base !== 'USD') {
                $rate = Arithmetic::div($rate, $usd[$base]);
            }

            if ($cushion) {
                $rate = self::apply_cushion($rate);
            }

            $rows[$code] = array(
                'label' => self::currency_label($code),
                'rate' => Arithmetic::round($rate, $precision),
            );
        }

        return array(
            'base' => $base,
            'precision' => $precision,
            'rows' => $rows,
            'info' => self::get_rates()['info'] ?? '',
        );
    }

    /**
     * Data for the calculator view
     *
     * The rates go to the page as json so the script can convert without a
     * request, the table is rendered here when asked for.
     *
     * @since 1.1.6
     * @param array $args base, currency, amount, precision, cushion, table, open
     * @return array
     */
    public static function calculator(array $args): array
    {
        $args = wp_parse_args($args, array(
            'base' => self::default_base(),
            'currency' => self::default_currency(),
            'amount' => 1,
            'precision' => 2,
            'cushion' => true,
            'table' => false,
            'open' => false,
        ));

        $usd = self::get_usd_rates();

        $currencies = array();
        foreach ($usd as $code => $rate) {
            $currencies[$code] = self::currency_label($code);
        }

        $bases = array('USD' => self::currency_label('USD')) + $currencies;

        $base = strtoupper($args['base']);
        $base = isset($bases[$base]) ? $base : 'USD';

        $currency = strtoupper($args['currency']);
        $currency = isset($currencies[$currency]) ? $currency : (string) key($currencies);

        $precision = intval($args['precision']);

        return array(
            'base' => $base,
            'currency' => $currency,
            'amount' => floatval($args['amount']),
            'precision' => $precision,
            'cushion' => $args['cushion'] ? floatval(get_option('zimrate-cushion', 1)) : 0,
            'bases' => $bases,
            'currencies' => $currencies,
            'rates' => array_map('floatval', $usd),
            'info' => self::get_rates()['info'] ?? '',
            'open' => (bool) $args['open'],
            'table' => $args['table']
                ? Template::get_template(
                    self::get_plugin_slug('-rates-table'),
                    self::rates_table($base, $precision, (bool) $args['cushion']),
                    'rates-table.php'
                )
                : '',
        );
    }

    /**
     * Get the rate a USD based plugin expects, without cushion
     *
     * @since 1.1.6
     * @param string|false $currency
     * @return float
     */
    public static function get_rate_from_usd($currency = false): float
    {
        return self::get_rate($currency, 'USD');
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
        delete_transient('zimrate-rates');
        delete_transient('zimrate-enum-prefer');

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
     * Get the currencies the api covers, keyed by code
     *
     * @version 1.1.6
     * @since 1.0.0
     * @return array
     */
    public static function supported_currencies(): array
    {
        $currencies = array();
        foreach (self::get_rates()['USD'] as $rate) {
            $currencies[$rate['currency']] = $rate['currency'];
        }

        ksort($currencies);

        return apply_filters('zimrate-currencies', $currencies);
    }

    /**
     * Get the base currencies rates can be quoted against: USD, which the api
     * quotes in, plus anything it covers
     *
     * @since 1.1.6
     * @return array
     */
    public static function supported_bases(): array
    {
        return apply_filters('zimrate-bases', array('USD' => 'USD') + self::supported_currencies());
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
     * Apply rate cushion
     *
     * Takes and returns a numeric string so the cushion can sit in the middle
     * of a decimal calculation, cast the result where a float is wanted.
     *
     * @version 1.1.6
     * @since 1.0.0
     * @param  float|string $rate
     * @return string
     */
    public static function apply_cushion($rate): string
    {
        $cushion = get_option('zimrate-cushion', 1);

        return apply_filters(
            'zimrate-cushion',
            Arithmetic::add($rate, Arithmetic::div(Arithmetic::mul($cushion, $rate), 100))
        );
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
