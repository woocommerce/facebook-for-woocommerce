<?php

class ProductSyncCest {


	public function _before( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '1235' );

		$I->loginAsAdmin();
	}


	/**
	 * Test that the Product sync fields are present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_product_sync_fields_present( AcceptanceTester $I ) {

		$I->amOnAdminPage('admin.php?page=wc-facebook&tab=product_sync' );

		$I->wantTo( 'Test that the Product sync fields are present' );

		$I->dontSee( 'Please connect to Facebook to enable and manage product sync.', '.notice' );

		$I->see( 'Sync products', 'a.button' );

		$I->see( 'Enable product sync', 'th.titledesc' );
		$I->seeElement( 'input[type=checkbox]#' . \WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC );

		$I->see( 'Exclude categories from sync', 'th.titledesc' );
		$I->seeElement( 'select.wc-enhanced-select#' . \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS );

		$I->see( 'Exclude tags from sync', 'th.titledesc' );
		$I->seeElement( 'select.wc-enhanced-select#' . \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS );

		$I->see( 'Product description sync', 'th.titledesc' );
		$I->seeElement( 'select#' . \WC_Facebookcommerce_Integration::SETTING_PRODUCT_DESCRIPTION_MODE );
	}


	public function try_connect_message( AcceptanceTester $I ) {

		$I->dontHaveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN );

		$I->amOnAdminPage('admin.php?page=wc-facebook&tab=product_sync' );

		$I->see( 'Please connect to Facebook to enable and manage product sync.', '.notice' );
	}


	/**
	 * Test that the Product sync fields are saved correctly.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws Exception
	 */
	public function try_product_sync_fields_saved( AcceptanceTester $I ) {

		// save a product category and a product tag to exclude from facebook sync
		list( $excluded_category_id, $excluded_category_taxonomy_id ) = $I->haveTermInDatabase( 'Excluded Category', 'product_cat' );
		list( $excluded_tag_id, $excluded_tag_taxonomy_id )           = $I->haveTermInDatabase( 'Excluded Tag', 'product_tag' );

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '1235' );
		$I->amOnAdminPage('admin.php?page=wc-facebook&tab=product_sync' );

		$I->wantTo( 'Test that the Product sync fields are saved correctly' );

		// select excluded categories/tags because submitForm can't set hidden elements
		$I->selectOption( '#' . \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS, $excluded_category_taxonomy_id );
		$I->selectOption( '#' . \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS, $excluded_tag_taxonomy_id );

		$form = [
			\WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC                  => true,
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS . '[]' => [ (string) $excluded_category_taxonomy_id ],
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS . '[]'      => [ (string) $excluded_tag_taxonomy_id ],
			\WC_Facebookcommerce_Integration::SETTING_PRODUCT_DESCRIPTION_MODE             => \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_SHORT,
		];

		$I->submitForm( '#mainform', $form, 'save_product_sync_settings' );
		$I->waitForText( 'Your settings have been saved.' );

		$I->seeInFormFields( '#mainform', $form );
	}


}
