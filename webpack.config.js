const path = require('node:path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

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
};
