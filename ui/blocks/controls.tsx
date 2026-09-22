/**
 * Sidebar controls shared by the blocks
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import { CheckboxControl, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';

/**
 * Register a block from its block.json, php already declared it server side so
 * only edit and save are supplied here
 */
export const register = <T extends Record<string, any>>(
    metadata: unknown,
    settings: Partial<BlockConfiguration<T>>,
) => registerBlockType<T>(metadata as BlockConfiguration<T>, settings);

const data: ZimrateBlocksData = window.zimrateBlocks ?? {
    currencies: {},
    bases: {},
    defaults: { base: 'USD', currency: '' },
};

/** Currency options as SelectControl wants them, in the order php gave them */
const options = (list: Record<string, string>) =>
    Object.entries(list).map(([value, label]) => ({ value, label }));

interface CurrencySelectProps {
    label: string;
    value: string;
    /** offer the bases (USD first) rather than the currencies */
    bases?: boolean;
    onChange: (value: string) => void;
}

/**
 * A currency dropdown.
 *
 * An empty value means "the plugin default", shown as the default itself, so
 * a site that later changes its default carries every block along with it.
 */
export const CurrencySelect = ({ label, value, bases = false, onChange }: CurrencySelectProps) => {
    const list = bases ? data.bases : data.currencies;
    const fallback = bases ? data.defaults.base : data.defaults.currency;

    return (
        <SelectControl
            __nextHasNoMarginBottom
            __next40pxDefaultSize
            label={label}
            value={value || fallback}
            options={options(list)}
            onChange={(next) => onChange(next === fallback ? '' : next)}
        />
    );
};

interface CurrencyPickerProps {
    /** the codes to show, none for every currency */
    value: string[];
    onChange: (value: string[]) => void;
}

/**
 * Tick the currencies a table shows, alphabetical as php lists them
 */
export const CurrencyPicker = ({ value, onChange }: CurrencyPickerProps) => (
    <>
        {Object.entries(data.currencies).map(([code, label]) => (
            <CheckboxControl
                __nextHasNoMarginBottom
                key={code}
                label={label}
                checked={value.includes(code)}
                onChange={(checked) =>
                    onChange(checked ? [...value, code] : value.filter((item) => item !== code))
                }
            />
        ))}
    </>
);

interface PrecisionControlProps {
    value: number;
    onChange: (value: number) => void;
}

export const PrecisionControl = ({ value, onChange }: PrecisionControlProps) => (
    <RangeControl
        __nextHasNoMarginBottom
        __next40pxDefaultSize
        label={__('Decimal places', 'zimrate')}
        value={value}
        min={0}
        max={6}
        onChange={(next) => onChange(next ?? 2)}
    />
);

interface CushionControlProps {
    checked: boolean;
    onChange: (checked: boolean) => void;
}

export const CushionControl = ({ checked, onChange }: CushionControlProps) => (
    <ToggleControl
        __nextHasNoMarginBottom
        label={__('Apply cushion', 'zimrate')}
        help={__('Pad the rate by the percentage set in ZimRate settings.', 'zimrate')}
        checked={checked}
        onChange={onChange}
    />
);
