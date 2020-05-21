<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API;

use SkyVerge\WooCommerce\Facebook\API\Request;

/**
 * Tests the API\Request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		if ( ! class_exists( Request::class ) ) {
			require_once 'includes/API/Request.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see Request::__construct()
	 *
	 * @param string $object_id object ID
	 * @param string $path endpoint route
	 * @param string $method HTTP method
	 * @param string $expected_path expected request path
	 *
	 * @dataProvider provider_constructor
	 */
	public function test_constructor( $object_id, $path, $method, $expected_path ) {

		$request = new Request( $object_id, $path, $method );

		$this->assertEquals( $expected_path, $request->get_path() );
		$this->assertEquals( $method, $request->get_method() );
	}

	/** @see test_constructor */
	public function provider_constructor( $requests ) {

		return [
			[ 'me', '', 'GET', '/me' ],
			[ '1234', '/products', 'GET', '/1234/products' ],

			[ '1234', 'batch', 'POST', '/1234/batch' ],
			[ '1234', '/batch/', 'POST', '/1234/batch' ],
			[ '1234', '', 'POST', '/1234' ],
		];
	}


	/** @see Request::set_data() */
	public function test_set_data() {

		$request = new Request( null, null, null );
		$data    = [ 'key' => 'value' ];

		$request->set_data( $data );

		$this->assertEquals( $data, $request->get_data() );
	}


}
