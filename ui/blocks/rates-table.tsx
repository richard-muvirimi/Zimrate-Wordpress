/**
 * Exchange rates table block
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from '../../src/Views/blocks/rates-table/block.json';
import { CurrencySelect, CushionControl, PrecisionControl, register } from './controls';

interface RatesTableAttributes {
    base: string;
    precision: number;
    cushion: boolean;
}

register<RatesTableAttributes>(metadata, {
    edit: ({ attributes, setAttributes }) => (
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
            </InspectorControls>
            <div {...useBlockProps()}>
                <ServerSideRender block={metadata.name} attributes={attributes} />
            </div>
        </>
    ),
    save: () => null,
});
