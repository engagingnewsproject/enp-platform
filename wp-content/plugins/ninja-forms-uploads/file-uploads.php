<?php
/*
Plugin Name: Ninja Forms - File Uploads
Plugin URI: https://ninjaforms.com/extensions/file-uploads/
Description: File Uploads add-on for Ninja Forms.
Version: 3.3.21
Author: Saturday Drive
Author URI: http://ninjaforms.com
Version Description: versionTagMessage
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NF_FU_BASE_DIR', dirname(__FILE__) );

/**
 * The main function responsible for returning the one true instance to functions everywhere.
 */
if( ! function_exists( 'NF_File_Uploads' ) ) {
    function NF_File_Uploads()
    {
        // Load our main plugin class
        require_once dirname(__FILE__) . '/includes/file-uploads.php';
        $version = '3.3.21';

        return NF_FU_File_Uploads::instance(__FILE__, $version);
    }
}

/**
 * Load an autoloader from vendor subdirectory
 *
 * This function can be copied and reused in other plugins using composer's
 * PSR-4 specification because it is namespaced within this file to avoid
 * collision.
 *
 * @return boolean
 */
function nFFileUploads_ComposerAutoloader(): bool
{
    $autoloaderDev = dirname(__FILE__) . '/vendor/autoload.php';
    $autoloaderDist = dirname(__FILE__) . '/vendor-dist/autoload.php';

    $amazonFunctions = dirname(__FILE__).'/lib/Aws/functions.php';
    include_once $amazonFunctions;

    if (file_exists($autoloaderDev)) {
        include_once $autoloaderDev;
        $return = true;
    }elseif (file_exists($autoloaderDist)) {
        include_once $autoloaderDist;
        $return = true;
    } else {
        $return = false;
    }

    return $return;
}

nFFileUploads_ComposerAutoloader();

NF_File_Uploads();
