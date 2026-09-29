/**
 * Currency calculator block
 *
 * The preview is the server's markup with the fields disabled, the script
 * that drives them only runs on the front end.
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Disabled, PanelBody, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from '../../src/Views/blocks/calculator/block.json';
import {
    allCurrencies,
    CurrencyPicker,
    CurrencySelect,
    CushionControl,
    PrecisionControl,
    register,
} from './controls';

interface CalculatorAttributes {
    base: string;
    currency: string;
    currencies: string[];
    amount: number;
    precision: number;
    cushion: boolean;
}

register<CalculatorAttributes>(metadata, {
    edit: ({ attributes, setAttributes }) => {
        // a freshly inserted block has nothing ticked and would show nothing,
        // the list of currencies is only known here so it starts with every one
        useEffect(() => {
            if (!attributes.currencies.length) {
                setAttributes({ currencies: allCurrencies() });
            }
        }, []);

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Starting point', 'zimrate')}>
                        <CurrencySelect
                            label={__('From', 'zimrate')}
                            value={attributes.base}
                            bases
                            onChange={(base) => setAttributes({ base })}
                        />
                        <CurrencySelect
                            label={__('To', 'zimrate')}
                            value={attributes.currency}
                            onChange={(currency) => setAttributes({ currency })}
                        />
                        <TextControl
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                            type="number"
                            min={0}
                            step="any"
                            label={__('Amount', 'zimrate')}
                            value={String(attributes.amount)}
                            onChange={(amount) =>
                                setAttributes({ amount: parseFloat(amount) || 0 })
                            }
                        />
                    </PanelBody>
                    <PanelBody title={__('Display', 'zimrate')} initialOpen={false}>
                        <PrecisionControl
                            value={attributes.precision}
                            onChange={(precision) => setAttributes({ precision })}
                        />
                        <CushionControl
                            checked={attributes.cushion}
                            onChange={(cushion) => setAttributes({ cushion })}
                        />
                    </PanelBody>
                    <PanelBody title={__('Currencies offered', 'zimrate')} initialOpen={false}>
                        <CurrencyPicker
                            value={attributes.currencies}
                            onChange={(currencies) => setAttributes({ currencies })}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...useBlockProps()}>
                    <Disabled>
                        <ServerSideRender block={metadata.name} attributes={attributes} />
                    </Disabled>
                </div>
            </>
        );
    },
    save: () => null,
});
