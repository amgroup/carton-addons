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

class AjaxThumbnailRebuild {

	function AjaxThumbnailRebuild() {
		add_action( 'admin_menu', array(&$this, 'addAdminMenu') );
	}

	function addAdminMenu() {
		add_management_page( __( 'Rebuild all Thumbnails', 'ajax-thumbnail-rebuild' ), __( 'Rebuild Thumbnails', 'ajax-thumbnail-rebuild'
 ), 'manage_options', 'ajax-thumbnail-rebuild', array(&$this, 'ManagementPage') );
	}

	function ManagementPage() {
		?>
		<div id="message" class="updated fade" style="display:none"></div>
		<script type="text/javascript">
		// <![CDATA[

		function setMessage(msg) {
			jQuery("#message").html(msg);
			jQuery("#message").show();
		}

		function regenerate() {
			jQuery("#ajax_thumbnail_rebuild").attr("disabled", true);
			setMessage("<p><?php _e('Reading attachments...', 'ajax-thumbnail-rebuild') ?></p>");

			inputs = jQuery( 'input:checked' );
			var thumbnails= '';
			if( inputs.length != jQuery( 'input[type=checkbox]' ).length ){
				inputs.each( function(){
					thumbnails += '&thumbnails[]='+jQuery(this).val();
				} );
			}

			var onlyfeatured = jQuery("#onlyfeatured").attr('checked') ? 1 : 0;

			jQuery.ajax({
				url: "<?php echo admin_url('admin-ajax.php'); ?>",
				type: "POST",
				data: "action=ajax_thumbnail_rebuild&do=getlist&onlyfeatured="+onlyfeatured,
				success: function(result) {
					var list = eval(result);
					var curr = 0;

					if (!list) {
						setMessage("<?php _e('No attachments found.', 'ajax-thumbnail-rebuild')?>");
						jQuery("#ajax_thumbnail_rebuild").removeAttr("disabled");
						return;
					}

					function regenItem() {
						if (curr >= list.length) {
							jQuery("#ajax_thumbnail_rebuild").removeAttr("disabled");
							setMessage("<?php _e('Done.', 'ajax-thumbnail-rebuild') ?>");
							return;
						}
						setMessage(<?php printf( __('"Rebuilding " + %s + " of " + %s + " (" + %s + ")..."', 'ajax-thumbnail-rebuild'), "(curr+1)", "list.length", "list[curr].title"); ?>);

						jQuery.ajax({
							url: "<?php echo admin_url('admin-ajax.php'); ?>",
							type: "POST",
							data: "action=ajax_thumbnail_rebuild&do=regen&id=" + list[curr].id + thumbnails,
							success: function(result) {
								jQuery("#thumb").show();
								jQuery("#thumb-img").attr("src",result);

								curr = curr + 1;
								regenItem();
							},
                            error: function(result) {
								jQuery("#thumb").show();
								jQuery("#thumb-img").attr("src",result);
								regenItem();
							}
						});
					}

					regenItem();
				},
				error: function(request, status, error) {
					setMessage("<?php _e('Error', 'ajax-thumbnail-rebuild') ?>" + request.status);
				}
			});
		}

		jQuery(document).ready(function() {
			jQuery('#size-toggle').click(function() {
				jQuery("#sizeselect").find("input[type=checkbox]").each(function() {
					jQuery(this).attr("checked", !jQuery(this).attr("checked"));
				});
			});
		});

		// ]]>
		</script>

		<form method="post" action="" style="display:inline; float:left; padding-right:30px;">
		    <h4><?php _e('Select which thumbnails you want to rebuild', 'ajax-thumbnail-rebuild'); ?>:</h4>
			<a href="javascript:void(0);" id="size-toggle"><?php _e('Toggle all', 'ajax-thumbnail-rebuild'); ?></a>
			<div id="sizeselect">
			<?php
			foreach ( ajax_thumbnail_rebuild_get_sizes() as $s ):
			?>

				<input type="checkbox" name="thumbnails[]" id="sizeselect" checked="checked" value="<?php echo $s['name'] ?>" />
				<label>
					<em><?php echo $s['name'] ?></em>
					&nbsp;(<?php echo $s['width'] ?>x<?php echo $s['height'] ?>
					<?php if ($s['crop']) _e('cropped', 'ajax-thumbnail-rebuild'); ?>)
				</label>
				<br/>
			<?php endforeach;?>
			</div>
			<p>
				<input type="checkbox" id="onlyfeatured" name="onlyfeatured" />
				<label><?php _e('Only rebuild featured images', 'ajax-thumbnail-rebuild'); ?></label>
			</p>

			<p><?php _e("Note: If you've changed the dimensions of your thumbnails, existing thumbnail images will not be deleted.",
			'ajax-thumbnail-rebuild'); ?></p>
			<input type="button" onClick="javascript:regenerate();" class="button"
			       name="ajax_thumbnail_rebuild" id="ajax_thumbnail_rebuild"
			       value="<?php _e( 'Rebuild All Thumbnails', 'ajax-thumbnail-rebuild' ) ?>" />
			<br />
		</form>

		<div id="thumb" style="display:none;"><h4><?php _e('Last image', 'ajax-thumbnail-rebuild'); ?>:</h4><img id="thumb-img" /></div>

		<p style="clear:both; padding-top:2em;">
		<?php printf( __("If you find this plugin useful, I'd be happy to read your comments on the %splugin homepage%s. If you experience any problems, feel free to leave a comment too.",
				 'ajax-thumbnail-rebuild'),
			         '<a href="http://breiti.cc/wordpress/ajax-thumbnail-rebuild" target="_blank">', '</a>');
		?>
		</p>
		<?php
	}
}
?>
