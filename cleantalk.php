<?php
/*
  Plugin Name: Anti-spam by CleanTalk
  Plugin URI: http://cleantalk.org
  Description: Max power, all-in-one, captcha less, premium anti-spam plugin. No comment spam, no registration spam, no contact spam, protects any WordPress forms. 
  Version: 5.3
  Author: СleanTalk <welcome@cleantalk.org>
  Author URI: http://cleantalk.org
 */
$cleantalk_plugin_version='5.3';

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
		if(!isset($_GET['activate-multi']))
		{
			wp_redirect("options-general.php?page=cleantalk");
		}
	}
}

function ct_add_event($event_type)
{
	global $ct_data;
	$t=time();
	if($event_type=='yes')
	{
		$ct_data['stat_accepted']++;
	}
	if($event_type=='no')
	{
		$ct_data['stat_blocked']++;
	}
	$ct_data['stat_all']++;
	update_option('cleantalk_data', $ct_data);
}


require_once(CLEANTALK_PLUGIN_DIR . 'cleantalk-comments.php');

?>
