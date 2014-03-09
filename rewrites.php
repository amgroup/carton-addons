<?php
/*
Plugin Name: CartOn Rewrite Rules
Description: This plugin helps to get page via old changed permalink
Version: 1.0
Author: Andrei Guriev
Author URI: http://carton-ecommerce.com
*/

function carton_log_rewrite() {
    global $wpdb;

    $uri = $_SERVER["HTTP_REFERER"];
    $uri = preg_replace( '/https?\:\/\//i', '', $uri );
    $uri = strtolower( str_replace( $_SERVER["SERVER_NAME"], '', $uri ) );

    $ip      = $_SERVER["HTTP_X_FORWARDED_FOR"] ? $_SERVER["HTTP_X_FORWARDED_FOR"] : ["HTTP_X_REAL_IP"];
    $agent   = $_SERVER["HTTP_USER_AGENT"];

    if ( preg_match ( '/\?/', $uri ) )
        list($uri, $params) = split('\?', $uri );

    $rewrite_id = $wpdb->get_var( $wpdb->prepare("SELECT rewrite_id FROM {$wpdb->prefix}woocommerce_rewrites WHERE uri = %s", $uri ) );
    if( $rewrite_id ) {
        $rewrite_id = $wpdb->get_results( $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}woocommerce_rewrites_log (rewrite_id,ip,agent,server,uri,referer) VALUES ( %u,%s,%s,%s,%s,%s )",
            $rewrite_id,$ip,$agent,$_SERVER["SERVER_NAME"],$uri,$_POST["referrer"]
        ));
    }
}
add_action( 'wp_ajax_nopriv_analytics_counter', 'carton_log_rewrite' );
add_action( 'wp_ajax_analytics_counter', 'carton_log_rewrite' );


function carton_log_rewrite_js_code() {
    echo '<script type="text/javascript">jQuery.ajax({ url: "/wp-admin/admin-ajax.php", type: "POST", data: {referrer:document.referrer,action:"analytics_counter"}} );</script>';
}
add_action('wp_footer', 'carton_log_rewrite_js_code');

function carton_get_rewrite_uri() {
    global $wpdb;

    $params = '';
    $uri    = '/';


    // against incorrect urls like "/%25d1%2588%25d0%25ba..."
    if ( preg_match_all ( '/%25/', $_SERVER['REQUEST_URI'] ) > 4 )
        $_SERVER['REQUEST_URI'] = urldecode( $_SERVER['REQUEST_URI'] );

    if ( preg_match ( '/\?/', $_SERVER['REQUEST_URI'] ) ) {
        list($uri, $params) = split('\?', $_SERVER['REQUEST_URI'] );
    } else {
        $uri = $_SERVER['REQUEST_URI'];
    }

    $rewrite_id = $wpdb->get_var( $wpdb->prepare("SELECT rewrite_id FROM {$wpdb->prefix}woocommerce_rewrites WHERE uri = %s", strtolower($uri) ) );

    if( $rewrite_id ) {
        $wpdb->get_var( $wpdb->prepare("UPDATE {$wpdb->prefix}woocommerce_rewrites SET accessed = now()::timestamp WHERE rewrite_id = %u", $rewrite_id));
        $rewrite = $wpdb->get_var( $wpdb->prepare("
            SELECT
                r.uri
            FROM {$wpdb->prefix}woocommerce_rewrites i, {$wpdb->prefix}woocommerce_rewrites r
            WHERE i.rewrite_id = %u AND r.object_id = i.object_id AND r.slug = i.slug
            ORDER BY r.created DESC
            LIMIT 1;",
            $rewrite_id)
        );

        if( $rewrite ) {
            if( $params ) {
                $_SERVER['REQUEST_URI'] = $rewrite . '?' . $params;
            } else {
                $_SERVER['REQUEST_URI'] = $rewrite;
            }
        }
    }
}
add_action( 'init', 'carton_get_rewrite_uri', 1 );


function carton_set_rewrite_uri() {
    global $wpdb, $post;

    // store only dry uri
    if ( preg_match ( '/\?/', $_SERVER['REQUEST_URI'] ) ) return;

    $uri = strtolower( $_SERVER['REQUEST_URI'] );
    $object_id = null;
    $slug      = null;

    if( is_product_category() ) {
	$page      = get_query_var('paged');
	$slug      = 'product_cat' . ( $page ? '/page/' . get_query_var('paged') : '' );
	$object_id = get_queried_object()->term_id;
    } else if ( is_product() ) {
	$slug      = 'product';
	$object_id = $post->ID;
    }

    if( $object_id )
    	carton_make_rewrite( $object_id, $slug, $uri );
}
add_action( 'get_header', 'carton_set_rewrite_uri' );

function carton_add_rewrite_rules() {
    global $wp_rewrite;

	//add_rewrite_rule('(shop)/([^/]+)/([^/]+)/?$', 'index.php?name=$matches[3]&post_type=product', 'top' );
    add_rewrite_rule('(ru)/([^/]+)/?$', 'index.php?name=$matches[2]&post_type=product', 'top' );
    flush_rewrite_rules();
}
add_action('init', 'carton_add_rewrite_rules' );


// Admin functions


