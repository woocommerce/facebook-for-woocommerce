<?php

use SkyVerge\WooCommerce\Facebook\Handlers\Connection;

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

		$I->dontHavePostInDatabase( [ 'post_type' => 'product' ], true );

		// save a generic product
		$this->product = $I->haveProductInDatabase();

		$I->haveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN, '1234' );
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

		$I->see( 'Do not sync', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Include in Facebook sync enables sync for a standard product' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action]', 'Include in Facebook sync' );
		$I->click( '#doaction' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );
		$I->dontSee( 'Do not sync', 'table.wp-list-table td' );

		$I->dontSee( 'If this product was previously visible in Facebook', '.notice' );
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

		$I->see( 'Do not sync', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Include in Facebook sync enables sync for a standard product when using the secondary dropdown at the bottom of the list table' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action2]', 'Include in Facebook sync' );
		$I->click( '#doaction2' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );
		$I->dontSee( 'Do not sync', 'table.wp-list-table td' );
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

		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS, [ $excluded_category_id ] );

		// add the product to the excluded category
		wp_add_object_terms( $this->product->get_id(), [ $excluded_category_id ], 'product_cat' );

		$I->amOnProductsPage();

		$I->see( 'Excluded category', 'table.wp-list-table td' );
		$I->see( 'Do not sync', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );

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
		$I->see( 'Do not sync', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );
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

		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS, [ $excluded_tag_id ] );

		// add the excluded tag to the product
		wp_add_object_terms( $this->product->get_id(), [ $excluded_tag_id ], 'product_tag' );

		$I->amOnProductsPage();

		$I->see( 'Excluded tag', 'table.wp-list-table td' );
		$I->see( 'Do not sync', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );

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
		$I->see( 'Do not sync', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );
	}


	/**
	 * Test that the Include in Facebook sync enables sync for a virtual product, but hides it.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_include_bulk_action_virtual_product( AcceptanceTester $I ) {

		// disable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product ] );

		// make the product virtual
		$this->product->set_virtual( true );
		$this->product->save();

		$I->amOnProductsPage();

		$I->see( 'Do not sync', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Include in Facebook sync enables sync for a virtual product, but hides it' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action]', 'Include in Facebook sync' );
		$I->click( '#doaction' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( '1 product or some of its variations could not be updated to show in the Facebook catalog', 'div.notice.is-dismissible' );

		$I->see( 'Sync and hide', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Do not sync', 'table.wp-list-table td' );
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

		$I->see( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );
		$I->dontSee( 'Do not sync', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Exclude from Facebook sync disables sync for a standard product' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action]', 'Exclude from Facebook sync' );
		$I->click( '#doaction' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( 'Do not sync', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );

		$I->waitForText( 'If this product was previously visible in Facebook' );
		$I->click( '.notice .js-wc-plugin-framework-notice-dismiss' );

		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->product ] );

		$I->amOnProductsPage();

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action]', 'Exclude from Facebook sync' );
		$I->click( '#doaction' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->dontSee( 'If this product was previously visible in Facebook', '.notice' );
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

		$I->see( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );
		$I->dontSee( 'Do not sync', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Exclude from Facebook sync disables sync for a standard product when using the secondary dropdown at the bottom of the list table' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action2]', 'Exclude from Facebook sync' );
		$I->click( '#doaction2' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( 'Do not sync', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and show', 'table.wp-list-table td' );
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );
	}


	/**
	 * Test that the Include in Facebook sync enables sync for non-virtual variations, but hides them.
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

		$I->wantTo( 'Test that the Include in Facebook sync enables sync for non-virtual variations, but hides them' );

		$I->click( "#cb-select-{$variable_product->get_id()}" );
		$I->selectOption( '[name=action]', 'Include in Facebook sync' );
		$I->click( '#doaction' );
		$I->waitForElement( "#cb-select-{$variable_product->get_id()}:not(:checked)" );

		$I->see( '1 product or some of its variations could not be updated to show in the Facebook catalog', 'div.notice.is-dismissible' );
		$I->see( 'Sync and show', 'table.wp-list-table td' ); // because it has non-virtual variations
		$I->dontSee( 'Sync and hide', 'table.wp-list-table td' );
		$I->dontSee( 'Do not sync', 'table.wp-list-table td' );

		$variable_product = wc_get_product( $variable_product );
		$variation_ids    = $variable_product->get_children();

		foreach ( $variation_ids as $variation_id ) {

			$variation = wc_get_product( $variation_id );

			$enabled_meta_criteria = [
				'post_id'    => $variation_id,
				'meta_key'   => '_wc_facebook_sync_enabled',
				'meta_value' => 'yes',
			];

			$visibility_meta_criteria = [
				'post_id'    => $variation_id,
				'meta_key'   => 'fb_visibility',
				'meta_value' => 'no',
			];

			if ( ! $variation->is_virtual() ) {
				$I->seePostMetaInDatabase( $enabled_meta_criteria );
				$I->dontSeePostMetaInDatabase( $visibility_meta_criteria );
			} else {
				$I->seePostMetaInDatabase( $enabled_meta_criteria );
				$I->seePostMetaInDatabase( $visibility_meta_criteria );
			}
		}
	}


}
