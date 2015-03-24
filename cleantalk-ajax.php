<?php

/*
AJAX functions
*/

/*hooks for AJAX Login & Register email validation*/
add_action( 'wp_ajax_nopriv_validate_email', 'ct_validate_email_ajaxlogin',1 );
add_action( 'wp_ajax_validate_email', 'ct_validate_email_ajaxlogin',1 );

/*hooks for user registration*/
add_action( 'user_register', 'ct_user_register_ajaxlogin',1 );


function ct_validate_email_ajaxlogin($email=null, $is_ajax=true)
{
	require_once(CLEANTALK_PLUGIN_DIR . 'cleantalk-public.php');
	global $ct_agent_version, $ct_checkjs_register_form, $ct_session_request_id_label, $ct_session_register_ok_label, $bp, $ct_signup_done, $ct_formtime_label, $ct_negative_comment, $ct_options;
	
	$ct_options=ct_get_options();
	
	$email = is_null( $email ) ? $email : $_POST['email'];
	$email=sanitize_email($email);
	$is_good=true;
	if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL )||email_exists( $email ) )
	{
		$is_good=false;
	}

	if(class_exists('AjaxLogin')&&isset($_POST['action'])&&$_POST['action']=='validate_email')
	{
		
		$ct_options=ct_get_options();
		$checkjs = js_test('ct_checkjs', $_COOKIE, true);
		$submit_time = submit_time_test();
	    $sender_info = get_sender_info();
	    $sender_info['post_checkjs_passed']=$checkjs;
	    
		if ($checkjs === null)
		{
			$checkjs = js_test('ct_checkjs', $_COOKIE, true);
			$sender_info['cookie_checkjs_passed'] = $checkjs;
		}
		
		$sender_info = json_encode($sender_info);
		if ($sender_info === false)
		{
			$sender_info= '';
		}
		
		require_once('cleantalk.class.php');
		$config = get_option('cleantalk_server');
		$ct = new Cleantalk();
		$ct->work_url = $config['ct_work_url'];
		$ct->server_url = $ct_options['server'];
		
		$ct->server_ttl = $config['ct_server_ttl'];
		$ct->server_changed = $config['ct_server_changed'];
		$ct->ssl_on = $ct_options['ssl_on'];		
		
		$ct_request = new CleantalkRequest();
		$ct_request->auth_key = $ct_options['apikey'];
		$ct_request->sender_email = $email; 
		$ct_request->sender_ip = $ct->ct_session_ip($_SERVER['REMOTE_ADDR']);
		$ct_request->sender_nickname = ''; 
		$ct_request->agent = $ct_agent_version; 
		$ct_request->sender_info = $sender_info;
		$ct_request->js_on = $checkjs;
		$ct_request->submit_time = $submit_time; 
		
		$ct_result = $ct->isAllowUser($ct_request);
		
		if ($ct->server_change)
		{
			update_option(
				'cleantalk_server', array(
					'ct_work_url' => $ct->work_url,
					'ct_server_ttl' => $ct->server_ttl,
					'ct_server_changed' => time()
					)
			);
		}
		if ($ct_result->allow===0)
		{
			$is_good=false;
		}
	}
	if($is_good)
	{
		$ajaxresult=array(
            'description' => null,
            'cssClass' => 'noon',
            'code' => 'success'
            );
	}
	else
	{
		$ajaxresult=array(
            'description' => 'Invalid Email',
            'cssClass' => 'error-container',
            'code' => 'error'
            );
	}
	$ajaxresult=json_encode($ajaxresult);
	print $ajaxresult;
	wp_die();
}

function ct_user_register_ajaxlogin($user_id)
{
	require_once(CLEANTALK_PLUGIN_DIR . 'cleantalk-public.php');
	global $ct_agent_version, $ct_checkjs_register_form, $ct_session_request_id_label, $ct_session_register_ok_label, $bp, $ct_signup_done, $ct_formtime_label, $ct_negative_comment, $ct_options;
	
	$ct_options=ct_get_options();

	if(class_exists('AjaxLogin')&&isset($_POST['action'])&&$_POST['action']=='register_submit')
	{
		$checkjs = js_test('ct_checkjs', $_COOKIE, true);
		$submit_time = submit_time_test();
	    $sender_info = get_sender_info();
	    $sender_info['post_checkjs_passed']=$checkjs;
	    
		if ($checkjs === null)
		{
			$checkjs = js_test('ct_checkjs', $_COOKIE, true);
			$sender_info['cookie_checkjs_passed'] = $checkjs;
		}
		
		$sender_info = json_encode($sender_info);
		if ($sender_info === false)
		{
			$sender_info= '';
		}
		
		require_once('cleantalk.class.php');
		$config = get_option('cleantalk_server');
		$ct = new Cleantalk();
		$ct->work_url = $config['ct_work_url'];
		$ct->server_url = $ct_options['server'];
		
		$ct->server_ttl = $config['ct_server_ttl'];
		$ct->server_changed = $config['ct_server_changed'];
		$ct->ssl_on = $ct_options['ssl_on'];
		
		$ct_request = new CleantalkRequest();
		$ct_request->auth_key = $ct_options['apikey'];
		$ct_request->sender_email = sanitize_email($_POST['email']); 
		$ct_request->sender_ip = $ct->ct_session_ip($_SERVER['REMOTE_ADDR']);
		$ct_request->sender_nickname = sanitize_email($_POST['login']); ; 
		$ct_request->agent = $ct_agent_version; 
		$ct_request->sender_info = $sender_info;
		$ct_request->js_on = $checkjs;
		$ct_request->submit_time = $submit_time; 
		
		$ct_result = $ct->isAllowUser($ct_request);
		
		if ($ct->server_change)
		{
			update_option(
				'cleantalk_server', array(
					'ct_work_url' => $ct->work_url,
					'ct_server_ttl' => $ct->server_ttl,
					'ct_server_changed' => time()
					)
			);			
		}
		if ($ct_result->allow===0)
		{
			wp_delete_user($user_id);
		}
	}
	return $user_id;
}

?>
