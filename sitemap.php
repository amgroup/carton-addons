<?php
/*
Plugin Name: CartOn Sitemap Creator
Description: This plugin creates sitemap.xml
Version: 1.0
Author: Andrei Guriev
Author URI: http://carton-ecommerce.com
*/

function carton_sitemap_settings_init() {
	add_management_page( __( 'Sitemap', 'carton-sitemap-rebuild' ), __( 'Sitemap', 'carton-sitemap-rebuild'
 ), 'manage_options', 'carton-sitemap-rebuild', 'carton_sitemap_rebuild_page' );
}
add_action( 'admin_menu', 'carton_sitemap_settings_init' );

function carton_sitemap_rebuild_page() {
?>
<div class="wrap">
	<div id="icon-tools" class="icon32"><br/></div>
	<h2>Sitemap</h2>

	<p>Create Sitemap.xml</p>
	<form method="post" >
		<input type="submit" class="button" name="carton_sitemap_create" id="carton_sitemap_create" value="Rebuild Sitemap">
	</form>
</div>
<?php
}

function carton_make_sitemap_on_submit () {
	if ( ! is_admin() )
		return;

	if ( isset( $_POST['carton_sitemap_create'] ) ) {
		$xml  = carton_make_sitemap();
		$file = ABSPATH . 'sitemap.xml';
		unlink( $file );
		$OUT = fopen( $file, 'w' );
		fwrite( $OUT, $xml );
		fclose( $OUT );
	}
}
add_action( 'woocommerce_init', 'carton_make_sitemap_on_submit' );


function carton_make_sitemap() {
	global $wpdb, $wp_rewrite;
	
	$urlset  = '<?xml version="1.0" encoding="utf-8" ?>' . "\n";
	$urlset .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";


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
		  t.term_id = tt.term_id AND
		  tt.taxonomy IN ('category', 'product_cat','nav_menu')
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

		$urlset .= carton_sitemap_new_url( $term->term_id, $term->taxonomy, $uri );
		for( $p = 2; $p < $pages; $p++ ) {
			$pagenum = 'page/' . $p;
			$urlset .= carton_sitemap_new_url( $term->term_id, $term->taxonomy . '/'. $pagenum , $uri . $pagenum . '/' );
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
			$urlset .= carton_sitemap_new_url( $post->ID, $post->post_type, $uri );
	}
	$urlset .= '</urlset>';
	return $urlset;
}

function carton_sitemap_new_url( $object_id, $slug, $uri ) {
	global $wpdb;
	$lastmod = $wpdb->get_var($wpdb->prepare("
		SELECT to_char(accessed,'yyyy-mm-ddThh24:mi:ss.SSSS') || to_char(EXTRACT(timezone_hour FROM accessed),'s09') || ':' || to_char(EXTRACT(timezone_min FROM accessed),'FM09')
		FROM (
			SELECT accessed, 0::integer AS rate FROM {$wpdb->prefix}woocommerce_rewrites WHERE object_id=%u AND slug=%s AND uri=%s
			UNION
			SELECT now() AS accessed, 1::integer AS rate
		) d
		ORDER BY rate
		LIMIT 1;
		", $object_id, $slug, $uri
	));
	return "<url><loc>" . home_url($uri) . "</loc><lastmod>" . str_replace(' ', 'T', $lastmod) . "</lastmod></url>\n";
}

?>