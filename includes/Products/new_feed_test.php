<?php
/**
 * This function is used to perform performance testing for background sync process job processing functionality.
 */
function perform_feed_test() {

	$feed_generator = new SkyVerge\WooCommerce\Facebook\Products\FB_Feed_Generator();

	$feed_generator->set_page( 1 );
	$feed_generator->generate_file();

}

add_action(
	'admin_menu',
	function() {
		add_submenu_page(
			null,
			__( 'Welcome', 'textdomain' ),
			__( 'Welcome', 'textdomain' ),
			'manage_options',
			'facebook-for-woocommerce-feed',
			'prefix_render'
		);
	}
);

function prefix_render() {
	echo '<div class="wrap">';
	$mem_start = memory_get_usage( true );
	echo 'Start the feed ' . var_export( $mem_start, true ) . PHP_EOL;
	perform_feed_test();
	$mem_end = memory_get_usage( true );
	echo 'Stop the feed ' . var_export( $mem_end, true ) . PHP_EOL;
	echo 'Mem diff ' . var_export( round( ( $mem_end - $mem_start ) / 1024 / 1024, 4 ), true );
	echo '</div>';
}
