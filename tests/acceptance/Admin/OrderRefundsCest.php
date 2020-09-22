<?php

use SkyVerge\WooCommerce\Facebook\Handlers\Connection;

class OrderRefundsCest {


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Exception
	 */
	public function _before( AcceptanceTester $I ) {

		// always log in
		$I->loginAsAdmin();
	}


	/**
	 * Tests that on the edit screen page of a pending order created via Commerce, the order actions metabox is hidden.
	 *
	 * @param \AcceptanceTester $I
	 * @throws \Exception
	 */
	public function try_maybe_remove_order_metaboxes( AcceptanceTester $I ) {

		$order = $I->haveOrderInDatabase();
		$order->set_status( 'pending' );
		$order->set_created_via( 'instagram' );
		$order->save();

		$I->amOnOrderPage( $order->get_id() );
		$I->dontSeeElement( [ 'css' => '#woocommerce-order-actions' ] );
	}


}
