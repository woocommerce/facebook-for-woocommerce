<?php

use SkyVerge\WooCommerce\Facebook\Products;

class ProductSyncSettingCest {


	/** @var string selector for the Facebook description field */
	const FIELD_DESCRIPTION = '#fb_product_description';

	/** @var string selector for the Facebook image source field */
	const FIELD_IMAGE_SOURCE = '[name="fb_product_image_source"]';

	/** @var string selector for the Facebook custom image URL field */
	const FIELD_CUSTOM_IMAGE_URL = '#fb_product_image';

	/** @var string selector for the Facebook price field */
	const FIELD_PRICE = '#fb_product_price';


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

		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );

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
	 * Test that the fields are present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_fields_present( AcceptanceTester $I ) {

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the fields are present' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->see( 'Include in Facebook sync', '.form-field' );
		$I->see( 'Facebook Description', '.form-field' );
		$I->see( 'Facebook Product Image', '.form-field' );
		$I->see( 'Facebook Price', '.form-field' );
	}


	/**
	 * Test that the field has the correct default value for sync enabled products.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_field_default_enabled( AcceptanceTester $I ) {

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the field has the correct default value for sync enabled products' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->seeCheckboxIsChecked( '#fb_sync_enabled' );
	}


	/**
	 * Test that the field has the correct default value for sync disabled products.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_field_default_disabled( AcceptanceTester $I ) {

		$I->amEditingPostWithId( $this->sync_disabled_product->get_id() );

		$I->wantTo( 'Test that the field has the correct default value for sync disabled products' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->dontSeeCheckboxIsChecked( '#fb_sync_enabled' );
	}


	/**
	 * Test that the field value is saved correctly when enabling sync.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws Exception
	 */
	public function try_field_enable( AcceptanceTester $I ) {

		/**
		 * Set these in the database so that the product processing hooks are properly set
		 * @see \WC_Facebookcommerce_Integration::__construct()
		 */
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PAGE_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );

		$I->amEditingPostWithId( $this->sync_disabled_product->get_id() );

		$I->wantTo( 'Test that the field value is saved correctly when enabling sync' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );
		// remove WP admin bar and WooCommerce Admin bar to fix "Element is not clickable" issue
		$I->executeJS( 'jQuery("#wpadminbar,#woocommerce-embedded-root").remove();' );
		$I->checkOption( '#fb_sync_enabled' );
		$I->click( 'Update' );
		$I->waitForText( 'Product updated' );
		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->seeCheckboxIsChecked( '#fb_sync_enabled' );
	}


	/**
	 * Test that the field value is saved correctly when disabling sync.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws Exception
	 */
	public function try_field_disable( AcceptanceTester $I ) {

		/**
		 * Set these in the database so that the product processing hooks are properly set
		 * @see \WC_Facebookcommerce_Integration::__construct()
		 */
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PAGE_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the field value is saved correctly when disabling sync' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );
		// remove WP admin bar and WooCommerce Admin bar to fix "Element is not clickable" issue
		$I->executeJS( 'jQuery("#wpadminbar,#woocommerce-embedded-root").remove();' );
		$I->uncheckOption( '#fb_sync_enabled' );
		$I->click( 'Update' );
		$I->waitForText( 'Product updated' );
		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->dontSeeCheckboxIsChecked( '#fb_sync_enabled' );
	}


	/**
	 * Tests that you can configure the product to use the WooCommerce image for Facebook sync
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_use_woocommerce_image_in_facebook( AcceptanceTester $I ) {

		$description = 'Test description.';
		$price       = '12.34';

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that you can configure the product to use the WooCommerce image for Facebook sync' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->fillField( self::FIELD_DESCRIPTION, $description );
		$I->selectOption( self::FIELD_IMAGE_SOURCE, Products::PRODUCT_IMAGE_SOURCE_PRODUCT );
		$I->fillField( self::FIELD_PRICE, $price );

		// scroll to and click the Update button
		$I->scrollTo( '#publish', 0, -200 );
		$I->click( '#publish' );

		$I->waitForText( 'Product updated' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->seeInField( self::FIELD_DESCRIPTION, $description );
		$I->seeOptionIsSelected( self::FIELD_IMAGE_SOURCE, 'Use WooCommerce image' );
		$I->seeInField( self::FIELD_PRICE, $price );
	}


	/**
	 * Tests that you can configure the product to use a custom image for Facebook sync
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_use_custom_image_in_facebook( AcceptanceTester $I ) {

		$description = 'Test description.';
		$image_url   = 'https://example.com/image.png';
		$price       = '12.34';

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that you can configure the product to use a custom image for Facebook sync' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->fillField( self::FIELD_DESCRIPTION, $description );
		$I->selectOption( self::FIELD_IMAGE_SOURCE, Products::PRODUCT_IMAGE_SOURCE_CUSTOM );
		$I->fillField( self::FIELD_CUSTOM_IMAGE_URL, $image_url );
		$I->fillField( self::FIELD_PRICE, $price );

		// scroll to and click the Update button
		$I->scrollTo( '#publish', 0, -200 );
		$I->click( '#publish' );

		$I->waitForText( 'Product updated' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->seeInField( self::FIELD_DESCRIPTION, $description );
		$I->seeOptionIsSelected( self::FIELD_IMAGE_SOURCE, 'Use custom image' );
		$I->seeInField( self::FIELD_CUSTOM_IMAGE_URL, $image_url );
		$I->seeInField( self::FIELD_PRICE, $price );
	}


	/**
	 * Test that the tab is hidden for virtual products.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_tab_hidden_virtual_products( AcceptanceTester $I ) {

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the tab is hidden when the product is made virtual' );

		// checkOption does not work here for some reason
		$I->click( '#_virtual' );

		$I->dontSee( 'Facebook', '.fb_commerce_tab_options' );

		$I->wantTo( 'Test that the tab and fields are shown when the product is made non virtual' );

		$I->click( '#_virtual' );

		$I->see( 'Facebook', '.fb_commerce_tab_options' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->see( 'Include in Facebook sync', '.form-field' );
		$I->see( 'Facebook Description', '.form-field' );
		$I->see( 'Facebook Product Image', '.form-field' );
		$I->see( 'Facebook Price', '.form-field' );
	}


}
