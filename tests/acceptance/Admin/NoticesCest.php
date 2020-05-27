<?php

/**
 * Tests for standalone admin notices.
 */
class NoticesCest {


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Codeception\Exception\ModuleException
	 */
	public function _before( AcceptanceTester $I ) {

		$I->loginAsAdmin();
	}


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\API::do_post_parse_response_validation()
	 * @see \WC_Facebookcommerce::add_admin_notices()
	 */
	public function try_invalid_connection_notice_with_token( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '12345' );
		$I->haveTransientInDatabase( 'wc_facebook_connection_invalid', time() );

		$I->amOnPluginsPage();

		$I->see( 'Your connection to Facebook is no longer valid', '.notice' );
	}


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\API::do_post_parse_response_validation()
	 * @see \WC_Facebookcommerce::add_admin_notices()
	 */
	public function try_invalid_connection_notice_without_token( AcceptanceTester $I ) {

		$I->haveTransientInDatabase( 'wc_facebook_connection_invalid', time() );

		$I->amOnPluginsPage();

		$I->dontSee( 'Your connection to Facebook is no longer valid', '.notice' );
	}


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\API::do_post_parse_response_validation()
	 * @see \WC_Facebookcommerce::add_admin_notices()
	 */
	public function try_invalid_connection_notice_valid_connection( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '12345' );

		$I->amOnPluginsPage();

		$I->dontSee( 'Your connection to Facebook is no longer valid', '.notice' );
	}


}
