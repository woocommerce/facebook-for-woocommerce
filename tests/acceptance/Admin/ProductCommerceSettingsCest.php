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
	 * @dataProvider provider_missing_google_product_category_alert_is_shown
	 */
	public function try_missing_google_product_category_alert_is_shown_on_simple_product( AcceptanceTester $I, Codeception\Example $example ) {

		$this->sync_enabled_product->set_regular_price( 10 );
		$this->sync_enabled_product->set_manage_stock( true );
		$this->sync_enabled_product->set_stock_quantity( 3 );
		$this->sync_enabled_product->save();

		$this->see_missing_google_product_category_alert( $I, $this->sync_enabled_product, $example['categories'] );
	}


	/**
	 * Test that the missing Google product category alert is shown when the given categories are selected.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @param WC_Product $product a product to edit
	 * @param array $categories list of categories to select
	 */
	private function see_missing_google_product_category_alert( AcceptanceTester $I, \WC_Product $product, array $categories ) {

		$I->amEditingPostWithId( $product->get_id() );

		$I->wantTo( "Test that an alert is shown if the user doesn't select a Google product sub-category" );

		$I->click( '.fb_commerce_tab_options' );

		// clear the Google product category
		$I->executeJS( "jQuery( '.wc-facebook-google-product-category-field:nth-child( 1 ) .wc-facebook-google-product-category-select' ).val( null ).trigger( 'change' )" );

		// set the Google product category for the test
		foreach ( $categories as $index => $category_id ) {

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


	/**
	 * @dataProvider provider_missing_google_product_category_alert_is_shown
	 */
	public function try_missing_google_product_category_alert_is_shown_on_variable_product( AcceptanceTester $I, Codeception\Example $example ) {

		$product_objects = $I->haveVariableProductInDatabase();

		/** @var \WC_Product_Variable */
		$variable_product = $product_objects['product'];

		/** @var \WC_Product_Variation */
		$product_variation = $product_objects['variations']['product_variation'];

		$variable_product->set_manage_stock( true );
		$variable_product->save();

		$product_variation->set_regular_price( 7 );
		$product_variation->save();

		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $variable_product ] );

		$this->see_missing_google_product_category_alert( $I, $variable_product, $example['categories'] );
	}


}
