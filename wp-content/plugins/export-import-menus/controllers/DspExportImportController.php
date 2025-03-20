<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists("DspExportImportController")):
class DspExportImportController
{
	var $viewdata = null;
	var $exportcontroller = null;
	var $importcontroller = null;
	var $modelObj = null;
	var $exportviewdata = null;
	public function __construct($task=null)
	{
      $this->modelObj=new DspExportImportModel();	
		if(!$task)
		{
			$this->callErrorView();
		}
		else{
			$this->$task($_REQUEST);
		}

	}
	
	/**
	*  Handle the Error 
	*/
	public function callErrorView()
	{
		include(DSPMENUS_DIR."views/DspError.php");
	}
	
	/**
	*  List all the Menus which exists
	*/
	public function listMenus($requested_vars=null)
	{		
		$this->exportviewdata	= $this->modelObj->getListMenus($requested_vars);
		require_once DSPMENUS_DIR . 'views/DspMenuListWizard.php';
	}
	
	/**
	*  Controller for Export of Menu
	*/
	public function dspExportMenus($requested_vars=null)
	{
		$this->exportcontroller = $this->modelObj->generateMenusJson($requested_vars);
		if(isset($requested_vars['menu']) && $requested_vars['menu']=='')
		{
			$this->listMenus();
		}	
	}

	/**
	*  Controller for Import of Menu
	*/	
	public function dspImportMenus($requested_vars=null)
	{
		if(is_array($_POST) && is_array($_FILES))
		$requested_vars = array_merge($_POST,$_FILES);
		$this->importcontroller = $this->modelObj->uploadMenusJson($requested_vars);
	}
	
}//end of class
endif;
?>