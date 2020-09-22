<?php

use SkyVerge\WooCommerce\Facebook\Handlers\Connection;

class ProductCommerceSettingsCest {


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

		$I->click( '.fb_commerce_tab_options' );

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

		$I->click( '.fb_commerce_tab_options' );

		$I->dontSee( 'Sell on Instagram', '.form-field' );
	}


	/**
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_fields_are_hidden_when_facebook_sync_is_disabled( AcceptanceTester $I ) {

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the Commerce fields are hidden when Facebook sync is disabled' );

		$I->click( '.fb_commerce_tab_options' );

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


	/**
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_commerce_enabled_field_is_disabled_if_price_is_not_set( AcceptanceTester $I ) {

		$this->sync_enabled_product->set_regular_price( null );
		$this->sync_enabled_product->set_manage_stock( true );
		$this->sync_enabled_product->set_stock_quantity( 3 );
		$this->sync_enabled_product->save();

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the Commerce Enabled field is disabled when no regular price is set' );

		$this->see_commerce_enabled_field_is_disabled( $I );

		$I->expect( 'The product not ready notice is shown' );

		$I->see( 'This product does not meet the requirements to sell on Instagram.', '#product-not-ready-notice' );

		$I->amGoingTo( 'Set the regular price to $10' );

		$I->click( '.general_options' );
		$I->fillField( '#_regular_price', 10 );

		$this->see_commerce_enabled_field_is_enabled( $I );
		$this->dont_see_product_not_ready_notice( $I );
	}


	/**
	 * @param AcceptanceTester $I tester instance
	 */
	private function see_commerce_enabled_field_is_disabled( AcceptanceTester $I ) {

		$I->expect( 'Commerce Enabled field is not checked and is disabled' );

		$I->click( '.fb_commerce_tab_options' );
		$I->dontSeeCheckboxIsChecked( '#wc_facebook_commerce_enabled' );
		$I->assertTrue( (bool) $I->executeJS( "return jQuery( '#wc_facebook_commerce_enabled' ).prop( 'disabled' )" ) );
	}


	/**
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_commerce_enabled_field_is_disabled_if_stock_management_is_disabled( AcceptanceTester $I ) {

		$this->sync_enabled_product->set_regular_price( 10 );
		$this->sync_enabled_product->set_manage_stock( false );
		$this->sync_enabled_product->set_stock_quantity( null );
		$this->sync_enabled_product->save();

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the Commerce Enabled field is disabled when Stock Management is disabled' );

		$this->see_commerce_enabled_field_is_disabled( $I );

		$I->expect( 'The product not ready notice is shown' );

		$I->see( 'This product does not meet the requirements to sell on Instagram.', '#product-not-ready-notice' );

		$I->amGoingTo( 'Enable Stock Management' );

		$I->click( '.inventory_options' );
		$I->checkOption( '#_manage_stock' );

		$this->see_commerce_enabled_field_is_enabled( $I );
		$this->dont_see_product_not_ready_notice( $I );
	}


	/**
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_commerce_enabled_field_is_disabled_if_no_variations_have_sync_enabled( AcceptanceTester $I ) {

		$product_objects = $I->haveVariableProductInDatabase();

		/** @var \WC_Product_Variable */
		$variable_product = $product_objects['product'];

		/** @var \WC_Product_Variation */
		$product_variation = $product_objects['variations']['product_variation'];

		$variable_product->set_manage_stock( true );
		$variable_product->save();

		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $variable_product ] );

		$I->amEditingPostWithId( $variable_product->get_id() );

		$I->wantTo( 'Test that the Commerce Enabled field is disabled if no variations have sync enabled' );

		$this->see_commerce_enabled_field_is_disabled( $I );

		$I->expect( 'The product not ready notice is shown' );

		$I->see( 'To sell this product on Instagram, at least one variation must be synced to Facebook.', '#variable-product-not-ready-notice' );

		$I->amGoingTo( 'Enable Facebook sync for a variation' );

		$I->click( '.variations_tab' );
		$index = $I->openVariationMetabox( $product_variation );

		$I->waitForElementVisible( "#variable_facebook_sync_mode{$index}" );
		$I->selectOption( "#variable_facebook_sync_mode{$index}", 'sync_and_show' );

		$this->see_commerce_enabled_field_is_enabled( $I );

		$I->expect( 'The product not ready notice is not shown' );

		$I->dontSeeElement( '#variable-product-not-ready-notice' );
	}


	/**
	 * @dataProvider provider_missing_google_product_category_alert_is_shown
	 */
	public function try_missing_google_product_category_alert_is_shown( AcceptanceTester $I, Codeception\Example $example ) {

		$this->sync_enabled_product->set_regular_price( 10 );
		$this->sync_enabled_product->set_manage_stock( true );
		$this->sync_enabled_product->set_stock_quantity( 3 );
		$this->sync_enabled_product->save();

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( "Test that an alert is shown if the user doesn't select a Google product sub-category" );

		$I->click( '.fb_commerce_tab_options' );

		// clear the Google product category
		$I->executeJS( "jQuery( '.wc-facebook-google-product-category-field:nth-child( 1 ) .wc-facebook-google-product-category-select' ).val( null ).trigger( 'change' )" );

		// set the Google product category for the test
		foreach ( $example['categories'] as $index => $category_id ) {

			$element_position = $index + 1;

			$I->executeJS( "jQuery( '.wc-facebook-google-product-category-field:nth-child( {$element_position} ) .wc-facebook-google-product-category-select' ).val( {$category_id} ).trigger( 'change' )" );
		}

		$I->scrollTo( 'input#publish', 0, -200 );
		$I->click( 'input#publish' );

		$I->seeInPopup( 'Please enter a Google product category' );
		$I->acceptPopup();
	}


	/** @see try_missing_google_product_category_alert_is_shown */
	public function provider_missing_google_product_category_alert_is_shown() {

		return [
			'no category selected'     => [ 'categories' => [] ],
			'no sub-category selected' => [ 'categories' => [ 1 ] ],
		];
	}


}
