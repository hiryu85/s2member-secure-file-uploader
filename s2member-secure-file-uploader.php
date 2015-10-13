<?php
/*
Plugin Name: s2member Secure-File Uploader
Plugin URI: http://lewayotte.com/plugins/s2member-secure-file-uploader/
Description: A plugin for uploading files to the secure-files location of the <a href="http://www.s2member.com/2496.html" target="_blank">s2member WordPress Membership plugin</a>
Author: Lew Ayotte
Version: 0.0.2
Author URI: http://lewayotte.com/
Tags: s2member, secure, files, downloads, security, enabled
*/

define( 's2sfu_version' , '0.0.2' );

add_action( 'admin_init', 's2sfu_initialization', 1 );
add_action( 'delete_attachment', 'delete_s2sfu_file' );

function s2sfu_initialization() {
		
	if ( is_plugin_active( 's2member/s2member.php' ) ) {
		
		/**/
		if ( !is_dir( $files_dir = $GLOBALS['WS_PLUGIN__']['s2member']['c']['files_dir'] ) )
			if ( is_writable( dirname ( c_ws_plugin__s2member_utils_dirs::strip_dir_app_data( $files_dir ) ) ) )
				mkdir ( $files_dir, 0777, true );
		/**/
		if ( is_dir( $files_dir ) && is_writable( $files_dir ) )
			if ( !file_exists( $htaccess = $files_dir . '/.htaccess' ) || !apply_filters ( 'ws_plugin__s2member_preserve_files_dir_htaccess', false, get_defined_vars() ) )
				file_put_contents( $htaccess, trim( c_ws_plugin__s2member_utilities::evl( file_get_contents( $GLOBALS['WS_PLUGIN__']["s2member"]["c"]['files_dir_htaccess'] ) ) ) );
		
		add_action( 'media_buttons', 's2sfu_media_button', 20 );
		
		add_action( 'media_upload_s2sfu', 's2sfu_media_upload_handler' );
	
	} else {
		
		add_action('admin_notices', 's2sfu_warning');
		
	}

}

function s2sfu_media_button( $editor_id = 'content' ) {
	
	echo "
	<a href='" . esc_url( get_upload_iframe_src( 's2sfu' ) ) . "' id='add_s2sfu' class='thickbox add_s2sfu button' title='" . __( 'Add s2member Secure-File', 's2sfu' ) . "'>
		<img src='" . plugins_url() . '/' . dirname( plugin_basename( __FILE__ ) ) . "/images/secure_files.gif' alt='" . __( 's2member Secure-File Upload', 's2sfu' ). "' onclick='return false;' />
		". __( 's2member Secure-File Upload', 's2sfu' ) ."
	</a>
	";
	
}

/**
 * Modified from media_upload_file in WordPress 3.2.1
 * {@internal Missing Short Description}}
 *
 * @since 2.5.0
 *
 * @return unknown
 */
function s2sfu_media_upload_handler() {
	

	add_filter( 'media_upload_tabs', function($_default_tabs) {
		unset($_default_tabs['type_url']);
		unset($_default_tabs['gallery']);
		unset($_default_tabs['library']);

		return $_default_tabs;
	});

	add_filter( 'upload_dir', 's2sfu_upload_dir' );
	
	$errors = array();
	$id = 0;

	if ( isset($_POST['html-upload']) && !empty($_FILES) ) {
		
		check_admin_referer('media-form');
		// Upload File button was clicked
		$id = media_handle_upload('async-upload', $_REQUEST['post_id'] );
		unset($_FILES);
		
		if ( is_wp_error($id) ) {
			
			$errors['upload_error'] = $id;
			$id = false;
			
		}
		
		//http://domain/?s2member_file_download=
		$filename = get_post_meta( $id, '_wp_attached_file', true );
		
		$html = '<a href="' . site_url() . '/?s2member_file_download=' . $filename . '">' . $filename . '</a>';
		
		return media_send_to_editor( $html );
		
	}

	return wp_iframe( 'media_upload_type_s2sfu', 's2sfu', $errors, $id );
	
}
/**
 * Modified From media_upload_type_form in WordPress 3.2.1 Core
 * {@internal Missing Short Description}}
 *
 * @since 2.5.0
 *
 * @param unknown_type $type
 * @param unknown_type $errors
 * @param unknown_type $id
 */
