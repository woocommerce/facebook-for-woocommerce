<?php
/**
* @package FacebookCommerce
*/

if (!class_exists('WC_Facebookcommerce_Pixel')) :


class WC_Facebookcommerce_Pixel {
  private $pixel_id;
  private $user_info;
  private $last_event;

  public function __construct($pixel_id, $user_info=array()) {
    $this->pixel_id = $pixel_id;
    $this->user_info = $user_info;
    $this->last_event = '';
  }

  /**
   * Returns FB pixel code
   */
  public function pixel_base_code() {
    $params = self::add_version_info();

    return sprintf("
<!-- %s Facebook Integration Begin -->
<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
%s
fbq('track', 'PageView', %s);
</script>
<noscript><img height=\"1\" width=\"1\" style=\"display:none\"
src=\"https://www.facebook.com/tr?id=%s&ev=PageView&noscript=1\"
/></noscript>
<!-- DO NOT MODIFY -->
<!-- End Facebook Pixel Code -->
<!-- %s Facebook Integration end -->
      ",
      WC_Facebookcommerce_Utils::getIntegrationName(),
      $this->pixel_init_code(),
      json_encode($params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT),
      esc_js($this->pixel_id),
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
   * You probably should use WC_Facebookcommerce_Pixel::inject_event() but
   * this method is available if you need to modify the JS code somehow
   */
  public static function build_event($event_name, $params, $method='track') {
    $params = self::add_version_info($params);
    return sprintf(
      "// %s Facebook Integration Event Tracking\n".
      "fbq('%s', '%s', %s);",
      WC_Facebookcommerce_Utils::getIntegrationName(),
      $method,
      $event_name,
      json_encode($params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT));
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
      esc_js($this->pixel_id),
      json_encode($this->user_info, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT),
      json_encode($params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT));
  }

}

endif;
