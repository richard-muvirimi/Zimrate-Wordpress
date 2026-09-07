<?php
/**
 * Base class for plugin integrations
 *
 * @package    Zimrate
 * @subpackage Zimrate/Integrations
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */

namespace RichardMuvirimi\Zimrate\Integrations;

use RichardMuvirimi\Zimrate\Helpers\Functions;

/**
 * Base plugin integration class
 *
 * All plugin-specific integrations should extend this class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
abstract class BasePluginIntegration
{
    /**
     * Get the plugin slug (relative path from plugins directory)
     *
     * @return string
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    abstract public function get_plugin_slug(): string;

    /**
     * Get the plugin display name
     *
     * Fetches plugin name from WordPress.org API and caches for 1 day.
     * This ensures we always display the official plugin name from WordPress.org.
     * Falls back to get_plugin_name_fallback() if API call fails.
     *
     * @return string The plugin display name
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public function get_plugin_name(): string
    {
        // Try to get from cache first
        $cacheKey = 'zimrate_plugin_name_' . md5($this->get_plugin_slug());
        $cachedName = get_transient($cacheKey);
        
        if ($cachedName !== false) {
            return $cachedName;
        }
        
        // Get WordPress.org slug from plugin slug (e.g., "plugin-name/plugin.php" -> "plugin-name")
        $wpOrgSlug = dirname($this->get_plugin_slug());
        
        // Fetch from WordPress.org API
        $response = @file_get_contents("https://api.wordpress.org/plugins/info/1.0/{$wpOrgSlug}.json");
        
        if ($response) {
            $data = json_decode($response, true);
            
            if (isset($data['name'])) {
                $pluginName = $data['name'];
                // Cache for 1 day
                set_transient($cacheKey, $pluginName, DAY_IN_SECONDS);
                return $pluginName;
            }
        }
        
        // Fallback to manual implementation
        return $this->get_plugin_name_fallback();
    }

    /**
     * Fallback method to get plugin name when API fails
     *
     * Override this in child classes to provide manual plugin name
     *
     * @return string
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    abstract protected function get_plugin_name_fallback(): string;

    /**
     * Get the tested version of the plugin
     *
     * @return string
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    abstract public function get_tested_version(): string;

    /**
     * Get the version from which support started
     *
     * @return string
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    abstract public function get_supported_from(): string;

    /**
     * Check if the plugin is active
     *
     * @return bool
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public function is_active(): bool
    {
        return Functions::plugin_active($this->get_plugin_slug());
    }

    /**
     * Register hooks for this plugin integration
     *
     * Override this method to register WordPress hooks (actions, filters)
     *
     * @return void
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public function register_hooks(): void
    {
        // Override in child classes to register hooks
    }

    /**
     * Initialize the integration when plugin is loaded
     *
     * Override this method for plugins_loaded hook logic
     *
     * @return void
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public function on_plugins_loaded(): void
    {
        // Override in child classes for plugins_loaded logic
    }

    /**
     * Get plugin metadata as array
     *
     * @return array
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public function get_metadata(): array
    {
        return [
            'slug' => $this->get_plugin_slug(),
            'name' => $this->get_plugin_name(),
            'tested' => $this->get_tested_version(),
            'supported_from' => $this->get_supported_from(),
        ];
    }

    /**
     * Convert currency with unified logic
     *
     * This method provides the core conversion logic that all integrations use.
     * Child classes only need to implement get_usd_rate() and optionally
     * save_conversion_state() and restore_conversion_state() for state management.
     *
     * @since 1.0.0
     * @param string $from Source currency code
     * @param string $to Target currency code
     * @param mixed ...$args Additional arguments to pass to get_usd_rate()
     * @return float The conversion rate
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    protected function convert_currency(string $from, string $to, ...$args): float
    {
        $currency = Functions::get_selected_currency();
        $rate = 1.0;

        if (in_array($from, Functions::get_isos())) {
            if ($to == 'USD') {
                $rate = pow(Functions::get_rate($currency), -1);
            } else {
                $state = $this->save_conversion_state($to, $args);
                $rate = pow(
                    Functions::get_rate($currency) * $this->get_usd_rate($to, ...$args),
                    -1
                );
                $this->restore_conversion_state($state);
            }
        } else {
            if ($from == 'USD') {
                $rate = Functions::get_rate($currency);
            } else {
                $state = $this->save_conversion_state($from, $args);
                $rate = Functions::get_rate($currency) * $this->get_usd_rate($from, ...$args);
                $this->restore_conversion_state($state);
            }
        }

        return Functions::apply_cushion($rate);
    }

    /**
     * Get USD exchange rate for a given currency
     *
     * Override this in child classes to implement currency-specific logic
     * for fetching exchange rates relative to USD.
     *
     * @since 1.0.0
     * @param string $currency Currency code to convert to USD
     * @param mixed ...$args Additional arguments (API params, server info, etc.)
     * @return float The exchange rate to USD
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    protected function get_usd_rate(string $currency, ...$args): float
    {
        return 1.0;
    }

    /**
     * Save conversion state before fetching rates
     *
     * Override this in child classes if you need to save and restore state
     * during conversion (e.g., global variables, request parameters).
     *
     * @since 1.0.0
     * @param string $currency The currency being converted
     * @param array $args Arguments passed to convert_currency
     * @return array|null State data to be restored later, or null if no state management needed
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    protected function save_conversion_state(string $currency, array $args): ?array
    {
        return null;
    }

    /**
     * Restore conversion state after fetching rates
     *
     * Override this in child classes if you need to restore state
     * after conversion.
     *
     * @since 1.0.0
     * @param array|null $state State data saved by save_conversion_state()
     * @return void
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    protected function restore_conversion_state(?array $state): void
    {
        // Override in child classes if state management needed
    }

    /**
     * Convert requested currency (legacy method for backward compatibility)
     *
     * @deprecated Use convert_currency() instead
     * @since 1.0.0
     * @param string $from
     * @param string $to
     * @param array  $parsed_args HTTP request arguments.
     * @param string $url         The request URL.
     * @return float
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    protected function convert_to_currency($from, $to, $parsed_args, $url): float
    {
        return $this->convert_currency($from, $to, $parsed_args, $url);
    }

    /**
     * Request rate to USD from remote API (legacy method for backward compatibility)
     *
     * @deprecated Use get_usd_rate() instead
     * @since 1.0.0
     * @param string $base
     * @param array  $parsed_args HTTP request arguments.
     * @param string $url         The request URL.
     * @return float
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    protected function request_rate_to_usd($base, $parsed_args, $url): float
    {
        return $this->get_usd_rate($base, $parsed_args, $url);
    }
}
