<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Zimrate
 * @subpackage Zimrate/admin/partials
 *
 * @link       https://tyganeutronics.com
 * @since      1.0.0
 * 
 * @var array $plugin Plugin data from get_plugin_data()
 */

use RichardMuvirimi\Zimrate\Helpers\Functions;

?>

<div class="zimrate">
    <div>
        <h1 class="center">
            <a href="<?php echo $plugin['PluginURI']; ?>">
                <?php echo $plugin['Name']; ?>
            </a>
        </h1>
        <h5>
            <?php echo $plugin['Version']; ?>
        </h5>
        <h4>
            <?php echo $plugin['Description']; ?>
        </h4>
        <?php do_action('zimrate-after-info'); ?>
    </div>

    <!--rates-->
    <?php do_action('zimrate-before-rates'); ?>

    <?php include_once 'admin-rates.php'; ?>

    <?php do_action('zimrate-after-rates'); ?>

    <!--plugins-->
    <?php do_action('zimrate-before-plugins'); ?>

    <?php include_once 'admin-plugins.php'; ?>

    <?php do_action('zimrate-after-plugins'); ?>

    <!--shortcodes-->
    <?php do_action('zimrate-before-shortcode'); ?>

    <?php include_once 'admin-shortcode.php'; ?>

    <?php do_action('zimrate-after-shortcode'); ?>

</div>