<?php
/**
* @package FacebookCommerce
*/

if (!class_exists('WC_Facebookcommerce_Pixel')) :

include_once 'includes/fb-pixel-proxy.php';

class WC_Facebookcommerce_Pixel {
  const BASECODE_KEY = 'proxy_basecode';
  const SETTINGS_KEY = 'facebook_config';
  const PIXEL_ID_KEY = 'pixel_id';
  const USE_PII_KEY = 'use_pii';
  const IS_BASECODE_FETCH_ENABLED = false;

  const NOSCRIPT_REGEX = '/<noscript.*?\/noscript>/s';
  const PIXEL_RENDER = 'pixel_render';
  const NO_SCRIPT_RENDER = 'no_script_render';

  private $user_info;
  private $last_event;
  static $render_cache = array();

  static $default_pixel_basecode = "
<script type='text/javascript'>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
</script>
";

  public function __construct($user_info=array()) {
    $this->user_info = $user_info;
    $this->last_event = '';
  }

  public static function initialize() {
    if (!is_admin()) {
      return;
    }

    if (self::IS_BASECODE_FETCH_ENABLED) {
      // run the replace basecode call any time the pixel is replaced
      add_action(
        'add_option_'.self::SETTINGS_KEY,
        function($option_name, $option_value) {
          WC_Facebookcommerce_Pixel::on_settings_changed(
            array(),
            $option_value);
        },
        /* priority */ 10,
        /* accepted_args*/ 2);
      add_action(
        'update_option_'.self::SETTINGS_KEY,
        array(WC_Facebookcommerce_Pixel, 'on_settings_changed'),
        /* priority */ 10,
        /* accepted_args*/ 2);
    }

    // Initialize PixelID in storage - this will only need to happen when the
    // use is an admin
    $pixel_id = self::get_pixel_id();
    $should_update = false;
    if (!WC_Facebookcommerce_Utils::is_valid_id($pixel_id) &&
      class_exists('WC_Facebookcommerce_WarmConfig')) {
      $fb_warm_pixel_id = WC_Facebookcommerce_WarmConfig::$fb_warm_pixel_id;

      if (WC_Facebookcommerce_Utils::is_valid_id($fb_warm_pixel_id) &&
          (int)$fb_warm_pixel_id == $fb_warm_pixel_id) {
        $fb_warm_pixel_id = (string)$fb_warm_pixel_id;
        self::set_pixel_id($fb_warm_pixel_id);
      }
    }
  }

  /**
   * Returns FB pixel code script part
   */
  public function pixel_base_code() {
    $pixel_id = self::get_pixel_id();
    if (
      self::$render_cache[self::PIXEL_RENDER] === true ||
      !isset($pixel_id) ||
      $pixel_id === 0
    ) {
      return;
    }

    self::$render_cache[self::PIXEL_RENDER] = true;

    $params = self::add_version_info();

    return sprintf("
<!-- %s Facebook Integration Begin -->
%s
<script>
%s
fbq('track', 'PageView', %s);
<!-- Support AJAX add to cart -->
document.addEventListener('DOMContentLoaded', function() {
  jQuery && jQuery(function($){
    $('body').on('added_to_cart', function(event) {
      // Ajax action.
      $.get('?wc-ajax=fb_inject_add_to_cart_event', function(data) {
        $('head').append(data);
      });
    });
  });
}, false);
<!-- End Support AJAX add to cart -->
</script>
<!-- DO NOT MODIFY -->
<!-- %s Facebook Integration end -->
    ",
    WC_Facebookcommerce_Utils::getIntegrationName(),
    self::get_basecode(),
    $this->pixel_init_code(),
    json_encode($params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT),
    WC_Facebookcommerce_Utils::getIntegrationName());
  }

  /**
   * Prevent double-fires by checking the last event
   */
  public function check_last_event($event_name) {
    return $event_name === $this->last_event;
  }

  /**
   * Preferred method to inject events in a page, normally you should use this
   * instead of WC_Facebookcommerce_Pixel::build_event()
   */
  public function inject_event($event_name, $params, $method='track') {
    $code = self::build_event($event_name, $params, $method);
    $this->last_event = $event_name;

    if (WC_Facebookcommerce_Utils::isWoocommerceIntegration()) {
      WC_Facebookcommerce_Utils::wc_enqueue_js($code);
    } else {
      printf("
<!-- Facebook Pixel Event Code -->
<script>
%s
</script>
<!-- End Facebook Pixel Event Code -->
        ",
        $code);
    }
  }

