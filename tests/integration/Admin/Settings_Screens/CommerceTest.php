<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\Admin\Settings_Screens;

use SkyVerge\WooCommerce\Facebook\Admin\Settings_Screens;

/**
 * Tests the Settings_Screens\Commerce class.
 */
class CommerceTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once 'includes/Admin/Abstract_Settings_Screen.php';
		require_once 'includes/Admin/Settings_Screens/Commerce.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see Settings_Screens\Commerce::get_connect_url() */
	public function test_get_connect_url() {

		$screen = new Settings_Screens\Commerce();
		$connect_url = $screen->get_connect_url();

		$this->assertStringContainsString( 'https://www.facebook.com/commerce_manager/onboarding/?app_id=', $connect_url );
		$this->assertStringContainsString( 'redirect_url=https%3A%2F%2Fconnect.woocommerce.com%2Fauth%2Ffacebook%2F%3Fsite_url%3D', $connect_url );
		$this->assertStringContainsString( 'wc-api%3D' . \SkyVerge\WooCommerce\Facebook\Handlers\Connection::ACTION_CONNECT_COMMERCE . '%26nonce%3D', $connect_url );
	}


}
