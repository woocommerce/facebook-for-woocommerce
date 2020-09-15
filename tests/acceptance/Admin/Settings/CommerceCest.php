<?php

class InstagramCheckoutCest {


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function _before( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '1235' );
		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_PAGE_ACCESS_TOKEN, '1235' );

		$I->loginAsAdmin();
	}


	/**
	 * Test that the Instagram Checkout connection message is shown.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_store_connect_to_instagram_message( AcceptanceTester $I ) {

		$I->amOnAdminPage('admin.php?page=wc-facebook&tab=commerce' );

		$I->wantTo( 'Test that the Instagram Checkout connection message is shown' );

		$I->see( 'Your store is connected to Instagram.', '.forminp' );
	}

	/**
	 * Test that the Instagram Checkout connection message is shown if the store is not connected.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_store_not_connected_to_instagram_message( AcceptanceTester $I ) {

		$I->dontHaveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_PAGE_ACCESS_TOKEN );

		$I->amOnAdminPage('admin.php?page=wc-facebook&tab=commerce' );

		$I->wantTo( 'Test that the Instagram Checkout connection message is shown when the store is not connected' );

		$I->see( 'Your store is not connected to Instagram.', '.forminp' );
	}


	/**
	 * Test that the Facebook connection message is shown if the plugin is not connected.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_connect_message( AcceptanceTester $I ) {

		$I->dontHaveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN );

		$I->amOnAdminPage('admin.php?page=wc-facebook&tab=commerce' );

		$I->see( 'Please connect to Facebook to enable Instagram Checkout', '.notice' );
	}


	/**
	 * Test that the US-only limitation message is shown if the default country is not US.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_us_only_limitation_message( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( 'woocommerce_default_country', 'UK' );

		$I->amOnAdminPage('admin.php?page=wc-facebook&tab=commerce' );

		$I->see( 'Instagram Checkout is only available to merchants located in the United States.', '.notice' );
	}


}
