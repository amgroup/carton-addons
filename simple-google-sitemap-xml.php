<?php
/*
Plugin Name: Simple Google Sitemap XML
Version:     1.5.0
Plugin URI:  http://itx-technologies.com/blog/simple-google-sitemap-xml-for-wordpress
Description: Generates a valid Google XML sitemap with a very simple admin interface
Author:      iTx Technologies
Author URI:  http://itx-technologies.com/
*/

if (!defined('ABSPATH')) die("Aren't you supposed to come here via WP-Admin?");

//Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
  define( 'WP_CONTENT_URL', get_bloginfo( 'wpurl' ) . '/wp-content' );

if ( ! defined( 'WP_CONTENT_DIR' ) )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

if ( ! defined( 'WP_PLUGIN_URL' ) )
    define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );

if ( ! defined( 'WP_PLUGIN_DIR' ) )
    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

/*
Genarates the actual XML sitemap file on the server
*/
function sitemap_generate_xmlsitemap() {
     
    $filename = "sitemap.xml";

    unlink( WP_PLUGIN_DIR.'/simple-google-sitemap-xml/'.$filename );
    unlink( ABSPATH . $filename );

    if (get_option('sitemap_store') == "1") {
        $file_handler = fopen(WP_PLUGIN_DIR.'/simple-google-sitemap-xml/'.$filename, "w+");
    } 

    elseif (get_option('sitemap_store') == "2") {
        $file_handler = fopen(ABSPATH.$filename, "w+");
    } else {
        $file_handler = fopen(WP_PLUGIN_DIR.'/simple-google-sitemap-xml/'.$filename, "w+");
    }


    if (!$file_handler) {
        die;
    } else {
        $content = sitemap_get_content();
        fwrite($file_handler, $content);
        fclose($file_handler);
    }
}

