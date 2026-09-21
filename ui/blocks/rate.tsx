/**
 * Exchange rate block, the [zimrate] shortcode with a sidebar
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from '../../src/Views/blocks/rate/block.json';
import { CurrencySelect, CushionControl, PrecisionControl, register } from './controls';

interface RateAttributes {
    base: string;
    currency: string;
    value: number;
    precision: number;
    format: boolean;
    cushion: boolean;
}

register<RateAttributes>(metadata, {
    edit: ({ attributes, setAttributes }) => (
        <>
            <InspectorControls>
                <PanelBody title={__('Rate', 'zimrate')}>
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
                        value={String(attributes.value)}
                        onChange={(value) => setAttributes({ value: parseFloat(value) || 0 })}
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
                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Thousands separators', 'zimrate')}
                        checked={attributes.format}
                        onChange={(format) => setAttributes({ format })}
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
