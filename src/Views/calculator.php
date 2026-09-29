<?php

/**
 * Rate calculator, with the rates table beneath it on the dashboard
 *
 * @package    Zimrate
 * @subpackage Zimrate/Views
 *
 * @link       https://tyganeutronics.com
 * @since      1.1.6
 * @version    1.1.6
 *
 * @var string $base
 * @var string $currency
 * @var float $amount
 * @var int $precision
 * @var float $cushion percent
 * @var array $bases code => label
 * @var array $currencies code => label
 * @var array $rates code => rate against USD, for the script
 * @var string $info
 * @var bool $open
 * @var string $table rendered rates table, empty for none
 */

defined('ABSPATH') || exit();
?>

<div class="zimrate-calculator"
     data-zimrate-calculator
     data-precision="<?php echo esc_attr($precision); ?>"
     data-cushion="<?php echo esc_attr($cushion); ?>"
     data-base="<?php echo esc_attr($base); ?>"
     data-rates="<?php echo esc_attr(wp_json_encode($rates)); ?>">

    <?php if (empty($rates)) : ?>

        <p class="zimrate-calculator-empty">
            <?php echo esc_html($info ?: __('No rates are available right now.', 'zimrate')); ?>
        </p>

    <?php else : ?>

        <div class="zimrate-calculator-fields">
            <label class="zimrate-calculator-field">
                <span>
                    <select data-zimrate-base>
                        <?php foreach ($bases as $code => $label) : ?>
                            <option value="<?php echo esc_attr($code); ?>"
                                <?php selected($code, $base); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>
                <input type="number"
                       step="any"
                       min="0"
                       inputmode="decimal"
                       data-zimrate-amount
                       value="<?php echo esc_attr($amount); ?>" />
            </label>

            <button type="button"
                    class="zimrate-calculator-swap"
                    data-zimrate-swap
                    aria-label="<?php esc_attr_e('Swap direction', 'zimrate'); ?>">&#8646;</button>

            <label class="zimrate-calculator-field">
                <span>
                    <select data-zimrate-currency>
                        <?php foreach ($currencies as $code => $label) : ?>
                            <option value="<?php echo esc_attr($code); ?>"
                                <?php selected($code, $currency); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>
                <input type="number" step="any" readonly data-zimrate-result />
            </label>
        </div>

        <p class="zimrate-calculator-summary" data-zimrate-summary></p>

        <?php if ($table) : ?>

            <details class="zimrate-calculator-table" <?php echo $open ? 'open' : ''; ?>>
                <summary><?php esc_html_e('Current rates', 'zimrate'); ?></summary>
                <?php echo $table; ?>
            </details>

        <?php endif; ?>

    <?php endif; ?>
</div>
