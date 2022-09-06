<?php
/*
Plugin Name: Custom Upload Folder
Description: Upload files to custom directory in WP Media Library.
Version: 1.1
Author: Motekar
Author URI: https://motekar.com/
Text Domain: custom-upload-folder
*/

class CustomUploadFolder
{
    private $default_folders = "assets\r\nassets/img\r\nassets/css\r\nassets/js";

    public function __construct()
    {
        add_action('pre-upload-ui', [$this, 'custom_upload_folder_select']);

        // Media table
        add_filter('manage_media_columns', function ($columns) {
            $columns['folder'] = 'Folder';

            return $columns;
        });

        add_action('manage_media_custom_column', function ($column_name, $post_ID) {
            if ('folder' == $column_name) {
                $file = pathinfo(get_post_meta($post_ID, '_wp_attached_file', true));
                echo $file['dirname'];
            }
        }, 10, 2);

        add_action('admin_init', [$this, 'admin_init']);

        // Check if from media uploader.
        if (preg_match('/(async-upload|media-new)\.php/', $_SERVER['REQUEST_URI'])) {
            // before upload
            add_filter('wp_handle_upload_prefilter', function ($file) {
                add_filter('upload_dir', [$this, 'upload_dir']);

                return $file;
            });

            // after
            add_filter('wp_handle_upload', function ($file) {
                remove_filter('upload_dir', [$this, 'upload_dir']);

                return $file;
            });
        }
    }

    public function upload_dir($dirs)
    {
        $folder_in_cookie = sanitize_text_field(filter_input(INPUT_COOKIE, 'custom_upload_folder'));

        if (empty($folder_in_cookie)) {
            return $dirs;
        }

        $folders = explode("\r\n", esc_attr(get_option('custom_upload_folders')));

        if (in_array($folder_in_cookie, $folders)) {
            $dirs['subdir'] = '/'.$folder_in_cookie;
            $dirs['path'] = $dirs['basedir'].'/'.$folder_in_cookie;
            $dirs['url'] = $dirs['baseurl'].'/'.$folder_in_cookie;
        }

        return $dirs;
    }

    public function admin_init()
    {
        register_setting(
            'media',
            'custom_upload_folders',
            [
                'type' => 'string',
                'default' => $this->default_folders,
            ]
        );

        add_settings_section(
            'custom_upload_folders_section',
            __('Custom Upload Folder', __FILE__),
            '',
            'media'
        );

        add_settings_field(
            'folders',
            __('Custom Upload Folders', __FILE__),
            [$this, 'folders_input_callback'],
            'media',
            'custom_upload_folders_section'
        );
    }

    public function folders_input_callback()
    {
        ?>
		<textarea id="custom_upload_folders" name="custom_upload_folders" class="regular-text ltr" rows="7"><?php echo esc_attr(get_option('custom_upload_folders')); ?></textarea>
		<?php
    }

    public function custom_upload_folder_select()
    {
        $folders = explode("\r\n", get_option('custom_upload_folders', $this->default_folders)); ?>
		<?php _e('Select Upload Folder', __FILE__); ?>
		<select class="js-custom-upload-folder" onchange="document.cookie='custom_upload_folder=' + event.target.value + ';path=<?php echo COOKIEPATH; ?>'">
			<option value=""><?php _e('Default', __FILE__); ?></option>
			<?php foreach ($folders as $folder) {
            $folder = trim($folder);
            echo "<option value=\"{$folder}\">{$folder}</option>";
        } ?>
		</select>
		<img onload="
			     var match = document.cookie.match( new RegExp( '(^| )custom_upload_folder=([^;]+)' ) );
			     document.getElementsByClassName('js-custom-upload-folder')[0].value = match ? match[2] : '';
			     "
		     src="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACwAAAAAAQABAAACAkQBADs=" />
	<?php
    }
}

new CustomUploadFolder;
