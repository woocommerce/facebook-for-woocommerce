<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

include_once('facebook-config-warmer.php');
include_once('includes/fbproduct.php');
include_once 'facebook-commerce-pixel-event.php';

class WC_Facebookcommerce_Integration extends WC_Integration {

  const FB_PRODUCT_GROUP_ID  = 'fb_product_group_id';
  const FB_PRODUCT_ITEM_ID = 'fb_product_item_id';
  const FB_PRODUCT_DESCRIPTION = 'fb_product_description';

  const FB_VISIBILITY = 'fb_visibility';

  const FB_CART_URL = 'fb_cart_url';

  const FB_MESSAGE_DISPLAY_TIME = 180;

  // Number of days to query tip.
  const FB_TIP_QUERY = 1;

  const FB_VARIANT_IMAGE = 'fb_image';

  const FB_ADMIN_MESSAGE_PREPEND = '<b>Facebook for WooCommerce</b><br/>';

  const FB_SYNC_IN_PROGRESS = 'fb_sync_in_progress';
  const FB_SYNC_REMAINING = 'fb_sync_remaining';
  const FB_SYNC_TIMEOUT = 30;
  const FB_PRIORITY_MID = 9;

  private $test_mode = false;

  public function init_settings() {
    parent::init_settings();
  }

  public function init_pixel() {
    WC_Facebookcommerce_Pixel::initialize();

    // Migrate WC customer pixel_id from WC settings to WP options.
    // This is part of a larger effort to consolidate all the FB-specific
    // settings for all plugin integrations.
    if (is_admin()) {
      $pixel_id = WC_Facebookcommerce_Pixel::get_pixel_id();
      $settings_pixel_id = isset($this->settings['fb_pixel_id']) ?
        (string)$this->settings['fb_pixel_id'] : null;
      if (
        WC_Facebookcommerce_Utils::is_valid_id($settings_pixel_id) &&
        (!WC_Facebookcommerce_Utils::is_valid_id($pixel_id) ||
          $pixel_id != $settings_pixel_id
        )
      ) {
        WC_Facebookcommerce_Pixel::set_pixel_id($settings_pixel_id);
      }
    }
  }

  /**
   * Init and hook in the integration.
   *
   * @access public
   * @return void
   */
  public function __construct() {
    if (!class_exists('WC_Facebookcommerce_REST_Controller')) {
      include_once( 'includes/fbcustomapi.php' );
      $this->customapi = new WC_Facebookcommerce_REST_Controller();
    }

    if (!class_exists('WC_Facebookcommerce_EventsTracker')) {
      include_once 'facebook-commerce-events-tracker.php';
    }

    $this->id = 'facebookcommerce';
    $this->method_title = __(
      'Facebook for WooCommerce',
      'facebook-for-commerce');
    $this->method_description = __(
      'Facebook Commerce and Dynamic Ads (Pixel) Extension',
      'facebook-for-commerce');

    // Load the settings.
    $this->init_settings();

    $this->page_id = isset($this->settings['fb_page_id'])
     ? $this->settings['fb_page_id']
     : '';

    $this->api_key = isset($this->settings['fb_api_key'])
     ? $this->settings['fb_api_key']
     : '';

    $pixel_id = WC_Facebookcommerce_Pixel::get_pixel_id();
    if (!$pixel_id) {
      $pixel_id = isset($this->settings['fb_pixel_id']) ?
                  $this->settings['fb_pixel_id'] : '';
    }
    $this->pixel_id = isset($pixel_id)
    ? $pixel_id
    : '';

    $this->pixel_install_time = isset($this->settings['pixel_install_time'])
    ? $this->settings['pixel_install_time']
    : '';

    $this->use_pii = isset($this->settings['fb_pixel_use_pii'])
     && $this->settings['fb_pixel_use_pii'] === 'yes'
     ? true
     : false;

    $this->product_catalog_id = isset($this->settings['fb_product_catalog_id'])
     ? $this->settings['fb_product_catalog_id']
     : '';

    $this->external_merchant_settings_id =
    isset($this->settings['fb_external_merchant_settings_id'])
     ? $this->settings['fb_external_merchant_settings_id']
     : '';

    if (!class_exists('WC_Facebookcommerce_Utils')) {
      include_once 'includes/fbutils.php';
    }

    WC_Facebookcommerce_Utils::$ems = $this->external_merchant_settings_id;

    if (!class_exists('WC_Facebookcommerce_Graph_API')) {
      include_once 'includes/fbgraph.php';
      $this->fbgraph = new WC_Facebookcommerce_Graph_API($this->api_key);
    }

    WC_Facebookcommerce_Utils::$fbgraph = $this->fbgraph;
    $this->feed_id = isset($this->settings['fb_feed_id'])
      ? $this->settings['fb_feed_id']
      : '';

    // Hooks
    if (is_admin()) {
      $this->init_pixel();
      $this->init_form_fields();
      // Display an info banner for eligible pixel and user.
      if ($this->external_merchant_settings_id
       && $this->pixel_id
       && $this->pixel_install_time) {
        $should_query_tip =
          WC_Facebookcommerce_Utils::check_time_cap(
            get_option('fb_info_banner_last_query_time', ''),
            self::FB_TIP_QUERY);
        $last_tip_info = WC_Facebookcommerce_Utils::get_cached_best_tip();

        if ($should_query_tip || $last_tip_info) {
          if (!class_exists('WC_Facebookcommerce_Info_Banner')) {
            include_once 'includes/fbinfobanner.php';
          }
          WC_Facebookcommerce_Info_Banner::get_instance(
              $this->external_merchant_settings_id,
              $this->fbgraph,
              $should_query_tip);
        }
      }
      $this->fb_check_for_new_version();

      if (!class_exists('WC_Facebook_Integration_Test')) {
        include_once 'includes/test/facebook-integration-test.php';
      }
      $integration_test = WC_Facebook_Integration_Test::get_instance($this);
      $integration_test::$fbgraph = $this->fbgraph;

      if (!$this->pixel_install_time && $this->pixel_id) {
        $this->pixel_install_time = current_time('mysql');
        $this->settings['pixel_install_time'] = $this->pixel_install_time;
        update_option(
          $this->get_option_key(),
          apply_filters(
            'woocommerce_settings_api_sanitized_fields_' . $this->id,
              $this->settings));
      }
      add_action('admin_notices', array( $this, 'checks' ));
      add_action('woocommerce_update_options_integration_facebookcommerce',
        array($this, 'process_admin_options'));
      add_action('admin_enqueue_scripts', array( $this, 'load_assets'));

      add_action('wp_ajax_ajax_save_fb_settings',
        array($this, 'ajax_save_fb_settings'), self::FB_PRIORITY_MID);

      add_action('wp_ajax_ajax_delete_fb_settings',
        array($this, 'ajax_delete_fb_settings'), self::FB_PRIORITY_MID);

      add_action('wp_ajax_ajax_sync_all_fb_products',
        array($this, 'ajax_sync_all_fb_products'), self::FB_PRIORITY_MID);

      add_action('wp_ajax_ajax_sync_all_fb_products_using_feed',
        array($this, 'ajax_sync_all_fb_products_using_feed'),
        self::FB_PRIORITY_MID);

      add_action('wp_ajax_ajax_check_feed_upload_status',
        array($this, 'ajax_check_feed_upload_status'),
        self::FB_PRIORITY_MID);

      add_action('wp_ajax_ajax_reset_all_fb_products',
        array($this, 'ajax_reset_all_fb_products'),
        self::FB_PRIORITY_MID);
      add_action('wp_ajax_ajax_display_test_result',
        array($this, 'ajax_display_test_result'));

      add_action('wp_ajax_ajax_schedule_force_resync',
        array($this, 'ajax_schedule_force_resync'), self::FB_PRIORITY_MID);

      add_action('wp_ajax_ajax_update_fb_option',
        array($this, 'ajax_update_fb_option'), self::FB_PRIORITY_MID);

      // Only load product processing hooks if we have completed setup.
      if ($this->api_key && $this->product_catalog_id) {
        add_action(
          'woocommerce_process_product_meta_simple',
          array($this, 'on_simple_product_publish'),
          10,  // Action priority
          1    // Args passed to on_product_publish (should be 'id')
        );

        add_action(
          'woocommerce_process_product_meta_variable',
          array($this, 'on_variable_product_publish'),
          10,  // Action priority
          1    // Args passed to on_product_publish (should be 'id')
        );

        add_action(
          'woocommerce_process_product_meta_booking',
          array($this, 'on_simple_product_publish'),
          10,  // Action priority
          1    // Args passed to on_product_publish (should be 'id')
        );

        add_action(
          'woocommerce_process_product_meta_external',
          array($this, 'on_simple_product_publish'),
          10,  // Action priority
          1    // Args passed to on_product_publish (should be 'id')
        );

        add_action(
          'woocommerce_process_product_meta_subscription',
          array($this, 'on_product_publish'),
          10,  // Action priority
          1    // Args passed to on_product_publish (should be 'id')
        );

        add_action(
          'woocommerce_process_product_meta_variable-subscription',
          array($this, 'on_product_publish'),
          10,  // Action priority
          1    // Args passed to on_product_publish (should be 'id')
        );

        add_action('woocommerce_process_product_meta_bundle',
          array($this, 'on_product_publish'),
          10,  // Action priority
          1    // Args passed to on_product_publish (should be 'id')
        );

        add_action(
          'woocommerce_product_quick_edit_save',
          array($this, 'on_quick_and_bulk_edit_save'),
          10,  // Action priority
          1    // Args passed to on_quick_and_bulk_edit_save ('product')
        );

        add_action(
          'woocommerce_product_bulk_edit_save',
          array($this, 'on_quick_and_bulk_edit_save'),
          10,  // Action priority
          1    // Args passed to on_quick_and_bulk_edit_save ('product')
        );

        add_action(
          'before_delete_post',
          array($this, 'on_product_delete'),
          10,
          1);

        add_action('add_meta_boxes', array($this, 'fb_product_metabox'), 10, 1);

        add_filter('manage_product_posts_columns',
          array($this, 'fb_product_columns'));
        add_action('manage_product_posts_custom_column',
          array( $this, 'fb_render_product_columns' ), 2);
        add_action('transition_post_status',
          array($this, 'fb_change_product_published_status'), 10, 3);

        // Product data tab
        add_filter('woocommerce_product_data_tabs',
          array($this, 'fb_new_product_tab'));

        add_action('woocommerce_product_data_panels',
          array($this, 'fb_new_product_tab_content' ));

        add_action('wp_ajax_ajax_fb_toggle_visibility',
          array($this, 'ajax_toggle_visibility'));

        add_action('wp_ajax_ajax_reset_single_fb_product',
          array($this, 'ajax_reset_single_fb_product'));

        add_action('wp_ajax_ajax_delete_fb_product',
          array($this, 'ajax_delete_fb_product'));

        add_filter('woocommerce_duplicate_product_exclude_meta',
          array($this, 'fb_duplicate_product_reset_meta'));

        add_action('pmxi_after_xml_import',
          array($this, 'wp_all_import_compat'));

        add_action('wp_ajax_wpmelon_adv_bulk_edit',
          array($this, 'ajax_woo_adv_bulk_edit_compat'), self::FB_PRIORITY_MID);

        // Used to remove the 'you need to resync' message.
        if (isset($_GET['remove_sticky'])) {
          $this->remove_sticky_message();
        }

        if (defined('ICL_LANGUAGE_CODE')) {
          include_once('includes/fbwpml.php');
          new WC_Facebook_WPML_Injector();
        }

      }
      $this->load_background_sync_process();
    }
    // Must be outside of admin for cron to schedule correctly.
    add_action('sync_all_fb_products_using_feed',
      array($this, 'sync_all_fb_products_using_feed'),
      self::FB_PRIORITY_MID);

    if ($this->pixel_id) {
      $user_info = WC_Facebookcommerce_Utils::get_user_info($this->use_pii);
      $this->events_tracker = new WC_Facebookcommerce_EventsTracker($user_info);
    }

    if (isset($this->settings['is_messenger_chat_plugin_enabled']) &&
        $this->settings['is_messenger_chat_plugin_enabled'] === 'yes') {
      if (!class_exists('WC_Facebookcommerce_MessengerChat')) {
        include_once 'facebook-commerce-messenger-chat.php';
      }
      $this->messenger_chat = new WC_Facebookcommerce_MessengerChat($this->settings);
    }
  }

