<?php

use SkyVerge\WooCommerce\Facebook\Admin;

/**
 * Tests the Admin\Settings class.
 */
class SettingsTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once 'includes/Admin/Settings.php';
		require_once 'includes/Admin/Abstract_Settings_Screen.php';
		require_once 'includes/Admin/Settings_Screens/Messenger.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see Admin\Settings::get_screen()
	 *
	 * @param string $screen_id
	 * @param Admin\Abstract_Settings_Screen|null $expected
	 *
	 * @dataProvider provider_get_screen
	 */
	public function test_get_screen( $screen_id, $expected ) {

		$this->assertSame( $expected, $this->get_setting_handler()->get_screen( $screen_id ) );
	}


	/** @see test_get_screen */
	public function provider_get_screen() {

		return [
			[ 'non-existent', null ],
		];
	}


	/** @see Admin\Settings::get_screens() */
	public function test_get_screens() {

		$screens = $this->get_setting_handler()->get_screens();

		$this->assertArrayHasKey( 'messenger', $screens );
		$this->assertInstanceOf( Admin\Settings_Screens\Messenger::class, $screens['messenger'] );
	}


	/** @see Admin\Settings::get_screens() */
	public function test_get_screens_filter() {

		add_filter( 'wc_facebook_admin_settings_screens', function( $screens ) {

			$screens['custom-screen'] = $this->getMockForAbstractClass( Admin\Abstract_Settings_Screen::class );;

			return $screens;

		} );

		$screens = $this->get_setting_handler()->get_screens();

		$this->assertArrayHasKey( 'custom-screen', $screens );
		$this->assertInstanceOf( Admin\Abstract_Settings_Screen::class, $screens['custom-screen'] );
	}


	/**
	 * Tests that the screens filter removes invalid screen values.
	 *
	 * @see Admin\Settings::get_screens()
	 */
	public function test_get_screens_filter_invalid_screen() {

		add_filter( 'wc_facebook_admin_settings_screens', function( $screens ) {

			$screens['bogus_screen'] = 'Nope!';

			return $screens;

		} );

		$this->assertArrayNotHasKey( 'bogus_screen', $this->get_setting_handler()->get_screens() );
	}


	/** @see Admin\Settings::get_tabs() */
	public function test_get_tabs() {

		$tabs = $this->get_setting_handler()->get_tabs();

		$this->assertArrayHasKey( 'messenger', $tabs );
	}


	/** @see Admin\Settings::get_tabs() */
	public function test_get_tabs_filter() {

		add_filter( 'wc_facebook_admin_settings_tabs', function( $tabs ) {

			$tabs['added-tab'] = 'Hello!';

			return $tabs;

		} );

		$tabs = $this->get_setting_handler()->get_tabs();

		$this->assertArrayHasKey( 'added-tab', $tabs );
		$this->assertSame( 'Hello!', $tabs['added-tab'] );
	}


	/** Utility methods ***********************************************************************************************/


	/**
	 * Gets a settings handler instance.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return Admin\Settings
	 */
	private function get_setting_handler() {

		return new Admin\Settings();
	}


}
