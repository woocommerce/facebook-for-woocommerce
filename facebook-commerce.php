<?php
/**
 * @package FacebookCommerce
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

include_once('facebook-config-warmer.php');
include_once('includes/fbproduct.php');

class WC_Facebookcommerce_Integration extends WC_Integration {


  const FB_PRODUCT_GROUP_ID  = 'fb_product_group_id';
  const FB_PRODUCT_ITEM_ID = 'fb_product_item_id';
  const FB_PRODUCT_DESCRIPTION = 'fb_product_description';

  const FB_VISIBILITY = 'fb_visibility';

  const FB_CART_URL = 'fb_cart_url';

  const FB_MESSAGE_DISPLAY_TIME = 180;

  const FB_VARIANT_SIZE = 'size';
  const FB_VARIANT_COLOR = 'color';
  const FB_VARIANT_COLOUR = 'colour';
  const FB_VARIANT_PATTERN = 'pattern';
  const FB_VARIANT_GENDER = 'gender';
  public static $validGenderArray =
    array("male" => 1, "female" => 1, "unisex" => 1);

  const FB_ADMIN_MESSAGE_PREPEND = '<b>Facebook for WooCommerce</b><br/>';

  const FB_SYNC_IN_PROGRESS = 'fb_sync_in_progress';
  const FB_SYNC_REMAINING = 'fb_sync_remaining';
  const FB_SYNC_TIMEOUT = 30;
  const FB_PRIORITY_MID = 9;

  public function init_settings() {
    parent::init_settings();

    // side-load pixel config if not present in WordPress config
    if (!isset($this->settings['fb_pixel_id']) && class_exists('WC_Facebookcommerce_WarmConfig')) {
      $fb_warm_pixel_id = WC_Facebookcommerce_WarmConfig::$fb_warm_pixel_id;

      if (isset($fb_warm_pixel_id) && is_numeric($fb_warm_pixel_id) && (int)$fb_warm_pixel_id == $fb_warm_pixel_id) {
        $this->settings['fb_pixel_id'] = (string)$fb_warm_pixel_id;
        update_option($this->get_option_key(), apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings));
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

    $this->pixel_id = isset($this->settings['fb_pixel_id'])
     ? $this->settings['fb_pixel_id']
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

    $this->init_form_fields();

    if (!class_exists('WC_Facebookcommerce_Utils')) {
      include_once 'includes/fbutils.php';
    }

    if (!class_exists('WC_Facebookcommerce_Graph_API')) {
      include_once 'includes/fbgraph.php';
      $this->fbgraph = new WC_Facebookcommerce_Graph_API($this->api_key);
    }

    // Hooks
    if (is_admin()) {
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

      add_action('wp_ajax_ajax_reset_all_fb_products',
        array($this, 'ajax_reset_all_fb_products'), self::FB_PRIORITY_MID);

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
          'before_delete_post',
          array($this, 'on_product_delete'),
          10,
          1);

        add_action('add_meta_boxes', array($this, 'fb_product_metabox'), 10, 1);

        add_filter('manage_product_posts_columns',
          array($this, 'fb_product_columns'));
        add_action('manage_product_posts_custom_column',
          array( $this, 'fb_render_product_columns' ), 2);


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

      }
    }

    if (!class_exists('WC_Facebookcommerce_EventsTracker')) {
      include_once 'facebook-commerce-events-tracker.php';
    }

    if ($this->pixel_id) {
      $user_info = WC_Facebookcommerce_Utils::get_user_info($this->use_pii);
      $this->events_tracker = new WC_Facebookcommerce_EventsTracker(
        $this->pixel_id, $user_info);

      // Pixel Tracking Hooks
      add_action('wp_head',
        array($this->events_tracker, 'inject_base_pixel'));
      add_action('wp_footer',
        array($this->events_tracker, 'inject_base_pixel_noscript'));
      add_action('woocommerce_after_single_product',
        array($this->events_tracker, 'inject_view_content_event'));
      add_action('woocommerce_after_shop_loop',
        array($this->events_tracker, 'inject_view_category_event'));
      add_action('pre_get_posts',
        array($this->events_tracker, 'inject_search_event'));
      add_action('woocommerce_after_cart',
        array($this->events_tracker, 'inject_add_to_cart_event'));
      add_action('woocommerce_after_checkout_form',
        array($this->events_tracker, 'inject_initiate_checkout_event'));
      add_action('woocommerce_thankyou',
        array($this->events_tracker, 'inject_purchase_event'));
    }

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

  public function fb_new_product_tab_content() {
    global $post;
    $woo_product = new WC_Facebook_Product($post->ID);
    $description = get_post_meta(
      $post->ID,
      self::FB_PRODUCT_DESCRIPTION,
      true);

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
      ?></div>
    </div><?php
  }

  public function fb_product_columns($existing_columns) {
    if (empty($existing_columns) && ! is_array($existing_columns)) {
      $existing_columns = array();
    }

    $columns = array();
    $columns['fb'] = __('Facebook Shop', 'facebook-for-woocommerce');

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
      $fb_product_group_id = get_post_meta(
        $post->ID,
        self::FB_PRODUCT_GROUP_ID,
        true);
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
            href="#" onclick="fb_toggle_visibility(%1$s, true)">Show</a>',
            $post->ID);
        } else {
          printf(
            '<a id="viz_%1$s" class="button" href="#"
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
    $fb_product_group_id = get_post_meta(
      $post->ID,
      self::FB_PRODUCT_GROUP_ID,
      true);
    printf('<span id="fb_metadata">');
    if ($fb_product_group_id) {
      printf('Facebook ID: <a href="https://facebook.com/'.
          $fb_product_group_id . '" target="_blank">' .
          $fb_product_group_id . '</a><p/>');

      $woo_product = new WC_Facebook_Product($post->ID);
      if ($woo_product->get_type() === 'variable') {
        printf('<p>Variant IDs:<br/>');
        $children = $woo_product->get_children();
        foreach ($children as $child_id) {
          $fb_product_item_id = get_post_meta(
            $child_id,
            self::FB_PRODUCT_ITEM_ID,
            true);
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

  // Return store name with sanitized apostrophe
  private function get_store_name() {
    $name = trim(str_replace(
      "'",
      "\u{2019}",
      html_entity_decode(
        get_bloginfo('name'),
        ENT_QUOTES,
        'UTF-8')));
    if ($name) {
      return $name;
    }
    // Fallback to site url
    $url = get_site_url();
    if ($url) {
      return parse_url($url, PHP_URL_HOST);
    }
    // If site url doesn't exist, fall back to http host.
    if ($_SERVER['HTTP_HOST']) {
      return $_SERVER['HTTP_HOST'];
    }

    // If http host doesn't exist, fall back to local host name.
    $url = gethostname();
    return ($url) ? $url : 'A Store Has No Name';
  }

  /**
   * Load DIA specific JS Data
   */
  public function load_assets() {
    $screen = get_current_screen();

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
        ,storeName: '<?php echo esc_js($this->get_store_name()); ?>'
        ,version: '<?php echo WC()->version ?>'
        ,php_version: '<?php echo PHP_VERSION ?>'
        ,plugin_version:
          '<?php echo WC_Facebookcommerce_Utils::PLUGIN_VERSION ?>'
      }
      ,feed: {
        totalVisibleProducts: '<?php echo $this->get_product_count() ?>'
      }
      ,feedPrepared: {
        feedUrl: ''
        ,feedPingUrl: ''
        ,samples: <?php echo $this->get_sample_product_feed()?>
      }
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
    $fb_product_group_id = get_post_meta(
      $wp_id,
      self::FB_PRODUCT_GROUP_ID,
      true);

    $fb_product_item_id = get_post_meta(
      $wp_id,
      self::FB_PRODUCT_ITEM_ID,
      true);

    if (! ($fb_product_group_id || $fb_product_item_id ) ) {
      return;  // No synced product, no-op.
    }

    $products = array($wp_id);
    if ($woo_product->get_type() == 'variable') {
      $children = $woo_product->get_children();
      $products = array_merge($products, $children);
    }

    foreach ($products as $item_id) {
      $this->delete_product_item ($item_id);
    }

    if ($fb_product_group_id) {
      $pg_result = $this->fbgraph->delete_product_group($fb_product_group_id);
        self::log($pg_result);
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
    if ($product_type === 'variable') {
      $this->on_variable_product_publish($wp_id, $woo_product);
    } else {
      $this->on_simple_product_publish($wp_id, $woo_product);
    }
  }

  function on_variable_product_publish($wp_id, $woo_product = null) {
    if (get_post_status($wp_id) != 'publish') {
      return;
    }
    // Check if product group has been published to FB.  If not, it's new.
    // If yes, loop through variants and see if product items are published.
    if (!$woo_product) {
      $woo_product = new WC_Facebook_Product($wp_id);
    }

    if (isset($_POST[self::FB_PRODUCT_DESCRIPTION])) {
      $woo_product->set_description($_POST[self::FB_PRODUCT_DESCRIPTION]);
    }

    $fb_product_group_id = get_post_meta(
      $wp_id,
      self::FB_PRODUCT_GROUP_ID,
      true);

    if ($fb_product_group_id) {
      $woo_product->update_visibility(
        isset($_POST['is_product_page']),
        isset($_POST[self::FB_VISIBILITY]));
      $this->update_product_group($woo_product);
      $child_products = $woo_product->get_children();
      $variation_id = $woo_product->find_matching_product_variation();
      $gallery_urls = $woo_product->get_image_urls();
      // check if item_id is default variation. If yes, update in the end.
      // If default variation value is to update, delete old fb_product_item_id
      // and create new one in order to make it order correctly.
      foreach ($child_products as $item_id) {
        if ($item_id !== $variation_id) {
          $this->on_simple_product_publish($item_id, null, $gallery_urls);
        } else {
          $this->delete_product_item($item_id);
        }
      }
      if ($variation_id) {
        $this->on_simple_product_publish(
          $variation_id,
          null,
          $gallery_urls,
          true);
      }
    } else {
      $this->create_product_variable($woo_product);
    }
  }

  function on_simple_product_publish(
    $wp_id,
    $woo_product = null,
    &$gallery_urls = null,
    $reset = false) {
    if (get_post_status($wp_id) != 'publish') {
      return;
    }

    if (!$woo_product) {
      $woo_product = new WC_Facebook_Product($wp_id, $gallery_urls);
    }

    if (isset($_POST[self::FB_PRODUCT_DESCRIPTION])) {
      $woo_product->set_description($_POST[self::FB_PRODUCT_DESCRIPTION]);
    }

    // Check if this product has already been published to FB.
    // If not, it's new!
    $fb_product_item_id = get_post_meta($wp_id, self::FB_PRODUCT_ITEM_ID, true);
    if ($fb_product_item_id && !$reset) {
      $woo_product->update_visibility(
        isset($_POST['is_product_page']),
        isset($_POST[self::FB_VISIBILITY]));
      $this->update_product_item($woo_product, $fb_product_item_id);
    } else {
      // Check if this is a new product item for an existing product group
      if ($woo_product->get_parent_id()) {
        $fb_product_group_id = get_post_meta(
          $woo_product->get_parent_id(),
          self::FB_PRODUCT_GROUP_ID,
          true);

        // New variant added
        if ($fb_product_group_id) {
          $this->create_product_simple($woo_product, $fb_product_group_id);
        } else {
          $this->fblog(
            "Wrong! simple_product_publish called without group ID for
              a variable product!");
        }
      } else {
        $this->create_product_simple($woo_product);  // new product
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
      $gallery_urls = $woo_product->get_image_urls();
      foreach ($child_products as $item_id) {
        if ($item_id !== $variation_id) {
          $this->create_product_item_using_itemid(
            $item_id,
            $fb_product_group_id,
            $gallery_urls);
        }
      }
      // In FB shop, when clicking on a variable product, it will redirect to
      // last child of this product group.
      if ($variation_id) {
        $this->create_product_item_using_itemid(
          $variation_id,
          $fb_product_group_id,
          $gallery_urls);
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
      $this->create_product_item(
        $woo_product,
        $retailer_id,
        $fb_product_group_id);
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
        $this->prepare_variants_for_group($woo_product);
    }

    $create_product_group_result = $this->check_api_result(
        $this->fbgraph->create_product_group(
            $this->product_catalog_id,
            $product_group_data),
          $product_group_data,
          $woo_product->get_id());

     // New variant added
    if ($create_product_group_result) {
      $fb_product_group_id = json_decode(
        $create_product_group_result['body'])->id;

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
    update_post_meta($woo_product->get_id(), self::FB_VISIBILITY, true);

    $product_data = $this->prepare_product($woo_product, $retailer_id);

    $product_result = $this->check_api_result(
        $this->fbgraph->create_product_item(
          $product_group_id,
          $product_data),
        $product_data,
        $woo_product->get_id());

    if ($product_result) {
      $fb_product_item_id = json_decode($product_result['body'])->id;

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
    $fb_product_group_id = get_post_meta(
      $woo_product->get_id(),
      self::FB_PRODUCT_GROUP_ID,
      true);

    if (!$fb_product_group_id) {
      return;
    }

    $variants = $this->prepare_variants_for_group($woo_product);

    if (!$variants) {
      self::log(
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
    $product_data = $this->prepare_product($woo_product);

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
   * Assemble product payload for POST
   **/
  function prepare_product($woo_product, $retailer_id = null) {
    if (!$retailer_id) {
      $retailer_id =
        WC_Facebookcommerce_Utils::get_fb_retailer_id($woo_product);
    }
    $image_urls = $woo_product->get_all_image_urls();

    // Replace Wordpress sanitization's ampersand with a real ampersand.
    $product_url = str_replace(
      '&amp%3B',
      '&',
      html_entity_decode($woo_product->get_permalink()));

    if (wc_get_cart_url()) {
      $char = '?';
      // Some merchant cart pages are actually a querystring
      if (strpos(wc_get_cart_url(), '?') !== false) {
        $char = '&';
      }

      $checkout_url = WC_Facebookcommerce_Utils::make_url(
        wc_get_cart_url() . $char);

      if ($woo_product->get_type() === 'variation') {
        $query_data = array(
          'add-to-cart' => $woo_product->get_parent_id(),
          'variation_id' => $woo_product->get_id()
        );

        $query_data = array_merge(
          $query_data,
          $woo_product->get_variation_attributes());

      } else {
        $query_data = array(
          'add-to-cart' => $woo_product->get_id()
        );
      }

      $checkout_url = $checkout_url . http_build_query($query_data);

    } else {
      $checkout_url = null;
    }

    // Get regular price: regular price doesn't include sales
    $regular_price = floatval($woo_product->get_regular_price());

    // If it's a bookable product, the normal price is null/0.
    if (!$regular_price) {
      $regular_price = $woo_product->get_bookable_price();
    }

    // Get regular price plus tax, if it's set to display and taxable
    $price = $woo_product->get_price_plus_tax($regular_price);

    $id = $woo_product->get_id();
    if ($woo_product->get_type() === 'variation') {
      $id = $woo_product->get_parent_id();
    }
    $categories =
      WC_Facebookcommerce_Utils::get_product_categories($id);

    // whether price including tax is based on 'woocommerce_tax_display_shop'
    $price = intval(round($price * 100));

    $product_data = array(
      'name' => $woo_product->get_title(),
      'description' => $woo_product->get_fb_description(),
      'image_url' => $image_urls[0], // The array can't be empty.
      'additional_image_urls' => array_filter($image_urls),
      'url'=> $product_url,
      'category' => $categories['categories'],
      'brand' => $this->get_store_name(),
      'retailer_id' => $retailer_id,
      'price' => $price,
      'currency' => get_woocommerce_currency(),
      'availability' => $woo_product->is_in_stock() ? 'in stock' :
        'out of stock',
      'visibility' => !$woo_product->is_hidden()
        ? 'published'
        : 'staging'
    );

    // Only use checkout URLs if they exist.
    if ($checkout_url) {
      $product_data['checkout_url'] = $checkout_url;
    }

    $product_data = $woo_product->add_sale_price($product_data);

    // Loop through variants (size, color, etc) if they exist
    // For each product field type, pull the single variant
    $variants = $this->prepare_variants_for_item($woo_product, $product_data);
    if ($variants) {
      foreach ($variants as $variant) {

        // Replace "custom_data:foo" with just "foo" so we can use the key
        // Product item API expects "custom_data" instead of "custom_data:foo"
        $product_field = str_replace(
          'custom_data:',
          '',
          $variant['product_field']);
        if ($product_field === self::FB_VARIANT_GENDER) {
          // If we can't validate the gender, this will be null.
          $product_data[$product_field] =
            $this->validateGender($variant['options'][0]);
        }

        switch ($product_field) {
          case self::FB_VARIANT_SIZE:
          case self::FB_VARIANT_COLOR:
          case self::FB_VARIANT_PATTERN:
            $product_data[$product_field] = $variant['options'][0];
            break;
          case self::FB_VARIANT_GENDER:
            // If we can't validate the GENDER field, we'll fall through to the
            // default case and set the gender into custom data.
            if ($product_data[$product_field]) {
              break;
            }
          default:
            // This is for any custom_data.
            if (!isset($product_data['custom_data'])) {
              $product_data['custom_data'] = array(
                $product_field => urldecode($variant['options'][0]),
              );
            } else {
              $product_data['custom_data'][$product_field]
                = urldecode($variant['options'][0]);
            }

            break;
        }
      }
    }

    return $product_data;
  }

  /**
   * Modify Woo variant/taxonomies for variable products to be FB compatible
   **/
  function prepare_variants_for_group($woo_product) {
    if ($woo_product->get_type() !== 'variable') {
      $this->fblog("prepare_variants_for_group called on non-variable product");
      return;
    }

    $variation_attributes = $woo_product->get_variation_attributes();
    if (!$variation_attributes) {
      return;
    }
    $final_variants = array();

    $attrs = array_keys($woo_product->get_attributes());
    foreach ($attrs as $name) {
      $label = wc_attribute_label($name, $woo_product);

      if (taxonomy_is_product_attribute($name)) {
        $key = $name;
      }else {
        // variation_attributes keys are labels for custom attrs for some reason
        $key = $label;
      }

      if (!$key) {
        $this->fblog("Critical error: can't get attribute name or label!");
        return;
      }

      if (isset($variation_attributes[$key])) {
         // Array of the options (e.g. small, medium, large)
        $option_values = $variation_attributes[$key];
      } else {
        self::log($woo_product->get_id() . ": No options for " . $name);
        continue; // Skip variations without valid attribute options
      }

      // If this is a wc_product_variable, check default attrib.
      // If it's being used, show it as the first option on Facebook.
      $first_option = $woo_product->get_variation_default_attribute($key);
      if ($first_option) {
        $idx = array_search($first_option, $option_values);
        unset($option_values[$idx]);
        array_unshift($option_values, $first_option);
      }

      if (
        function_exists('taxonomy_is_product_attribute') &&
        taxonomy_is_product_attribute($name)
      ) {
        $option_values = $woo_product->get_grouped_product_option_names(
          $key,
          $option_values);
      }

      // Clean up variant name (e.g. pa_color should be color)
      $name = $this->sanitize_variant_name($name);

      array_push($final_variants, array(
        'product_field' => $name,
        'label' => $label,
        'options' => $option_values,
      ));
    }

    return $final_variants;

  }

  /**
   * Modify Woo variant/taxonomies to be FB compatible
   **/
  function prepare_variants_for_item($woo_product, &$product_data) {
    if ($woo_product->get_type() !== 'variation') {
      return;
    }

    $attributes = $woo_product->get_variation_attributes();
    if (!$attributes) {
      return;
    }

    $variant_names = array_keys($attributes);
    $variant_array = array();

    foreach ($variant_names as $orig_name) {
      // Retrieve label name for attribute
      $label = wc_attribute_label($orig_name, $woo_product);

      // Clean up variant name (e.g. pa_color should be color)
      $new_name = $this->sanitize_variant_name($orig_name);

      // Sometimes WC returns an array, sometimes it's an assoc array, depending
      // on what type of taxonomy it's using.  array_values will guarantee we
      // only get a flat array of values.
      $options = $woo_product->get_variant_option_name(
        $label,
        $attributes[$orig_name]);
      if (isset($options)) {
        if (is_array($options)) {
          $option_values = array_values($options);
        } else {
          $option_values = array($options);
          // If this attribute has value 'any', options will be empty strings
          // Redirect to product page to select variants.
          // Reset checkout url since checkout_url (build from query data will
          // be invalid in this case.
          if (count($option_values) === 1 && empty($option_values[0])) {
            $option_values[0] =
              'any '.str_replace('custom_data:', '', $new_name);
            $product_data['checkout_url'] = $product_data['url'];
          }
        }
      } else {
        self::log($woo_product->get_id() . ": No options for " . $orig_name);
        continue;
      }

      array_push($variant_array, array(
        'product_field' => $new_name,
        'label' => $label,
        'options' => $option_values,
      ));
    }

    return $variant_array;
  }

  /*
  * Change variant product field name from Woo taxonomy to FB name
  */
  function sanitize_variant_name($name) {
    $name = str_replace(array('attribute_', 'pa_'), '', strtolower($name));

    // British spelling
    if ($name === self::FB_VARIANT_COLOUR) {
      $name = self::FB_VARIANT_COLOR;
    }

    switch ($name) {
      case self::FB_VARIANT_SIZE:
      case self::FB_VARIANT_COLOR:
      case self::FB_VARIANT_GENDER:
      case self::FB_VARIANT_PATTERN:
        break;
      default:
        $name = 'custom_data:' . strtolower($name);
        break;
    }

    return $name;
  }


  /**
   * Save settings via AJAX (to preserve window context for onboarding)
   **/
  function ajax_save_fb_settings() {
    if (!current_user_can('manage_woocommerce')) {
      self::log('Non manage_woocommerce user attempting to save settings!');
      echo "Non manage_woocommerce user attempting to save settings!";
      wp_die();
    }

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
        } else {
          self::log("Got pixel-only settings, doing nothing");
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

      update_option(
        $this->get_option_key(),
        apply_filters(
          'woocommerce_settings_api_sanitized_fields_' . $this->id,
            $this->settings));

      self::log("Settings saved!");
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
    if (!current_user_can('manage_woocommerce')) {
      wp_send_json('Need manage_woocommerce capability to delete settings');
      $this->fblog('Non manage_woocommerce user attemping to delete settings!');
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
        $this->fblog("Deleted all settings!", array(), false, $ems);
      }

      $this->init_settings();
      $this->settings['fb_api_key'] = '';
      $this->settings['fb_product_catalog_id'] = '';

      $this->settings['fb_pixel_id'] = '';
      $this->settings['fb_pixel_use_pii'] = 'no';

      $this->settings['fb_page_id'] = '';
      $this->settings['fb_external_merchant_settings_id'] = '';

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

      self::log("Settings deleted");
      echo "Settings Deleted";

    }

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

  /**
   * Display custom error message (sugar)
   **/
  function display_error_message($msg) {
    $msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
    self::log($msg);
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
   *  Specific handling for Error #10800 (duplicate retailer_id)
   **/
  function display_duplicate_retailer_id_message($result) {
    $msg = __('We\'ve detected duplicate SKUs in your shop. This can happen
      for a few reasons, including the effects of other plugins. <br/>
      Some of your products may not have been synced to Facebook.
      Please <a href="' . WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL . '">
      delete your settings via the "Advanced" tab and try setup again.</a><br/>

      If this error persists, or you have a use-case for duplicated SKUs,
      <a href="mailto:ads_extension_woocommerce@fb.com">please contact us.</a>',
    'facebook-for-woocommerce');
    self::log($msg);
    set_transient('facebook_plugin_api_error', $msg,
      self::FB_MESSAGE_DISPLAY_TIME);
  }

  /**
   * Deal with FB API responses, display error if FB API returns error
   *
   * @return result if response is 200, null otherwise
   **/
  function check_api_result($result, $logdata = null, $wpid = null) {
    if (is_wp_error($result)) {
      self::log($result->get_error_message());
      $this->display_error_message(
        "There was an issue connecting to the Facebook API: ".
          $result->get_error_message());
      return;
    }
    if ($result['response']['code'] != '200') {
      // Catch 10800 fb error code ("Duplicate retailer ID") and capture FBID
      // if possible, otherwise let user know we found dupe SKUs
      $body = json_decode($result['body']);
      if ($body && $body->error->code == '10800') {
        $error_data = $body->error->error_data; // error_data may contain FBIDs
        if ($error_data && $wpid) {
          $existing_id = $this->get_existing_fbid($error_data, $wpid);
          if (!$existing_id) {
            $this->display_duplicate_retailer_id_message($result);
          } else {
            // Add "existing_id" ID to result
            $body->id = $existing_id;
            $result['body'] = json_encode($body);
            return $result;
          }
        } else {
          $this->display_duplicate_retailer_id_message($result);
        }
      } else {
        $this->display_error_message_from_result($result);
      }

      self::log($result);
      $data = array(
        'result' => $result,
        'data' => $logdata,
      );
      $this->fblog('Non-200 error code from FB', $data, true);
      return null;
    }
    return $result;
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
      $product_data = $this->prepare_product($woo_product);

      $feed_item = array(
        'title' => strip_tags($product_data['name']),
        'availability' => $woo_product->is_in_stock() ? 'in stock' :
          'out of stock',
        'description' => strip_tags($product_data['description']),
        'id' => $product_data['retailer_id'],
        'image_link' => $product_data['image_url'],
        'brand' => strip_tags($this->get_store_name()),
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
      self::log("Not resetting any FBIDs from products,
        must call reset from admin context.");
      return;
    }

    // Include draft products (omit 'post_status' => 'publish')
    self::log("Removing FBIDs from all products");

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

    self::log("Product FBIDs deleted");
  }

  /**
   * Remove FBIDs from a single WC product
   **/
  function reset_single_product($wp_id) {
    $woo_product = new WC_Facebook_Product($wp_id);
    $products = array($woo_product->get_id());
    if ($woo_product->get_type() === 'variable') {
      $products = array_merge($products, $woo_product->get_children());
    }

    $this->delete_post_meta_loop($products);

    self::log("Deleted FB Metadata for product " . $wp_id);
  }

  function ajax_reset_all_fb_products() {
    $this->reset_all_products();
    wp_reset_postdata();
    wp_die();
  }

  function ajax_reset_single_fb_product() {
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
    if (!$this->api_key || !$this->product_catalog_id) {
      self::log("No API key or catalog ID: " . $this->api_key .
        ' and ' . $this->product_catalog_id);
      wp_die();
      return;
    }

    $currently_syncing = get_transient(self::FB_SYNC_IN_PROGRESS);

    if (isset($this->background_processor)) {
      if ($this->background_processor->is_updating()) {
        $this->background_processor->handle_cron_healthcheck();
        $currently_syncing = 1;
      }
    }

    if ($currently_syncing) {
      self::log('Not syncing, sync in progress');
      $this->fblog('Tried to sync during an in-progress sync!');
      $this->display_warning_message('A product sync is in progress.
        Please wait until the sync finishes before starting a new one.');
      wp_die();
      return;
    }

    $is_valid_product_catalog =
      $this->fbgraph->validate_product_catalog($this->product_catalog_id);

    if (!$is_valid_product_catalog) {
      self::log('Not syncing, invalid product catalog!');
      $this->fblog('Tried to sync with an invalid product catalog!');
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
    $args_new = array(
         'post_type'  => 'product',
         'posts_per_page' => -1,
         'post_status' => 'publish',
         'fields'         => 'ids',
         'meta_query' => array(
           array(
             'key'     => self::FB_PRODUCT_GROUP_ID,
             'compare' => 'NOT EXISTS',
           ),
         ),
       );
    $args_old = array(
         'post_type'  => 'product',
         'posts_per_page' => -1,
         'post_status' => 'publish',
         'fields'         => 'ids',
         'meta_query' => array(
           array(
             'key'     => self::FB_PRODUCT_GROUP_ID,
             'compare' => 'EXISTS',
           ),
         ),
       );

    $post_ids_new = get_posts($args_new);
    $post_ids_old = get_posts($args_old);
    $total_new = count($post_ids_new);
    $total_old = count($post_ids_old);
    $post_ids = array_merge($post_ids_new, $post_ids_old);
    $total = count($post_ids);

    $this->fblog(
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
      self::log($starting_message);

      foreach ($post_ids as $post_id) {
        self::log("Pushing post to queue: " . $post_id);
        $this->background_processor->push_to_queue($post_id);
      }

      $this->background_processor->save()->dispatch();
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
      self::log('Synced ' . $count . ' products');
      $this->remove_sticky_message();
      $this->display_info_message('Facebook product sync complete!');
      delete_transient(self::FB_SYNC_IN_PROGRESS);
      $this->fblog('Product sync complete. Total products synced: ' . $count);
    }

    // https://codex.wordpress.org/Function_Reference/wp_reset_postdata
    wp_reset_postdata();

    // This is important, for some reason.
    // See https://codex.wordpress.org/AJAX_in_Plugins
    wp_die();
  }

  /**
  * Toggles product visibility via AJAX (checks current viz and flips it)
  **/
  function ajax_toggle_visibility() {
    if (!isset($_POST['wp_id']) || ! isset($_POST['published'])) {
      wp_die();
    }

    $wp_id = esc_js($_POST['wp_id']);
    $published = esc_js($_POST['published']) === 'true' ? true : false;

    $woo_product = new WC_Facebook_Product($wp_id);
    $products = WC_Facebookcommerce_Utils::get_product_array($woo_product);

    // Loop through product items and flip visibility
    foreach ($products as $item_id) {

      $fb_product_item_id = get_post_meta(
        $item_id,
        self::FB_PRODUCT_ITEM_ID,
        true);

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

  public function fblog($message, $object = array(), $error = false, $ems = '') {
    $message = json_encode(array(
      'message' => $message,
      'object' => $object
    ));

    if (!$ems) {
      $ems = $this->external_merchant_settings_id;
    }

    $this->fbgraph->log(
      $ems,
      $message,
      $error);
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
        'description' => __('The unique identifier for your unique Facebook
          Pixel.', 'facebook-for-woocommerce'),
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

      $this->fblog($error_msg, array(), true);
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

  /**
   * Admin Panel Options
   */
  function admin_options() {
    $configure_button_text = __('Get Started', 'facebook-for-woocommerce');
    $page_name = '';
    if (!empty($this->settings['fb_page_id']) &&
      !empty($this->settings['fb_api_key']) ) {

      $page_name = $this->fbgraph->get_page_name($this->settings['fb_page_id'],
        $this->settings['fb_api_key']);

      $configure_button_text = __('Re-configure Facebook Settings',
        'facebook-for-woocommerce');
    }
    ?>
    <h2><?php _e('Facebook', 'facebook-for-woocommerce'); ?></h2>
    <p><?php printf(__('Control how WooCommerce integrates with your Facebook
      store.', 'facebook-for-woocommerce'), $configure_button_text);?>
    </p>
    <hr/>

    <div id="fbsetup">
      <div class="wrapper">
        <header></header>
        <div class="content">
          <h1><?php _e('Grow your business on Facebook',
          'facebook-for-woocommerce'); ?></h1>
          <p><?php _e('Use this official plugin to help sell more of your
          products using Facebook. After completing the setup, you\'ll be
          ready to create ads that promote your products and you can also
          create a shop section on your Page where customers can browse your
          products on Facebook.', 'facebook-for-woocommerce'); ?></p>

          <?php
            if ($this->settings['fb_api_key'] && !$page_name) {
               // API key is set, but no page name.
               echo sprintf(__('<strong>Your API key is no longer valid.
                Please click "Re-configure Facebook Settings >
                Advanced Options > Delete Settings" and setup
                Facebook for WooCommerce again.</strong></br>',
                'facebook-for-woocommerce'));
                echo '<p id ="configure_button"><a href="#"
                  class="btn" onclick="facebookConfig()" id="set_dia" ';
                echo '>' . esc_html($configure_button_text) . '</a></p>';
            } else {
              if (!current_user_can('manage_woocommerce')) {
                printf(__('<strong>You must have "manage_woocommerce"
                    permissions to use this plugin. </strong></br>',
                      'facebook-for-woocommerce')) .
                    '</p>';
              } else {
                $currently_syncing = get_transient(self::FB_SYNC_IN_PROGRESS);
                $connected = ($page_name != '');

                echo '<p id="connection_status">';
                if ($connected) {
                  echo sprintf(__('Currently connected to Facebook page:
                    </br><a target="_blank"
                    href="https://www.facebook.com/%1$s">%2$s</a>',
                    'facebook-for-woocommerce'),
                    $this->settings['fb_page_id'],
                    '<strong>' . esc_html($page_name) . '</strong>');
                }
                echo '</p>';

                echo '<p id="resync_button"><a href="#"
                    class="btn" onclick="sync_confirm()" id="resync_products" ';

                if (($connected && $currently_syncing) || !$connected) {
                  echo 'style="display:none;" ';
                }
                echo '>Force Product Resync</a><p/>';

                echo '<p id ="configure_button"><a href="#"
                  class="btn" onclick="facebookConfig()" id="set_dia" ';
                if ($currently_syncing) {
                  echo 'style="display:none;" ';
                }
                echo '>' . esc_html($configure_button_text) . '</a></p>';

                echo '<p id="sync_status">';
                if ($connected && $currently_syncing) {
                  echo sprintf(__('<strong>Facebook product sync in progress!
                    <br/>LEAVE THIS PAGE OPEN TO KEEP SYNCING!</strong></br>',
                    'facebook-for-woocommerce'));
                }
                if ($connected && !$currently_syncing) {
                  echo '<strong>Status: </strong>' .
                  'Products are synced to Facebook.';
                }
                echo '</p>';

                echo '<p id="sync_progress"></p>';

              }
            }
          ?>
        </div>
      </div>
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

  /**
   * Helper log function for debugging
   */
  public static function log($message) {
    if (WP_DEBUG === true) {
      if (is_array($message) || is_object($message)) {
        error_log(json_encode($message));
      }
      else {
        error_log($message);
      }
    }
  }

  private function validateGender($gender) {
    if (!self::$validGenderArray[$gender] && $gender) {
      $first_char = strtolower(substr($gender, 0, 1));
      // Men, Man, Boys
      if ($first_char === 'm' || $first_char === 'b') {
        return "male";
      }
      // Women, Woman, Female, Ladies
      if ($first_char === 'w' || $first_char === 'f' || $first_char === 'l') {
        return "female";
      }
      if ($first_char === 'u') {
        return "unisex";
      }
      if (strlen($gender) >= 3) {
        $gender = strtolower(substr($gender, 0, 3));
        if ($gender === 'gir' || $gender === 'her') {
          return "female";
        }
        if ($gender === 'him' || $gender === 'his' || $gender == 'guy') {
          return "male";
        }
      }
      return null;
    }
    return $gender;
  }

  function delete_product_item($wp_id) {
    $fb_product_item_id = get_post_meta(
      $wp_id,
      self::FB_PRODUCT_ITEM_ID,
      true);
    if ($fb_product_item_id) {
      $pi_result =
        $this->fbgraph->delete_product_item($fb_product_item_id);
      self::log($pi_result);
    }
  }

  function create_product_item_using_itemid(
    $wp_id,
    $product_group_id,
    &$gallery_urls) {
    $woo_product = new WC_Facebook_Product($wp_id, $gallery_urls);
    $retailer_id =
      WC_Facebookcommerce_Utils::get_fb_retailer_id($woo_product);
    return $this->create_product_item(
      $woo_product,
      $retailer_id,
      $product_group_id);

  }
}
