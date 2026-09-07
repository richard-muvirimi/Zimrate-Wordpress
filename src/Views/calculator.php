<?php

/**
 * Dashboard rate calculator and rates table
 *
 * @package    Zimrate
 * @subpackage Zimrate/Views
 *
 * @link       https://tyganeutronics.com
 * @since      1.1.6
 * @version    1.1.6
 *
 * @var array $args
 */

use RichardMuvirimi\Zimrate\Helpers\Functions;

defined('ABSPATH') || exit();

// everything is quoted against USD, any other base is a cross rate derived
// from it, so one response covers every base the reader can pick
$rates = Functions::get_rates(false, 'USD');

$raw = array();
foreach ($rates['USD'] ?? array() as $row) {
    $raw[$row['currency']] = floatval($row['rate']);
}

// the cushion is applied after the cross rate, applying it to both sides
// beforehand would cancel it out
$cushion = $args['cushion'] === 'yes' ? floatval(get_option('zimrate-cushion', 1)) : 0;
$precision = intval($args['precision']);

$bases = array();
foreach (Functions::supported_bases() as $code => $label) {
    if ($code === 'USD' || isset($raw[$code])) {
        $bases[] = $code;
    }
}

$base = in_array($args['base'], $bases, true) ? $args['base'] : reset($bases);
$currency = isset($raw[$args['currency']]) ? $args['currency'] : key($raw);

/**
 * Cross rate for a currency against the chosen base, cushion applied
 *
 * @param string $to
 * @return float
 */
$rate_for = function ($to) use ($raw, $base, $cushion) {
    if ($to === $base) {
        $rate = 1.0;
    } elseif ($base === 'USD') {
        $rate = $raw[$to];
    } else {
        $rate = $raw[$to] / $raw[$base];
    }

    return $rate + ($cushion * $rate) / 100;
};
?>

<div class="zimrate-calculator"
     data-zimrate-calculator
     data-precision="<?php echo esc_attr($precision); ?>"
     data-cushion="<?php echo esc_attr($cushion); ?>"
     data-base="<?php echo esc_attr($base); ?>"
     data-rates="<?php echo esc_attr(wp_json_encode($raw)); ?>">

    <?php if (empty($raw)) : ?>

        <p class="zimrate-calculator-empty">
            <?php echo esc_html($rates['info'] ?? __('No rates are available right now.', 'zimrate')); ?>
        </p>

    <?php else : ?>

        <div class="zimrate-calculator-fields">
            <label class="zimrate-calculator-field">
                <span>
                    <select data-zimrate-base>
                        <?php foreach ($bases as $code) : ?>
                            <option value="<?php echo esc_attr($code); ?>"
                                <?php selected($code, $base); ?>>
                                <?php echo esc_html($code); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>
                <input type="number"
                       step="any"
                       min="0"
                       inputmode="decimal"
                       data-zimrate-amount
                       value="<?php echo esc_attr($args['amount']); ?>" />
            </label>

            <button type="button"
                    class="zimrate-calculator-swap"
                    data-zimrate-swap
                    aria-label="<?php esc_attr_e('Swap direction', 'zimrate'); ?>">&#8646;</button>

            <label class="zimrate-calculator-field">
                <span>
                    <select data-zimrate-currency>
                        <?php foreach ($raw as $code => $value) : ?>
                            <option value="<?php echo esc_attr($code); ?>"
                                <?php selected($code, $currency); ?>>
                                <?php echo esc_html($code); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </span>
                <input type="number" step="any" readonly data-zimrate-result />
            </label>
        </div>

        <p class="zimrate-calculator-summary" data-zimrate-summary></p>

        <?php if ($args['table'] === 'yes') : ?>

            <details class="zimrate-calculator-table" <?php echo $args['open'] === 'yes' ? 'open' : ''; ?>>
                <summary><?php esc_html_e('Current rates', 'zimrate'); ?></summary>

                <div class="zimrate-calculator-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Currency', 'zimrate'); ?></th>
                                <th data-zimrate-heading>
                                    <?php printf(
                                        /* translators: %s: base currency code */
                                        esc_html__('Rate (per 1 %s)', 'zimrate'),
                                        esc_html($base)
                                    ); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($raw as $code => $value) : ?>
                                <tr data-zimrate-row="<?php echo esc_attr($code); ?>">
                                    <td><?php echo esc_html($code); ?></td>
                                    <td data-zimrate-cell><?php echo esc_html(
                                        number_format_i18n($rate_for($code), $precision)
                                    ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($rates['info'])) : ?>
                    <p class="zimrate-calculator-info"><?php echo esc_html($rates['info']); ?></p>
                <?php endif; ?>
            </details>

        <?php endif; ?>

    <?php endif; ?>
</div>
