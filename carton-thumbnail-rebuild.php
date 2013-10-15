<?php
/* Plugin name: CartOn Thumbnail Rebuild (AJAX based)
   Plugin URI: http://kidberries.com
   Author: Kidberries TEAM
   Author URI: http://kidberries.com
   Version: 1.08
   Description: Rebuild all thumbnails
   Max WP Version: 3.2.1
   Text Domain: ajax-thumbnail-rebuild

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
define( 'CARTON_THUMBNAIL_REBUILD_DIR', plugin_dir_path(__FILE__) );
define( 'CARTON_THUMBNAIL_REBUILD_CLASSES_DIR', CARTON_THUMBNAIL_REBUILD_DIR . 'carton/classes/' );

function append_thumbnail_rebuild_plugin() {
    global $AjaxThumbnailRebuild;

    load_plugin_textdomain('ajax-thumbnail-rebuild', false, CARTON_THUMBNAIL_REBUILD_DIR . 'carton/languages' );
    include_once( CARTON_THUMBNAIL_REBUILD_CLASSES_DIR . 'class-carton-thumbnail-rebuild.php' );

    $AjaxThumbnailRebuild = new AjaxThumbnailRebuild();
}
add_action('plugins_loaded', 'append_thumbnail_rebuild_plugin' );


function carton_extend_imagick_editor( $editors ) {

    include_once( CARTON_THUMBNAIL_REBUILD_CLASSES_DIR . 'class-carton-image-editor-imagick-extd.php' );

    $editors = array( 'WP_Image_Editor_Imagick_Extd', 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' );
    return $editors;
}
add_filter( 'wp_image_editors', 'carton_extend_imagick_editor', 10, 1 );


function ajax_thumbnail_rebuild_ajax() {
	global $wpdb;

	$action = $_POST["do"];
	$thumbnails = isset( $_POST['thumbnails'] )? $_POST['thumbnails'] : NULL;
	$onlyfeatured = isset( $_POST['onlyfeatured'] ) ? $_POST['onlyfeatured'] : 0;

	if ($action == "getlist") {

		if ($onlyfeatured) {
			/* Get all featured images */
			$featured_images = $wpdb->get_results( "SELECT meta_value,{$wpdb->posts}.post_title AS title FROM {$wpdb->postmeta}, {$wpdb->posts}
		                                        WHERE meta_key = '_thumbnail_id' AND {$wpdb->postmeta}.post_id={$wpdb->posts}.ID");

			foreach($featured_images as $image) {
			    $res[] = array('id' => $image->meta_value, 'title' => $image->title);
			}
		} else {
			$attachments =& get_children( array(
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'numberposts' => -1,
				'post_status' => null,
				'post_parent' => null, // any parent
				'output' => 'object',
			) );
			foreach ( $attachments as $attachment ) {
			    $res[] = array('id' => $attachment->ID, 'title' => $attachment->post_title);
			}
		}

		die( json_encode($res) );
	} else if ($action == "regen") {
		$id = $_POST["id"];

		$fullsizepath = get_attached_file( $id );

		if ( FALSE !== $fullsizepath && @file_exists($fullsizepath) ) {
			set_time_limit( 30 );
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata_custom( $id, $fullsizepath, $thumbnails ) );
		}

		die( wp_get_attachment_thumb_url( $id ));
	}
}
add_action('wp_ajax_ajax_thumbnail_rebuild', 'ajax_thumbnail_rebuild_ajax');

function ajax_thumbnail_rebuild_get_sizes() {
	global $_wp_additional_image_sizes;

	foreach ( get_intermediate_image_sizes() as $s ) {
		$sizes[$s] = array( 'name' => '', 'width' => '', 'height' => '', 'crop' => FALSE );

		/* Read theme added sizes or fall back to default sizes set in options... */

		$sizes[$s]['name'] = $s;

		if ( isset( $_wp_additional_image_sizes[$s]['width'] ) )
			$sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] ); 
		else
			$sizes[$s]['width'] = get_option( "{$s}_size_w" );

		if ( isset( $_wp_additional_image_sizes[$s]['height'] ) )
			$sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] );
		else
			$sizes[$s]['height'] = get_option( "{$s}_size_h" );

		if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )
			$sizes[$s]['crop'] = intval( $_wp_additional_image_sizes[$s]['crop'] );
		else
			$sizes[$s]['crop'] = get_option( "{$s}_crop" );
	}

	return $sizes;
}

/**
 * Generate post thumbnail attachment meta data.
 *
 * @since 2.1.0
 *
 * @param int $attachment_id Attachment Id to process.
 * @param string $file Filepath of the Attached image.
 * @return mixed Metadata for attachment.
 */
function wp_generate_attachment_metadata_custom( $attachment_id, $file, $thumbnails = NULL ) {
	$attachment = get_post( $attachment_id );

	$metadata = array();
	if ( preg_match('!^image/!', get_post_mime_type( $attachment )) && file_is_displayable_image($file) ) {
		$imagesize = getimagesize( $file );
		$metadata['width'] = $imagesize[0];
		$metadata['height'] = $imagesize[1];
		list($uwidth, $uheight) = wp_constrain_dimensions($metadata['width'], $metadata['height'], 128, 96);
		$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";

		// Make the file path relative to the upload dir
		$metadata['file'] = _wp_relative_upload_path($file);

		$sizes = ajax_thumbnail_rebuild_get_sizes();
		$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes );

		foreach ($sizes as $size => $size_data ) {
			if( isset( $thumbnails ) && !in_array( $size, $thumbnails ))
				$intermediate_size = image_get_intermediate_size( $attachment_id, $size_data['name'] );
			else
				$intermediate_size = image_make_intermediate_size( $file, $size_data['width'], $size_data['height'], $size_data['crop'] );

			if ($intermediate_size)
				$metadata['sizes'][$size] = $intermediate_size;
		}

		// fetch additional metadata from exif/iptc
		$image_meta = wp_read_image_metadata( $file );
		if ( $image_meta )
			$metadata['image_meta'] = $image_meta;

	}

	return apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id );
}

?>
