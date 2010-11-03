var $j =jQuery.noConflict();
$j(document).ready(function(){
	$j("a.fancybox").fancybox();
	
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
	// find how to add condition here.
	$j("#picasa-album-images ul.ui-sortable").sortable({
		containment: 'parent',
		forcePlaceholderSize: true,
		distance: 1,
		tolerance: 'intersect',
		placeholder: 'ui-state-highlight',
		opacity: 0.6,
		update: function(event, ui) {
			$j("#save_image_order.button").addClass('button-primary');
		}
	});
	$j("#picasa-album-images input.button").click(function(){
		$j.get('/wp-admin/admin-ajax.php?action=picasa_ajax_image_action','todo=reorder&'+$j("#picasa-album-images ul.ui-sortable").sortable("serialize"),function(res){
			
		});
		$j("#picasa-album-images input.button-primary").removeClass('button-primary');
		return false;
	});
	
	$j('.hide_image').click(function(){
		var l = $j(this);
		var m=0; // martch
		for(i=0; i<images.length; i++){
			if(images[i].id == l.attr('id')){
				m=i;
				l.toggleClass('visible');
				if(l.hasClass('visible')){
					images[i].show="Yes";
					l.parent().prev('img').fadeTo(0,1);
				}else{
					images[i].show="No";
					l.parent().prev('img').fadeTo(0,.5);
				}				
			}
		}
		$j('span',l).toggle();
		return false;
	});
	$j("#publish").bind("click",function(){
		var post = $j("#picasa-album-images ul.ui-sortable").sortable("serialize");
		for(i=0; i<images.length; i++){
			post += '&id['+images[i].id+']='+images[i].show;
		}
		$j.get('/wp-admin/admin-ajax.php?action=picasa_ajax_reload_images',post,function(r){
			
			// get responce and update textarea
			$j("form#post").submit();
		});
		return false;
	});
});