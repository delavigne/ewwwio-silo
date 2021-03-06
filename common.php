<?php
// common functions for Standard and Cloud plugins

// TODO: make sure to update timestamp field in table for image record

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Hooks
 */
	// used to move the W3TC CDN uploads filter
/*	add_filter( 'wp_handle_upload', 'ewww_image_optimizer_handle_upload' );
	// used to turn off ewwwio_image_editor during uploads
	add_action( 'add_attachment', 'ewww_image_optimizer_add_attachment' );
	add_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 );
	add_filter( 'wp_generate_attachment_metadata', 'ewww_image_optimizer_resize_from_meta_data', 15, 2 );
// this filter turns off ewwwio_image_editor during save from the actual image editor
// and ensures that we parse the resizes list during the image editor save function
add_filter( 'load_image_to_edit_path', 'ewww_image_optimizer_editor_save_pre' );
// this hook is used to ensure we populate the metadata with webp images
add_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment_metadata', 8, 2 );
add_filter( 'manage_media_columns', 'ewww_image_optimizer_columns' );
// filters to set default permissions, anyone can override these if they wish
add_filter( 'ewww_image_optimizer_manual_permissions', 'ewww_image_optimizer_manual_permissions', 8 );
add_filter( 'ewww_image_optimizer_bulk_permissions', 'ewww_image_optimizer_admin_permissions', 8 );
add_filter( 'ewww_image_optimizer_admin_permissions', 'ewww_image_optimizer_admin_permissions', 8 );
add_filter( 'ewww_image_optimizer_superadmin_permissions', 'ewww_image_optimizer_superadmin_permissions', 8 );
// variable for plugin settings link
$plugin = plugin_basename ( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE );
add_filter( "plugin_action_links_$plugin", 'ewww_image_optimizer_settings_link' );
add_filter( 'intermediate_image_sizes_advanced', 'ewww_image_optimizer_image_sizes_advanced' );
add_filter( 'ewww_image_optimizer_settings', 'ewww_image_optimizer_filter_settings_page' );
add_filter( 'myarcade_filter_screenshot', 'ewww_image_optimizer_myarcade_thumbnail' );
add_filter( 'myarcade_filter_thumbnail', 'ewww_image_optimizer_myarcade_thumbnail' );
add_filter( 'jpeg_quality', 'ewww_image_optimizer_set_jpg_quality' );
add_action( 'manage_media_custom_column', 'ewww_image_optimizer_custom_column', 10, 2 );
add_action( 'plugins_loaded', 'ewww_image_optimizer_preinit' );
add_action( 'init', 'ewww_image_optimizer_gallery_support' );
add_action( 'admin_init', 'ewww_image_optimizer_admin_init' );
add_action( 'admin_action_ewww_image_optimizer_manual_optimize', 'ewww_image_optimizer_manual' );
add_action( 'admin_action_ewww_image_optimizer_manual_restore', 'ewww_image_optimizer_manual' );
add_action( 'admin_action_ewww_image_optimizer_manual_convert', 'ewww_image_optimizer_manual' );
add_action( 'delete_attachment', 'ewww_image_optimizer_delete' );
add_action( 'admin_menu', 'ewww_image_optimizer_admin_menu', 60 );
add_action( 'network_admin_menu', 'ewww_image_optimizer_network_admin_menu' );
add_action( 'load-upload.php', 'ewww_image_optimizer_load_admin_js' );
add_action( 'admin_action_bulk_optimize', 'ewww_image_optimizer_bulk_action_handler' ); 
add_action( 'admin_action_-1', 'ewww_image_optimizer_bulk_action_handler' ); 
add_action( 'admin_enqueue_scripts', 'ewww_image_optimizer_media_scripts' );
add_action( 'admin_enqueue_scripts', 'ewww_image_optimizer_settings_script' );
add_action( 'ewww_image_optimizer_auto', 'ewww_image_optimizer_auto' );
add_action( 'wr2x_retina_file_added', 'ewww_image_optimizer_retina', 20, 2 );
add_action( 'wp_ajax_ewww_webp_rewrite', 'ewww_image_optimizer_webp_rewrite' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'iocli.php' );
}*/

function ewwwio_memory( $function ) {
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
		global $ewww_memory;
//		$ewww_memory .= $function . ': ' . memory_get_usage(true) . "\n";
	}
}

if ( ! function_exists( 'boolval' ) ) {
	function boolval( $value ) {
		return (bool) $value;
	}
}

// function to check if set_time_limit() is disabled
function ewww_image_optimizer_stl_check() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ewww_image_optimizer_safemode_check() ) {
                return false;
        }
	if ( defined( 'EWWW_IMAGE_OPTIMIZER_DISABLE_STL' ) && EWWW_IMAGE_OPTIMIZER_DISABLE_STL ) {
                ewwwio_debug_message( 'stl disabled by user' );
                return false;
        }
	$disabled = ini_get('disable_functions');
	ewwwio_debug_message( "disable_functions = $disabled" );
	if ( preg_match( '/set_time_limit/', $disabled ) ) {
		ewwwio_memory( __FUNCTION__ );
		return false;
	} elseif ( function_exists( 'set_time_limit' ) ) {
		ewwwio_memory( __FUNCTION__ );
		return true;
	} else {
		ewwwio_debug_message( 'set_time_limit does not exist' );
                return false;
        }
}

function ewww_image_optimizer_preinit() {
	// TODO: for future translation capability
	load_plugin_textdomain( EWWW_IMAGE_OPTIMIZER_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
	
/**
 * Plugin initialization function
 */
function ewww_image_optimizer_upgrade() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_memory( __FUNCTION__ );
	if ( get_option( 'ewww_image_optimizer_version' ) < EWWW_IMAGE_OPTIMIZER_VERSION ) {
		//ewww_image_optimizer_install_table();
		ewww_image_optimizer_upgrade_table();
		ewww_image_optimizer_set_defaults();
		update_option( 'ewww_image_optimizer_version', EWWW_IMAGE_OPTIMIZER_VERSION );
	}
	ewwwio_memory( __FUNCTION__ );
//	ewww_image_optimizer_debug_log();
}

// Plugin initialization for admin area
function ewww_image_optimizer_admin_init() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_memory( __FUNCTION__ );
	ewww_image_optimizer_cloud_init();
	ewww_image_optimizer_upgrade();
		// set the common network settings if they have been POSTed
		if ( isset( $_POST['_ewww_nonce'] ) && current_user_can( 'manage_options' ) && wp_verify_nonce( $_REQUEST['_ewww_nonce'], 'ewww_image_optimizer_options' ) ) {
			ewwwio_debug_message( print_r( $_POST, true ) );
			$_POST['ewww_image_optimizer_debug'] = ( empty( $_POST['ewww_image_optimizer_debug'] ) ? false : true );
			update_option( 'ewww_image_optimizer_debug', $_POST['ewww_image_optimizer_debug'] );
			$_POST['ewww_image_optimizer_remove_meta'] = ( empty( $_POST['ewww_image_optimizer_remove_meta'] ) ? false : true );
			update_option( 'ewww_image_optimizer_remove_meta', $_POST['ewww_image_optimizer_remove_meta'] );
			if ( empty( $_POST['ewww_image_optimizer_jpg_level'] ) ) $_POST['ewww_image_optimizer_jpg_level'] = '';
			update_option( 'ewww_image_optimizer_jpg_level', (int) $_POST['ewww_image_optimizer_jpg_level'] );
			if ( empty( $_POST[ 'ewww_image_optimizer_png_level'] ) ) $_POST['ewww_image_optimizer_png_level'] = '';
			update_option( 'ewww_image_optimizer_png_level', (int) $_POST['ewww_image_optimizer_png_level'] );
			if ( empty( $_POST['ewww_image_optimizer_gif_level'] ) ) $_POST['ewww_image_optimizer_gif_level'] = '';
			update_option( 'ewww_image_optimizer_gif_level', (int) $_POST['ewww_image_optimizer_gif_level'] );
			if ( empty( $_POST['ewww_image_optimizer_pdf_level'] ) ) $_POST['ewww_image_optimizer_pdf_level'] = '';
			update_option( 'ewww_image_optimizer_pdf_level', (int) $_POST['ewww_image_optimizer_pdf_level'] );
			$_POST['ewww_image_optimizer_delete_originals'] = ( empty( $_POST['ewww_image_optimizer_delete_originals'] ) ? false : true );
			update_option( 'ewww_image_optimizer_delete_originals', $_POST['ewww_image_optimizer_delete_originals'] );
			$_POST['ewww_image_optimizer_jpg_to_png'] = ( empty( $_POST['ewww_image_optimizer_jpg_to_png'] ) ? false : true );
			update_option( 'ewww_image_optimizer_jpg_to_png', $_POST['ewww_image_optimizer_jpg_to_png'] );
			$_POST['ewww_image_optimizer_png_to_jpg'] = ( empty( $_POST['ewww_image_optimizer_png_to_jpg'] ) ? false : true );
			update_option( 'ewww_image_optimizer_png_to_jpg', $_POST['ewww_image_optimizer_png_to_jpg'] );
			$_POST['ewww_image_optimizer_gif_to_png'] = ( empty( $_POST['ewww_image_optimizer_gif_to_png'] ) ? false : true );
			update_option( 'ewww_image_optimizer_gif_to_png', $_POST['ewww_image_optimizer_gif_to_png'] );
			$_POST['ewww_image_optimizer_webp'] = ( empty( $_POST['ewww_image_optimizer_webp'] ) ? false : true );
			update_option( 'ewww_image_optimizer_webp', $_POST['ewww_image_optimizer_webp'] );
			if (empty($_POST['ewww_image_optimizer_jpg_background'])) $_POST['ewww_image_optimizer_jpg_background'] = '';
			update_option( 'ewww_image_optimizer_jpg_background', ewww_image_optimizer_jpg_background( $_POST['ewww_image_optimizer_jpg_background'] ) );
			if (empty($_POST['ewww_image_optimizer_jpg_quality'])) $_POST['ewww_image_optimizer_jpg_quality'] = '';
			update_option( 'ewww_image_optimizer_jpg_quality', ewww_image_optimizer_jpg_quality( $_POST['ewww_image_optimizer_jpg_quality'] ) );
			if ( empty( $_POST['ewww_image_optimizer_cloud_key'] ) ) $_POST['ewww_image_optimizer_cloud_key'] = '';
			update_option( 'ewww_image_optimizer_cloud_key', ewww_image_optimizer_cloud_key_sanitize( $_POST['ewww_image_optimizer_cloud_key'] ) );
			if ( empty( $_POST['ewww_image_optimizer_aux_paths'] ) ) $_POST['ewww_image_optimizer_aux_paths'] = '';
			update_option( 'ewww_image_optimizer_aux_paths', ewww_image_optimizer_aux_paths_sanitize( $_POST['ewww_image_optimizer_aux_paths'] ) );
			if ( empty( $_POST['ewww_image_optimizer_delay'] ) ) $_POST['ewww_image_optimizer_delay'] = '';
			update_option( 'ewww_image_optimizer_delay', (int) $_POST['ewww_image_optimizer_delay'] );
			if ( empty( $_POST['ewww_image_optimizer_maxmediawidth'] ) ) $_POST['ewww_image_optimizer_maxmediawidth'] = 0;
			update_option( 'ewww_image_optimizer_maxmediawidth', (int) $_POST['ewww_image_optimizer_maxmediawidth'] );
			if ( empty( $_POST['ewww_image_optimizer_maxmediaheight'] ) ) $_POST['ewww_image_optimizer_maxmediaheight'] = 0;
			update_option( 'ewww_image_optimizer_maxmediaheight', (int) $_POST['ewww_image_optimizer_maxmediaheight'] );
			/*if ( empty( $_POST['ewww_image_optimizer_maxotherwidth'] ) ) $_POST['ewww_image_optimizer_maxotherwidth'] = 0;
			update_option( 'ewww_image_optimizer_maxotherwidth', (int) $_POST['ewww_image_optimizer_maxotherwidth'] );
			if ( empty( $_POST['ewww_image_optimizer_maxotherheight'] ) ) $_POST['ewww_image_optimizer_maxotherheight'] = 0;
			update_option( 'ewww_image_optimizer_maxotherheight', (int) $_POST['ewww_image_optimizer_maxotherheight'] );*/
			if ( empty( $_POST['ewww_image_optimizer_skip_size'] ) ) $_POST['ewww_image_optimizer_skip_size'] = '';
			update_option( 'ewww_image_optimizer_skip_size', (int) $_POST['ewww_image_optimizer_skip_size'] );
			if ( empty( $_POST['ewww_image_optimizer_skip_png_size'] ) ) $_POST['ewww_image_optimizer_skip_png_size'] = '';
			update_option('ewww_image_optimizer_skip_png_size', (int) $_POST['ewww_image_optimizer_skip_png_size'] );
			$_POST['ewww_image_optimizer_skip_bundle'] = ( empty( $_POST['ewww_image_optimizer_skip_bundle'] ) ? false : true );
			update_option( 'ewww_image_optimizer_skip_bundle', $_POST['ewww_image_optimizer_skip_bundle'] );
			if ( ! empty( $_POST['ewww_image_optimizer_optipng_level'] ) ) {
				update_option( 'ewww_image_optimizer_optipng_level', (int) $_POST['ewww_image_optimizer_optipng_level'] );
				update_option( 'ewww_image_optimizer_pngout_level', (int) $_POST['ewww_image_optimizer_pngout_level'] );
			}
			$_POST['ewww_image_optimizer_disable_pngout'] = ( empty( $_POST['ewww_image_optimizer_disable_pngout'] ) ? false : true );
			update_option( 'ewww_image_optimizer_disable_pngout', $_POST['ewww_image_optimizer_disable_pngout'] );
			// TODO: display the confirmation for saving settings
			ewww_image_optimizer_network_settings_saved(); // rename this without 'network'
		}

	ewww_image_optimizer_exec_init();
	// require the files that do the bulk processing 
//	require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'bulk.php' );
	require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'aux-optimize.php' );
	// queue the function that contains custom styling for our progressbars
	add_action('admin_enqueue_scripts', 'ewww_image_optimizer_progressbar_style'); 
	ewwwio_memory( __FUNCTION__ );
//	ewww_image_optimizer_debug_log();
}

// sets all the tool constants to false
function ewww_image_optimizer_disable_tools() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_JPEGTRAN' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_JPEGTRAN', false);
	}
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_OPTIPNG' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_OPTIPNG', false);
	}
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_PNGOUT' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_PNGOUT', false);
	}
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_GIFSICLE' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_GIFSICLE', false);
	}
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_PNGQUANT' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_PNGQUANT', false);
	}
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_WEBP' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_WEBP', false);
	}
	ewwwio_memory( __FUNCTION__ );
}

// generates css include for progressbars to match admin style
function ewww_image_optimizer_progressbar_style() {
	wp_add_inline_style( 'jquery-ui-progressbar', ".ui-widget-header { background-color: " . ewww_image_optimizer_admin_background() . "; }" );
	ewwwio_memory( __FUNCTION__ );
}

// determines the background color to use based on the selected theme
function ewww_image_optimizer_admin_background() {
	if ( function_exists( 'wp_add_inline_style' ) ) {
		$user_info = wp_get_current_user();
		switch( $user_info->admin_color ) {
			case 'midnight':
				return "#e14d43";
			case 'blue':
				return "#096484";
			case 'light':
				return "#04a4cc";
			case 'ectoplasm':
				return "#a3b745";
			case 'coffee':
				return "#c7a589";
			case 'ocean':
				return "#9ebaa0";
			case 'sunrise':
				return "#dd823b";
			default:
				return "#0073aa";
		}
	}
	ewwwio_memory( __FUNCTION__ );
}

// tells WP to ignore the 'large network' detection by filtering the results of wp_is_large_network()
function ewww_image_optimizer_large_network() {
	return false;
}

// lets the user know their network settings have been saved
function ewww_image_optimizer_network_settings_saved() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	echo "<div id='ewww-image-optimizer-settings-saved' class='updated fade'><p><strong>" . esc_html__('Settings saved', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</strong></p></div>";
}

// adds a global settings page to the network admin settings menu
function ewww_image_optimizer_network_admin_menu() {
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		// add options page to the settings menu
		$permissions = apply_filters( 'ewww_image_optimizer_superadmin_permissions', '' );
		$ewww_network_options_page = add_submenu_page(
			'settings.php',				//slug of parent
			'EWWW Image Optimizer',			//Title
			'EWWW Image Optimizer',			//Sub-menu title
			$permissions,				//Security
			EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE,	//File to open
			'ewww_image_optimizer_options'		//Function to call
		);
	} 
}

