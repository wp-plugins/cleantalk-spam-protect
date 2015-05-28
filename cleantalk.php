<?php
/*
  Plugin Name: Anti-spam by CleanTalk
  Plugin URI: http://cleantalk.org
  Description: Max power, all-in-one, captcha less, premium anti-spam plugin. No comment spam, no registration spam, no contact spam, protects any WordPress forms. 
  Version: 5.12
  Author: СleanTalk <welcome@cleantalk.org>
  Author URI: http://cleantalk.org
 */
$cleantalk_plugin_version='5.12';
$cleantalk_executed=false;

if(!defined('CLEANTALK_PLUGIN_DIR')){
    define('CLEANTALK_PLUGIN_DIR', plugin_dir_path(__FILE__));

    require_once(CLEANTALK_PLUGIN_DIR . 'cleantalk-common.php');

    // Activation/deactivation functions must be in main plugin file.
    // http://codex.wordpress.org/Function_Reference/register_activation_hook
    register_activation_hook( __FILE__, 'ct_activation' );
    register_deactivation_hook( __FILE__, 'ct_deactivation' );
    add_action('admin_init', 'ct_plugin_redirect');
    
    

    // After plugin loaded - to load locale as described in manual
    add_action( 'plugins_loaded', 'ct_plugin_loaded' );
    
    $ct_options=ct_get_options();
    if(isset($ct_options['use_ajax']))
    {
    	$use_ajax = @intval($ct_options['use_ajax']);
    }
    else
    {
    	$use_ajax=1;
    }
    
    if($use_ajax==1)
    {
		add_action('wp_loaded', 'ct_add_nocache_script', 1);
		add_action('wp_footer', 'ct_add_nocache_script_footer', 1);
		add_action('wp_head', 'ct_add_nocache_script_header', 1);
	}
    
    add_action( 'wp_ajax_nopriv_ct_get_cookie', 'ct_get_cookie',1 );
	add_action( 'wp_ajax_ct_get_cookie', 'ct_get_cookie',1 );
    

    if (is_admin())
    {
		require_once(CLEANTALK_PLUGIN_DIR . 'cleantalk-admin.php');

	if (!(defined( 'DOING_AJAX' ) && DOING_AJAX)) {
    	    add_action('admin_init', 'ct_admin_init', 1);
    	    add_action('admin_menu', 'ct_admin_add_page');
    	    add_action('admin_notices', 'admin_notice_message');
	}
	if (defined( 'DOING_AJAX' ) && DOING_AJAX)
		{
			require_once(CLEANTALK_PLUGIN_DIR . 'cleantalk-public.php');
			require_once(CLEANTALK_PLUGIN_DIR . 'cleantalk-ajax.php');
		}

	add_action('admin_enqueue_scripts', 'ct_enqueue_scripts');
    	add_action('comment_unapproved_to_approvecomment', 'ct_comment_approved'); // param - comment object
    	add_action('comment_unapproved_to_approved', 'ct_comment_approved'); // param - comment object
    	add_action('comment_approved_to_unapproved', 'ct_comment_unapproved'); // param - comment object
    	add_action('comment_unapproved_to_spam', 'ct_comment_spam');  // param - comment object
    	add_action('comment_approved_to_spam', 'ct_comment_spam');   // param - comment object
    	add_filter('get_comment_text', 'ct_get_comment_text');   // param - current comment text
    	add_filter('unspam_comment', 'ct_unspam_comment');
    	add_action('delete_user', 'ct_delete_user');
    	add_filter('plugin_row_meta', 'ct_register_plugin_links', 10, 2);
    	add_filter('plugin_action_links', 'ct_plugin_action_links', 10, 2);
	add_action('updated_option', 'ct_update_option'); // param - option name, i.e. 'cleantalk_settings'
    }else{
	require_once(CLEANTALK_PLUGIN_DIR . 'cleantalk-public.php');

	// Init action.
	add_action('init', 'ct_init', 1);

	// Hourly run hook
	add_action('ct_hourly_event_hook', 'ct_do_this_hourly');

	// Comments 
	add_filter('preprocess_comment', 'ct_preprocess_comment', 1, 1);     // param - comment data array
	add_filter('comment_text', 'ct_comment_text' );

	// Registrations
	add_action('register_form','ct_register_form');
	add_filter('registration_errors', 'ct_registration_errors', 1, 3);
	add_action('user_register', 'ct_user_register');

	// Multisite registrations
	add_action('signup_extra_fields','ct_register_form');
	add_filter('wpmu_validate_user_signup', 'ct_registration_errors_wpmu', 10, 3);

	// Login form - for notifications only
	add_filter('login_message', 'ct_login_message');
    }
}

