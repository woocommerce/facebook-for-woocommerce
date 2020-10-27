<?php

use SkyVerge\WooCommerce\Facebook\Utilities\Shipment;

/**
 * Tests the shipment utility class.
 */
class ShipmentTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		if ( ! class_exists( Shipment::class ) ) {
			require_once 'includes/Utilities/Shipment.php';
		}
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see Shipment::get_carrier_options() */
	public function test_get_carrier_options() {

		$carrier_options = $this->get_shipment_utilities()->get_carrier_options();
		$this->assertIsArray( $carrier_options );
		$this->assertNotEmpty( $carrier_options );
		$this->assertArrayHasKey( 'AUSTRALIA_POST', $carrier_options );
		$this->assertEquals( 'Australia Post', $carrier_options['AUSTRALIA_POST'] );
	}


	/**
	 * @see Shipment::is_valid_carrier()
	 *
	 * @param string $carrier a shipment carrier
	 * @dataProvider provider_is_valid_carrier
	 */
	public function test_is_valid_carrier( string $carrier, bool $valid ) {

		$this->assertSame( $valid, $this->get_shipment_utilities()->is_valid_carrier( $carrier ) );
	}


	/** @see test_is_valid_carrier() */
	public function provider_is_valid_carrier() {

		return [
			'valid'   => [ 'RAM',           true ],
			'invalid' => [ 'NOT_A_CARRIER', false ],
		];
	}


	/**
	 * @see Shipment::convert_shipment_tracking_carrier_code()
	 *
	 * @param string $carrier Shipment Tracking carrier
	 * @param string $expected_value expected returned value
	 * @dataProvider provider_convert_shipment_tracking_carrier_code
	 */
	public function test_convert_shipment_tracking_carrier_code( $carrier, $expected_value ) {

		$this->assertSame( $expected_value, $this->get_shipment_utilities()->convert_shipment_tracking_carrier_code( $carrier ) );
	}


	/** @see test_convert_shipment_tracking_carrier_code */
	public function provider_convert_shipment_tracking_carrier_code() {

		return [
			'mapped'         => [ 'SAPO', 'SOUTH_AFRICAN_POST_OFFICE' ],
			'matching label' => [ 'South African Post Office', 'SOUTH_AFRICAN_POST_OFFICE' ],
			'matching code'  => [ 'SOUTH_AFRICAN_POST_OFFICE', 'SOUTH_AFRICAN_POST_OFFICE' ],
			'not found'      => [ 'Imaginary post office', 'OTHER' ],
		];
	}


	/** Helper methods **************************************************************************************************/


	/**
	 * Gets the shipment utilities instance.
	 *
	 * @return \SkyVerge\WooCommerce\Facebook\Utilities\Shipment
	 */
	private function get_shipment_utilities() {

		return new Shipment();
	}


}
