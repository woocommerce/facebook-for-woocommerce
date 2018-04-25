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

  /** @var object Class Instance */
  private static $instance;

  /** @var string If the banner has been dismissed */
  private $last_dismissed_time = '';
  private $external_merchant_settings_id = '';

  /**
   * Get the class instance
   */
  public static function get_instance(
    $last_dismissed_time = '',
    $external_merchant_settings_id) {
    return null === self::$instance
      ? (self::$instance = new self(
        $last_dismissed_time,
        $external_merchant_settings_id))
      : self::$instance;
  }

  /**
   * Constructor
   */
  public function __construct(
    $last_dismissed_time = '',
    $external_merchant_settings_id) {
    $this->last_dismissed_time = $last_dismissed_time;
    $this->external_merchant_settings_id = $external_merchant_settings_id;
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
    $message = __('<strong>Facebook for WooCommerce: </strong>' .
      'Take advantage of your integration between WooCommerce and Facebook.
      Create ads that are designed for getting more website purchases. You\'ll
      see the impact on sales and revenue.',


      'facebook-for-woocommerce');
    echo '<div class="updated fade"><p>' . $message . "\n";
    echo '<p><a href="' . $redirect_url . '" title="' .
      __('Click and redirect.', 'facebook-for-woocommerce').
      '"> ' . __('Create an Ad', 'facebook-for-woocommerce') . '</a>' . ' | '.
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
}

endif;
