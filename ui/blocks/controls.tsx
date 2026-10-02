/**
 * Sidebar controls shared by the blocks
 */
import type { CSSProperties } from 'react';
import { __ } from '@wordpress/i18n';
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import {
    CheckboxControl,
    CustomSelectControl,
    RangeControl,
    ToggleControl,
} from '@wordpress/components';

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
    flags: {},
    defaults: { base: 'USD', currency: '' },
};

/**
 * A currency's flag as --zimrate-flag, for blocks-editor.css to draw ahead of
 * its name. Nothing where the currency has no flag.
 */
const flag = (code: string) =>
    (data.flags[code] ? { '--zimrate-flag': `url("${data.flags[code]}")` } : {}) as CSSProperties;

/** Currency options as CustomSelectControl wants them, in the order php gave them */
const options = (list: Record<string, string>) =>
    Object.entries(list).map(([key, name]) => ({
        key,
        name,
        className: 'zimrate-flagged',
        style: flag(key),
    }));

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
    const list = options(bases ? data.bases : data.currencies);
    const fallback = bases ? data.defaults.base : data.defaults.currency;
    const current = value || fallback;

    // the closed select shows its flag from the wrapper, see blocks-editor.css
    return (
        <div className="zimrate-flagged-select" style={flag(current)}>
            <CustomSelectControl
                __next40pxDefaultSize
                label={label}
                value={list.find((option) => option.key === current)}
                options={list}
                onChange={({ selectedItem }) =>
                    onChange(selectedItem.key === fallback ? '' : selectedItem.key)
                }
            />
        </div>
    );
};

interface CurrencyPickerProps {
    /** the codes to show */
    value: string[];
    onChange: (value: string[]) => void;
}

/** Every currency code, for a block that starts with all of them ticked */
export const allCurrencies = () => Object.keys(data.currencies);

/**
 * Tick the currencies a table shows, alphabetical as php lists them
 */
export const CurrencyPicker = ({ value, onChange }: CurrencyPickerProps) => (
    <>
        {Object.entries(data.currencies).map(([code, label]) => (
            <div key={code} className="zimrate-flagged-check" style={flag(code)}>
                <CheckboxControl
                    __nextHasNoMarginBottom
                    label={label}
                    checked={value.includes(code)}
                    onChange={(checked) =>
                        onChange(checked ? [...value, code] : value.filter((item) => item !== code))
                    }
                />
            </div>
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