// adds the bulk optimize and settings page to the admin menu
function ewww_image_optimizer_admin_menu() {
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	// adds bulk optimize to the media library menu
	$ewww_bulk_page = add_media_page( esc_html__( 'Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $permissions, 'ewww-image-optimizer-bulk', 'ewww_image_optimizer_bulk_preview' );
	$ewww_unoptimized_page = add_media_page( esc_html__( 'Unoptimized Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Unoptimized Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $permissions, 'ewww-image-optimizer-unoptimized', 'ewww_image_optimizer_display_unoptimized_media' );
	$ewww_webp_migrate_page = add_submenu_page( null, esc_html__( 'Migrate WebP Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Migrate WebP Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $permissions, 'ewww-image-optimizer-webp-migrate', 'ewww_image_optimizer_webp_migrate_preview' );
	if ( ! function_exists( 'is_plugin_active' ) ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( ! is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		// add options page to the settings menu
		$ewww_options_page = add_options_page(
			'EWWW Image Optimizer',		//Title
			'EWWW Image Optimizer',		//Sub-menu title
			apply_filters( 'ewww_image_optimizer_admin_permissions', '' ),		//Security
			EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE,			//File to open
			'ewww_image_optimizer_options'	//Function to call
		);
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
		add_media_page( esc_html__( 'Dynamic Image Debugging', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Dynamic Image Debugging', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $permissions, 'ewww-image-optimizer-dynamic-debug', 'ewww_image_optimizer_dynamic_image_debug' );
	}
	if ( is_plugin_active( 'image-store/ImStore.php' ) || is_plugin_active_for_network( 'image-store/ImStore.php' ) ) {
		$ims_menu ='edit.php?post_type=ims_gallery';
		$ewww_ims_page = add_submenu_page( $ims_menu, esc_html__( 'Image Store Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ), 'ims_change_settings', 'ewww-ims-optimize', 'ewww_image_optimizer_ims');
//		add_action( 'admin_footer-' . $ewww_ims_page, 'ewww_image_optimizer_debug' );
	}
}

// enqueue custom jquery stylesheet for bulk optimizer
function ewww_image_optimizer_media_scripts( $hook ) {
	if ( $hook == 'upload.php' ) {
		wp_enqueue_script( 'jquery-ui-tooltip' );
		wp_enqueue_style( 'jquery-ui-tooltip-custom', plugins_url( '/includes/jquery-ui-1.10.1.custom.css', __FILE__ ) );
	}
}

// used to output debug messages to a logfile in the plugin folder in cases where output to the screen is a bad idea
function ewww_image_optimizer_debug_log() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_debug;
	if (! empty( $ewww_debug ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
		$timestamp = date( 'y-m-d h:i:s.u' ) . "\n";
		if ( ! file_exists( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'debug.log' ) ) {
			touch( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'debug.log' );
		}
		$ewww_debug_log = str_replace( '<br>', "\n", $ewww_debug );
		file_put_contents( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'debug.log', $timestamp . $ewww_debug_log, FILE_APPEND );
	}
	$ewww_debug = '';
	ewwwio_memory( __FUNCTION__ );
}

// adds a link on the Plugins page for the EWWW IO settings
function ewww_image_optimizer_settings_link( $links ) {
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	// load the html for the settings link
	if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		$settings_link = '<a href="network/settings.php?page=' . plugin_basename( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE ) . '">' . esc_html__( 'Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</a>';
	} else {
		$settings_link = '<a href="options-general.php?page=' . plugin_basename( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE ) . '">' . esc_html__( 'Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</a>';
	}
	// load the settings link into the plugin links array
	array_unshift( $links, $settings_link );
	// send back the plugin links array
	return $links;
}

// check for GD support of both PNG and JPG
function ewww_image_optimizer_gd_support() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( function_exists( 'gd_info' ) ) {
		$gd_support = gd_info();
		ewwwio_debug_message( "GD found, supports:" ); 
		foreach ( $gd_support as $supports => $supported ) {
			 ewwwio_debug_message( "$supports: $supported" );
		}
		ewwwio_memory( __FUNCTION__ );
		if ( ( ! empty( $gd_support["JPEG Support"] ) || ! empty( $gd_support["JPG Support"] ) ) && ! empty( $gd_support["PNG Support"] ) ) {
			return TRUE;
		}
	}
	return FALSE;
}

// check for IMagick support of both PNG and JPG
function ewww_image_optimizer_imagick_support() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( extension_loaded( 'imagick' ) ) {
		$imagick = new Imagick();
		$formats = $imagick->queryFormats();
		if ( in_array( 'PNG', $formats ) && in_array( 'JPG', $formats ) ) {
			return true;
		}
	}
	return false;
}

// check for IMagick support of both PNG and JPG
function ewww_image_optimizer_gmagick_support() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( extension_loaded( 'gmagick' ) ) {
		$gmagick = new Gmagick();
		$formats = $gmagick->queryFormats();
		if ( in_array( 'PNG', $formats ) && in_array( 'JPG', $formats ) ) {
			return true;
		}
	}
	return false;
}

function ewww_image_optimizer_disable_resizes_sanitize( $disabled_resizes ) {
	if ( is_array( $disabled_resizes ) ) {
		return $disabled_resizes;
	} else {
		return '';
	}
}

function ewww_image_optimizer_aux_paths_sanitize( $input ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if (empty($input)) {
		return '';
	}
	$path_array = array();
	$paths = explode("\n", $input);
	foreach ($paths as $path) {
		$path = sanitize_text_field( $path );
		ewwwio_debug_message( "validating auxiliary path: $path" );
		// retrieve the location of the wordpress upload folder
		$upload_dir = apply_filters( 'ewww_image_optimizer_folder_restriction', wp_upload_dir() );
		// retrieve the path of the upload folder
		$upload_path = trailingslashit($upload_dir['basedir']);
		if ( is_dir( $path ) && ( strpos( $path, ABSPATH ) === 0 || strpos( $path, $upload_path ) === 0 ) ) {
			$path_array[] = $path;
		}
	}
//	ewww_image_optimizer_debug_log();
	ewwwio_memory( __FUNCTION__ );
	return $path_array;
}

// replacement for escapeshellarg() that won't kill non-ASCII characters
function ewww_image_optimizer_escapeshellarg( $arg ) {
	if ( PHP_OS === 'WINNT' ) {
		$safe_arg = '"' . $arg . '"';
	} else {
		$safe_arg = "'" . str_replace("'", "'\"'\"'", $arg) . "'";
	}
	ewwwio_memory( __FUNCTION__ );
	return $safe_arg;
}

// Retrieves/sanitizes jpg background fill setting or returns null for png2jpg conversions
function ewww_image_optimizer_jpg_background( $background = null ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( $background === null ) {
		// retrieve the user-supplied value for jpg background color
		$background = ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_background' );
	}
	//verify that the supplied value is in hex notation
	if ( preg_match( '/^\#*([0-9a-fA-F]){6}$/', $background ) ) {
		// we remove a leading # symbol, since we take care of it later
		preg_replace( '/#/', '', $background );
		// send back the verified, cleaned-up background color
		ewwwio_debug_message( "background: $background" );
		ewwwio_memory( __FUNCTION__ );
		return $background;
	} else {
		// send back a blank value
		ewwwio_memory( __FUNCTION__ );
		return NULL;
	}
}

// Retrieves/sanitizes the jpg quality setting for png2jpg conversion or returns null
function ewww_image_optimizer_jpg_quality( $quality = null ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( $quality === null ) {
		// retrieve the user-supplied value for jpg quality
		$quality = ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_quality' );
	}
	// verify that the quality level is an integer, 1-100
	if ( preg_match( '/^(100|[1-9][0-9]?)$/', $quality ) ) {
		ewwwio_debug_message( "quality: $quality" );
		// send back the valid quality level
		ewwwio_memory( __FUNCTION__ );
		return $quality;
	} else {
		// send back nothing
		ewwwio_memory( __FUNCTION__ );
		return NULL;
	}
}

function ewww_image_optimizer_set_jpg_quality( $quality ) {
	$new_quality = ewww_image_optimizer_jpg_quality();
	if ( ! empty( $new_quality ) ) {
		return $new_quality;
	}
	return $quality;
}
		

// check filesize, and prevent errors by ensuring file exists, and that the cache has been cleared
function ewww_image_optimizer_filesize( $file ) {
	if ( is_file( $file ) ) {
		// flush the cache for filesize
		clearstatcache();
		// find out the size of the new PNG file
		return filesize( $file );
	} else {
		return 0;
	}
}

/**
 * Manually process an image from the Media Library
 */
function ewww_image_optimizer_manual() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_defer;
	$ewww_defer = false;
	// check permissions of current user
	$permissions = apply_filters( 'ewww_image_optimizer_manual_permissions', '' );
	if ( FALSE === current_user_can( $permissions ) ) {
		// display error message if insufficient permissions
		wp_die( esc_html__( 'You do not have permission to optimize images.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	// make sure we didn't accidentally get to this page without an attachment to work on
	if ( FALSE === isset($_GET['ewww_attachment_ID'])) {
		// display an error message since we don't have anything to work on
		wp_die( esc_html__('No attachment ID was provided.', EWWW_IMAGE_OPTIMIZER_DOMAIN));
	}
	session_write_close();
	// store the attachment ID value
	$attachment_ID = intval($_GET['ewww_attachment_ID']);
	if ( empty( $_REQUEST['ewww_manual_nonce'] ) || ! wp_verify_nonce( $_REQUEST['ewww_manual_nonce'], "ewww-manual-$attachment_ID" ) ) {
		wp_die( esc_html__( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	// retrieve the existing attachment metadata
	$original_meta = wp_get_attachment_metadata( $attachment_ID );
	// if the call was to optimize...
	if ($_REQUEST['action'] === 'ewww_image_optimizer_manual_optimize') {
		// call the optimize from metadata function and store the resulting new metadata
		$new_meta = ewww_image_optimizer_resize_from_meta_data($original_meta, $attachment_ID);
	} elseif ($_REQUEST['action'] === 'ewww_image_optimizer_manual_restore') {
		$new_meta = ewww_image_optimizer_restore_from_meta_data($original_meta, $attachment_ID);
	}
	global $ewww_attachment;
	$ewww_attachment['id'] = $attachment_ID;
	$ewww_attachment['meta'] = $new_meta;
	add_filter( 'w3tc_cdn_update_attachment_metadata', 'ewww_image_optimizer_w3tc_update_files' );
	// update the attachment metadata in the database
	wp_update_attachment_metadata( $attachment_ID, $new_meta );
	ewww_image_optimizer_debug_log();
	// store the referring webpage location
	$sendback = wp_get_referer();
	// sanitize the referring webpage location
	$sendback = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $sendback);
	// send the user back where they came from
	wp_redirect( $sendback );
	// we are done, nothing to see here
	ewwwio_memory( __FUNCTION__ );
	exit(0);
}

/**
 * Manually restore a converted image
 */
function ewww_image_optimizer_restore_from_meta_data( $meta, $id ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// get the filepath
	list( $file_path, $upload_path ) = ewww_image_optimizer_attachment_path( $meta, $id );
	$file_path = get_attached_file( $id );
	if ( ! empty( $meta['converted'] ) ) {
		if ( file_exists( $meta['orig_file'] ) ) {
			// update the filename in the metadata
			$meta['file'] = $meta['orig_file'];
			// update the optimization results in the metadata
			$meta['ewww_image_optimizer'] = __( 'Original Restored', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			$meta['orig_file'] = $file_path;
			$meta['real_orig_file'] = $file_path;
			$meta['converted'] = 0;
			unlink( $meta['orig_file'] );
			unset( $meta['orig_file'] );
			$meta['file'] = str_replace($upload_path, '', $meta['file']);
			// if we don't already have the update attachment filter
			if (FALSE === has_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment'))
				// add the update attachment filter
				add_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10, 2);
		} else {
			remove_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10);
		}
	}
	if ( isset( $meta['sizes'] ) ) {
		// process each resized version
		$processed = array();
		// meta sizes don't contain a path, so we calculate one
		$base_dir = trailingslashit( dirname( $file_path ) );
		foreach( $meta['sizes'] as $size => $data ) {
			// check through all the sizes we've processed so far
			foreach( $processed as $proc => $scan ) {
				// if a previous resize had identical dimensions
				if ( $scan['height'] == $data['height'] && $scan['width'] == $data['width'] && isset( $meta['sizes'][ $proc ]['converted'] ) ) {
					// point this resize at the same image as the previous one
					$meta['sizes'][ $size ]['file'] = $meta['sizes'][ $proc ]['file'];
				}
			}
			if ( isset( $data['converted'] ) ) {
				// if this is a unique size
				if ( file_exists( $base_dir . $data['orig_file'] ) ) {
					// update the filename
					$meta['sizes'][ $size ]['file'] = $data['orig_file'];
					// update the optimization results
					$meta['sizes'][ $size ]['ewww_image_optimizer'] = __( 'Original Restored', EWWW_IMAGE_OPTIMIZER_DOMAIN );
					$meta['sizes'][ $size ]['orig_file'] = $data['file'];
					$meta['sizes'][ $size ]['real_orig_file'] = $data['file'];
					$meta['sizes'][ $size ]['converted'] = 0;
						// if we don't already have the update attachment filter
						if ( FALSE === has_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment' ) ) {
							// add the update attachment filter
							add_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10, 2 );
						}
					unlink( $base_dir . $data['file'] );
					unset( $meta['sizes'][ $size ]['orig_file'] );
				}
				// store info on the sizes we've processed, so we can check the list for duplicate sizes
				$processed[$size]['width'] = $data['width'];
				$processed[$size]['height'] = $data['height'];
			}		
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return $meta;
}

// deletes 'orig_file' when an attachment is being deleted
function ewww_image_optimizer_delete ($id) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	// retrieve the image metadata
	$meta = wp_get_attachment_metadata($id);
	// if the attachment has an original file set
	if ( ! empty( $meta['orig_file'] ) ) {
		unset($rows);
		// get the filepath from the metadata
		$file_path = $meta['orig_file'];
		// get the filename
		$filename = basename($file_path);
		// delete any residual webp versions
		$webpfile = $filename . '.webp';
		$webpfileold = preg_replace( '/\.\w+$/', '.webp', $filename );
		if ( file_exists( $webpfile) ) {
			unlink( $webpfile );
		}
		if ( file_exists( $webpfileold) ) {
			unlink( $webpfileold );
		}
		// retrieve any posts that link the original image
		$esql = "SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%$filename%'";
		$rows = $wpdb->get_row($esql);
		// if the original file still exists and no posts contain links to the image
		if ( file_exists( $file_path ) && empty( $rows ) ) {
			unlink( $file_path );
			$wpdb->delete( $wpdb->ewwwio_images, array( 'path' => $file_path ) );
		}
	}
	// remove the regular image from the ewwwio_images tables
	list( $file_path, $upload_path ) = ewww_image_optimizer_attachment_path( $meta, $id );
	$wpdb->delete($wpdb->ewwwio_images, array('path' => $file_path));
	// resized versions, so we can continue
	if (isset($meta['sizes']) ) {
		// one way or another, $file_path is now set, and we can get the base folder name
		$base_dir = dirname($file_path) . '/';
		// check each resized version
		foreach($meta['sizes'] as $size => $data) {
			// delete any residual webp versions
			$webpfile = $base_dir . $data['file'] . '.webp';
			$webpfileold = preg_replace( '/\.\w+$/', '.webp', $base_dir . $data['file'] );
			if ( file_exists( $webpfile) ) {
				unlink( $webpfile );
			}
			if ( file_exists( $webpfileold) ) {
				unlink( $webpfileold );
			}
			$wpdb->delete($wpdb->ewwwio_images, array('path' => $base_dir . $data['file']));
			// if the original resize is set, and still exists
			if (!empty($data['orig_file']) && file_exists($base_dir . $data['orig_file'])) {
				unset($srows);
				// retrieve the filename from the metadata
				$filename = $data['orig_file'];
				// retrieve any posts that link the image
				$esql = "SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%$filename%'";
				$srows = $wpdb->get_row($esql);
				// if there are no posts containing links to the original, delete it
				if(empty($srows)) {
					unlink($base_dir . $data['orig_file']);
					$wpdb->delete($wpdb->ewwwio_images, array('path' => $base_dir . $data['orig_file']));
				}
			}
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return;
}

function ewww_image_optimizer_cloud_key_sanitize( $key ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$key = trim( $key );
	ewwwio_debug_message( print_r( $_REQUEST, true ) );
	if ( ewww_image_optimizer_cloud_verify( false, $key ) ) {
		ewwwio_debug_message( 'sanitize (verification) successful' );
		ewwwio_memory( __FUNCTION__ );
//		ewww_image_optimizer_debug_log();
		return $key;
	} else {
		ewwwio_debug_message( 'sanitize (verification) failed' );
		ewwwio_memory( __FUNCTION__ );
//		ewww_image_optimizer_debug_log();
		return '';
	}
}

function ewww_image_optimizer_full_cloud() {
//	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_level' ) > 10 && ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_level' ) > 10 ) {
//		ewwwio_debug_message( 'all cloud mode enabled, no local' );
		return true;
	} elseif ( EWWW_IMAGE_OPTIMIZER_DOMAIN == 'ewww-image-optimizer-cloud' ) {
//		ewwwio_debug_message( 'cloud-only plugin, no local' );
		return true;
	}
//	ewwwio_debug_message( 'local mode allowed' );
	return false;
}

// turns on the cloud settings when they are all disabled
function ewww_image_optimizer_cloud_enable() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	add_option( 'ewww_image_optimizer_jpg_level', '20' );
	add_option( 'ewww_image_optimizer_png_level', '20' );
	add_option( 'ewww_image_optimizer_gif_level', '10' );
	add_option( 'ewww_image_optimizer_pdf_level', '10' );
	// just to make sure they get set with & without a database
	ewww_image_optimizer_set_option('ewww_image_optimizer_jpg_level', 20);
	ewww_image_optimizer_set_option('ewww_image_optimizer_png_level', 20);
	ewww_image_optimizer_set_option('ewww_image_optimizer_gif_level', 10);
	ewww_image_optimizer_set_option('ewww_image_optimizer_pdf_level', 10);
}

// adds our version to the useragent for http requests
function ewww_image_optimizer_cloud_useragent() {
	return 'EWWW SILO/' . EWWW_IMAGE_OPTIMIZER_VERSION;
}

// submits the api key for verification
function ewww_image_optimizer_cloud_verify( $cache = true, $api_key = '' ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( empty( $api_key ) ) {
		$api_key = ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' );
	} elseif ( empty( $api_key ) && ! empty( $_POST['ewww_image_optimizer_cloud_key'] ) ) {
		$api_key = $_POST['ewww_image_optimizer_cloud_key'];
	}
	if ( empty( $api_key ) ) {
		ewwwio_debug_message( 'no api key' );
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_level' ) > 10 ) {
			update_option( 'ewww_image_optimizer_jpg_level', 10 );
		}
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_level' ) > 10 && ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_level' ) != 40 ) {
			update_option( 'ewww_image_optimizer_png_level', 10 );
		}
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_pdf_level' ) > 0 ) {
			update_option( 'ewww_image_optimizer_pdf_level', 0 );
		}
		return false;
	}
	$ewww_cloud_status = get_transient( 'ewww_image_optimizer_cloud_status' );
	if ( false && $cache && preg_match( '/great/', $ewww_cloud_status ) ) {
		ewwwio_debug_message( 'using cached verification' );
		return $ewww_cloud_status;
	}
		$result = ewww_image_optimizer_cloud_post_key( 'optimize.exactlywww.com', 'https', $api_key );
		if ( empty( $result->success ) ) { 
			$result->throw_for_status( false );
			ewwwio_debug_message( "verification failed" );
		} elseif ( ! empty( $result->body ) && preg_match( '/(great|exceeded)/', $result->body ) ) {
			$verified = $result->body;
			ewwwio_debug_message( "verification success" );
		} else {
			ewwwio_debug_message( "verification failed" );
			ewwwio_debug_message( print_r( $result, true ) );
		}
	if ( empty( $verified ) ) {
		ewwwio_memory( __FUNCTION__ );
		return FALSE;
	} else {
		set_transient( 'ewww_image_optimizer_cloud_status', $verified, 3600 ); 
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_level' ) < 20 && ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_level' ) < 20 && ewww_image_optimizer_get_option( 'ewww_image_optimizer_gif_level' ) < 20 && ewww_image_optimizer_get_option( 'ewww_image_optimizer_pdf_level' ) == 0 ) {
			ewww_image_optimizer_cloud_enable();
		}
		ewwwio_debug_message( "verification body contents: " . $result->body );
		ewwwio_memory( __FUNCTION__ );
		return $verified;
	}
}

function ewww_image_optimizer_cloud_post_key( $ip, $transport, $key ) {
	$useragent = ewww_image_optimizer_cloud_useragent();
	$result = Requests::post( "$transport://$ip/verify/", array(), array( 'api_key' => $key ), array( 'timeout' => 5, 'useragent' => $useragent ) );
	return $result;
}

// checks the provided api key for quota information
function ewww_image_optimizer_cloud_quota() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$api_key = ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' );
	$url = "https://optimize.exactlywww.com/quota/";
	$useragent = ewww_image_optimizer_cloud_useragent();
	$result = Requests::post( $url, array(), array( 'api_key' => $api_key ), array( 'timeout' => 5, 'useragent' => $useragent ) );
	/*$result = wp_remote_post( $url, array(
		'timeout' => 5,
		'sslverify' => false,
		'body' => array( 'api_key' => $api_key )
	) );*/
	if ( ! $result->success ) {
		$result->throw_for_status( false );
		ewwwio_debug_message( "quota request failed: $error_message" );
		ewwwio_memory( __FUNCTION__ );
		return '';
	} elseif ( ! empty( $result->body ) ) {
		ewwwio_debug_message( "quota data retrieved: " . $result->body );
		$quota = explode( ' ', $result->body );
		ewwwio_memory( __FUNCTION__ );
		if ( $quota[0] == 0 && $quota[1] > 0 ) {
			return esc_html( sprintf( _n( 'optimized %1$d images, usage will reset in %2$d day.', 'optimized %1$d images, usage will reset in %2$d days.', $quota[2], EWWW_IMAGE_OPTIMIZER_DOMAIN ), $quota[1], $quota[2] ) );
		} elseif ( $quota[0] == 0 && $quota[1] < 0 ) {
			return esc_html( sprintf( _n( '%1$d image credit remaining.', '%1$d image credits remaining.', abs( $quota[1] ), EWWW_IMAGE_OPTIMIZER_DOMAIN ), abs( $quota[1] ) ) );
		} elseif ( $quota[0] > 0 && $quota[1] < 0 ) {
			$real_quota = $quota[0] - $quota[1];
			return esc_html( sprintf( _n( '%1$d image credit remaining.', '%1$d image credits remaining.', $real_quota, EWWW_IMAGE_OPTIMIZER_DOMAIN ), $real_quota ) );
		} else {
			return esc_html( sprintf( _n( 'used %1$d of %2$d, usage will reset in %3$d day.', 'used %1$d of %2$d, usage will reset in %3$d days.', $quota[2], EWWW_IMAGE_OPTIMIZER_DOMAIN ), $quota[1], $quota[0], $quota[2] ) );
		}
	}
}

/* submits an image to the cloud optimizer and saves the optimized image to disk
 *
 * Returns an array of the $file, $results, $converted to tell us if an image changes formats, and the $original file if it did.
 *
 * @param   string $file		Full absolute path to the image file
 * @param   string $type		mimetype of $file
 * @param   boolean $convert		true says we want to attempt conversion of $file
 * @param   string $newfile		filename of new converted image
 * @param   string $newtype		mimetype of $newfile
 * @param   boolean $fullsize		is this the full-size original?
 * @param   array $jpg_params		r, g, b values and jpg quality setting for conversion
 * @returns array
*/
function ewww_image_optimizer_cloud_optimizer( $file, $type, $convert = false, $newfile = null, $newtype = null, $fullsize = false, $jpg_params = array( 'r' => '255', 'g' => '255', 'b' => '255', 'quality' => null ) ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
//	$ewww_cloud_ip = get_transient( 'ewww_image_optimizer_cloud_ip' );
//	$ewww_cloud_transport = get_transient( 'ewww_image_optimizer_cloud_transport' );
	$ewww_status = get_transient( 'ewww_image_optimizer_cloud_status' );
	$started = microtime( true );
	if ( preg_match( '/exceeded/', $ewww_status ) ) {
		if ( ! ewww_image_optimizer_cloud_verify() ) { 
			return array( $file, false, 'key verification failed', 0 );
/*		} else {
			$ewww_cloud_ip = get_transient( 'ewww_image_optimizer_cloud_ip' );
			$ewww_cloud_transport = get_transient( 'ewww_image_optimizer_cloud_transport' );*/
		}
	}
	EWWWIO_CLI::line( ewww_image_optimizer_cloud_quota() );
	// calculate how much time has elapsed since we started
	$elapsed = microtime( true ) - $started;
	// output how much time has elapsed since we started
	ewwwio_debug_message( sprintf( 'Cloud verify took %.3f seconds', $elapsed ) );
	$ewww_status = get_transient( 'ewww_image_optimizer_cloud_status' );
	if ( ! empty ( $ewww_status ) && preg_match( '/exceeded/', $ewww_status ) ) {
		ewwwio_debug_message( 'license exceeded, image not processed' );
		return array($file, false, 'exceeded', 0);
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_metadata_skip_full' ) && $fullsize ) {
		$metadata = 1;
	} elseif ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) ){
        	// don't copy metadata
                $metadata = 0;
        } else {
                // copy all the metadata
                $metadata = 1;
        }
	if ( empty( $convert ) ) {
		$convert = 0;
	} else {
		$convert = 1;
	}
	$lossy_fast = 0;
	if ( ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_skip_full') && $fullsize ) {
		$lossy = 0;
	} elseif ( $type == 'image/png' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_level' ) >= 40 ) {
		$lossy = 1;
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_level' ) == 40 ) {
			$lossy_fast = 1;
		}
	} elseif ( $type == 'image/jpeg' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_level' ) >= 30 ) {
		$lossy = 1;
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_level' ) == 30 ) {
			$lossy_fast = 1;
		}
	} elseif ( $type == 'application/pdf' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_pdf_level' ) == 20 ) {
		$lossy = 1;
	} else {
		$lossy = 0;
	}
	if ( $newtype == 'image/webp' ) {
		$webp = 1;
	} else {
		$webp = 0;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_level' ) == 30 ) {
		$png_compress = 1;
	} else {
		$png_compress = 0;
	}
	ewwwio_debug_message( "file: $file " );
	ewwwio_debug_message( "type: $type" );
	ewwwio_debug_message( "convert: $convert" );
	ewwwio_debug_message( "newfile: $newfile" );
	ewwwio_debug_message( "newtype: $newtype" );
	ewwwio_debug_message( "webp: $webp" );
	ewwwio_debug_message( "jpg_params: " . print_r($jpg_params, true) );
	$api_key = ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_key');
	$url = "https://optimize.exactlywww.com/";
	$boundary = generate_password( 24 );

	$useragent = ewww_image_optimizer_cloud_useragent();
	$headers = array(
        	'content-type' => 'multipart/form-data; boundary=' . $boundary,
//		'timeout' => 90,
//		'httpversion' => '1.0',
//		'blocking' => true
		);
	$post_fields = array(
		'filename' => $file,
		'convert' => $convert, 
		'metadata' => $metadata, 
		'api_key' => $api_key,
		'red' => $jpg_params['r'],
		'green' => $jpg_params['g'],
		'blue' => $jpg_params['b'],
		'quality' => $jpg_params['quality'],
		'compress' => $png_compress,
		'lossy' => $lossy,
		'lossy_fast' => $lossy_fast,
		'webp' => $webp,
	);

	$payload = '';
	foreach ($post_fields as $name => $value) {
        	$payload .= '--' . $boundary;
	        $payload .= "\r\n";
	        $payload .= 'Content-Disposition: form-data; name="' . $name .'"' . "\r\n\r\n";
	        $payload .= $value;
	        $payload .= "\r\n";
	}

	$payload .= '--' . $boundary;
	$payload .= "\r\n";
	$payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file) . '"' . "\r\n";
	$payload .= 'Content-Type: ' . $type . "\r\n";
	$payload .= "\r\n";
	$payload .= file_get_contents($file);
	$payload .= "\r\n";
	$payload .= '--' . $boundary;
	$payload .= 'Content-Disposition: form-data; name="submitHandler"' . "\r\n";
	$payload .= "\r\n";
	$payload .= "Upload\r\n";
	$payload .= '--' . $boundary . '--';

	// retrieve the time when the optimizer starts
//	$started = microtime(true);
	$response = Requests::post(
		$url,
		$headers,
		$payload,
		array(
			'timeout' => 90,
			'useragent' => $useragent,
		)
	);
/*	$response = wp_remote_post( $url, array(
		'timeout' => 90,
		'headers' => $headers,
		'sslverify' => false,
		'body' => $payload,
		) );*/
//	$elapsed = microtime(true) - $started;
//	$ewww_debug .= "processing image via cloud took $elapsed seconds<br>";
	if ( ! $response->success ) {
		$response->throw_for_status( false );
		ewwwio_debug_message( "optimize failed, see exception" );
		return array( $file, false, 'cloud optimize failed', 0 );
	} else {
		$tempfile = $file . ".tmp";
		file_put_contents( $tempfile, $response->body );
		$orig_size = filesize( $file );
		$newsize = $orig_size;
		$converted = false;
		$msg = '';
		if ( preg_match( '/exceeded/', $response->body ) ) {
			ewwwio_debug_message( 'License Exceeded' );
			set_transient( 'ewww_image_optimizer_cloud_status', 'exceeded', 3600 );
			$msg = 'exceeded';
			unlink( $tempfile );
		} elseif ( ewww_image_optimizer_mimetype( $tempfile, 'i' ) == $type ) {
			$newsize = filesize( $tempfile );
			ewwwio_debug_message( "cloud results: $newsize (new) vs. $orig_size (original)" );
			rename( $tempfile, $file );
		} elseif ( ewww_image_optimizer_mimetype( $tempfile, 'i' ) == 'image/webp' ) {
			$newsize = filesize( $tempfile );
			ewwwio_debug_message( "cloud results: $newsize (new) vs. $orig_size (original)" );
			rename( $tempfile, $newfile );
		} elseif ( ewww_image_optimizer_mimetype( $tempfile, 'i' ) == $newtype ) {
			$converted = true;
			$newsize = filesize( $tempfile );
			ewwwio_debug_message( "cloud results: $newsize (new) vs. $orig_size (original)" );
			rename( $tempfile, $newfile );
			$file = $newfile;
		} else {
			unlink( $tempfile );
		}
		ewwwio_memory( __FUNCTION__ );
		return array( $file, $converted, $msg, $newsize );
	}
}

// check the database to see if we've done this image before
function ewww_image_optimizer_check_table( $file, $orig_size ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	ewwwio_debug_message( "checking for $file with size: $orig_size" );
	$image = ewww_image_optimizer_find_already_optimized( $file );
	if ( is_array( $image ) && $image['image_size'] == $orig_size ) {
		$prev_string = " - " . __( 'Previously Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		if ( preg_match( '/' . __( 'License exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '/', $image['results'] ) ) {
			return;
		}
		$already_optimized = preg_replace( "/$prev_string/", '', $image['results'] );
		$already_optimized = $already_optimized . $prev_string;
		ewwwio_debug_message( "already optimized: {$image['path']} - $already_optimized" );
		ewwwio_memory( __FUNCTION__ );
		return $already_optimized;
	}
}

