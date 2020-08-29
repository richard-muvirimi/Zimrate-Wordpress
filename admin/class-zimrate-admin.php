<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Zimrate
 * @subpackage Zimrate/admin
 *
 * @link       https://tyganeutronics.com
 * @since      1.0.0
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Zimrate
 * @subpackage Zimrate/admin
 *
 * @author     Richard Muvirimi <tygalive@gmail.com>
 */
class Zimrate_Admin
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
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        switch (get_current_screen()->id) {
            case 'toplevel_page_zimrate-dashboard':
                wp_enqueue_style($this->plugin_name . '-dashboard', plugin_dir_url(__FILE__) . 'css/zimrate-dashboard.css', array(), $this->version, 'all');
            default:
                wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/zimrate-admin.css', array(), $this->version, 'all');
        }

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        switch (get_current_screen()->id) {
            case 'toplevel_page_zimrate-dashboard':
                wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/zimrate-admin.js', array(), $this->version, false);
                break;
        }

    }

    /**
     * On create the about menu
     *
     * @return void
     */
    public function on_admin_menu()
    {
        add_menu_page(__('ZimRate', $this->plugin_name), __('ZimRate', $this->plugin_name), 'manage_options', 'zimrate-dashboard', array($this, 'zimrate'), plugin_dir_url(__FILE__) . 'img/logo.svg');

        //options
        add_submenu_page('zimrate-dashboard', __('Options', $this->plugin_name), __('Options', $this->plugin_name), 'manage_options', 'zimrate-options', array($this, 'zimrateOptions'));

    }

    /**
     * Render the main page
     */
    public function zimrate()
    {

        include plugin_dir_path(__FILE__) . 'partials/zimrate-admin-display.php';
    }

    /**
     * Render the options page
     */
    public function zimrateOptions()
    {
        include plugin_dir_path(__FILE__) . 'partials/zimrate-options.php';
    }

    /**
     * Register all app options
     */
    public function register_setting()
    {
        register_setting('zimrate-options', 'zimrate-prefer', array('default' => 'mean'));
        register_setting('zimrate-options', 'zimrate-interval', array('default' => 'hourly'));
        register_setting('zimrate-options', 'zimrate-cushion', array('type' => 'integer', 'default' => 1));
        register_setting('zimrate-options', 'zimrate-currencies', array('default' => 'RBZ'));
    }

    /**
     * Add a section for options
     */
    public function add_settings_section()
    {

        add_settings_section(
            'zimrate-options-section',
            __('ZimRate Options', $this->plugin_name),
            function () {

                $title = __('ZimRate exchange rate retrievial options.', $this->plugin_name);

                $this->print_html_label($title);
            },
            'zimrate-options'
        );
    }

    /**
     * Converts a key value list to ann attribute string
     *
     * @param array $attr
     */
    private function array_to_attributes($attr)
    {
        $attributes = '';
        $delimeter  = '';

        foreach ($attr as $attribute => $value) {

            if (!empty($value)) {
                $attributes .= $delimeter . esc_attr($attribute) . '="' . esc_attr($value) . '"';
                $delimeter = ' ';
            }

        }

        return $attributes;
    }

    /**
     * Print an html input element
     *
     * @param array  $attributes
     * @param string $desc
     */
    private function print_html_input($attributes, $desc = '')
    {

        print('<input ' . $this->array_to_attributes($attributes) . '>');

        if ($desc) {
            $this->print_html_label($desc, array('class' => 'description'));
        }

    }

    /**
     * Print an html select element
     *
     * @param array  $attributes
     * @param array  $options
     * @param string $selecte
     * @param string $desc
     */
    private function print_html_select($attributes, $options, $selected = true, $desc = '')
    {

        print('<select ' . $this->array_to_attributes($attributes) . '>');

        foreach ($options as $key => $value) {
            print('<option value="' . esc_attr($key) . '" ' . selected($key, $selected, false) . '>');
            print(esc_html($value));
            print('</option>');
        }

        print('</select>');

        if ($desc) {
            $this->print_html_label($desc, array('class' => 'description'));
        }

    }

    /**
     * Add plugin settings fields
     */
    public function add_settings_fields()
    {
        add_settings_field(
            'zimrate-prefer',
            __('Zimrate Rate', $this->plugin_name),
            function () {

                $attr = array(
                    'name' => 'zimrate-prefer',
                    'id' => 'zimrate-prefer',
                    'required' => 'true'
                );

                $options = array(
                    'max' => 'Maximum',
                    'mean' => 'Average',
                    'min' => 'Minimum'
                );

                $this->print_html_select($attr, $options, get_option('zimrate-prefer', 'mean'), __('The exchange rate value to use.', $this->plugin_name));

            },
            'zimrate-options',
            'zimrate-options-section',
            array('label_for' => 'zimrate-prefer')
        );

        add_settings_field(
            'zimrate-interval',
            __('Refresh Interval', $this->plugin_name),
            function () {

                $attr = array(
                    'name' => 'zimrate-interval',
                    'id' => 'zimrate-interval',
                    'required' => 'true'
                );

                $this->print_html_select($attr, zimrate_intervals(), get_option('zimrate-interval', HOUR_IN_SECONDS), __('The refresh interval', $this->plugin_name));

            },
            'zimrate-options',
            'zimrate-options-section',
            array('label_for' => 'zimrate-interval')
        );

        add_settings_field(
            'zimrate-cushion',
            __('Rate Cushion', $this->plugin_name),
            function () {

                $attr = array(
                    'type' => 'number',
                    'value' => intval(get_option('zimrate-cushion', 1)),
                    'name' => 'zimrate-cushion',
                    'id' => 'zimrate-cushion',
                    'required' => 'true'
                );

                $this->print_html_input($attr, __('The percentage value to apply on retrived rate as a cushion.', $this->plugin_name));

            },
            'zimrate-options',
            'zimrate-options-section',
            array('label_for' => 'zimrate-cushion')
        );

        add_settings_field(
            'zimrate-currencies',
            __('Preferred Rate', $this->plugin_name),
            function () {

                $attr = array(
                    'name' => 'zimrate-currencies',
                    'id' => 'zimrate-currencies',
                    'required' => 'true'
                );

                $this->print_html_select($attr, zimrate_supported_currencies(), zimrate_get_selected_currency(), __('The preferred exchange rate', $this->plugin_name));

            },
            'zimrate-options',
            'zimrate-options-section',
            array('label_for' => 'zimrate-currencies')
        );
    }

    /**
     * Print html text inside a paragram element
     */
    public function print_html_label($label, $attributes = array())
    {
        print('<p ' . $this->array_to_attributes($attributes) . '>' . $label . '</p>');
    }

    /**
     * Add Zimbabwean Currency to woocommerce
     *
     * @param  array   $currencies
     * @return array
     */
    public function add_woocommerce_currencies($currencies)
    {

        $currencies[zimrate_get_iso()] = __('Zimbabwean Dollar', $this->plugin_name);

        return $currencies;
    }

    /**
     * Add Zimbabwean Currency Symbol to woocommerce
     *
     * @param  array   $currencies
     * @return array
     */
    public function add_woocommerce_currency_symbols($currencies)
    {

        $currencies[zimrate_get_iso()] = '&#36;';

        return $currencies;
    }

}