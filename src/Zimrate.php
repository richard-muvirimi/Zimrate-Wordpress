<?php
/**
 * Bootstrap the plugin
 *
 * This file is the entry point into the plugin, registering all functions
 *
 * @package    Zimrate
 * @subpackage Zimrate/includes
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 * @version 1.1.3
 */

namespace RichardMuvirimi\Zimrate;

use BadMethodCallException;
use RichardMuvirimi\Zimrate\Controller\Admin;
use RichardMuvirimi\Zimrate\Controller\Ajax;
use RichardMuvirimi\Zimrate\Controller\Plugin;
use RichardMuvirimi\Zimrate\Controller\Site;
use RichardMuvirimi\Zimrate\Helpers\Functions;
use RichardMuvirimi\Zimrate\Locale\I18n;

/**
 * Class to bootstrap the plugin
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 * @version 1.1.3
 *
 * @method void register_deactivation_hook($file, $component, $method)
 *  {@see \register_deactivation_hook}
 * @method void register_uninstall_hook($file, $component, $method)
 *  {@see \register_uninstall_hook}
 * @method void register_activation_hook($file, $component, $method)
 *  {@see \register_activation_hook}
 * @method bool|true add_filter($hook_name, $component, $method, $priority = 10, $accepted_args = 1)
 *  {@see \add_filter}
 * @method bool|true add_action($hook_name, $component, $method, $priority = 10, $accepted_args = 1)
 *  {@see \add_action}
 * @method void add_shortcode($tag, $component, $method)
 *  {@see \add_shortcode}
 */
class Zimrate
{

    /**
     * Hold reference to a single instance of this class
     *
     * @var self
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     * @version 1.0.0
     */
    private static $instance;

    /**
     * Init plugin Loader
     *
     * @return void
     * @since 1.0.0
     * @version 1.1.3
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    protected function __construct()
    {
        $this->register_activation_hook(ZIMRATE_FILE, Plugin::class, 'on_activation');
        $this->register_deactivation_hook(ZIMRATE_FILE, Plugin::class, 'on_deactivation');
        $this->register_uninstall_hook(ZIMRATE_FILE, Plugin::class, 'on_uninstall');
    }

    /**
     * Bootstrap the plugin
     *
     * @return self
     * @version 1.0.0
     * @since 1.0.0
     */
    public static function instance(): Zimrate
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize plugin actions and filters
     *
     * @return void
     * @version 1.1.3
     * @since 1.0.0
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    public function run(): void
    {
        $this->register_i18n();
        $this->register_plugin_integrations();
        $this->register_admin_hooks();
        $this->register_public_hooks();
        $this->register_ajax_hooks();
    }

