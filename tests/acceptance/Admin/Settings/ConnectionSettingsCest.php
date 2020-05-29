<?php

use SkyVerge\WooCommerce\Facebook\Handlers\Connection;

class ConnectionSettingsCest {


	public function _before( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN, '1234' );

		$I->loginAsAdmin();
	}


	/**
	 * Test that the Get Started button is present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_get_started_button_present( AcceptanceTester $I ) {

		$I->wantTo( 'Test that the Get Started button is present' );

		$I->dontHaveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN );

		$I->amOnAdminPage('admin.php?page=wc-facebook' );

		$I->seeConnectButton( 'Get Started', 'a.button.button-primary' );
	}


	/**
	 * Test that the connected assets are present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_connected_assets_present( AcceptanceTester $I ) {

		$I->wantTo( 'Test that the assets are shown when connected' );

		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PIXEL_ID, '5678' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '8765' );
		$I->haveOptionInDatabase( Connection::OPTION_BUSINESS_MANAGER_ID, '4321' );

		$I->amOnAdminPage('admin.php?page=wc-facebook' );

		$I->canSee( 'Create Ad', '.button' );
		$I->canSee( 'Manage Connection', '.button' );
		$I->canSee( 'Uninstall', 'a.uninstall' );

		$I->canSee( '1234', '.wc-facebook-connected-page code' );
		$I->canSee( '5678', '.wc-facebook-connected-pixel code' );
		$I->canSee( '8765', '.wc-facebook-connected-catalog a' );
		$I->canSee( '4321', '.wc-facebook-connected-business-manager code' );
	}


	/**
	 * Test that the Debug fields are present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_debug_fields_present( AcceptanceTester $I ) {

		$I->amOnAdminPage('admin.php?page=wc-facebook' );

		$I->wantTo( 'Test that the Debug fields are present' );

		$I->see( 'Enable debug mode', 'th.titledesc' );
		$I->seeElement( 'input[type=checkbox]#' . \WC_Facebookcommerce_Integration::SETTING_ENABLE_DEBUG_MODE );
	}


	/**
	 * Test that the Debug fields are saved correctly.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws Exception
	 */
	public function try_debug_fields_saved( AcceptanceTester $I ) {

		$I->amOnAdminPage('admin.php?page=wc-facebook' );

		$I->wantTo( 'Test that the Debug fields are saved correctly' );

		$form = [
			\WC_Facebookcommerce_Integration::SETTING_ENABLE_DEBUG_MODE => true,
		];

		$I->submitForm( '#mainform', $form, 'save_connection_settings' );
		$I->waitForText( 'Your settings have been saved.', 15 );

		$I->seeInFormFields( '#mainform', $form );
	}


}