/**
 * On activation, set a time, frequency and name of an action hook to be scheduled.
 */
if (!function_exists ( 'ct_activation')) {
    function ct_activation() {
	wp_schedule_event(time(), 'hourly', 'ct_hourly_event_hook' );
	add_option('ct_plugin_do_activation_redirect', true);
    }
}
/**
 * On deactivation, clear schedule.
 */
if (!function_exists ( 'ct_deactivation')) {
    function ct_deactivation() {
	wp_clear_scheduled_hook( 'ct_hourly_event_hook' );
    }
}

/**
 * Uses for redirection after activation
 */
function ct_plugin_redirect()
{
	if (get_option('ct_plugin_do_activation_redirect', false))
	{
		delete_option('ct_plugin_do_activation_redirect');
		if(!isset($_GET['activate-multi']))
		{
			wp_redirect("options-general.php?page=cleantalk");
		}
	}
}

function ct_add_event($event_type)
{
	global $ct_data,$cleantalk_executed;
	$ct_data = ct_get_data();
	$t=time();
	if($event_type=='yes')
	{
		@$ct_data['stat_accepted']++;
	}
	if($event_type=='no')
	{
		@$ct_data['stat_blocked']++;
	}
	$ct_data['stat_all']++;
	update_option('cleantalk_data', $ct_data);
	$cleantalk_executed=true;
}

/**
 * return new cookie value
 */
function ct_get_cookie()
{
	global $ct_checkjs_def;
	$ct_checkjs_key = ct_get_checkjs_value(true); 
	print $ct_checkjs_key;
	die();
}

/**
 * adds nocache script
 */
function ct_add_nocache_script()
{
	ob_start('ct_inject_nocache_script');
}

function ct_add_nocache_script_footer()
{
	print "<script type='text/javascript' src='".plugins_url( '/cleantalk_nocache.js' , __FILE__ )."?random=".rand()."'></script>\n";
}

function ct_add_nocache_script_header()
{
	print "\n<script type='text/javascript'>\nvar ct_ajaxurl = '".admin_url('admin-ajax.php')."';\n</script>\n";
}

function ct_inject_nocache_script($html)
{
	if(!is_admin()&&stripos($html,"</body")!==false)
	{
		//$ct_replace.="\n<script type='text/javascript'>var ajaxurl = '".admin_url('admin-ajax.php')."';</script>\n";
		$ct_replace="<script type='text/javascript' src='".plugins_url( '/cleantalk_nocache.js' , __FILE__ )."?random=".rand()."'></script>\n";
		//$html=str_ireplace("</body",$ct_replace."</body",$html);
		$html=substr_replace($html,$ct_replace."</body",strripos($html,"</body"),6);
	}
	if(!is_admin()&&preg_match("#<head[^>]*>#i",$html)==1)
	{
		$ct_replace="\n<script type='text/javascript'>\nvar ct_ajaxurl = '".admin_url('admin-ajax.php')."';\n</script>\n";
		$html=preg_replace("(<head[^>]*>)","$0".$ct_replace,$html,1);
	}
	return $html;
}

require_once(CLEANTALK_PLUGIN_DIR . 'cleantalk-comments.php');

?>
