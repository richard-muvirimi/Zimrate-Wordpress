<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @package    Zimrate
 * @subpackage Zimrate/includes
 *
 * @link       https://tyganeutronics.com
 * @since      1.0.0
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @package    Zimrate
 * @subpackage Zimrate/includes
 *
 * @author     Richard Muvirimi <tygalive@gmail.com>
 *
 * @since      1.0.0
 */
class Zimrate
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @access   protected
     * @var Zimrate_Loader $loader Maintains and registers all hooks for the plugin.
     * @since    1.0.0
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @access   protected
     * @var string $plugin_name The string used to uniquely identify this plugin.
     * @since    1.0.0
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @access   protected
     * @var string $version The current version of the plugin.
     * @since    1.0.0
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('ZIMRATE_VERSION')) {
            $this->version = ZIMRATE_VERSION;
        } else {
            $this->version = '1.0.0';
        }

        $this->plugin_name = 'zimrate';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_plugins_hooks();
        $this->define_public_hooks();
        $this->define_ajax_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Zimrate_Loader. Orchestrates the hooks of the plugin.
     * - Zimrate_i18n. Defines internationalization functionality.
     * - Zimrate_Admin. Defines all hooks for the admin area.
     * - Zimrate_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @access   private
     * @since    1.0.0
     */
    private function load_dependencies()
    {
        /**
         * The file housing common plugin functions
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/functions.php';

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-zimrate-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-zimrate-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-zimrate-admin.php';

        /**
         * The class responsible for defining all actions that occur in the plugins
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'plugins/class-zimrate-plugin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-zimrate-public.php';

        /**
         * The class responsible for defining all actions that occur in the ajax-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'ajax/class-zimrate-ajax.php';

        $this->loader = new Zimrate_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Zimrate_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @access   private
     * @since    1.0.0
     */
    private function set_locale()
    {
        $plugin_i18n = new Zimrate_i18n();

        $this->loader->add_action(
            'plugins_loaded',
            $plugin_i18n,
            'load_plugin_textdomain'
        );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @access   private
     * @since    1.0.0
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Zimrate_Admin(
            $this->get_plugin_name(),
            $this->get_version()
        );

        $this->loader->add_action(
            'admin_enqueue_scripts',
            $plugin_admin,
            'enqueue_styles'
        );
        $this->loader->add_action(
            'admin_enqueue_scripts',
            $plugin_admin,
            'enqueue_scripts'
        );

        //on init admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'on_admin_menu');

        //register options
        $this->loader->add_action(
            'admin_init',
            $plugin_admin,
            'register_setting'
        );
        $this->loader->add_action(
            'admin_init',
            $plugin_admin,
            'add_settings_section'
        );
        $this->loader->add_action(
            'admin_init',
            $plugin_admin,
            'add_settings_fields'
        );

        //add zimbabwean currency
        $this->loader->add_filter(
            'woocommerce_currencies',
            $plugin_admin,
            'add_woocommerce_currencies'
        );
        $this->loader->add_filter(
            'woocommerce_currency_symbols',
            $plugin_admin,
            'add_woocommerce_currency_symbols'
        );

        // request rating
        $this->loader->add_filter('admin_notices', $plugin_admin, 'show_rating');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @access private
     * @since 1.1.3
     */
    private function define_ajax_hooks()
    {

        $plugin_ajax = new ZimRate_Ajax($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_ajax_' . $this->get_plugin_name() . '-rate', $plugin_ajax, 'ajaxDoRate');
        $this->loader->add_action('wp_ajax_' . $this->get_plugin_name() . '-remind', $plugin_ajax, 'ajaxDoRemind');
        $this->loader->add_action('wp_ajax_' . $this->get_plugin_name() . '-cancel', $plugin_ajax, 'ajaxDoCancel');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @access   private
     * @since    1.0.0
     */
    private function define_public_hooks()
    {
        $plugin_public = new Zimrate_Public(
            $this->get_plugin_name(),
            $this->get_version()
        );

        $this->loader->add_shortcode(
            zimrate_get_shortcode(),
            $plugin_public,
            'currency_shortcode'
        );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @access   private
     * @since    1.0.0
     * @version 1.1.0
     */
    private function define_plugins_hooks()
    {
        $plugin_plugins = new Zimrate_Plugins(
            $this->get_plugin_name(),
            $this->get_version()
        );

        //modify requests before delivery to plugin
        $this->loader->add_filter(
            'http_response',
            $plugin_plugins,
            'inject_http_response',
            10,
            3
        );

        $this->loader->add_action(
            'plugins_loaded',
            $plugin_plugins,
            'plugins_loaded'
        );

        //currency-switcher-woocommerce
        $this->loader->add_filter(
            'alg_wc_cs_get_exchange_rate',
            $plugin_plugins,
            'currency_switcher_woocommerce',
            10,
            4
        );

        //currency-exchange-for-woocommerce
        $this->loader->add_filter(
            'berocket_ce_apis_sanitize_oer',
            $plugin_plugins,
            'currency_exchange_for_woocommerce'
        );
        $this->loader->add_filter(
            'berocket_ce_apis_sanitize_currencylayer',
            $plugin_plugins,
            'currency_exchange_for_woocommerce'
        );
        $this->loader->add_filter(
            'berocket_ce_apis_sanitize_fixerio',
            $plugin_plugins,
            'currency_exchange_for_woocommerce'
        );
        $this->loader->add_filter(
            'berocket_ce_apis_sanitize_floatrates',
            $plugin_plugins,
            'currency_exchange_for_woocommerce'
        );

        //woocommerce-currency-switcher
        $this->loader->add_filter(
            'woocs_add_custom_rate',
            $plugin_plugins,
            'woocs_add_custom_rate',
            10,
            3
        );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     *
     * @return string The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     *
     * @return Zimrate_Loader Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     *
     * @return string The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}