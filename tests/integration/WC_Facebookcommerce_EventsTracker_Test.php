<?php

/**
 * Tests the class that fires server side events creation
 */
class WC_Facebookcommerce_EventsTracker_Test extends \Codeception\TestCase\WPTestCase {
  /** @var \IntegrationTester */
	protected $tester;

  /**
	 * Runs before each test.
	 */
	protected function _before() {
    WC_Facebookcommerce_ServerEventSender::get_instance()->untrack_events();
    WC_Facebookcommerce_Pixel::set_use_s2s(true);
	}

  public function test_view_content_event_creation() {
    //Creates a fake product and setups the global $post
    $product = $this->get_product();
    $raw_post = new stdClass();
    $raw_post->ID = $product -> get_id();
    global $post;
    $post = new WP_Post( $raw_post );
    //This object setups the actions and injects the events
    $event_tracker = new WC_Facebookcommerce_EventsTracker( array() );
    //When this action fires, a view content event is added
    do_action('woocommerce_after_single_product');
    $this->assertEquals( WC_Facebookcommerce_ServerEventSender::get_instance()->get_num_tracked_events(), 1 );
    //Checking that the events attributes are equal to the product attributes
    $server_event = WC_Facebookcommerce_ServerEventSender::get_instance()->get_tracked_events()[0];
    $custom_data = $server_event->getCustomData();
    $this->assertEquals( $server_event->getEventName(), 'ViewContent' );
    $this->assertEquals( $custom_data->getContentName(), $product->get_name() );
    $this->assertEquals( $custom_data->getValue(), $product->get_price() );
    $this->assertEquals( $custom_data->getContentType(), 'product');
  }

  public function test_search_event_creation(){
    $event_tracker = new WC_Facebookcommerce_EventsTracker( array() );
    wc_get_products( array( 's' => 'dinosaur' ) );
    // TODO
    // Cannot simulate the event creation because:
    // The code uses get_search_query() and I cannot configure it correctly
  }

  public function test_view_category_event_creation(){
    //Creates a product
    $product =  $this->get_product();
    //Creates a category
    $category_id = $this->get_category();
    //Associates product and category
    wp_set_object_terms($product->get_id(), $category_id, 'product_cat');
    //Arguments for search query
    $args = array(
      'post_type'             => 'product',
      'post_status'           => 'publish',
      'ignore_sticky_posts'   => 1,
      'posts_per_page'        => '12',
      'tax_query'             => array(
          array(
              'taxonomy'      => 'product_cat',
              'field' => 'term_id', //This is optional, as it defaults to 'term_id'
              'terms'         => $category_id,
              'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
          ),
          array(
              'taxonomy'      => 'product_visibility',
              'field'         => 'slug',
              'terms'         => 'exclude-from-catalog', // Possibly 'exclude-from-search' too
              'operator'      => 'NOT IN'
          )
      )
    );
    //Creates a new wordpress query
    $products_in_category = new WP_Query($args);
    global $wp_query;
    $wp_query = $products_in_category;
    //Setups the action and functions to be fired
    $event_tracker = new WC_Facebookcommerce_EventsTracker( array() );
    //Fires the action that triggers ViewCategory event creation
    do_action('woocommerce_after_shop_loop');
    $this->assertEquals( WC_Facebookcommerce_ServerEventSender::get_instance()->get_num_tracked_events(), 1 );
    //Checking that the events attributes are equal to the product attributes
    $server_event = WC_Facebookcommerce_ServerEventSender::get_instance()->get_tracked_events()[0];
    $this->assertEquals( $server_event->getEventName(), 'ViewCategory' );
    $custom_data = $server_event->getCustomData();
    $this->assertEquals( $custom_data->getContentType(), 'product' );
    $this->assertEquals( $custom_data->getContentIds(), json_encode([ 'wc_post_id_'.strval($product->get_id()) ]) );
  }

