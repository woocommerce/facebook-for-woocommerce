// Load the default @wordpress/scripts config object
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const legacyAdminFileNames = [
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

const legacyAdminFileEntries = {};

legacyAdminFileNames.forEach( ( name ) => {
	legacyAdminFileEntries[ `admin/${ name }` ] = `./assets/js/admin/${ name }.js`;
} );

module.exports = {
	...defaultConfig,
	entry: {
		'admin/index.js': './assets/js/admin/index.js',
		...legacyAdminFileEntries,
	},
	output: {
		filename: '[name].js',
		path: __dirname + '/assets/build',
	},
};
