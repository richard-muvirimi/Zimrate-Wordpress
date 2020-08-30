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
 */
?>
<h2>
    <?php _e('Short Codes', $this->plugin_name); ?>
</h2>

<div class="zimrate">
    <div class="zimrate-shortcode">
        <p>
            <strong>
                <?php _e(
                    'So you might want to display an amount of money in your preffered currency and not worry about modifying your posts as the rate changes.',
                    $this->plugin_name
                ); ?>
            </strong>
        </p>
        <p>
            <?php printf(
                __(
                    'This plugin provides a short code (<code>[%s]</code>) which you can use to display that value using the latest rates with the following attribute options:',
                    $this->plugin_name
                ),
                zimrate_get_shortcode()
            ); ?>
        </p>
        <ul class="zimrate-shortcode-list">
            <li>
                <code>currency</code>
                <?php _e(
                    'Can only be either one of "ZAR", "BOND", "OMIR", "RBZ", "RTGS" defaulting to RBZ',
                    $this->plugin_name
                ); ?>
            </li>
            <li>
                <code>value</code>
                <?php _e(
                    'The value in USD defaulting to 1',
                    $this->plugin_name
                ); ?>
            </li>
            <li>
                <code>precision</code>
                <?php _e(
                    'The precision level (Number of decimal digits) defaulting to 2.',
                    $this->plugin_name
                ); ?>
            </li>
            <li>
                <code>format</code>
                <?php _e(
                    'Whether to format calculated value to user\'s locale (Can only be either yes or no) defaulting to no.',
                    $this->plugin_name
                ); ?>
            </li>
            <li>
                <code>cushion</code>
                <?php _e(
                    'Whether to apply cushion from rate calculation settings (Can only be either yes or no) defaulting to yes.',
                    $this->plugin_name
                ); ?>
            </li>
        </ul>
        <h4>
            <?php _e('Shortcode Samples', $this->plugin_name); ?>
        </h4>
        <ul class="zimrate-shortcode-list">
            <li>
                <code>
                    [<?php echo zimrate_get_shortcode(); ?>]
                </code>
                <strong>
                    &longrightarrow;
                </strong>
                <?php echo do_shortcode('[' . zimrate_get_shortcode() . ']') ?>
            </li>
            <li>
                <code>
                    [<?php echo zimrate_get_shortcode(); ?> currency="RBZ" cushion="no"]
                </code>
                <strong>
                    &longrightarrow;
                </strong>
                <?php echo do_shortcode('[' . zimrate_get_shortcode() . ' currency="RBZ" cushion="no"]') ?>
            </li>
        </ul>
    </div>

</div>