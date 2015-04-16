function ct_update_stats()
{
	var data = {
		'action': 'ajax_get_stats',
		'security': ajax_nonce
	};
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		dataType: 'json',
		success: function(msg){
			jQuery('#ct_stats').html('<span style="color:green">' + msg.stat_accepted + '</span> / <span style="color:red">' + msg.stat_blocked + '</span> / <span style="color:white">' + msg.stat_all + '</span>');
			setTimeout(ct_update_stats,5000);
		}
	});
}
jQuery(document).ready(function(){
	ct_update_stats();
});