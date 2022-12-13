=== Facebook for WooCommerce ===
Contributors: facebook, automattic, woothemes
Tags: facebook, shop, catalog, advertise, pixel, product
Requires at least: 4.4
Tested up to: 6.1
Stable tag: 3.0.6
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

= 3.0.6 - 2022-12-13 =
* Dev - Add node and npm version restrictions.
* Fix  - PHP Warning: Attempt to read property on array in Tracker.php.
* Fix - Deprecated notice fix.
* Fix - Facebook Sync status is incorrect when a product has catalog visibility hidden.
* Fix - Issue in running Background Process Test debug tool.
* Tweak - WC 7.2 compatibility.

= 3.0.5 - 2022-11-30 =
* Add - Debug tools to help reset settings, delete background options and delete catalog products.
* Add - Inbox note about Facebook menu moved under the Marketing menu.
* Dev - Add .nvmrc file.
* Fix - Facebook Product ID is different from what Facebook ID actually is.
* Fix - Prevent class redeclaration error for class WC_Facebookcommerce_Utils.
* Fix - Prevent errors in the disconnection procedure when the user id is missing.
* Tweak - Remove Facebook Orders sync functionality.
* Tweak - Update the API version set in the fbAsyncInit script in Advertise tab.
* Tweak - Update the plugin URI in the plugin file.

= 3.0.4 - 2022-11-21 =
* Dev - Ensure return value matches method signature.

= 3.0.3 - 2022-11-18 =
* Fix - Remove flexible heredoc syntax that is incompatible with PHP 7.2

= 3.0.2 - 2022-11-18 =
* Fix - Properly handle API exceptions
* Fix - Set correct PHP version in plugin header
* Dev - Add ArrayAccess implementation to JSONResponse class

= 3.0.1 - 2022-11-17 =
* Fix - Wrong path to the fbutils.php file.

= 3.0.0 - 2022-11-17 =
* Dev - Adding API Unit Tests.
* Dev - Adding unit test workflow.
* Dev - Adjusting php code styling.
* Dev - Refactoring multiple Facebook APIs into a single one.
* Dev - Removing SkyVerge dependency.
* Dev - Removing deprecations.
* Tweak - WC 5.4 compatibility.

= 2.6.30 - 2022-11-09 =
* Fix - Add backward compatibility for WC 6.1, 6.2, and 6.3 versions.
* Fix - Sync product set when the term name changes.

= 2.6.29 - 2022-11-08 =
* Add - Facebook Product Set under the Marketing menu.
* Add - HPOS Compatibility.
* Add - Inbox note about Facebook menu moved under the Marketing menu.
* Add - Set up Facebook task to the WooCommerce admin tasks.
* Dev - Replaced methods from classes in the `Internal` namespace.
* Fix - Ensure the enhanced product enhance catalog attributes value is unslashed before saving in the post_meta table.
* Fix - Hosted Woo Updates.
* Fix - Release/2.6.28.
* Fix - duplicate InitiateCheckout when using checkout block.
* Tweak - WC 7.1 compatibility.
* Tweak - WP 6.1 compatibility.
* Update - FB Product Set name changed to Facebook Product Set.
* Update - On successful FBE install users will be redirected to Advertise tab of the plugin.

= 2.6.28 - 2022-10-25 =
* Fix - Ensure bundles are not treated as virtual products on product_sync.
* Fix - Ensure google-product-category-fields-loads.js loads only on the product category screens.
* Fix - Server side sending of pixel events blocks generating pages .

= 2.6.27 - 2022-10-14 =
* Fix - Revert "Switch to Jetpack autoloader. (#1996 PR refresh)".

= 2.6.26 - 2022-10-13 =
* Add - wc_facebook_should_sync_product filter.
* Dev - Rename JobRegistry to JobManager.
* Dev - Replace composer autoloader with Jetpack autoloader.
* Fix - Fix content_name and content_category attributes set on ViewCategory pixel events.
* Tweak - WC 7.0 compatibility.

