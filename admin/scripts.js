var $j =jQuery.noConflict();
$j(document).ready(function(){
	$j("#private_import_albums").attr('checked','');
	$j("#private_import_albums").change(function(){
		$j("#gpass_holder").toggleClass('hide');
		if($j("#gpass_holder").hasClass('hide')){
			$j("#gpass_holder input").val('');
		}
	});
	$j("#picasa-album-side").addClass("closed");
	
	$j("#import_albums").click(function(){
		var l = $j(this).next();
		l.show();
		$j.get('/wp-admin/admin-ajax.php?action=picasa_ajax_import',{'password':$j("#gpassword").val()},function(){
			l.hide();
		});
	});
	
	$j("#import_album_images").click(function(){
		var l = $j(this);
		var t = $j(this).val();
		l.val('Loading...');
		$j.get('/wp-admin/admin-ajax.php?action=picasa_ajax_reload_images',{"id":l.attr("data"),"authkey":l.attr("authkey"),'post_ID':$j("#post_ID").val()},function(){
			l.val(t);
			window.location.href=window.location.href
		});
	});

});