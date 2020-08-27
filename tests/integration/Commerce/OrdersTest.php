<?php

use SkyVerge\WooCommerce\Facebook\Commerce;
use SkyVerge\WooCommerce\Facebook\Commerce\Orders;

/**
 * Tests the general Commerce orders handler class.
 */
class OrdersTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Test methods **************************************************************************************************/


	/** @see Orders::find_local_order() */
	public function test_find_local_order_found() {

		$order = new \WC_Order();
		$order->save();

		$remote_id = '335211597203390';

		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, $remote_id );
		$order->save_meta_data();

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( \WC_Order::class, $this->get_commerce_orders_handler()->find_local_order( $remote_id ) );
		$this->assertEquals( $order->get_id(), $this->get_commerce_orders_handler()->find_local_order( $remote_id )->get_id() );
	}


	/** @see Orders::find_local_order() */
	public function test_find_local_order_not_found() {

		$order = new \WC_Order();
		$order->save();

		$remote_id           = '435211597203390';
		$different_remote_id = '335211597203390';

		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, $different_remote_id );
		$order->save_meta_data();

		$this->assertNull( $this->get_commerce_orders_handler()->find_local_order( $remote_id ) );
	}


	/** Helper methods **************************************************************************************************/


	/**
	 * Gets the Commerce orders handler instance.
	 *
	 * @return Orders
	 */
	private function get_commerce_orders_handler() {

		$commerce_handler = new Commerce();

		return $commerce_handler->get_orders_handler();
	}


}
