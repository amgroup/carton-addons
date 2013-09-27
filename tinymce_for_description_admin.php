<?php
/*
Plugin Name: Tinymce for Taxonomy Description Textarea
Plugin URI: http://carton-ecommerce.com
Description: Adds a tinymce editor to the category description and taxonomy description textarea. Disables HTML filtering on it.
Version: 1.0
Author: Andrew Guryev (RU)
Author URI: http://carton-ecommerce.com

License: GPL
*/
/*  Copyright 2013  Andrew Guryev  (email: andrey@kidberries.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Remove the html filtering
remove_filter( 'pre_term_description', 'wp_filter_kses' );
remove_filter( 'term_description',     'wp_kses_data' );

// Add filter to required actions
foreach ( array( 'edit_tag_form_fields', 'edit_category_form_fields', 'add_tag_form' ) as $filter ) {
	add_filter( $filter, 'refine_description');
}

function refine_description($tag) {
	?>
<div id="tinymce-description" style="display: none;">
	<?php
	$settings = array( 'textarea_rows' => get_option('default_post_edit_rows'), 'textarea_name' => 'description' );
	wp_editor( html_entity_decode( $tag->description ), 'tinymce-description-editor', $settings );
	?>
  <br/>
  <span class="description"><?php _e('The description is not prominent by default, however some themes may show it.', 'woocommerce'); ?></span>
</div>
	<?php
}

// Use jquery to refine the default tag description textarea
function refine_description_js() {
	global $current_screen;
	if ( $current_screen->id == 'edit-'.$current_screen->taxonomy ) {
?>
<style>.form-field span { margin:0; }</style>
<script type="text/javascript">
  jQuery(document).ready(function() {
    var cont  = jQuery('textarea#description,textarea#tag-description').closest('td,div');
    var descr = jQuery('#tinymce-description');
    cont.empty().append( descr.html() );
    descr.remove();
  });
</script>
<?php
	}
}
// 
add_action('admin_head', 'refine_description_js');
?>