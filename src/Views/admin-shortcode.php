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

use RichardMuvirimi\Zimrate\Helpers\Functions;

?>
<h2>
    <?php _e('Short Codes', Functions::get_plugin_slug()); ?>
</h2>

<div class="zimrate">
    <div class="zimrate-shortcode">
        <p>
            <strong>
                <?php _e(
                    'So you might want to display an amount of money in your preffered currency and not worry about modifying your posts as the rate changes.',
                    Functions::get_plugin_slug()
                ); ?>
            </strong>
        </p>
        <p>
            <?php printf(
                __(
                    'This plugin provides a short code (<code>[%s]</code>) which you can use to display that value using the latest rates with the following attribute options:',
                    Functions::get_plugin_slug()
                ),
                Functions::get_shortcode()
            ); ?>
        </p>
        <ul class="zimrate-shortcode-list">
            <li>
                <code>currency</code>
                <?php printf(
                    /* translators: 1: available currency codes 2: selected currency code */
                    __(
                        'Any of %1$s, defaulting to %2$s',
                        Functions::get_plugin_slug()
                    ),
                    '"' . implode('", "', array_keys(Functions::supported_currencies())) . '"',
                    Functions::default_currency()
                ); ?>
            </li>
            <li>
                <code>value</code>
                <?php printf(
                    /* translators: %s: base currency code */
                    __('The value in %s defaulting to 1', Functions::get_plugin_slug()),
                    Functions::default_base()
                ); ?>
            </li>
            <li>
                <code>precision</code>
                <?php _e(
                    'The precision level (Number of decimal digits) defaulting to 2.',
                    Functions::get_plugin_slug()
                ); ?>
            </li>
            <li>
                <code>format</code>
                <?php _e(
                    'Whether to format calculated value to user\'s locale (Can only be either yes or no) defaulting to no.',
                    Functions::get_plugin_slug()
                ); ?>
            </li>
            <li>
                <code>cushion</code>
                <?php _e(
                    'Whether to apply cushion from rate calculation settings (Can only be either yes or no) defaulting to yes.',
                    Functions::get_plugin_slug()
                ); ?>
            </li>
        </ul>
        <h4>
            <?php _e('Shortcode Samples', Functions::get_plugin_slug()); ?>
        </h4>

        <?php $sample = Functions::default_currency(); ?>
        <ul class="zimrate-shortcode-list">
            <li>
                <code>
                    [<?php echo Functions::get_shortcode(); ?>]
                </code>
                <strong>
                    &longrightarrow;
                </strong>
                <?php echo do_shortcode('[' . Functions::get_shortcode() . ']') ?>
            </li>
            <li>
                <code>
                    [<?php echo Functions::get_shortcode(); ?> currency="<?php echo esc_html($sample); ?>" cushion="no"]
                </code>
                <strong>
                    &longrightarrow;
                </strong>
                <?php echo do_shortcode(
                    '[' . Functions::get_shortcode() . ' currency="' . $sample . '" cushion="no"]'
                ) ?>
            </li>
        </ul>
    </div>

</div>