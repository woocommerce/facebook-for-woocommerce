<?php

use SkyVerge\WooCommerce\Facebook\Commerce;

/**
 * Tests the Commerce handler class.
 */
class CommerceTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Test methods **************************************************************************************************/


	/** @see Commerce::get_default_google_product_category_id() */
	public function test_get_default_google_product_category_id() {

		update_option( Commerce::OPTION_GOOGLE_PRODUCT_CATEGORY_ID, 'default' );

		$this->assertSame( 'default', $this->get_commerce_handler()->get_default_google_product_category_id() );
	}


	public function test_get_default_google_product_category_id_filter() {

		add_filter( 'wc_facebook_commerce_default_google_product_category_id', static function() {

			return 'filtered';
		} );

		$this->assertSame( 'filtered', $this->get_commerce_handler()->get_default_google_product_category_id() );
	}


	/** Helper methods **************************************************************************************************/


	/**
	 * Gets the commerce handler instance.
	 *
	 * @return Commerce
	 */
	private function get_commerce_handler() {

		return new Commerce();
	}


}
