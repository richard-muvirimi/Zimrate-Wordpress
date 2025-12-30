<?php
/**
 * Admin controller
 *
 * @package    Zimrate
 * @subpackage Zimrate/Controller
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */

namespace RichardMuvirimi\Zimrate\Controller;

use RichardMuvirimi\Zimrate\Helpers\Functions;
use RichardMuvirimi\Zimrate\Helpers\Logger;
use RichardMuvirimi\Zimrate\Helpers\Template;

/**
 * Admin controller class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class Admin extends BaseController
{
    /**
     * Register the stylesheets for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_styles(): void
    {
        $screen = get_current_screen();
        
        // Enqueue dashboard styles on zimrate dashboard page
        if ($screen && $screen->id === 'toplevel_page_zimrate-dashboard') {
            wp_enqueue_style(
                Functions::get_plugin_slug() . '-dashboard',
                Template::get_style_url('admin-dashboard.css'),
                array(),
                Functions::get_plugin_version()
            );
            
            // Enqueue plugin-install styles for Thickbox modal
            wp_enqueue_style('plugin-install');
        }

        // Register rating and about styles (enqueued conditionally by showAdminNotices/renderAboutPage)
        wp_register_style(Functions::get_plugin_slug() . "-rate", Template::get_style_url('admin-rating.css'), array(), Functions::get_plugin_version());
        wp_register_style(Functions::get_plugin_slug() . "-about", Template::get_style_url('admin-about.css'), array(), Functions::get_plugin_version());
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts(): void
    {
        $screen = get_current_screen();
        
        // Enqueue plugin-install scripts on dashboard page for Thickbox modal
        if ($screen && $screen->id === 'toplevel_page_zimrate-dashboard') {
            wp_enqueue_script('plugin-install');
            add_thickbox();
        }

        // Register rating script and localize it (enqueued conditionally by showAdminNotices)
        wp_register_script(Functions::get_plugin_slug() . "-rate", Template::get_script_url('admin-rating.js'), array('jquery'), Functions::get_plugin_version(), false);
        wp_localize_script(Functions::get_plugin_slug() . "-rate", "zimrate", array(
            "ajax_url" => admin_url('admin-ajax.php'),
            "name" => Functions::get_plugin_slug()
        ));
    }

    /**
     * On create the about menu
     *
     * @since 1.0.0
     */
    public function on_admin_menu(): void
    {
        add_menu_page(
            __('ZimRate', Functions::get_plugin_slug()),
            __('ZimRate', Functions::get_plugin_slug()),
            'manage_options',
            'zimrate-dashboard',
            array($this, 'zimrate'),
            Template::get_template_url('img/logo.svg')
        );

        add_submenu_page(
            'zimrate-dashboard',
            __('Options', Functions::get_plugin_slug()),
            __('Options', Functions::get_plugin_slug()),
            'manage_options',
            'zimrate-options',
            array($this, 'zimrateOptions')
        );

        add_submenu_page(
            'zimrate-dashboard',
            __('About', Functions::get_plugin_slug()),
            __('About', Functions::get_plugin_slug()),
            'manage_options',
            Functions::get_plugin_slug("-about"),
            array($this, 'renderAboutPage')
        );
    }

    /**
     * Render the main page
     *
     * @since 1.0.0
     */
    public function zimrate(): void
    {
        $plugin = get_plugin_data(ZIMRATE_FILE);
        
        echo Template::get_template(Functions::get_plugin_slug('-admin-display'), compact('plugin'), 'admin-dashboard.php');
    }

    /**
     * Render the options page
     *
     * @since 1.0.0
     */
    public function zimrateOptions(): void
    {
        echo Template::get_template(Functions::get_plugin_slug('-admin-options'), array(), 'settings-options.php');
    }

    /**
     * Register all app options
     *
     * @since 1.0.0
     */
    public function register_setting(): void
    {
        register_setting('zimrate-options', 'zimrate-prefer', array(
            'default' => 'mean',
            'sanitize_callback' => array(Functions::class, 'clear_rate_cache')
        ));
        register_setting('zimrate-options', 'zimrate-interval', array(
            'default' => 'hourly',
            'sanitize_callback' => array(Functions::class, 'clear_rate_cache')
        ));
        register_setting('zimrate-options', 'zimrate-cushion', array(
            'type' => 'integer',
            'default' => 1,
        ));
        register_setting('zimrate-options', 'zimrate-currencies', array(
            'default' => 'RBZ',
        ));
    }

    /**
     * Add a section for options
     *
     * @since 1.0.0
     */
    public function add_settings_section(): void
    {
        add_settings_section(
            'zimrate-options-section',
            __('ZimRate Options', Functions::get_plugin_slug()),
            function () {
                $title = __(
                    'ZimRate exchange rate retrieval options.',
                    Functions::get_plugin_slug()
                );

                $this->print_html_label($title);
            },
            'zimrate-options'
        );
    }

    /**
     * Add plugin settings fields
     *
     * @since 1.0.0
     */
    public function add_settings_fields(): void
    {
        add_settings_field(
            'zimrate-prefer',
            __('Zimrate Rate', Functions::get_plugin_slug()),
            function () {
                $attr = array(
                    'name' => 'zimrate-prefer',
                    'id' => 'zimrate-prefer',
                    'required' => 'true',
                    'style' => 'width: 100%;'
                );

                $options = array(
                    'max' => 'Maximum',
                    'mean' => 'Average',
                    'min' => 'Minimum',
                );

                $this->print_html_select(
                    $attr,
                    $options,
                    get_option('zimrate-prefer', 'mean'),
                    __('The exchange rate value to use.', Functions::get_plugin_slug())
                );
            },
            'zimrate-options',
            'zimrate-options-section',
            array('label_for' => 'zimrate-prefer')
        );

        add_settings_field(
            'zimrate-interval',
            __('Refresh Interval', Functions::get_plugin_slug()),
            function () {
                $attr = array(
                    'name' => 'zimrate-interval',
                    'id' => 'zimrate-interval',
                    'required' => 'true',
                    'style' => 'width: 100%;'
                );

                $this->print_html_select(
                    $attr,
                    Functions::intervals(),
                    get_option('zimrate-interval', MINUTE_IN_SECONDS),
                    __('The refresh interval (Rates will be cached for this long)', Functions::get_plugin_slug())
                );
            },
            'zimrate-options',
            'zimrate-options-section',
            array('label_for' => 'zimrate-interval')
        );

        add_settings_field(
            'zimrate-cushion',
            __('Rate Cushion', Functions::get_plugin_slug()),
            function () {
                $attr = array(
                    'type' => 'number',
                    'value' => intval(get_option('zimrate-cushion', 5)),
                    'name' => 'zimrate-cushion',
                    'id' => 'zimrate-cushion',
                    'required' => 'true',
                    'style' => 'max-width: 25rem; width: 100%;'
                );

                $this->print_html_input(
                    $attr,
                    __(
                        'The percentage value to apply on retrieved rate as a cushion.',
                        Functions::get_plugin_slug()
                    )
                );
            },
            'zimrate-options',
            'zimrate-options-section',
            array('label_for' => 'zimrate-cushion')
        );

        add_settings_field(
            'zimrate-currencies',
            __('Preferred Rate', Functions::get_plugin_slug()),
            function () {
                $attr = array(
                    'name' => 'zimrate-currencies',
                    'id' => 'zimrate-currencies',
                    'required' => 'true',
                    'style' => 'width: 100%;'
                );

                $this->print_html_select(
                    $attr,
                    Functions::supported_currencies(),
                    Functions::get_selected_currency(),
                    __('The preferred exchange rate', Functions::get_plugin_slug())
                );
            },
            'zimrate-options',
            'zimrate-options-section',
            array('label_for' => 'zimrate-currencies')
        );
    }

    /**
     * Converts a key value list to an attribute string
     *
     * @since 1.0.0
     * @param array $attr
     * @return string
     */
    private function array_to_attributes(array $attr): string
    {
        $attributes = '';
        $delimeter = '';

        foreach ($attr as $attribute => $value) {
            if (!empty($value)) {
                $attributes .=
                    $delimeter .
                    esc_attr($attribute) .
                    '="' .
                    esc_attr($value) .
                    '"';
                $delimeter = ' ';
            }
        }

        return $attributes;
    }

    /**
     * Print an html input element
     *
     * @since 1.0.0
     * @param array  $attributes
     * @param string $desc
     */
    private function print_html_input(array $attributes, string $desc = ''): void
    {
        print '<input ' . $this->array_to_attributes($attributes) . '>';

        if ($desc) {
            $this->print_html_label($desc, array('class' => 'description'));
        }
    }

    /**
     * Print an html select element
     *
     * @since 1.0.0
     * @param array  $attributes
     * @param array  $options
     * @param mixed  $selected
     * @param string $desc
     */
    private function print_html_select(
        array $attributes,
        array $options,
        $selected = true,
        string $desc = ''
    ): void {
        print '<select ' . $this->array_to_attributes($attributes) . '>';

        foreach ($options as $key => $value) {
            print '<option value="' .
                esc_attr($key) .
                '" ' .
                selected($key, $selected, false) .
                '>';
            print esc_html($value);
            print '</option>';
        }

        print '</select>';

        if ($desc) {
            $this->print_html_label($desc, array('class' => 'description'));
        }
    }

    /**
     * Print html text inside a paragraph element
     *
     * @since 1.0.0
     */
    public function print_html_label(string $label, array $attributes = array()): void
    {
        print '<p ' .
            $this->array_to_attributes($attributes) .
            '>' .
            $label .
            '</p>';
    }

    /**
     * Add Zimbabwean Currency to woocommerce
     *
     * @since 1.0.0
     * @version 1.1.0
     * @param  array   $currencies
     * @return array
     */
    public function add_woocommerce_currencies(array $currencies): array
    {
        if (!isset($currencies[Functions::get_iso()])) {
            $currencies[Functions::get_iso()] = __(
                'Zimbabwean Dollar',
                Functions::get_plugin_slug()
            );
        }

        return $currencies;
    }

    /**
     * Add Zimbabwean Currency Symbol to woocommerce
     *
     * @since 1.0.0
     * @version 1.1.0
     * @param  array $currencies
     * @return array
     */
    public function add_woocommerce_currency_symbols(array $currencies): array
    {
        if (!isset($currencies[Functions::get_iso()])) {
            $currencies[Functions::get_iso()] = '&#36;';
        }

        return $currencies;
    }

    /**
     * Show rating request
     *
     * @since 1.1.3
     * @version 1.1.3
     * @return void
     */
    public function showAdminNotices(): void
    {
        /**
         * Request Rating
         */
        if (boolval(get_transient(Functions::get_plugin_slug("-rate"))) === false) {
            wp_enqueue_script(Functions::get_plugin_slug("-rate"));
            wp_enqueue_style(Functions::get_plugin_slug("-rate"));

            echo Template::get_template(Functions::get_plugin_slug('-admin-notice-rating'), array(), 'admin-notice-rating.php');

            Logger::logEvent("request_plugin_rating");
        }

        if (get_option(Functions::get_plugin_slug("-analytics"), "off") !== "on" && boolval(get_transient(Functions::get_plugin_slug('-analytics'))) === false) {
            wp_enqueue_script(Functions::get_plugin_slug("-rate"));
            wp_enqueue_style(Functions::get_plugin_slug("-rate"));

            echo Template::get_template(Functions::get_plugin_slug('-admin-notice-analytics'), array(), 'admin-notice-analytics.php');

            Logger::logEvent("request_plugin_analytics");
        }
    }

    /**
     * Register plugin options
     *
     * @return void
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     * @since 1.0.0
     */
    public function registerOptions(): void
    {

        register_setting(
            Functions::get_plugin_slug("-about"),
            Functions::get_plugin_slug("-analytics"),
            array("sanitize_callback" => "sanitize_text_field")
        );

        add_settings_section(
            Functions::get_plugin_slug("-settings"),
            __("Settings", Functions::get_plugin_slug()),
            array($this, "renderSectionHeader"),
            Functions::get_plugin_slug("-about")
        );

        add_settings_field(
            Functions::get_plugin_slug("-analytics"),
            __('Collect Anonymous Usage Data', Functions::get_plugin_slug()),
            array($this, 'renderInputField'),
            Functions::get_plugin_slug("-about"),
            Functions::get_plugin_slug("-settings"),
            array(
                'label_for' => Functions::get_plugin_slug("-analytics"),
                'class' => Functions::get_plugin_slug( '-row'),
                "value" => get_option(Functions::get_plugin_slug("-analytics"), "off"),
                'description' => Template::get_template(Functions::get_plugin_slug("-about-analytics-disclaimer"), [], "about-analytics-disclaimer.php"),
                "type" => "checkbox",
            )
        );
    }

    /**
     * Display the settings header
     *
     * @return void
     * @since 1.0.0
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    public function renderSectionHeader(): void
    {
        echo Template::get_template(Functions::get_plugin_slug("-about-section-header"), [], "about-section-header.php");
    }

    /**
     * Display input field
     *
     * @param array $args
     *
     * @return void
     * @since 1.0.0
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    public function renderInputField(array $args): void
    {
        echo Template::get_template(Functions::get_plugin_slug("-about-input-field"), $args, "about-input-field.php");
    }

    /**
     * Render the about page
     *
     * @return void
     * @since 1.0.0
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    public function renderAboutPage(): void
    {

        Logger::logEvent("view_about_page");

        wp_enqueue_style(Functions::get_plugin_slug("-about"));

        $plugin = get_plugin_data(ZIMRATE_FILE);

        echo Template::get_template(Functions::get_plugin_slug("admin-about"), compact("plugin"), "admin-about.php");
    }
}

