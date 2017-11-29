<?php
/**
 * @package FacebookCommerce
 */
if (! defined('ABSPATH')) {
  exit;
}

if (! class_exists('WC_Facebookcommerce_Info_Banner')) :

/**
 * FB Info Banner class
 */
class WC_Facebookcommerce_Info_Banner {

  const FB_SHOW_REDIRECT = 7;
  const FB_BANNER_CAP = 9000;

  /** @var object Class Instance */
  private static $instance;

  /** @var string If the banner has been dismissed */
  private $last_dismissed_time = '';
  private $external_merchant_settings_id = '';
  private $pixel_install_time = '';

  /**
   * Get the class instance
   */
  public static function get_instance(
    $last_dismissed_time = '',
    $external_merchant_settings_id,
    $pixel_install_time) {
    return null === self::$instance
      ? (self::$instance = new self(
        $last_dismissed_time,
        $external_merchant_settings_id,
        $pixel_install_time))
      : self::$instance;
  }

  /**
   * Constructor
   */
  public function __construct(
    $last_dismissed_time = '',
    $external_merchant_settings_id,
    $pixel_install_time) {
    $this->last_dismissed_time = $last_dismissed_time;
    $this->external_merchant_settings_id = $external_merchant_settings_id;
    $this->pixel_install_time = $pixel_install_time;
    $is_pixel_eligible = !$pixel_install_time
      ? false
      : self::check_time_cap($pixel_install_time, self::FB_SHOW_REDIRECT);
    $show_notice = !$last_dismissed_time
      ? true
      : self::check_time_cap($last_dismissed_time, self::FB_BANNER_CAP);
    // Reset dismiss time if pass date cap.
    if ($show_notice && !$last_dismissed_time) {
      update_option('fb_info_banner_last_dismiss_time', '');
    }
    // Don't show notice if dismissed or ineligible.
    if (!$show_notice || !$is_pixel_eligible) {
      return true;
    }
    add_action('admin_notices', array($this, 'banner'));
    add_action('admin_init', array($this, 'dismiss_banner'));
  }

  /**
   * Display a info banner on Woocommerce pages.
   */
  public function banner() {
    $screen = get_current_screen();
    if (!in_array($screen->base, array('woocommerce_page_wc-reports',
      'woocommerce_page_wc-settings', 'woocommerce_page_wc-status')) ||
      $screen->is_network || $screen->action) {
      return;
    }
    $redirect_url =
      esc_url('https://www.facebook.com/ads/dia/redirect/?settings_id='
        .$this->external_merchant_settings_id);
    $dismiss_url = $this->dismiss_url();
    $message = __('<strong>Facebook for WooCommerce: </strong>' . 'You can now
      optimize your Facebook Ads, based on data from your pixel.',
      'facebook-for-woocommerce');
    echo '<div class="updated fade"><p>' . $message . "\n";
    echo '<p><a href="' . $redirect_url . '" title="' .
      __('Click and redirect.', 'facebook-for-woocommerce').
      '"> ' . __('Get Started', 'facebook-for-woocommerce') . '</a>' . ' | '.
      '<a href="' . esc_url($dismiss_url). '" title="' .
      __('Dismiss this notice.', 'facebook-for-woocommerce').
      '"> ' . __('Dismiss', 'facebook-for-woocommerce') . '</a></p></div>';

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

  /**
   * Helper function to check time cap.
   */
  private static function check_time_cap($from, $date_cap) {
    $now = new DateTime(current_time('mysql'));
    $diff_in_day = $now->diff(new DateTime($from))->format('%a');
    return is_numeric($diff_in_day) && (int)$diff_in_day > $date_cap;
  }

}

endif;
