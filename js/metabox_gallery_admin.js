/* 
jQuery(function($){
	//store old send to editor function
	window.restore_send_to_editor = window.send_to_editor;
	$('#select_gallery_images').on('click', function(){
		formFileld = $('#gallery_attachment_ids').attr('gallery_attachment_ids');
		tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
		return false;
	});

	$('#select_gallery_images').on('click', function(){
		window.send_to_editor = function(html) {
			imgurl = $('img', html).attr('src');
			$('#gallery_attachment_ids').val(imgurl);
			tb_remove();
			// restore old send to editor function
			window.send_to_editor = window.restore_send_to_editor;
		}

	});
});
*/
jQuery(function($){
	var gallery_media_frame;
	$('#select_gallery_images').on('click', function(e){
		e.preventDefault();
		// If gallery_media_frame already exists, reopen it.
		if(typeof(gallery_media_frame) !== 'undefined') {
			gallery_media_frame.close();
		}

		// Let's create WP media frame.
		gallery_media_frame = wp.media.frames.customHeader = wp.media({
			title: "Select images for gallery",
			library: {
				type: 'image'
			},
			button: {
				text: 'Select'
			},
			multiple: true
		});

		// Callback for selected image
		gallery_media_frame.on('select', function(){
			var selection = gallery_media_frame.state().get('selection');
			var appendments = "";
			var selectionCount = 1;
			var image_ids = new Array();
			selection.map(function(attachment){
				image_ids.push(attachment.id);
				if(selectionCount < selection.length) {
					appendments += attachment.id + ",";
					selectionCount = selectionCount + 1;
				} else {
					appendments += attachment.id;
				}
			});

			$('#gallery_attachment_ids').val(appendments);
			render_gallery_images(image_ids, $('#gallery_images_list'));


		});
		gallery_media_frame.open();
	});
	var sortable_gallery = $('.gallery-sortable');
	if(sortable_gallery !== "undefined") {
		sortable_gallery.sortable({
			update: function(){
				get_image_order();
			}
		});
	}
});

function render_gallery_images(image_ids, placement) {
	jQuery.ajax({
		url: metabox_gallery_obj_params.ajax_url,
		type: 'POST',
		data: {
			action: 'render_gallery_images',
			image_ids: image_ids
		},
		success: function(response) {
			placement.html(response);
			placement.find('.gallery-sortable').sortable({
				update: function() {
					  get_image_order();
				}
			});
		}
	});
}

function get_image_order() {
	var appendments = "";
	var objectx = jQuery('#gallery_images_list ul.gallery-sortable li');
	var count = 1;
	objectx.each(function(){
		
		if(objectx.length > count) {
			appendments += jQuery(this).attr('data-image-id') + ',';
			count = count + 1;
		} else {
			appendments += jQuery(this).attr('data-image-id');
		}
	});
	jQuery('#gallery_attachment_ids').val(appendments);
}
