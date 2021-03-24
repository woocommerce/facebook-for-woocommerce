// Load the default @wordpress/scripts config object
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		frontend: './assets/js/frontend/index.js',
		admin: './assets/js/admin/index.js',
	},
	output: {
		filename: '[name].js',
		path: __dirname + '/assets/build',
	},
};
