<?php

if ( ! function_exists ( 'yith_get_filesize_text' ) ) {
	/**
	 * Given a size in bytes, build a text rappresentation of the size using the correct unit of measure
	 *
	 * @param $size size in bytes
	 *
	 * @return string Textual rappresentation of the size
	 */
	function yith_get_filesize_text( $size ) {
		$unit = array( "bytes", "KB", "MB", "GB", "TB" );
		$step = 0;
		while ( $size >= 1024 ) {
			$size = $size / 1024;
			$step ++;
		}
		
		return sprintf ( "%s %s", round ( $size ), $unit[ $step ] );
	}
}

if ( ! function_exists ( 'yith_create_zip' ) ) {
	/**
	 * Create a compressed archive containing one or more files
	 *
	 * @param array  $files       files to add to the compressed archive
	 * @param string $destination path of the resulting file
	 * @param        $base_folder base folder of the files
	 * @param bool   $overwrite
	 */
	function yith_create_zip( $files = array(), $destination = '', $base_folder, $overwrite = false ) {
		
		$archive = new PclZip( $destination );
		
		foreach ( $files as $file ) {
			$v_list = $archive->add ( $file, PCLZIP_OPT_REMOVE_PATH, $base_folder );
		}
		
		if ( $v_list == 0 ) {
			die( "Error : " . $archive->errorInfo ( true ) );
		}
	}
}

if ( ! function_exists ( 'yith_download_file' ) ) {
	
	/**
	 * Download a file
	 *
	 * @param $filepath
	 */
	function yith_download_file( $filepath ) {

        if ( file_exists( $filepath ) ) {

            $type = filetype ( $filepath );
            $size = filesize ( $filepath );
            $name = basename ( $filepath );

            header('Content-Description: File Transfer');
            header("Content-Type: " . $type );
            header("Content-Disposition: attachment; filename=" . $name );
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header( "Content-Length: " . $size );

            readfile( $filepath );

            exit;

        }

	}
}

if ( ! function_exists ( 'yith_delete_folder' ) ) {
	
	function yith_delete_folder( $path ) {
		if ( is_dir ( $path ) === true ) {
			$files = array_diff ( scandir ( $path ), array( '.', '..' ) );
			
			foreach ( $files as $file ) {
				yith_delete_folder ( realpath ( $path ) . '/' . $file );
			}
			
			return rmdir ( $path );
		} else if ( is_file ( $path ) === true ) {
			return unlink ( $path );
		}
		
		return false;
	}
}

if ( ! function_exists ( 'ywqe_get_dropbox' ) ) {
	/**
	 * Load all requirements for Dropbox and retrieve the instance
	 * @return YITH_Quick_Export_DropBox
	 */
	function ywqe_get_dropbox() {
		
		require_once ( YITH_YWQE_LIB_DIR . 'class.yith-quick-export-dropbox.php' );
		
		$dropbox = YITH_Quick_Export_DropBox::get_instance();
		$dropbox->initialize( YITH_YWQE_DOCUMENT_SAVE_DIR );
		
		$access_token = get_option( 'ywqe_dropbox_access_token', '' );
		$dropbox->dropbox_accesstoken = $access_token;
		
		return $dropbox;
	}
}

?>