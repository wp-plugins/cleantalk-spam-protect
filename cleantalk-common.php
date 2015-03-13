<?php

$ct_agent_version = 'wordpress-422';
$ct_plugin_name = 'Anti-spam by CleanTalk';
$ct_checkjs_frm = 'ct_checkjs_frm';
$ct_checkjs_register_form = 'ct_checkjs_register_form';
$ct_session_request_id_label = 'request_id';
$ct_session_register_ok_label = 'register_ok';

$ct_checkjs_cf7 = 'ct_checkjs_cf7';
$ct_cf7_comment = '';

$ct_checkjs_jpcf = 'ct_checkjs_jpcf';
$ct_jpcf_patched = false; 
$ct_jpcf_fields = array('name', 'email');

// Comment already proccessed
$ct_comment_done = false;

// Comment already proccessed
$ct_signup_done = false;

// Default value for JS test
$ct_checkjs_def = 0;

// COOKIE label to store request id for last approved  
$ct_approved_request_id_label = 'ct_approved_request_id';

// Last request id approved for publication 
$ct_approved_request_id = null;

// COOKIE label for trial notice flag
$ct_notice_trial_label = 'ct_notice_trial';

// Flag to show trial notice
$show_ct_notice_trial = false;

// COOKIE label for renew notice flag
$ct_notice_renew_label = 'ct_notice_renew';

// Flag to show renew notice
$show_ct_notice_renew = false;

// COOKIE label for online notice flag
$ct_notice_online_label = 'ct_notice_online';

// Flag to show online notice - 'Y' or 'N'
$show_ct_notice_online = '';

// Timeout before new check for trial notice in hours
$trial_notice_check_timeout = 1;

// Timeout before new check account notice in hours
$account_notice_check_timeout = 24;

// Trial notice show time in minutes
$trial_notice_showtime = 10;

// Renew notice show time in minutes
$renew_notice_showtime = 10;

// COOKIE label for WP Landing Page proccessing result
$ct_wplp_result_label = 'ct_wplp_result';

// Flag indicates active JetPack comments 
$ct_jp_comments = false;

// S2member PayPal post data label
$ct_post_data_label = 's2member_pro_paypal_registration'; 

// S2member Auth.Net post data label
$ct_post_data_authnet_label = 's2member_pro_authnet_registration'; 

// Form time load label  
$ct_formtime_label = 'ct_formtime'; 

// Plugin's options 
$ct_options = null; 

// Account status check last time
$ct_account_status_check = 0;

// Post without page load
$ct_direct_post = 0;

// WP admin email notice interval in seconds
$ct_admin_notoice_period = 10800;

// Sevice negative comment to visitor.
// It uses for BuddyPress registrations to avoid double checks
$ct_negative_comment = null;

// Flag to show apikey automatic getting error
$show_ct_notice_autokey = false;

// Apikey automatic getting label  
$ct_notice_autokey_label = 'ct_autokey'; 

// Apikey automatic getting error text
$ct_notice_autokey_value = '';

/**
 * Public action 'plugins_loaded' - Loads locale, see http://codex.wordpress.org/Function_Reference/load_plugin_textdomain
 */
function ct_plugin_loaded() {
    load_plugin_textdomain('cleantalk', false, basename(dirname(__FILE__)) . '/i18n');
}

/**
 * Session init
 * @return null;
 */
function ct_init_session() {
    if(session_id() === '') {
        @session_start();
    }

    return null;
}

/**
 * Inner function - Current Cleantalk options
 * @return 	mixed[] Array of options
 */
function ct_get_options() {
    $options = get_option('cleantalk_settings');
    if (!is_array($options)){
        $options = array();
    }else{
	if(array_key_exists('apikey', $options))
	    $options['apikey'] = trim($options['apikey']);
    }
    return array_merge(ct_def_options(), (array) $options);
}

/**
 * Inner function - Default Cleantalk options
 * @return 	mixed[] Array of default options
 */
