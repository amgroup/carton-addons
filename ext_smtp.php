<?php
/*
Plugin Name: Extended SMTP Preferences Plugin
Description: Extended SMTP Preferences plugin can help you to send emails via SMTP instead of the PHP mail() function.
Version: 1.0
Author: kidberries.com
Author URI: http://kidberries.com/
Text Domain: Extended SMTP Preferences Plugin
Domain Path: /lang
*/

function load_ext_smtp_lang(){
	$currentLocale = get_locale();
	if(!empty($currentLocale)){
		$moFile = dirname(__FILE__) . "/lang/" . $currentLocale . ".mo";
		if(@file_exists($moFile) && is_readable($moFile)) { load_textdomain('jermynfit',$moFile); }
	}
}
add_filter('init','load_ext_smtp_lang');

$wsOptions = get_option("ext_smtp_options");

function ext_smtp($phpmailer){
	global $wsOptions;
	if( !is_email($wsOptions["from"]) || empty($wsOptions["host"]) ){
		return;
	}
	$phpmailer->Mailer = "smtp";
	$phpmailer->From = $wsOptions["from"];
	$phpmailer->FromName = $wsOptions["fromname"];
	$phpmailer->Sender = $phpmailer->From; //Return-Path
	$phpmailer->AddReplyTo( //Reply-To
		$phpmailer->From,
		$phpmailer->FromName);
	$phpmailer->Host = $wsOptions["host"];
	$phpmailer->SMTPSecure = $wsOptions["smtpsecure"];
	$phpmailer->Port = $wsOptions["port"];
	$phpmailer->SMTPAuth = ($wsOptions["smtpauth"]=="yes") ? TRUE : FALSE;
	if($phpmailer->SMTPAuth){
		$phpmailer->Username = $wsOptions["username"];
		$phpmailer->Password = $wsOptions["password"];
	}
}
add_action('phpmailer_init','ext_smtp');

function ext_smtp_activate(){
	$wsOptions = array();
	$wsOptions["from"] = "";
	$wsOptions["fromname"] = "";
	$wsOptions["host"] = "";
	$wsOptions["smtpsecure"] = "";
	$wsOptions["port"] = "";
	$wsOptions["smtpauth"] = "yes";
	$wsOptions["username"] = "";
	$wsOptions["password"] = "";
	$wsOptions["deactivate"] = "";
	add_option("ext_smtp_options",$wsOptions);
}
register_activation_hook( __FILE__ , 'ext_smtp_activate' );

if($wsOptions["deactivate"]=="yes"){
	register_deactivation_hook( __FILE__ , create_function('','delete_option("ext_smtp_options");') );
}

function ext_smtp_settings_link($action_links,$plugin_file){
	if($plugin_file==plugin_basename(__FILE__)){
		$ws_settings_link = '<a href="options-general.php?page=' . dirname(plugin_basename(__FILE__)) . '/ext_smtp_admin.php">' . __("Settings") . '</a>';
		array_unshift($action_links,$ws_settings_link);
	}
	return $action_links;
}
add_filter('plugin_action_links','ext_smtp_settings_link',10,2);

if(is_admin()){require_once('ext_smtp_admin.php');}

?>