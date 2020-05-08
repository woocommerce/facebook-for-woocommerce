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


	/**
	 * Test that the Include in Facebook sync does not enable sync for a virtual product.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_include_bulk_action_virtual( AcceptanceTester $I ) {

		// disable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product ] );

		// make the product virtual
		$this->product->set_virtual( true );
		$this->product->save();

		$I->amOnProductsPage();

		$I->see( 'Disabled', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Include in Facebook sync does not enable sync for a virtual product' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action]', 'Include in Facebook sync' );
		$I->click( '#doaction' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( 'Heads up! Facebook does not support selling virtual products, so we can\'t include virtual products in your catalog sync. Click here to read more about Facebook\'s policy.', 'div.notice.is-dismissible' );
		$I->see( 'Disabled', 'table.wp-list-table td' );
	}


	/**
	 * Test that the Exclude from Facebook sync disables sync for a standard product.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_exclude_bulk_action_standard( AcceptanceTester $I ) {

		// enable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->product ] );

		$I->amOnProductsPage();

		$I->see( 'Enabled', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Exclude from Facebook sync disables sync for a standard product' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action]', 'Exclude from Facebook sync' );
		$I->click( '#doaction' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( 'Disabled', 'table.wp-list-table td' );
	}


	/**
	 * Test that the Exclude from Facebook sync disables sync when using the secondary dropdown at the bottom of the list table.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_exclude_bulk_action_secondary_dropdown( AcceptanceTester $I ) {

		// enable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->product ] );

		$I->amOnProductsPage();

		$I->see( 'Enabled', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Exclude from Facebook sync disables sync for a standard product when using the secondary dropdown at the bottom of the list table' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action2]', 'Exclude from Facebook sync' );
		$I->click( '#doaction2' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( 'Disabled', 'table.wp-list-table td' );
	}


	/**
	 * Test that the Include in Facebook sync only enables sync for non-virtual variations.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_include_bulk_action_virtual_variation( AcceptanceTester $I ) {

		// save a variable product
		$result = $I->haveVariableProductInDatabase( [
			'attributes' => [
				'color' => [ 'red', 'green' ]
			],
			'variations' => [
				'regular_variation' => [ 'color' => 'red' ],
				'virtual_variation' => [ 'color' => 'green' ],
			],
		] );

		$variable_product = $result['product'];

		/** @var WC_Product_Variation[] $variations */
		$variations = $result['variations'];

		$virtual_variation = $variations['virtual_variation'];
		$virtual_variation->set_virtual( true );
		$virtual_variation->save();

		// disable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $variable_product ] );

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the Include in Facebook sync only enables sync for non-virtual variations' );

		$I->click( "#cb-select-{$variable_product->get_id()}" );
		$I->selectOption( '[name=action]', 'Include in Facebook sync' );
		$I->click( '#doaction' );
		$I->waitForElement( "#cb-select-{$variable_product->get_id()}:not(:checked)" );

		$I->see( 'Enabled', 'table.wp-list-table td' );

		$variable_product = wc_get_product( $variable_product );
		$variation_ids    = $variable_product->get_children();

		foreach ( $variation_ids as $variation_id ) {

			$variation = wc_get_product( $variation_id );

			$meta_criteria = [
				'post_id'    => $variation_id,
				'meta_key'   => '_wc_facebook_sync_enabled',
				'meta_value' => 'yes',
			];

			if ( ! $variation->is_virtual() ) {
				$I->seePostMetaInDatabase( $meta_criteria );
			} else {
				$I->dontSeePostMetaInDatabase( $meta_criteria );
			}
		}
	}


}
