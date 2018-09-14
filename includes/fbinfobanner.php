<?php
/**
 * @package FacebookCommerce
 */
if (! defined('ABSPATH')) {
  exit;
}

if (!class_exists('WC_Facebookcommerce_Utils')) {
  include_once 'includes/fbutils.php';
}

if (! class_exists('WC_Facebookcommerce_Info_Banner')) :

/**
 * FB Info Banner class
 */
class WC_Facebookcommerce_Info_Banner {

  const FB_DEFAULT_TIP_DISMISS_TIME_CAP = 60;
  const FB_NO_TIP_EXISTS = 'No Tip Exist!';
  const DEFAULT_TIP_TITLE = 'Facebook for WooCommerce';
  const DEFAULT_TIP_BODY = 'Create ads that are designed
                for getting online sales and revenue.';
  const DEFAULT_TIP_ACTION = 'Create Ads';
  const DEFAULT_TIP_ACTION_LINK = 'https://www.facebook.com/ads/dia/redirect/?settings_id=';
  const DEFAULT_TIP_IMG_URL_PREFIX = 'https://www.facebook.com';
  const DEFAULT_TIP_IMG_URL = 'https://www.facebook.com/images/ads/growth/aymt/glyph-shopping-cart_page-megaphone.png';
  const CHANNEL_ID = 2087541767986590;
  private $tip_id = '';

  /** @var object Class Instance */
  private static $instance;

  /** @var string If the banner has been dismissed */
  private $last_dismissed_time = '';
  private $external_merchant_settings_id = '';
  private $fbgraph = '';

  /**
   * Get the class instance
   */
  public static function get_instance(
    $last_dismissed_time = '',
    $external_merchant_settings_id,
    $fbgraph) {
    return null === self::$instance
      ? (self::$instance = new self(
        $last_dismissed_time,
        $external_merchant_settings_id,
        $fbgraph))
      : self::$instance;
  }

  /**
   * Constructor
   */
  public function __construct(
    $last_dismissed_time = '',
    $external_merchant_settings_id,
    $fbgraph) {
    $this->last_dismissed_time = $last_dismissed_time;
    $this->external_merchant_settings_id = $external_merchant_settings_id;
    $this->fbgraph = $fbgraph;
    add_action('wp_ajax_ajax_woo_infobanner_post_click', array($this, 'ajax_woo_infobanner_post_click'));
    add_action('wp_ajax_ajax_woo_infobanner_post_xout', array($this, 'ajax_woo_infobanner_post_xout'));
    add_action('admin_notices', array($this, 'banner'));
    add_action('admin_init', array($this, 'dismiss_banner'));
  }

  /**
   * Post click event when hit primary button.
   */
   function ajax_woo_infobanner_post_click() {
     WC_Facebookcommerce_Utils::check_woo_ajax_permissions(
       'post tip click event',
       true);
     if ($this->tip_id == null) {
       WC_Facebookcommerce_Utils::fblog(
         'Do not have tip id maybe
         rendering static one',
         array(),
         true);
     } else {
       WC_Facebookcommerce_Utils::tip_events_log(
         $this->tip_id,
         self::CHANNEL_ID,
         'click');
     }
   }

  /**
   * Post xout event when hit dismiss button.
   */
   function ajax_woo_infobanner_post_xout() {
     WC_Facebookcommerce_Utils::check_woo_ajax_permissions(
       'post tip xout event',
       true);
     if ($this->tip_id == null) {
       WC_Facebookcommerce_Utils::fblog(
         'Do not have tip id maybe
         rendering static one',
         array(),
         true);
     } else {
       WC_Facebookcommerce_Utils::tip_events_log(
         $this->tip_id,
         self::CHANNEL_ID,
         'xout');
     }
   }

