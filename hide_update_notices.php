<?php
/*
Plugin Name: Hide Update Notices
Description: Shows and Hides Update Notices for WP Admin Pannel. Use Activate / Deactivate this plugin.
Version: 1.0
Author: Carton TEAM
Author URI: http://Carton-ecommerce.com/
*/

add_action('admin_menu','hide_update_notices');

function hide_update_notices() {

  remove_action( 'init', 'wp_version_check' );
  remove_action( 'admin_init', 'wp_plugin_update_rows' );
  remove_action( 'admin_init', 'wp_theme_update_rows' );
  remove_action( 'admin_notices', 'maintenance_nag' );
/* */
  remove_action( 'admin_init', '_maybe_update_core' );
  remove_action( 'wp_version_check', 'wp_version_check' );

  remove_action( 'load-plugins.php', 'wp_update_plugins' );
  remove_action( 'load-update.php', 'wp_update_plugins' );
  remove_action( 'load-update-core.php', 'wp_update_plugins' );
  remove_action( 'admin_init', '_maybe_update_plugins' );
  remove_action( 'wp_update_plugins', 'wp_update_plugins' );

  remove_action( 'load-themes.php', 'wp_update_themes' );
  remove_action( 'load-update.php', 'wp_update_themes' );
  remove_action( 'load-update-core.php', 'wp_update_themes' );
  remove_action( 'admin_init', '_maybe_update_themes' );
  remove_action( 'wp_update_themes', 'wp_update_themes' );

  remove_action('init', 'wp_schedule_update_checks');
}

?>