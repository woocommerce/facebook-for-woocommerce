<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Traits;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Tests the API\Traits\Idempotent_Request trait.
 */
class IdempotentRequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		parent::_before();

		if ( ! trait_exists( API\Traits\Rate_Limited_Request::class, false ) ) {
			require_once 'includes/API/Traits/Rate_Limited_Request.php';
		}

		if ( ! class_exists( API\Request::class ) ) {
			require_once 'includes/API/Request.php';
		}

		if ( ! trait_exists( API\Traits\Idempotent_Request::class, false ) ) {
			require_once 'includes/API/Traits/Idempotent_Request.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/** @see API\Traits\Idempotent_Request::get_idempotency_key() */
	public function test_get_idempotency_key() {

		$request = $this->tester->get_idempotent_request( 'path' );

		$this->assertNotEmpty( $request->get_idempotency_key() );
	}


}
