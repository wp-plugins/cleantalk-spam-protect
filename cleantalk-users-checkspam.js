var working=false;

String.prototype.format = String.prototype.f = function ()
{
    var args = arguments;
    return this.replace(/\{\{|\}\}|\{(\d+)\}/g, function (m, n)
    {
        if (m == "{{") { return "{"; }
        if (m == "}}") { return "}"; }
        return args[n];
    });
};

function ct_clear_users()
{
	var data = {
		'action': 'ajax_clear_users',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			ct_send_users();
		}
	});
}

function ct_send_users()
{
	var data = {
		'action': 'ajax_check_users',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			if(parseInt(msg)==1)
			{
				ct_send_users();
			}
			else if(parseInt(msg)==0)
			{
				working=false;
				jQuery('#ct_working_message').hide();
				//alert('finish!');
				location.href='users.php?page=ct_check_users';
			}
			else
			{
				working=false;
				alert(msg);
			}
		}
	});
}
function ct_show_users_info()
{
	if(working)
	{
		var data = {
			'action': 'ajax_info_users',
			'security': ajax_nonce
		};
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: data,
			success: function(msg){
				jQuery('#ct_checking_users_status').html(msg);
				setTimeout(ct_show_users_info, 1000);
			}
		});
	}
}
function ct_insert_users()
{
	var data = {
		'action': 'ajax_insert_users',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			if(msg=='ok')
			{
				alert('Added 500 users');
			}
		}
	});
}
function ct_delete_all_users()
{
	var data = {
		'action': 'ajax_delete_all_users',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			if(msg>0)
			{
				jQuery('#cleantalk_users_left').html(msg);
				ct_delete_all();
			}
			else
			{
				location.href='users.php?page=ct_check_users';
			}
		}
	});
}
function ct_delete_checked_users()
{
	ids=Array();
	var cnt=0;
	jQuery('input[id^=cb-select-][id!=cb-select-all-1]').each(function(){
		if(jQuery(this).prop('checked'))
		{
			ids[cnt]=jQuery(this).attr('id').substring(10);
			cnt++;
		}
	});
	var data = {
		'action': 'ajax_delete_checked_users',
		'security': ajax_nonce,
		'ids':ids
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			location.href='users.php?page=ct_check_users';
			//alert(msg);
		}
	});
	return false;
}
jQuery("#ct_check_users_button").click(function(){
	jQuery('#ct_working_message').show();
	working=true;
	ct_clear_users();
});
jQuery("#ct_check_users_button").click(function(){
	jQuery('#ct_checking_users_status').html('');
	working=true;
	ct_show_users_info();
});
jQuery("#ct_insert_users").click(function(){
	ct_insert_users();
});
jQuery("#ct_delete_all_users").click(function(){
	jQuery('#ct_check_users_table').hide();
	jQuery('#ct_deleting_message').show();
	jQuery("html, body").animate({ scrollTop: 0 }, "slow");
	ct_delete_all_users();
});
jQuery("#ct_delete_checked_users").click(function(){
	ct_delete_checked_users();
});

jQuery(document).ready(function(){
	working=true;
	ct_show_users_info();
	working=false;
	if(location.href.match(/do_check/))
	{
		jQuery("#ct_check_users_button").click();
	}
});
