<?php

use SkyVerge\WooCommerce\Facebook\Events\Event;

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


	/** Helper methods ************************************************************************************************/


	private function get_events_tracker( array $user_info = [] ) {

		return new WC_Facebookcommerce_EventsTracker( $user_info );
	}


}
