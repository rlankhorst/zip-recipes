/*
 License: GPLv3 or later

 Copyright 2014 Gezim Hoxha
 This code is derived from the 2.6 version build of ZipList Recipe Plugin released by ZipList Inc.:
 http://get.ziplist.com/partner-with-ziplist/wordpress-recipe-plugin/ and licensed under GPLv3 or later
 */

/*
 This file is part of Zip Recipes Plugin.

 Zip Recipes Plugin is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Zip Recipes Plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Zip Recipes Plugin. If not, see <http://www.gnu.org/licenses/>.
 */

(function() {

	tinymce.create('tinymce.plugins.zrdnEditRecipe', {
		init: function( editor, url ) {
			var t = this;
			t.url = url;

			//replace shortcode before editor content set
			editor.onBeforeSetContent.add(function(ed, o) {
				o.content = t._convert_codes_to_imgs(o.content);
				o.content = t._convert_new_codes_to_imgs(o.content);
			});

			/* FIXME
			editor.on('BeforeSetcontent', function(event){
				//console.log(event);
				event.content = t._convert_codes_to_imgs(event.content);
				//console.log('post');
			});
			*/

			//replace shortcode as its inserted into editor (which uses the exec command)
			editor.onExecCommand.add(function(ed, cmd) {
				if (cmd ==='mceInsertContent') {
					var bm = tinyMCE.activeEditor.selection.getBookmark();
					tinyMCE.activeEditor.setContent( t._convert_codes_to_imgs(tinyMCE.activeEditor.getContent()) );
					tinyMCE.activeEditor.setContent( t._convert_new_codes_to_imgs(tinyMCE.activeEditor.getContent()) );
					tinyMCE.activeEditor.selection.moveToBookmark(bm);
				}
			});

			/* FIXME
			editor.on('ExecCommand', function(e) {
				console.log('ExecCommand event', e);
				something happens
			});
			*/

			//replace the image back to shortcode on save
			editor.onPostProcess.add(function(ed, o) {
				if (o.get)
					o.content = t._convert_imgs_to_codes(o.content);
			});

			editor.addButton( 'zrdn_buttons', {
				title: 'Zip Recipes',
				image: url + '/../images/zrecipes-icon.png',
				onclick: function() {
					var recipe_id = null;
					if (recipe = editor.dom.select('img.amd-zlrecipe-recipe')[0]) {
						editor.selection.select(recipe);
						recipe_id = /amd-zlrecipe-recipe-([0-9]+)/i.exec(editor.selection.getNode().id);
					}
					var iframe_url = baseurl + '/wp-admin/media-upload.php?recipe_post_id=' + ((recipe_id) ? '1-' + recipe_id[1] : post_id) + '&type=z_recipe&tab=amd_zlrecipe&TB_iframe=true&width=640&height=523';
					editor.windowManager.open( {
						title: 'Edit Recipe',
						url: iframe_url,
						width: 700,
						height: 600,
						scrollbars : "yes",
						inline : 1,
						onsubmit: function( e ) {
							editor.insertContent( '<h3>' + e.data.title + '</h3>');
						}
					});
				}
			});
    	},

		_convert_codes_to_imgs : function(co) {
                    return co.replace(/\[amd-zlrecipe-recipe:([0-9]+)\]/g, function(a, b) {
                                                                        return '<img id="amd-zlrecipe-recipe-'+b+'" class="amd-zlrecipe-recipe" src="' + plugindir + '/images/zrecipe-placeholder.png" alt="" />';
                    });
		},
		_convert_new_codes_to_imgs : function(co) {
                    return co.replace(/\[ziprecipes recipe id="([0-9]+)"\]/g, function(a, b) {
                                                                        return '<img id="amd-zlrecipe-recipe-'+b+'" class="amd-zlrecipe-recipe" src="' + plugindir + '/images/zrecipe-placeholder.png" alt="" />';
                    });
		},

		_convert_imgs_to_codes : function(co) {
//			return co.replace(/\<img[^>]*?\sid="amd-zlrecipe-recipe-([0-9]+)[^>]*?\>/g, function(a, b){
//                return '[amd-zlrecipe-recipe:'+b+']';
			return co.replace(/\<img[^>]*?\sid="amd-zlrecipe-recipe-([0-9]+)[^>]*?\>/g, function(a, b){
                return '[ziprecipes recipe id="'+b+'"]';
            });
		},

		getInfo : function() {
            return {
                longname : "Zip Recipes Plugin",
                author : 'HappyGezim',
                authorurl : 'https://www.ziprecipes.net/',
                infourl : 'https://www.ziprecipes.net/',
                version : "4.7.2.20"
            };
        }
	});

	tinymce.PluginManager.add('zrdn_plugin', tinymce.plugins.zrdnEditRecipe);

})();
