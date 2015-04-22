var ct_adv_settings=null;
var ct_adv_settings_title=null;
var ct_adv_settings_show=false;
jQuery(document).ready(function(){
	var d = new Date();
	var n = d.getTimezoneOffset();
	var data = {
		'action': 'ajax_get_timezone',
		'security': ajax_nonce,
		'offset': n
	};
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			//
		}
	});
	ct_adv_settings=jQuery('#cleantalk_registrations_test1').parent().parent().parent().parent();
	ct_adv_settings.hide();
	ct_adv_settings_title=ct_adv_settings.prev();
	ct_adv_settings.wrap("<div id='ct_advsettings_hide'>");
	ct_adv_settings_title.append(" <span id='ct_adv_showhide' style='cursor:pointer'><b>+</b></span>");
	ct_adv_settings_title.css('cursor','pointer');
	ct_adv_settings_title.click(function(){
		if(ct_adv_settings_show)
		{
			ct_adv_settings.hide();
			ct_adv_settings_show=false;
			jQuery('#ct_adv_showhide').html('+');
		}
		else
		{
			ct_adv_settings.show();
			ct_adv_settings_show=true;
			jQuery('#ct_adv_showhide').html('-');
		}
		
	});
});