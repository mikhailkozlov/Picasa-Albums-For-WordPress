(function() {
	tinymce.create('tinymce.plugins.wpPicasaGallery', {
		init : function(ed, url) {
			ed.addCommand('mcePicasa', function() {
				// all work is done by content from ajax
				tb_show('Select Picasa Web Album', '/wp-admin/admin-ajax.php?action=picasa_ajax_list_albums');
				tinymce.DOM.setStyle( ['TB_overlay','TB_window','TB_load'], 'z-index', '999999' );
			});
			ed.addButton('wppicasagallery', {
				title : 'Select Picasa Web Album',
				cmd : 'mcePicasa',
				image : url + '/img/picasa_btn.gif'
			});
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('wppicasagallery', n.nodeName == 'IMG');
			});
		},
		createControl : function(n, cm) {
			return null;
		},

		getInfo : function() {
			return {
				longname : 'Picasa Gallery Settings',
				author : 'Mikhail Kozlov',
				authorurl : 'http://mikhailkozlov.com',
				infourl : '',
				version : "1.0"
			};
		}
	});
	tinymce.PluginManager.add('wppicasagallery', tinymce.plugins.wpPicasaGallery);
})();