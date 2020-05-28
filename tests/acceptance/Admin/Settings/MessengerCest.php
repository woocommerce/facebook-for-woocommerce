<?php

class MessengerCest {


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I
	 * @throws \Codeception\Exception\ModuleException
	 */
	public function _before( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '1234' );

		$I->loginAsAdmin();

		$I->amOnAdminPage('admin.php?page=wc-facebook&tab=messenger' );
	}


	/**
	 * Test that the Messenger fields are present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_messenger_fields_present( AcceptanceTester $I ) {

		$I->wantTo( 'Test that the Messenger fields are present' );

		$I->see( 'Enable Messenger', 'th.titledesc' );
		$I->seeElement( 'input[type=checkbox]#' . \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER );

		$I->see( 'Language', 'th.titledesc' );
		$I->seeElement( 'select#' . \WC_Facebookcommerce_Integration::SETTING_MESSENGER_LOCALE );

		$I->see( 'Greeting', 'th.titledesc' );
		$I->seeElement( 'textarea#' . \WC_Facebookcommerce_Integration::SETTING_MESSENGER_GREETING );

		$I->see( 'Colors', 'th.titledesc' );
		$I->seeElement( 'input[type=text].colorpick.messenger-field#' . \WC_Facebookcommerce_Integration::SETTING_MESSENGER_COLOR_HEX );
	}


	/**
	 * Test that the Messenger fields are saved correctly.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws Exception
	 */
	public function try_messenger_fields_saved( AcceptanceTester $I ) {

		$I->wantTo( 'Test that the Messenger fields are saved correctly' );

		$form = [
			\WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER    => true,
			\WC_Facebookcommerce_Integration::SETTING_MESSENGER_LOCALE    =>'ja_JP',
			\WC_Facebookcommerce_Integration::SETTING_MESSENGER_GREETING  => 'Hello darkness my old friend',
			\WC_Facebookcommerce_Integration::SETTING_MESSENGER_COLOR_HEX => '#000000',
		];

		$I->submitForm( '#mainform', $form, 'save_messenger_settings' );
		$I->waitForText( 'Your settings have been saved.' );

		$I->seeInFormFields( '#mainform', $form );
	}


}