= 2.6.25 - 2022-10-04 =
* Add - New filter (wc_facebook_product_group_default_variation) to allow customizing a product group's default variation.
* Update - Remove Skyverge's sake as a dependency from the extension build process.

= 2.6.24 - 2022-09-27 =
* Fix - Adds helpful admin notices for correct user roles.
* Fix - Track purchase event flag in session variable instead post meta table.

= 2.6.23 - 2022-09-13 =
* Add - Show warning when creating product set with excluded categories.
* Fix - Messenger settings are no longer overridden after business config refresh.
* Fix - PHP notice thrown by get_page_id() in facebook-for-woocommerce/includes/API/FBE/Installation/Read/Response.php.
* Fix - When disabling Enable Messenger on the Messenger setting page, the setting does not persist after selecting Save Changes.

= 2.6.22 - 2022-09-06 =
* Fix - Adding an excluded category doesn't remove that category synced products.
* Fix - Ensure content_name and content_ids addToCart pixel event properties are correct for variable products when redirect to cart is enabled in WooCommerce.
* Fix - Remove out-of-stock products on Facebook when the "Hide out of stock items from the catalog" option in WooCommerce is checked.
* Tweak - WC 6.9 compatibility.
* Update - Facebook Business Extension flow from COMMERCE_OFFSITE to DEFAULT.

= 2.6.21 - 2022-08-16 =
* Dev - Add branch-labels GH workflow.
* Fix - `Undefined array key "HTTP_REFERER"` not longer happens when `new Event` is triggered from an AJAX call that doesn't include a referrer (likely due to browser configuration).
* Tweak - WC 6.8 compatibility.
* Tweak - WP 6.0 compatibility.

= 2.6.20 - 2022-08-09 =
* Fix - Ensure product is deleted from FB when moved to trash.
* Fix - Price not updating when the sale price is removed.

= 2.6.19 - 2022-07-27 =
* Add  - `wc_facebook_string_apply_shortcodes` filter to check whether to apply shortcodes on a string before syncing.
* Tweak - Use the Heartbeat system to refresh the local business configuration data with the latest from Facebook.
* Tweak - WC 6.8 compatibility.

= 2.6.18 - 2022-07-19 =
* Fix - Misaligned help icons on Product Categories > Google Product Categories form.
* Fix - Syncing WC custom placeholder to Facebook shop.
* Fix - is_search() causing fatal error when custom queries are used.

= 2.6.17 - 2022-07-06 =
* Fix - Add allow-plugins directive and adjust phpcs GitHub workflow.
* Fix - Scheduled product not synced when status becomes "publish".
* Tweak - WooCommerce 6.7 compatibility.
* Update - Facebook Marketing API from v12.0 to v13.0.

= 2.6.16 - 2022-06-07 =
* Fix - Updating reference from old master branch.
* Tweak - WC 6.6 compatibility.

= 2.6.15 - 2022-06-01 =
* Fix - Do not set `sale_price` when the product is not on sale.
* Fix - FB Pixel is missing some ajax Add to cart events.
* Fix - Feed visibility field value for hidden items.
* Fix - Wrong Value Field in AddToCart Events.
* Tweak - Not show the removed from sync confirm modal for unpublished products.

= 2.6.14 - 2022-05-18 =
* Fix - Non-latin custom product attribute names sync.
* Fix - Syncing brand FB attribute instead of the website name.
* Fix - Trigger InitiateCheckout event when site uses checkout block.
* Fix - Wrong sale price start date getting synced to FB Catalog.
* Fix - Allow products with "shop only" WooCommerce catalog visibility to sync to FB.
* Fix - Remove semicolon from custom attribute value.
* Tweak - Update the __experimental_woocommerce_blocks_checkout_update_order_meta action.
* Tweak - WooCommerce 6.5 compatibility.
* Tweak - WordPress 6.0 compatibility.

