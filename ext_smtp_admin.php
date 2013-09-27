<?php
function ext_smtp_admin(){
	add_options_page('Extended SMTP Preferences Plugin  Options', 'EXT SMTP','manage_options', __FILE__, 'ext_smtp_page');
}

function ext_smtp_page(){
	global $wsOptions;
	if(isset($_POST['ext_smtp_update'])){
		$wsOptions = array();
		$wsOptions["from"] = trim($_POST['ext_smtp_from']);
		$wsOptions["fromname"] = trim($_POST['ext_smtp_fromname']);
		$wsOptions["host"] = trim($_POST['ext_smtp_host']);
		$wsOptions["smtpsecure"] = trim($_POST['ext_smtp_smtpsecure']);
		$wsOptions["port"] = trim($_POST['ext_smtp_port']);
		$wsOptions["smtpauth"] = trim($_POST['ext_smtp_smtpauth']);
		$wsOptions["username"] = trim($_POST['ext_smtp_username']);
		$wsOptions["password"] = trim($_POST['ext_smtp_password']);
		$wsOptions["deactivate"] = (isset($_POST['ext_smtp_deactivate'])) ? trim($_POST['ext_smtp_deactivate']) : "";
		update_option("ext_smtp_options",$wsOptions);
		if(!is_email($wsOptions["from"])){
			echo '<div id="message" class="updated fade"><p><strong>' . __("The field \"From\" must be a valid email address!","EXT-SMTP-PRF") . '</strong></p></div>';
		}
		elseif(empty($wsOptions["host"])){
			echo '<div id="message" class="updated fade"><p><strong>' . __("The field \"Host\" can not be left blank!","EXT-SMTP-PRF") . '</strong></p></div>';
		}
		else{
			echo '<div id="message" class="updated fade"><p><strong>' . __("Options saved.") . '</strong></p></div>';
		}
	}
	if(isset($_POST['ext_smtp_test'])){
		$to = trim($_POST['ext_smtp_to']);
		$subject = trim($_POST['ext_smtp_subject']);
		$message = trim($_POST['ext_smtp_message']);
		$failed = 0;
		if(!empty($to) && !empty($subject) && !empty($message)){
			try{
				$result = wp_mail($to,$subject,$message);
			}catch(phpmailerException $e){
				$failed = 1;
			}
		}
		else{
			$failed = 1;
		}
		if(!$failed){
			if($result==TRUE){
				echo '<div id="message" class="updated fade"><p><strong>' . __("Message sent!","EXT-SMTP-PRF") . '</strong></p></div>';
			}
			else{
				$failed = 1;
			}
		}
		if($failed){
			echo '<div id="message" class="updated fade"><p><strong>' . __("Some errors occurred!","EXT-SMTP-PRF") . '</strong></p></div>';
		}
	}
?>
<div class="wrap">
	
<?php screen_icon(); ?>
<h2>Extended SMTP Preferences Plugin</h2>

<form action="" method="post" enctype="multipart/form-data" name="ext_smtp_form">

<table class="form-table">
	<tr valign="top">
		<th scope="row">
			<?php _e('From','jermynfit'); ?>
		</th>
		<td>
			<label>
				<input type="text" name="ext_smtp_from" value="<?php echo $wsOptions["from"]; ?>" size="43" style="width:272px;height:24px;" />
			</label>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php _e('From Name','jermynfit'); ?>
		</th>
		<td>
			<label>
				<input type="text" name="ext_smtp_fromname" value="<?php echo $wsOptions["fromname"]; ?>" size="43" style="width:272px;height:24px;" />
			</label>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php _e('Host','jermynfit'); ?>
		</th>
		<td>
			<label>
				<input type="text" name="ext_smtp_host" value="<?php echo $wsOptions["host"]; ?>" size="43" style="width:272px;height:24px;" />
			</label>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php _e('SMTP Secure','jermynfit'); ?>
		</th>
		<td>
			<label>
				<input name="ext_smtp_smtpsecure" type="radio" value=""<?php if ($wsOptions["smtpsecure"] == '') { ?> checked="checked"<?php } ?> />
				None
			</label>
			&nbsp;
			<label>
				<input name="ext_smtp_smtpsecure" type="radio" value="ssl"<?php if ($wsOptions["smtpsecure"] == 'ssl') { ?> checked="checked"<?php } ?> />
				SSL
			</label>
			&nbsp;
			<label>
				<input name="ext_smtp_smtpsecure" type="radio" value="tls"<?php if ($wsOptions["smtpsecure"] == 'tls') { ?> checked="checked"<?php } ?> />
				TLS
			</label>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php _e('Port','jermynfit'); ?>
		</th>
		<td>
			<label>
				<input type="text" name="ext_smtp_port" value="<?php echo $wsOptions["port"]; ?>" size="43" style="width:272px;height:24px;" />
			</label>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php _e('SMTP Authentication','jermynfit'); ?>
		</th>
		<td>
			<label>
				<input name="ext_smtp_smtpauth" type="radio" value="no"<?php if ($wsOptions["smtpauth"] == 'no') { ?> checked="checked"<?php } ?> />
				No
			</label>
			&nbsp;
			<label>
				<input name="ext_smtp_smtpauth" type="radio" value="yes"<?php if ($wsOptions["smtpauth"] == 'yes') { ?> checked="checked"<?php } ?> />
				Yes
			</label>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php _e('Username','jermynfit'); ?>
		</th>
		<td>
			<label>
				<input type="text" name="ext_smtp_username" value="<?php echo $wsOptions["username"]; ?>" size="43" style="width:272px;height:24px;" />
			</label>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php _e('Password','jermynfit'); ?>
		</th>
		<td>
			<label>
				<input type="password" name="ext_smtp_password" value="<?php echo $wsOptions["password"]; ?>" size="43" style="width:272px;height:24px;" />
			</label>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php _e('Delete Options','jermynfit'); ?>
		</th>
		<td>
			<label>
				<input type="checkbox" name="ext_smtp_deactivate" value="yes" <?php if($wsOptions["deactivate"]=='yes') echo 'checked="checked"'; ?> />
				<?php _e('Delete options while deactivate this plugin.','jermynfit'); ?>
			</label>
		</td>
	</tr>
</table>

<p class="submit">
<input type="hidden" name="ext_smtp_update" value="update" />
<input type="submit" class="button-primary" name="Submit" value="<?php _e('Save Changes'); ?>" />
</p>

</form>

<form action="" method="post" enctype="multipart/form-data" name="ext_smtp_testform">
<table class="form-table">
	<legend><h3>Send Test Message</h3></legend>
	<tr valign="top">
		<th scope="row">
			<?php _e('To:','jermynfit'); ?>
		</th>
		<td>
			<label>
				<input type="text" name="ext_smtp_to" value="" size="43" style="width:272px;height:24px;" />
			</label>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php _e('Subject:','jermynfit'); ?>
		</th>
		<td>
			<label>
				<input type="text" name="ext_smtp_subject" value="" size="43" style="width:272px;height:24px;" />
			</label>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<?php _e('Message:','jermynfit'); ?>
		</th>
		<td>
			<label>
				<textarea type="text" name="ext_smtp_message" value="" cols="45" rows="3" style="width:284px;height:62px;"></textarea>
			</label>
		</td>
	</tr>
</table>
<p class="submit">
<input type="hidden" name="ext_smtp_test" value="test" />
<input type="submit" class="button-primary" value="<?php _e('Send Test','jermynfit'); ?>" />
</p>
</form>

</div>
<?php 
}
add_action('admin_menu', 'ext_smtp_admin');
?>
