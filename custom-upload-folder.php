<?php
/*
Plugin Name: Custom Upload Folder
Description: Upload files to custom directory in WP Media Library.
Version: 1.0
Author: Motekar
Author URI: https://motekar.com/
Text Domain: custom-upload-folder
*/

class CustomUploadFolder
{
	private $default_folders = "assets\r\nassets/img\r\nassets/css\r\nassets/js";

	public function __construct() {
		add_action( 'pre-upload-ui', [$this, 'custom_upload_folder_select'] );

		// Media table
		add_filter( 'manage_media_columns', function( $columns ) {
			$columns['folder'] = 'Folder';
			return $columns;
		} );

		add_action( 'manage_media_custom_column', function( $column_name, $post_ID ) {
			if( 'folder' == $column_name ) {
				$file = pathinfo( get_post_meta( $post_ID, '_wp_attached_file', true ) );
				echo $file['dirname'];
			}
		}, 10, 2 );

		add_action( 'admin_init', [$this, 'admin_init'] );
		add_action( 'admin_menu', [$this, 'admin_menu'] );

		// Check if from media uploader.
		if ( preg_match( '/(async-upload|media-new)\.php/', $_SERVER['REQUEST_URI'] ) ) {
			// before upload
			add_filter( 'wp_handle_upload_prefilter', function( $file ) {
				add_filter( 'upload_dir', [$this, 'upload_dir'] );

				return $file;
			} );

			// after
			add_filter( 'wp_handle_upload', function( $file ) {
				remove_filter( 'upload_dir', [$this, 'upload_dir'] );

				return $file;
			} );
		}
	}

	public function upload_dir( $dirs ) {
		$folder_in_cookie = filter_input( INPUT_COOKIE, 'custom_upload_folder' );

		if ( empty( $folder_in_cookie ) ) {
			return $dirs;
		}

		$folders = explode( "\r\n", esc_attr( get_option( 'custom_upload_folders' ) ) );

		if ( in_array( $folder_in_cookie, $folders ) ) {
			$dirs['subdir'] = '/' . $folder_in_cookie;
			$dirs['path'] = $dirs['basedir'] . '/' . $folder_in_cookie;
			$dirs['url'] = $dirs['baseurl'] . '/' . $folder_in_cookie;
		}

		return $dirs;
	}

	function admin_init() {
		register_setting(
			__FILE__,
			'custom_upload_folders',
			[
				'type' => 'string',
				'default' => $this->default_folders,
			]
		);
	}

	function admin_menu() {
		add_options_page(
			__( 'Custom Upload Folder', __FILE__ ),
			__( 'Custom Upload Folder', __FILE__ ),
			'manage_options',
			__FILE__,
			[$this, 'option_page']
		);
	}

	function option_page() { ?>
		<div class="wrap">
			<h1><?php _e( 'Custom Upload Folder', __FILE__ ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( __FILE__ ); ?>
				<?php do_settings_sections( __FILE__ ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="custom_upload_folders"><?php _e( 'Custom Upload Folders', __FILE__ ); ?></label></th>
							<td>
								<textarea id="custom_upload_folders" name="custom_upload_folders" class="regular-text ltr" rows="7"><?php echo esc_attr( get_option( 'custom_upload_folders' ) ); ?></textarea>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
	<?php }

	function custom_upload_folder_select() {
		$folders  = explode( "\r\n", get_option( 'custom_upload_folders', $this->default_folders ) ); ?>
		<?php _e( 'Select Upload Folder', __FILE__ ); ?>
		<select class="js-custom-upload-folder" onchange="document.cookie='custom_upload_folder=' + event.target.value + ';path=<?php echo COOKIEPATH; ?>'">
			<option value=""><?php _e( 'Default', __FILE__ ); ?></option>
			<?php foreach ( $folders as $folder ) {
				$folder = trim( $folder );
				echo "<option value=\"{$folder}\">{$folder}</option>";
			} ?>
		</select>
		<script>
		jQuery( function() {
			var match = document.cookie.match( new RegExp( '(^| )custom_upload_folder=([^;]+)' ) );
			jQuery( '.js-custom-upload-folder' ).val( match ? match[2] : '' );
		} );
		</script>
	<?php }
}

new CustomUploadFolder;