function ct_def_options() {
    return array(
        'server' => 'http://moderate.cleantalk.org',
        'apikey' => __('enter key', 'cleantalk'),
        'autoPubRevelantMess' => '0', 
        'registrations_test' => '1', 
        'comments_test' => '1', 
        'contact_forms_test' => '1', 
        'general_contact_forms_test' => '1', // Antispam test for unsupported and untested contact forms 
        'remove_old_spam' => '0',
        'spam_store_days' => '15', // Days before delete comments from folder Spam 
        'ssl_on' => 0, // Secure connection to servers 
        'next_account_status_check' => 0, // Time label when the plugin should check account status 
        'user_token' => '', // User token 
        'relevance_test' => 0, // Test comment for relevance 
        'notice_api_errors' => 0, // Send API error notices to WP admin 
        'js_keys' => array(), // Keys to do JavaScript antispam test 
        'js_keys_store_days' => 8, // JavaScript keys store days - 8 days now
        'js_key_lifetime' => 86400, // JavaScript key life time in seconds - 1 day now
    );
}

/**
 * Inner function - Stores ang returns cleantalk hash of current comment
 * @param	string New hash or NULL
 * @return 	string New hash or current hash depending on parameter
 */
function ct_hash($new_hash = '') {
    /**
     * Current hash
     */
    static $hash;

    if (!empty($new_hash)) {
        $hash = $new_hash;
    }
    return $hash;
}

/**
 * Inner function - Write manual moderation results to PHP sessions 
 * @param 	string $hash Cleantalk comment hash
 * @param 	string $message comment_content
 * @param 	int $allow flag good comment (1) or bad (0)
 * @return 	string comment_content w\o cleantalk resume
 */
function ct_feedback($hash, $message = null, $allow) {
    global $ct_options;

    require_once('cleantalk.class.php');

    $config = get_option('cleantalk_server');

    $ct = new Cleantalk();
    $ct->work_url = $config['ct_work_url'];
    $ct->server_url = $ct_options['server'];
    $ct->server_ttl = $config['ct_server_ttl'];
    $ct->server_changed = $config['ct_server_changed'];

    if (empty($hash)) {
	    $hash = $ct->getCleantalkCommentHash($message);
    }
    
    $resultMessage = null;
    if ($message !== null) {
        $resultMessage = $ct->delCleantalkComment($message);
    }

    $ct_feedback = $hash . ':' . $allow . ';';
    if (empty($_SESSION['feedback_request'])) {
	$_SESSION['feedback_request'] = $ct_feedback; 
    } else {
	$_SESSION['feedback_request'] .= $ct_feedback; 
    }

    return $resultMessage;
}

/**
 * Inner function - Sends the results of moderation
 * @param string $feedback_request
 * @return bool
 */
function ct_send_feedback($feedback_request = null) {
    global $ct_options;

    if (empty($feedback_request) && isset($_SESSION['feedback_request']) && preg_match("/^[a-z0-9\;\:]+$/", $_SESSION['feedback_request'])) {
	$feedback_request = $_SESSION['feedback_request'];
	unset($_SESSION['feedback_request']);
    }

    if ($feedback_request !== null) {
	require_once('cleantalk.class.php');
	$config = get_option('cleantalk_server');

	$ct = new Cleantalk();
	$ct->work_url = $config['ct_work_url'];
	$ct->server_url = $ct_options['server'];
	$ct->server_ttl = $config['ct_server_ttl'];
	$ct->server_changed = $config['ct_server_changed'];

	$ct_request = new CleantalkRequest();
	$ct_request->auth_key = $ct_options['apikey'];
	$ct_request->feedback = $feedback_request;

	$ct->sendFeedback($ct_request);

	if ($ct->server_change) {
		update_option(
			'cleantalk_server', array(
			'ct_work_url' => $ct->work_url,
			'ct_server_ttl' => $ct->server_ttl,
			'ct_server_changed' => time()
			)
		);
	}
	return true;
    }

    return false;
}

/**
 * On the scheduled action hook, run the function.
 */
function ct_do_this_hourly() {
    global $ct_options;
    // do something every hour

    if (!isset($ct_options))
	$ct_options = ct_get_options();

    delete_spam_comments();
    ct_send_feedback();
}

/**
 * Delete old spam comments 
 * @return null 
 */
function delete_spam_comments() {
    global $pagenow, $ct_options;
    
    if ($ct_options['remove_old_spam'] == 1) {
        $last_comments = get_comments(array('status' => 'spam', 'number' => 1000, 'order' => 'ASC'));
        foreach ($last_comments as $c) {
            if (time() - strtotime($c->comment_date_gmt) > 86400 * $ct_options['spam_store_days']) {
                // Force deletion old spam comments
                wp_delete_comment($c->comment_ID, true);
            } 
        }
    }

    return null; 
}

?>
