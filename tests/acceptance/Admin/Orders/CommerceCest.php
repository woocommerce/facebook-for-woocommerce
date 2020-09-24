<?php

use SkyVerge\WooCommerce\Facebook\Commerce\Orders;
use SkyVerge\WooCommerce\Facebook\Handlers\Connection;

class CommerceCest {


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Exception
	 */
	public function _before( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );

		$I->haveOptionInDatabase( Connection::OPTION_PAGE_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( Connection::OPTION_COMMERCE_MANAGER_ID, '1234' );

		// always log in
		$I->loginAsAdmin();
	}


}
