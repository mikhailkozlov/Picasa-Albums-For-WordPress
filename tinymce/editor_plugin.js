
(function() {
	tinymce.create('tinymce.plugins.wpPicasaGallery', {

		init : function(ed, url) {
			ed.addCommand('mcePicasa', function() {
				
				//ed.execCommand('mceInsertContent', false, '[picasaweb id="'+m[1]+'"]');
			});

			ed.addButton('wppicasagallery', {
				title : 'Select PicasaWeb Album',
				cmd : 'mcePicasa',
				image : url + '/img/ice--plus.png',
				'class':'thickbox',
				href:tinymce.documentBaseURL + '/media-upload.php?tab=gallery&TB_iframe=true&width=300&height=100'
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