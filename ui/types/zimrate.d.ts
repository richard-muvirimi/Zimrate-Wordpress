/**
 * What php hands the editor bundle, see Site::localize_block_editor()
 */
interface ZimrateBlocksData {
    /** currency code => label, alphabetical */
    currencies: Record<string, string>;
    /** USD first, then every currency */
    bases: Record<string, string>;
    defaults: {
        base: string;
        currency: string;
    };
}

interface Window {
    zimrateBlocks?: ZimrateBlocksData;
}

// these packages ship without type declarations
declare module '@wordpress/block-editor' {
    import type { ComponentType, HTMLAttributes, ReactNode } from 'react';

    export const InspectorControls: ComponentType<{ children?: ReactNode }>;
    export function useBlockProps(props?: Record<string, unknown>): HTMLAttributes<HTMLDivElement>;
}

declare module '@wordpress/server-side-render' {
    import type { ComponentType } from 'react';

    const ServerSideRender: ComponentType<{
        block: string;
        attributes?: Record<string, unknown>;
        skipBlockSupportAttributes?: boolean;
    }>;

    export default ServerSideRender;
}