function media_upload_type_s2sfu( $type = 'file', $errors = null, $id = null ) {
	
	media_upload_header();

	$post_id = isset( $_REQUEST['post_id'] )? intval( $_REQUEST['post_id'] ) : 0;

	$form_action_url = admin_url("media-upload.php?type=$type&tab=type&post_id=$post_id");
	$form_action_url = apply_filters('media_upload_form_url', $form_action_url, $type);
	?>
	
	<form enctype="multipart/form-data" method="post" action="<?php echo esc_attr($form_action_url); ?>" class="media-upload-form type-form validate" id="<?php echo $type; ?>-form">
	<?php submit_button( '', 'hidden', 'save', false ); ?>
	<input type="hidden" name="post_id" id="post_id" value="<?php echo (int) $post_id; ?>" />
	<?php wp_nonce_field('media-form'); ?>
	
	<h3 class="media-title"><?php _e('Add media files from your computer'); ?></h3>
	
	<?php s2sfu_media_upload_form( $errors ); ?>
	
	</form>
	<?php
}

/**
 * Modified From media_upload_form in WordPress 3.2.1 Core
 *
 * {@internal Missing Short Description}}
 *
 * @since 2.5.0
 *
 * @return unknown
 */
function s2sfu_media_upload_form( $errors ) {
	global $type, $tab, $pagenow;
		
	$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;

	$upload_size_unit = $max_upload_size =  wp_max_upload_size();
	$sizes = array( 'KB', 'MB', 'GB' );
	for ( $u = -1; $upload_size_unit > 1024 && $u < count( $sizes ) - 1; $u++ )
		$upload_size_unit /= 1024;
	if ( $u < 0 ) {
		$upload_size_unit = 0;
		$u = 0;
	} else {
		$upload_size_unit = (int) $upload_size_unit;
	}
?>

<div id="media-upload-notice">
<?php if (isset($errors['upload_notice']) ) { ?>
	<?php echo $errors['upload_notice']; ?>
<?php } ?>
</div>
<div id="media-upload-error">
<?php if (isset($errors['upload_error']) && is_wp_error($errors['upload_error'])) { ?>
	<?php echo $errors['upload_error']->get_error_message(); ?>
<?php } ?>
</div>
<?php
// Check quota for this blog if multisite
if ( is_multisite() && !is_upload_space_available() ) {
	echo '<p>' . sprintf( __( 'Sorry, you have filled your storage quota (%s MB).' ), get_space_allowed() ) . '</p>';
	return;
}

do_action('pre-upload-ui');
?>

<div id="html-upload-ui" <?php if ( $flash ) echo 'class="hide-if-js"'; ?>>
<?php do_action('pre-html-upload-ui'); ?>
	<p id="async-upload-wrap">
		<label class="screen-reader-text" for="async-upload"><?php _e('Upload'); ?></label>
		<input type="file" name="async-upload" id="async-upload" />
		<?php submit_button( __( 'Insert into Post' ), 'button', 'html-upload', false ); ?>
		<a href="#" onclick="try{top.tb_remove();}catch(e){}; return false;"><?php _e('Cancel'); ?></a>
	</p>
	<div class="clear"></div>
	<p class="media-upload-size"><?php printf( __( 'Maximum upload file size: %d%s' ), $upload_size_unit, $sizes[$u] ); ?></p>
	<?php if ( is_lighttpd_before_150() ): ?>
	<p><?php _e('If you want to use all capabilities of the uploader, like uploading multiple files at once, please update to lighttpd 1.5.'); ?></p>
	<?php endif;?>
<?php do_action('post-html-upload-ui', $flash); ?>
</div>
<?php do_action('post-upload-ui');

}

