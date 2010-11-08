
(function() {
	tinymce.create('tinymce.plugins.wpPicasaGallery', {

		init : function(ed, url) {
			ed.addCommand('mcePicasa', function() {
				alert("Picasa");
			});

			ed.addButton('picasa', {
				title : 'picasa.desc',
				cmd : 'mcePicasa',
				image : url + '/img/ice--plus.png'
			});
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('picasa', n.nodeName == 'IMG');
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