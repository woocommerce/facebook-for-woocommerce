<?php
/**
 * @package FacebookCommerce
 */
if (! defined('ABSPATH')) {
  exit;
}

if (! class_exists('FacebookWordPress_Pixel_Proxy')) :

/**
 * Utility class for implemnenting FB Reverse Proxy
 *
 * The FB Reverse Proxy is used to load the pixel base code in cases where it
 * can not be loaded normally. It also fetches the Pixel basecode so the
 * injected basecode is always up-to-date.
 */
class FacebookWordPress_Pixel_Proxy {
  const MAX_PROXY_ENDPOINT_LENGTH = 20;
  const MIN_PROXY_ENDPOINT_LENGTH = 8;
  const PROXY_NAMESPACE = 'proxy_namespace';
  const PROXY_FBEVENTS = 'proxy_fbevents';
  const PROXY_TR = 'proxy_tr';
  const SETTINGS_KEY = 'facebook_proxy_config';

  public static function replace_basecode($pixel_id) {
    self::setup_proxy_endpoints();

    if (!isset($pixel_id)) {
      $pixel_id = WC_Facebookcommerce_Pixel::get_pixel_id();
    }
    if (!isset($pixel_id)) {
      return;
    }

    $config = get_option(self::SETTINGS_KEY, array());
    $proxy_namespace = $config[self::PROXY_NAMESPACE];
    $proxy_fbevents = $config[self::PROXY_FBEVENTS];
    $proxy_tr = $config[self::PROXY_TR];

    $query_string = http_build_query(array(
      'js' => 'index.php/wp-json/'.$proxy_namespace.'/'.$proxy_fbevents,
      'tr' => 'index.php/wp-json/'.$proxy_namespace.'/'.$proxy_tr,
    ));

    $response = wp_remote_get(
      'https://connect.facebook.com/signals/fbevents/basecode/'.
        $pixel_id.'?'.$query_string);
    if (wp_remote_retrieve_response_code($response) != 200) {
      return;
    }

    WC_Facebookcommerce_Pixel::set_basecode($response['body']);
  }

  /**
   * Helper function for registering the fbevents + tr "proxy" endpoints
   *
   * These will look something like /wp-json/5a25d5a3ac60d/5a25c881bff14
   * unless the user overrides them in settings.
   */
  public static function register_pixel_proxy_api() {
    $options = get_option(self::SETTINGS_KEY);
    // We don't need to render endpoints if the plugin has not been
    // configured
    if (!isset($options[self::PROXY_NAMESPACE])) {
      return;
    }

    register_rest_route(
      $options[self::PROXY_NAMESPACE],
      $options[self::PROXY_FBEVENTS],
      array(
        'methods' => 'GET',
        'callback' =>
          array(self, 'fetch_fbevents_script'),
      ));
    register_rest_route(
      $options[self::PROXY_NAMESPACE],
      $options[self::PROXY_TR],
      array(
        'methods' => 'GET',
        'callback' => array(self, 'ping_tr_endpoint'),
      ));
  }

  public static function fetch_fbevents_script() {
    $pixel_id = WC_Facebookcommerce_Pixel::get_pixel_id();
    if (!isset($pixel_id)) {
      return new WP_Error(
        'fb_no_pixel_id_set',
        'A pixel ID was not set in the Facebook plugin settings',
        array( 'status' => 500 ));
    }

    $options = get_option(self::SETTINGS_KEY);
    $proxy_namespace = $options[self::PROXY_NAMESPACE];
    $proxy_tr = $options[self::PROXY_TR];

    $query_string = http_build_query(array(
      'tr' => 'index.php/wp-json/'.$proxy_namespace.'/'.$proxy_tr,
      'v' => 'next',
    ));

    $url = 'https://connect.facebook.net/signals/fbevents/'
      .$pixel_id.'/?'.$query_string;
    $response = wp_remote_get($url, array(
      'headers' => array(
        'X-FB-Proxy' => true,
        'X-Forwarded-For' => self::get_user_ip(),
      ),
      'timeout' => 10,
    ));

    self::return_response($response);
  }

  public static function ping_tr_endpoint(WP_REST_Request $request) {
    $uri = 'https://www.facebook.com/tr/';
    $query_string = http_build_query($request->get_params());

    $headers = $request->get_headers();
    $response = wp_remote_get(
      $uri.'?'.$query_string,
      array(
        'headers' => array_merge(
          $request->get_headers(),
          array(
            'X-FB-Proxy' => true,
            'X-Forwarded-For' => self::get_user_ip(),
          )),
      ));

    self::return_response($response);
  }

  private static function setup_proxy_endpoints() {
    $options = get_option(self::SETTINGS_KEY);
    $options[self::PROXY_NAMESPACE] = self::get_random_endpoint_name();
    $options[self::PROXY_FBEVENTS] = self::get_random_endpoint_name();
    $options[self::PROXY_TR] = self::get_random_endpoint_name();
    update_option(self::SETTINGS_KEY, $options);
  }

  private static function get_random_endpoint_name() {
    $length = rand(
      self::MIN_PROXY_ENDPOINT_LENGTH,
      self::MAX_PROXY_ENDPOINT_LENGTH);
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
  }

  private static function return_response($response) {
    foreach ($response['headers'] as $key => $value) {
      if (trim($key) == 'content-encoding') {
        continue;
      }
      if (trim($key) == 'content-length') {
        continue;
      }
      if (trim($key) == 'transfer-encoding') {
        continue;
      }
      if (trim($key) == 'location') {
        continue;
      }

      header($key.': '.$value);
    }

    echo $response['body'];
    exit();
  }

  private static function get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      return $_SERVER['REMOTE_ADDR'];
    }
  }
}

endif;