function s2sfu_upload_dir( $uploads ) {
	
	if ( version_compare( get_bloginfo( 'version' ), '3.0', '<' ) && is_ssl() )
		$wp_content_url = str_replace( 'http://' , 'https://' , get_option( 'siteurl' ) );
	else
		$wp_content_url = get_option( 'siteurl' );
	
	$wp_content_url 	.= '/wp-content';
	$wp_content_dir 	 = ABSPATH . 'wp-content';
	$wp_plugin_url 		 = $wp_content_url . '/plugins';
	$wp_plugin_dir 		 = $wp_content_dir . '/plugins';

	$dir = $wp_plugin_dir . '/s2member-files';
	$url = $wp_plugin_url . '/s2member-files';

	$bdir = $dir;
	$burl = $url;

	$subdir = '';

	$dir .= $subdir;
	$url .= $subdir;

	$uploads = array( 'path' => $dir, 'url' => $url, 'subdir' => $subdir, 'basedir' => $bdir, 'baseurl' => $burl, 'error' => false );
	
	// Make sure we have an uploads dir
	if ( ! wp_mkdir_p( $uploads['path'] ) ) {
		$message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $uploads['path'] );
		return array( 'error' => $message );
	}
	
	return $uploads;
	
}

function delete_s2sfu_file( $post_id ) {
	
	/* This is kind of hacky, the default WordPress delete functionality assumes the files are in /uploads/YYYY/MM/, 
	so we need to hook into the delete_attachment action and make sure we delete the file from the s2member-files/ directory as well.
	
	This should not cause any conflicts, the $filename for /uploads/YYYY/MM/ a file is "YYYY/MM/FILENAME", 
	the $filename for s2member-files/ a file is just "FILENAME". */

	$filename = get_post_meta( $post_id, '_wp_attached_file', true );
	@unlink( ABSPATH . 'wp-content/plugins/s2member-files/' . $filename );
	
}

function s2sfu_warning() {
	
	echo "
	<div id='s2sfu-warning' class='updated fade'><p><strong>" . __( 's2member secure-file Uploader is almost ready.' ) . "</strong> " . sprintf( __( 'You must install and activate the <a href="%1$s">s2member WordPress Membership Plugin</a> for it to work.' ), "http://www.s2member.com/2496.html" ) . "</p></div>
	";
	
}




function custom_media_upload_tab_name( $tabs ) {
    $newtab = array( 's2member_files_archive' => __('Protected files') );
    return array_merge( $tabs, $newtab );
}


function custom_media_upload_tab_content() {
	add_filter( 'media_upload_tabs', function($_default_tabs) {
		unset($_default_tabs['type_url']);
		unset($_default_tabs['gallery']);
		unset($_default_tabs['library']);

		return $_default_tabs;
	});

	wp_enqueue_style('media');
	media_upload_header();

	wp_iframe( 's2member_show_files' );
}

function s2member_show_files() {
	$path  = WP_PLUGIN_DIR. '/s2member-files';
	$files = glob($path . '/*.{mp3,mp4,avi,mpeg,doc,docx,pdf,txt}', GLOB_BRACE);
	$html  = '<h3>' .$path. '</h3> <form action="" id="s2member-files-archive-form">';

	foreach ($files as $i => $file) {
		$html .= '<input type="checkbox" data-filename="' .basename($file). '" class="file" name="file[]" />';
		$html .= basename($file);
	}

	$html .= '
		<br />
		<br />
		<button type="button" onclick="javascript:s2member_files_archive_selection();">'. esc_attr(__('Link selected files')) .'</button>
		<script>
		function s2member_files_archive_selection() {
			jQuery("#s2member-files-archive-form").find("input[type=checkbox]:checked").each(function(index, el) {
				var $check = jQuery(el);
				parent.send_to_editor(\'[s2File download="\' +$check.data("filename")+ \'" /]\');
			});
			
			parent.tb_remove();
		}
		</script>
	</form>
	';
    echo $html;
}


add_filter( 'media_upload_tabs', 'custom_media_upload_tab_name' );
add_action( 'media_upload_s2member_files_archive', 'custom_media_upload_tab_content' );
