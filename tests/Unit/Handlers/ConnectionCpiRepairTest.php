<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */

declare( strict_types=1 );

namespace WooCommerce\Facebook\Tests\Unit\Handlers;

use WooCommerce\Facebook\API;
use WooCommerce\Facebook\API\CommerceIntegration\Configuration\Update\Response as UpdateResponse;
use WooCommerce\Facebook\API\CommerceIntegration\Repair\Response as RepairResponse;
use WooCommerce\Facebook\API\FBE\Installation\Read\Response as InstallationResponse;
use WooCommerce\Facebook\Handlers\Connection;
use WooCommerce\Facebook\Tests\AbstractWPUnitTestWithOptionIsolationAndSafeFiltering;
use WooCommerce\Facebook\Utilities\Heartbeat;

/**
 * Unit tests for repairing missing Commerce Partner Integration data.
 */
class ConnectionCpiRepairTest extends AbstractWPUnitTestWithOptionIsolationAndSafeFiltering {

	/**
	 * Verifies that the daily refresh repairs a connected merchant with no CPI before updating its configuration.
	 *
	 * No persistent entities are required for this test, so a data preparer is unnecessary.
	 */
	public function test_missing_cpi_is_repaired_and_configuration_is_updated(): void {
		$external_business_id            = wp_generate_uuid4();
		$commerce_partner_integration_id = wp_generate_uuid4();

		update_option( Connection::OPTION_ACCESS_TOKEN, 'access-token' );
		update_option( Connection::OPTION_EXTERNAL_BUSINESS_ID, $external_business_id );
		delete_option( Connection::OPTION_COMMERCE_PARTNER_INTEGRATION_ID );
		delete_transient( '_wc_facebook_for_woocommerce_refresh_installation_data' );

		$api = $this->createMock( API::class );
		$api->expects( $this->once() )
			->method( 'get_installation_ids' )
			->with( $external_business_id )
			->willReturn( new InstallationResponse( wp_json_encode( array( 'data' => array( array() ) ) ) ) );
		$api->expects( $this->once() )
			->method( 'repair_commerce_integration' )
			->with(
				$external_business_id,
				site_url( '/' ),
				admin_url(),
				'test-version'
			)
			->willReturn(
				new RepairResponse(
					wp_json_encode(
						array(
							'success' => true,
							'id'      => $commerce_partner_integration_id,
						)
					)
				)
			);
		$api->expects( $this->once() )
			->method( 'update_commerce_integration' )
			->with(
				$commerce_partner_integration_id,
				'test-version',
				admin_url(),
				$this->anything(),
				get_woocommerce_currency(),
				$this->anything()
			)
			->willReturn( new UpdateResponse( wp_json_encode( array( 'success' => true ) ) ) );

		$plugin = $this->getMockBuilder( \WC_Facebookcommerce::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_api', 'get_version', 'log' ) )
			->getMock();
		$plugin->method( 'get_api' )->willReturn( $api );
		$plugin->method( 'get_version' )->willReturn( 'test-version' );

		$connection = new Connection( $plugin );

		$this->assertSame( 10, has_action( Heartbeat::DAILY, array( $connection, 'refresh_installation_data' ) ) );

		$connection->refresh_installation_data();

		$this->assertSame(
			$commerce_partner_integration_id,
			get_option( Connection::OPTION_COMMERCE_PARTNER_INTEGRATION_ID )
		);
	}
}