  /**
   * Display a info banner on Woocommerce pages.
   */
  public function banner() {
    update_option('fb_info_banner_last_show_tip_time', current_time('mysql'));
    $screen = get_current_screen();
    if (!in_array($screen->base, array('woocommerce_page_wc-reports',
      'woocommerce_page_wc-settings', 'woocommerce_page_wc-status')) ||
      $screen->is_network || $screen->action) {
      return;
    }
    $aymt_gate = get_option('fb_aymt_temporary_gatekeeper');
    $tip_info = $aymt_gate
    ? $this->fbgraph->get_tip_info(
      $this->external_merchant_settings_id)
    : self::FB_NO_TIP_EXISTS;

    if ($tip_info != null) {
      $is_default = ($tip_info === self::FB_NO_TIP_EXISTS);

      // Will not show tip if tip is default and has not over 60 days after last dismissed
      if ($is_default &&
      !WC_Facebookcommerce_Utils::check_time_cap($this->last_dismissed_time,
      self::FB_DEFAULT_TIP_DISMISS_TIME_CAP)) {
          return;
      }
      // Get tip creatives via API
      $tip_title = self::DEFAULT_TIP_TITLE;
      $tip_body = self::DEFAULT_TIP_BODY;
      $tip_action = self::DEFAULT_TIP_ACTION;
      $tip_action_link = esc_url(self::DEFAULT_TIP_ACTION_LINK.
        $this->external_merchant_settings_id);
      $tip_img_url = self::DEFAULT_TIP_IMG_URL;
      if (!$is_default) {
        $tip_title = isset($tip_info->tip_title->__html)
          ? $tip_info->tip_title->__html
          : self::DEFAULT_TIP_TITLE;

        $tip_body = isset($tip_info->tip_body->__html)
          ? $tip_info->tip_body->__html
          : self::DEFAULT_TIP_BODY;

        $tip_action_link = isset($tip_info->tip_action_link)
          ? $tip_info->tip_action_link
          : esc_url(self::DEFAULT_TIP_ACTION_LINK.
            $this->external_merchant_settings_id);

        $tip_action = isset($tip_info->tip_action->__html)
          ? $tip_info->tip_action->__html
          : self::DEFAULT_TIP_ACTION;

        $tip_img_url = isset($tip_info->tip_img_url)
          ? self::DEFAULT_TIP_IMG_URL_PREFIX . $tip_info->tip_img_url
          : self::DEFAULT_TIP_IMG_URL;

        $tip_id = isset($tip_info->tip_id)
          ? $tip_info->tip_id
          : '';
      }

      $dismiss_url = $this->dismiss_url();
      echo '<div class="updated fade"><div id="fbinfobanner"><div><img src="'. $tip_img_url .
      '" class="iconDetails"></div><p class = "tipTitle">' .
      __('<strong>' . $tip_title . '</strong>', 'facebook-for-woocommerce') . "\n";
      echo '<p class = "tipContent">'.
        __($tip_body, 'facebook-for-woocommerce') . '</p>';
      echo '<p class = "tipButton"><a href="' . $tip_action_link . '" class = "btn" onclick="fb_woo_infobanner_post_click()" title="' .
        __('Click and redirect.', 'facebook-for-woocommerce').
        '"> ' . __($tip_action, 'facebook-for-woocommerce') . '</a>' .
        '<a href="' . esc_url($dismiss_url). '" class = "btn dismiss grey" onclick="fb_woo_infobanner_post_xout()" title="' .
        __('Dismiss this notice.', 'facebook-for-woocommerce').
        '"> ' . __('Dismiss', 'facebook-for-woocommerce') . '</a></p></div></div>';
    } else {
      WC_Facebookcommerce_Utils::fblog(
        "Fail to get tip via GraphAPI", array(), true);
    }
  }

  /**
   * Returns the url that the user clicks to remove the info banner
   * @return (string)
   */
  private function dismiss_url() {
    $url = admin_url('admin.php');

    $url = add_query_arg(array(
      'page'      => 'wc-settings',
      'tab'       => 'integration',
      'wc-notice' => 'dismiss-fb-info-banner',
    ), $url);

    return wp_nonce_url($url, 'woocommerce_info_banner_dismiss');
  }

  /**
   * Handles the dismiss action so that the banner can be permanently hidden
   * during time threshold
   */
  public function dismiss_banner() {
    if (!isset($_GET['wc-notice'])) {
      return;
    }

    if ('dismiss-fb-info-banner' !== $_GET['wc-notice']) {
      return;
    }

    if (!check_admin_referer('woocommerce_info_banner_dismiss')) {
      return;
    }

    update_option('fb_info_banner_last_dismiss_time', current_time('mysql'));

    if (wp_get_referer()) {
      wp_safe_redirect(wp_get_referer());
    } else {
      wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=integration'));
    }
  }
}

endif;
