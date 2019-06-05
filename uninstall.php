<?php
/**
 * File that is run during plugin uninstall (not just de-activate)
 *
 * @TODO: delete all tables in network if on multisite
 */

// If uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;

// Remove database table
$table_name = $wpdb->prefix . 'meta_by_path';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
