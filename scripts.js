var $j =jQuery.noConflict();
$j(document).ready(function(){
	$j.get('/wp-admin/admin-ajax.php?action=picasa_ajax_import');
});