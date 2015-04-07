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
});