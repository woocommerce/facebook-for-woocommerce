<?php
/**
* @package FacebookCommerce
*/

if (!class_exists('WC_Facebookcommerce_EventsTracker')) :

if (!class_exists('WC_Facebookcommerce_Utils')) {
  include_once 'includes/fbutils.php';
}

if (!class_exists('WC_Facebookcommerce_Pixel')) {
  include_once 'facebook-commerce-pixel-event.php';
}

class WC_Facebookcommerce_EventsTracker {
  private $pixel;

  public function __construct($pixel_id, $user_info) {
    $this->pixel = new WC_Facebookcommerce_Pixel($pixel_id, $user_info);
  }

  /**
   * Base pixel code to be injected on page head. Because of this, it's better
   * to echo the return value than using
   * WC_Facebookcommerce_Utils::wc_enqueue_js() in this case
   */
  public function inject_base_pixel() {
    echo $this->pixel->pixel_base_code();
  }

  /**
   * Base pixel noscript to be injected on page body. This is to avoid W3
   * validation error.
   */
  public function inject_base_pixel_noscript() {
    echo $this->pixel->pixel_base_code_noscript();
  }

  /**
   * Triggers ViewCategory for product category listings
   */
  public function inject_view_category_event() {
    global $wp_query;

    $products = array_values(array_map(function($item) {
        return wc_get_product($item->ID);
      },
      $wp_query->get_posts()));

    // if any product is a variant, fire the pixel with
    // content_type: product_group
    $content_type = 'product';
    $product_ids = array();
    foreach ($products as $product) {
      if (!$product) {
        continue;
      }
      $product_ids = array_merge(
        $product_ids,
        WC_Facebookcommerce_Utils::get_fb_content_ids($product));
      if (WC_Facebookcommerce_Utils::is_variable_type($product->get_type())) {
        $content_type = 'product_group';
      }
    }

    $categories =
      WC_Facebookcommerce_Utils::get_product_categories(get_the_ID());

    $this->pixel->inject_event(
      'ViewCategory',
      array(
        'content_name' => $categories['name'],
        'content_category' => $categories['categories'],
        'content_ids' => json_encode(array_slice($product_ids, 0, 10)),
        'content_type' => $content_type
      ),
      'trackCustom');
  }

  /**
   * Triggers Search for result pages (deduped)
   */
  public function inject_search_event() {
    if (!is_admin() && is_search() && get_search_query() !== '') {
      if ($this->pixel->check_last_event('Search')) {
        return;
      }

      if (WC_Facebookcommerce_Utils::isWoocommerceIntegration()) {
        $this->actually_inject_search_event();
      } else {
        add_action('wp_head', array($this, 'actually_inject_search_event'), 11);
      }
    }
  }

  /**
   * Triggers Search for result pages
   */
  public function actually_inject_search_event() {
    $this->pixel->inject_event(
      'Search',
      array(
        'search_string' => get_search_query()
      ));
  }

  /**
   * Helper function to iterate through a cart and gather all content ids
   */
  private function get_content_ids_from_cart($cart) {
    $product_ids = array();
    foreach ($cart as $item) {
      $product_ids = array_merge(
        $product_ids,
        WC_Facebookcommerce_Utils::get_fb_content_ids($item['data']));
    }
    return $product_ids;
  }

  /**
   * Triggers ViewContent product pages
   */
  public function inject_view_content_event() {
    $product = wc_get_product(get_the_ID());
    $content_type = 'product';
    if (!$product) {
      return;
    }

    // if product is a variant, fire the pixel with content_type: product_group
    if (WC_Facebookcommerce_Utils::is_variable_type($product->get_type())) {
      $content_type = 'product_group';
    }

    $content_ids = WC_Facebookcommerce_Utils::get_fb_content_ids($product);
    $this->pixel->inject_event(
      'ViewContent',
      array(
        'content_name' => $product->get_title(),
        'content_ids' => json_encode($content_ids),
        'content_type' => $content_type,
        'value' => $product->get_price(),
        'currency' => get_woocommerce_currency()
      ));
  }

  /**
   * Triggers AddToCart for cart page and add_to_cart button clicks
   */
  public function inject_add_to_cart_event() {
    $product_ids = $this->get_content_ids_from_cart(WC()->cart->get_cart());

    $this->pixel->inject_event(
      'AddToCart',
      array(
        'content_ids' => json_encode($product_ids),
        'content_type' => 'product',
        'value' => WC()->cart->total,
        'currency' => get_woocommerce_currency()
      ));
  }

  /**
  * Triggered by add_to_cart jquery trigger
  */
  public function inject_ajax_add_to_cart_event() {
    ob_start();

    echo '<script>';

    $product_ids = $this->get_content_ids_from_cart(WC()->cart->get_cart());

    echo $this->pixel->build_event(
      'AddToCart',
      array(
        'content_ids' => json_encode($product_ids),
        'content_type' => 'product',
        'value' => WC()->cart->total,
        'currency' => get_woocommerce_currency()
      ));
    echo '</script>';

    $pixel = ob_get_clean();

    wp_send_json($pixel);
  }

  /**
   * Triggers InitiateCheckout for checkout page
   */
  public function inject_initiate_checkout_event() {
    if ($this->pixel->check_last_event('InitiateCheckout')) {
      return;
    }

    $product_ids = $this->get_content_ids_from_cart(WC()->cart->get_cart());

    $this->pixel->inject_event(
      'InitiateCheckout',
      array(
        'num_items' => WC()->cart->get_cart_contents_count(),
        'content_ids' => json_encode($product_ids),
        'content_type' => 'product',
        'value' => WC()->cart->total,
        'currency' => get_woocommerce_currency()
      ));
  }

  /**
   * Triggers Purchase for payment transaction complete and for the thank you
   * page in cases of delayed payment.
   */
  public function inject_purchase_event($order_id) {
    if ($this->pixel->check_last_event('Purchase')) {
      return;
    }

    $order = new WC_Order($order_id);
    $content_type = 'product';
    $product_ids = array();
    foreach ($order->get_items() as $item) {
      $product = wc_get_product($item['product_id']);
      $product_ids = array_merge(
        $product_ids,
        WC_Facebookcommerce_Utils::get_fb_content_ids($product));
      if (WC_Facebookcommerce_Utils::is_variable_type($product->get_type())) {
        $content_type = 'product_group';
      }
    }

    $this->pixel->inject_event(
      'Purchase',
      array(
        'content_ids' => json_encode($product_ids),
        'content_type' => $content_type,
        'value' => $order->get_total(),
        'currency' => get_woocommerce_currency()
      ));
  }

  /**
   * Triggers Purchase for thank you page for COD, BACS CHEQUE payment
   * which won't invoke woocommerce_payment_complete.
   */
  public function inject_gateway_purchase_event($order_id) {
    if ($this->pixel->check_last_event('Purchase')) {
      return;
    }

    $order = new WC_Order($order_id);
    $payment = $order->get_payment_method();
    if (!in_array($payment, array('cod', 'cheque', 'bacs'))) {
      return;
    }
    $this->inject_purchase_event($order_id);
  }
}

endif;