= 2.6.13 - 2022-04-26 =
* Fix - Issue with Facebook not displayed in the new WC navigation.
* Fix - Issue with variable products syncing to FB product sets.
* Fix - Scheduled job logs written to options table are never removed if job does not complete.
* Fix - User-Agent to contain English extension name.
* Fix - clear out wc_facebook_external_business_id option on disconnect.
* Fix - fix product title length check to account for encoding.
* Tweak - Use `Automattic\WooCommerce\Admin\Features\Features::is_enabled` instead of the deprecated `WooCommerce\Admin\Loader::is_feature_enabled`.

= 2.6.12 - 2022-03-08 =
* Add - Filter to change Facebook Retailer ID, wc_facebook_fb_retailer_id.

= 2.6.11 - 2022-02-28 =
* Fix - The syntax parsing error "unexpected ')'" in facebook-for-woocommerce.php.

= 2.6.10 - 2022-02-22 =
* Add - Filter to block full catalog batch API sync 'facebook_for_woocommerce_block_full_batch_api_sync'.
* Update - Deprecate 'facebook_for_woocommerce_allow_full_batch_api_sync' filter.
* Update - Facebook Marketing API from v11.0 to v12.0.

= 2.6.9 - 2022-01-14 =
* Fix - Replace is_ajax with wp_doing_ajax
* Tweak - Update contributor guidelines
* Tweak - WC 6.1 compatibility

= 2.6.8 - 2021-12-21 =
* Fix - Bump template from 1.0.4 to 1.0.5. #2115
* Fix - Fix empty "value" for variable products. #1784
* Tweak - WC 6.0 compatibility.
* Tweak - WP 5.9 compatibility.

= 2.6.7 - 2021-11-04 =
* Fix - Parameter overloading error for PHP70 #2112

= 2.6.6 - 2021-11-03 =
* New - Memory improved feed generation process. #2099
* New - Add compatibility with the WooCommerce checkout block. #2095
* New - Track batched feed generation time in the tracker snapshots. #2104
* New - Track usage of the new style feed generator in the tracker snapshots. #2103
* New - Hide headers in logs for better visibility. #2093
* Dev - Update composer dependencies. #2090
* New - Add no synchronization reason to the product edit screen in the Facebook meta box. #1937
* Fix - Use published variations only for the default variation. #2091

= 2.6.5 - 2021-09-16 =
* Fix - Incorrect `is_readable()` usage when loading Integration classes.
* Tweak - WC 5.7 compatibility.
* Tweak - WP 5.8 compatibility.

= 2.6.4 - 2021-08-31 =
* Fix - Correct the version string in the plugin file to remove -dev

= 2.6.3 - 2021-08-31 =
* Fix – Include missing assets from previous build.

= 2.6.2 - 2021-08-31 =
* Fix - Update the Facebook Marketing API to version 11

= 2.6.1 - 2021-06-28 =
 * Dev - Add `facebook_for_woocommerce_allow_full_batch_api_sync` filter to allow opt-out full batch API sync, to avoid possible performance issues on large sites

= 2.6.0 - 2021-06-10 =
 * Fix – Add cron heartbeat and use to offload feed generation from init / admin_init (performance) #1953
 * Fix – Clean up background sync options (performance) #1962
 * Dev – Add tracker props to understand usage of feed-based sync and other FB business config options #1972
 * Dev – Configure release tooling to auto-update version numbers in code #1982
 * Dev – Refactor code responsible for validating whether a product should be synced to FB into one place #19333

= 2.5.1 - 2021-05-28 =
 * Fix - Reinstate reset and delete functions in Facebook metabox on Edit product admin screen

