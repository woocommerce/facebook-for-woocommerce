<?php

class SettingsCest {


	public function _before( AcceptanceTester $I ) {

		$I->loginAsAdmin();
	}


	/**
	 * Tests the menu item.
	 *
	 * @param AcceptanceTester $I
	 */
	public function try_menu_item( AcceptanceTester $I ) {

		$I->amOnAdminPage( 'edit.php?post_type=shop_order' );

		$I->see( 'Facebook', '#toplevel_page_woocommerce .wp-submenu li a' );

		$I->click( 'Facebook' );

		$I->see( 'Connection', '.woo-nav-tab-wrapper a' );
		$I->see( 'Product sync', '.woo-nav-tab-wrapper a' );
		$I->see( 'Messenger', '.woo-nav-tab-wrapper a' );
		$I->see( 'Advertise', '.woo-nav-tab-wrapper a' );
	}


	/**
	 * Tests the plugin page "Configure" link.
	 *
	 * @param AcceptanceTester $I
	 */
	public function try_configure_link( AcceptanceTester $I ) {

		$I->amOnPluginsPage();

		$I->click( 'Configure' );

		$I->seeInCurrentUrl( 'page=wc-facebook' );
	}


}
