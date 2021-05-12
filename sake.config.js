module.exports = {
	plugin: {
		id: 'facebook-for-woocommerce',
	},
	deploy: {
		type: 'wp',
	},
	paths: {
		js: false,
		exclude: [
			'bin',
		],
	},
	framework: 'v5',
	deployAssets: false,
	autoload: true,
};
