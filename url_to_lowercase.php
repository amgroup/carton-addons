<?php
/*
Plugin Name: CartOn Lowercase permalinks
Description: A plugin to make lowercase permalinks
Version: 1.0
Author: Andrey Guryev
Author URI: http://carton-ecommerce.com
*/

function array_strtolower($m=array()){return strtolower($m[0]);}
function array_strtoupper($m=array()){return strtoupper($m[0]);}

function correct_encoded_cyr_permalink ( $permalink ) {
    $permalink = utf8_uri_encode( rawurldecode( $permalink ) );
    $permalink = preg_replace_callback( "/(%[a-f0-9]{2})+/", 'array_strtoupper', $permalink );
    return $permalink;
}

foreach( array( 'clean_url', 'page_link', 'post_link', 'term_link', 'home_url' ) as $filter ) {
    add_filter($filter, 'correct_encoded_cyr_permalink' );
}

?>
