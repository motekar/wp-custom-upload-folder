<?php
/*
Plugin Name: Custom Upload Folder
Description: Upload files to custom directory in WP Media Library.
Version: 0.2
Author: Notekar
Author URI: https://motekar.com/
Text Domain: custom-upload-folder
*/

class CustomUploadFolder
{
	public function __construct() {
		// Set default value.
		setcookie( 'custom_upload_folder', '' );

		// Check if from media uploader.
		if ( preg_match( '/(async-upload|media-new)\.php/', $_SERVER['REQUEST_URI'] ) ) {
			// before upload
			add_filter( 'wp_handle_upload_prefilter', function( $file ) {
				add_filter( 'upload_dir', ['CustomUploadFolder', 'custom_dir'] );

				return $file;
			} );

			// after
			add_filter( 'wp_handle_upload', function( $file ) {
				remove_filter( 'upload_dir', ['CustomUploadFolder', 'custom_dir'] );

				return $file;
			} );

			add_action( 'pre-upload-ui', 'custom_upload_folder_select' );
			add_action( 'admin_print_scripts', 'custom_upload_folder_script', 99 );
		}

		// Media table
		add_filter( 'manage_media_columns', function( $columns ) {
			$columns['folder'] = 'Folder';
			return $columns;
		} );

		add_action( 'manage_media_custom_column', function( $column_name, $post_ID ) {
			if( $column_name == 'folder' ) {
				$file = pathinfo( get_post_meta( $post_ID, '_wp_attached_file', true ) );
				echo $file['dirname'];
			}
		}, 10, 2 );

	}

	public function custom_dir( $dirs ) {
		if (
			! isset( $_COOKIE['custom_upload_folder'] ) ||
			  empty( $_COOKIE['custom_upload_folder'] )
		) return $dirs;

		$dir = $_COOKIE['custom_upload_folder'];

		$dirs['subdir'] = '/' . $dir;
		$dirs['path'] = $dirs['basedir'] . '/' . $dir;
		$dirs['url'] = $dirs['baseurl'] . '/' . $dir;

		return $dirs;
	}
}

new CustomUploadFolder;

// TODO: settings page for manage these options.
function custom_upload_folder_select() {
?>
Select Upload Folder
<select class="js-custom-upload-folder">
	<option value="">Choose Folder</option>
	<option value="assets">assets</option>
	<option value="assets/img">assets/img</option>
	<option value="assets/css">assets/css</option>
	<option value="assets/js">assets/js</option>
</select>
<?php
}

// Send folder name through cookie.
function custom_upload_folder_script() {
?>
<script type="text/javascript">
jQuery(function($) {
	$('body').on('change', '.js-custom-upload-folder', function() {
		document.cookie = 'custom_upload_folder=' + $(this).val();
	});
} );
</script>
<?
}