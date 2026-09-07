<?php

/**
 * Options Page
 *
 */
defined('ABSPATH') || exit();

// check user capabilities
if (!current_user_can('manage_options')) {
    return;
}
?>

<div class="wrap">
    <form method="post" action="options.php">
        <?php
        settings_fields('zimrate-options');
        do_settings_sections('zimrate-options');

        submit_button();
        ?>
    </form>
</div>