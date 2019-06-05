<?php
 /*
 Plugin Name: Meta By Path
 Plugin URI: https://www.nettantra.com/wordpress/?utm_src=meta-by-path
 Description: Meta By Path facilitates for easily replacing an existing value inside a meta content with a new one. Also, it can create new meta names and properties without requiring the knowledge on source code modification.
 Version: 1.0.2
 Author: NetTantra
 Author URI: https://www.nettantra.com/wordpress/?utm_src=meta-by-path
 Text Domain: meta-by-path
 License: GPLv2 or later
 */


if ( ! defined( 'ABSPATH' ) ) {
	die;
}


function wpmbp_create_table(){
    global $wpdb;
    $table_name = $wpdb->prefix.'meta_by_path';

    if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
          `id` bigint(20) UNSIGNED UNSIGNED NOT NULL AUTO_INCREMENT,
          `pageurl` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL,
					`all_page` SMALLINT(2) NOT NULL,
					`meta_info` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
          `creation_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `modification_timestamp` timestamp NOT NULL,
          `created_by` int(11) NOT NULL,
          `modified_by` int(11) NOT NULL,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}


function wpmbp_on_activate( $network_wide ){
    global $wpdb;
    if ( is_multisite() && $network_wide ) {
        // Get all blogs in the network and activate plugin on each one
        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            wpmbp_create_table();
            restore_current_blog();
        }
    } else {
        wpmbp_create_table();
    }
}

register_activation_hook( __FILE__, 'wpmbp_on_activate' );


function wpmbp_on_deactivate() {
  // do nothing
}
register_deactivation_hook( __FILE__, 'wpmbp_on_deactivate' );


require plugin_dir_path( __FILE__ ) . 'includes/class-meta-by-path.php';

new WPMetaByPath();