/*
Gets the content of the database and formats it to form valid XML
*/
function sitemap_get_content() {
    global $wpdb, $woocommerce;
    
    /* Setting default values for the settings */
    if (get_option('sitemap_hp')) { $home_p = get_option('sitemap_hp'); } else { $home_p = 0.5;}
    if (get_option('sitemap_hf')) { $home_f = get_option('sitemap_hf'); } else { $home_f = 'weekly';}
    if (get_option('sitemap_gp')) { $other_p = get_option('sitemap_gp'); } else { $other_p = 0.5;}
    if (get_option('sitemap_gf')) { $other_f = get_option('sitemap_gf'); } else { $other_f = 'weekly';}
    if (get_option('sitemap_pri_freq')) { $sitemap_pri_freq = get_option('sitemap_pri_freq');} else { $sitemap_pri_freq = "Disable"; }
    if (!get_option('sitemap_cat')) { $sitemap_cat = 'NotInclude';} else {	$sitemap_cat = get_option('sitemap_cat'); }
    if (!get_option('sitemap_tag')) { $sitemap_tag = 'NotInclude';} else {	$sitemap_tag = get_option('sitemap_tag'); }
    if (!get_option('sitemap_last_ch')) { $sitemap_last_ch = 'Disable';} else {	$sitemap_last_ch = get_option('sitemap_last_ch'); }
    
    // XML's header
    $xmlcontent =  '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xmlcontent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
     
    // Query for the posts 
    $query = "
        SELECT
            post_modified::date,
            \"ID\",
            post_title,
            post_name,
            post_type,
            post_parent
        FROM {$wpdb->posts}
        WHERE post_status = 'publish'
            AND post_type IN ( 'page', 'product' )
        ORDER BY post_date DESC
    ";
    $myrows = $wpdb->get_results($query);

    $max_date = '0000-00-00';
    $products = 0;

    foreach ($myrows as $myrow) {
	$products++;

        $permalink = utf8_encode($myrow->post_name);
        $type      = $myrow->post_type;
        $date      = $myrow->post_modified;

	if( strcmp( $max_date, $date ) < 0 )
	    $max_date = $date;

        $id  = $myrow->ID;
        $url = get_permalink( $id );
        $xmlcontent .= "<url><loc>".utfurltoupper($url)."</loc>";

        if ($sitemap_last_ch == 'Enable')
            $xmlcontent .= "<lastmod>" . $date . "</lastmod>";

        // verify that priority + change frequency have been enabled
        if ($sitemap_pri_freq == 'Enable') {
            $xmlcontent .= "<changefreq>" . $other_f . "</changefreq><priority>". $other_p . "</priority>";
        }

        $xmlcontent .= "</url>\n";
    }


    // Menu items
    $args = array(
      'theme_location'  => 'top',
      'menu'            => '',
      'container'       => 'div',
      'container_class' => 'nav-container',
      'container_id'    => '',
      'menu_class'      => '',
      'menu_id'         => 'nav',
      'echo'            => false,
      'fallback_cb'     => 'wp_page_menu',
      'before'          => '',
      'after'           => '',
      'link_before'     => '<span>',
      'link_after'      => '</span>',
      'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
      'depth'           => 0
    );

    $links = (array) simplexml_load_string( wp_nav_menu( $args ) )->xpath('//a[@href]/@href');


    // If categories have been enabled, include them in the XML
    if ($sitemap_cat == 'Include') {
        // Prepare the SQL query
        $query = "
            SELECT
                tt.term_id,
                t.slug,
                tt.parent
            FROM
                {$wpdb->terms} t,
                {$wpdb->term_taxonomy} tt
            WHERE
                t.term_id = tt.term_id
            AND tt.taxonomy = 'product_cat'
        ";
        $mycats  = $wpdb->get_results($query); 
        $siteurl = get_bloginfo("url");

        //  Output each category link with the date being when it 
        $date = date('Y-m-d');
        $cat  = array();
        foreach ($mycats as $mycat)
            $cat[ $mycat->term_id ] = $mycat->slug;

        foreach ($mycats as $mycat) {
            $anch = get_ancestors( $mycat->term_id, 'product_cat' );
            $anch = array_reverse( $anch );
            $anch[] = $mycat->term_id;
            
            $url = $siteurl . "/товарная-категория/";
            foreach( $anch as $id )
                $url .= $cat[ $id ] . '/';

	    $links[] = $url;
	    
        }
    }
    
    $links[] = get_bloginfo("url") . '/';
    $links = array_unique($links);

    foreach($links as $url) {
        $xmlcontent .= "<url><loc>" . urlencode_gr($url) . "</loc>";

        if ($sitemap_last_ch == 'Enable')
            $xmlcontent .= "<lastmod>" . $max_date . "</lastmod>";

        // verify that priority + change frequency have been enabled
        if ($sitemap_pri_freq == 'Enable') {
            $xmlcontent .= "<changefreq>" . $other_f . "</changefreq><priority>". $other_p . "</priority>";
        }
        $xmlcontent .= "</url>\n";

	for( $p=1; $p < $products/20; $p++) {
            $xmlcontent .= "<url><loc>" . urlencode_gr($url) . "page/$p/</loc>";

            if ($sitemap_last_ch == 'Enable')
                $xmlcontent .= "<lastmod>" . $max_date . "</lastmod>";

            // verify that priority + change frequency have been enabled
            if ($sitemap_pri_freq == 'Enable') {
                $xmlcontent .= "<changefreq>" . $other_f . "</changefreq><priority>". $other_p . "</priority>";
            }
            $xmlcontent .= "</url>\n";
        }
    }

     // end of the XML sitemap </urlset>
    $xmlcontent .= '</urlset>'."\n";

    return $xmlcontent ;

}


function urlencode_gr ($s='') {
    $s = preg_replace_callback('/([^\/:]+)/', create_function('$m','return urlencode($m[0]);'), $s );
    $s = utfurltoupper( $s );
    return $s;
}


function utfurltoupper ($s='') {
    $s = preg_replace_callback('/(%[a-f0-9]{2})+/i', create_function('$m','return strtoupper($m[0]);'), $s );
    return $s;
}