// receives a path, optimized size, and an original size to insert into ewwwwio_images table
// if this is a $new image, copy the result stored in the database
function ewww_image_optimizer_update_table( $attachment, $opt_size, $orig_size, $preserve_results = false ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	$already_optimized = ewww_image_optimizer_find_already_optimized( $attachment );
	if ( $already_optimized && $opt_size >= $orig_size ) {
		$prev_string = ' - ' . __( 'Previously Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	} else {
		$prev_string = '';
	}
	if ( is_array( $already_optimized ) && ! empty( $already_optimized['orig_size'] ) && $already_optimized['orig_size'] > $orig_size ) {
		$orig_size = $already_optimized['orig_size'];
	}
	ewwwio_debug_message( "savings: $opt_size (new) vs. $orig_size (orig)" );
	if ( is_array( $already_optimized ) && ! empty( $already_optimized['results'] ) && $preserve_results && $opt_size == $orig_size) {
		$results_msg = $already_optimized['results'];
	} elseif ( $opt_size >= $orig_size ) {
		ewwwio_debug_message( "original and new file are same size (or something weird made the new one larger), no savings" );
		$results_msg = __( 'No savings', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	} else {
		// calculate how much space was saved
		$savings = intval( $orig_size ) - intval( $opt_size );
		// convert it to human readable format
		$savings_str = size_format( $savings, 1 );
		// replace spaces and extra decimals with proper html entity encoding
		$savings_str = preg_replace( '/\.0 B /', ' B', $savings_str );
		$savings_str = str_replace( ' ', '&nbsp;', $savings_str );
		// determine the percentage savings
		$percent = 100 - ( 100 * ( $opt_size / $orig_size ) );
		// use the percentage and the savings size to output a nice message to the user
		$results_msg = sprintf( __( "Reduced by %01.1f%% (%s)", EWWW_IMAGE_OPTIMIZER_DOMAIN ),
			$percent,
			$savings_str
		) . $prev_string;
		ewwwio_debug_message( "original and new file are different size: $results_msg" );
	}
	if ( empty( $already_optimized ) ) {
		ewwwio_debug_message( "creating new record, path: $attachment, size: $opt_size" );
		// store info on the current image for future reference
		$wpdb->insert( $wpdb->ewwwio_images, array(
			'path' => $attachment,
			'image_size' => $opt_size,
			'orig_size' => $orig_size,
			'results' => $results_msg,
			'updated' => date( 'Y-m-d H:i:s' ),
			'updates' => 1,
		) );
	} else {
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
			$trace = ewwwio_debug_backtrace();
		} else {
			$trace = '';
		}
		ewwwio_debug_message( "updating existing record ({$already_optimized['id']}), path: $attachment, size: $opt_size" );
		// store info on the current image for future reference
		$wpdb->update( $wpdb->ewwwio_images,
			array(
				'image_size' => $opt_size,
				'results' => $results_msg,
				'updates' => $already_optimized['updates'] + 1,
				'trace' => $trace,
			),
			array(
				'id' => $already_optimized['id'],
			)
		);
	}
	ewwwio_memory( __FUNCTION__ );
	$wpdb->flush();
	ewwwio_memory( __FUNCTION__ );
	return $results_msg;
}

// called to process each image in the loop for images outside of media library
function ewww_image_optimizer_aux_images_loop( $attachment = null, $auto = false ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_defer;
	$ewww_defer = false;
	$output = array();
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! $auto && ( empty( $_REQUEST['ewww_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) ) {
		$output['error'] = esc_html__( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		echo json_encode( $output );
		die();
	}
	session_write_close();
	if ( ! empty( $_REQUEST['ewww_wpnonce'] ) ) {
		// find out if our nonce is on it's last leg/tick
		$tick = wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' );
		if ( $tick === 2 ) {
			ewwwio_debug_message( 'nonce on its last leg' );
			$output['new_nonce'] = wp_create_nonce( 'ewww-image-optimizer-bulk' );
		} else {
			ewwwio_debug_message( 'nonce still alive and kicking' );
			$output['new_nonce'] = '';
		}
	}
	// retrieve the time when the optimizer starts
	$started = microtime( true );
	// get the 'aux attachments' with a list of attachments remaining
	$attachments = get_option( 'ewww_image_optimizer_aux_attachments' );
	if ( empty( $attachment ) ) {
		$attachment = array_shift( $attachments );
	}
	// do the optimization for the current image
	$results = ewww_image_optimizer( $attachment );
	//global $ewww_exceed;
	$ewww_status = get_transient( 'ewww_image_optimizer_cloud_status' );
	if ( ! empty ( $ewww_status ) && preg_match( '/exceeded/', $ewww_status ) ) {
		if ( ! $auto ) {
			$output['error'] = esc_html__( 'License Exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			echo json_encode( $output );
		}
		die();
	}
	// store the updated list of attachment IDs back in the 'bulk_attachments' option
	update_option( 'ewww_image_optimizer_aux_attachments', $attachments, false );
	if ( ! $auto ) {
		// output the path
		$output['results'] = sprintf( "<p>" . esc_html__( 'Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . " <strong>%s</strong><br>", esc_html( $attachment ) );
		// tell the user what the results were for the original image
		$output['results'] .= sprintf( "%s<br>", $results[1] );
		// calculate how much time has elapsed since we started
		$elapsed = microtime( true ) - $started;
		// output how much time has elapsed since we started
		$output['results'] .= sprintf( esc_html__( 'Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p>", $elapsed );
		if ( get_site_option( 'ewww_image_optimizer_debug' ) ) {
			global $ewww_debug;
			$output['results'] .= '<div style="background-color:#ffff99;">' . $ewww_debug . '</div>';
		}
		if ( ! empty( $attachments ) ) {
			$next_file = array_shift( $attachments );
			$loading_image = plugins_url( '/images/wpspin.gif', __FILE__ );
			$output['next_file'] = "<p>" . esc_html__( 'Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . " <b>$next_file</b>&nbsp;<img src='$loading_image' alt='loading'/></p>";
		}
		echo json_encode( $output );
		ewwwio_memory( __FUNCTION__ );
		die();
	}
	ewwwio_memory( __FUNCTION__ );
}

// processes metadata and looks for any webp version to insert in the meta
function ewww_image_optimizer_update_attachment_metadata( $meta, $ID ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( "attachment id: $ID" );
	list( $file_path, $upload_path ) = ewww_image_optimizer_attachment_path( $meta, $ID );
	// don't do anything else if the attachment path can't be retrieved
	if ( ! is_file( $file_path ) ) {
		ewwwio_debug_message( "could not retrieve path" );
		return $meta;
	}
	ewwwio_debug_message( "retrieved file path: $file_path" );
	if ( is_file( $file_path . '.webp' ) ) {
		$meta['sizes']['webp-full'] = array(
			'file' => pathinfo( $file_path, PATHINFO_BASENAME ) . '.webp',
			'width' => 0,
			'height' => 0,
			'mime-type' => 'image/webp',
		);
		
	}
	// if the file was converted
	// resized versions, so we can continue
	if ( isset( $meta['sizes'] ) ) {
		ewwwio_debug_message( 'processing resizes for webp updates' );
		// meta sizes don't contain a path, so we use the foldername from the original to generate one
		$base_dir = trailingslashit( dirname( $file_path ) );
		// process each resized version
		$processed = array();
		foreach( $meta['sizes'] as $size => $data ) {
			$resize_path = $base_dir . $data['file'];
			// update the webp paths
			if ( is_file( $resize_path . '.webp' ) ) {
				$meta['sizes'][ 'webp-' . $size ] = array(
					'file' => $data['file'] . '.webp',
					'width' => 0,
					'height' => 0,
					'mime-type' => 'image/webp',
				);
			}
		}
	}
	ewwwio_memory( __FUNCTION__ );
	// send back the updated metadata
	return $meta;
}

// looks for a retina version of the original file so that we can optimize that too
function ewww_image_optimizer_hidpi_optimize( $orig_path ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$hidpi_suffix = apply_filters( 'ewww_image_optimizer_hidpi_suffix', '@2x' );
	$pathinfo = pathinfo( $orig_path );
	$hidpi_path = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . $hidpi_suffix . '.' . $pathinfo['extension'];
	if ( ewww_image_optimizer_test_parallel_opt() ) {
	//if ( ewww_image_optimizer_test_parallel_opt( $ID ) ) {
		if ( ! empty( $_REQUEST['ewww_force'] ) ) {
			$force = true;
		} else {
			$force = false;
		}
		add_filter( 'http_headers_useragent', 'ewww_image_optimizer_cloud_useragent', PHP_INT_MAX );
		global $ewwwio_async_optimize_media;
		$async_path = str_replace( ABSPATH, '', $hidpi_path );
		$ewwwio_async_optimize_media->data( array( 'ewwwio_path' => $async_path, 'ewww_force' => $force ) )->dispatch();
	} else {
		ewww_image_optimizer( $hidpi_path );
	}
}

function ewww_image_optimizer_remote_fetch( $id, $meta ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $as3cf;
	if ( ! function_exists( 'download_url' ) ) {
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
	}
	if ( class_exists( 'Amazon_S3_And_CloudFront' ) ) {
		$full_url = get_attached_file( $id );
		if ( strpos( $full_url, 's3' ) === 0 ) {
			$full_url = $as3cf->get_attachment_url( $id, null, null, $meta );
		}
		$filename = get_attached_file( $id, true );
		ewwwio_debug_message( "amazon s3 fullsize url: $full_url" );
		ewwwio_debug_message( "unfiltered fullsize path: $filename" );
		$temp_file = download_url( $full_url );
		if ( ! is_wp_error( $temp_file ) ) {
			rename( $temp_file, $filename );
		}
		// resized versions, so we'll grab those too
		if ( isset( $meta['sizes'] ) ) {
			$disabled_sizes = ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_resizes_opt' );
			ewwwio_debug_message( 'retrieving resizes' );
			// meta sizes don't contain a path, so we calculate one
			$base_dir = trailingslashit( dirname( $filename) );
			// process each resized version
			$processed = array();
			foreach($meta['sizes'] as $size => $data) {
				ewwwio_debug_message( "processing size: $size" );
				if ( preg_match('/webp/', $size) ) {
					continue;
				}
				if ( ! empty( $disabled_sizes[$size] ) ) {
					continue;
				}
				// initialize $dup_size
				$dup_size = false;
				// check through all the sizes we've processed so far
				foreach($processed as $proc => $scan) {
					// if a previous resize had identical dimensions
					if ($scan['height'] == $data['height'] && $scan['width'] == $data['width']) {
						// found a duplicate resize
						$dup_size = true;
					}
				}
				// if this is a unique size
				if (!$dup_size) {
					$resize_path = $base_dir . $data['file'];
					$resize_url = $as3cf->get_attachment_url( $id, null, $size, $meta );
					ewwwio_debug_message( "fetching $resize_url to $resize_path" );
					$temp_file = download_url( $resize_url );
					if ( ! is_wp_error( $temp_file ) ) {
						rename( $temp_file, $resize_path );
					}
				}
				// store info on the sizes we've processed, so we can check the list for duplicate sizes
				$processed[$size]['width'] = $data['width'];
				$processed[$size]['height'] = $data['height'];
			}
		}
	}
	if ( class_exists( 'WindowsAzureStorageUtil' ) && get_option( 'azure_storage_use_for_default_upload' ) ) {
		$full_url = $meta['url'];
		$filename = $meta['file'];
		ewwwio_debug_message( "azure fullsize url: $full_url" );
		ewwwio_debug_message( "fullsize path: $filename" );
		$temp_file = download_url( $full_url );
		if ( ! is_wp_error( $temp_file ) ) {
			rename( $temp_file, $filename );
		}
		// resized versions, so we'll grab those too
		if (isset($meta['sizes']) ) {
			$disabled_sizes = ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_resizes_opt' );
			ewwwio_debug_message( 'retrieving resizes' );
			// meta sizes don't contain a path, so we calculate one
			$base_dir = trailingslashit( dirname( $filename) );
			$base_url = trailingslashit( dirname( $full_url ) );
			// process each resized version
			$processed = array();
			foreach($meta['sizes'] as $size => $data) {
				ewwwio_debug_message( "processing size: $size" );
				if ( preg_match('/webp/', $size) ) {
					continue;
				}
				if ( ! empty( $disabled_sizes[$size] ) ) {
					continue;
				}
				// initialize $dup_size
				$dup_size = false;
				// check through all the sizes we've processed so far
				foreach($processed as $proc => $scan) {
					// if a previous resize had identical dimensions
					if ($scan['height'] == $data['height'] && $scan['width'] == $data['width']) {
						// found a duplicate resize
						$dup_size = true;
					}
				}
				// if this is a unique size
				if (!$dup_size) {
					$resize_path = $base_dir . $data['file'];
					$resize_url = $base_url . $data['file'];
					ewwwio_debug_message( "fetching $resize_url to $resize_path" );
					$temp_file = download_url( $resize_url );
					if ( ! is_wp_error( $temp_file ) ) {
						rename( $temp_file, $resize_path );
					}
				}
				// store info on the sizes we've processed, so we can check the list for duplicate sizes
				$processed[$size]['width'] = $data['width'];
				$processed[$size]['height'] = $data['height'];
			}
		}
	}
	if ( ! empty( $filename ) && file_exists( $filename ) ) {
		return $filename;
	} else {
		return false;
	}
}

function ewww_image_optimizer_check_table_as3cf( $meta, $ID, $s3_path ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$local_path = get_attached_file( $ID, true );
	ewwwio_debug_message( "unfiltered local path: $local_path" );
	if ( $local_path !== $s3_path ) {
		ewww_image_optimizer_update_table_as3cf( $local_path, $s3_path );
	}
	if ( isset( $meta['sizes'] ) ) {
		ewwwio_debug_message( 'updating s3 resizes' );
		// meta sizes don't contain a path, so we calculate one
		$local_dir = trailingslashit( dirname( $local_path ) );
		$s3_dir = trailingslashit( dirname( $s3_path ) );
		// process each resized version
		$processed = array();
		foreach ( $meta['sizes'] as $size => $data ) {
			if ( strpos( $size, 'webp') === 0 ) {
				continue;
			}
			// check through all the sizes we've processed so far
			foreach ( $processed as $proc => $scan ) {
				// if a previous resize had identical dimensions
				if ( $scan['height'] === $data['height'] && $scan['width'] === $data['width'] ) {
					// found a duplicate resize
					continue;
				}
			}
			// if this is a unique size
			$local_resize_path = $local_dir . $data['file'];
			$s3_resize_path = $s3_dir . $data['file'];
			if ( $local_resize_path !== $s3_resize_path ) {
				ewww_image_optimizer_update_table_as3cf( $local_resize_path, $s3_resize_path );
			}
			// store info on the sizes we've processed, so we can check the list for duplicate sizes
			$processed[ $size ]['width'] = $data['width'];
			$processed[ $size ]['height'] = $data['height'];
		}
	}
	global $wpdb;
	$wpdb->flush();
}

function ewww_image_optimizer_update_table_as3cf( $local_path, $s3_path ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// first we need to see if anything matches the old local path
//	$s3_query = $wpdb->prepare( "SELECT id,path,orig_size,results FROM $wpdb->ewwwio_images WHERE path = %s", $s3_path );
//	$s3_images = $wpdb->get_results( $s3_query, ARRAY_A );
	$s3_image = ewww_image_optimizer_find_already_optimized( $s3_path );
	ewwwio_debug_message( "looking for $s3_path" );
	if ( is_array( $s3_image ) ) {
/*		foreach ( $s3_images as $s3_image ) {
			if ( $s3_image['path'] !== $s3_path ) {
				ewwwio_debug_message( "{$s3_image['path']} does not match $s3_path, continuing our search" );
			} else {*/
				global $wpdb;
				ewwwio_debug_message( "found $s3_path in db" );
				// when we find a match by the s3 path, we need to find out if there are already records for the local path
				$found_local_image = ewww_image_optimizer_find_already_optimized( $local_path );
//				$local_query = $wpdb->prepare( "SELECT id,path,orig_size FROM $wpdb->ewwwio_images WHERE path = %s", $local_path );
//				$local_images = $wpdb->get_results( $local_query, ARRAY_A );
				ewwwio_debug_message( "looking for $local_path" );
/*				foreach ( $local_images as $local_image ) {
					if ( $local_image['path'] === $local_path ) {
						$found_local_image = $local_image;
						break;
					}
				}*/
				// if we found records for both local and s3 paths, we delete the s3 record, but store the original size in the local record
				if ( ! empty( $found_local_image ) && is_array( $found_local_image ) ) {
					ewwwio_debug_message( "found $local_path in db" );
					$wpdb->delete( $wpdb->ewwwio_images,
						array(
							'id' => $s3_image['id'],
						),
						array(
							'%d'
						)
					);
					if ( $s3_image['orig_size'] > $found_local_image['orig_size'] ) {
						$wpdb->update( $wpdb->ewwwio_images,
							array(
								'orig_size' => $s3_image['orig_size'],
								'results' => $s3_image['results'],
							),
							array(
								'id' => $found_local_image['id'],
							)
						);
					}
				// if we just found an s3 path and no local match, then we just update the path in the table to the local path
				} else {
					ewwwio_debug_message( "just updating s3 to local" );
					$wpdb->update( $wpdb->ewwwio_images,
						array(
							'path' => $local_path,
						),
						array(
							'id' => $s3_image['id'],
						)
					);
				}
				//break;
//			}
//		}
	}
}	

// resizes Media Library uploads based on the maximum dimensions specified by the user
function ewww_image_optimizer_resize_upload( $file ) {
	// parts adapted from Imsanity (THANKS Jason!)
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ! $file ) {
		return false;
	}
//	ewwwio_debug_message( print_r( $_SERVER, true ) );
	if ( ! empty( $_REQUEST['post_id'] ) || ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] === 'upload-attachment' ) || ( ! empty( $_SERVER['HTTP_REFERER'] ) && strpos( $_SERVER['HTTP_REFERER'], 'media-new.php' ) ) ) {
		$maxwidth = ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxmediawidth' );
		$maxheight = ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxmediaheight' );
		ewwwio_debug_message( 'resizing image from media library or attached to post' );
	} else {
		$maxwidth = ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxotherwidth' );
		$maxheight = ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxotherheight' );
		ewwwio_debug_message( 'resizing images from somewhere else' );
	}

	// allow other developers to modify the dimensions to their liking based on whatever parameters they might choose
	list( $maxwidth, $maxheight ) = apply_filters( 'ewww_image_optimizer_resize_dimensions', array( $maxwidth, $maxheight ) );

	//check that options are not == 0
	if ( $maxwidth == 0 && $maxheight == 0 ) {
		return false;
	}
	//check file type
	$type = ewww_image_optimizer_mimetype( $file, 'i' );
	if ( strpos( $type, 'image' ) === FALSE ) {
		ewwwio_debug_message( 'not an image, cannot resize' );
		return false;
	}
	//check file size (dimensions)
	list( $oldwidth, $oldheight ) = getimagesize( $file );
	if ( $oldwidth <= $maxwidth && $oldheight <= $maxheight ) {
		ewwwio_debug_message( 'image too small for resizing' );
		return false;
	}
	list( $newwidth, $newheight ) = wp_constrain_dimensions( $oldwidth, $oldheight, $maxwidth, $maxheight );
	if ( ! function_exists( 'wp_get_image_editor' ) ) {
		ewwwio_debug_message( 'no image editor function' );
		return false;
	}
	remove_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 );
	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) ) {
		ewwwio_debug_message( 'could not get image editor' );
		return false;
	}
	if ( function_exists( 'exif_read_data' ) && $type === 'image/jpeg' ) {
		$exif = @exif_read_data( $file );
		if ( is_array( $exif ) && array_key_exists( 'Orientation', $exif ) ) {
			$orientation = $exif['Orientation'];
			switch( $orientation ) {
				case 3:
					$editor->rotate( 180 );
					break;
				case 6:
					$editor->rotate( -90 );
					break;
				case 8:
					$editor->rotate( 90 );
					break;
			}
		}
	}
	$resized_image = $editor->resize( $newwidth, $newheight );
	if ( is_wp_error( $resized_image ) ) {
		ewwwio_debug_message( 'error during resizing' );
		return false;
	}
	$new_file = $editor->generate_filename( 'tmp' );
	$orig_size = filesize( $file );
	$saved = $editor->save( $new_file );
	if ( is_wp_error( $saved ) ) {
		ewwwio_debug_message( 'error saving resized image' );
	}
	add_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 );
	$new_size = ewww_image_optimizer_filesize( $new_file );
	if ( $new_size && $new_size < $orig_size ) {
		// generate a retina file from the full original if they have WP Retina 2x Pro
		if ( function_exists( 'wr2x_is_pro' ) && wr2x_is_pro() ) {
			$full_size_needed = wr2x_getoption( "full_size", "wr2x_basics", false );
			if ( $full_size_needed ) {
				// Is the file related to this size there?
				$retina_file = '';
	
				$pathinfo = pathinfo( $file ) ;
				$retina_file = trailingslashit( $pathinfo['dirname'] ) . $pathinfo['filename'] . wr2x_retina_extension() . $pathinfo['extension'];
	
				if ( $retina_file && ! file_exists( $retina_file ) && wr2x_are_dimensions_ok( $oldwidth, $oldheight, $newwidth * 2, $newheight * 2 ) ) {
					$image = wr2x_vt_resize( $file, $newwidth * 2, $newheight * 2, false, $retina_file );
				}
			}
		}
		rename( $new_file, $file );
		// store info on the current image for future reference
		global $wpdb;
		$already_optimized = ewww_image_optimizer_find_already_optimized( $file );
		// if the original file has never been optimized, then just update the record that was created with the proper filename (because the resized file has usually been optimized)
		if ( empty( $already_optimized ) ) {
			$tmp_exists = $wpdb->update( $wpdb->ewwwio_images,
				array(
					'path' => $file,
					'orig_size' => $orig_size,
				),
				array(
					'path' => $new_file,
				)
			);
			// if the tmp file didn't get optimized (and it shouldn't), then just insert a dummy record to be updated shortly
			if ( ! $tmp_exists ) {
				$wpdb->insert( $wpdb->ewwwio_images, array(
					'path' => $file,
					'orig_size' => $orig_size,
				) );
			}
		// otherwise, we delete the record created from optimizing the resized file, and update our records for the original file
		} else {
			$temp_optimized = ewww_image_optimizer_find_already_optimized( $new_file );
			if ( is_array( $temp_optimized ) && ! empty( $temp_optimized['id'] ) ) {
				$wpdb->delete( $wpdb->ewwwio_images,
					array(
						'id' => $temp_optimized['id'],
					),
					array(
						'%d',
					)
				);
			}
			// should not need this, as the image will get optimized shortly
			//ewww_image_optimizer_update_table( $file, $new_size, $orig_size );
		}
		return array( $newwidth, $newheight );
	}
	if ( file_exists( $new_file ) ) {
		unlink( $new_file );
	}
	return false;
}

function ewww_image_optimizer_find_already_optimized( $attachment ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	$query = $wpdb->prepare( "SELECT * FROM $wpdb->ewwwio_images WHERE path = %s", $attachment );
	$optimized_query = $wpdb->get_results( $query, ARRAY_A );
	if ( ! empty( $optimized_query ) ) {
		foreach ( $optimized_query as $image ) {
			if ( $image['path'] != $attachment ) {
				ewwwio_debug_message( "{$image['path']} does not match $attachment, continuing our search" );
			} else {
				ewwwio_debug_message( "found a match for $attachment" );
				return $image;
			}
		}
	}
	return false;
}

function ewww_image_optimizer_test_background_opt() {
	if ( ewww_image_optimizer_detect_wpsf_location_lock() ) {
		return false;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
		return false;
	}
	return true;
}

function ewww_image_optimizer_test_parallel_opt( $id = 0 ) {
	if ( ewww_image_optimizer_detect_wpsf_location_lock() ) {
		return false;
	}
	if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_parallel_optimization') ) {
		return false;
	}
	if ( empty( $id ) ) {
		return true;
	}
	$type = get_post_mime_type( $id );
	if ( $type == 'image/jpeg' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_to_png' ) ) {
		return false;
	}
	if ( $type == 'image/png' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_to_jpg' ) ) {
		return false;
	}
	if ( $type == 'image/gif' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_gif_to_png' ) ) {
		return false;
	}
	if ( $type == 'application/pdf' ) {
		return false;
	}
	return true;
}

// takes a human-readable size, and generates an approximate byte-size
function ewww_image_optimizer_size_unformat( $formatted ) {
	$size_parts = explode( '&nbsp;', $formatted );
	switch ( $size_parts[1] ) {
		case 'B':
			return intval( $size_parts[0] );
		case 'kB':
			return intval( $size_parts[0] * 1024 );
		case 'MB':
			return intval( $size_parts[0] * 1048576 );
		case 'GB':
			return intval( $size_parts[0] * 1073741824 );
		case 'TB':
			return intval( $size_parts[0] * 1099511627776 );
		default:
			return 0;
	}
}

// generate a unique filename for a converted image
function ewww_image_optimizer_unique_filename( $file, $fileext ) {
	// strip the file extension
	$filename = preg_replace( '/\.\w+$/', '', $file );
	// set the increment to 1 (we always rename converted files with an increment)
	$filenum = 1;
	// while a file exists with the current increment
	while ( file_exists( $filename . '-' . $filenum . $fileext ) ) {
		// increment the increment...
		$filenum++;
	}
	// all done, let's reconstruct the filename
	ewwwio_memory( __FUNCTION__ );
	return array( $filename . '-' . $filenum . $fileext, $filenum );
}

/**
 * Check the submitted PNG to see if it has transparency
 */
function ewww_image_optimizer_png_alpha( $filename ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// determine what color type is stored in the file
	$color_type = ord( @file_get_contents( $filename, NULL, NULL, 25, 1 ) );
	ewwwio_debug_message( "color type: $color_type" );
	// if it is set to RGB alpha or Grayscale alpha
	if ( $color_type == 4 || $color_type == 6 ) {
		ewwwio_debug_message( 'transparency found' );
		return true;
	} elseif ( $color_type == 3 && ewww_image_optimizer_gd_support() ) {
		$image = imagecreatefrompng( $filename );
		if ( imagecolortransparent( $image ) >= 0 ) {
			ewwwio_debug_message( 'transparency found' );
			return true;
		}
		list( $width, $height ) = getimagesize( $filename );
		ewwwio_debug_message( "image dimensions: $width x $height" );
		ewwwio_debug_message( 'preparing to scan image' );
		for ( $y = 0; $y < $height; $y++ ) {
			for ( $x = 0; $x < $width; $x++ ) {
				$color = imagecolorat( $image, $x, $y );
				$rgb = imagecolorsforindex( $image, $color );
				if ( $rgb['alpha'] > 0 ) {
					ewwwio_debug_message( 'transparency found' );
					return true;
				}
			}
		}
	}
	ewwwio_debug_message( 'no transparency' );
	ewwwio_memory( __FUNCTION__ );
	return false;
}

/**
 * Check the submitted GIF to see if it is animated
 */
function ewww_image_optimizer_is_animated( $filename ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// if we can't open the file in read-only buffered mode
	if(!($fh = @fopen($filename, 'rb'))) {
		return false;
	}
	// initialize $count
	$count = 0;
   
	// We read through the file til we reach the end of the file, or we've found
	// at least 2 frame headers
	while(!feof($fh) && $count < 2) {
		$chunk = fread($fh, 1024 * 100); //read 100kb at a time
		$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
	}
	fclose($fh);
	ewwwio_debug_message( "scanned GIF and found $count frames" );
	// return TRUE if there was more than one frame, or FALSE if there was only one
	ewwwio_memory( __FUNCTION__ );
	return $count > 1;
}

// test mimetype based on file extension instead of file contents
// only use for places where speed outweighs accuracy
function ewww_image_optimizer_quick_mimetype( $path ) {
	$pathextension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	switch ( $pathextension ) {
		case 'jpg':
		case 'jpeg':
		case 'jpe':
			return 'image/jpeg';
		case 'png':
			return 'image/png';
		case 'gif':
			return 'image/gif';
		case 'pdf':
			return 'application/pdf';
		default:
			return false;
	}
}

// make sure an array/object can be parsed by a foreach()
function ewww_image_optimizer_iterable( $var ) {
	return ! empty( $var ) && ( is_array( $var ) || is_object( $var ) );
}

/**
 * Print column header for optimizer results in the media library using
 * the `manage_media_columns` hook.
 */
function ewww_image_optimizer_columns( $defaults ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$defaults['ewww-image-optimizer'] = esc_html__( 'Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	ewwwio_memory( __FUNCTION__ );
	return $defaults;
}

/**
 * Print column data for optimizer results in the media library using
 * the `manage_media_custom_column` hook.
 */
function ewww_image_optimizer_custom_column( $column_name, $id ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// once we get to the EWWW IO custom column
	if ( $column_name == 'ewww-image-optimizer' ) {
		// retrieve the metadata
		$meta = wp_get_attachment_metadata( $id );
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
			$print_meta = print_r( $meta, TRUE );
			$print_meta = preg_replace( array('/ /', '/\n+/' ), array( '&nbsp;', '<br />' ), $print_meta );
			echo '<div style="background-color:#ffff99;font-size: 10px;padding: 10px;margin:-10px -10px 10px;line-height: 1.1em">' . $print_meta . '</div>';
		}
		$ewww_cdn = false;
		if( ! empty( $meta['cloudinary'] ) ) {
			esc_html_e( 'Cloudinary image', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			return;
		}
		if ( class_exists( 'WindowsAzureStorageUtil' ) && ! empty( $meta['url'] ) ) {
			esc_html_e( 'Azure Storage image', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			$ewww_cdn = true;
		}
		if ( class_exists( 'Amazon_S3_And_CloudFront' ) && preg_match( '/^(http|s3)\w*:/', get_attached_file( $id ) ) ) {
			esc_html_e( 'Amazon S3 image', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			$ewww_cdn = true;
		}
		list( $file_path, $upload_path ) = ewww_image_optimizer_attachment_path( $meta, $id );
		// if the file does not exist
		if ( empty( $file_path ) && ! $ewww_cdn ) {
			esc_html_e( 'Could not retrieve file path.', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			ewww_image_optimizer_debug_log();
			return;
		}
		$msg = '';
		$convert_desc = '';
		$convert_link = '';
		if ( $ewww_cdn ) {
			$type = get_post_mime_type( $id );
		} else {
			// retrieve the mimetype of the attachment
			$type = ewww_image_optimizer_mimetype( $file_path, 'i' );
			// get a human readable filesize
			$file_size = size_format( filesize( $file_path ), 2 );
			$file_size = preg_replace( '/\.00 B /', ' B', $file_size );
		}
		$skip = ewww_image_optimizer_skip_tools();
		// run the appropriate code based on the mimetype
		switch( $type ) {
			case 'image/jpeg':
				// if jpegtran is missing, tell them that
				if( ! EWWW_IMAGE_OPTIMIZER_JPEGTRAN && ! $skip['jpegtran'] ) {
					$valid = false;
					$msg = '<br>' . wp_kses( sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>jpegtran</em>' ), array( 'em' => array() ) );
				} else {
					$convert_link = esc_html__('JPG to PNG', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$convert_desc = esc_attr__( 'WARNING: Removes metadata. Requires GD or ImageMagick. PNG is generally much better than JPG for logos and other images with a limited range of colors.', EWWW_IMAGE_OPTIMIZER_DOMAIN );
				}
				break; 
			case 'image/png':
				// if pngout and optipng are missing, tell the user
				if( ! EWWW_IMAGE_OPTIMIZER_PNGOUT && ! EWWW_IMAGE_OPTIMIZER_OPTIPNG && ! $skip['optipng'] && ! $skip['pngout'] ) {
					$valid = false;
					$msg = '<br>' . wp_kses( sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<em>optipng/pngout</em>' ), array( 'em' => array() ) );
				} else {
					$convert_link = esc_html__('PNG to JPG', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$convert_desc = esc_attr__('WARNING: This is not a lossless conversion and requires GD or ImageMagick. JPG is much better than PNG for photographic use because it compresses the image and discards data. Transparent images will only be converted if a background color has been set.', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				break;
			case 'image/gif':
				// if gifsicle is missing, tell the user
				if( ! EWWW_IMAGE_OPTIMIZER_GIFSICLE && ! $skip['gifsicle'] ) {
					$valid = false;
					$msg = '<br>' . wp_kses( sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<em>gifsicle</em>' ), array( 'em' => array() ) );
				} else {
					$convert_link = esc_html__('GIF to PNG', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$convert_desc = esc_attr__('PNG is generally better than GIF, but does not support animation. Animated images will not be converted.', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				break;
			case 'application/pdf':
				$convert_desc = '';
				break;
			default:
				// not a supported mimetype
				esc_html_e( 'Unsupported file type', EWWW_IMAGE_OPTIMIZER_DOMAIN );
				ewww_image_optimizer_debug_log();
				return;
		}
		$ewww_manual_nonce = wp_create_nonce( "ewww-manual-$id" );
		if ( $ewww_cdn ) {
			// if the optimizer metadata exists
			if ( ! empty( $meta['ewww_image_optimizer'] ) ) {
				// output the optimizer results
				echo "<br>" . esc_html( $meta['ewww_image_optimizer'] );
				if ( current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
					// output a link to re-optimize manually
					printf("<br><a href=\"admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_force=1&amp;ewww_attachment_ID=%d\">%s</a>",
						$id,
						esc_html__( 'Re-optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				}
			} elseif ( get_transient( 'ewwwio-background-in-progress-' . $id ) ) {
				esc_html_e( 'In Progress', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			} elseif ( current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
				// and give the user the option to optimize the image right now
				printf( "<br><a href=\"admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_attachment_ID=%d\">%s</a>", $id, esc_html__( 'Optimize now!', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
			}
			return;
		}
		// if the optimizer metadata exists
		if ( ! empty( $meta['ewww_image_optimizer'] ) ) {
			// output the optimizer results
			echo esc_html( $meta['ewww_image_optimizer'] );
			// output the filesize
			echo "<br>" . sprintf( esc_html__( 'Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $file_size );
			if ( empty( $msg ) && current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
				// output a link to re-optimize manually
				printf("<br><a href=\"admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_force=1&amp;ewww_attachment_ID=%d\">%s</a>",
					$id,
					esc_html__( 'Re-optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_convert_links' ) && 'ims_image' != get_post_type( $id ) && ! empty( $convert_desc ) ) {
					echo " | <a class='ewww-convert' title='$convert_desc' href='admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_attachment_ID=$id&amp;ewww_convert=1&amp;ewww_force=1'>$convert_link</a>";
				}
			} else {
				echo $msg;
			}
			$restorable = false;
			if ( ! empty( $meta['converted'] ) ) {
				if ( ! empty( $meta['orig_file'] ) && file_exists( $meta['orig_file'] ) ) {
					$restorable = true;
				}
			}
			if ( isset( $meta['sizes'] ) ) {
				// meta sizes don't contain a path, so we calculate one
				$base_dir = trailingslashit( dirname( $file_path ) );
				foreach( $meta['sizes'] as $size => $data ) {
					if ( ! empty( $data['converted'] ) ) {
						if ( ! empty( $data['orig_file'] ) && file_exists( $base_dir . $data['orig_file'] ) ) {
							$restorable = true;
						}
					}		
				}
			}
			if ( $restorable && current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
				printf( "<br><a href=\"admin.php?action=ewww_image_optimizer_manual_restore&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_attachment_ID=%d\">%s</a>",
					$id,
					esc_html__( 'Restore original', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
			}

			// link to webp upgrade script
			$oldwebpfile = preg_replace('/\.\w+$/', '.webp', $file_path);
			if ( file_exists( $oldwebpfile ) && current_user_can( apply_filters( 'ewww_image_optimizer_admin_permissions', '' ) ) ) {
				echo "<br><a href='options.php?page=ewww-image-optimizer-webp-migrate'>" . esc_html__( 'Run WebP upgrade', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>";
			}

			// determine filepath for webp
			$webpfile = $file_path . '.webp';
			$webp_size = ewww_image_optimizer_filesize( $webpfile );
			if ( $webp_size ) {
				$webp_size = size_format( $webp_size, 2 );
				$webpurl = esc_url( wp_get_attachment_url( $id ) . '.webp' );
				// get a human readable filesize
				$webp_size = preg_replace( '/\.00 B /', ' B', $webp_size );
				echo "<br>WebP: <a href='$webpurl'>$webp_size</a>";
			}
		} elseif ( get_transient( 'ewwwio-background-in-progress-' . $id ) ) {
			esc_html_e( 'In Progress', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		} else {
			// otherwise, this must be an image we haven't processed
			esc_html_e( 'Not processed', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			// tell them the filesize
			echo "<br>" . sprintf( esc_html__( 'Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $file_size );
			if ( empty( $msg ) && current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
				// and give the user the option to optimize the image right now
				printf( "<br><a href=\"admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_attachment_ID=%d\">%s</a>", $id, esc_html__( 'Optimize now!', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_convert_links' ) && 'ims_image' != get_post_type( $id ) && ! empty( $convert_desc ) ) {
					echo " | <a class='ewww-convert' title='$convert_desc' href='admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_attachment_ID=$id&amp;ewww_convert=1&amp;ewww_force=1'>$convert_link</a>";
				}
			} else {
				echo $msg;
			}
		}
	}
	ewwwio_memory( __FUNCTION__ );
}

// display a page of unprocessed images from Media library
function ewww_image_optimizer_display_unoptimized_media() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$bulk_resume = get_option( 'ewww_image_optimizer_bulk_resume' );
	update_option( 'ewww_image_optimizer_bulk_resume', '' );
	$attachments = ewww_image_optimizer_count_optimized( 'media', true );
	update_option( 'ewww_image_optimizer_bulk_resume', $bulk_resume );
	echo "<div class='wrap'><h1>" . esc_html__( 'Unoptimized Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</h1>";
	printf( '<p>' . esc_html__( 'We have %d images to optimize.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</p>', count( $attachments ) );
	if ( count( $attachments ) != 0 ) {
		sort( $attachments, SORT_NUMERIC );
		$image_string = implode( ',', $attachments );
		echo '<form method="post" action="upload.php?page=ewww-image-optimizer-bulk">'
			. "<input type='hidden' name='ids' value='$image_string' />"
			. '<input type="submit" class="button-secondary action" value="' . esc_html__( 'Optimize All Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '" />'
			. '</form>';
		if ( count( $attachments ) < 500 ) {
			sort( $attachments, SORT_NUMERIC );
			$image_string = implode( ',', $attachments );
			echo '<table class="wp-list-table widefat media" cellspacing="0"><thead><tr><th>ID</th><th>&nbsp;</th><th>' . esc_html__('Title', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . esc_html__('Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th></tr></thead>';
			$alternate = true;
			foreach ( $attachments as $ID ) {
				$image_name = get_the_title( $ID );
?>				<tr<?php if( $alternate ) echo " class='alternate'"; ?>><td><?php echo $ID; ?></td>
<?php				echo "<td style='width:80px' class='column-icon'>" . wp_get_attachment_image( $ID, 'thumbnail' ) . "</td>";
				echo "<td class='title'>$image_name</td>";
				echo "<td>";
				ewww_image_optimizer_custom_column( 'ewww-image-optimizer', $ID );
				echo "</td></tr>";
				$alternate = ! $alternate;
			}
			echo '</table>';
		} else {
			echo '<p>' . esc_html__( 'There are too many images to display.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</p>'; 
		}
	}
	echo '</div>';
	return;	
}

// retrieve an option: use 'site' setting if plugin is network activated, otherwise use 'blog' setting
function ewww_image_optimizer_get_option( $option_name ) {
	global $ewwwio_settings;
/*	if ( isset( $ewwwio_settings[ $option_name ] ) ) {
		return $ewwwio_settings[ $option_name ];
	}*/
	$option_value = get_option( $option_name );
//	$ewwwio_settings[ $option_name ] = $option_value;
	return $option_value;
}

// set an option: use 'site' setting if plugin is network activated, otherwise use 'blog' setting
function ewww_image_optimizer_set_option( $option_name, $option_value ) {
	$success = update_option( $option_name, $option_value );
	return $success;
}

function ewww_image_optimizer_settings_script( $hook ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	// make sure we are being called from the bulk optimization page
	if ( strpos( $hook,'settings_page_ewww-image-optimizer' ) !== 0 ) {
		return;
	}
	wp_enqueue_script( 'ewwwbulkscript', plugins_url( '/includes/eio.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'postbox' );
	wp_enqueue_script( 'dashboard' );
	wp_localize_script( 'ewwwbulkscript', 'ewww_vars', array(
			'_wpnonce' => wp_create_nonce( 'ewww-image-optimizer-settings' ),
		)
	);
	ewwwio_memory( __FUNCTION__ );
	return;
}

function ewww_image_optimizer_savings() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		ewwwio_debug_message( 'querying savings for multi-site' );
		if ( function_exists( 'wp_get_sites' ) ) {
			ewwwio_debug_message( 'retrieving list of sites the easy way' );
			add_filter( 'wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0 );
			$blogs = wp_get_sites( array(
				'network_id' => $wpdb->siteid,
				'limit' => 10000
			) );
			remove_filter( 'wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0 );
		} else {
			ewwwio_debug_message( 'retrieving list of sites the hard way' );
			$query = "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";
			$blogs = $wpdb->get_results( $query, ARRAY_A );
		}
		$total_savings = 0;
		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			ewwwio_debug_message( "getting savings for site: {$blog['blog_id']}" );
			$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'ewwwio_images WHERE image_size > orig_size' );
			$total_query = "SELECT SUM(orig_size-image_size) FROM $wpdb->ewwwio_images";
			ewwwio_debug_message( "query to be performed: $total_query" );
			$savings = $wpdb->get_var($total_query);
			ewwwio_debug_message( "savings found: $savings" );
			$total_savings += $savings;
		}
		restore_current_blog();
	} else {
		ewwwio_debug_message( 'querying savings for single site' );
		$total_savings = 0;
		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'ewwwio_images WHERE image_size > orig_size' );
		$total_query = "SELECT SUM(orig_size-image_size) FROM $wpdb->ewwwio_images";
		ewwwio_debug_message( "query to be performed: $total_query" );
		$total_savings = $wpdb->get_var($total_query);
		ewwwio_debug_message( "savings found: $total_savings" );
	}
	return $total_savings;
}

function ewww_image_optimizer_htaccess_path() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$htpath = get_home_path();
	if ( get_option( 'siteurl' ) !== get_option( 'home' ) ) {
		ewwwio_debug_message( 'WordPress Address and Site Address are different, possible subdir install' );
		$path_diff = str_replace(  get_option( 'home' ), '', get_option( 'siteurl' ) );
		$newhtpath = trailingslashit( $htpath . $path_diff ) . '.htaccess';
		if ( is_file( $newhtpath ) ) {
			ewwwio_debug_message( 'subdir install confirmed' );
			return $newhtpath;
		}
	}
	return $htpath . '.htaccess';
}

function ewww_image_optimizer_webp_rewrite() {
	// verify that the user is properly authorized
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-settings' ) ) {
		wp_die( esc_html__( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	if ( $ewww_rules = ewww_image_optimizer_webp_rewrite_verify() ) {
		if ( insert_with_markers( ewww_image_optimizer_htaccess_path(), 'EWWWIO', $ewww_rules ) && ! ewww_image_optimizer_webp_rewrite_verify() ) {
			esc_html_e( 'Insertion successful', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		} else {
			esc_html_e( 'Insertion failed', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		}
	}
	die();
}

// if rules are present, stay silent, otherwise, give us some rules to insert!
function ewww_image_optimizer_webp_rewrite_verify() {
	$current_rules = extract_from_markers( ewww_image_optimizer_htaccess_path(), 'EWWWIO' ) ;
	$ewww_rules = array(
		"<IfModule mod_rewrite.c>",
		"RewriteEngine On",
		"RewriteCond %{HTTP_ACCEPT} image/webp",
		"RewriteCond %{REQUEST_FILENAME} (.*)\.(jpe?g|png)$",
		"RewriteCond %{REQUEST_FILENAME}.webp -f",
		"RewriteRule (.+)\.(jpe?g|png)$ %{REQUEST_FILENAME}.webp [T=image/webp,E=accept:1]",
		"</IfModule>",
		"<IfModule mod_headers.c>",
		"Header append Vary Accept env=REDIRECT_accept",
		"</IfModule>",
		"AddType image/webp .webp",
	);
	if ( array_diff( $ewww_rules, $current_rules ) ) {
		ewwwio_memory( __FUNCTION__ );
		return $ewww_rules;
	} else {
		ewwwio_memory( __FUNCTION__ );
		return;
	}
}

function ewww_image_optimizer_get_image_sizes() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $_wp_additional_image_sizes;
	$sizes = array();
	$image_sizes = get_intermediate_image_sizes();
	ewwwio_debug_message( print_r( $image_sizes, true ) );
//	ewwwio_debug_message( print_r( $_wp_additional_image_sizes, true ) );
	foreach( $image_sizes as $_size ) {
		if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
			$sizes[ $_size ]['width'] = get_option( $_size . '_size_w' );
			$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
			if ( $_size === 'medium_large' && $sizes[ $_size ]['width'] == 0 ) {
				$sizes[ $_size ]['width'] = '768';
			}
			if ( $_size === 'medium_large' && $sizes[ $_size ]['height'] == 0 ) {
				$sizes[ $_size ]['height'] = '9999';
			}
		} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			$sizes[ $_size ] = array( 
				'width' => $_wp_additional_image_sizes[ $_size ]['width'],
				'height' => $_wp_additional_image_sizes[ $_size ]['height'],
			);
		}
	}
	ewwwio_debug_message( print_r( $sizes, true ) );
	return $sizes;
}

// displays the EWWW IO options and provides one-click install for the optimizer utilities
function ewww_image_optimizer_options () {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( 'ABSPATH: ' . ABSPATH );
	ewwwio_debug_message( 'home url: ' . get_home_url() );
	ewwwio_debug_message( 'site url: ' . get_site_url() );

	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$output = array();
	if (isset($_REQUEST['ewww_pngout'])) {
		if ($_REQUEST['ewww_pngout'] == 'success') {
			$output[] = "<div id='ewww-image-optimizer-pngout-success' class='updated fade'>\n";
			$output[] = '<p>' . esc_html__('Pngout was successfully installed, check the Plugin Status area for version information.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n";
			$output[] = "</div>\n";
		}
		if ($_REQUEST['ewww_pngout'] == 'failed') {
			$output[] = "<div id='ewww-image-optimizer-pngout-failure' class='error'>\n";
			$output[] = '<p>' . sprintf( esc_html__('Pngout was not installed: %1$s. Make sure this folder is writable: %2$s', EWWW_IMAGE_OPTIMIZER_DOMAIN), sanitize_text_field( $_REQUEST['ewww_error'] ), EWWW_IMAGE_OPTIMIZER_TOOL_PATH) . "</p>\n";
			$output[] = "</div>\n";
		}
	}
	$output[] = "<script type='text/javascript'>\n" .
		'jQuery(document).ready(function($) {$(".fade").fadeTo(5000,1).fadeOut(3000);$(".updated").fadeTo(5000,1).fadeOut(3000);});' . "\n" .
		"</script>\n";
	$output[] = "<style>\n" .
		".ewww-tab a { font-size: 15px; font-weight: 700; color: #555; text-decoration: none; line-height: 36px; padding: 0 10px; }\n" .
		".ewww-tab a:hover { color: #464646; }\n" .
		".ewww-tab { margin: 0 0 0 5px; padding: 0px; border-width: 1px 1px 1px; border-style: solid solid none; border-image: none; border-color: #ccc; display: inline-block; background-color: #e4e4e4 }\n" .
		".ewww-tab:hover { background-color: #fff }\n" .
		".ewww-selected { background-color: #f1f1f1; margin-bottom: -1px; border-bottom: 1px solid #f1f1f1 }\n" .
		".ewww-selected a { color: #000; }\n" .
		".ewww-selected:hover { background-color: #f1f1f1; }\n" .
		".ewww-tab-nav { list-style: none; margin: 10px 0 0; padding-left: 5px; border-bottom: 1px solid #ccc; }\n" .
	"</style>\n";
	$output[] = "<div class='wrap'>\n";
	$output[] = "<h1>EWWW Image Optimizer</h1>\n";
	$output[] = "<div id='ewww-container-left' style='float: left; margin-right: 225px;'>\n";
	$output[] = "<p><a href='https://wordpress.org/extend/plugins/ewww-image-optimizer/'>" . esc_html__( 'Plugin Home Page', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a> | " .
		"<a href='https://wordpress.org/extend/plugins/ewww-image-optimizer/installation/'>" .  esc_html__( 'Installation Instructions', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a> | " .
		"<a href='https://wordpress.org/support/plugin/ewww-image-optimizer'>" . esc_html__( 'Plugin Support', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a> | " .
		"<a href='https://ewww.io/status/'>" . esc_html__( 'Cloud Status', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a> | " .
		"<a href='https://ewww.io/downloads/s3-image-optimizer/'>" . esc_html__( 'S3 Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a></p>\n";
		if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
			$bulk_link = esc_html__( 'Media Library', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ' -> ' . esc_html__( 'Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		} else {
			$bulk_link = '<a href="upload.php?page=ewww-image-optimizer-bulk">' . esc_html__( 'Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</a>';
		}
	$output[] = "<p>" . wp_kses( sprintf( __( 'New images uploaded to the Media Library will be optimized automatically. If you have existing images you would like to optimize, you can use the %s tool.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $bulk_link ), array( 'a' => array( 'href' => array() ) ) ) . " " . wp_kses( __( 'Images stored in an Amazon S3 bucket can be optimized using our <a href="https://ewww.io/downloads/s3-image-optimizer/">S3 Image Optimizer</a>.' ), array( 'a' => array( 'href' => array() ) ) ) . "</p>\n";
	//if ( EWWW_IMAGE_OPTIMIZER_CLOUD ) {
	if ( ewww_image_optimizer_full_cloud() ) {
		$collapsed = '';
	} else {
		$collapsed = "$('#ewww-status').toggleClass('closed');\n";
	}
	$output[] = "<div id='ewww-widgets' class='metabox-holder'><div class='meta-box-sortables'><div id='ewww-status' class='postbox'>\n" .
		"<button type='button' class='handlediv button-link' aria-expanded='true'>" .
			"<span class='screen-reader-text'>" . esc_html__( 'Click to toggle', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</span>" .
			"<span class='toggle-indicator' aria-hidden='true'></span>" .
		"</button>" .
		"<h2 class='hndle'>" . esc_html__('Plugin Status', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "&emsp;" .
			"<span id='ewww-status-ok' style='display: none; color: green;'>" . esc_html__('All Clear', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>" . 
			"<span id='ewww-status-attention' style='color: red;'>" . esc_html__('Requires Attention', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>"  .
		"</h2>\n" .
			"<div class='inside'>" .
			"<b>" . esc_html__('Total Savings:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> <span id='ewww-total-savings'>" . size_format( ewww_image_optimizer_savings(), 2 ) . "</span><br>";
			$collapsible = true;
			if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) ) {
				$output[] = '<p><b>' . esc_html__( 'Cloud optimization API Key', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ":</b> ";
				$verify_cloud = ewww_image_optimizer_cloud_verify(); 
				if ( preg_match( '/great/', $verify_cloud ) ) {
					$output[] = '<span style="color: green">' . esc_html__( 'Verified,', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ' </span>' . ewww_image_optimizer_cloud_quota();
				} elseif ( preg_match( '/exceeded/', $verify_cloud ) ) { 
					$output[] = '<span style="color: orange">' . esc_html__( 'Verified,', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ' </span>' . ewww_image_optimizer_cloud_quota();
					$collapsible = false;
				} else { 
					$output[] = '<span style="color: red">' . esc_html__( 'Not Verified', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</span>';
					$collapsible = false;
				}
				$output[] = "</p>\n";
				$disable_level = '';
			} else {
				if ( EWWW_IMAGE_OPTIMIZER_DOMAIN == 'ewww-image-optimizer-cloud' ) {
					$collapsible = false;
				}
				$disable_level = "disabled='disabled'";
			}
			if ( ewww_image_optimizer_detect_wpsf_location_lock() ) {
				$output[] = '<p><span style="color: orange">' . esc_html__('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span> ' . esc_html__( "You are using Shield's Lock to Location feature, which prevents the use of Background & Parallel Optimization for faster processing time.", EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</p>';
				$collapsible = false;
			}
			if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_bundle' ) && ! ewww_image_optimizer_full_cloud() && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<p>" . esc_html__( 'If updated versions are available below you may either download the newer versions and install them yourself, or uncheck "Use System Paths" and use the bundled tools. ', EWWW_IMAGE_OPTIMIZER_DOMAIN)  . "<br />\n" .
					"<i>*" . esc_html__('Updates are optional, but may contain increased optimization or security patches', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</i></p>\n";
			} elseif ( ! ewww_image_optimizer_full_cloud() && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<p>" . sprintf( esc_html__( 'If updated versions are available below, you may need to enable write permission on the %s folder to use the automatic installs.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<i>' . EWWW_IMAGE_OPTIMIZER_TOOL_PATH . '</i>' ) . "<br />\n" .
					"<i>*" . esc_html__( 'Updates are optional, but may contain increased optimization or security patches', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</i></p>\n";
			}
			if ( ! ewww_image_optimizer_full_cloud() && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				list ( $jpegtran_src, $optipng_src, $gifsicle_src, $jpegtran_dst, $optipng_dst, $gifsicle_dst ) = ewww_image_optimizer_install_paths();
			}
			$skip = ewww_image_optimizer_skip_tools();
			$output[] = "<p>\n";
			if ( ! $skip['jpegtran']  && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<b>jpegtran:</b> ";
				if ( EWWW_IMAGE_OPTIMIZER_JPEGTRAN ) {
					$jpegtran_installed = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_JPEGTRAN, 'j' );
					if ( ! $jpegtran_installed ) {
						$jpegtran_installed = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_JPEGTRAN, 'jb' );
					}
				}
				if ( ! empty( $jpegtran_installed ) ) {
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . esc_html__('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $jpegtran_installed . "<br />\n"; 
				} else { 
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if ( ! $skip['optipng'] && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<b>optipng:</b> ";
				if ( EWWW_IMAGE_OPTIMIZER_OPTIPNG ) {
					$optipng_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_OPTIPNG, 'o' );
					if ( ! $optipng_version ) {
						$optipng_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_OPTIPNG, 'ob' );
					}
				}
				if ( ! empty( $optipng_version ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . esc_html__('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $optipng_version . "<br />\n"; 
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if ( ! $skip['pngout'] && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<b>pngout:</b> ";
				if ( EWWW_IMAGE_OPTIMIZER_PNGOUT ) {
					$pngout_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_PNGOUT, 'p' );
					if ( ! $pngout_version ) {
						$pngout_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_PNGOUT, 'pb' );
					}
				}
				if ( ! empty( $pngout_version ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__( 'Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</span>&emsp;' . esc_html__( 'version', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ': ' . preg_replace( '/PNGOUT \[.*\)\s*?/', '', $pngout_version ) . "<br />\n";
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;<b>' . esc_html__('Install', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' <a href="admin.php?action=ewww_image_optimizer_install_pngout">' . esc_html__('automatically', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</a> | <a href="http://advsys.net/ken/utils.htm">' . esc_html__('manually', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</a></b> - ' . esc_html__('Pngout is free closed-source software that can produce drastically reduced filesizes for PNGs, but can be very time consuming to process images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br />\n"; 
					$collapsible = false;
				}
			}
			if ( $skip['pngout'] && ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_level' ) == 10 ) {
					$output[] = '<b>pngout:</b> ' . esc_html__( 'Not installed, enable in Advanced Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "<br />\n";
			}
			if ( ! $skip['gifsicle'] && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<b>gifsicle:</b> ";
				if ( EWWW_IMAGE_OPTIMIZER_GIFSICLE ) {
					$gifsicle_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_GIFSICLE, 'g' );
					if ( ! $gifsicle_version ) {
						$gifsicle_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_GIFSICLE, 'gb' );
					}
				}
				if ( ! empty( $gifsicle_version ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . esc_html__('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $gifsicle_version . "<br />\n";
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if ( ! $skip['pngquant'] && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<b>pngquant:</b> ";
				if ( EWWW_IMAGE_OPTIMIZER_PNGQUANT ) {
					$pngquant_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_PNGQUANT, 'q' );
					if ( ! $pngquant_version ) {
						$pngquant_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_PNGQUANT, 'qb' );
					}
				}
				if ( ! empty( $pngquant_version ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . esc_html__('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $pngquant_version . "<br />\n"; 
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if ( EWWW_IMAGE_OPTIMIZER_WEBP && ! $skip['webp'] && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<b>webp:</b> ";
				if ( EWWW_IMAGE_OPTIMIZER_WEBP ) {
					$webp_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_WEBP, 'w' );
					if ( ! $webp_version ) {
						$webp_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_WEBP, 'wb' );
					}
				}
				if ( ! empty( $webp_version ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__( 'Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</span>&emsp;' . esc_html__( 'version', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ': ' . $webp_version . "<br />\n"; 
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if ( ! ewww_image_optimizer_full_cloud() && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				if ( ewww_image_optimizer_safemode_check() ) {
					$output[] = 'safe mode: <span style="color: red; font-weight: bolder">' . esc_html__('On', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
					$collapsible = false;
				} else {
					$output[] = 'safe mode: <span style="color: green; font-weight: bolder">' . esc_html__('Off', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				}
				if ( ewww_image_optimizer_exec_check() ) {
					$output[] = 'exec(): <span style="color: red; font-weight: bolder">' . esc_html__('Disabled', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
					$collapsible = false;
				} else {
					$output[] = 'exec(): <span style="color: green; font-weight: bolder">' . esc_html__('Enabled', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				}
				$output[] = "<br />\n";
				$output[] = wp_kses( sprintf( __( "%s only need one, used for conversion, not optimization", EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<b>' . __( 'Graphics libraries', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</b> - ' ), array( 'b' => array() ) );
				$output[] = '<br>';
				$toolkit_found = false;
				if ( ewww_image_optimizer_gd_support() ) {
					$output[] = 'GD: <span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$toolkit_found = true;
				} else {
					$output[] = 'GD: <span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				$output[] = '</span>&emsp;&emsp;' .
					"Gmagick: ";
				if ( ewww_image_optimizer_gmagick_support() ) {
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$toolkit_found = true;
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				$output[] = '</span>&emsp;&emsp;' .
					"Imagick: ";
				if ( ewww_image_optimizer_imagick_support() ) {
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$toolkit_found = true;
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				$output[] = "</span>&emsp;&emsp;Imagemagick 'convert': ";
				if ( 'WINNT' == PHP_OS && ewww_image_optimizer_find_win_binary( 'convert', 'i' ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>';
					$toolkit_found = true;
				} elseif ( 'WINNT' != PHP_OS && ewww_image_optimizer_find_nix_binary( 'convert', 'i' ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>';
					$toolkit_found = true;
				} else { 
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>';
				}
				if ( ! $toolkit_found && ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_to_jpg' ) || ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_to_png' ) ) ) {
					$collapsible = false;
				}
				$output[] = "<br />\n";
			}
			$output[] = '<b>' . esc_html__('Only need one of these:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' </b><br>';
			// initialize this variable to check for the 'file' command if we don't have any php libraries we can use
			$file_command_check = true;
			if ( function_exists( 'finfo_file' ) ) {
				$output[] = 'finfo: <span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				$file_command_check = false;
			} else {
				$output[] = 'finfo: <span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
			}
			if ( function_exists( 'getimagesize' ) ) {
				$output[] = 'getimagesize(): <span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				if ( ewww_image_optimizer_full_cloud() || EWWW_IMAGE_OPTIMIZER_NOEXEC || PHP_OS == 'WINNT' ) {
					$file_command_check = false;
				}
			} else {
				$output[] = 'getimagesize(): <span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
			}
			if (function_exists('mime_content_type')) {
				$output[] = 'mime_content_type(): <span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
				$file_command_check = false;
			} else {
				$output[] = 'mime_content_type(): <span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
			}
			if ( PHP_OS != 'WINNT' && ! ewww_image_optimizer_full_cloud() && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				if ($file_command_check && !ewww_image_optimizer_find_nix_binary('file', 'f')) {
					$output[] = '<span style="color: red; font-weight: bolder">file: ' . esc_html__('command not found on your system', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />';
					$collapsible = false;
				}
				if (!ewww_image_optimizer_find_nix_binary('nice', 'n')) {
					$output[] = '<span style="color: orange; font-weight: bolder">nice: ' . esc_html__('command not found on your system', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' (' . esc_html__('not required', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ')</span><br />';
				}
				if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_pngout' ) && ! $skip['pngout'] && PHP_OS != 'SunOS' && ! ewww_image_optimizer_find_nix_binary( 'tar', 't' ) ) {
					$output[] = '<span style="color: red; font-weight: bolder">tar: ' . esc_html__('command not found on your system', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' (' . esc_html__('required for automatic pngout installer', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ')</span><br />';
					$collapsible = false;
				}
			} elseif ( $file_command_check ) {
				$collapsible = false;
			}
			$output[] = '</div><!-- end .inside -->';
			if ( $collapsible ) {
				$output[] = "<script type='text/javascript'>\n" .
					"jQuery(document).ready(function($) {\n" .
					$collapsed .
					"$('#ewww-status-attention').hide();\n" .
					"$('#ewww-status-ok').show();\n" .
					"});\n" .
					"</script>\n";
			}
			$output[] = "</div></div></div>\n";
	$output[] = "<ul class='ewww-tab-nav'>\n" .
//		"<li class='ewww-tab ewww-cloud-nav'><span class='ewww-tab-hidden'><a class='ewww-cloud-nav' href='#'>" . esc_html__('Cloud Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
		"<li class='ewww-tab ewww-general-nav'><span class='ewww-tab-hidden'><a class='ewww-general-nav' href='#'>" . esc_html__('Basic Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
		"<li class='ewww-tab ewww-optimization-nav'><span class='ewww-tab-hidden'><a class='ewww-optimization-nav' href='#'>" .  esc_html__('Advanced Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
		"<li class='ewww-tab ewww-conversion-nav'><span class='ewww-tab-hidden'><a class='ewww-conversion-nav' href='#'>" . esc_html__('Conversion Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
	"</ul>\n";
			if ( is_multisite() && is_plugin_active_for_network(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
				$output[] = "<form method='post' action=''>\n";
			} else {
				$output[] = "<form method='post' action='options.php'>\n";
			}
			$output[] = "<input type='hidden' name='option_page' value='ewww_image_optimizer_options' />\n";
		        $output[] = "<input type='hidden' name='action' value='update' />\n";
		        $output[] = wp_nonce_field( "ewww_image_optimizer_options-options", '_wpnonce', true, false ) . "\n";
			$output[] = "<div id='ewww-general-settings'>\n";
			$output[] = "<p class='nocloud'>" . esc_html__( 'To unlock additional compression levels below, you may purchase an API key for our cloud optimization service.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='https://ewww.io/plans/'>" . esc_html__('Purchase an API key.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p>\n";
			$output[] = "<table class='form-table'>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_cloud_key'>" . esc_html__( 'Cloud optimization API Key', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</label></th><td><input type='password' id='ewww_image_optimizer_cloud_key' name='ewww_image_optimizer_cloud_key' value='" . ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) . "' size='32' /> " . esc_html__('API Key will be validated when you save your settings.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='https://ewww.io/plans/'>" . esc_html__('Purchase an API key.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_debug'>" . esc_html__('Debugging', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_debug' name='ewww_image_optimizer_debug' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_debug') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('Use this to provide information for support purposes, or if you feel comfortable digging around in the code to fix a problem you are experiencing.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_jpegtran_copy'>" . esc_html__('Remove metadata', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><input type='checkbox' id='ewww_image_optimizer_jpegtran_copy' name='ewww_image_optimizer_jpegtran_copy' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpegtran_copy') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('This will remove ALL metadata: EXIF and comments.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "remove metadata: " . ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th>&nbsp;</th><td><p class='description'>" . esc_html__( 'Lossless compression keeps the original quality of the image.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' ' . esc_html__('While most users will not notice a difference in image quality, lossy means there IS a loss in image quality.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_jpg_level'>" . esc_html__('JPG Optimization Level', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><span><select id='ewww_image_optimizer_jpg_level' name='ewww_image_optimizer_jpg_level'>\n" .
				"<option value='0'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_level' ) == 0 ? " selected='selected'" : "" ) . '>' . esc_html__( 'No Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n";
				if ( EWWW_IMAGE_OPTIMIZER_DOMAIN !== 'ewww-image-optimizer-cloud' ) {
					$output[] = "<option value='10'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_level' ) == 10 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Lossless Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n";
				}
				$output[] = "<option $disable_level value='20'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_level' ) == 20 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Maximum Lossless Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n" .
				"<option $disable_level value='30'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_level' ) == 30 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Lossy Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n" .
				"<option $disable_level value='40'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_level' ) == 40 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Maximum Lossy Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n" .
				"</select></td></tr>\n";
				ewwwio_debug_message( "jpg level: " . ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_level') );
				$output[] = "<tr><th><label for='ewww_image_optimizer_png_level'>" . esc_html__('PNG Optimization Level', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><span><select id='ewww_image_optimizer_png_level' name='ewww_image_optimizer_png_level'>\n" .
				"<option value='0'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_level' ) == 0 ? " selected='selected'" : "" ) . '>' . esc_html__( 'No Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n";
				if ( EWWW_IMAGE_OPTIMIZER_DOMAIN !== 'ewww-image-optimizer-cloud' ) {
					$output[] = "<option value='10'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_level' ) == 10 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Lossless Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n";
				}
				$output[] = "<option $disable_level value='20'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_level' ) == 20 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Better Lossless Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n" .
				"<option $disable_level value='30'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_level' ) == 30 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Maximum Lossless Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n" .
				"<option value='40'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_level' ) == 40 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Lossy Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n" .
				"<option $disable_level value='50'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_level' ) == 50 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Maximum Lossy Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n" .
				"</select></td></tr>\n";
				ewwwio_debug_message( "png level: " . ewww_image_optimizer_get_option('ewww_image_optimizer_png_level') );
				$output[] = "<tr><th><label for='ewww_image_optimizer_gif_level'>" . esc_html__('GIF Optimization Level', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><span><select id='ewww_image_optimizer_gif_level' name='ewww_image_optimizer_gif_level'>\n" .
				"<option value='0'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_gif_level' ) == 0 ? " selected='selected'" : "" ) . '>' . esc_html__( 'No Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"<option value='10'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_gif_level' ) == 10 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Lossless Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n" .
				"</select></td></tr>\n";
				ewwwio_debug_message( "gif level: " . ewww_image_optimizer_get_option('ewww_image_optimizer_gif_level') );
				$output[] = "<tr><th><label for='ewww_image_optimizer_pdf_level'>" . esc_html__('PDF Optimization Level', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><span><select id='ewww_image_optimizer_pdf_level' name='ewww_image_optimizer_pdf_level'>\n" .
				"<option value='0'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pdf_level' ) == 0 ? " selected='selected'" : "" ) . '>' . esc_html__( 'No Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"<option $disable_level value='10'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pdf_level' ) == 10 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Lossless Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n" .
				"<option $disable_level value='20'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pdf_level' ) == 20 ? " selected='selected'" : "" ) . '>' . esc_html__( 'Lossy Compression', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</option>\n" .
				"</select></td></tr>\n";
				ewwwio_debug_message( "pdf level: " . ewww_image_optimizer_get_option('ewww_image_optimizer_pdf_level') );
				$output[] = "<tr><th><label for='ewww_image_optimizer_delay'>" . esc_html__('Bulk Delay', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='text' id='ewww_image_optimizer_delay' name='ewww_image_optimizer_delay' size='5' value='" . ewww_image_optimizer_get_option('ewww_image_optimizer_delay') . "'> " . esc_html__('Choose how long to pause between images (in seconds, 0 = disabled)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "bulk delay: " . ewww_image_optimizer_get_option('ewww_image_optimizer_delay') );
	if ( class_exists( 'Cloudinary' ) && Cloudinary::config_get( 'api_secret' ) ) {
				$output[] = "<tr><th><label for='ewww_image_optimizer_enable_cloudinary'>" . esc_html__('Automatic Cloudinary upload', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_enable_cloudinary' name='ewww_image_optimizer_enable_cloudinary' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_enable_cloudinary') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('When enabled, uploads to the Media Library will be transferred to Cloudinary after optimization. Cloudinary generates resizes, so only the full-size image is uploaded.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "cloudinary upload: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_enable_cloudinary') == TRUE ? "on" : "off" ) );
	}
			$output[] = "</table>\n</div>\n";
			$output[] = "<div id='ewww-optimization-settings'>\n";
			$output[] = "<table class='form-table'>\n";
			if ( ewww_image_optimizer_full_cloud() ) {
				$output[] = "<input id='ewww_image_optimizer_optipng_level' name='ewww_image_optimizer_optipng_level' type='hidden' value='2'>\n" .
					"<input id='ewww_image_optimizer_pngout_level' name='ewww_image_optimizer_pngout_level' type='hidden' value='2'>\n";
			} else {
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_optipng_level'>" . esc_html__('optipng optimization level', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><span><select id='ewww_image_optimizer_optipng_level' name='ewww_image_optimizer_optipng_level'>\n" .
				"<option value='1'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 1 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 1) . ': ' . sprintf(esc_html__('%d trial', EWWW_IMAGE_OPTIMIZER_DOMAIN), 1) . "</option>\n" .
				"<option value='2'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 2 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 2) . ': ' . sprintf(esc_html__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 8) . "</option>\n" .
				"<option value='3'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 3 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 3) . ': ' . sprintf(esc_html__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 16) . "</option>\n" .
				"<option value='4'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 4 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 4) . ': ' . sprintf(esc_html__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 24) . "</option>\n" .
				"<option value='5'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 5 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 5) . ': ' . sprintf(esc_html__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 48) . "</option>\n" .
				"</select> (" . esc_html__('default', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "=2)</span>\n" .
				"<p class='description'>" . esc_html__( 'Levels 4 and above are unlikely to yield any additional savings.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				ewwwio_debug_message( "optipng level: " . ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') );
//				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_disable_optipng'>" . esc_html__('disable', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " optipng</label></th><td><input type='checkbox' id='ewww_image_optimizer_disable_optipng' name='ewww_image_optimizer_disable_optipng' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
//				ewwwio_debug_message( "optipng disabled: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng') == TRUE ? "yes" : "no" ) );
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_disable_pngout'>" . esc_html__('disable', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " pngout</label></th><td><input type='checkbox' id='ewww_image_optimizer_disable_pngout' name='ewww_image_optimizer_disable_pngout' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout') == TRUE  ? "checked='true'" : "" ) . " /></td><tr>\n";
				ewwwio_debug_message( "pngout disabled: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout') == TRUE ? "yes" : "no" ) );
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_pngout_level'>" . esc_html__('pngout optimization level', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><span><select id='ewww_image_optimizer_pngout_level' name='ewww_image_optimizer_pngout_level'>\n" .
				"<option value='0'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 0 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 0) . ': ' . esc_html__('Xtreme! (Slowest)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"<option value='1'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 1 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 1) . ': ' . esc_html__('Intense (Slow)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"<option value='2'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 2 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 2) . ': ' . esc_html__('Longest Match (Fast)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"<option value='3'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 3 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 3) . ': ' . esc_html__('Huffman Only (Faster)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"</select> (" . esc_html__('default', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "=2)</span>\n" .
				"<p class='description'>" . sprintf(esc_html__('If you have CPU cycles to spare, go with level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 0) . "</p></td></tr>\n";
				ewwwio_debug_message( "pngout level: " . ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') );
			}
			//	$output[] = "<tr><th><label for='ewww_image_optimizer_defer'>" . esc_html__( 'Deferred Optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_defer' name='ewww_image_optimizer_defer' value='true' " . ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__( 'Optimize images later via wp_cron, after image upload or generation is complete.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</td></tr>\n";
			//	ewwwio_debug_message( "deferred optimization: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_defer') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><span><label for='ewww_image_optimizer_jpg_quality'>" . esc_html__('JPG quality level:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='text' id='ewww_image_optimizer_jpg_quality' name='ewww_image_optimizer_jpg_quality' class='small-text' value='" . ewww_image_optimizer_jpg_quality() . "' /> " . esc_html__('Valid values are 1-100.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "\n<p class='description'>" . esc_html__( 'Use this to override the default WordPress quality level of 82. This only applies to edited images, resizes, and converted PNG files. Original images are uploaded unmodified.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_parallel_optimization'>" . esc_html__('Parallel optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_parallel_optimization' name='ewww_image_optimizer_parallel_optimization' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_parallel_optimization') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('All resizes generated from a single upload are optimized in parallel for faster optimization. If this is causing performance issues, disable parallel optimization to reduce the load on your server.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "parallel optimization: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_parallel_optimization') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_auto'>" . esc_html__('Scheduled optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_auto' name='ewww_image_optimizer_auto' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_auto') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('This will enable scheduled optimization of unoptimized images for your theme, buddypress, and any additional folders you have configured below. Runs hourly: wp_cron only runs when your site is visited, so it may be even longer between optimizations.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "scheduled optimization: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_auto') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_aux_paths'>" . esc_html__('Folders to optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td>" . sprintf(esc_html__('One path per line, must be within %s. Use full paths, not relative paths.', EWWW_IMAGE_OPTIMIZER_DOMAIN), ABSPATH) . "<br>\n";
				$output[] = "<textarea id='ewww_image_optimizer_aux_paths' name='ewww_image_optimizer_aux_paths' rows='3' cols='60'>" . ( ( $aux_paths = ewww_image_optimizer_get_option( 'ewww_image_optimizer_aux_paths' ) ) ? implode( "\n", $aux_paths ) : "" ) . "</textarea>\n";
				$output[] = "<p class='description'>" . esc_html__( 'Provide paths containing images to be optimized using "Scan and Optimize" on the Bulk Optimize page or by Scheduled Optimization.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p></td></tr>\n";
				if ( ! empty( $aux_paths ) ) {
					ewwwio_debug_message( "folders to optimize:" );
					foreach ( $aux_paths as $aux_path ) {
						ewwwio_debug_message( $aux_path );
					}
				}
				$output[] = "<tr><th><label for='ewww_image_optimizer_include_media_paths'>" . esc_html__('Include Media Library Folders', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_include_media_paths' name='ewww_image_optimizer_include_media_paths' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_include_media_paths') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('Include the latest two folders from the Media Library in Scheduled Optimization and Optimize Everything Else.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "include media library: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_include_media_paths') == TRUE ? "on" : "off" ) );

				$output[] = "<tr><th>" . esc_html__( 'Resize Media Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</th><td><label for='ewww_image_optimizer_maxmediawidth'>" . esc_html__( 'Max Width', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label> <input type='number' step='1' min='0' class='small-text' id='ewww_image_optimizer_maxmediawidth' name='ewww_image_optimizer_maxmediawidth' value='" . ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxmediawidth' ) . "' /> <label for='ewww_image_optimizer_maxmediaheight'>" . esc_html__( 'Max Height', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label> <input type='number' step='1' min='0' class='small-text' id='ewww_image_optimizer_maxmediaheight' name='ewww_image_optimizer_maxmediaheight' value='" . ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxmediaheight' ) . "' />\n" .
				"<p class='description'>" . esc_html__('Resizes images uploaded directly to the Media Library and those uploaded within a post or page.', EWWW_IMAGE_OPTIMIZER_DOMAIN) .
				"</td></tr>\n";
				ewwwio_debug_message( "max media dimensions: " . ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxmediawidth' ) . ' x ' . ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxmediaheight' ) );
				$output[] = "<tr><th>" . esc_html__( 'Resize Other Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</th><td><label for='ewww_image_optimizer_maxotherwidth'>" . esc_html__( 'Max Width', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label> <input type='number' step='1' min='0' class='small-text' id='ewww_image_optimizer_maxotherwidth' name='ewww_image_optimizer_maxotherwidth' value='" . ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxotherwidth' ) . "' /> <label for='ewww_image_optimizer_maxotherheight'>" . esc_html__( 'Max Height', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label> <input type='number' step='1' min='0' class='small-text' id='ewww_image_optimizer_maxotherheight' name='ewww_image_optimizer_maxotherheight' value='" . ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxotherheight' ) . "' />\n" .
				"<p class='description'>" . esc_html__('Resizes images uploaded indirectly to the Media Library, like theme images or front-end uploads.', EWWW_IMAGE_OPTIMIZER_DOMAIN) .
				"</td></tr>\n";
				ewwwio_debug_message( "max other dimensions: " . ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxotherwidth' ) . ' x ' . ewww_image_optimizer_get_option( 'ewww_image_optimizer_maxotherheight' ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_resize_existing'>" . esc_html__( 'Resize Existing Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_resize_existing' name='ewww_image_optimizer_resize_existing' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_resize_existing') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__( 'Allow resizing of existing Media Library images.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( 'resize existing images: ' . ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_resize_existing' ) == TRUE ? 'on' : 'off' ) );



				$output[] = "<tr><th>" . esc_html__( 'Disable Resizes', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</th><td><p>" . esc_html__( 'Wordpress, your theme, and other plugins generate various image sizes. You may disable optimization for certain sizes, or completely prevent those sizes from being created.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p>\n";
				$image_sizes = ewww_image_optimizer_get_image_sizes();
				$disabled_sizes = ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_resizes' );
				$disabled_sizes_opt = ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_resizes_opt' );
				$output[] = '<table><tr><th>' . esc_html__( 'Disable Optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</th><th>' . esc_html__( 'Disable Creation', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</th></tr>\n";
				ewwwio_debug_message( 'disabled resizes:' );
				foreach ( $image_sizes as $size => $dimensions ) {
					if ( $size != 'thumbnail' ) {
						$output [] = "<tr><td><input type='checkbox' id='ewww_image_optimizer_disable_resizes_opt_$size' name='ewww_image_optimizer_disable_resizes_opt[$size]' value='true' " . ( ! empty( $disabled_sizes_opt[$size] ) ? "checked='true'" : "" ) . " /></td><td><input type='checkbox' id='ewww_image_optimizer_disable_resizes_$size' name='ewww_image_optimizer_disable_resizes[$size]' value='true' " . ( ! empty( $disabled_sizes[$size] ) ? "checked='true'" : "" ) . " /></td><td><label for='ewww_image_optimizer_disable_resizes_$size'>$size - {$dimensions['width']}x{$dimensions['height']}</label></td></tr>\n";
					} else {
						$output [] = "<tr><td><input type='checkbox' id='ewww_image_optimizer_disable_resizes_opt_$size' name='ewww_image_optimizer_disable_resizes_opt[$size]' value='true' " . ( ! empty( $disabled_sizes_opt[$size] ) ? "checked='true'" : "" ) . " /></td><td><input type='checkbox' id='ewww_image_optimizer_disable_resizes_$size' name='ewww_image_optimizer_disable_resizes[$size]' value='true' disabled /></td><td><label for='ewww_image_optimizer_disable_resizes_$size'>$size - {$dimensions['width']}x{$dimensions['height']}</label></td></tr>\n";
					}
					ewwwio_debug_message( $size . ': ' . ( ! empty( $disabled_sizes_opt[$size] ) ? "optimization=disabled " : "optimization=enabled " ) . ( ! empty( $disabled_sizes[$size] ) ? "creation=disabled" : "creation=enabled" ) );
				}
				$output[] = "</table>\n";
				$output[] = "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_skip_size'>" . esc_html__('Skip Small Images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='text' id='ewww_image_optimizer_skip_size' name='ewww_image_optimizer_skip_size' size='8' value='" . ewww_image_optimizer_get_option('ewww_image_optimizer_skip_size') . "'> " . esc_html__('Do not optimize images smaller than this (in bytes)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "skip images smaller than: " . ewww_image_optimizer_get_option('ewww_image_optimizer_skip_size') . " bytes" );
				$output[] = "<tr><th><label for='ewww_image_optimizer_skip_png_size'>" . esc_html__('Skip Large PNG Images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='text' id='ewww_image_optimizer_skip_png_size' name='ewww_image_optimizer_skip_png_size' size='8' value='" . ewww_image_optimizer_get_option('ewww_image_optimizer_skip_png_size') . "'> " . esc_html__('Do not optimize PNG images larger than this (in bytes)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "skip PNG images larger than: " . ewww_image_optimizer_get_option('ewww_image_optimizer_skip_png_size') . " bytes" );
				$output[] = "<tr><th><label for='ewww_image_optimizer_lossy_skip_full'>" . esc_html__('Exclude full-size images from lossy optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_lossy_skip_full' name='ewww_image_optimizer_lossy_skip_full' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_skip_full') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				ewwwio_debug_message( "exclude originals from lossy: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_skip_full') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_metadata_skip_full'>" . esc_html__('Exclude full-size images from metadata removal', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_metadata_skip_full' name='ewww_image_optimizer_metadata_skip_full' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_metadata_skip_full') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				ewwwio_debug_message( "exclude originals from metadata removal: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_metadata_skip_full') == TRUE ? "on" : "off" ) );
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_skip_bundle'>" . esc_html__('Use System Paths', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_skip_bundle' name='ewww_image_optimizer_skip_bundle' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_skip_bundle') == TRUE ? "checked='true'" : "" ) . " /> " . sprintf(esc_html__('If you have already installed the utilities in a system location, such as %s or %s, use this to force the plugin to use those versions and skip the auto-installers.', EWWW_IMAGE_OPTIMIZER_DOMAIN), '/usr/local/bin', '/usr/bin') . "</td></tr>\n";
				ewwwio_debug_message( "use system binaries: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_skip_bundle') == TRUE ? "yes" : "no" ) );
			$output[] = "</table>\n</div>\n";
			$output[] = "<div id='ewww-conversion-settings'>\n";
			$output[] = "<p>" . esc_html__('Conversion is only available for images in the Media Library (except WebP). By default, all images have a link available in the Media Library for one-time conversion. Turning on individual conversion operations below will enable conversion filters any time an image is uploaded or modified.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br />\n" .
				"<b>" . esc_html__('NOTE:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . esc_html__('The plugin will attempt to update image locations for any posts that contain the images. You may still need to manually update locations/urls for converted images.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "\n" .
			"</p>\n";
			$output[] = "<table class='form-table'>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_disable_convert_links'>" . esc_html__('Hide Conversion Links', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label</th><td><input type='checkbox' id='ewww_image_optimizer_disable_convert_links' name='ewww_image_optimizer_disable_convert_links' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_convert_links') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('Site or Network admins can use this to prevent other users from using the conversion links in the Media Library which bypass the settings below.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_delete_originals'>" . esc_html__('Delete originals', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_delete_originals' name='ewww_image_optimizer_delete_originals' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_delete_originals') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('This will remove the original image from the server after a successful conversion.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_webp'>" . esc_html__('JPG/PNG to WebP', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_webp' name='ewww_image_optimizer_webp' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp') == TRUE ? "checked='true'" : "" ) . " /> <b>" . esc_html__('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</b> ' . esc_html__('JPG to WebP conversion is lossy, but quality loss is minimal. PNG to WebP conversion is lossless.', EWWW_IMAGE_OPTIMIZER_DOMAIN) .  "</span>\n" .
				"<p class='description'>" . esc_html__('Originals are never deleted, and WebP images should only be served to supported browsers.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='#webp-rewrite'>" .  ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp_for_cdn' ) ? esc_html__('You can use the rewrite rules below to serve WebP images with Apache.', EWWW_IMAGE_OPTIMIZER_DOMAIN) : '' ) . "</a></td></tr>\n";
				ewwwio_debug_message( "webp conversion: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp') == TRUE ? "on" : "off" ) );
				if ( ! ewww_image_optimizer_ce_webp_enabled() ) {
					$output[] = "<tr><th><label for='ewww_image_optimizer_webp_for_cdn'>" . esc_html__('Alternative WebP Rewriting', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_webp_for_cdn' name='ewww_image_optimizer_webp_for_cdn' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp_for_cdn') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('Uses output buffering and libxml functionality from PHP. Use this if the Apache rewrite rules do not work, or if your images are served from a CDN.', EWWW_IMAGE_OPTIMIZER_DOMAIN) .  ' ' . sprintf( esc_html( 'Sites using a CDN may also use the WebP option in the %s plugin.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<a href="https://wordpress.org/plugins/cache-enabler/">Cache Enabler</a>' ). "</span></td></tr>";
				}
				ewwwio_debug_message( "alt webp rewriting: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp_for_cdn') == TRUE ? "on" : "off" ) );
//				$output[] = "<tr><th><label for='ewww_image_optimizer_webp_cdn_path'>" . esc_html__('WebP CDN URL', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_webp_cdn_path' name='ewww_image_optimizer_webp_cdn_path' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp_for_cdn') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('Uses output buffering and libxml functionality from PHP. Use this if the Apache rewrite rules do not work, or if your images are served from a CDN.', EWWW_IMAGE_OPTIMIZER_DOMAIN) .  "</span></td></tr>";
				$output[] = "<tr><th><label for='ewww_image_optimizer_jpg_to_png'>" . sprintf(esc_html__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'JPG', 'PNG') . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_jpg_to_png' name='ewww_image_optimizer_jpg_to_png' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_to_png') == TRUE ? "checked='true'" : "" ) . " /> <b>" . esc_html__('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . esc_html__('Removes metadata and increases cpu usage dramatically.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
				"<p class='description'>" . esc_html__('PNG is generally much better than JPG for logos and other images with a limited range of colors. Checking this option will slow down JPG processing significantly, and you may want to enable it only temporarily.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				ewwwio_debug_message( "jpg2png: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_to_png') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_png_to_jpg'>" . sprintf(esc_html__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'PNG', 'JPG') . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_png_to_jpg' name='ewww_image_optimizer_png_to_jpg' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_to_jpg') == TRUE ? "checked='true'" : "" ) . " /> <b>" . esc_html__('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . esc_html__('This is not a lossless conversion.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
				"<p class='description'>" . esc_html__('JPG is generally much better than PNG for photographic use because it compresses the image and discards data. PNGs with transparency are not converted by default.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n" .
				"<span><label for='ewww_image_optimizer_jpg_background'> " . esc_html__('JPG background color:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label> #<input type='text' id='ewww_image_optimizer_jpg_background' name='ewww_image_optimizer_jpg_background' size='6' value='" . ewww_image_optimizer_jpg_background() . "' /> <span style='padding-left: 12px; font-size: 12px; border: solid 1px #555555; background-color: #" . ewww_image_optimizer_jpg_background() . "'>&nbsp;</span> " . esc_html__('HEX format (#123def)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</span>\n" .
				"<p class='description'>" . esc_html__('Background color is used only if the PNG has transparency. Leave this value blank to skip PNGs with transparency.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
			//	"<span><label for='ewww_image_optimizer_jpg_quality'>" . esc_html__('JPG quality level:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label> <input type='text' id='ewww_image_optimizer_jpg_quality' name='ewww_image_optimizer_jpg_quality' class='small-text' value='" . ewww_image_optimizer_jpg_quality() . "' /> " . esc_html__('Valid values are 1-100.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
//				"<p class='description'>" . esc_html__('If JPG quality is blank, the plugin will attempt to set the optimal quality level or default to 92. Remember, this is a lossy conversion, so you are losing pixels, and it is not recommended to actually set the level here unless you want noticable loss of image quality.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				ewwwio_debug_message( "png2jpg: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_to_jpg') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_gif_to_png'>" . sprintf(esc_html__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'GIF', 'PNG') . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_gif_to_png' name='ewww_image_optimizer_gif_to_png' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_gif_to_png') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('No warnings here, just do it.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
				"<p class='description'> " . esc_html__('PNG is generally better than GIF, but animated images cannot be converted.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				ewwwio_debug_message( "gif2png: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_gif_to_png') == TRUE ? "on" : "off" ) );
			$output[] = "</table>\n</div>\n";
			$output[] = "<p class='submit'><input type='submit' class='button-primary' value='" . esc_attr__('Save Changes', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "' /></p>\n";
		$output[] = "</form>\n";
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp_for_cdn' ) && ! ewww_image_optimizer_ce_webp_enabled() ) {
		$output[] = "<form id='ewww-webp-rewrite'>\n";
			$output[] = "<p>" . esc_html__('There are many ways to serve WebP images to visitors with supported browsers. You may choose any you wish, but it is recommended to serve them with an .htaccess file using mod_rewrite and mod_headers. The plugin can insert the rules for you if the file is writable, or you can edit .htaccess yourself.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n";
			if ( ! ewww_image_optimizer_webp_rewrite_verify() ) {
				$output[] = "<img id='webp-image' src='" . plugins_url('/images/test.png', __FILE__) . "' style='float: right; padding: 0 0 10px 10px;'>\n" .
				"<p id='ewww-webp-rewrite-status'><b>" . esc_html__('Rules verified successfully', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b></p>\n";
				ewwwio_debug_message( 'webp .htaccess rewriting enabled' );
			} else {
				$output[] = "<pre id='webp-rewrite-rules' style='background: white; font-color: black; border: 1px solid black; clear: both; padding: 10px;'>\n" .
					"&lt;IfModule mod_rewrite.c&gt;\n" .
					"RewriteEngine On\n" .
					"RewriteCond %{HTTP_ACCEPT} image/webp\n" .
					"RewriteCond %{REQUEST_FILENAME} (.*)\.(jpe?g|png)$\n" .
					"RewriteCond %{REQUEST_FILENAME}\.webp -f\n" .
					"RewriteRule (.+)\.(jpe?g|png)$ %{REQUEST_FILENAME}.webp [T=image/webp,E=accept:1]\n" .
					"&lt;/IfModule&gt;\n" .
					"&lt;IfModule mod_headers.c&gt;\n" .
					"Header append Vary Accept env=REDIRECT_accept\n" .
					"&lt;/IfModule&gt;\n" .
					"AddType image/webp .webp</pre>\n" .
					"<img id='webp-image' src='" . plugins_url('/images/test.png', __FILE__) . "' style='float: right; padding-left: 10px;'>\n" .
					"<p id='ewww-webp-rewrite-status'>" . esc_html__('The image to the right will display a WebP image with WEBP in white text, if your site is serving WebP images and your browser supports WebP.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n" .
					"<button type='submit' class='button-secondary action'>" . esc_html__('Insert Rewrite Rules', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</button>\n";
				ewwwio_debug_message( 'webp .htaccess rules not detected' );

			}
		$output[] = "</form>\n";
		}
		$output[] = "</div><!-- end container left -->\n";
		$output[] = "<div id='ewww-container-right' style='border: 1px solid #e5e5e5; float: right; margin-left: -215px; padding: 0em 1.5em 1em; background-color: #fff; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04); display: inline-block; width: 174px;'>\n" .
			"<h2>" . esc_html__( 'Support EWWW I.O.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</h2>\n" .
			"<p>" . esc_html__( 'Would you like to help support development of this plugin?', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p>\n" .
			"<p><a href='https://translate.wordpress.org/projects/wp-plugins/ewww-image-optimizer/'>" . esc_html__( 'Help translate EWWW I.O.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a></p>\n" .
			"<p><a href='https://wordpress.org/support/view/plugin-reviews/ewww-image-optimizer#postform'>" . esc_html__( 'Write a review.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a></p>\n" .
			"<p>" . wp_kses( sprintf( __( 'Contribute directly via %s.',  EWWW_IMAGE_OPTIMIZER_DOMAIN ), "<a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=MKMQKCBFFG3WW'>Paypal</a>" ), array( 'a' => array( 'href' => array() ) ) ) . "</p>\n" .
			"<p>" . esc_html__( 'Use any of these referral links to show your appreciation:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p>\n" .
			"<p><b>" . esc_html__( 'Web Hosting:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</b><br>\n" .
				"<a href='https://partners.a2hosting.com/solutions.php?id=5959&url=638'>A2 Hosting:</a> " . esc_html_x( 'with automatic EWWW IO setup', 'A2 Hosting:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "<br>\n" .
				"<a href='http://www.bluehost.com/track/nosilver4u'>Bluehost</a><br>\n" .
				"<a href='http://www.dreamhost.com/r.cgi?132143'>Dreamhost</a><!-- <br>\n" .
				"<a href='http://www.liquidweb.com/?RID=nosilver4u'>liquidweb</a><br>\n" .
				"<a href='http://www.stormondemand.com/?RID=nosilver4u'>Storm on Demand</a>-->\n" .
			"</p>\n" .
			"<p><b>" . esc_html_x( 'VPS:', 'abbreviation for Virtual Private Server', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</b><br>\n" .
				"<a href='https://www.digitalocean.com/?refcode=89ef0197ec7e'>DigitalOcean</a><br>\n" .
				"<a href='https://clientarea.ramnode.com/aff.php?aff=1469'>RamNode</a><br>\n" .
			"</p>\n" .
			"<p><b>" . esc_html_x( 'CDN:', 'abbreviation for Content Delivery Network', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</b><br><a target='_blank' href='http://tracking.maxcdn.com/c/91625/36539/378'>" . esc_html__( 'Add MaxCDN to increase website speeds dramatically! Sign Up Now and Save 25%.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a> " . esc_html__( 'Integrate MaxCDN within Wordpress using the W3 Total Cache plugin.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p>\n" .
		"</div>\n" .
	"</div>\n";
	ewwwio_debug_message( 'max_execution_time: ' . ini_get('max_execution_time') );

	echo apply_filters( 'ewww_image_optimizer_settings', $output );

	if ( ewww_image_optimizer_get_option ( 'ewww_image_optimizer_debug' ) ) {
		?>
<script type="text/javascript">
    function selectText(containerid) {
        if (document.selection) {
            var range = document.body.createTextRange();
            range.moveToElementText(document.getElementById(containerid));
            range.select();
        } else if (window.getSelection) {
            var range = document.createRange();
            range.selectNode(document.getElementById(containerid));
            window.getSelection().addRange(range);
        }
    }
</script>
		<?php
		global $ewww_debug;
		echo '<p style="clear:both"><b>' . esc_html__( 'Debugging Information', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ':</b> <button onclick="selectText(' . "'ewww-debug-info'" . ')">' . esc_html__( 'Select All', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</button></p>';
		echo '<div id="ewww-debug-info" style="background:#ffff99;margin-left:-20px;padding:10px" contenteditable="true">' . $ewww_debug . '</div>';
	}
	ewwwio_memory( __FUNCTION__ );
	ewww_image_optimizer_debug_log();
}

function ewww_image_optimizer_filter_settings_page( $input ) {
	$output = '';
	foreach ( $input as $line ) {
		if ( ewww_image_optimizer_full_cloud() && preg_match( "/class='nocloud'/", $line ) ) {
			continue;
		} else {
			$output .= $line;
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return $output;
}

function ewwwio_debug_message( $message ) {
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
		global $ewww_debug;
		$ewww_debug .= "$message<br>";
		echo $message . "\n";
	}
}

function ewwwio_debug_backtrace() {
	if ( defined( 'DEBUG_BACKTRACE_IGNORE_ARGS' ) ) {
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
	} else {
		$backtrace = debug_backtrace( false );
	}
	array_shift( $backtrace );
	array_shift( $backtrace );
	return maybe_serialize( $backtrace );
}

function ewww_image_optimizer_dynamic_image_debug() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	echo "<div class='wrap'><h1>" . esc_html__( 'Dynamic Image Debugging', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</h1>";
	global $wpdb;
	$debug_images = $wpdb->get_results( "SELECT path,updates,updated,trace FROM $wpdb->ewwwio_images WHERE trace IS NOT NULL ORDER BY updated DESC LIMIT 100" );
	if ( count( $debug_images ) != 0 ) {
		foreach ( $debug_images as $image ) {
			$trace = unserialize( $image->trace );
			echo '<p><b>' . esc_html__( 'File path', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $image->path . '</b><br>';
			echo esc_html__( 'Number of attempted optimizations', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $image->updates . '<br>';
			echo esc_html__( 'Last attempted', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $image->updated . '<br>';
			echo esc_html__( 'PHP trace', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ':<br>';
			$i = 0;
			if ( is_array( $trace ) ) {
				foreach ( $trace as $function ) {
					if ( ! empty( $function['file'] ) && ! empty( $function['line'] ) ) {
						echo "#$i {$function['function']}() called at {$function['file']}:{$function['line']}<br>";
					} else {
						echo "#$i {$function['function']}() called<br>";
					}
					$i++;
				}
			} else {
				esc_html_e( 'Cannot display trace',  EWWW_IMAGE_OPTIMIZER_DOMAIN);
			}
			echo '</p>';
		}
	}
	echo '</div>';
	return;
}

function ewwwio_memory_output() {
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
		global $ewww_memory;
		$timestamp = date('y-m-d h:i:s.u') . "  ";
		if (!file_exists(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'memory.log'))
			touch(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'memory.log');
		file_put_contents(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'memory.log', $timestamp . $ewww_memory, FILE_APPEND);
	}
}

// EWWW replacements

// adds table to db for storing status of auxiliary images that have been optimized
function ewww_image_optimizer_install_sqlite_table() {
	global $wpdb;
	if ( ! isset( $wpdb ) && ! defined( 'DB_NAME' ) ) {
		$wpdb = new wpdb( ABSPATH . 'ewwwio.db' );
	}

	if ( ! isset( $wpdb->ewwwio_images ) ) {
		$wpdb->ewwwio_images = $wpdb->prefix . "ewwwio_images";
	}

	// create a table with 4 columns: an id, the file path, the md5sum, and the optimization results
	$images_sql = "CREATE TABLE $wpdb->ewwwio_images (
		id integer PRIMARY KEY AUTOINCREMENT,
		path text NOT NULL UNIQUE,
		results text NOT NULL,
		image_size int unsigned,
		orig_size int unsigned,
		updates int unsigned,
		updated timestamp,
		trace blob
	);";
	
	$images = $wpdb->query( $images_sql );

	$options_sql = "CREATE TABLE $wpdb->options (
		option_id integer PRIMARY KEY AUTOINCREMENT,
		option_name text UNIQUE DEFAULT NULL, 
		option_value text NOT NULL,
		autoload text DEFAULT 'yes'
	);";
	$options = $wpdb->query( $options_sql );
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
}

// adds table to db for storing status of auxiliary images that have been optimized
function ewww_image_optimizer_install_mysql_table() {
	global $wpdb;
	if ( ! isset( $wpdb ) && defined( 'DB_NAME' ) && DB_NAME ) {
		$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
	}
	if ( ! isset( $wpdb->ewwwio_images ) ) {
		$wpdb->ewwwio_images = $wpdb->prefix . "ewwwio_images";
	}

	//see if the path column exists, and what collation it uses to determine the column index size
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->ewwwio_images'" ) == $wpdb->ewwwio_images ) {
		return;
	}
	// get the current wpdb charset and collation
	$charset_collate = $wpdb->get_charset_collate();

	// create a table with 4 columns: an id, the file path, the md5sum, and the optimization results
	$images_sql = "CREATE TABLE $wpdb->ewwwio_images (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		path text NOT NULL,
		results varchar(55) NOT NULL,
		image_size int(10) unsigned,
		orig_size int(10) unsigned,
		updates int(5) unsigned,
		updated timestamp DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
		trace blob,
		UNIQUE KEY id (id),
		KEY path_image_size (path(191),image_size)
	) $charset_collate;";

	$images = $wpdb->query( $images_sql );
	$options_sql = "CREATE TABLE $wpdb->options (
		option_id bigint(20) unsigned NOT NULL auto_increment,
		option_name varchar(191) NOT NULL default '',
		option_value longtext NOT NULL,
		autoload varchar(20) NOT NULL default 'yes',
		PRIMARY KEY  (option_id),
		UNIQUE KEY option_name (option_name)
		) $charset_collate;";
	$options = $wpdb->query( $options_sql );

	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
}

function ewww_image_optimizer_upgrade_table() {
}

// WP replacement code
function plugin_dir_path( $file ) {
	return trailingslashit( dirname( $file ) );
}

function trailingslashit( $string ) {
	return untrailingslashit( $string ) . '/';
}

function untrailingslashit( $string ) {
	return rtrim( $string, '/\\' );
}

function _doing_it_wrong( $function, $message, $version ) {
	trigger_error( $function . ': ' . $message );
}

function maybe_serialize( $data ) {
	if ( is_array( $data ) || is_object( $data ) )
		return serialize( $data );
	if ( is_serialized( $data, false ) )
		return serialize( $data );

	return $data;
}

function maybe_unserialize( $original ) {
	if ( is_serialized( $original ) ) // don't attempt to unserialize data that wasn't serialized going in
		return @unserialize( $original );
	return $original;
}

function is_serialized( $data, $strict = true ) {
    // if it isn't a string, it isn't serialized.
    if ( ! is_string( $data ) ) {
        return false;
    }
    $data = trim( $data );
    if ( 'N;' == $data ) {
        return true;
    }
    if ( strlen( $data ) < 4 ) {
        return false;
    }
    if ( ':' !== $data[1] ) {
        return false;
    }
    if ( $strict ) {
        $lastc = substr( $data, -1 );
        if ( ';' !== $lastc && '}' !== $lastc ) {
            return false;
        }
    } else {
        $semicolon = strpos( $data, ';' );
        $brace     = strpos( $data, '}' );
        // Either ; or } must exist.
        if ( false === $semicolon && false === $brace )
            return false;
        // But neither must be in the first X characters.
        if ( false !== $semicolon && $semicolon < 3 )
            return false;
        if ( false !== $brace && $brace < 4 )
            return false;
    }
    $token = $data[0];
    switch ( $token ) {
        case 's' :
            if ( $strict ) {
                if ( '"' !== substr( $data, -2, 1 ) ) {
                    return false;
                }
            } elseif ( false === strpos( $data, '"' ) ) {
                return false;
            }
            // or else fall through
        case 'a' :
        case 'O' :
            return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
        case 'b' :
        case 'i' :
        case 'd' :
            $end = $strict ? '$' : '';
            return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
    }
    return false;
}

function mbstring_binary_safe_encoding( $reset = false ) {
	static $encodings = array();
	static $overloaded = null;
 
	if ( is_null( $overloaded ) )
		$overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 );
 
	if ( false === $overloaded )
		return;
 
	if ( ! $reset ) {
		$encoding = mb_internal_encoding();
		array_push( $encodings, $encoding );
		mb_internal_encoding( 'ISO-8859-1' );
	}
 
	if ( $reset && $encodings ) {
		$encoding = array_pop( $encodings );
		mb_internal_encoding( $encoding );
	}
}

function reset_mbstring_encoding() {
	mbstring_binary_safe_encoding( true );
}

function wp_upload_dir() {
	return array( 'basedir' => ABSPATH );
}

function size_format( $bytes, $decimals = 0 ) {
    $quant = array(
        'TB' => 1024 * 1024 * 1024 * 1024,
        'GB' => 1024 * 1024 * 1024,
        'MB' => 1024 * 1024,
        'KB' => 1024,
        'B'  => 1,
    );
 
    if ( 0 === $bytes ) {
        return number_format_i18n( 0, $decimals ) . ' B';
    }
 
    foreach ( $quant as $unit => $mag ) {
        if ( doubleval( $bytes ) >= $mag ) {
            return number_format_i18n( $bytes / $mag, $decimals ) . ' ' . $unit;
        }
    }
 
    return false;
}

function number_format_i18n( $number, $decimals = 0 ) {
    /*if ( isset( $wp_locale ) ) {
        $formatted = number_format( $number, absint( $decimals ), $wp_locale->number_format['decimal_point'], $wp_locale->number_format['thousands_sep'] );
    } else {*/
    $formatted = number_format( $number, absint( $decimals ) );
    return $formatted;
}

function absint( $maybeint ) {
	return abs( intval( $maybeint ) );
}

function esc_html( $string ) { // TODO: make this do something
	return $string;
}

// WP translation function replacements
function __( $string ) {
	return $string;
}

function _e( $string ) {
	echo $string;
}

function _x( $string ) {
	return $string;
}
function _n( $string1, $string2, $count ) {
	if ( $count == 1 ) {
		return $string1;
	} else {
		return $string2;
	}
}

// stubs
function add_action() {
}

function add_filter() {
}

function apply_filters( $hook, $data ) {
	return $data;
}

function do_action() {
}

// WP option replacements
function get_option( $option, $default = false ) {
	global $wpdb;
	global $alloptions;

	$option = trim( $option );
	if ( empty( $option ) )
		return false;
	if ( empty( $alloptions ) ) {
		load_alloptions();
	}
	if ( isset( $alloptions[$option] ) ) {
		$value = maybe_unserialize( $alloptions[$option] );
		//if ( $option != 'ewww_image_optimizer_debug' )
		//	echo "getting autoloaded: $option: $value\n";
	} else {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );
		// Has to be get_row instead of get_var because of funkiness with 0, false, null values
		if ( is_array( $row ) ) {
	//	print_r( $row );
	//	echo "\n";
			$value = $row['option_value'];
			//if ( $option != 'ewww_image_optimizer_debug' )
			//	echo "getting $option from db: $value\n";
			$alloptions[$option] = $value;
		} else { // option does not exist, so we must cache its non-existence
			return $default;
		}
	}

	return maybe_unserialize( $value );
}

function load_alloptions() {
	global $wpdb;
	global $alloptions;
	global $ewwwio_settings; // allow overrides from config.php

	if ( ! $alloptions ) {
		$suppress = $wpdb->suppress_errors();
		if ( ! $alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE autoload = 'yes'" ) )
			$alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options" );
		$wpdb->suppress_errors($suppress);
		$alloptions = array();
		foreach ( (array) $alloptions_db as $o ) {
			$option_name = $o['option_name'];
			$alloptions[ $option_name ] = $o['option_value'];
		}
		foreach ( $ewwwio_settings as $name => $value ) {
			//echo "setting $name to $value\n";
			$alloptions[ $name ] = $value;
		}
	}


}

function update_option( $option, $value, $autoload = null ) {
	global $wpdb;

	$option = trim($option);
	if ( empty($option) )
		return false;

	$old_value = get_option( $option );

	// If the new and old values are the same, no need to update.
	if ( $value === $old_value )
		return false;

	$serialized_value = maybe_serialize( $value );

	$update_args = array(
		'option_value' => $serialized_value,
	);

	if ( null !== $autoload ) {
		$update_args['autoload'] = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';
	}

	$result = $wpdb->update( $wpdb->options, $update_args, array( 'option_name' => $option ) );
	if ( ! $result && $wpdb->ready )
		return false;
	global $alloptions;
	if ( isset( $alloptions[$option] ) ) {
		$alloptions[ $option ] = $serialized_value;
	}

	return true;
}

function add_option( $option, $value = '', $deprecated = '', $autoload = 'yes' ) {
	global $wpdb;

	$option = trim($option);
	if ( empty($option) )
		return false;

	$serialized_value = maybe_serialize( $value );
	$autoload = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';

	if ( $wpdb->is_mysql ) {
		//$result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) );
		$result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s)", $option, $serialized_value, $autoload ) );
	} else {
		$result = $wpdb->query( $wpdb->prepare( "INSERT OR IGNORE INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s)", $option, $serialized_value, $autoload ) );
	}
	if ( ! $result && $wpdb->ready )
		return false;

	if ( 'yes' == $autoload ) {
		global $alloptions;
		$alloptions[ $option ] = $serialized_value;
	}

	return true;
}

/**
 * Removes option by name. Prevents removal of protected WordPress options.
 *
 * @since 1.2.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $option Name of option to remove. Expected to not be SQL-escaped.
 * @return bool True, if option is successfully deleted. False on failure.
 */
function delete_option( $option ) {
	global $wpdb;

	$option = trim( $option );
	if ( empty( $option ) )
		return false;

	// Get the ID, if no ID then return
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", $option ) );
	if ( is_null( $row ) )
		return false;

	$result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option ) );
		if ( 'yes' == $row['autoload'] ) {
			global $alloptions;
			if ( is_array( $alloptions ) && isset( $alloptions[$option] ) ) {
				unset( $alloptions[$option] );
			}
		}
	if ( $result ) {
		return true;
	}
	return false;
}

function delete_transient( $transient ) {

	$option_timeout = '_transient_timeout_' . $transient;
	$option = '_transient_' . $transient;
	$result = delete_option( $option );
	if ( $result )
		delete_option( $option_timeout );

	return $result;
}

function get_transient( $transient ) {

	$transient_option = '_transient_' . $transient;
	// If option is not in alloptions, it is not autoloaded and thus has a timeout
	global $alloptions;
	if ( ! isset( $alloptions[$transient_option] ) ) {
		$transient_timeout = '_transient_timeout_' . $transient;
		$timeout = get_option( $transient_timeout );
		if ( false !== $timeout && $timeout < time() ) {
			delete_option( $transient_option  );
			delete_option( $transient_timeout );
			$value = false;
		}
	}

	if ( ! isset( $value ) )
		$value = get_option( $transient_option );

	return $value;
}

function set_transient( $transient, $value, $expiration = 0 ) {

	$expiration = (int) $expiration;

	$transient_timeout = '_transient_timeout_' . $transient;
	$transient_option = '_transient_' . $transient;
	if ( false === get_option( $transient_option ) ) {
		$autoload = 'yes';
		if ( $expiration ) {
			$autoload = 'no';
			add_option( $transient_timeout, time() + $expiration, '', 'no' );
		}
		$result = add_option( $transient_option, $value, '', $autoload );
	} else {
		// If expiration is requested, but the transient has no timeout option,
		// delete, then re-create transient rather than update.
		$update = true;
		if ( $expiration ) {
			if ( false === get_option( $transient_timeout ) ) {
				delete_option( $transient_option );
				add_option( $transient_timeout, time() + $expiration, '', 'no' );
				$result = add_option( $transient_option, $value, '', 'no' );
				$update = false;
			} else {
				update_option( $transient_timeout, time() + $expiration );
			}
		}
		if ( $update ) {
			$result = update_option( $transient_option, $value );
		}
	}
	return $result;
}

//basically just used to generate random strings
function generate_password( $length = 12 ) {
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$password = '';
	for ( $i = 0; $i < $length; $i++ ) {
		$password .= substr( $chars, rand( 0, strlen( $chars ) - 1 ), 1 );
	}
	return $password;
}
?>