  public function test_add_to_cart_event_creation(){
    //Creates a product
    $product =  $this->get_product();
    WC()->cart = new WC_Cart();
    WC()->cart->add_to_cart( $product->get_id() );
    //This object setups the actions and injects the events
    $event_tracker = new WC_Facebookcommerce_EventsTracker( array() );
    //Fires the action that adds to cart
    do_action( 'woocommerce_add_to_cart', '', $product->get_id(), 1, 0 );
    $this->assertEquals( WC_Facebookcommerce_ServerEventSender::get_instance()->get_num_tracked_events(), 1 );
    $server_event = WC_Facebookcommerce_ServerEventSender::get_instance()->get_tracked_events()[0];
    //Asserting correct fields inside the event
    $this->assertEquals( $server_event->getEventName(), 'AddToCart' );
    $custom_data = $server_event->getCustomData();
    $this->assertEquals( $product->get_price(), $custom_data->getValue() );
    $this->assertEquals( $custom_data->getContentType(), 'product' );
    $this->assertEquals( $custom_data->getContentIds(), json_encode([ 'wc_post_id_'.strval($product->get_id()) ]) );
    $this->assertIsArray( $custom_data->getContents() );
    $this->assertEquals( count($custom_data->getContents()), 1 );
  }

  public function test_initiate_checkout_event_creation(){
    //Creates a product
    $product =  $this->get_product();
    WC()->cart = new WC_Cart();
    WC()->cart->add_to_cart( $product->get_id() );
    //This object setups the actions and injects the events
    $event_tracker = new WC_Facebookcommerce_EventsTracker( array() );
    //Fires the action that initiates checkout
    do_action( 'woocommerce_after_checkout_form' );
    //Asserting event creation
    $this->assertEquals( WC_Facebookcommerce_ServerEventSender::get_instance()->get_num_tracked_events(), 1 );
    //Asserting correct fields inside the event
    $server_event = WC_Facebookcommerce_ServerEventSender::get_instance()->get_tracked_events()[0];
    $this->assertEquals( $server_event->getEventName(), 'InitiateCheckout' );
    $custom_data = $server_event->getCustomData();
    $this->assertEquals( $product->get_price(), $custom_data->getValue() );
    $this->assertEquals( $custom_data->getContentType(), 'product' );
    $this->assertEquals( $custom_data->getContentIds(), json_encode([ 'wc_post_id_'.strval($product->get_id()) ]) );
    $this->assertEquals( $custom_data->getNumItems(), 1 );
  }

  public function test_purchase_event_creation(){
    //Creates a product
    $product =  $this->get_product();
    //Creates an order
    $order = $this->get_order();
    //Associates the order with the product
    $this->associate_order_and_product( $order, $product );
    $event_tracker = new WC_Facebookcommerce_EventsTracker( array() );
    $event_tracker->inject_purchase_event( $order->get_id() );
    //Asserting event creation
    $this->assertEquals( WC_Facebookcommerce_ServerEventSender::get_instance()->get_num_tracked_events(), 1 );
    //Asserting correct fields inside the event
    $server_event = WC_Facebookcommerce_ServerEventSender::get_instance()->get_tracked_events()[0];
    $this->assertEquals( $server_event->getEventName(), 'Purchase' );
    $custom_data = $server_event->getCustomData();
    $this->assertEquals( $product->get_price(), $custom_data->getValue() );
    $this->assertEquals( $custom_data->getContentType(), 'product' );
    $this->assertEquals( $custom_data->getContentIds(), json_encode([ 'wc_post_id_'.strval($product->get_id()) ]) );
    $this->assertIsArray( $custom_data->getContents() );
    $this->assertEquals( count($custom_data->getContents()), 1 );
  }

  public function test_subscribe_event_creation(){
    // TODO
    // Cannot simulate the creation condition because:
    // The function wcs_get_subscriptions_for_order must be defined
    // Some associations between an order and a subscription must be created
  }

  /**
	 * Gets a new product object with descriptions.
	 *
	 * @return \WC_Product
	 */
	private function get_product() {

		$product = $this->tester->get_product();

		$product->set_description( 'Standard Description.' );
		$product->set_short_description( 'Short Description.' );
    $product->set_price(90);
    $product->set_regular_price(90);
    $product->set_name('Name');
		$product->save();

		return  $product;
	}

  /**
	 * Gets id of a new product category
	 *
	 * @return int category id
	 */
  private function get_category(){
    return $this->tester->create_product_category();
  }

  /**
	 * Gets a new order object
	 *
	 * @return \WC_Order
	 */
  private function get_order(){
    return $this->tester->get_order();
  }

  /**
	 * Associates an order with some items of a product
	 *
   * @param order WC_Order
   * @param product WC_Product
   * @param num_items int
	 */
  private function associate_order_and_product( $order, $product, $num_items = 1 ) {
    $this->tester->associate_order_and_product( $order, $product, $num_items );
  }
}