/*
Creates an admin link
*/
function sitemap_menu_link() {
  if (function_exists('add_options_page')) {
    $sitemap_page = add_options_page('Google Sitemap XML', 'Google Sitemap XML', 'administrator', basename(__FILE__), 'sitemap_settings');
  }
}


/*
Admin setting page
*/
function sitemap_settings() { 

if (get_option('sitemap_store') == "1") { $path = WP_PLUGIN_URL.'/simple-google-sitemap-xml/sitemap.xml';  } 

elseif (get_option('sitemap_store') == "2") { $path = get_option( 'siteurl' ).'/sitemap.xml'; }
else {	$path = WP_PLUGIN_URL.'/simple-google-sitemap-xml/sitemap.xml';  }


?>
  <h2>Google Sitemap XML</h2>
     <h4>by <a style="color: #A30B06;" href="http://itx-technologies.com" target="_blank">iTx Technologies</a></h4>
     <div style="float:left;"><?php sitemap_paypal_donate();?></div>
     <div style="clear:both;"></div>
     <div style="margin: 20px 0 0 0;">
     <h3 style="color: #A30B06; margin-bottom: 0;">Your XML Sitemap</h3>
     <p style="margin:0 0 1em 0;">
     This is the absolute URL of your XML sitemap.  You can copy/paste it in <a href="https://www.google.com/webmasters/tools/" target="_blank">Google Webmaster Tools</a> which greatly increases the speed at which Google indexes your website.  
     <br /><br />
     <strong>The XML sitemap is automatically regenerated when you publish or delete a new post/page.</strong>
     </p>
     <form method="post" action="options.php">
     <?php wp_nonce_field('update-options'); ?>
     <table>
     <tr>
     <td>Where do you want to store your XML file ?</td>
     <td>
     <select name="sitemap_store" id="sitemap_store" type="text" value="<?php echo get_option('sitemap_store'); ?>" />
     <option value="1" <?php if (get_option('sitemap_store') == "1") { echo "selected"; } ?> >In the plugin's folder</option>
     <option value="2" <?php if (get_option('sitemap_store') == "2") { echo "selected"; } ?> >In my website's root folder</option>
     </select>
     </td>
     </tr>
     </table>
     
     <table>
     <tr>
     <td>Your XML absolute URL:</td><td style="background-color: white; padding: 5px;"><?php echo $path; ?></td>
     </tr>
     </table>
     
     
     <h3 style="color: #A30B06; margin-bottom: 0;">Parameters</h3>
     <p style="margin:0 0 1em 0;">
     You can slightly tweak your XML sitemap as described in the <a href="http://sitemaps.org/protocol.php" target="_blank">Sitemaps XML Protocol</a>.<br /><br />
     The following parameters will be applied to the global XML sitemap.  In other words, you cannot choose different parameters for each and every post/page, except the homepage.
     </p>
     <p style="font-weight:bold;">Last changed</p>
     <table width="50%">
	  <tr>
	       <td width="80%">Do you want to enable the <strong>last changed</strong> attribute ?  It is set to <strong>disabled</strong> by default.</td>
	       <td>
		    <?php
			 // if the value for the frequency + priority have neve been set, set to Yes
			 if (!get_option('sitemap_last_ch')) { $sitemap_last_ch = 'Disable';}
			 else {	$sitemap_last_ch = get_option('sitemap_last_ch'); }
		    ?>
		    <select name="sitemap_last_ch" id="sitemap_hf" type="text" value="<?php echo $sitemap_last_ch ?>" />
		    <option value="Disable" <?php if($sitemap_last_ch=="Disable") {echo 'selected';}?>>disable</option>
		    <option value="Enable" <?php if($sitemap_last_ch=="Enable") {echo 'selected';}?>>enable</option>
		    </select>
	       </td>
	  </tr>
	  <tr>
	       <td>
		    If the attributes are <strong>enabled</strong>, you can set them here :
	       </td>
	  </tr>
     </table>
      <p style="font-weight:bold;">Attributes</p>
     <table width="50%">
	  <tr>
	       <td width="80%">Do you want to enable the <strong>priority</strong> and the <strong>change frequency</strong> attributes ?  It is set to <strong>disabled</strong> by default.</td>
	       <td>
		    <?php
			 // if the value for the frequency + priority have neve been set, set to Yes
			 if (!get_option('sitemap_pri_freq')) { $sitemap_pri_freq = 'Disable';}
			 else {	$sitemap_pri_freq = get_option('sitemap_pri_freq'); }
		    ?>
		    <select name="sitemap_pri_freq" id="sitemap_hf" type="text" value="<?php echo $sitemap_pri_freq ?>" />
		    <option value="Disable" <?php if($sitemap_pri_freq=="Disable") {echo 'selected';}?>>disable</option>
		    <option value="Enable" <?php if($sitemap_pri_freq=="Enable") {echo 'selected';}?>>enable</option>
		    </select>
	       </td>
	  </tr>
	  <tr>
	       <td>
		    If the attributes are <strong>enabled</strong>, you can set them here :
	       </td>
	  </tr>
     </table>
     <div style="margin-left: 10%;">
	  <table width="50%">
	  <tr>
	       <p style="font-weight:bold;">Homepage parameters</p>
	       <th width="150">Priority</th>
	       <td width="100">
		    <select name="sitemap_hp" id="sitemap_hp" type="text" value="<?php echo get_option('sitemap_hp'); ?>" />
		    <?php for ($i=0; $i<1.05; $i+=0.1) {
			 echo "<option value='".$i."' ";
			 if (get_option('sitemap_hp')==$i) {
			      echo ' selected';
			 } // end if
			 
			 echo ">";
			 if($i==0) { echo "0.".$i;} 
			 elseif($i==1.0) { echo $i.'0';} 
			 else {echo $i;}
			 echo "</option>";
		    } // end for
		    ?>
		    </select>
	       </td>
	       <th width="150">Frequency</th>
	       <td width="100">
		    <select name="sitemap_hf" id="sitemap_hf" type="text" value="<?php echo get_option('sitemap_hf'); ?>" />
		    <option value="always" <?php if(get_option('sitemap_hf')=="always") {echo 'selected';}?>>always</option>
		    <option value="hourly" <?php if(get_option('sitemap_hf')=="hourly") {echo 'selected';}?>>hourly</option>
		    <option value="weekly" <?php if(get_option('sitemap_hf')=="weekly") {echo 'selected';}?>>weekly</option>
		    <option value="monthly" <?php if(get_option('sitemap_hf')=="monthly") {echo 'selected';}?>>monhtly</option>
		    <option value="yearly" <?php if(get_option('sitemap_hf')=="yearly") {echo 'selected';}?>>yearly</option>
		    <option value="never"  <?php if(get_option('sitemap_hf')=="never") {echo 'selected';}?>>never</option>
		    </select>
		    </td>
	  </tr>
	  </table>
	  <table width="50%">
	  <tr>
	  <p style="font-weight:bold;">General parameters</p>
	  
	       <th width="150">Priority</th>
	       <td width="100">
	       <select name="sitemap_gp" id="sitemap_gp" type="text" value="<?php echo get_option('sitemap_gp'); ?>" />
		    <?php for ($i=0; $i<1.05; $i+=0.1) {
			 echo "<option value='".$i."' ";
			 if (get_option('sitemap_gp')==$i) {
			      echo ' selected';
			 } // end if
			 
			 echo ">";
			 if($i==0) { echo "0.".$i;} 
			 elseif($i==1.0) { echo $i.'0';} 
			 else {echo $i;}
			 echo "</option>";
		    } // end for
		    ?>
		    </select>
	       </td>
	       
	       <th width="150">Frequency</th>
	       <td width="100">
		    <select name="sitemap_gf" id="sitemap_gf" type="text" value="<?php echo get_option('sitemap_gf'); ?>" />
		    <option value="always" <?php if(get_option('sitemap_gf')=='always') {echo 'selected';}?>>always</option>
		    <option value="hourly" <?php if(get_option('sitemap_gf')=='hourly') {echo 'selected';}?>>hourly</option>
		    <option value="weekly" <?php if(get_option('sitemap_gf')=='weekly') {echo 'selected';}?>>weekly</option>
		    <option value="monthly" <?php if(get_option('sitemap_gf')=='monthly') {echo 'selected';}?>>monthly</option>
		    <option value="yearly" <?php if(get_option('sitemap_gf')=='yearly') {echo 'selected';}?>>yearly</option>
		    <option value="never" <?php if(get_option('sitemap_gf')=='never') {echo 'selected';}?>>never</option>
		    </select>
	       </td>
	  </tr>
	  </table>
     </div>
     <h3 style="color: #A30B06; margin-bottom: 0;">Categories and Tags</h3>
     <p style="margin:0 0 1em 0;">
	  Simple Google Sitemap XML nows lets you include the categories and tags of your website into your generated sitemap.xml.<br />Simply choose which of them (or both) you want to include below :
     </p>
     <p style="font-weight:bold;">Categories: &nbsp;&nbsp;
	  <?php
	       // if the value for the categories have neve been set, set to Yes
	       if (!get_option('sitemap_cat')) { $sitemap_cat = 'NotInclude';}
	       else {	$sitemap_cat = get_option('sitemap_cat'); }
	  ?>
	  <select name="sitemap_cat" id="sitemap_cat" type="text" value="<?php echo $sitemap_tag ?>" />
	  <option value="NotInclude" <?php if($sitemap_cat=="NotInclude") {echo 'selected';}?>>do not include</option>
	  <option value="Include" <?php if($sitemap_cat=="Include") {echo 'selected';}?>>include</option>
	  </select>
     </p>
     <p style="font-weight:bold;">Tags: &nbsp;&nbsp;
	  <?php
	       // if the value for the tags have neve been set, set to Yes
	       if (!get_option('sitemap_tag')) { $sitemap_tag = 'NotInclude';}
	       else {	$sitemap_tag = get_option('sitemap_tag'); }
	  ?>
	  <select name="sitemap_tag" id="sitemap_tag" type="text" value="<?php echo $sitemap_tag ?>" />
	  <option value="NotInclude" <?php if($sitemap_tag=="NotInclude") {echo 'selected';}?>>do not include</option>
	  <option value="Include" <?php if($sitemap_tag=="Include") {echo 'selected';}?>>include</option>
	  </select>
     </p>
     <!-- Update the values -->
     <input type="hidden" name="action" value="update" />
     <input type="hidden" name="page_options" value="sitemap_hp,sitemap_gp,sitemap_hf,sitemap_gf,sitemap_store,sitemap_pri_freq, sitemap_cat, sitemap_tag,sitemap_last_ch" />

     <p style="margin-top: 20px;">
     <input type="submit" value="<?php _e('Save Changes'); ?>" />
     </p>
     </div>
<?php

/* Generate the sitemap once the plugin is saved */
sitemap_generate_xmlsitemap();
}



/*
Paypal donate button
*/
function sitemap_paypal_donate() {
echo '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="5MTLLW5LKCGEN">
<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - la solution de paiement en ligne la plus simple et la plus sécurisée !">
<img alt="" border="0" src="https://www.paypal.com/fr_CA/i/scr/pixel.gif" width="1" height="1">
</form>';

}


if ( is_admin() ){
     add_action('admin_menu', 'sitemap_menu_link');
}

function activate_sitemap () {

     sitemap_generate_xmlsitemap();
}

//register_activation_hook(WP_PLUGIN_DIR.'/simple-google-sitemap-xml/simple-google-sitemap-xml.php', 'generate_xmlsitemap');
register_activation_hook(WP_PLUGIN_DIR.'/simple-google-sitemap-xml/simple-google-sitemap-xml.php','activate_sitemap');
add_action ( 'activate_plugin', 'sitemap_generate_xmlsitemap' );
add_action ( 'publish_post', 'sitemap_generate_xmlsitemap' );
add_action ( 'publish_page', 'sitemap_generate_xmlsitemap' );
add_action ( 'trashed_post', 'sitemap_generate_xmlsitemap' );
?>