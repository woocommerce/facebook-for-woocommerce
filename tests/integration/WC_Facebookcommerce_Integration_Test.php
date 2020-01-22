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


	/** Helper methods ************************************************************************************************/


	/**
	 * Adds configured options.
	 */
	private function add_options() {

	}


}

