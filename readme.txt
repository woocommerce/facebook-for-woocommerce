=== Facebook for WooCommerce ===
Contributors: facebook, automattic, woothemes
Tags: facebook, shop, catalog, advertise, pixel, product
Requires at least: 4.4
Tested up to: 5.5.1
Stable tag: 2.1.3
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

= 2020.10.29 - version 2.1.3 =
 * Fix - Prevent JavaScript error triggered while trying to refund orders

= 2020.10.28 - version 2.1.2 =
 * Tweak - Default variation selection will be synced to Facebook if the default product variation is already synced
 * Fix - Trigger a pixel Search event for product search requests with a single result (works for logged in users or visitors with an active WooCommerce session)
 * Fix - Prevent a JavaScript error on the Add New Product page when Facebook for WooCommerce is not connected to Facebook

= 2020.10.27 - version 2.1.1 =
 * Fix - Adjust code syntax that may have issued errors in installations running PHP lower than 7.3

= 2020.10.26 - version 2.1.0 =
 * Feature - Set Google category at the shop level for the Facebook catalog sync (on the product sync tab).
 * Feature - Set Google category for the Facebook catalog sync at the WooCommerce category level.
 * Feature - Set Google category for the Facebook catalog sync at the product level.
 * Feature - Set Enhanced Catalog category specific fields for the Facebook catalog sync at the WooCommerce category level.
 * Feature - Set Enhanced Catalog category specific fields for the Facebook catalog sync at the product level.

= 2020.10.12 - version 2.0.5 =
 * Tweak - Update product availability when stock changes in the store
 * Fix - Don't prevent variation products from being updated when they're set to not sync with Facebook but have their categories excluded from syncing
 * Fix - Prevent an error during the feed generation when variable products are still using deleted terms

= 2020.10.08 - version 2.0.4 =
 * Fix - Fix SQL errors triggered while trying to remove duplicate visibility meta entries from postmeta table

= 2020.10.02 - version 2.0.3 =
 * Tweak - Pixel events now can include advanced matching information
 * Fix - Send contents parameter for ViewContent event using the correct format
 * Fix - Remove duplicate visibility meta entries from postmeta table

= 2020.09.25 - version 2.0.2 =
 * Tweak - Allow simple and variable products with zero/empty price to sync to Facebook
 * Tweak - Use the bundle price for Product Bundles products with individually priced items
 * Fix - Update connection parameters to use an array to pass the Messenger domain
 * Fix - Ensure out-of-stock products are marked as such in Facebook when the feed file replacement is run
 * Fix - Address a potential error when connecting from a site whose title contains special characters

= 2020.08.17 - version 2.0.1 =
 * Fix - Ensure the configured business name is never empty when connecting to Facebook

= 2020.07.30 - version 2.0.0 =
 * Tweak - Show Facebook options for virtual products and variations
 * Tweak - Hide "Sync and show" option for virtual products and variations
 * Tweak - On upgrade, automatically set sync-enabled and visible virtual products and virtual variations to Sync and hide
 * Tweak - Allow to bulk enable sync for virtual products, but automatically set them to Sync and hide
 * Fix - Use the plugin version instead of a timestamp as the version number for enqueued scripts and stylesheets
 * Fix - Use the short description of the parent product for product variations that don't have a description or Facebook description
 * Fix - Prevent an error when YITH Booking and Appointment for WooCommerce plugin is active

= 2020.06.04 - version 1.11.4 =
 * Fix - Do not sync variations for draft variable products created by duplicating products
 * Fix - Do not log an error when the product is null on add to cart redirect

= 2020.05.20 - version 1.11.3 =
 * Tweak - Write product feed to a temporary file and rename it when done, to prevent Facebook from downloading an incomplete feed file
 * Tweak - Hide Facebook options for virtual products and virtual variations
 * Tweak - Do not allow merchant to bulk enable sync for virtual products
 * Tweak - On upgrade, automatically disable sync for virtual products and virtual variations
 * Tweak - When using checkboxes for tags, make sure the modal is displayed when trying to enable sync for a product with an excluded tag
 * Fix - Prevent tracking of a duplicated purchase event in some circumstances such as when the customer reloads the "Thank You" page after completing an order
 * Fix - Fix a JavaScript issue that was causing a notice to be displayed when bulk editing product variations

= 2020.05.04 - version 1.11.2 =
 * Misc - Add support for WooCommerce 4.1

= 2020.04.27 - version 1.11.1 =
 * Fix - Fix integration with WPML

= 2020.04.23 - version 1.11.0 =
 * Tweak - Sync products using Facebook's feed pull method
 * Fix - When filtering products by sync enabled status, make sure variable products with sync disabled status do not show up in results
 * Fix - Make sure that the Facebook sync enabled and catalog visibility columns are properly displayed on narrow screen sizes on some browsers
 * Fix - Do not show a confirmation modal when saving a variable product that was previously synced but belongs now to a term excluded from sync
 * Fix - Ensure variable products excluded from sync are not synced in Facebook
 * Fix - Trigger a modal prompt when attempting to enable sync for variations of a variable product that belongs to a term excluded from sync
 * Fix - Address potential PHP warnings in the product feed with non-standard product variations introduced by third party plugins
 * Fix - Fix a JavaScript error triggered on the settings page while trying to excluded terms from sync
 * Fix - Fix a JavaScript error triggered when saving a product and using checkboxes for tags

= 2020.03.17 - version 1.10.2 =
 * Tweak - Add a setting to easily enable debug logging
 * Tweak - Allow third party plugins and themes to track an add-to-cart event on added_to_cart JS event
 * Tweak - When excluding a product term from syncing in the plugin settings page, offer an option to hide excluded synced products from Facebook
 * Tweak - When excluding product terms from syncing in the plugin settings page, and settings are saved, exclude corresponding products from sync
 * Tweak - Improve error messages shown when a problem occurs during products sync
 * Tweak - Log Graph API communication if logging is enabled
 * Fix - When excluding a product term from syncing in the plugin settings page, ensure a modal opens to warn about possible conflicts with already synced products
 * Fix - Messenger settings fields will correctly reflect the values selected during initial setup
 * Fix - Fix a bug that caused newly added gallery images not to be synced immediately after they were added
 * Fix - Fix a bug that prevented gallery images from being removed from products on Facebook
 * Fix - Fix AddToCart Pixel event tracking when adding products from archive with AJAX and redirect to cart enabled
 * Fix - Fix undefined index and undefined property notices.
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
