var $j =jQuery.noConflict();
$j(document).ready(function(){
	$j("#picasa-album-side").addClass("closed");
	$j("#import_albums").click(function(){
		var l = $j(this).next();
		l.show();
		$j.get('/wp-admin/admin-ajax.php?action=picasa_ajax_import',function(){
			l.hide();
		});
	});
	$j("#import_album_images").click(function(){
		var l = $j(this);
		var t = $j(this).val();
		l.val('Loading...');
		$j.get('/wp-admin/admin-ajax.php?action=picasa_ajax_reload_images',{"id":l.attr("data"),'post_ID':$j("#post_ID").val()},function(){
			l.val(t);
		});
	});

});