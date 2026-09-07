<?php
/**
 * AJAX controller
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

/**
 * AJAX controller class
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */
class Ajax extends BaseController
{
    /**
     * Set reminder for half a year and send redirect link
     * 
     * @since 1.1.3
     *
     * @return void
     */
    public function ajaxDoRate(): void
    {
        if (check_ajax_referer(Functions::get_plugin_slug('-rate-enable'), "_ajax_nonce", false) !== false) {
            set_transient(Functions::get_plugin_slug("-rate"), true, defined("MONTH_IN_SECONDS") ? MONTH_IN_SECONDS * 6 : YEAR_IN_SECONDS / 2);

            Logger::logEvent("rate_plugin_accepted");

            wp_send_json(array(
                "redirect" => "https://wordpress.org/support/plugin/" . Functions::get_plugin_slug() . "/reviews/"
            ), 200);
        }
    }

    /**
     * Remind again in a week
     * 
     * @since 1.1.3
     *
     * @return void
     */
    public function ajaxDoRemindRate(): void
    {
        if (check_ajax_referer(Functions::get_plugin_slug('-rate-remind'), "_ajax_nonce", false) !== false) {
            set_transient(Functions::get_plugin_slug("-rate"), true, WEEK_IN_SECONDS);

            Logger::logEvent("rate_plugin_remind");

            wp_send_json(array(
                "success" => true
            ), 200);
        }
    }

    /**
     * Remind in a year
     * 
     * @since 1.1.3
     *
     * @return void
     */
    public function ajaxDoCancelRate(): void
    {
        if (check_ajax_referer(Functions::get_plugin_slug('-rate-cancel'), "_ajax_nonce", false) !== false) {
            set_transient(Functions::get_plugin_slug("-rate"), true, YEAR_IN_SECONDS);

            Logger::logEvent("rate_plugin_declined");

            wp_send_json(array(
                "success" => true
            ), 200);
        }
    }

    /**
     * Enable analytics
     *
     * @return void
     * @since 1.0.0
     *
     */
    public function ajaxDoAnalytics(): void
    {

        if (check_ajax_referer(Functions::get_plugin_slug('-analytics-enable'), '_ajax_nonce', false) !== false) {
            // remind again in three months
            set_transient(Functions::get_plugin_slug('-analytics'), true, YEAR_IN_SECONDS / 2);

            Logger::logEvent("analytics_plugin_accepted");

            wp_send_json(
                array(
                    'redirect' => add_query_arg(["page" => Functions::get_plugin_slug("-about")], admin_url("admin.php")),
                ),
                200
            );
        }
    }

    /**
     * Remind again in a week
     *
     * @return void
     * @since 1.0.0
     *
     */
    public function ajaxDoRemindAnalytics(): void
    {

        if (check_ajax_referer(Functions::get_plugin_slug('-analytics-remind'), '_ajax_nonce', false) !== false) {
            // remind after a week
            set_transient(Functions::get_plugin_slug('-analytics'), true, WEEK_IN_SECONDS);

            Logger::logEvent("analytics_plugin_remind");

            wp_send_json(
                array(
                    'success' => true,
                ),
                200
            );
        }
    }

    /**
     * Remind in a year
     *
     * @return void
     * @since 1.0.0
     *
     */
    public function ajaxDoCancelAnalytics(): void
    {
        if (check_ajax_referer(Functions::get_plugin_slug('-analytics-cancel'), '_ajax_nonce', false) !== false) {
            set_transient(Functions::get_plugin_slug('-analytics'), true, YEAR_IN_SECONDS);

            Logger::logEvent("analytics_plugin_declined");

            wp_send_json(
                array(
                    'success' => true,
                ),
                200
            );
        }
    }
}
