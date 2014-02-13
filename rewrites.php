<?php
/*
Plugin Name: CartOn Rewrite Rules
Description: This plugin helps to get page via old changed permalink
Version: 1.0
Author: Andrei Guriev
Author URI: http://carton-ecommerce.com
*/

function carton_add_rewrite_rules() {
    global $wp_rewrite;

    add_rewrite_rule('(ru)/([^/]+)/?$', 'index.php?name=$matches[2]&post_type=product&rewrite=$matches[1]', 'top' );
    add_rewrite_rule('(shop)/(.*)/?$', 'index.php?name=$matches[2]&post_type=product&rewrite=$matches[1]', 'top' );

    flush_rewrite_rules();
}
add_action('init', 'carton_add_rewrite_rules' );

function carton_add_rewrite_query_vars($public_query_vars) {
    $new_vars = array( 'rewrite' );
    return array_merge( $public_query_vars, $new_vars );
}
add_filter( 'query_vars', 'carton_add_rewrite_query_vars' );

function carton_parse_query( $wp_query ) {
    global $wpdb;
    if ( $wp_query->get( 'rewrite' ) ) {
	$old_permalink = strtolower( $wp_query->get( 'rewrite' ) .'/' . $wp_query->query_vars['name'] );
	$q = $wpdb->prepare("SELECT object_id, rewrite_id FROM {$wpdb->prefix}woocommerce_rewrites WHERE uri=%s AND object_type=%s", $old_permalink, $wp_query->query_vars['post_type']);
	$rewrite = $wpdb->get_row( $q );
	if($rewrite){
		$wp_query->query_vars['p']    = $rewrite->object_id;
		$wp_query->query_vars['name'] = '';
		$wpdb->get_var( $wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_rewrites SET accessed=now() WHERE rewrite_id=%u RETURNING rewrite_id",$rewrite->rewrite_id));
	}
    }
}
add_action( 'parse_query', 'carton_parse_query' );

function carton_add_rewrite() {
    global $post, $wpdb;

    if( is_product() ) {
	$uri = strtolower( preg_replace( '/\/?$/', '', str_replace( network_site_url( '/' ), '', get_permalink( $post->ID ) ) ) );
	$wpdb->get_var( $wpdb->prepare("
	    INSERT INTO {$wpdb->prefix}woocommerce_rewrites (object_id, uri, object_type)
	    SELECT * FROM (
		SELECT %u::bigint AS object_id, %s::text AS uri, %s::text AS object_type
	    ) n
	    WHERE NOT EXISTS (
		SELECT 1 FROM  {$wpdb->prefix}woocommerce_rewrites o WHERE o.uri=n.uri AND o.object_type=n.object_type
	    )",
	    $post->ID,
	    $uri,
	    'product'
	));
    }
}
add_action( 'woocommerce_before_single_product', 'carton_add_rewrite' );

?>
