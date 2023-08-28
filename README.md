# Facebook for WooCommerce

[![PHP Coding Standards](https://github.com/woocommerce/facebook-for-woocommerce/actions/workflows/php-coding-standards.yml/badge.svg)](https://github.com/woocommerce/facebook-for-woocommerce/actions/workflows/php-coding-standards.yml)

This is the development repository for the Facebook for WooCommerce plugin.

- [WooCommerce.com product page](https://woocommerce.com/products/facebook)
- [WordPress.org plugin page](https://wordpress.org/plugins/facebook-for-woocommerce/)
- [User documentation](https://docs.woocommerce.com/document/facebook-for-woocommerce)

## Support
The best place to get support is the [WordPress.org Facebook for WooCommerce forum](https://wordpress.org/support/plugin/facebook-for-woocommerce/).

If you have a WooCommerce.com account, you can [start a chat or open a ticket on WooCommerce.com](https://woocommerce.com/my-account/create-a-ticket/).

### Logging
The plugin offers logging that can help debug various problems. You can enable debug mode in the main plugin settings panel under the `Enable debug mode` section.
By default plugin omits headers in the requests to make the logs more readable. If debugging with headers is necessary you can enable the headers in the logs by setting `wc_facebook_request_headers_in_debug_log` option to true.
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
- Testing:
	- `./bin/install-wp-tests.sh <test-db-name> <db-user> <db-password> [db-host]` to set up testing environment
	- `npm run test:php` to run PHP unit tests on all PHP files

#### Production build

- `npm run build` : Builds a production version.

### Releasing
Refer to the [wiki for details of how to build and release the plugin](https://github.com/woocommerce/facebook-for-woocommerce/wiki/Build-&-Release).

### PHPCS Linting and PHP 8.1+

We currently do not support PHPCS on PHP 8.1+ versions. Please run PHPCS checks on PHP 8.0 or lower versions.

Alternately, you can run PHPCS checks on PHP8.1+ versions by appending `?? ''` code within `trim()` at Line 280 of file /vendor/wp-coding-standards/wpcs/WordPress/Sniffs/NamingConventions/PrefixAllGlobalsSniff.php and Line 194 of file /vendor/wp-coding-standards/wpcs/WordPress/Sniffs/WP/I18nSniff.php