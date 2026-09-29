/**
 * Exchange rates table block
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from '../../src/Views/blocks/rates-table/block.json';
import {
    allCurrencies,
    CurrencyPicker,
    CurrencySelect,
    CushionControl,
    PrecisionControl,
    register,
} from './controls';

interface RatesTableAttributes {
    base: string;
    currencies: string[];
    precision: number;
    cushion: boolean;
}

register<RatesTableAttributes>(metadata, {
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
                    <PanelBody title={__('Rates', 'zimrate')}>
                        <CurrencySelect
                            label={__('Quoted against', 'zimrate')}
                            value={attributes.base}
                            bases
                            onChange={(base) => setAttributes({ base })}
                        />
                        <PrecisionControl
                            value={attributes.precision}
                            onChange={(precision) => setAttributes({ precision })}
                        />
                        <CushionControl
                            checked={attributes.cushion}
                            onChange={(cushion) => setAttributes({ cushion })}
                        />
                    </PanelBody>
                    <PanelBody title={__('Currencies shown', 'zimrate')} initialOpen={false}>
                        <CurrencyPicker
                            value={attributes.currencies}
                            onChange={(currencies) => setAttributes({ currencies })}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...useBlockProps()}>
                    <ServerSideRender block={metadata.name} attributes={attributes} />
                </div>
            </>
        );
    },
    save: () => null,
});
