<?php
/**
 * Phpunit bootstrap file for running tests
 *
 * phpcs:disable WordPress.VIP.RestrictedFunctions.file_get_contents_file_get_contents
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
 */

$root = __DIR__;

do {
    $root = dirname($root);

    // break if we don't find the file
    if (strrpos($root, DIRECTORY_SEPARATOR, intval(strpos($root, PATH_SEPARATOR))) < 3) {
        throw new Exception("Base Plugin file not found");
    }
} while (!file_exists($root . DIRECTORY_SEPARATOR .'zimrate.php'));

/**
 * WP loaded check constant
 */
const WPINC = 'wp-includes';

/**
 * Reference to this file
 */
define("ZIMRATE_FILE", $root . DIRECTORY_SEPARATOR . 'zimrate.php');

/**
 * Method stubs
 */
if (!function_exists('register_activation_hook')) {
    /**
     * Register activation hook stub
     *
     * @param string $file
     * @param callable $callable
     * @return void
     */
    function register_activation_hook(string $file, callable $callable): void
    {
    }
}
if (!function_exists('register_deactivation_hook')) {
    /**
     * Register deactivation hook stub
     *
     * @param string $file
     * @param callable $callable
     * @return void
     */
    function register_deactivation_hook(string $file, callable $callable): void
    {
    }
}
if (!function_exists('register_uninstall_hook')) {
    /**
     * Register uninstall hook stub
     *
     * @param string $file
     * @param callable $callable
     * @return void
     */
    function register_uninstall_hook(string $file, callable $callable): void
    {
    }
}
if (!function_exists('plugin_basename')) {

    function plugin_basename(string $file):string
    {
        return basename($file, ".php") . "/" . basename($file);
    }
}

if (!function_exists('get_transient')) {
    /**
     * Get transient stub - always returns false (cache miss)
     *
     * @param string $transient
     * @return mixed
     */
    function get_transient(string $transient)
    {
        return false;
    }
}

if (!function_exists('set_transient')) {
    /**
     * Set transient stub - does nothing in tests
     *
     * @param string $transient
     * @param mixed $value
     * @param int $expiration
     * @return bool
     */
    function set_transient(string $transient, $value, int $expiration = 0): bool
    {
        return true;
    }
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

/**
 * Load constants
 */
$content = file_get_contents(ZIMRATE_FILE);

preg_match('/#region\sConstants(.*)#endregion\sConstants/s', $content, $matches);

eval($matches[1]);

// clean up
unset($content, $matches);
