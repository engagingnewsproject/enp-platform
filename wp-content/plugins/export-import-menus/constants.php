<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! defined( 'DSPMENUS_VERSION' ) )
define( 'DSPMENUS_VERSION', '1.0.0' );

if( ! defined( 'DSPMENUS_URL' ) )
	define( 'DSPMENUS_URL', plugin_dir_url( __FILE__ ) );

if( ! defined( 'DSPMENUS_DIR' ) )
	define( 'DSPMENUS_DIR', plugin_dir_path( __FILE__ ) );
	
if( ! defined( 'DSPMENUS_EXPORTERR1' ) )
	define( 'DSPMENUS_EXPORTERR1' , 'Please select menu.');
	
if( ! defined( 'DSPMENUS_UPLOADMSG' ) )
	define( 'DSPMENUS_UPLOADMSG' , 'Error');	
	
if( ! defined( 'DSPMENUS_UPLOADMSG1' ) )
	define( 'DSPMENUS_UPLOADMSG1' , 'File Uploaded Sucessfully, Importing Navigations.');
 
if( ! defined( 'DSPMENUS_UPLOADMSG2' ) )
	define( 'DSPMENUS_UPLOADMSG2' , 'Please select a file to upload.');
 
if( ! defined( 'DSPMENUS_UPLOADMSG3' ) )
	define( 'DSPMENUS_UPLOADMSG3' , 'You dont have permission to make folder.');

if( ! defined( 'DSPMENUS_UPLOADMSG4' ) )
	define( 'DSPMENUS_UPLOADMSG4' , 'Please Enter Menu Name.');
	
if( ! defined( 'DSPMENUS_UPLOADMSG5' ) )
	define( 'DSPMENUS_UPLOADMSG5' , 'You do not have permission to upload file.');
	
if( ! defined( 'DSPMENUS_UPLOADMSG6' ) )
	define( 'DSPMENUS_UPLOADMSG6' , 'Uploaded file is not a JSON file.');	
	
if( ! defined( 'DSPMENUS_IMPORTMSG1' ) )
	define( 'DSPMENUS_IMPORTMSG1' , 'Menu name is already exist, Please enter a unique name.');
	
if( ! defined( 'DSPMENUS_IMPORTMSG2' ) )
	define( 'DSPMENUS_IMPORTMSG2' , 'Uploaded file is either Empty or not a valid JSON file.');
	
if( ! defined( 'DSPMENUS_IMPORTMSG3' ) )
	define( 'DSPMENUS_IMPORTMSG3' , 'Unable to import menu item.');
	
if( ! defined( 'DSPMENUS_IMPORTMSG4' ) )
	define( 'DSPMENUS_IMPORTMSG4' , ' menu item has been successfully imported.');
	
if( ! defined( 'DSPMENUS_IMPORTMSG5' ) )
	define( 'DSPMENUS_IMPORTMSG5' , 'All menu items has been successfully imported.');
   
if( ! defined( 'DSPMENUS_IMPORTMSG6' ) )
	define( 'DSPMENUS_IMPORTMSG6' , 'Required Parameters are missing.');
   
if( ! defined( 'DSPMENUS_IMPORTMSG7' ) )
	define( 'DSPMENUS_IMPORTMSG7' , 'Nonce verification failed !');