= 2.5.0 - 2021-05-19 =
 * New - Option to allow larger sites to opt-out of feed generation (product sync) job
 * New - Log connection errors to allow easier troubleshooting
 * Fix - Reduce default feed generation (product sync) interval to once per day to reduce overhead
 * Fix - Trigger feed (product sync) job from to `admin_init` to reduce impact on front-end requests
 * Fix - Ensure variable product attribute values containing comma (`,`) sync correctly
 * Fix - Use existing / current tab for connection `Get Started` button
 * Dev - Require PHP version 7.0 or newer
 * Dev - Adopt Composer autoloader to avoid manually `require`ing PHP class files
 * Dev - Adopt WooRelease release tool for deploying releases
 * Dev - Use wp-scripts to build assets
 * Dev - Add `phpcs` tooling to help standardise PHP code style
 * Dev - Add JobRegistry engine for managing periodic background batch jobs

= 2021.04.29 - version 2.4.1 =
 * Fix - PHP<7.1 incompatible code for Google Taxonomy Setting in products.

= 2021.04.23 - version 2.4.0 =
 * Tweak - Add an initial performance debug mode to measure resource usage in some areas
 * Tweak - Add 3 usage tracking properties: "is-connected", "product-sync-enabled", "messenger-enabled"
 * Fix - High memory usage when starting full catalog sync
 * Fix - High memory usage of Google Product Category data
 * Fix - Fatal error for product categories with missing attributes
 * Fix - Connection data is now correctly cleared when using the "Disconnect" button
 * Fix – Error modals when setting default exclude categories in Product sync now work correctly

= 2021.03.31 - version 2.3.5 =
 * Fix - critical issue for pre 5.0.0 WC sites

= 2021.03.30 - version 2.3.4 =
 * Feature - Add connection state to WooCommerce Usage Tracking.
 * Feature - Register WooCommerce Navigation items.
 * Fix - Disable product sync on 2.3.3 update ( temporary fix ).
 * Fix - Add default placeholder for products with no image set.
 * Fix - Undefined array key error for products without 'Product image' set.
 * Dev - PHP Deprecated: Non-static method should not be called statically.

= 2021.03.22 - version 2.3.3 =
 * Fix - WooCommerce variation attribute sync not matching Enhanced Catalog attributes.
 * Fix - Enable display names to be used for variant attribute values.
 * Fix - Performance, do not auto-load Google Categories option.
 * Fix - Logs being recorded even with debug option disabled.

= 2021.03.02 - version 2.3.2 =
 * Tweak - Bump Facebook Marketing API version to 9.0

= 2021.02.23 - version 2.3.1 =
 * Fix - Fix errors when product set is empty
 * Fix - Ensure that events have an action_source

= 2021.02.16 - version 2.3.0 =
 * Feature - Add ability to create and assign products to Facebook product sets
 * Feature - Add support for Facebook App store flow
 * Tweak - Ask merchants to delete products when changing from sync to not sync state
 * Tweak - Remove business_management permission from login scopes
 * Tweak - Store parameters for Commerce merchant settings ID and Instagram business ID
 * Fix - Fix Products::get_google_product_category_id_from_highest_category() to handle WP_Error
 * Fix - Fix random HELLO appearing in the category settings
 * Fix - Make sure that list of strings params are now converted to actual arrays. Fixes an issue with the use of the additional_features parameter

= 2020.11.19 - version 2.2.0 =
 * Feature - Add an Advertise tab in the Facebook settings page to manage Facebook ads from within WooCommerce
 * Tweak - Move the Facebook settings page into the Marketing menu item (WooCommerce 4.0+)
 * Fix - Move the filter `facebook_for_woocommerce_integration_pixel_enabled` initialization to avoid possible uncaught JavaScript errors in front end
 * Fix - Update field name and format for additional_variant_attribute to resolve Facebook catalog sync for variable products.

= 2020.11.04 - version 2.1.4 =
 * Fix - Ensure product variant attributes are correctly handled when checking for enhanced attribute values.

= 2020.10.29 - version 2.1.3 =
 * Fix - Prevent error triggered while trying to refund orders

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
