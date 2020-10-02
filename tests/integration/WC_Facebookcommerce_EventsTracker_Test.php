<?php

use SkyVerge\WooCommerce\Facebook\Events\Event;

use SkyVerge\WooCommerce\Facebook\Events\AAMSettings;

/**
 * Tests the WC_Facebookcommerce_EventsTracker class.
 */
class WC_Facebookcommerce_EventsTracker_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Test methods **************************************************************************************************/


	/** @see WC_Facebookcommerce_EventsTracker::get_search_event() */
	public function test_get_search_event() {
		global $wp_query;

		$product = $this->tester->get_product();

		$wp_query->query_vars = [
			's' => 'term',
		];

		$wp_query->posts = [
			(object) [ 'ID' => $product->get_id() ],
		];

		$tracker = $this->get_events_tracker();
		$event   = $this->tester->getMethod( $tracker, 'get_search_event' )->invoke( $tracker );

		$this->assertInstanceOf( Event::class, $event );
		$this->assertEquals( 'Search', $event->get_name() );
		$this->assertEquals( 'term', $event->get_custom_data()['search_string'] );
		$this->assertStringContainsString( $product->get_id(), $event->get_custom_data()['content_ids'] );
	}


	/** @see WC_Facebookcommerce_EventsTracker::get_search_event() */
	public function test_get_search_event_returns_same_instance() {
		global $wp_query;

		$wp_query->posts = [];

		$tracker = $this->get_events_tracker();
		$method  = $this->tester->getMethod( $tracker, 'get_search_event' );
		$event   = $method->invoke( $tracker );

		$this->assertSame( $event, $method->invoke( $tracker ) );
	}

	public function test_purchase_event(){
		$product = $this->create_product();
		$order = $this->create_order();
		$order->add_product( $product, 1 );
		$order->set_total($product->get_price());
		$order->save();
		$tracker = $this->get_events_tracker();
		$tracker->inject_purchase_event( $order->get_id() );
		$this->assertEquals(1, count($tracker->get_tracked_events()));
		$event = $tracker->get_tracked_events()[0];
		$this->assertEquals('Purchase', $event->get_name());
		$user_data = $event->get_user_data();
		$custom_data = $event->get_custom_data();
		$this->assertArrayHasKey('em', $user_data);
		$this->assertArrayHasKey('ln', $user_data);
		$this->assertArrayHasKey('fn', $user_data);
		$this->assertArrayHasKey('ph', $user_data);
		$this->assertArrayHasKey('ct', $user_data);
		$this->assertArrayHasKey('st', $user_data);
		$this->assertArrayHasKey('country', $user_data);
		$this->assertArrayHasKey('zp', $user_data);
		$this->assertEquals($custom_data['content_name'], '["Sample name"]');
		$this->assertEquals($custom_data['content_type'], 'product');
		$this->assertArrayHasKey('currency', $custom_data);
		$this->assertEquals($custom_data['value'], '10.00');
		$this->assertEquals($custom_data['content_ids'], '["wc_post_id_'.$product->get_id().'"]');
		$this->assertEquals($custom_data['contents'], '[{"id":"wc_post_id_'.$product->get_id().'","quantity":1}]');
	}


	/** Helper methods ************************************************************************************************/


	private function get_events_tracker( array $user_info = [] ) {
		$aam_settings = (new AAMSettings())
											->set_enable_automatic_matching(true)
											->set_enabled_automatic_matching_fields(
												['em', 'fn', 'ln', 'ph', 'ct', 'st', 'zp', 'country']
											);
		return new WC_Facebookcommerce_EventsTracker( $user_info, $aam_settings );
	}

	private function create_product(){
		$product = $this->tester->get_product();
		$product->set_name('Sample name');
		$product->set_description( 'Sample description' );
		$product->set_short_description( 'Sample description' );
		$product->set_price('10.00');
		$product->set_regular_price('10.00');
		$product->save();
		return $product;
	}

	private function create_order(){
		$order = wc_create_order();
		$order->set_billing_first_name('Homero');
		$order->set_billing_last_name('Simpson');
		$order->set_billing_email('homero@simpson.com');
		$order->set_billing_postcode('12345');
		$order->set_billing_state('Washington');
		$order->set_billing_country('US');
		$order->set_billing_phone('(650) 123 1234');
		$order->set_billing_city('Springfield');
		$order_placed_meta = '_wc_' . facebook_for_woocommerce()->get_id() . '_order_placed';
		$order->update_meta_data( $order_placed_meta, 'yes' );
		$order->save_meta_data();
		$order->save();
		return $order;
	}
}
