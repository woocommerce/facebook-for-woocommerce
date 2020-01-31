<?php

class IntegrationSettingsCest {


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Exception
	 */
	public function _before( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PAGE_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );

		$I->haveFacebookForWooCommerceSettingsInDatabase( [
			\WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID => '1234',
		] );

		// always log in
		$I->loginAsAdmin();
	}


	/**
	 * Test that the Connection section is present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_connection_section_present( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Connection section is present' );

		$I->see( 'Connection', 'h3.wc-settings-sub-title' );
	}


	/**
	 * Test that the Product sync section is present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_product_sync_section_present( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Product sync section is present' );

		$I->see( 'Product sync', 'h3.wc-settings-sub-title' );
	}


	/**
	 * Test that the Messenger section is present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_messenger_section_present( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Messenger sync section is present' );

		$I->see( 'Messenger', 'h3.wc-settings-sub-title' );
	}


}