  public function load_background_sync_process() {
    // Attempt to load background processing (Woo 3.x.x only)
    include_once('includes/fbbackground.php');
    if (class_exists('WC_Facebookcommerce_Background_Process')) {
      if (!isset($this->background_processor)) {
        $this->background_processor =
        new WC_Facebookcommerce_Background_Process($this);
      }
    }
    add_action('wp_ajax_ajax_fb_background_check_queue',
      array($this, 'ajax_fb_background_check_queue'));
  }

  public function ajax_fb_background_check_queue() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions('background check queue', true);
    $request_time = null;
    if (isset($_POST['request_time'])) {
      $request_time = esc_js($_POST['request_time']);
    }
    if ($this->settings['fb_api_key']) {
      if (isset($this->background_processor)) {
        $is_processing = $this->background_processor->handle_cron_healthcheck();
        $remaining = $this->background_processor->get_item_count();
        $response = array(
          'connected'  => true,
          'background' => true,
          'processing' => $is_processing,
          'remaining'  => $remaining,
          'request_time' => $request_time,
        );
      } else {
        $response = array(
          'connected' => true,
          'background' => false,
        );
      }
    } else {
      $response = array(
        'connected' => false,
        'background' => false,
      );
    }
    printf(json_encode($response));
    wp_die();
  }

  public function fb_new_product_tab($tabs) {
    $tabs['fb_commerce_tab'] = array(
      'label'   => __('Facebook', 'facebook-for-woocommerce'),
      'target' => 'facebook_options',
      'class'   => array( 'show_if_simple', 'show_if_variable'  ),
    );
    return $tabs;
  }

  public function fb_check_for_new_version() {
    if (!class_exists('WC_Facebook_Github_Updater')) {
      include_once 'includes/fb-github-plugin-updater.php';
    }
    $path = __FILE__;
    $path = substr($path, 0, strrpos($path, '/') + 1) .
      'facebook-for-woocommerce.php';
    WC_Facebook_Github_Updater::get_instance(
      $path, 'facebookincubator', 'facebook-for-woocommerce');
  }

  public function fb_new_product_tab_content() {
    global $post;
    $woo_product = new WC_Facebook_Product($post->ID);
    $description = get_post_meta(
      $post->ID,
      self::FB_PRODUCT_DESCRIPTION,
      true);

    $price = get_post_meta(
      $post->ID,
      WC_Facebook_Product::FB_PRODUCT_PRICE,
      true);

    $image = get_post_meta(
      $post->ID,
      WC_Facebook_Product::FB_PRODUCT_IMAGE,
      true);

    $image_setting = null;
    if (WC_Facebookcommerce_Utils::is_variable_type($woo_product->get_type())) {
      $image_setting = $woo_product->get_use_parent_image();
    }

    // 'id' attribute needs to match the 'target' parameter set above
    ?><div id='facebook_options' class='panel woocommerce_options_panel'><?php
      ?><div class='options_group'><?php
        woocommerce_wp_textarea_input(
          array(
            'id' => self::FB_PRODUCT_DESCRIPTION,
            'label' => __('Facebook Description', 'facebook-for-woocommerce'),
            'desc_tip' => 'true',
            'description' => __(
              'Custom (plain-text only) description for product on Facebook. '.
              'If blank, product description will be used. ' .
              'If product description is blank, shortname will be used.',
              'facebook-for-woocommerce'),
            'cols' => 40,
            'rows' => 20,
            'value' => $description,
          ));
      woocommerce_wp_textarea_input(
        array(
          'id' => WC_Facebook_Product::FB_PRODUCT_IMAGE,
          'label' => __('Facebook Product Image', 'facebook-for-woocommerce'),
          'desc_tip' => 'true',
          'description' => __(
            'Image URL for product on Facebook. Must be an absolute URL '.
            'e.g. https://...'.
            'This can be used to override the primary image that will be '.
            'used on Facebook for this product. If blank, the primary '.
            'product image in Woo will be used as the primary image on FB.',
            'facebook-for-woocommerce'),
          'cols' => 40,
          'rows' => 10,
          'value' => $image,
        ));
        woocommerce_wp_text_input(
          array(
            'id' => WC_Facebook_Product::FB_PRODUCT_PRICE,
            'label' => __('Facebook Price (' .
              get_woocommerce_currency_symbol() . ')', 'facebook-for-woocommerce'),
            'desc_tip' => 'true',
            'description' => __(
              'Custom price for product on Facebook. '.
              'Please enter in monetary decimal (.) format without thousand '.
              'separators and currency symbols. '.
              'If blank, product price will be used. ',
              'facebook-for-woocommerce'),
            'cols' => 40,
            'rows' => 60,
            'value' => $price,
          ));
        if ($image_setting !== null) {
         woocommerce_wp_checkbox(array(
          'id'    => self::FB_VARIANT_IMAGE,
          'label' => __('Use Parent Image', 'facebook-for-woocommerce'),
          'required'  => false,
          'desc_tip' => 'true',
          'description' => __(
            ' By default, the primary image uploaded to Facebook is the image'.
            ' specified in each variant, if provided. '.
            ' However, if you enable this setting, the '.
            ' image of the parent will be used as the primary image'.
            ' for this product and all its variants instead.'),
          'value' => $image_setting ? 'yes' : 'no',
         ));
        }
      ?>
      </div>
    </div><?php
  }

  public function fb_product_columns($existing_columns) {
    if (empty($existing_columns) && ! is_array($existing_columns)) {
      $existing_columns = array();
    }

    $columns = array();
    $columns['fb'] = __('FB Shop', 'facebook-for-woocommerce');

    // Verify that cart URL hasn't changed.  We do it here because this page
    // is most likely to be visited (so it's a handy place to make the check)
    $cart_url = get_option(self::FB_CART_URL);
    if (!empty($cart_url) && (wc_get_cart_url() !== $cart_url)) {
      $this->display_warning_message('One or more of your products is using a
        checkout URL that may be different than your shop checkout URL.
        <a href="' . WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL . '">
      Re-sync your products to update checkout URLs on Facebook.</a>');
    }

    return array_merge($columns, $existing_columns);
  }

  public function fb_render_product_columns($column) {
    global $post, $the_product;

    wp_enqueue_script('wc_facebook_jsx', plugins_url(
      '/assets/js/facebook-products.js?ts=' . time(), __FILE__));

    if (empty($the_product) || $the_product->get_id() != $post->ID) {
      $the_product = new WC_Facebook_Product($post);
    }

    if ($column === 'fb') {
      $fb_product_group_id = $this->get_product_fbid(
        self::FB_PRODUCT_GROUP_ID,
        $post->ID,
        $the_product);
      if (!$fb_product_group_id) {
        printf('<span>Not Synced</span>');
      } else {
        $viz_value = get_post_meta($post->ID, self::FB_VISIBILITY, true);
        $data_tip = $viz_value === '' ?
          'Product is synced but not marked as published (visible)
          on Facebook.' :
          'Product is synced and published (visible) on Facebook.';

        printf('<span class="tips" id="tip_%1$s" data-tip="%2$s">',
          $post->ID,
          $data_tip);

        if ($viz_value === '') {
          printf(
            '<a id="viz_%1$s" class="button button-primary button-large"
            href="javascript:;" onclick="fb_toggle_visibility(%1$s, true)">Show</a>',
            $post->ID);
        } else {
          printf(
            '<a id="viz_%1$s" class="button" href="javascript:;"
            onclick="fb_toggle_visibility(%1$s, false)">Hide</a>',
            $post->ID);
        }
      }
    }
  }

  public function fb_product_metabox() {
    wp_enqueue_script('wc_facebook_jsx', plugins_url(
      '/assets/js/facebook-metabox.js?ts=' . time(), __FILE__));

    add_meta_box(
        'facebook_metabox', // Meta box ID
        'Facebook', // Meta box Title
        array($this, 'fb_product_meta_box_html'), // Callback
        'product', // Screen to which to add the meta box
        'side' // Context
    );
  }

  public function fb_product_meta_box_html() {
    global $post;
    $woo_product = new WC_Facebook_Product($post->ID);
    $fb_product_group_id = $this->get_product_fbid(
      self::FB_PRODUCT_GROUP_ID,
      $post->ID,
      $woo_product);
    printf('<span id="fb_metadata">');
    if ($fb_product_group_id) {
      printf('Facebook ID: <a href="https://facebook.com/'.
          $fb_product_group_id . '" target="_blank">' .
          $fb_product_group_id . '</a><p/>');
      if (WC_Facebookcommerce_Utils::is_variable_type($woo_product->get_type())) {
        printf('<p>Variant IDs:<br/>');
        $children = $woo_product->get_children();
        foreach ($children as $child_id) {
          $fb_product_item_id = $this->get_product_fbid(
            self::FB_PRODUCT_ITEM_ID,
            $child_id);
          printf($child_id .' : <a href="https://facebook.com/'.
          $fb_product_item_id . '" target="_blank">' .
          $fb_product_item_id . '</a><br/>');
        }
        printf('</p>');
      }

      $checkbox_value = get_post_meta($post->ID, self::FB_VISIBILITY, true);

      printf('Visible:  <input name="%1$s" type="checkbox" value="1" %2$s/>',
        self::FB_VISIBILITY,
        $checkbox_value === '' ? '' : 'checked');
      printf('<p/><input name="is_product_page" type="hidden" value="1"');

      printf(
        '<p/><a href="#" onclick="fb_reset_product(%1$s)">
          Reset Facebook metadata</a>',
        $post->ID);

      printf(
        '<p/><a href="#" onclick="fb_delete_product(%1$s)">
          Delete product(s) on Facebook</a>',
        $post->ID);
    } else {
      printf("<b>This product is not yet synced to Facebook.</b>");
    }
    printf('</span>');
  }

  private function get_product_count() {
    $args = array(
      'post_type' => 'product',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'fields'         => 'ids'
    );
    $products = new WP_Query($args);

    wp_reset_postdata();

    echo $products->found_posts;
  }

  /**
   * Load DIA specific JS Data
   */
  public function load_assets() {
    $screen = get_current_screen();

    // load banner assets
    wp_enqueue_script('wc_facebook_infobanner_jsx', plugins_url(
      '/assets/js/facebook-infobanner.js?ts=' . time(), __FILE__));

    wp_enqueue_style('wc_facebook_infobanner_css', plugins_url(
      '/assets/css/facebook-infobanner.css', __FILE__));

    if (strpos($screen->id , "page_wc-settings") == 0) {
      return;
    }

    if (empty($_GET['tab'])) {
      return;
    }

    if ('integration' !== $_GET['tab']) {
      return;
    }

    ?>
    <script>
    window.facebookAdsToolboxConfig = {
      hasGzipSupport:
        '<?php echo extension_loaded('zlib') ? 'true' : 'false' ?>'
      ,enabledPlugins: ['MESSENGER_CHAT','INSTAGRAM_SHOP']
      ,enableSubscription:
        '<?php echo class_exists('WC_Subscriptions') ? 'true' : 'false' ?>'
      ,popupOrigin: '<?php echo isset($_GET['url']) ? esc_js($_GET['url']) :
        'https://www.facebook.com/' ?>'
      ,feedWasDisabled: 'true'
      ,platform: 'WooCommerce'
      ,pixel: {
        pixelId: '<?php echo $this->pixel_id ?: '' ?>'
       ,advanced_matching_supported: true
      }
      ,diaSettingId: '<?php echo $this->external_merchant_settings_id ?: '' ?>'
      ,store: {
        baseUrl: window.location.protocol + '//' + window.location.host
        ,baseCurrency:
        '<?php echo esc_js(
            WC_Admin_Settings::get_option('woocommerce_currency'))?>'
        ,timezoneId: '<?php echo date('Z') ?>'
        ,storeName:
          '<?php echo esc_js(WC_Facebookcommerce_Utils::get_store_name()); ?>'
        ,version: '<?php echo WC()->version ?>'
        ,php_version: '<?php echo PHP_VERSION ?>'
        ,plugin_version:
          '<?php echo WC_Facebookcommerce_Utils::PLUGIN_VERSION ?>'
      }
      ,feed: {
        totalVisibleProducts: '<?php echo $this->get_product_count() ?>'
        ,hasClientSideFeedUpload: '<?php echo !!$this->feed_id ?>'
      }
      ,feedPrepared: {
        feedUrl: ''
        ,feedPingUrl: ''
        ,samples: <?php echo $this->get_sample_product_feed()?>
      }
      ,tokenExpired: '<?php echo $this->settings['fb_api_key'] &&
        !$this->get_page_name()?>'
    };
    </script>
  <?php
    wp_enqueue_script('wc_facebook_jsx', plugins_url(
      '/assets/js/facebook-settings.js?ts=' . time(), __FILE__));
    wp_enqueue_style('wc_facebook_css', plugins_url(
      '/assets/css/facebook.css', __FILE__));
  }

  function on_product_delete($wp_id) {
    $woo_product = new WC_Facebook_Product($wp_id);
    if (!$woo_product->exists()) {
      // This happens when the wp_id is not a product or it's already
      // been deleted.
      return;
    }
    $fb_product_group_id = $this->get_product_fbid(
      self::FB_PRODUCT_GROUP_ID,
      $wp_id,
      $woo_product);
    $fb_product_item_id = $this->get_product_fbid(
      self::FB_PRODUCT_ITEM_ID,
      $wp_id,
      $woo_product);
    if (! ($fb_product_group_id || $fb_product_item_id ) ) {
      return;  // No synced product, no-op.
    }
    $products = array($wp_id);
    if (WC_Facebookcommerce_Utils::is_variable_type($woo_product->get_type())) {
      $children = $woo_product->get_children();
      $products = array_merge($products, $children);
    }
    foreach ($products as $item_id) {
      $this->delete_product_item($item_id);
    }
    if ($fb_product_group_id) {
      $pg_result = $this->fbgraph->delete_product_group($fb_product_group_id);
      WC_Facebookcommerce_Utils::log($pg_result);
    }
  }

  /**
   * Update FB visibility for trashing and restore.
   */
  function fb_change_product_published_status($new_status, $old_status, $post) {
    global $post;
    $visibility = $new_status == 'publish' ? 'published' : 'staging';

    // change from publish status -> unpublish status, e.g. trash, draft, etc.
    // change from trash status -> publish status
    // no need to update for change from trash <-> unpublish status
    if (($old_status == 'publish' && $new_status != 'publish') ||
      ($old_status == 'trash' && $new_status == 'publish')) {
        $this->update_fb_visibility($post->ID, $visibility);
    }
  }

  /**
   * Generic function for use with any product publishing.
   * Will determine product type (simple or variable) and delegate to
   * appropriate handler.
   */
  function on_product_publish($wp_id) {
    if (get_post_status($wp_id) != 'publish') {
      return;
    }

    $woo_product = new WC_Facebook_Product($wp_id);
    $product_type = $woo_product->get_type();
    if (WC_Facebookcommerce_Utils::is_variable_type($woo_product->get_type())) {
      $this->on_variable_product_publish($wp_id, $woo_product);
    } else {
      $this->on_simple_product_publish($wp_id, $woo_product);
    }
  }

  /**
   * If the user has opt-in to remove products that are out of stock,
   * this function will delete the product from FB Page as well.
   */
  function delete_on_out_of_stock($wp_id, $woo_product) {
    if (get_option('woocommerce_hide_out_of_stock_items') === 'yes' &&
      !$woo_product->is_in_stock()) {
      $this->delete_product_item($wp_id);
      return true;
    }
    return false;
  }

  function on_variable_product_publish($wp_id, $woo_product = null) {
    if (get_option('fb_disable_sync_on_dev_environment', false)) {
      return;
    }

    if (get_post_status($wp_id) != 'publish') {
      return;
    }
    // Check if product group has been published to FB.  If not, it's new.
    // If yes, loop through variants and see if product items are published.
    if (!$woo_product) {
      $woo_product = new WC_Facebook_Product($wp_id);
    }

    if ($this->delete_on_out_of_stock($wp_id, $woo_product)) {
      return;
    }

    if (isset($_POST[self::FB_PRODUCT_DESCRIPTION])) {
      $woo_product->set_description($_POST[self::FB_PRODUCT_DESCRIPTION]);
    }
    if (isset($_POST[WC_Facebook_Product::FB_PRODUCT_PRICE])) {
      $woo_product->set_price($_POST[WC_Facebook_Product::FB_PRODUCT_PRICE]);
    }
    if (isset($_POST[WC_Facebook_Product::FB_PRODUCT_IMAGE])) {
      $woo_product->set_product_image($_POST[WC_Facebook_Product::FB_PRODUCT_IMAGE]);
    }

    $woo_product->set_use_parent_image(
      (isset($_POST[self::FB_VARIANT_IMAGE])) ?
        $_POST[self::FB_VARIANT_IMAGE] :
        null);
    $fb_product_group_id = $this->get_product_fbid(
      self::FB_PRODUCT_GROUP_ID,
      $wp_id,
      $woo_product);

    if ($fb_product_group_id) {
      $woo_product->update_visibility(
        isset($_POST['is_product_page']),
        isset($_POST[self::FB_VISIBILITY]));
      $this->update_product_group($woo_product);
      $child_products = $woo_product->get_children();
      $variation_id = $woo_product->find_matching_product_variation();
      // check if item_id is default variation. If yes, update in the end.
      // If default variation value is to update, delete old fb_product_item_id
      // and create new one in order to make it order correctly.
      foreach ($child_products as $item_id) {
        $fb_product_item_id =
          $this->on_simple_product_publish($item_id, null, $woo_product);
        if ($item_id == $variation_id && $fb_product_item_id) {
          $this->set_default_variant($fb_product_group_id, $fb_product_item_id);
        }
      }
    } else {
      $this->create_product_variable($woo_product);
    }
  }

  function on_simple_product_publish(
    $wp_id,
    $woo_product = null,
    &$parent_product = null) {
    if (get_option('fb_disable_sync_on_dev_environment', false)) {
      return;
    }

    if (get_post_status($wp_id) != 'publish') {
      return;
    }

    if (!$woo_product) {
      $woo_product = new WC_Facebook_Product($wp_id, $parent_product);
    }

    if ($this->delete_on_out_of_stock($wp_id, $woo_product)) {
      return;
    }

    if (isset($_POST[self::FB_PRODUCT_DESCRIPTION])) {
      $woo_product->set_description($_POST[self::FB_PRODUCT_DESCRIPTION]);
    }

    if (isset($_POST[WC_Facebook_Product::FB_PRODUCT_PRICE])) {
      $woo_product->set_price($_POST[WC_Facebook_Product::FB_PRODUCT_PRICE]);
    }

    if (isset($_POST[WC_Facebook_Product::FB_PRODUCT_IMAGE])) {
      $woo_product->set_product_image($_POST[WC_Facebook_Product::FB_PRODUCT_IMAGE]);
    }

    // Check if this product has already been published to FB.
    // If not, it's new!
    $fb_product_item_id = $this->get_product_fbid(
      self::FB_PRODUCT_ITEM_ID,
      $wp_id,
      $woo_product);

    if ($fb_product_item_id) {
      $woo_product->update_visibility(
        isset($_POST['is_product_page']),
        isset($_POST[self::FB_VISIBILITY]));
      $this->update_product_item($woo_product, $fb_product_item_id);
      return $fb_product_item_id;
    } else {
      // Check if this is a new product item for an existing product group
      if ($woo_product->get_parent_id()) {
        $fb_product_group_id = $this->get_product_fbid(
          self::FB_PRODUCT_GROUP_ID,
          $woo_product->get_parent_id(),
          $woo_product);

        // New variant added
        if ($fb_product_group_id) {
          return
            $this->create_product_simple($woo_product, $fb_product_group_id);
        } else {
          WC_Facebookcommerce_Utils::fblog(
            "Wrong! simple_product_publish called without group ID for
              a variable product!", array(), true);
        }
      } else {
        return $this->create_product_simple($woo_product);  // new product
      }
    }
  }

  function create_product_variable($woo_product) {
    $retailer_id = WC_Facebookcommerce_Utils::get_fb_retailer_id($woo_product);

    $fb_product_group_id = $this->create_product_group(
      $woo_product,
      $retailer_id,
      true);

    if ($fb_product_group_id) {
      $child_products = $woo_product->get_children();
      $variation_id = $woo_product->find_matching_product_variation();
      foreach ($child_products as $item_id) {
        $child_product = new WC_Facebook_Product($item_id, $woo_product);
        $retailer_id =
          WC_Facebookcommerce_Utils::get_fb_retailer_id($child_product);
        $fb_product_item_id = $this->create_product_item(
            $child_product,
            $retailer_id,
            $fb_product_group_id);
        if ($item_id == $variation_id && $fb_product_item_id) {
          $this->set_default_variant($fb_product_group_id, $fb_product_item_id);
        }
      }
    }
  }

  /**
  * Create product group and product, store fb-specific info
  **/
  function create_product_simple($woo_product, $fb_product_group_id = null) {
    $retailer_id = WC_Facebookcommerce_Utils::get_fb_retailer_id($woo_product);

    if (!$fb_product_group_id) {
      $fb_product_group_id = $this->create_product_group(
        $woo_product,
        $retailer_id);
    }

    if ($fb_product_group_id) {
      $fb_product_item_id = $this->create_product_item(
        $woo_product,
        $retailer_id,
        $fb_product_group_id);
      return $fb_product_item_id;
    }
  }

  function create_product_group($woo_product, $retailer_id, $variants = false) {

    $product_group_data = array(
      'retailer_id' => $retailer_id,
    );

    // Default visibility on create = published
    $woo_product->fb_visibility = true;
    update_post_meta($woo_product->get_id(), self::FB_VISIBILITY, true);

    if ($variants) {
      $product_group_data['variants'] =
        $woo_product->prepare_variants_for_group();
    }

    $create_product_group_result = $this->check_api_result(
        $this->fbgraph->create_product_group(
            $this->product_catalog_id,
            $product_group_data),
          $product_group_data,
          $woo_product->get_id());

     // New variant added
    if ($create_product_group_result) {
      $decode_result = WC_Facebookcommerce_Utils::decode_json($create_product_group_result['body']);
      $fb_product_group_id = $decode_result->id;
      // update_post_meta is actually more of a create_or_update
      update_post_meta(
        $woo_product->get_id(),
        self::FB_PRODUCT_GROUP_ID,
        $fb_product_group_id);

      $this->display_success_message(
        'Created product group <a href="https://facebook.com/'.
          $fb_product_group_id . '" target="_blank">' .
          $fb_product_group_id . '</a> on Facebook.');

      return $fb_product_group_id;
    }
  }

  function create_product_item($woo_product, $retailer_id, $product_group_id) {
    // Default visibility on create = published
    $woo_product->fb_visibility = true;
    $product_data = $woo_product->prepare_product($retailer_id);
    if (!$product_data['price']) {
      return 0;
    }

    update_post_meta($woo_product->get_id(), self::FB_VISIBILITY, true);

    $product_result = $this->check_api_result(
        $this->fbgraph->create_product_item(
          $product_group_id,
          $product_data),
        $product_data,
        $woo_product->get_id());

    if ($product_result) {
      $decode_result = WC_Facebookcommerce_Utils::decode_json($product_result['body']);
      $fb_product_item_id = $decode_result->id;

      update_post_meta($woo_product->get_id(),
        self::FB_PRODUCT_ITEM_ID, $fb_product_item_id);

      $this->display_success_message(
        'Created product item <a href="https://facebook.com/'.
          $fb_product_item_id . '" target="_blank">' .
          $fb_product_item_id . '</a> on Facebook.');

      return $fb_product_item_id;
    }
  }


  /**
   * Update existing product group (variant data only)
   **/
  function update_product_group($woo_product) {
    $fb_product_group_id = $this->get_product_fbid(
      self::FB_PRODUCT_GROUP_ID,
      $woo_product->get_id(),
      $woo_product);

    if (!$fb_product_group_id) {
      return;
    }

    $variants = $woo_product->prepare_variants_for_group();

    if (!$variants) {
      WC_Facebookcommerce_Utils::log(
        sprintf(__('Nothing to update for product group for %1$s',
          'facebook-for-woocommerce'),
          $fb_product_group_id));
      return;
    }

    $product_group_data = array(
      'variants' => $variants
    );

    $result = $this->check_api_result(
      $this->fbgraph->update_product_group(
        $fb_product_group_id,
        $product_group_data));

    if ($result) {
      $this->display_success_message(
        'Updated product group <a href="https://facebook.com/'.
        $fb_product_group_id .'" target="_blank">' . $fb_product_group_id .
        '</a> on Facebook.');
    }
  }

  /**
   * Update existing product
   **/
  function update_product_item($woo_product, $fb_product_item_id) {
    $product_data = $woo_product->prepare_product();

    $result = $this->check_api_result(
      $this->fbgraph->update_product_item(
        $fb_product_item_id,
        $product_data));

    if ($result) {
      $this->display_success_message(
        'Updated product  <a href="https://facebook.com/'. $fb_product_item_id .
          '" target="_blank">' . $fb_product_item_id . '</a> on Facebook.');
    }
  }

  /**
   * Save settings via AJAX (to preserve window context for onboarding)
   **/
  function ajax_save_fb_settings() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions('save settings', true);

    if (isset($_REQUEST)) {
      if (!isset($_REQUEST['facebook_for_woocommerce'])) {
        // This is not a request from our plugin,
        // some other handler or plugin probably
        // wants to handle it and wp_die() after.
        return;
      }

      if (isset($_REQUEST['api_key']) && ctype_alnum($_REQUEST['api_key'])) {
        $this->settings['fb_api_key'] = $_REQUEST['api_key'];
      }
      if (isset($_REQUEST['product_catalog_id']) &&
        ctype_digit($_REQUEST['product_catalog_id'])) {

        if ($this->product_catalog_id != '' &&
          $this->product_catalog_id != $_REQUEST['product_catalog_id']) {
          $this->reset_all_products();
        }
        $this->settings['fb_product_catalog_id'] =
          $_REQUEST['product_catalog_id'];
      }
      if (isset($_REQUEST['pixel_id']) && ctype_digit($_REQUEST['pixel_id'])) {
        // To prevent race conditions with pixel-only settings,
        // only save a pixel if we already have an API key.
        if ($this->settings['fb_api_key']) {
          $this->settings['fb_pixel_id'] = $_REQUEST['pixel_id'];
          if ($this->pixel_id != $_REQUEST['pixel_id']) {
            $this->settings['pixel_install_time'] = current_time('mysql');
          }
        } else {
          WC_Facebookcommerce_Utils::log(
            "Got pixel-only settings, doing nothing");
          echo "Not saving pixel-only settings";
          wp_die();
        }
      }
      if (isset($_REQUEST['pixel_use_pii'])) {
          $this->settings['fb_pixel_use_pii'] =
            ($_REQUEST['pixel_use_pii'] === 'true' ||
            $_REQUEST['pixel_use_pii'] === true) ? 'yes' : 'no';
      }
      if (isset($_REQUEST['page_id']) &&
        ctype_digit($_REQUEST['page_id'])) {
          $this->settings['fb_page_id'] = $_REQUEST['page_id'];
      }
      if (isset($_REQUEST['external_merchant_settings_id']) &&
        ctype_digit($_REQUEST['external_merchant_settings_id'])) {
          $this->settings['fb_external_merchant_settings_id'] =
          $_REQUEST['external_merchant_settings_id'];
      }
      if (isset($_REQUEST['is_messenger_chat_plugin_enabled'])) {
        $this->settings['is_messenger_chat_plugin_enabled'] =
          ($_REQUEST['is_messenger_chat_plugin_enabled'] === 'true' ||
          $_REQUEST['is_messenger_chat_plugin_enabled'] === true) ? 'yes' : 'no';
      }
      if (isset($_REQUEST['facebook_jssdk_version'])) {
        $this->settings['facebook_jssdk_version'] =
          $_REQUEST['facebook_jssdk_version'];
      }
      if (isset($_REQUEST['msger_chat_customization_greeting_text_code'])) {
        $this->settings['msger_chat_customization_greeting_text_code'] =
          $_REQUEST['msger_chat_customization_greeting_text_code'];
      }
      if (isset($_REQUEST['msger_chat_customization_locale'])) {
        $this->settings['msger_chat_customization_locale'] =
          $_REQUEST['msger_chat_customization_locale'];
      }
      if (isset($_REQUEST['msger_chat_customization_theme_color_code'])) {
        $this->settings['msger_chat_customization_theme_color_code'] =
          $_REQUEST['msger_chat_customization_theme_color_code'];
      }

      update_option(
        $this->get_option_key(),
        apply_filters(
          'woocommerce_settings_api_sanitized_fields_' . $this->id,
            $this->settings));

      WC_Facebookcommerce_Utils::log("Settings saved!");
      echo "settings_saved";
    } else {
      echo "No Request";
    }

    wp_die();
  }

  /**
   * Delete all settings via AJAX
   **/
  function ajax_delete_fb_settings() {
    if (!WC_Facebookcommerce_Utils::check_woo_ajax_permissions('delete settings', false)) {
      return;
    }

    // Do not allow reset in the middle of product sync
    $currently_syncing = get_transient(self::FB_SYNC_IN_PROGRESS);
    if ($currently_syncing) {
      wp_send_json('A Facebook product sync is currently in progress.
        Deleting settings during product sync may cause errors.');
      return;
    }

    if (isset($_REQUEST)) {
      $ems = $this->settings['fb_external_merchant_settings_id'];
      if ($ems) {
        WC_Facebookcommerce_Utils::fblog(
          "Deleted all settings!",
          array(),
          false,
          $ems);
      }

      $this->init_settings();
      $this->settings['fb_api_key'] = '';
      $this->settings['fb_product_catalog_id'] = '';

      $this->settings['fb_pixel_id'] = '';
      $this->settings['fb_pixel_use_pii'] = 'no';

      $this->settings['fb_page_id'] = '';
      $this->settings['fb_external_merchant_settings_id'] = '';
      $this->settings['pixel_install_time'] = '';
      $this->settings['fb_feed_id'] = '';
      $this->settings['fb_upload_id'] = '';
      $this->settings['upload_end_time'] = '';

      WC_Facebookcommerce_Pixel::set_pixel_id(0);

      update_option(
        $this->get_option_key(),
        apply_filters(
          'woocommerce_settings_api_sanitized_fields_' . $this->id,
            $this->settings));

      // Clean up old  messages
      delete_transient('facebook_plugin_api_error');
      delete_transient('facebook_plugin_api_success');
      delete_transient('facebook_plugin_api_warning');
      delete_transient('facebook_plugin_api_info');
      delete_transient('facebook_plugin_api_sticky');

      $this->reset_all_products();

      WC_Facebookcommerce_Utils::log("Settings deleted");
      echo "Settings Deleted";

    }

    wp_die();
  }

  /**
   * Check Feed Upload Status
   **/
  function ajax_check_feed_upload_status() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions('check feed upload status', true);
    if ($this->settings['fb_api_key']) {
      $response = array(
        'connected'  => true,
        'status'  => 'in progress',
      );
      if ($this->settings['fb_upload_id']) {
        if (!isset($this->fbproductfeed)) {
          if (!class_exists('WC_Facebook_Product_Feed')) {
            include_once 'includes/fbproductfeed.php';
          }
          $this->fbproductfeed = new WC_Facebook_Product_Feed(
            $this->product_catalog_id, $this->fbgraph);
        }
        $status = $this->fbproductfeed->is_upload_complete($this->settings);

        $response['status'] = $status;
      } else {
        $response = array(
          'connected'  => true,
          'status'  => 'error',
        );
      }
      if ($response['status'] == 'complete') {
        update_option(
          $this->get_option_key(),
          apply_filters(
            'woocommerce_settings_api_sanitized_fields_' . $this->id,
              $this->settings));
      }
    } else {
      $response = array(
        'connected' => false,
      );
    }
    printf(json_encode($response));
    wp_die();
  }

  /**
   * Display custom success message (sugar)
   **/
  function display_success_message($msg) {
    $msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
    set_transient('facebook_plugin_api_success', $msg,
      self::FB_MESSAGE_DISPLAY_TIME);
  }

  /**
   * Display custom warning message (sugar)
   **/
  function display_warning_message($msg) {
    $msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
    set_transient('facebook_plugin_api_warning', $msg,
      self::FB_MESSAGE_DISPLAY_TIME);
  }

  /**
   * Display custom info message (sugar)
   **/
  function display_info_message($msg) {
    $msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
    set_transient('facebook_plugin_api_info', $msg,
      self::FB_MESSAGE_DISPLAY_TIME);
  }

  /**
   * Display custom "sticky" info message.
   * Call remove_sticky_message or wait for time out.
   **/
  function display_sticky_message($msg) {
    $msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
    set_transient('facebook_plugin_api_sticky', $msg,
      self::FB_MESSAGE_DISPLAY_TIME);
  }

  /**
   * Remove custom "sticky" info message
   **/
  function remove_sticky_message() {
    delete_transient('facebook_plugin_api_sticky');
  }

  function remove_resync_message() {
    $msg = get_transient('facebook_plugin_api_sticky');
    if ($msg && strpos($msg, 'Sync') !== false) {
      delete_transient('facebook_plugin_resync_sticky');
    }
  }

  /**
   * Display custom error message (sugar)
   **/
  function display_error_message($msg) {
    $msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
    WC_Facebookcommerce_Utils::log($msg);
    set_transient('facebook_plugin_api_error', $msg,
      self::FB_MESSAGE_DISPLAY_TIME);
  }

  /**
   *  Display error message from API result (sugar)
   **/
  function display_error_message_from_result($result) {
    $msg = json_decode($result['body'])->error->message;
    $this->display_error_message($msg);
  }

  /**
   * Deal with FB API responses, display error if FB API returns error
   *
   * @return result if response is 200, null otherwise
   **/
  function check_api_result($result, $logdata = null, $wpid = null) {
    if (is_wp_error($result)) {
      WC_Facebookcommerce_Utils::log($result->get_error_message());
      $this->display_error_message(
        "There was an issue connecting to the Facebook API: ".
          $result->get_error_message());
      return;
    }
    if ($result['response']['code'] != '200') {
      // Catch 10800 fb error code ("Duplicate retailer ID") and capture FBID
      // if possible, otherwise let user know we found dupe SKUs
      $body = WC_Facebookcommerce_Utils::decode_json($result['body']);
      if ($body && $body->error->code == '10800') {
        $error_data = $body->error->error_data; // error_data may contain FBIDs
        if ($error_data && $wpid) {
          $existing_id = $this->get_existing_fbid($error_data, $wpid);
          if ($existing_id) {
            // Add "existing_id" ID to result
            $body->id = $existing_id;
            $result['body'] = json_encode($body);
            return $result;
          }
        }
      } else {
        $this->display_error_message_from_result($result);
      }

      WC_Facebookcommerce_Utils::log($result);
      $data = array(
        'result' => $result,
        'data' => $logdata,
      );
      WC_Facebookcommerce_Utils::fblog(
        'Non-200 error code from FB',
        $data,
        true);
      return null;
    }
    return $result;
  }

  function ajax_woo_adv_bulk_edit_compat($import_id) {
    if (!WC_Facebookcommerce_Utils::check_woo_ajax_permissions('adv bulk edit', false)) {
      return;
    }
    $type = isset($_POST["type"]) ? $_POST["type"] : "";
    if (strpos($type, 'product') !== false && strpos($type, 'load') === false) {
      $this->display_out_of_sync_message("advanced bulk edit");
    }
  }

  function wp_all_import_compat($import_id) {
    $import = new PMXI_Import_Record();
    $import->getById($import_id);
    if (!$import->isEmpty() && in_array($import->options['custom_type'], array('product', 'product_variation'))) {
      $this->display_out_of_sync_message("import");
    }
  }

  function display_out_of_sync_message($action_name) {
    $this->display_sticky_message(
      sprintf(
        'Products may be out of Sync with Facebook due to your recent '.$action_name.'.'.
        ' <a href="%s&fb_force_resync=true&remove_sticky=true">Re-Sync them with FB.</a>',
        WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL));
  }

  /**
   * If we get a product group ID or product item ID back for a dupe retailer
   * id error, update existing ID.
   *
   * @return null
   **/
  function get_existing_fbid($error_data, $wpid) {
    if (isset($error_data->product_group_id)) {
      update_post_meta(
        $wpid,
        self::FB_PRODUCT_GROUP_ID,
        (string)$error_data->product_group_id);
      return $error_data->product_group_id;
    }
    else if (isset($error_data->product_item_id)) {
      update_post_meta(
        $wpid,
        self::FB_PRODUCT_ITEM_ID,
        (string)$error_data->product_item_id);
      return $error_data->product_item_id;
    } else {
      return;
    }
  }

  /**
   * Check for api key and any other API errors
   **/
  function checks() {
    // Check required fields

    if (!$this->api_key || !$this->product_catalog_id) {
      echo $this->get_message_html(sprintf(__('%1$sFacebook for WooCommerce
        is almost ready.%2$s To complete your configuration, %3$scomplete the
        setup steps%4$s.',
        'facebook-for-woocommerce'), '<strong>', '</strong>',

      '<a href="' . esc_url(WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL) . '">',
      '</a>'), 'info');
    }

    // WooCommerce 2.x upgrade nag
    if ($this->api_key && (!isset($this->background_processor))) {
      echo $this->get_message_html(sprintf(__(
        'Facebook product sync may not work correctly in WooCommerce version
        %1$s. Please upgrade to WooCommerce 3.',
        'facebook-for-woocommerce'), WC()->version), 'warning');
    }

    $this->maybe_display_facebook_api_messages();
  }

  function get_sample_product_feed() {
    ob_start();

    // Get up to 12 published posts that are products
    $args = array(
      'post_type'  => 'product',
      'post_status' => 'publish',
      'posts_per_page' => 12,
      'fields'         => 'ids'
    );

    $post_ids = get_posts($args);
    $items = array();

    foreach ($post_ids as $post_id) {

      $woo_product = new WC_Facebook_Product($post_id);
      $product_data = $woo_product->prepare_product();

      $feed_item = array(
        'title' => strip_tags($product_data['name']),
        'availability' => $woo_product->is_in_stock() ? 'in stock' :
          'out of stock',
        'description' => strip_tags($product_data['description']),
        'id' => $product_data['retailer_id'],
        'image_link' => $product_data['image_url'],
        'brand' => strip_tags(WC_Facebookcommerce_Utils::get_store_name()),
        'link' => $product_data['url'],
        'price' => $product_data['price'] . ' ' . get_woocommerce_currency(),
      );

      array_push($items, $feed_item);
    }
    // https://codex.wordpress.org/Function_Reference/wp_reset_postdata
    wp_reset_postdata();
    ob_end_clean();
    return json_encode(array($items));
  }

  /**
   * Loop through array of WPIDs to remove metadata.
   **/
  function delete_post_meta_loop($products) {
    foreach ($products as $product_id) {
      delete_post_meta($product_id, self::FB_PRODUCT_GROUP_ID);
      delete_post_meta($product_id, self::FB_PRODUCT_ITEM_ID);
      delete_post_meta($product_id, self::FB_VISIBILITY);
    }
  }

  /**
   * Remove FBIDs from all products when resetting store.
   **/
  function reset_all_products() {
    if (!is_admin()) {
      WC_Facebookcommerce_Utils::log("Not resetting any FBIDs from products,
        must call reset from admin context.");
      return false;
    }

    $test_instance = WC_Facebook_Integration_Test::get_instance($this);
    $this->test_mode = $test_instance::$test_mode;

    // Include draft products (omit 'post_status' => 'publish')
    WC_Facebookcommerce_Utils::log("Removing FBIDs from all products");

    $post_ids = get_posts(array(
      'post_type'  => 'product',
      'posts_per_page' => -1,
      'fields'         => 'ids'
    ));

    $children = array();
    foreach ($post_ids as $post_id) {
      $children = array_merge(get_posts(array(
        'post_type'     => 'product_variation',
        'posts_per_page' => -1,
        'post_parent'  =>  $post_id,
        'fields'       => 'ids'
      )), $children);
    }
    $post_ids = array_merge($post_ids, $children);
    $this->delete_post_meta_loop($post_ids);

    WC_Facebookcommerce_Utils::log("Product FBIDs deleted");
    return true;
  }

  /**
   * Remove FBIDs from a single WC product
   **/
  function reset_single_product($wp_id) {
    $woo_product = new WC_Facebook_Product($wp_id);
    $products = array($woo_product->get_id());
    if (WC_Facebookcommerce_Utils::is_variable_type($woo_product->get_type())) {
      $products = array_merge($products, $woo_product->get_children());
    }

    $this->delete_post_meta_loop($products);

    WC_Facebookcommerce_Utils::log("Deleted FB Metadata for product " . $wp_id);
  }

  function ajax_reset_all_fb_products() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions('reset products', true);
    $this->reset_all_products();
    wp_reset_postdata();
    wp_die();
  }

  function ajax_reset_single_fb_product() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions('reset single product', true);
    if (!isset($_POST['wp_id'])) {
      wp_die();
    }

    $wp_id = esc_js($_POST['wp_id']);
    $woo_product = new WC_Facebook_Product($wp_id);
    if ($woo_product) {
      $this->reset_single_product($wp_id);
    }

    wp_reset_postdata();
    wp_die();
  }

  function ajax_delete_fb_product() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions('delete single product', true);
    if (!isset($_POST['wp_id'])) {
      wp_die();
    }

    $wp_id = esc_js($_POST['wp_id']);
    $this->on_product_delete($wp_id);
    $this->reset_single_product($wp_id);
    wp_reset_postdata();
    wp_die();
  }

  /**
   * Special function to run all visible products through on_product_publish
   **/
  function ajax_sync_all_fb_products() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions('syncall products', true);
    if (get_option('fb_disable_sync_on_dev_environment', false)) {
      WC_Facebookcommerce_Utils::log(
        'Sync to FB Page is not allowed in Dev Environment');
      wp_die();
      return;
    }

    if (!$this->api_key || !$this->product_catalog_id) {
      WC_Facebookcommerce_Utils::log("No API key or catalog ID: " .
        $this->api_key . ' and ' . $this->product_catalog_id);
      wp_die();
      return;
    }
    $this->remove_resync_message();

    $currently_syncing = get_transient(self::FB_SYNC_IN_PROGRESS);

    if (isset($this->background_processor)) {
      if ($this->background_processor->is_updating()) {
        $this->background_processor->handle_cron_healthcheck();
        $currently_syncing = 1;
      }
    }

    if ($currently_syncing) {
      WC_Facebookcommerce_Utils::log('Not syncing, sync in progress');
      WC_Facebookcommerce_Utils::fblog(
        'Tried to sync during an in-progress sync!', array(), true);
      $this->display_warning_message('A product sync is in progress.
        Please wait until the sync finishes before starting a new one.');
      wp_die();
      return;
    }

    $is_valid_product_catalog =
      $this->fbgraph->validate_product_catalog($this->product_catalog_id);

    if (!$is_valid_product_catalog) {
      WC_Facebookcommerce_Utils::log('Not syncing, invalid product catalog!');
      WC_Facebookcommerce_Utils::fblog(
        'Tried to sync with an invalid product catalog!', array(), true);
      $this->display_warning_message('We\'ve detected that your
        Facebook Product Catalog is no longer valid. This may happen if it was
        deleted, or this may be a transient error.
        If this error persists please delete your settings via
        "Re-configure Facebook Settings > Advanced Settings > Delete Settings"
        and try setup again');
      wp_die();
      return;
    }

    // Cache the cart URL to display a warning in case it changes later
    $cart_url = get_option(self::FB_CART_URL);
    if ($cart_url != wc_get_cart_url()) {
      update_option(self::FB_CART_URL, wc_get_cart_url());
    }

    $sanitized_settings = $this->settings;
    unset($sanitized_settings['fb_api_key']);

    // Get all published posts. First unsynced then already-synced.
    $post_ids_new = WC_Facebookcommerce_Utils::get_wp_posts(
      self::FB_PRODUCT_GROUP_ID, 'NOT EXISTS');
    $post_ids_old = WC_Facebookcommerce_Utils::get_wp_posts(
      self::FB_PRODUCT_GROUP_ID, 'EXISTS');

    $total_new = count($post_ids_new);
    $total_old = count($post_ids_old);
    $post_ids = array_merge($post_ids_new, $post_ids_old);
    $total = count($post_ids);

    WC_Facebookcommerce_Utils::fblog(
      'Attempting to sync ' . $total . ' ( ' .
        $total_new . ' new) products with settings: ',
      $sanitized_settings,
      false);

    // Check for background processing (Woo 3.x.x)
    if (isset($this->background_processor)) {
      $starting_message = sprintf(
        'Starting background sync to Facebook: %d products...',
        $total);

      set_transient(
        self::FB_SYNC_IN_PROGRESS,
        true,
        self::FB_SYNC_TIMEOUT);

      set_transient(
        self::FB_SYNC_REMAINING,
        (int)$total);

      $this->display_info_message($starting_message);
      WC_Facebookcommerce_Utils::log($starting_message);

      foreach ($post_ids as $post_id) {
        WC_Facebookcommerce_Utils::log("Pushing post to queue: " . $post_id);
        $this->background_processor->push_to_queue($post_id);
      }

      $this->background_processor->save()->dispatch();
      // reset FB_SYNC_REMAINING to avoid race condition
      set_transient(
        self::FB_SYNC_REMAINING,
        (int)$total);
      // handle_cron_healthcheck must be called
      // https://github.com/A5hleyRich/wp-background-processing/issues/34
      $this->background_processor->handle_cron_healthcheck();
    } else {
      // Oldschool sync for WooCommerce 2.x
      $count = ($total_old === $total) ? 0 : $total_old;
      foreach ($post_ids as $post_id) {
        // Repeatedly overwrite sync total while in actual sync loop
        set_transient(
          self::FB_SYNC_IN_PROGRESS,
          true,
          self::FB_SYNC_TIMEOUT);

        $this->display_sticky_message(
          sprintf(
            'Syncing products to Facebook: %d out of %d...',
            // Display different # when resuming to avoid confusion.
            min($count, $total),
            $total),
          true);

        $this->on_product_publish($post_id);
        $count++;
      }
      WC_Facebookcommerce_Utils::log('Synced ' . $count . ' products');
      $this->remove_sticky_message();
      $this->display_info_message('Facebook product sync complete!');
      delete_transient(self::FB_SYNC_IN_PROGRESS);
      WC_Facebookcommerce_Utils::fblog(
        'Product sync complete. Total products synced: ' . $count);
    }

    // https://codex.wordpress.org/Function_Reference/wp_reset_postdata
    wp_reset_postdata();

    // This is important, for some reason.
    // See https://codex.wordpress.org/AJAX_in_Plugins
    wp_die();
  }

  /**
   * Special function to run all visible products by uploading feed.
   **/
  function ajax_sync_all_fb_products_using_feed() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions(
      'syncall products using feed', !$this->test_mode);
    return $this->sync_all_fb_products_using_feed();
  }

  // Separate entry point that bypasses permission check for use in cron.
  function sync_all_fb_products_using_feed() {
    if (get_option('fb_disable_sync_on_dev_environment', false)) {
      WC_Facebookcommerce_Utils::log(
        'Sync to FB Page is not allowed in Dev Environment');
      $this->fb_wp_die();
      return false;
    }

    if (!$this->api_key || !$this->product_catalog_id) {
      self::log("No API key or catalog ID: " . $this->api_key .
        ' and ' . $this->product_catalog_id);
      $this->fb_wp_die();
      return false;
    }
    $this->remove_resync_message();
    $is_valid_product_catalog =
      $this->fbgraph->validate_product_catalog($this->product_catalog_id);

    if (!$is_valid_product_catalog) {
      WC_Facebookcommerce_Utils::log('Not syncing, invalid product catalog!');
      WC_Facebookcommerce_Utils::fblog(
        'Tried to sync with an invalid product catalog!', array(), true);
      $this->display_warning_message('We\'ve detected that your
        Facebook Product Catalog is no longer valid. This may happen if it was
        deleted, or this may be a transient error.
        If this error persists please delete your settings via
        "Re-configure Facebook Settings > Advanced Settings > Delete Settings"
        and try setup again');
      $this->fb_wp_die();
      return false;
    }

    // Cache the cart URL to display a warning in case it changes later
    $cart_url = get_option(self::FB_CART_URL);
    if ($cart_url != wc_get_cart_url()) {
      update_option(self::FB_CART_URL, wc_get_cart_url());
    }

    if (!class_exists('WC_Facebook_Product_Feed')) {
      include_once 'includes/fbproductfeed.php';
    }
    if ($this->test_mode) {
      $this->fbproductfeed = new WC_Facebook_Product_Feed_Test_Mock(
        $this->product_catalog_id, $this->fbgraph, $this->feed_id);
    } else {
      $this->fbproductfeed = new WC_Facebook_Product_Feed(
        $this->product_catalog_id, $this->fbgraph, $this->feed_id);
    }

    $upload_success = $this->fbproductfeed->sync_all_products_using_feed();
    if ($upload_success) {
      $this->settings['fb_feed_id'] = $this->fbproductfeed->feed_id;
      $this->settings['fb_upload_id'] = $this->fbproductfeed->upload_id;
      update_option($this->get_option_key(),
        apply_filters('woocommerce_settings_api_sanitized_fields_' .
          $this->id, $this->settings));
      wp_reset_postdata();
      $this->fb_wp_die();
      return true;
    } else if (!$this->test_mode) {
      // curl failed, roll back to original sync approach.
      WC_Facebookcommerce_Utils::fblog(
        'Sync all products using feed, curl failed', array(), true);
      $this->sync_all_products();
    }
    return false;
  }

  /**
  * Toggles product visibility via AJAX (checks current viz and flips it)
  **/
  function ajax_toggle_visibility() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions('toggle visibility', true);
    if (!isset($_POST['wp_id']) || ! isset($_POST['published'])) {
      wp_die();
    }

    $wp_id = esc_js($_POST['wp_id']);
    $published = esc_js($_POST['published']) === 'true' ? true : false;

    $woo_product = new WC_Facebook_Product($wp_id);
    $products = WC_Facebookcommerce_Utils::get_product_array($woo_product);

    // Loop through product items and flip visibility
    foreach ($products as $item_id) {
      $fb_product_item_id = $this->get_product_fbid(
        self::FB_PRODUCT_ITEM_ID,
        $item_id);
      $data = array(
        'visibility' => $published ? 'published' : 'staging'
      );

      $result = $this->check_api_result(
          $this->fbgraph->update_product_item(
          $fb_product_item_id,
          $data));

      if ($result) {
        update_post_meta($item_id, self::FB_VISIBILITY, $published);
        update_post_meta($wp_id, self::FB_VISIBILITY, $published);
      }
    }
    wp_die();
  }

  /**
   * Initialize Settings Form Fields
   *
   * @access public
   * @return void
   */
  function init_form_fields() {
    $this->form_fields = array(
      'fb_settings_heading' => array(
        'title'       => __('Debug Mode', 'facebook-for-woocommerce'),
        'type'        => 'title',
        'description' => '',
        'default'     => ''
      ),
      'fb_page_id' => array(
        'title'       => __('Facebook Page ID', 'facebook-for-woocommerce'),
        'type'        => 'text',
        'description' => __('The unique identifier for your Facebook page.',
          'facebook-for-woocommerce'),
        'default'     => '',
        ),
      'fb_product_catalog_id' => array(
        'title'       => __('Product Catalog ID', 'facebook-for-woocommerce'),
        'type'        => 'text',
        'description' => __('The unique identifier for your product catalog,
          on Facebook.', 'facebook-for-woocommerce'),
        'default'     => ''
        ),
      'fb_pixel_id' => array(
        'title'       => __('Pixel ID', 'facebook-for-woocommerce'),
        'type'        => 'text',
        'description' => __('The unique identifier for your Facebook pixel',
          'facebook-for-woocommerce'),
        'default'     => ''
        ),
      'fb_pixel_use_pii' => array(
        'title'       => __('Use Advanced Matching on pixel?',
          'facebook-for-woocommerce'),
        'type'        => 'checkbox',
        'description' => __('Enabling Advanced Matching
          improves audience building.', 'facebook-for-woocommerce'),
        'default'     => 'yes'
        ),
      'fb_external_merchant_settings_id' => array(
        'title'       => __('External Merchant Settings ID',
          'facebook-for-woocommerce'),
        'type'        => 'text',
        'description' => __('The unique identifier for your external merchant
          settings, on Facebook.', 'facebook-for-woocommerce'),
        'default'     => ''
        ),
      'fb_api_key' => array(
        'title'       => __('API Key', 'facebook-for-woocommerce'),
        'type'        => 'text',
        'description' => sprintf(__('A non-expiring Page Token with
          %1$smanage_pages%2$s permissions.', 'facebook-for-woocommerce'),
        '<code>', '</code>'),
        'default'     => ''
        ),
    );

    if (!class_exists('WC_Facebookcommerce_EventsTracker')) {
      include_once 'includes/fbutils.php';
    }
  } // End init_form_fields()


  /**
   * Get message
   * @return string Error
   */
  private function get_message_html($message, $type = 'error') {
    ob_start();

    ?>
    <div class="notice is-dismissible notice-<?php echo $type ?>">
      <p><?php echo $message ?></p>
    </div>
    <?php
    return ob_get_clean();
  }

  /**
   * Display relevant messages to user from transients, clear once displayed
   *
   * @param void
   */
  public function maybe_display_facebook_api_messages() {
    $error_msg = get_transient('facebook_plugin_api_error');

    if ($error_msg) {
      echo $this->get_message_html(sprintf(__('Facebook extension error: %s ',
        'facebook-for-woocommerce'), $error_msg));
      delete_transient('facebook_plugin_api_error');

      WC_Facebookcommerce_Utils::fblog(
        $error_msg,
        array(),
        true);
    }

    $warning_msg = get_transient('facebook_plugin_api_warning');

    if ($warning_msg) {
      echo $this->get_message_html(__($warning_msg, 'facebook-for-woocommerce'),
       'warning');
      delete_transient('facebook_plugin_api_warning');
    }

    $success_msg = get_transient('facebook_plugin_api_success');

    if ($success_msg) {
      echo $this->get_message_html(__($success_msg, 'facebook-for-woocommerce'),
       'success');
      delete_transient('facebook_plugin_api_success');
    }

    $info_msg = get_transient('facebook_plugin_api_info');

    if ($info_msg) {
      echo $this->get_message_html(__($info_msg, 'facebook-for-woocommerce'),
        'info');
      delete_transient('facebook_plugin_api_info');
    }

    $sticky_msg = get_transient('facebook_plugin_api_sticky');

    if ($sticky_msg) {
      echo $this->get_message_html(__($sticky_msg, 'facebook-for-woocommerce'),
        'info');
      // Transient must be deleted elsewhere, or wait for timeout
    }

  }

  function get_page_name() {
    $page_name = '';
    if (!empty($this->settings['fb_page_id']) &&
      !empty($this->settings['fb_api_key']) ) {

      $page_name = $this->fbgraph->get_page_name($this->settings['fb_page_id'],
        $this->settings['fb_api_key']);
    }
    return $page_name;
  }

  function get_nux_message_ifexist() {
    $nux_type_to_elemid_map = array(
      'messenger_chat' => 'connect_button',
      'instagram_shopping' => 'connect_button'
    );
    $nux_type_to_message_map = array(
      'messenger_chat' => __('Get started with Messenger Customer Chat'),
      'instagram_shopping' => __('Get started with Instagram Shopping')
    );
    if (isset($_GET['nux'])) {
      return sprintf('<div class="nux-message" style="display: none;" data-target="%s">
          <div class="nux-message-text">%s</div>
          <div class="nux-message-arrow"></div>
          <i class="nux-message-close-btn">x</i>
        </div>
        <script>(function() { fbe_init_nux_messages(); })();</script>',
        $nux_type_to_elemid_map[$_GET['nux']],
        $nux_type_to_message_map[$_GET['nux']]);
    } else {
      return '';
    }
  }

  /**
   * Admin Panel Options
   */
  function admin_options() {
    $domain = 'facebook-for-woocommerce';
    $cta_button_text = __('Get Started', $domain);
    $page_name = $this->get_page_name();

    $can_manage = current_user_can('manage_woocommerce');
    $pre_setup = empty($this->settings['fb_page_id']) ||
      empty($this->settings['fb_api_key']);
    $apikey_invalid = !$pre_setup && $this->settings['fb_api_key'] && !$page_name;

    $redirect_uri = '';
    $remove_http_active = is_plugin_active('remove-http/remove-http.php');
    $https_will_be_stripped = $remove_http_active &&
      !get_option('factmaven_rhttp')['external'];
    if ($https_will_be_stripped) {
      $this->display_sticky_message(__('You\'re using Remove HTTP which has
        incompatibilities with our extension. Please disable it, or select the
        "Ignore external links" option on the Remove HTTP settings page.'));
    }

    if (!$pre_setup) {
      $cta_button_text = __('Create Ad', $domain);
      $redirect_uri = 'https://www.facebook.com/ads/dia/redirect/?settings_id='
        . $this->external_merchant_settings_id . '&version=2' .
        '&entry_point=admin_panel';
    }
    $currently_syncing = get_transient(self::FB_SYNC_IN_PROGRESS);
    $connected = ($page_name != '');
    $hide_test = ($connected && $currently_syncing) || !defined('WP_DEBUG') ||
      WP_DEBUG !== true;
    $nux_message = $this->get_nux_message_ifexist();
    ?>
    <h2><?php _e('Facebook', $domain); ?></h2>
    <p><?php _e('Control how WooCommerce integrates with your Facebook store.',
      $domain);?>
    </p>
    <hr/>

    <div id="fbsetup">
      <div class="wrapper">
        <header></header>
        <div class="content">
          <h1 id="setup_h1">
            <?php
              $pre_setup
                ? _e('Grow your business on Facebook', $domain)
                : _e('Reach The Right People and Sell More Online', $domain);
            ?>
          </h1>
          <h2>
            <?php _e('Use this WooCommerce and Facebook integration to:',
              $domain); ?>
          </h2>
          <ul>
            <li id="setup_l1">
              <?php
                $pre_setup
                  ? _e('Easily install a tracking pixel', $domain)
                  : _e('Create an ad in a few steps', $domain);
                ?>
            </li>
            <li id="setup_l2">
              <?php
                $pre_setup
                  ? _e('Upload your products and create a shop', $domain)
                  : _e('Use built-in best practices for online sales', $domain);
                ?>
            </li>
            <li id="setup_l3">
              <?php
                $pre_setup
                  ? _e('Create dynamic ads with your products and pixel', $domain)
                  : _e('Get reporting on sales and revenue', $domain);
                ?>
            </li>
          </ul>
          <span
          <?php
            if ($pre_setup) {
              if (!$can_manage) {
                echo ' style="pointer-events: none;"';
              }
              echo '><a href="#" class="btn pre-setup" onclick="facebookConfig()"
                id="cta_button">' . esc_html($cta_button_text) . '</a></span>';
            } else {
              if (!$can_manage || $apikey_invalid ||
                !isset($this->external_merchant_settings_id)) {
                echo ' style="pointer-events: none;"';
              }
              echo (
                '><a href='.$redirect_uri.' class="btn" id="cta_button">' .
                esc_html($cta_button_text) . '</a>' .
                '<a href="https://www.facebook.com/business/m/drive-more-online-sales"
                 class="btn grey" id="learnmore_button">' . __("Learn More") .
                '</a></span>'
              );
            }
          ?>
        <hr />
        <div class="settings-container">
          <div id="plugins" class="settings-section"
            <?php echo ($pre_setup && $can_manage) ? ' style="display:none;"' : ''; ?>
          >
            <h1><?php echo __('Add Ways for People to Shop'); ?></h1>
            <h2><?php echo __('Connect your business with features on Instagram, Messenger and more.'); ?></h2>
            <a href="#" class="btn small" onclick="facebookConfig()" id="connect_button">
            <?php echo __('Add Features'); ?>
            </a>
          </div>
          <div id="settings" class="settings-section"
          <?php
          if ($pre_setup && $can_manage) {
            echo ' style="display:none;"';
          }
          echo '><h1>' . esc_html__('Settings', $domain) . '</h1>';
          if ($apikey_invalid) {
            // API key is set, but no page name.
            echo '<h2 id="token_text" style="color:red;">' .
              __('Your API key is no longer valid. Please click "Settings >
              Advanced Options > Update Token".', $domain) . '</h2>

              <span><a href="#" class="btn small" onclick="facebookConfig()"
              id="setting_button">' . __('Settings', $domain) . '</a>
              </span>';
          } else {
            if (!$can_manage) {
              echo '<h2 style="color:red;">' . __('You must have
              "manage_woocommerce" permissions to use this plugin.', $domain) .
              '</h2>';
            } else {
              echo '<h2><span id="connection_status"';
              if (!$connected) {
                echo ' style="display: none;"';
              }
              echo '>';
              echo __('Your WooCommerce store is connected to ', $domain) .
                (($page_name != '')
                 ? sprintf(
                     __('the Facebook page <a target="_blank" href="https://www.facebook.com/%1$s">%2$s</a></span>', $domain),
                     $this->settings['fb_page_id'],
                     esc_html($page_name))
                 : sprintf(
                     __('<a target="_blank" href="https://www.facebook.com/%1$s">your Facebook page</a></span>', $domain),
                     $this->settings['fb_page_id'])
                   ) .
                '.<span id="sync_complete" style="margin-left: 5px;';
              if (!$connected || $currently_syncing) {
                echo ' display: none;';
              }
              echo '">' . __('Status', $domain) . ': '
                . __('Products are synced to Facebook.', $domain) . '</span>'.
                sprintf(__('<span><a href="#" onclick="show_debug_info()"
                id="debug_info" style="display:none;" > More Info </a></span>',
                $domain)) . '</span></h2>
                <span><a href="#" class="btn small" onclick="facebookConfig()"
                  id="setting_button"';

              if ($currently_syncing) {
                echo ' style="pointer-events: none;" ';
              }
              echo '>' . __('Manage Settings', $domain) . '</a></span>

              <span><a href="#" class="btn small" onclick="sync_confirm()"
                id="resync_products"';

              if ($connected && $currently_syncing) {
                echo ' style="pointer-events: none;" ';
              }
              echo '>' . __('Sync Products', $domain) . '</a></span>

              <p id="sync_progress">';
              if ($connected && $currently_syncing) {
                echo '<hr/>';
                echo __('Syncing... Keep this browser open', $domain);
                echo '<br/>';
                echo __('Until sync is complete', $domain);
              }
              echo '</p>';
            }
          } ?>
          </div>
          <hr />
        </div>
        <?php echo $nux_message; ?>

        <div>
          <div id='fbAdvancedOptionsText' onclick="toggleAdvancedOptions();">
            Show Advanced Settings
          </div>
          <div id='fbAdvancedOptions'>
              <div class='autosync' title="This experimental feature will call force resync at the specified time using wordpress cron scheduling.">
                <input type="checkbox"
                  onclick="saveAutoSyncSchedule()"
                  class="autosyncCheck"
                  <?php echo get_option('woocommerce_fb_autosync_time', false) ? 'checked' : 'unchecked'; ?>>
                Automatically Force Resync of Products At

                <input
                  type="time"
                  value="<?php echo get_option('woocommerce_fb_autosync_time', '23:00'); ?>"
                  class="autosyncTime"
                  onfocusout="saveAutoSyncSchedule()"
                  <?php echo get_option('woocommerce_fb_autosync_time', 0) ? '' : 'disabled'; ?> />
                Every Day.
                <span class="autosyncSavedNotice" disabled> Saved </span>
              </div>
              <div title="This option is meant for development and testing environments.">
                <input type="checkbox"
                  onclick="onSetDisableSyncOnDevEnvironment()"
                  class="disableOnDevEnvironment"
                  <?php echo get_option('fb_disable_sync_on_dev_environment', false)
                    ? 'checked'
                    : 'unchecked'; ?> />
                Disable Product Sync with FB
              </div>
              <div class='shortdescr' title="This experimental feature will import short description instead of description for all products.">
                <input type="checkbox"
                  onclick="syncShortDescription()"
                  class="syncShortDescription"
                  <?php echo get_option('fb_sync_short_description', false)
                    ? 'checked'
                    : 'unchecked'; ?> />
                Sync Short Description Instead of Description
              </div>
          </div>
        </div>
      </div>
    </div>
    <div <?php echo ($hide_test) ? ' style="display:none;" ' : ''; ?> >
      <p class="tooltip" id="test_product_sync">
      <?php
        // WP_DEBUG mode: button to launch test
        echo sprintf(__('<a href="%s&fb_test_product_sync=true"', $domain),
          WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL);
        echo '>' . esc_html__('Launch Test', $domain);
      ?>
      <span class='tooltiptext'>
        <?php
          _e('This button will run an integration test suite verifying the
          extension. Note that this will reset your products and resync them
          to Facebook. Not recommended to use unless you are changing the
          extension code and want to test your changes.', $domain);
        ?>
      </span>
      <?php
         echo '</a>';
      ?>
      </p>
      <p id="stack_trace"></p>
    </div>
    <br/><hr/><br/>
    <?php

      $GLOBALS['hide_save_button'] = true;
      if (defined('WP_DEBUG') && true === WP_DEBUG) {
        $GLOBALS['hide_save_button'] = false;
    ?>
      <table class="form-table">
        <?php $this->generate_settings_html(); ?>
     </table><!--/.form-table-->
     <?php
    }
  }

  function delete_product_item($wp_id) {
    $fb_product_item_id = $this->get_product_fbid(
        self::FB_PRODUCT_ITEM_ID,
        $wp_id);
    if ($fb_product_item_id) {
      $pi_result =
        $this->fbgraph->delete_product_item($fb_product_item_id);
      WC_Facebookcommerce_Utils::log($pi_result);
    }
  }

  function fb_duplicate_product_reset_meta($to_delete) {
    array_push($to_delete, self::FB_PRODUCT_ITEM_ID);
    array_push($to_delete, self::FB_PRODUCT_GROUP_ID);
    return $to_delete;
  }

  /**
   * Helper function to update FB visibility.
   */
  function update_fb_visibility($wp_id, $visibility) {
    $woo_product = new WC_Facebook_Product($wp_id);
    if (!$woo_product->exists()) {
      // This function can be called for non-woo products.
      return;
    }

    $products = WC_Facebookcommerce_Utils::get_product_array($woo_product);
    foreach ($products as $item_id) {
      $fb_product_item_id = $this->get_product_fbid(
          self::FB_PRODUCT_ITEM_ID,
          $item_id);

      if (!$fb_product_item_id) {
        WC_Facebookcommerce_Utils::fblog(
          $fb_product_item_id." doesn't exist but underwent a visibility transform.",
          array(),
          true);
        continue;
      }
      $result = $this->check_api_result(
        $this->fbgraph->update_product_item(
        $fb_product_item_id,
        array('visibility' => $visibility)));
      if ($result) {
        update_post_meta($item_id, self::FB_VISIBILITY, $visibility);
        update_post_meta($wp_id, self::FB_VISIBILITY, $visibility);
      }
    }
  }

  function on_quick_and_bulk_edit_save($product) {
    $wp_id = $product->get_id();
    $visibility = get_post_status($wp_id) == 'publish'
    ? 'published'
    : 'staging';
    // case 1: new status is 'publish' regardless of old status, sync to FB
    if ($visibility == 'published') {
      $this->on_product_publish($wp_id);
    } else {
      // case 2: product never publish to FB, new status is not publish
      // case 3: product new status is not publish and published before
      $this->update_fb_visibility($wp_id, $visibility);
    }
  }

  private function get_product_fbid($fbid_type, $wp_id, $woo_product = null) {
    $fb_id = WC_Facebookcommerce_Utils::get_fbid_post_meta(
      $wp_id,
      $fbid_type);
    if ($fb_id) {
      return $fb_id;
    }
    if (!isset($this->settings['upload_end_time'])) {
      return null;
    }
    if (!$woo_product) {
      $woo_product = new WC_Facebook_Product($wp_id);
    }
    $products = WC_Facebookcommerce_Utils::get_product_array($woo_product);
    $woo_product = new WC_Facebook_Product(current($products));
    // This is a generalized function used elsewhere
    // Cannot call is_hidden for VC_Product_Variable Object
    if ($woo_product->is_hidden()) {
      return null;
    }
    $fb_retailer_id =
      WC_Facebookcommerce_Utils::get_fb_retailer_id($woo_product);

    $product_fbid_result = $this->fbgraph->get_facebook_id(
      $this->product_catalog_id,
      $fb_retailer_id);
    if (is_wp_error($product_fbid_result)) {
      WC_Facebookcommerce_Utils::log($product_fbid_result->get_error_message());
      $this->display_error_message(
        "There was an issue connecting to the Facebook API: ".
          $product_fbid_result->get_error_message());
      return;
    }

    if ($product_fbid_result && isset($product_fbid_result['body'])) {
      $body = WC_Facebookcommerce_Utils::decode_json($product_fbid_result['body']);
      if ($body && $body->id) {
        if ($fbid_type == self::FB_PRODUCT_GROUP_ID) {
          $fb_id = $body->product_group->id;
          } else {
           $fb_id = $body->id;
          }
          update_post_meta(
            $wp_id,
            $fbid_type,
            $fb_id);
          update_post_meta($wp_id, self::FB_VISIBILITY, true);
          return $fb_id;
      }
    }
    return;
  }

  private function set_default_variant($product_group_id, $product_item_id) {
    $result = $this->check_api_result(
      $this->fbgraph->set_default_variant(
      $product_group_id,
      array('default_product_id' => $product_item_id)));
    if (!$result) {
      WC_Facebookcommerce_Utils::fblog(
        'Fail to set default product item',
        array(),
        true);
    }
  }

  private function fb_wp_die() {
    if (!$this->test_mode) {
      wp_die();
    }
  }

  /**
   * Display test result.
   **/
  function ajax_display_test_result() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions('test result', true);
    $response = array(
      'pass'  => 'true',
    );
    $test_pass = get_option('fb_test_pass', null);
    if (!isset($test_pass)) {
      $response['pass'] = 'in progress';
    } else if ($test_pass == 0) {
      $response['pass'] = 'false';
      $response['debug_info'] = get_transient('facebook_plugin_test_fail');
      $response['stack_trace'] =
      get_transient('facebook_plugin_test_stack_trace');
      $response['stack_trace'] =
        preg_replace("/\n/", '<br>', $response['stack_trace']);
      delete_transient('facebook_plugin_test_fail');
      delete_transient('facebook_plugin_test_stack_trace');
    }
    delete_option('fb_test_pass');
    printf(json_encode($response));
    wp_die();
  }

  /**
   * Schedule Force Resync
   */
  function ajax_schedule_force_resync() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions('resync schedule', true);
    if (isset($_POST) && isset($_POST['enabled'])) {
      if (isset($_POST['time']) && $_POST['enabled']) { // Enabled
        wp_clear_scheduled_hook('sync_all_fb_products_using_feed');
        wp_schedule_event(
          strtotime($_POST['time']),
          'daily',
          'sync_all_fb_products_using_feed');
        WC_Facebookcommerce_Utils::fblog('Scheduled autosync for '.$_POST['time'], $_POST);
        update_option('woocommerce_fb_autosync_time', $_POST['time']);
      } else if (!$_POST['enabled']) { // Disabled
        wp_clear_scheduled_hook('sync_all_fb_products_using_feed');
        WC_Facebookcommerce_Utils::fblog('Autosync disabled', $_POST);
        delete_option('woocommerce_fb_autosync_time');
      }
    } else {
      WC_Facebookcommerce_Utils::fblog('Autosync AJAX Problem', $_POST, true);
    }
    wp_die();
  }

  function ajax_update_fb_option() {
    WC_Facebookcommerce_Utils::check_woo_ajax_permissions('update fb options', true);
    if (isset($_POST) && stripos($_POST['option'], 'fb_') === 0) {
      update_option($_POST['option'], $_POST['option_value']);
    }
    wp_die();
  }
}
