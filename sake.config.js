module.exports = {
	plugin: {
		id: 'facebook-for-woocommerce',
	},
	deploy: {
		type: 'wp',
	},
	paths: {
		// Prevent Sake from processing JS files (use WP scripts instead)
		js: false,
		exclude: [
			'bin',
		],
	},
	framework: 'v5',
	deployAssets: false,
	autoload: true,
};
