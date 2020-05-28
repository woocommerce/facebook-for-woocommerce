<?php

use SkyVerge\WooCommerce\Facebook\Admin\Settings_Screens\Messenger;

class MessengerTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		require_once 'includes/Admin/Abstract_Settings_Screen.php';
		require_once 'includes/Admin/Settings_Screens/Messenger.php';
	}


	/**
	 * @see \Messenger::sanitize_messenger_greeting()
	 *
	 * @dataProvider sanitize_messenger_greeting_provider
	 *
	 * @param null|string $value value to validate
	 * @param string $expected expected result
	 */
	public function test_sanitize_messenger_greeting( $value, $expected ) {

		$screen = new Messenger();

		$this->assertSame( $expected, $screen->sanitize_messenger_greeting( $value ) );
	}


	/**
	 * Provider for sanitize_messenger_greeting()
	 *
	 * @return array
	 */
	public function sanitize_messenger_greeting_provider() {

		return [
			[ null, '' ],
			[ 'This is a valid value', 'This is a valid value' ],
			[ 'This is a valid value that is exactly the max length and should still get saved.', 'This is a valid value that is exactly the max length and should still get saved.' ],
			[ 'This is a valid value with spèciäl characters and should still get saved okay???', 'This is a valid value with spèciäl characters and should still get saved okay???' ],
			[ 'This is a valid value that exceeds the max length and should get trucated before save', 'This is a valid value that exceeds the max length and should get trucated before' ],
		];
	}


}
