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


	/** @see Commerce::get_default_google_product_category_id() */
	public function test_get_default_google_product_category_id_filter() {

		add_filter( 'wc_facebook_commerce_default_google_product_category_id', static function() {

			return 'filtered';
		} );

		$this->assertSame( 'filtered', $this->get_commerce_handler()->get_default_google_product_category_id() );
	}


	/**
	 * @see Commerce::update_default_google_product_category_id()
	 *
	 * @param string $new_value new product category ID
	 * @param string $stored_value expected stored value
	 * @dataProvider provider_update_default_google_product_category_id
	 */
	public function test_update_default_google_product_category_id( $new_value, $stored_value ) {

		$this->get_commerce_handler()->update_default_google_product_category_id( $new_value );

		$this->assertSame( $stored_value, $this->get_commerce_handler()->get_default_google_product_category_id() );
		$this->assertSame( $stored_value, $this->get_commerce_handler()->get_default_google_product_category_id() );
	}


	/** @see test_update_default_google_product_category_id */
	public function provider_update_default_google_product_category_id() {

		return [
			[ 'category_id', 'category_id' ],
			[ '12',          '12' ],
			[ 12,            '' ],
			[ null,          '' ]
		];
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
