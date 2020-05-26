<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Traits;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Tests the API\Traits\Paginated_Response trait.
 */
class PaginatedResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var array test data */
	protected $data = [
		'data' => [
			[ 'id' => '1234' ],
		],
		'paging' => [
			'next'     => 'next page',
			'previous' => 'previous page',
		],
	];


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		parent::_before();

		if ( ! class_exists( API\Response::class ) ) {
			require_once 'includes/API/Response.php';
		}

		if ( ! trait_exists( API\Traits\Paginated_Response::class, false ) ) {
			require_once 'includes/API/Traits/Paginated_Response.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see API\Traits\Paginated_Response::set_pages_retrieved()
	 * @see API\Traits\Paginated_Response::get_pages_retrieved()
	 */
	public function test_pages_retrieved() {

		$response = $this->tester->get_paginated_response();

		$response->set_pages_retrieved( 5 );

		$this->assertEquals( 5, $response->get_pages_retrieved() );
	}


	/** @see API\Traits\Paginated_Response::get_pages_retrieved() */
	public function test_get_pages_retrieved_default_value() {

		$response = $this->tester->get_paginated_response( $this->data );

		$this->assertEquals( 1, $response->get_pages_retrieved() );
	}


	/** @see API\Traits\Paginated_Response::get_data() */
	public function test_get_data() {

		$response = $this->tester->get_paginated_response( $this->data );

		$this->assertEquals( [ (object) [ 'id' => '1234' ] ], $response->get_data() );
	}


	/** @see API\Traits\Paginated_Response::get_pagination_data() */
	public function test_get_pagination_data() {

		$response = $this->tester->get_paginated_response( $this->data );

		$pagination_data = (object) [ 'next' => 'next page', 'previous' => 'previous page' ];

		$this->assertEquals( $pagination_data, $response->get_pagination_data() );
	}


	/** @see API\Traits\Paginated_Response::get_next_page_endpoint() */
	public function test_get_next_page_endpoint() {

		$response = $this->tester->get_paginated_response( $this->data );

		$this->assertEquals( 'next page', $response->get_next_page_endpoint() );
	}


	/** @see API\Traits\Paginated_Response::get_previous_page_endpoint() */
	public function test_get_previous_page_endpoint() {

		$response = $this->tester->get_paginated_response( $this->data );

		$this->assertEquals( 'previous page', $response->get_previous_page_endpoint() );
	}


}
