module.exports = {
  plugin: {
    id: 'facebook-for-woocommerce'
  },
  deploy: {
    type: 'wp'
  },
  paths: {
    exclude: [
      'bin'
    ]
  },
  framework: 'v5',
  deployAssets: false
};
