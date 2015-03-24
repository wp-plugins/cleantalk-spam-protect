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

function ct_send_comments()
{
	var data = {
		'action': 'ajax_check_comments',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			if(parseInt(msg)==1)
			{
				ct_send_comments();
			}
			else if(parseInt(msg)==0)
			{
				working=false;
				jQuery('#ct_working_message').hide();
				//alert('finish!');
				location.href='edit-comments.php?page=ct_check_spam';
			}
			else
			{
				working=false;
				alert(msg);
			}
		}
	});
}
function ct_show_info()
{
	if(working)
	{
		var data = {
			'action': 'ajax_info_comments',
			'security': ajax_nonce
		};
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: data,
			success: function(msg){
				jQuery('#ct_checking_status').html(msg);
				setTimeout(ct_show_info, 1000);
			}
		});
	}
}
function ct_insert_comments()
{
	var data = {
		'action': 'ajax_insert_comments',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			if(msg=='ok')
			{
				alert('Added 500 comments');
			}
		}
	});
}
function ct_delete_all()
{
	var data = {
		'action': 'ajax_delete_all',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			location.href='edit-comments.php?page=ct_check_spam';
		}
	});
}
function ct_delete_checked()
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
		'action': 'ajax_delete_checked',
		'security': ajax_nonce,
		'ids':ids
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			location.href='edit-comments.php?page=ct_check_spam';
			//alert(msg);
		}
	});
}
jQuery("#ct_check_spam_button").click(function(){
	jQuery('#ct_working_message').show();
	working=true;
	ct_send_comments();
});
jQuery("#ct_check_spam_button").click(function(){
	jQuery('#ct_checking_status').html('');
	working=true;
	ct_show_info();
});
jQuery("#ct_insert_comments").click(function(){
	ct_insert_comments();
});
jQuery("#ct_delete_all").click(function(){
	ct_delete_all();
});
jQuery("#ct_delete_checked").click(function(){
	ct_delete_checked();
});

jQuery(document).ready(function(){
	working=true;
	ct_show_info();
	working=false;
});
