<?php

use SkyVerge\WooCommerce\Facebook\Handlers\Connection;

class ProductSyncSettingCest {


	/** @var \WC_Product|null product object created for the test */
	private $sync_enabled_product;

	/** @var \WC_Product|null product object created for the test */
	private $sync_disabled_product;


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Exception
	 */
	public function _before( AcceptanceTester $I ) {

		/**
		 * Set these in the database so that the product processing hooks are properly set
		 * @see \WC_Facebookcommerce_Integration::__construct()
		 */
		$I->haveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( Connection::OPTION_PAGE_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( Connection::OPTION_COMMERCE_MANAGER_ID, '1234' );
		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );
		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID, '1234' );

		// set these in the database so that the Commerce fields are rendered
		$I->haveOptionInDatabase( Connection::OPTION_PAGE_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( Connection::OPTION_COMMERCE_MANAGER_ID, '1234' );

		// save two generic products
		$this->sync_enabled_product  = $I->haveProductInDatabase();
		$this->sync_disabled_product = $I->haveProductInDatabase();

		// enable/disable sync for the products
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->sync_enabled_product ] );
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->sync_disabled_product ] );

		// always log in
		$I->loginAsAdmin();
	}


	/**
	 * Test that the Commerce fields are present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_fields_are_visible( AcceptanceTester $I ) {

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the Commerce fields are visible' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->see( 'Sell on Instagram', '.form-field' );
	}


	/**
	 * Test that the Commerce fields are not visible for products with Facebook sync disabled.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_fields_are_not_visible( AcceptanceTester $I ) {

		$I->amEditingPostWithId( $this->sync_disabled_product->get_id() );

		$I->wantTo( 'Test that the Commerce fields are not visible' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->dontSee( 'Sell on Instagram', '.form-field' );
	}


	/**
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_fields_are_hidden_when_facebook_sync_is_disabled( AcceptanceTester $I ) {

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the Commerce fields are hidden when Facebook sync is disabled' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->selectOption( '#wc_facebook_sync_mode', 'Do not sync' );

		$I->dontSee( 'Sell on Instagram', '.form-field' );
	}


	/**
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_commerce_enabled_field_is_enabled( AcceptanceTester $I ) {

		$this->sync_enabled_product->set_regular_price( 10 );
		$this->sync_enabled_product->set_manage_stock( true );
		$this->sync_enabled_product->set_stock_quantity( 3 );
		$this->sync_enabled_product->save();

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the Commerce Enabled field is enabled' );

		$this->see_commerce_enabled_field_is_enabled( $I );
		$this->dont_see_product_not_ready_notice( $I );
	}


	/**
	 * @param AcceptanceTester $I tester instance
	 */
	private function see_commerce_enabled_field_is_enabled( AcceptanceTester $I ) {

		$I->expect( 'Commerce Enabled field is enabled (but not necessarily checked)' );

		$I->scrollTo( '.fb_commerce_tab_options', null, -200 );
		$I->click( '.fb_commerce_tab_options' );
		$I->assertFalse( (bool) $I->executeJS( "return jQuery( '#wc_facebook_commerce_enabled' ).prop( 'disabled' )" ) );
	}


	/**
	 * @param AcceptanceTester $I tester instance
	 */
	private function dont_see_product_not_ready_notice( AcceptanceTester $I ) {

		$I->expect( 'The product not ready notice is not shown' );

		$I->dontSeeElement( '#product-not-ready-notice' );
	}


}
