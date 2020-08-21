<?php

use SkyVerge\WooCommerce\Facebook\Commerce;

/**
 * Tests the Commerce handler class.
 */
class CommerceTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Test methods **************************************************************************************************/


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