  /**
   * Returns FB pixel code noscript part to avoid W3 validation error
   */
  public function pixel_base_code_noscript() {
    $pixel_id = self::get_pixel_id();
    if (
      self::$render_cache[self::NO_SCRIPT_RENDER] === true ||
      !isset($pixel_id) ||
      $pixel_id === 0
    ) {
      return;
    }

    self::$render_cache[self::NO_SCRIPT_RENDER] = true;

    return sprintf("
<!-- Facebook Pixel Code -->
<noscript>
<img height=\"1\" width=\"1\" style=\"display:none\" alt=\"fbpx\"
src=\"https://www.facebook.com/tr?id=%s&ev=PageView&noscript=1\"/>
</noscript>
<!-- DO NOT MODIFY -->
<!-- End Facebook Pixel Code -->
    ",
    esc_js($pixel_id));
  }

  /**
   * You probably should use WC_Facebookcommerce_Pixel::inject_event() but
   * this method is available if you need to modify the JS code somehow
   */
  public static function build_event($event_name, $params, $method='track') {
    $params = self::add_version_info($params);
    return sprintf(
      "/* %s Facebook Integration Event Tracking */\n".
      "fbq('%s', '%s', %s);",
      WC_Facebookcommerce_Utils::getIntegrationName(),
      $method,
      $event_name,
      json_encode($params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT));
  }

  public static function get_pixel_id() {
    $fb_options = self::get_options();
    if (!$fb_options) {
      return '';
    }
    return isset($fb_options[self::PIXEL_ID_KEY]) ?
           $fb_options[self::PIXEL_ID_KEY] : '';
  }

  public static function set_pixel_id($pixel_id) {
    $fb_options = self::get_options();

    if (isset($fb_options[self::PIXEL_ID_KEY])
        && $fb_options[self::PIXEL_ID_KEY] == $pixel_id) {
      return;
    }

    $fb_options[self::PIXEL_ID_KEY] = $pixel_id;
    update_option(self::SETTINGS_KEY, $fb_options);
  }

  public static function set_basecode($new_basecode) {
    $fb_options = self::get_options();
    $new_basecode = preg_replace(self::NOSCRIPT_REGEX, '', $new_basecode);
    $fb_options[self::BASECODE_KEY] =
      htmlentities(stripslashes($new_basecode));
    update_option(self::SETTINGS_KEY, $fb_options);
  }

  public static function get_basecode() {
    $fb_options = self::get_options();
    $basecode = $fb_options[self::BASECODE_KEY];
    if (!isset($basecode)) {
      return self::$default_pixel_basecode;
    }

    return htmlspecialchars_decode($basecode);
  }

  public static function on_settings_changed($old_value, $new_value) {
    $old_pixel = $old_value[self::PIXEL_ID_KEY];
    $new_pixel = $new_value[self::PIXEL_ID_KEY];
    if (isset($new_pixel) && $old_pixel !== $new_pixel) {
      FacebookWordPress_Pixel_Proxy::replace_basecode($new_pixel);
    }
  }

  private static function get_version_info() {
    global $wp_version;

    if (WC_Facebookcommerce_Utils::isWoocommerceIntegration()) {
      return array(
        'source' => 'woocommerce',
        'version' => WC()->version,
        'pluginVersion' => WC_Facebookcommerce_Utils::PLUGIN_VERSION
      );
    }

    return array(
      'source' => 'wordpress',
      'version' => $wp_version,
      'pluginVersion' => WC_Facebookcommerce_Utils::PLUGIN_VERSION
    );
  }

  public static function get_options() {
    return get_option(self::SETTINGS_KEY, array(
      self::PIXEL_ID_KEY => '0',
      self::USE_PII_KEY => 0,
    ));
  }

  /**
   * Returns an array with version_info for pixel fires. Parameters provided by
   * users should not be overwritten by this function
   */
  private static function add_version_info($params=array()) {
    // if any parameter is passed in the pixel, do not overwrite it
    return array_replace(self::get_version_info(), $params);
  }

  /**
   * Init code might contain additional information to help matching website
   * users with facebook users. Information is hashed in JS side using SHA256
   * before sending to Facebook.
   */
  private function pixel_init_code() {
    $version_info = self::get_version_info();
    $agent_string = sprintf(
      '%s-%s-%s',
      $version_info['source'],
      $version_info['version'],
      $version_info['pluginVersion']);

    $params = array(
      'agent' => $agent_string);

    return sprintf(
      "fbq('init', '%s', %s, %s);\n",
      esc_js(self::get_pixel_id()),
      json_encode($this->user_info, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT),
      json_encode($params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT));
  }

}

endif;
