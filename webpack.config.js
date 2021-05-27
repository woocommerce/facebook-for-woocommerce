// Load the default @wordpress/scripts config object
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

// Legacy jQuery UI powered admin files
const jQueryUIAdminFileNames = [
	'google-product-category-fields',
	'infobanner',
	'metabox',
	'modal',
	'orders',
	'product-categories',
	'product-sets-admin',
	'products-admin',
	'settings-commerce',
	'settings-sync',
];

const jQueryUIAdminFileEntries = {};

jQueryUIAdminFileNames.forEach( ( name ) => {
	jQueryUIAdminFileEntries[ `admin/${ name }` ] = `./assets/js/admin/${ name }.js`;
} );

module.exports = {
	...defaultConfig,
	entry: {
		// Use admin/index.js for any new React-powered UI
		'admin/index': './assets/js/admin/index.js',
		...jQueryUIAdminFileEntries,
	},
	output: {
		filename: '[name].js',
		path: __dirname + '/assets/build',
		// The legacy JS code is invoked from various templates as hard-coded calls to functions in global namespace.
		// So we are using `umd` output mode to ensure exported functions are available.
		// See also: https://webpack.js.org/configuration/output/#module-definition-systems
		// Unfortunately this applies to all JS as there can only be one output definition.
		libraryTarget: 'umd',
	},
};
