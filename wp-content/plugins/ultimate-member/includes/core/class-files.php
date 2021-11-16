<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Files' ) ) {


	/**
	 * Class Files
	 * @package um\core
	 */
	class Files {


		/**
		 * @var
		 */
		var $upload_temp;


		/**
		 * @var
		 */
		var $upload_baseurl;


		/**
		 * @var
		 */
		var $upload_basedir;


		/**
		 * Files constructor.
		 */
		function __construct() {

			$this->setup_paths();

			add_action( 'template_redirect', array( &$this, 'download_routing' ), 1 );

			$this->fonticon = array(
				'pdf' 	=> array('icon' 	=> 'um-faicon-file-pdf-o', 'color' => '#D24D4D' ),
				'txt' 	=> array('icon' 	=> 'um-faicon-file-text-o' ),
				'csv' 	=> array('icon' 	=> 'um-faicon-file-text-o' ),
				'doc' 	=> array('icon' 	=> 'um-faicon-file-text-o', 'color' => '#2C95D5' ),
				'docx' 	=> array('icon' 	=> 'um-faicon-file-text-o', 'color' => '#2C95D5' ),
				'odt' 	=> array('icon' 	=> 'um-faicon-file-text-o', 'color' => '#2C95D5' ),
				'ods' 	=> array('icon' 	=> 'um-faicon-file-excel-o', 'color' => '#51BA6A' ),
				'xls' 	=> array('icon' 	=> 'um-faicon-file-excel-o', 'color' => '#51BA6A' ),
				'xlsx' 	=> array('icon' 	=> 'um-faicon-file-excel-o', 'color' => '#51BA6A' ),
				'zip' 	=> array('icon' 	=> 'um-faicon-file-zip-o' ),
				'rar' 	=> array('icon'		=> 'um-faicon-file-zip-o' ),
				'mp3'	=> array('icon'		=> 'um-faicon-file-audio-o' ),
				'jpg' 	=> array('icon' 	=> 'um-faicon-picture-o' ),
				'jpeg' 	=> array('icon' 	=> 'um-faicon-picture-o' ),
				'png' 	=> array('icon' 	=> 'um-icon-image' ),
				'gif' 	=> array('icon' 	=> 'um-icon-images' ),
				'eps' 	=> array('icon' 	=> 'um-icon-images' ),
				'psd' 	=> array('icon' 	=> 'um-icon-images' ),
				'tif' 	=> array('icon' 	=> 'um-icon-image' ),
				'tiff' 	=> array('icon' 	=> 'um-icon-image' ),
			);

			$this->default_file_fonticon = 'um-faicon-file-o';
		}


		/**
		 * File download link generate
		 *
		 * @param int $form_id
		 * @param string $field_key
		 * @param int $user_id
		 *
		 * @return string
		 */
		function get_download_link( $form_id, $field_key, $user_id ) {
			$field_key = urlencode( $field_key );

			if ( UM()->is_permalinks ) {
				$url = get_home_url( get_current_blog_id() );
				$nonce = wp_create_nonce( $user_id . $form_id . 'um-download-nonce' );
				$url = $url . "/um-download/{$form_id}/{$field_key}/{$user_id}/{$nonce}";
			} else {
				$url = get_home_url( get_current_blog_id() );
				$nonce = wp_create_nonce( $user_id . $form_id . 'um-download-nonce' );
				$url = add_query_arg( array( 'um_action' => 'download', 'um_form' => $form_id, 'um_field' => $field_key, 'um_user' => $user_id, 'um_verify' => $nonce ), $url );
			}

			//add time to query args for sites with the cache
			return add_query_arg( array( 't' => time() ), $url );
		}


		/**
		 * @return bool
		 */
		function download_routing() {
			if ( 'download' !== get_query_var( 'um_action' ) ) {
				return false;
			}

			$query_form = get_query_var( 'um_form' );
			if ( empty( $query_form ) ) {
				return false;
			}

			$form_id = get_query_var( 'um_form' );
			$query_field = get_query_var( 'um_field' );
			if ( empty( $query_field ) ) {
				return false;
			}
			$field_key = urldecode( get_query_var( 'um_field' ) );
			$query_user = get_query_var( 'um_user' );
			if ( empty( $query_user ) ) {
				return false;
			}

			$user_id = get_query_var( 'um_user' );
			$user = get_userdata( $user_id );

			if ( empty( $user ) || is_wp_error( $user ) ) {
				return false;
			}
			$query_verify = get_query_var( 'um_verify' );
			if ( empty( $query_verify ) ||
			     ! wp_verify_nonce( $query_verify, $user_id . $form_id . 'um-download-nonce' ) ) {
				return false;
			}

			um_fetch_user( $user_id );
			$field_data = get_post_meta( $form_id, '_um_custom_fields', true );
			if ( empty( $field_data[ $field_key ] ) ) {
				return false;
			}

			if ( ! um_can_view_field( $field_data[ $field_key ] ) ) {
				return false;
			}

			$field_value = UM()->fields()->field_value( $field_key );
			if ( empty( $field_value ) ) {
				return false;
			}

			$download_type = $field_data[ $field_key ]['type'];
			if ( $download_type === 'file' ) {
				$this->file_download( $user_id, $field_key, $field_value );
			} else {
				$this->image_download( $user_id, $field_key, $field_value );
			}

			return false;
		}


		/**
		 * @param $user_id
		 * @param $field_key
		 * @param $field_value
		 */
		function image_download( $user_id, $field_key, $field_value ) {
			$file_path = UM()->uploader()->get_upload_base_dir() . $user_id . DIRECTORY_SEPARATOR . $field_value;
			if ( ! file_exists( $file_path ) ) {
				if ( is_multisite() ) {
					//multisite fix for old customers
					$file_path = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $file_path );
				}
			}

			//validate traversal file
			if ( validate_file( $file_path ) === 1 ) {
				return;
			}

			$file_info = get_user_meta( $user_id, $field_key . "_metadata", true );

			$pathinfo = pathinfo( $file_path );
			$size = filesize( $file_path );
			$originalname = ! empty( $file_info['original_name'] ) ? $file_info['original_name'] : $pathinfo['basename'];
			$type = ! empty( $file_info['type'] ) ? $file_info['type'] : $pathinfo['extension'];

			header('Content-Description: File Transfer');
			header('Content-Type: ' . $type );
			header('Content-Disposition: inline; filename="' . $originalname . '"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . $size);

			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean();
			}

			readfile( $file_path );
			exit;
		}


		/**
		 * @param $user_id
		 * @param $field_key
		 * @param $field_value
		 */
		function file_download( $user_id, $field_key, $field_value ) {
			$file_path = UM()->uploader()->get_upload_base_dir() . $user_id . DIRECTORY_SEPARATOR . $field_value;
			if ( ! file_exists( $file_path ) ) {
				if ( is_multisite() ) {
					//multisite fix for old customers
					$file_path = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $file_path );
				}
			}

			//validate traversal file
			if ( validate_file( $file_path ) === 1 ) {
				return;
			}

			$file_info = get_user_meta( $user_id, $field_key . "_metadata", true );

			$pathinfo = pathinfo( $file_path );
			$size = filesize( $file_path );
			$originalname = ! empty( $file_info['original_name'] ) ? $file_info['original_name'] : $pathinfo['basename'];
			$type = ! empty( $file_info['type'] ) ? $file_info['type'] : $pathinfo['extension'];

			header('Content-Description: File Transfer');
			header('Content-Type: ' . $type );
			header('Content-Disposition: attachment; filename="' . $originalname . '"');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . $size);

			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean();
			}

			readfile( $file_path );
			exit;
		}


		/**
		 * Remove file by AJAX
		 */
		function ajax_remove_file() {
			UM()->check_ajax_nonce();

			if ( empty( $_POST['src'] ) ) {
				wp_send_json_error( __( 'Wrong path', 'ultimate-member' ) );
			}

			if ( empty( $_POST['mode'] ) ) {
				wp_send_json_error( __( 'Wrong mode', 'ultimate-member' ) );
			}

			$src = esc_url_raw( $_POST['src'] );
			if ( strstr( $src, '?' ) ) {
				$splitted = explode( '?', $src );
				$src = $splitted[0];
			}

			$mode = sanitize_key( $_POST['mode'] );

			if ( $mode == 'register' || empty( $_POST['user_id'] ) ) {

				$is_temp = um_is_temp_upload( $src );
				if ( ! $is_temp ) {
					wp_send_json_success();
				}

			} else {

				$user_id = absint( $_POST['user_id'] );

				if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
					wp_send_json_error( __( 'You have no permission to edit this user', 'ultimate-member' ) );
				}

				$is_temp = um_is_temp_upload( $src );
				if ( ! $is_temp ) {
					if ( ! empty( $_POST['filename'] ) && file_exists( UM()->uploader()->get_upload_user_base_dir( $user_id ) . DIRECTORY_SEPARATOR . sanitize_file_name( $_POST['filename'] ) ) ) {
						wp_send_json_success();
					}
				}

			}

			if ( $this->delete_file( $src ) ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( __( 'You have no permission to delete this file', 'ultimate-member' ) );
			}
		}


		/**
		 * Resize image AJAX handler
		 */
		function ajax_resize_image() {
			UM()->check_ajax_nonce();

			/**
			 * @var $key
			 * @var $src
			 * @var $coord
			 * @var $user_id
			 */
			extract( $_REQUEST );

			if ( ! isset( $src ) || ! isset( $coord ) ) {
				wp_send_json_error( esc_js( __( 'Invalid parameters', 'ultimate-member' ) ) );
			}

			$coord_n = substr_count( $coord, "," );
			if ( $coord_n != 3 ) {
				wp_send_json_error( esc_js( __( 'Invalid coordinates', 'ultimate-member' ) ) );
			}

			$user_id = empty( $_REQUEST['user_id'] ) ? get_current_user_id() : absint( $_REQUEST['user_id'] );

			UM()->fields()->set_id = filter_input( INPUT_POST, 'set_id', FILTER_SANITIZE_NUMBER_INT );
			UM()->fields()->set_mode = filter_input( INPUT_POST, 'set_mode', FILTER_SANITIZE_STRING );

			if ( UM()->fields()->set_mode != 'register' && ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				$ret['error'] = esc_js( __( 'You have no permission to edit this user', 'ultimate-member' ) );
				wp_send_json_error( $ret );
			}

			$src = esc_url_raw( $src );

			$image_path = um_is_file_owner( $src, $user_id, true );
			if ( ! $image_path ) {
				wp_send_json_error( esc_js( __( 'Invalid file ownership', 'ultimate-member' ) ) );
			}

			UM()->uploader()->replace_upload_dir = true;
			$output = UM()->uploader()->resize_image( $image_path, $src, sanitize_text_field( $key ), $user_id, sanitize_text_field( $coord ) );
			UM()->uploader()->replace_upload_dir = false;

			delete_option( "um_cache_userdata_{$user_id}" );

			wp_send_json_success( $output );
		}


		/**
		 * Image upload by AJAX
		 *
		 * @throws \Exception
		 */
		function ajax_image_upload() {
			$ret['error'] = null;
			$ret = array();

			$id = sanitize_text_field( $_POST['key'] );
			$timestamp = absint( $_POST['timestamp'] );
			$nonce = sanitize_text_field( $_POST['_wpnonce'] );
			$user_id = empty( $_POST['user_id'] ) ? get_current_user_id() : absint( $_POST['user_id'] );

			UM()->fields()->set_id = absint( $_POST['set_id'] );
			UM()->fields()->set_mode = sanitize_key( $_POST['set_mode'] );

			if ( UM()->fields()->set_mode != 'register' && ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				$ret['error'] = __( 'You have no permission to edit this user', 'ultimate-member' );
				wp_send_json_error( $ret );
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_image_upload_nonce
			 * @description Change Image Upload nonce
			 * @input_vars
			 * [{"var":"$nonce","type":"bool","desc":"Nonce"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_image_upload_nonce', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_image_upload_nonce', 'my_image_upload_nonce', 10, 1 );
			 * function my_image_upload_nonce( $nonce ) {
			 *     // your code here
			 *     return $nonce;
			 * }
			 * ?>
			 */
			$um_image_upload_nonce = apply_filters( 'um_image_upload_nonce', true );

			if ( $um_image_upload_nonce ) {
				if ( ! wp_verify_nonce( $nonce, "um_upload_nonce-{$timestamp}" ) && is_user_logged_in() ) {
					// This nonce is not valid.
					$ret['error'] = __( 'Invalid nonce', 'ultimate-member' );
					wp_send_json_error( $ret );
				}
			}

			if ( isset( $_FILES[ $id ]['name'] ) ) {

				if ( ! is_array( $_FILES[ $id ]['name'] ) ) {

					UM()->uploader()->replace_upload_dir = true;
					$uploaded = UM()->uploader()->upload_image( $_FILES[ $id ], $user_id, $id );
					UM()->uploader()->replace_upload_dir = false;
					if ( isset( $uploaded['error'] ) ) {
						$ret['error'] = $uploaded['error'];
					} else {
						$ret[] = $uploaded['handle_upload'];
					}

				}

			} else {
				$ret['error'] = __( 'A theme or plugin compatibility issue', 'ultimate-member' );
			}
			wp_send_json_success( $ret );
		}


		/**
		 * File upload by AJAX
		 */
		function ajax_file_upload() {
			$ret['error'] = null;
			$ret = array();

			/* commented for enable download files on registration form
			 * if ( ! is_user_logged_in() ) {
				$ret['error'] = 'Invalid user';
				die( json_encode( $ret ) );
			}*/

			$nonce = sanitize_text_field( $_POST['_wpnonce'] );
			$id = sanitize_text_field( $_POST['key'] );
			$timestamp = absint( $_POST['timestamp'] );

			UM()->fields()->set_id = absint( $_POST['set_id'] );
			UM()->fields()->set_mode = sanitize_key( $_POST['set_mode'] );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_file_upload_nonce
			 * @description Change File Upload nonce
			 * @input_vars
			 * [{"var":"$nonce","type":"bool","desc":"Nonce"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_file_upload_nonce', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_file_upload_nonce', 'my_file_upload_nonce', 10, 1 );
			 * function my_file_upload_nonce( $nonce ) {
			 *     // your code here
			 *     return $nonce;
			 * }
			 * ?>
			 */
			$um_file_upload_nonce = apply_filters("um_file_upload_nonce", true );

			if ( $um_file_upload_nonce  ) {
				if ( ! wp_verify_nonce( $nonce, 'um_upload_nonce-'.$timestamp  ) && is_user_logged_in() ) {
					// This nonce is not valid.
					$ret['error'] = 'Invalid nonce';
					wp_send_json_error( $ret );

				}
			}


			if( isset( $_FILES[ $id ]['name'] ) ) {

				if ( ! is_array( $_FILES[ $id ]['name'] ) ) {

					$user_id = absint( $_POST['user_id'] );

					UM()->uploader()->replace_upload_dir = true;
					$uploaded = UM()->uploader()->upload_file( $_FILES[ $id ], $user_id, $id );
					UM()->uploader()->replace_upload_dir = false;
					if ( isset( $uploaded['error'] ) ){

						$ret['error'] = $uploaded['error'];

					} else {

						$uploaded_file = $uploaded['handle_upload'];
						$ret['url'] = $uploaded_file['file_info']['name'];
						$ret['icon'] = UM()->files()->get_fonticon_by_ext( $uploaded_file['file_info']['ext'] );
						$ret['icon_bg'] = UM()->files()->get_fonticon_bg_by_ext( $uploaded_file['file_info']['ext'] );
						$ret['filename'] = $uploaded_file['file_info']['basename'];
						$ret['original_name'] = $uploaded_file['file_info']['original_name'];

					}

				}

			} else {
				$ret['error'] = __('A theme or plugin compatibility issue','ultimate-member');
			}


			wp_send_json_success( $ret );
		}


		/**
		 * Allowed image types
		 *
		 * @return array
		 */
		function allowed_image_types() {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_allowed_image_types
			 * @description Extend allowed image types
			 * @input_vars
			 * [{"var":"$types","type":"array","desc":"Image ext types"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_allowed_image_types', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_allowed_image_types', 'my_allowed_image_types', 10, 1 );
			 * function my_allowed_image_types( $types ) {
			 *     // your code here
			 *     return $types;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_allowed_image_types', array(
				'png'   => 'PNG',
				'jpeg'  => 'JPEG',
				'jpg'   => 'JPG',
				'gif'   => 'GIF'
			) );
		}


		/**
		 * Allowed file types
		 *
		 * @return mixed
		 */
		function allowed_file_types() {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_allowed_file_types
			 * @description Extend allowed File types
			 * @input_vars
			 * [{"var":"$types","type":"array","desc":"Files ext types"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_allowed_file_types', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_allowed_file_types', 'my_allowed_file_types', 10, 1 );
			 * function my_allowed_file_types( $types ) {
			 *     // your code here
			 *     return $types;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_allowed_file_types', array(
				'pdf'   => 'PDF',
				'txt'   => 'Text',
				'csv'   => 'CSV',
				'doc'   => 'DOC',
				'docx'  => 'DOCX',
				'odt'   => 'ODT',
				'ods'   => 'ODS',
				'xls'   => 'XLS',
				'xlsx'  => 'XLSX',
				'zip'   => 'ZIP',
				'rar'   => 'RAR',
				'mp3'   => 'MP3',
				'jpg'   => 'JPG',
				'jpeg'  => 'JPEG',
				'png'   => 'PNG',
				'gif'   => 'GIF',
				'eps'   => 'EPS',
				'psd'   => 'PSD',
				'tif'   => 'TIF',
				'tiff'  => 'TIFF',
			) );
		}


		/**
		 * Get extension icon
		 *
		 * @param $extension
		 *
		 * @return string
		 */
		function get_fonticon_by_ext( $extension ) {
			if ( isset( $this->fonticon[$extension]['icon'] ) ) {
				return $this->fonticon[$extension]['icon'];
			} else {
				return $this->default_file_fonticon;
			}
		}


		/**
		 * Get extension icon background
		 *
		 * @param $extension
		 *
		 * @return string
		 */
		function get_fonticon_bg_by_ext( $extension ) {
			if ( isset( $this->fonticon[$extension]['color'] ) ) {
				return $this->fonticon[$extension]['color'];
			} else {
				return '#666';
			}
		}


		/**
		 * Setup upload directory
		 */
		function setup_paths() {

			$this->upload_dir = wp_upload_dir();

			$this->upload_basedir = $this->upload_dir['basedir'] . '/ultimatemember/';
			$this->upload_baseurl = $this->upload_dir['baseurl'] . '/ultimatemember/';

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_upload_basedir_filter
			 * @description Change Uploads Basedir
			 * @input_vars
			 * [{"var":"$basedir","type":"string","desc":"Uploads basedir"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_upload_basedir_filter', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_upload_basedir_filter', 'my_upload_basedir', 10, 1 );
			 * function my_upload_basedir( $basedir ) {
			 *     // your code here
			 *     return $basedir;
			 * }
			 * ?>
			 */
			$this->upload_basedir = apply_filters( 'um_upload_basedir_filter', $this->upload_basedir );
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_upload_baseurl_filter
			 * @description Change Uploads Base URL
			 * @input_vars
			 * [{"var":"$baseurl","type":"string","desc":"Uploads base URL"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_upload_baseurl_filter', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_upload_baseurl_filter', 'my_upload_baseurl', 10, 1 );
			 * function my_upload_baseurl( $baseurl ) {
			 *     // your code here
			 *     return $baseurl;
			 * }
			 * ?>
			 */
			$this->upload_baseurl = apply_filters( 'um_upload_baseurl_filter', $this->upload_baseurl );

			// @note : is_ssl() doesn't work properly for some sites running with load balancers
			// Check the links for more info about this bug
			// https://codex.wordpress.org/Function_Reference/is_ssl
			// http://snippets.webaware.com.au/snippets/wordpress-is_ssl-doesnt-work-behind-some-load-balancers/
			if ( is_ssl() || stripos( get_option( 'siteurl' ), 'https://' ) !== false
			     || ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) ) {
				$this->upload_baseurl = str_replace("http://", "https://",  $this->upload_baseurl);
			}

			$this->upload_temp = $this->upload_basedir . 'temp/';
			$this->upload_temp_url = $this->upload_baseurl . 'temp/';

			if ( ! file_exists( $this->upload_basedir ) ) {
				$old = umask(0);
				@mkdir( $this->upload_basedir, 0755, true );
				umask( $old );
			}

			if ( ! file_exists( $this->upload_temp ) ) {
				$old = umask(0);
				@mkdir( $this->upload_temp , 0755, true );
				umask( $old );
			}

		}


		/**
		 * Generate unique temp directory
		 *
		 * @return mixed
		 */
		function unique_dir(){
			$unique_number = UM()->validation()->generate();
			$array['dir'] = $this->upload_temp . $unique_number . '/';
			$array['url'] = $this->upload_temp_url . $unique_number . '/';
			return $array;
		}


		/**
		 * Get path only without file name
		 *
		 * @param $file
		 *
		 * @return string
		 */
		function path_only( $file ) {

			return trailingslashit( dirname( $file ) );
		}


		/**
		 * Fix image orientation
		 *
		 * @param $rotate
		 * @param $source
		 *
		 * @return resource
		 */
		function fix_image_orientation( $rotate, $source ) {
			if ( extension_loaded('exif') ) {
				$exif = @exif_read_data( $source );

				if ( isset( $exif['Orientation'] ) ) {
					switch ( $exif['Orientation'] ) {
						case 3:
							$rotate = imagerotate( $rotate, 180, 0 );
							break;

						case 6:
							$rotate = imagerotate( $rotate, -90, 0 );
							break;

						case 8:
							$rotate = imagerotate( $rotate, 90, 0 );
							break;
					}
				}
			}
			return $rotate;
		}


		/**
		 * Process an image
		 *
		 * @param $source
		 * @param $destination
		 * @param int $quality
		 *
		 * @return array
		 */
		function create_and_copy_image($source, $destination, $quality = 100) {

			$info = @getimagesize($source);

			if ($info['mime'] == 'image/jpeg'){

				$image = imagecreatefromjpeg( $source );

			} else if ($info['mime'] == 'image/gif'){

				$image = imagecreatefromgif( $source );

			} else if ($info['mime'] == 'image/png'){

				$image = imagecreatefrompng( $source );
				imagealphablending( $image, false );
				imagesavealpha( $image, true );

			}

			list($w, $h) = @getimagesize( $source );
			if ( $w > UM()->options()->get('image_max_width') ) {

				$ratio = round( $w / $h, 2 );
				$new_w = UM()->options()->get('image_max_width');
				$new_h = round( $new_w / $ratio, 2 );

				if ( $info['mime'] == 'image/jpeg' ||  $info['mime'] == 'image/gif' ){

					$image_p = imagecreatetruecolor( $new_w, $new_h );
					imagecopyresampled( $image_p, $image, 0, 0, 0, 0, $new_w, $new_h, $w, $h );
					$image_p = $this->fix_image_orientation( $image_p, $source );

				}else if( $info['mime'] == 'image/png' ){

					$srcImage = $image;
					$targetImage = imagecreatetruecolor( $new_w, $new_h );
					imagealphablending( $targetImage, false );
					imagesavealpha( $targetImage, true );
					imagecopyresampled( $targetImage, $srcImage,   0, 0, 0, 0, $new_w, $new_h, $w, $h );

				}

				if ( $info['mime'] == 'image/jpeg' ){
					$has_copied = imagejpeg( $image_p, $destination, $quality );
				}else if ( $info['mime'] == 'image/gif' ){
					$has_copied = imagegif( $image_p, $destination );
				}else if ( $info['mime'] == 'image/png' ){
					$has_copied = imagepng( $targetImage, $destination, 0 ,PNG_ALL_FILTERS);
				}

				$info['um_has_max_width'] = 'custom';
				$info['um_has_copied'] = $has_copied ? 'yes':'no';

			} else {

				$image = $this->fix_image_orientation( $image, $source );

				if ( $info['mime'] == 'image/jpeg' ){
					$has_copied = imagejpeg( $image, $destination, $quality );
				}else if ( $info['mime'] == 'image/gif' ){
					$has_copied = imagegif( $image, $destination );
				}else if ( $info['mime'] == 'image/png' ){
					$has_copied = imagepng( $image , $destination , 0 ,PNG_ALL_FILTERS);
				}

				$info['um_has_max_width'] = 'default';
				$info['um_has_copied'] = $has_copied ? 'yes':'no';
			}

			return $info;
		}


		/**
		 * Process a file
		 *
		 * @param $source
		 * @param $dest