=== Facebook for WooCommerce ===
Contributors: facebook, automattic, woothemes
Tags: facebook, shop, catalog, advertise, pixel, product
Requires at least: 4.4
Tested up to: 5.3.2
Stable tag: 1.10.2-dev.1
Requires PHP: 5.6 or greater
MySQL: 5.6 or greater
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Get the Official Facebook for WooCommerce plugin for two powerful ways to help grow your business, including an ads extension and shops tab for your page.

== Description ==

This is the official Facebook for WooCommerce plugin that connects your WooCommerce website to Facebook. With this plugin, you can install the Facebook pixel, upload your online store catalog, and create a shop on your Facebook page, enabling you to easily run dynamic ads.

Marketing on Facebook helps your business build lasting relationships with people, find new customers, and increase sales for your online store. With this Facebook ad extension, reaching the people who matter most to your business is simple. This extension will track the results of your advertising across devices. It will also help you:

* Maximize your campaign performance. By setting up the Facebook pixel and building your audience, you will optimize your ads for people likely to buy your products, and reach people with relevant ads on Facebook after they’ve visited your website.
* Find more customers. Connecting your product catalog automatically creates carousel ads that showcase the products you sell and attract more shoppers to your website.
* Generate sales among your website visitors. When you set up the Facebook pixel and connect your product catalog, you can use dynamic ads to reach shoppers when they’re on Facebook with ads for the products they viewed on your website. This will be included in a future release of Facebook for WooCommerce.

== Installation ==

Visit the Facebook Help Center [here](https://www.facebook.com/business/help/900699293402826).

== Support ==

If you believe you have found a security vulnerability on Facebook, we encourage you to let us know right away. We investigate all legitimate reports and do our best to quickly fix the problem. Before reporting, please review [this page](https://www.facebook.com/whitehat), which includes our responsible disclosure policy and reward guideline. You can submit bugs [here](https://github.com/facebookincubator/facebook-for-woocommerce/issues) or contact advertising support [here](https://www.facebook.com/business/help/900699293402826).

When opening a bug on GitHub, please give us as many details as possible.

* Symptoms of your problem
* Screenshot, if possible
* Your Facebook page URL
* Your website URL
* Current version of Facebook-for-WooCommerce, WooCommerce, Wordpress, PHP

== Changelog ==

= 2020.nn.nn - version 1.10.2-dev.1 =
 * Tweak - Add a setting to easily enable debug logging
 * Tweak - Allow third party plugins and themes to track an add-to-cart event on added_to_cart JS event
 * Tweak - When excluding a product term from syncing in the plugin settings page, offer an option to hide excluded synced products from Facebook
 * Tweak - When excluding product terms from syncing in the plugin settings page, and settings are saved, exclude corresponding products from sync
 * Fix - When excluding a product term from syncing in the plugin settings page, ensure a modal opens to warn about possible conflicts with already synced products
 * Fix - Messenger settings fields will correctly reflect the values selected during initial setup
 * Fix - Fix a bug that caused newly added gallery images not to be synced immediately after they were added
 * Fix - Fix a bug that prevented gallery images from being removed from products on Facebook
 * Fix - Fix AddToCart Pixel event tracking when adding products from archive with AJAX and redirect to cart enabled
 * Dev - Make Pixel script attributes and event parameters filterable

= 2020.03.10 - version 1.10.1 =
 * Fix - Prevent Fatal error during the upgrade routine introduced in version 1.10.0
 * Fix - Only load the admin settings JavaScript on the Facebook settings page to prevent conflicts with other scripts
 * Misc - Add support for WooCommerce 4.0

= 2020.03.03 - version 1.10.0 =
 * Feature - Exclude specific products, variations, product categories, and product tags from syncing to Facebook
 * Feature - Add Facebook product settings like price and description to variations
 * Feature - Revamped settings screen with on-site control over pixel, product sync, and Messenger behavior
 * Tweak - Use Action Scheduler for the daily forced re-sync, if enabled
 * Fix - Improve pixel tracking accuracy for add-to-cart events
 * Misc - Add the SkyVerge plugin framework as the plugin base
 * Misc - Require WooCommerce 3.5 and above

= 1.9.15 - 2019-06-27 =
* CSRF handling for Ajax calls like ajax_woo_infobanner_post_click, ajax_woo_infobanner_post_xout, ajax_fb_toggle_visibility
* use phpcs to adhere to WP coding standards
* Minor UI changes on the iFrame

= 1.9.14 - 2019-06-20 =
* Revisit CSRF security issue
* Remove rest controller which is not used
* Tested installation in wordpress 5.2.2, WooCommerce 3.64, php 5.6/7.3 with browser Chrome v75/Safari v12.1/Firefox v67.

= 1.9.13 - 2019-06-18 =
* Fix security issue
* Add more contributors to the plugin

= 1.9.12 - 2019-05-2 =
* Remove dead code which causes exception (Issue 975)

= 1.9.11 - 2019-02-26 =
* changing contributor to facebook from facebook4woocommerce, so that
  woo plugin will be shown under
  https://profiles.wordpress.org/facebook/#content-plugins
* adding changelog in readme.txt so that notifications will be sent for
  updates and changelog will be shown under
  https://wordpress.org/plugins/facebook-for-woocommerce/#developers
* removing debug flags notice under facebook-for-woocommerce.php so that
  developers will be able to debug with debug logs
