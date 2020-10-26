<?php

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\Commerce;
use SkyVerge\WooCommerce\Facebook\Handlers\Connection;
use SkyVerge\WooCommerce\Facebook\Products\Sync;
use SkyVerge\WooCommerce\Facebook\Products\Sync\Background;
use SkyVerge\WooCommerce\PluginFramework\v5_9_0 as Framework;

/**
 * Tests the WC_Facebookcommerce class.
 */
class WC_Facebookcommerce_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		facebook_for_woocommerce()->get_connection_handler()->update_access_token( '1234' );

		facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/** @see \WC_Facebookcommerce::get_api() */
	public function test_get_api() {

		$this->assertInstanceOf( API::class, facebook_for_woocommerce()->get_api() );
	}


	/** @see \WC_Facebookcommerce::get_api() */
	public function test_get_api_exception() {

		$this->expectException( Framework\SV_WC_API_Exception::class );

		$plugin = facebook_for_woocommerce();

		$plugin->get_connection_handler()->update_access_token( null );

		// remove existing instances to make sure the method attempts to create a new one
		$instance = new ReflectionProperty( WC_Facebookcommerce::class, 'api' );
		$instance->setAccessible( true );
		$instance->setValue( $plugin, null );

		$this->assertInstanceOf( API::class, $plugin->get_api() );
	}


	/** @see \WC_Facebookcommerce::get_api() */
	public function test_get_api_with_access_token() {

		$this->assertSame( 'new_access_token', facebook_for_woocommerce()->get_api( 'new_access_token' )->get_access_token() );
	}


	/** @see \WC_Facebookcommerce::get_connection_handler() */
	public function test_get_connection_handler() {

		$this->assertInstanceOf( Connection::class, facebook_for_woocommerce()->get_connection_handler() );
	}


	/** @see WC_Facebookcommerce::get_products_sync_handler() */
	public function test_get_products_sync_handler() {

		$this->assertInstanceOf( Sync::class, facebook_for_woocommerce()->get_products_sync_handler() );
	}


	/** @see WC_Facebookcommerce::get_products_sync_background_handler() */
	public function test_get_products_sync_background_handler() {

		$this->assertInstanceOf( Background::class, facebook_for_woocommerce()-> get_products_sync_background_handler() );
	}


	/** @see \WC_Facebookcommerce::get_commerce_handler() */
	public function test_get_commerce_handler() {

		$this->assertInstanceOf( Commerce::class, facebook_for_woocommerce()->get_commerce_handler() );
	}


	/** @see \WC_Facebookcommerce::get_support_url() */
	public function test_get_support_url() {

		$this->assertEquals( 'https://wordpress.org/support/plugin/facebook-for-woocommerce/', facebook_for_woocommerce()->get_support_url() );
	}


}
