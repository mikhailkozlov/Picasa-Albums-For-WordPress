var albumPage = false;

var $j =jQuery.noConflict();
$j(document).ready(function(){
	/************ option page function **************/

	/************ END option page function **************/	
	
	/************ shared function **************/
	$j("#import_albums").click(function(){
		var l = $j(this).next();
		l.show();
		$j.get('/wp-admin/admin-ajax.php?action=picasa_ajax_import',{'password':$j("#gpassword").val()},function(){
			l.hide();
		});
	});

	/************ end shared function **************/
	
	
	/************ custom post type functions **************/
	$j("#load_imges_now").bind("click",function(){
		$j("#import_album_images").click();
		return false;
	});
	// enable fancybox 	
	$j("a.fancybox").fancybox();
	// import button
	$j("#import_album_images").click(function(){
		var l = $j(this);
		var t = $j(this).val();
		l.val("Loading...");
		$j.get("/wp-admin/admin-ajax.php?action=picasa_ajax_reload_images",{"id":l.attr("data"),"authkey":l.attr("authkey"),"post_ID":$j("#post_ID").val()},function(){
			l.val(t);
			window.location.href=window.location.href
		});
	});
	// check if sortable here
	if($j().sortable) {
		// find how to add condition here.
		$j("#picasa-album-images ul.ui-sortable").sortable({
			containment: 'parent',
			forcePlaceholderSize: true,
			distance: 1,
			tolerance: 'intersect',
			placeholder: 'ui-state-highlight',
			opacity: 0.6
		});
	}
	// set album thumbnail
	$j("a.cover_image").click(function(){
		var l = $j(this);
		album.thumbnail.url=l.attr("href").substr(1);
		album.thumbnail.height=l.attr("ref");
		album.thumbnail.width=l.attr("ref");
		$j("#cover_image").attr("height",album.thumbnail.height).attr("width",album.thumbnail.width).attr("src",album.thumbnail.url).fadeOut("fast").fadeIn("fast");
		$j("#picasa-album-images .ui-sortable li").siblings().removeAttr("style");
		l.parent().prev('img').parent().css({border:"1px solid #999"});
		// update text
		$j("textarea#excerpt").val(JSON.stringify(album));
		return false;
	});
	// show hide images
	$j('a.hide_image').click(function(){
		var l = $j(this);
		var m=0; // martch
		for(i=0; i<images.length; i++){
			if(images[i].id == l.attr('id')){
				m=i;
				l.toggleClass('visible');
				if(l.hasClass('visible')){
					images[i].show="yes";
					l.parent().prev('img').toggleClass('dimlight');
				}else{
					images[i].show="no";
					l.parent().prev('img').toggleClass('dimlight');
				}				
			}
		}
		$j('span',l).toggle();
		return false;
	});
	
	// save album changes
	var clickPublish = false;
	$j("#publish, #save-post").bind("click",function(){
		var btn = $j(this);
		if(clickPublish == false && $j("textarea#content").hasClass("albumpage") ){
			clickPublish = true;
			var post = "todo=saveAlbum&post_ID="+$j("#post_ID").val()+"&"+$j("#picasa-album-images ul.ui-sortable").sortable("serialize");
			album.summary = $j("#album_summary").val();
			$j("textarea#excerpt").val(JSON.stringify(album));
			for(i=0; i<images.length; i++){
				post += '&id['+images[i].id+']='+images[i].show;
			}
			$j.get('/wp-admin/admin-ajax.php?action=picasa_ajax_image_action',post,function(r){
				// get responce and update textarea
				$j("textarea#content").val(r);
				btn.click();
			},'html');
			return false;
		}
	});
});