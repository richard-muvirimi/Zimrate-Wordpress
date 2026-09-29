<?php
/**
 * Logger helper for analytics
 *
 * @package    Zimrate
 * @subpackage Zimrate/Helpers
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 */

namespace RichardMuvirimi\Zimrate\Helpers;

use Zimrate_ClientIP as ClientIP;
use Throwable;

/**
 * Class to handle plugin logging functions
 *
 * Sends plugin lifecycle events to GA4 over the Measurement Protocol, which is
 * a single JSON post.  Mirrors the api's own analytics middleware: no raw ip,
 * no user_id for anonymous callers, no page_view for something that is not a
 * page, and sessions that expire.
 *
 * @package    Zimrate
 * @subpackage Zimrate/Helpers
 *
 * @author Richard Muvirimi <richard@tyganeutronics.com>
 * @since 1.0.0
 * @version 1.1.6
 */
class Logger
{
    /**
     * Measurement Protocol collection endpoint
     *
     * @since 1.1.6
     */
    const ENDPOINT = 'https://www.google-analytics.com/mp/collect';

    /**
     * Sessions rotate on this window, GA4's own web sessions time out at 30 min
     *
     * @since 1.1.6
     */
    const SESSION_WINDOW = 30 * MINUTE_IN_SECONDS;

    /**
     * Log events to Google analytics
     *
     * Fires and forgets, it must never delay or fail whatever called it.
     *
     * @param string $event
     * @return void
     * @since 1.0.0
     * @version 1.1.6
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    public static function logEvent(string $event): void
    {
        if (get_option(Functions::get_plugin_slug("-analytics"), "off") !== "on") {
            return;
        }

        try {
            $credentials = self::fetchAnalyticsCredentials();

            if (!is_array($credentials)) {
                return;
            }

            $clientId = self::getClientId();

            // same identity bucketed into windows, so sessions expire instead
            // of every caller having one session that never ends
            $sessionId = substr($clientId, 0, 16) . '.' . intval(floor(time() / self::SESSION_WINDOW));

            $payload = array(
                // no user_id: that field means a known, signed in person, and
                // reporting anonymous admins as identified users misrepresents
                // the data
                'client_id' => $clientId,
                'user_properties' => array(
                    'php_version' => array('value' => PHP_VERSION),
                    'wordpress_version' => array('value' => get_bloginfo('version')),
                    'plugin_version' => array('value' => ZIMRATE_VERSION),
                ),
                // one custom event, no page_view: a plugin lifecycle event is
                // not a page view, and emitting one puts it in the pages report
                'events' => array(
                    array(
                        'name' => $event,
                        'params' => array(
                            // without session_id and engagement_time_msec GA4
                            // leaves the event out of session and realtime
                            // reports
                            'session_id' => $sessionId,
                            'engagement_time_msec' => '100',
                            'language' => get_user_locale(),
                        ),
                    ),
                ),
            );

            wp_remote_post(
                add_query_arg(
                    array(
                        'measurement_id' => $credentials['MEASUREMENT_ID'],
                        'api_secret' => $credentials['MEASUREMENT_PROTOCOL_API_SECRET'],
                    ),
                    self::ENDPOINT
                ),
                array(
                    'headers' => array('Content-Type' => 'application/json'),
                    'body' => wp_json_encode($payload),
                    'blocking' => false,
                    'timeout' => 2,
                )
            );
        } catch (Throwable $e) {
            // analytics is never worth breaking the caller for
        }
    }

    /**
     * Fetch analytics credentials
     *
     * @return array|false
     * @since 1.0.0
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    public static function fetchAnalyticsCredentials()
    {
        $analyticsKeys = get_transient(Functions::get_plugin_slug("analytics-keys"));

        if (!is_array($analyticsKeys)) {

            $url = add_query_arg([
                "version" => ZIMRATE_VERSION
            ],
                "https://tyganeutronics.com/versionify/api/v1/b08cbde0b6f164a25097cdc54825fbe4"
            );

            $response = wp_remote_get($url);

            if (!is_wp_error($response)) {
                $data = wp_remote_retrieve_body($response);

                list("data" => $analyticsKeys) = json_decode($data, true);

                set_transient(Functions::get_plugin_slug("analytics-keys"), $analyticsKeys, DAY_IN_SECONDS);
            }
        }

        return $analyticsKeys;
    }

    /**
     * Get users user agent to forward for analytics collection
     *
     * @return string
     * @since 1.0.0
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    public static function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? apply_filters('http_headers_useragent', 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'), site_url());
    }

    /**
     * Pseudonymous, stable identity for the caller.
     *
     * The raw ip must never be sent: Google's Measurement Protocol policy
     * forbids uploading personal data and an ip counts as such.  It is hashed
     * with a salt because the ipv4 space is small enough that an unsalted
     * digest is reversible by brute force.
     *
     * @return string
     * @since 1.1.6
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    public static function getClientId(): string
    {
        return substr(hash('sha256', self::getSalt() . ClientIP::get() . self::getUserAgent()), 0, 32);
    }

    /**
     * Salt for the caller hash, made once and kept.
     *
     * A salt made per request would split one caller into a new user every
     * time, which over counts rather than leaks, but a kept one is what makes
     * the identity stable.
     *
     * @return string
     * @since 1.1.6
     *
     * @author Richard Muvirimi <richard@tyganeutronics.com>
     */
    private static function getSalt(): string
    {
        $key = Functions::get_plugin_slug('-analytics-salt');

        $salt = get_option($key, '');

        if ($salt === '') {
            $salt = bin2hex(random_bytes(16));

            add_option($key, $salt, '', 'no');
        }

        return $salt;
    }
}
