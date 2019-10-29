<?php
/*
Plugin Name: Custom Upload Folder
Description: Upload files to custom directory in WP Media Library.
Version: 0.7
Author: Motekar
Author URI: https://motekar.com/
Text Domain: custom-upload-folder
*/

class CustomUploadFolder
{
	public function __construct() {
		// Set default value.
		if ( empty( $_POST ) ) {
			setcookie( 'custom_upload_folder', '' );
		}

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
				add_filter( 'upload_dir', [$this, 'custom_dir'] );

				return $file;
			} );

			// after
			add_filter( 'wp_handle_upload', function( $file ) {
				remove_filter( 'upload_dir', [$this, 'custom_dir'] );

				return $file;
			} );
		}
	}

	public function custom_dir( $dirs ) {
		if (
			! isset( $_COOKIE['custom_upload_folder'] ) ||
				empty( $_COOKIE['custom_upload_folder'] )
		) return $dirs;

		$folders = explode( "\r\n", esc_attr( get_option( 'custom_upload_folders' ) ) );
		$dir = sanitize_text_field( $_COOKIE['custom_upload_folder'] );

		if ( in_array( $dir, $folders ) ) {
			$dirs['subdir'] = '/' . $dir;
			$dirs['path'] = $dirs['basedir'] . '/' . $dir;
			$dirs['url'] = $dirs['baseurl'] . '/' . $dir;
		}

		return $dirs;
	}

	function admin_init() {
		register_setting(
			__FILE__,
			'custom_upload_folders',
			[
				'type' => 'string',
				'default' => "assets\r\nassets/img\r\nassets/css\r\nassets/js",
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
		$folders = explode( "\n", get_option( 'custom_upload_folders', "assets\r\nassets/img\r\nassets/css\r\nassets/js" ) ); ?>
		<?php _e( 'Select Upload Folder', __FILE__ ); ?>
		<select class="js-custom-upload-folder" onchange="document.cookie = 'custom_upload_folder=' + event.target.value;">
			<option value=""><?php _e( 'Choose Folder', __FILE__ ); ?></option>
			<?php foreach ( $folders as $folder ) {
				echo '<option value="' . $folder . '">' . $folder . '</option>';
			} ?>
		</select>
	<?php }
}

new CustomUploadFolder;