    /**
     * Register internationalization functionality
     *
     * @return void
     * @since 1.0.0
     * @version 1.0.0
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    private function register_i18n(): void
    {
        $locale = new I18n();

        $this->add_action('plugins_loaded', $locale, 'load_plugin_textdomain');
    }

    /**
     * Register third-party plugin integrations
     *
     * @return void
     * @since 1.0.0
     * @version 1.0.0
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    private function register_plugin_integrations(): void
    {
        $integrations = [
            new Integrations\WooMultiCurrency(),
            new Integrations\WcMultiCurrency(),
            new Integrations\CurrencyConverter(),
            new Integrations\CurrencySwitcherWoocommerce(),
            new Integrations\CurrencyExchangeWoocommerce(),
            new Integrations\Woocs(),
        ];

        foreach ($integrations as $integration) {
            // Only register hooks and initialization for active plugins
            if ($integration->is_active()) {
                $integration->register_hooks();
                $this->add_action('plugins_loaded', $integration, 'on_plugins_loaded');
            }
        }
    }

    /**
     * Register admin-specific hooks
     *
     * @return void
     * @since 1.0.0
     * @version 1.0.0
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    private function register_admin_hooks(): void
    {

        $controller = new Admin();

        $this->add_action('admin_menu', $controller, 'on_admin_menu');
        $this->add_action('admin_init', $controller, 'register_setting');
        $this->add_action('admin_init', $controller, 'add_settings_section');
        $this->add_action('admin_init', $controller, 'add_settings_fields');
        $this->add_action('admin_init', $controller, 'registerOptions');
        $this->add_action('admin_notices', $controller, 'showAdminNotices');
        $this->add_action('wp_dashboard_setup', $controller, 'on_dashboard_setup');
        $this->add_filter('woocommerce_currencies', $controller, 'add_woocommerce_currencies');
        $this->add_filter('woocommerce_currency_symbols', $controller, 'add_woocommerce_currency_symbols');
        $this->add_action('admin_enqueue_scripts', $controller, 'enqueue_styles');
        $this->add_action('admin_enqueue_scripts', $controller, 'enqueue_scripts');
    }

    /**
     * Register public-facing hooks
     *
     * @return void
     * @since 1.0.0
     * @version 1.0.0
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    private function register_public_hooks(): void
    {

        $controller = new Site();

        $this->add_shortcode(Functions::get_shortcode(), $controller, 'currency_shortcode');
    }

    /**
     * Register AJAX hooks
     *
     * @return void
     * @since 1.0.0
     * @version 1.0.0
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    private function register_ajax_hooks(): void
    {
        $controller = new Ajax();

        $this->add_action('wp_ajax_' . Functions::get_plugin_slug('-rate-enable'), $controller, 'ajaxDoRate');
        $this->add_action('wp_ajax_' . Functions::get_plugin_slug('-rate-remind'), $controller, 'ajaxDoRemindRate');
        $this->add_action('wp_ajax_' . Functions::get_plugin_slug('-rate-cancel'), $controller, 'ajaxDoCancelRate');

        $this->add_action('wp_ajax_' . Functions::get_plugin_slug('-analytics-enable'), $controller, 'ajaxDoAnalytics');
        $this->add_action('wp_ajax_' . Functions::get_plugin_slug('-analytics-remind'), $controller, 'ajaxDoRemindAnalytics');
        $this->add_action('wp_ajax_' . Functions::get_plugin_slug('-analytics-cancel'), $controller, 'ajaxDoCancelAnalytics');
    }

    /**
     * Call the appropriate WordPress registration hooks
     *
     * Allows us to hook the functions to this class so that we have a unified api
     *
     * @param string $name Name of function to call.
     * @param array $arguments Arguments passed to function.
     *
     * @return mixed #type intentionally left out.
     * @throws BadMethodCallException When called function does not exist or has missing arguments.
     * @since 1.0.0
     * @version 1.0.5
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    public function __call(string $name, array $arguments)
    {

        assert(count($arguments) >= 2, new BadMethodCallException('You need to provide at least two arguments for ' . $name));

        switch ($name) {
            case 'register_activation_hook':
            case 'register_deactivation_hook':
            case 'register_uninstall_hook':
                // Hook file.
                $file = array_shift($arguments);

                assert(file_exists($file), new BadMethodCallException('Please provide a valid file path for ' . $name));

                // Function to call.
                $component = array_shift($arguments);
                if (is_array($component) || (is_string($component) && is_callable($component))) {
                    $callable = $component;
                } else {
                    $callable = array($component, array_shift($arguments));
                }
                unset($component);

                assert(is_callable($callable, true), new BadMethodCallException('Please provide a callable function for ' . $name));

                // Register Hook.
                $name($file, $callable);
                break;
            case 'add_filter':
            case 'add_action':
                // The hook.
                $hook = array_shift($arguments);

                assert(is_string($hook), new BadMethodCallException('Please provide the name of the hook for ' . $name));

                // Function to call.
                $component = array_shift($arguments);
                if (is_array($component) || (is_string($component) && is_callable($component))) {
                    $callable = $component;
                } else {
                    $callable = array($component, array_shift($arguments));
                }
                unset($component);

                assert(is_callable($callable, true), new BadMethodCallException('Please provide a callable function for ' . $name));

                // Function Priority.
                $priority = array_shift($arguments);
                if (is_null($priority)) {
                    $priority = 10;
                }

                assert(is_numeric($priority), new BadMethodCallException('Priority should be numeric for ' . $name));

                // Arguments Count.
                $args = array_shift($arguments);
                if (is_null($args)) {
                    $args = 1;
                }

                assert(is_numeric($args), new BadMethodCallException('Number of arguments should be numeric for ' . $name));

                // Register hook.
                return $name($hook, $callable, $priority, $args);
            case 'add_shortcode':
                // The shortcode tag.
                $tag = array_shift($arguments);

                assert(is_string($tag), new BadMethodCallException('Please provide the shortcode tag for ' . $name));

                // Function to call.
                $component = array_shift($arguments);
                if (is_array($component) || (is_string($component) && is_callable($component))) {
                    $callable = $component;
                } else {
                    $callable = array($component, array_shift($arguments));
                }
                unset($component);

                assert(is_callable($callable, true), new BadMethodCallException('Please provide a callable function for ' . $name));

                // Register shortcode.
                return $name($tag, $callable);

            default:
                throw new BadMethodCallException('The method ' . $name . ' does not exist');
        }
    }
}
