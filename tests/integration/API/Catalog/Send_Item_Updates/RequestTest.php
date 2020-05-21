<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Catalog\Send_Item_Updates;

use SkyVerge\WooCommerce\Facebook\API\Catalog\Send_Item_Updates\Request;
use SkyVerge\WooCommerce\Facebook\Products\Sync;

/**
 * Tests the API\Catalog\Send_Item_Updates\Request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		require_once 'includes/API/Request.php';
		require_once 'includes/API/Catalog/Send_Item_Updates/Request.php';
	}


	/** Test methods **************************************************************************************************/


	/** @see Request::__construct() */
	public function test_constructor() {

		$request = new Request( '1234' );

		$this->assertEquals( '/1234/batch', $request->get_path() );
		$this->assertEquals( 'POST', $request->get_method() );
	}


	/** @see Request::set_requests() */
	public function test_set_requests() {

		$request  = new Request( '1234 ');

		$requests = [ [
			'data'        => [],
			'method'      => Sync::ACTION_UPDATE,
			'retailer_id' => 'wc_post_id_7890',
		] ];

		$request->set_requests( $requests );

		$this->assertSame( $requests, $request->get_requests() );
	}


	/**
	 * @see Request::set_allow_upsert()
	 *
	 * @param boolean $allow_upsert whether updates can create new items
	 *
	 * @dataProvider provider_set_allow_upsert()
	 */
	public function test_set_allow_upsert( bool $allow_upsert ) {

		$request = new Request( '1234 ');

		$request->set_allow_upsert( $allow_upsert );

		$this->assertSame( $allow_upsert, $request->get_allow_upsert() );
	}


	/** @see test_set_allow_upsert() */
	public function provider_set_allow_upsert() {

		return [ [ true ], [ false ] ];
	}


	/** @see Request::get_data() */
	public function test_get_data() {

		$request = new Request( '1234' );

		$allow_upsert = false;
		$requests     = [ [
			'data'        => [],
			'method'      => Sync::ACTION_UPDATE,
			'retailer_id' => 'wc_post_id_7890',
		] ];

		$request->set_requests( $requests );
		$request->set_allow_upsert( $allow_upsert );

		$data = $request->get_data();

		$this->assertSame( $requests, $data['requests'] );
		$this->assertSame( $allow_upsert, $data['allow_upsert'] );
	}


}
