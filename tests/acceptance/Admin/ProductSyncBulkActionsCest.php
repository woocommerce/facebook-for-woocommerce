<?php

class ProductSyncBulkActionsCest {


	// product objects created for the tests */
	/** @var \WC_Product */
	private $product;


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Exception
	 */
	public function _before( AcceptanceTester $I ) {

		// save a generic product
		$this->product = $I->haveProductInDatabase();

		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );

		// always log in
		$I->loginAsAdmin();
	}


	/**
	 * Test that the Include in Facebook sync enables sync for a standard product.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_include_bulk_action_standard( AcceptanceTester $I ) {

		// disable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product ] );

		$I->amOnProductsPage();

		$I->see( 'Disabled', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Include in Facebook sync enables sync for a standard product' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action]', 'Include in Facebook sync' );
		$I->click( '#doaction' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( 'Enabled', 'table.wp-list-table td' );
	}


	/**
	 * Test that the Include in Facebook sync enables sync when using the secondary dropdown at the bottom of the list table.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_include_bulk_action_secondary_dropdown( AcceptanceTester $I ) {

		// disable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product ] );

		$I->amOnProductsPage();

		$I->see( 'Disabled', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Include in Facebook sync enables sync for a standard product when using the secondary dropdown at the bottom of the list table' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action2]', 'Include in Facebook sync' );
		$I->click( '#doaction2' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( 'Enabled', 'table.wp-list-table td' );
	}


	/**
	 * Test that the Include in Facebook sync does not enable sync for a product in an excluded category.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_include_bulk_action_excluded_category( AcceptanceTester $I ) {

		// disable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product ] );

		// have an excluded category
		list( $excluded_category_id, $excluded_category_taxonomy_id ) = $I->haveTermInDatabase( 'Excluded category', 'product_cat' );
		$I->haveFacebookForWooCommerceSettingsInDatabase( [
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS => [ $excluded_category_id ]
		] );

		// add the product to the excluded category
		wp_add_object_terms( $this->product->get_id(), [ $excluded_category_id ], 'product_cat' );

		$I->amOnProductsPage();

		$I->see( 'Excluded category', 'table.wp-list-table td' );
		$I->see( 'Disabled', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Include in Facebook sync does not enable sync for a product in an excluded category' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action]', 'Include in Facebook sync' );
		$I->click( '#doaction' );

		$I->waitForElementVisible( '#wc-backbone-modal-dialog' );
		$I->see( 'One or more of the selected products belongs to a category or tag that is excluded from the Facebook catalog sync. To sync these products to Facebook, please remove the category or tag exclusion from the plugin settings.', '#wc-backbone-modal-dialog' );
		$I->see( 'Go to Settings', '#wc-backbone-modal-dialog' );
		$I->see( 'Cancel', '#wc-backbone-modal-dialog' );

		$I->click( 'Cancel', '#wc-backbone-modal-dialog' );

		$I->waitForElementNotVisible( '#wc-backbone-modal-dialog' );
		$I->see( 'Disabled', 'table.wp-list-table td' );
	}


	/**
	 * Test that the Include in Facebook sync does not enable sync for a product with an excluded tag.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_include_bulk_action_excluded_tag( AcceptanceTester $I ) {

		// disable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product ] );

		// have an excluded tag
		list( $excluded_tag_id, $excluded_tag_taxonomy_id ) = $I->haveTermInDatabase( 'Excluded tag', 'product_tag' );
		$I->haveFacebookForWooCommerceSettingsInDatabase( [
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS => [ $excluded_tag_id ]
		] );

		// add the excluded tag to the product
		wp_add_object_terms( $this->product->get_id(), [ $excluded_tag_id ], 'product_tag' );

		$I->amOnProductsPage();

		$I->see( 'Excluded tag', 'table.wp-list-table td' );
		$I->see( 'Disabled', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Include in Facebook sync does not enable sync for a product with an excluded tag' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action]', 'Include in Facebook sync' );
		$I->click( '#doaction' );

		$I->waitForElementVisible( '#wc-backbone-modal-dialog' );
		$I->see( 'One or more of the selected products belongs to a category or tag that is excluded from the Facebook catalog sync. To sync these products to Facebook, please remove the category or tag exclusion from the plugin settings.', '#wc-backbone-modal-dialog' );
		$I->see( 'Go to Settings', '#wc-backbone-modal-dialog' );
		$I->see( 'Cancel', '#wc-backbone-modal-dialog' );

		$I->click( 'Cancel', '#wc-backbone-modal-dialog' );

		$I->waitForElementNotVisible( '#wc-backbone-modal-dialog' );
		$I->see( 'Disabled', 'table.wp-list-table td' );
	}


}
