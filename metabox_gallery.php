<?php
/**
 * Plugin Name: Metabox Gallery
 * Plugin URI: http://www.positionmysite.com
 * Description: A Simple Metabox gallery that can be embedded to your post and pages.
 * Version: 1.0
 * Author: Theepan Kanthavel
 * Author URI: http://www.cutedrops.com
 */


require_once('inc/timthumb.php');

wp_enueue_style('magnific-popup', plugins_url('css/magnific-popup.css'));
wp_enqueue_script('magnific-popup-js', plugins_url() . '/metabox_gallery/js/jquery.magnific-popup.min.js', array('jquery'));
wp_enqueue_script('metabox-gallery-js', plugins_url() . '/metabox_gallery/js/metabox_gallery.js', array('jquery'));
wp_register_script('metabox_gallery-admin-js', plugins_url() . '/metabox_gallery/js/metabox_gallery_admin.js', array('jquery'));

$file_name = basename($_SERVER['PHP_SELF']);

if(is_admin() && ($file_name == 'post.php' || $file_name == 'post-new.php')) {
	wp_localize_script(
		'metabox_gallery',
		'metabox_gallery_obj_params',
		array(
			'ajax_url' => home_url() . '/wp-admin/admin-ajax.php'
		)
	);
	wp_enqueue_script('metabox_gallery');
	// Add some styles to the gallery
	add_action('admin_head', 'gallery_images_css');
}

// add metabox
function gallery_facility() {
	new GalleryFacility();
}
if(is_admin()) {
	add_action('load-post.php', 'gallery_facility');
	add_action('load-post-new.php', 'gallery_facility');
}
class GalleryFacility {
	public function __construct() {
		add_action('add_meta_boxes', array($this, 'add_meta_box_facility'));
		add_action('save_post', array($this, 'save'));
	}

	public function add_meta_box_facility($post_type) {
		$post_types = array ('post');
		if(in_array($post_type, $post_types)) {
			add_meta_box(
				'metabox_gallery', __('Gallery', 'metabox_gallery_textdomain'),
				array($this, 'render_metabox_facility_content'),
				$post_type, 'advanced', 'high'
			);
		}
	}

	public function render_metabox_facility_content($post) {
		wp_nonce_field('metabox_gallery_box', 'metabox_gallery_box_nonce');
		$attachment_ids = get_post_meta($post->ID, 'gallery_attachment_ids', true);
		$image_ids = explode(',', $attachment_ids);

		echo "<h4>Images</h4>";
		echo "<input type='hidden' name='gallery_attachment_ids' id='gallery_attachment_ids' value='" . $attachment_ids . "' />";
		echo "<div id='gallery_images_list'>" . render_gallery_images_in_backend($image_ids) . "<div class='clearfix'></div></div>";
		echo "<p><button id='select_gallery_images' class='button'>Select Images</button></p>";
	}

	public function save($post_id) {
		if(!isset($_POST['metabox_gallery_box_nonce'])) {
			return $post_id;
		}
		$nonce = $_POST['metabox_gallery_box_nonce'];
		if(!wp_verify_nonce($nonce, 'metabox_gallery_box')) {
			return $post_id;
		}
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}
		if(!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}
		$image_ids = sanitize_text_field($_POST['gallery_attachment_ids']);
		update_post_meta($post_id, 'gallery_attachment_ids', $image_ids);
	}
}

add_action('wp_ajax_render_gallery_images', 'render_gallery_images_in_backend');
function render_gallery_images_in_backend($image_ids = null) {
	if(isset($_POST['image_ids'])) {
		$image_ids = $_POST['image_ids'];
	}
	
	$html = "<ul class='gallery-sortable'>";
	foreach($image_ids as $image_id) {
		$html .= "<li class='ui-state-default' data-image-id='$image_id'>" . wp_get_attachment_image($image_id) . "</li>";
	}
	$html .= "</ul>";
	$html .= "<div class='clearfix'></div>";
	if(isset($_POST['image_ids'])) {
		wp_send_json($html);
	} else {
		return $html;
	}	
}

function gallery_images_css() {
	?>
	<style type="text/css">
		#gallery_images_list ul.gallery-sortable li {
			margin: 0 5px;
			float: left;
		}
		#gallery_images_list ul.gallery-sortable li img {
			cursor: move;
		}
		.clearfix {
			clear: both;
		}
	</style>
	<?php
}

// Front-end display
add_shortcode('metabox_gallery', 'render_metabox_gallery');

function render_metabox_gallery() {
	$image_ids = get_post_meta(get_the_ID(), 'gallery_attachment_ids', true);
	$image_ids = explode(',', $image_ids);
	foreach($image_ids as $image_id) {
		echo wp_get_attachment_image($image_id, 'full');
	}
}