function carton_make_all_rewrites() {
	global $wpdb, $wp_rewrite;

	$posts_per_page = get_option('posts_per_page');

	// Saving terms
	$terms = $wpdb->get_results("
		SELECT
		  t.*,
		  tt.taxonomy,
		  count(tr.object_id) AS posts
		FROM
		  $wpdb->terms  t,
		  $wpdb->term_taxonomy tt
		JOIN $wpdb->term_relationships tr USING (term_taxonomy_id)    
		WHERE
		  t.term_id = tt.term_id
		GROUP BY
		  t.term_id,
		  tt.taxonomy");
	foreach( $terms as $term ) {
		$taxonomy = $term->taxonomy;
		$termlink = $wp_rewrite->get_extra_permastruct($taxonomy);
		$slug     = $term->slug;
		$t        = get_taxonomy($taxonomy);

		if ( empty($termlink) ) {
			if ( 'category' == $taxonomy )
				$termlink = '?cat=' . $term->term_id;
			elseif ( $t->query_var )
				$termlink = "?$t->query_var=$slug";
			else
				$termlink = "?taxonomy=$taxonomy&term=$slug";
		} else {
			if ( $t->rewrite['hierarchical'] ) {
				$hierarchical_slugs = array();
				$ancestors = get_ancestors($term->term_id, $taxonomy);
				foreach ( (array)$ancestors as $ancestor ) {
					$ancestor_term = get_term($ancestor, $taxonomy);
					$hierarchical_slugs[] = $ancestor_term->slug;
				}
				$hierarchical_slugs = array_reverse($hierarchical_slugs);
				$hierarchical_slugs[] = $slug;
				$termlink = str_replace("%$taxonomy%", implode('/', $hierarchical_slugs), $termlink);
			} else {
				$termlink = str_replace("%$taxonomy%", $slug, $termlink);
			}
		}

		if( preg_match( '/^\?/', $termlink) )
			continue;

		$uri   = strtolower( str_replace( 'http:/', '', esc_url($termlink) ). '/' );
		$fr    = ($term->posts % $posts_per_page);
		$pages = ((int) ( ($term->posts - $fr) / $posts_per_page) ) + ( $fr ? 1 : 0 );

		carton_make_rewrite( $term->term_id, $term->taxonomy, $uri );
		for( $p = 2; $p < $pages; $p++ ) {
			$pagenum = 'page/' . $p;
			carton_make_rewrite( $term->term_id, $term->taxonomy . '/'. $pagenum , $uri . $pagenum . '/' );
		}

	}

	// Savnig posts
	$posts = $wpdb->get_results("
		SELECT
		  \"ID\",
		  post_type
		FROM
		  $wpdb->posts
		WHERE
		  post_status='publish' AND
		  post_type IN ('product','page','post')"
	);
	$host = home_url();
	foreach( $posts as $post ) {
		$uri = strtolower( str_replace( $host, '', get_permalink( $post->ID ) ) );
		if( $uri && $uri != '/' )
			carton_make_rewrite( $post->ID, $post->post_type, $uri );
	}

}


function carton_make_all_rewrites_on_submit () {
	if ( ! is_admin() )
		return;

	if ( isset( $_POST['make_rewrites'] ) )
		carton_make_all_rewrites();
}
add_action( 'woocommerce_init', 'carton_make_all_rewrites_on_submit' );

function carton_make_rewrite( $object_id, $slug, $uri ) {
	global $wpdb;

	$wpdb->get_var( $wpdb->prepare("
	    INSERT INTO {$wpdb->prefix}woocommerce_rewrites (object_id, slug, uri)
	    SELECT object_id, slug, uri FROM (
		SELECT %s::text AS object_id, %s::text AS slug, %s::text AS uri
	    ) n
	    WHERE NOT EXISTS (
		SELECT 1 FROM  {$wpdb->prefix}woocommerce_rewrites o WHERE o.uri=n.uri AND o.object_id=n.object_id AND o.slug=n.slug
	    )",
	    $object_id,
	    $slug,
	    $uri
	));
}


function carton_rewrites_settings_init() {
	//add_filter( 'pre_update_option_' . $option, $newvalue, $oldvalue );
	add_settings_section( 'carton-rewrites', __( 'Before and after permalink changes', 'woocommerce' ), 'carton_rewrites_settings', 'permalink' );
}
add_action( 'admin_init', 'carton_rewrites_settings_init' );

function carton_rewrites_settings() {
	echo wpautop( __( 'Before and after permalink structure changes <strong>please press a button below</strong> to save old or create rewrite links', 'woocommerce' ) );
	echo '<input type="submit" name="make_rewrites" id="make_rewrites" class="button button-primary" value="Save All Products and Other Links">';
}


// Autosave Changes
/*
function carton_make_all_rewrites_before_permalink_changes( $newvalue, $oldvalue ) {
	if( $newvalue != $oldvalue )
		carton_make_all_rewrites();
	return $newvalue;
}
add_filter( 'pre_update_option_woocommerce_permalinks', 'carton_make_all_rewrites_before_permalink_changes' );


function carton_make_all_rewrites_after_permalink_changes( $newvalue, $oldvalue ) {
	if( $newvalue != $oldvalue )
		carton_make_all_rewrites();
	return $newvalue;
}
add_action( 'update_option_woocommerce_permalinks', 'carton_make_all_rewrites_after_permalink_changes' );
*/

?>
