<?php

class Subscribers {

	public function __construct() {
		global $wpdb;
		$wpdb instanceof wpdb;

		$charset = !empty( $wpdb->charset ) ?
						"DEFAULT CHARACTER SET {$wpdb->charset}" :
						'';

		$collate = !empty( $wpdb->collate ) ?
						"COLLATE {$wpdb->collate}" :
						'';

		$query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}subscribers ("
						. "subscriber_id int(11) NOT NULL AUTO_INCREMENT,"
						. "subscriber_name VARCHAR(255) NULL,"
						. "subscriber_email VARCHAR(255) NOT NULL,"
						. "subscriber_source VARCHAR(255) NULL,"
						. "suscriber_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
						. "PRIMARY KEY (subscriber_id)"
						. ") ENGINE=InnoDB {$charset} {$collate} AUTO_INCREMENT=1";
		$wpdb->query( $query );
	}

	public function add_subscriber() {
		$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
		$verify_nonce = (bool) wp_verify_nonce( $nonce, 'add_subscriber' );

		if ( !$verify_nonce ) wp_send_json_error();

		$submit = array(
				'subscriber_email' => filter_input( INPUT_POST, 'subscriber_email', FILTER_SANITIZE_STRING ),
				'subscriber_name' => filter_input( INPUT_POST, 'subscriber_name', FILTER_SANITIZE_STRING ),
		);

		if ( !is_email( $submit['subscriber_email'] ) ) wp_send_json_error();


		global $wpdb;
		$wpdb instanceof wpdb;

		$result = $wpdb->query( "SELECT * FROM {$wpdb->prefix}subscribers WHERE subscriber_email = '{$submit['subscriber_email']}' LIMIT 1" );

		if ( $result == 0 ) {
			$wpdb->insert( "{$wpdb->prefix}subscribers", $submit );
		}

		wp_send_json_success();
	}

}

$Subscriber = new Subscribers();
add_action( 'wp_ajax_nopriv_add_subscriber', array( $Subscriber, 'add_subscriber' ) );
add_action( 'wp_ajax_add_subscriber', array( $Subscriber, 'add_subscriber' ) );

