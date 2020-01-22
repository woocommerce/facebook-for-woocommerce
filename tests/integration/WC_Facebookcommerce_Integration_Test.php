<?php

/**
 * Tests the integration class.
 */
class WC_Facebookcommerce_Integration_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var \WC_Facebookcommerce_Integration integration instance */
	private $integration;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		$this->integration = facebook_for_woocommerce()->get_integration();

		$this->add_options();
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see \WC_Facebookcommerce_Integration::get_page_access_token() */
	public function test_get_page_access_token() {

		$this->assertEquals( 'abc123', $this->integration->get_page_access_token() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_page_access_token() */
	public function test_get_page_access_token_filter() {

		add_filter( 'wc_facebook_page_access_token', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_page_access_token() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_product_catalog_id() */
	public function test_get_product_catalog_id() {

		$this->assertEquals( 'def456', $this->integration->get_product_catalog_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_product_catalog_id() */
	public function test_get_product_catalog_id_filter() {

		add_filter( 'wc_facebook_product_catalog_id', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_product_catalog_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_external_merchant_settings_id() */
	public function test_get_external_merchant_settings_id() {

		$this->assertEquals( 'ghi789', $this->integration->get_external_merchant_settings_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_external_merchant_settings_id() */
	public function test_get_external_merchant_settings_id_filter() {

		add_filter( 'wc_facebook_external_merchant_settings_id', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_external_merchant_settings_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_feed_id() */
	public function test_get_feed_id() {

		$this->assertEquals( 'jkl012', $this->integration->get_feed_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_feed_id() */
	public function test_get_feed_id_filter() {

		add_filter( 'wc_facebook_feed_id', function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->integration->get_feed_id() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_pixel_install_time() */
	public function test_get_pixel_install_time() {

		$this->assertEquals( 123, $this->integration->get_pixel_install_time() );
	}


	/** @see \WC_Facebookcommerce_Integration::get_pixel_install_time() */
	public function test_get_pixel_install_time_filter() {

		add_filter( 'wc_facebook_pixel_install_time', function() {
			return 321;
		} );

		$this->assertEquals( 321, $this->integration->get_pixel_install_time() );
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::update_page_access_token()
	 *
	 * @param string|null|array $value value to set
	 * @param string $expected expected stored value
	 *
	 * @dataProvider provider_update_page_access_token
	 */
	public function test_update_page_access_token( $value, $expected ) {

		$this->integration->update_page_access_token( $value );

		$this->assertEquals( $expected, $this->integration->get_page_access_token() );
		$this->assertEquals( $expected, get_option( \WC_Facebookcommerce_Integration::OPTION_PAGE_ACCESS_TOKEN ) );
	}


	/** @see test_update_page_access_token() */
	public function provider_update_page_access_token() {

		return [
			[ 'new-token', 'new-token' ],
			[ [ 1, 2 ], '' ],
		];
	}


	/**
	 * @see \WC_Facebookcommerce_Integration::update_product_catalog_id()
	 *
	 * @param string|null|array $value value to set
	 * @param string $expected expected stored value
	 *
	 * @dataProvider provider_update_product_catalog_id
	 */
	public function test_update_product_catalog_id( $value, $expected ) {

		$this->integration->update_product_catalog_id( $value );

		$this->assertEquals( $expected, $this->integration->get_product_catalog_id() );
		$this->assertEquals( $expected, get_option( \WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID ) );
	}


	/** @see test_update_product_catalog_id() */
	public function provider_update_product_catalog_id() {

		return [
			[ 'new-id', 'new-id' ],
			[ [ 1, 2 ], '' ],
		];
	}


	/** Helper methods ************************************************************************************************/


	/**
	 * Adds configured options.
	 */
	private function add_options() {

		update_option( WC_Facebookcommerce_Integration::OPTION_PAGE_ACCESS_TOKEN, 'abc123' );
		update_option( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, 'def456' );
		update_option( WC_Facebookcommerce_Integration::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, 'ghi789' );
		update_option( WC_Facebookcommerce_Integration::OPTION_FEED_ID, 'jkl012' );
		update_option( WC_Facebookcommerce_Integration::OPTION_PIXEL_INSTALL_TIME, 123 );

		// TODO: remove once these properties are no longer set directly in the constructor
		$this->integration->product_catalog_id            = null;
		$this->integration->external_merchant_settings_id = null;
		$this->integration->feed_id                       = null;
		$this->integration->pixel_install_time            = null;
	}


}

