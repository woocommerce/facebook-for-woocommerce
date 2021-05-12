# Facebook for WooCommerce

This is the development repository for the Facebook for WooCommerce plugin.

- [WooCommerce.com product page](https://woocommerce.com/products/facebook)
- [WordPress.org plugin page](https://wordpress.org/plugins/facebook-for-woocommerce/)
- [User documentation](https://docs.woocommerce.com/document/facebook-for-woocommerce)

## Support
The best place to get support is the [WordPress.org Facebook for WooCommerce forum](https://wordpress.org/support/plugin/facebook-for-woocommerce/).

If you have a WooCommerce.com account, you can [start a chat or open a ticket on WooCommerce.com](https://woocommerce.com/my-account/create-a-ticket/).

## Development 
### Developing
- Clone this repository into the `wp-content/plugins/` folder your WooCommerce development environment.
- Install dependencies: 
	- `npm install`
	- `composer install`
- Build assets:
	- `npm start` to build a development version
- Linting:
	- `npm run lint:php` to run PHPCS linter on all PHP files

#### Production build
This plugin uses a custom build tool called [`sake`](https://github.com/skyverge/sake). 

If you have `sake` set up on your system, these commands can be used to generate a production build.

- `npm run build` builds and zips to `/build/facebook-for-woocommerce.{version}.zip`.

### Releasing
Refer to the [wiki for details of how to build and release the plugin](https://github.com/woocommerce/facebook-for-woocommerce/wiki/Build-&-Release). 
