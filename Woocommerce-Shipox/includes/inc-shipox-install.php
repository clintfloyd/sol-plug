<?php
/**
 * Created by Shipox.
 * User: Shipox
 * Date: 11/8/2017
 * Time: 2:41 PM
 */

class Shipox_Install
{

    /**
     * API_HELPER constructor.
     */
    public function __construct()
    {
    }

    /**
     * Update Wing version to current.
     */
    private static function update_wing_version() {
        delete_option( 'shipox_version' );
        add_option( 'shipox_version', shipox()->version );
    }

    /**
     * Install WC.
     */
    public static function install() {
        self::create_files();
        self::update_wing_version();
    }

    /**
     * Create files/directories.
     */
    private static function create_files() {
        // Bypass if filesystem is read-only and/or non-standard upload system is used
        if ( apply_filters( 'woocommerce_install_skip_create_files', false ) ) {
            return;
        }

        $files = array(
            array(
                'base' 		=> SHIPOX_LOGS,
                'file' 		=> '.htaccess',
                'content' 	=> 'deny from all',
            ),
            array(
                'base' 		=> SHIPOX_LOGS,
                'file' 		=> 'index.html',
                'content' 	=> '',
            ),
        );


        foreach ( $files as $file ) {
            if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
                if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
                    fwrite( $file_handle, $file['content'] );
                    fclose( $file_handle );
                }
            }
        }
    }
}