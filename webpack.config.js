const path = require('node:path');
const { cpSync } = require('node:fs');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

/**
 * Stages flag-icons' SVGs into src/Views/img/flags, one file per country, so
 * the flags shipped are whatever flag-icons version is installed rather than a
 * committed copy that drifts. The directory is gitignored and taken into the
 * release by the deploy workflow.
 *
 * Runs on initialize so it covers `build`, `dist` and `start` alike.
 */
const flagAssets = {
    apply(compiler) {
        compiler.hooks.initialize.tap('zimrate-flag-assets', () => {
            cpSync(
                path.resolve(__dirname, 'node_modules/flag-icons/flags/4x3'),
                path.resolve(__dirname, 'src/Views/img/flags'),
                { recursive: true }
            );
        });
    },
};

// the editor bundle for every block, the blocks themselves are rendered in php
module.exports = {
    ...defaultConfig,
    entry: {
        'blocks/index': path.resolve(__dirname, 'ui/blocks/index.tsx'),
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'src/Views/js/dist'),
    },
    plugins: [...defaultConfig.plugins, flagAssets],
};
