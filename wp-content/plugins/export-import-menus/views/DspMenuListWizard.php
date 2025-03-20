<?php if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap">
<h1 class="wp-heading-inline">Export/Import Menus</h1>
<h2 class="nav-tab-wrapper wp-clearfix tablinks">
   <a onclick="dsp_menu_tabs(event, 'dsp_export')" href="javascript:void(0)" class="tablinks nav-tab nav-tab-active"><?php esc_html_e( 'Export Menus' ); ?></a>
   <a onclick="dsp_menu_tabs(event, 'dsp_import')" href="javascript:void(0)" class="tablinks nav-tab"><?php esc_html_e( 'Import Menus' ); ?></a>
</h2>
<?php
if(isset($this->exportviewdata) && is_array($this->exportviewdata) && !empty($this->exportviewdata))
{ ?>
	<div id="dsp_export" class="tabcontent manage-menus">
 		<form method="post" class="admin_form" action="<?php echo admin_url( 'admin.php?page=dsp_export_import_menus' ); ?>">
			
			<input type="hidden" name="dspmenustask" value="dspExportMenus">
			<div class="form-group">
						<div class="form-label">
							<label>Select a menu to export</label>
						</div>
						<div class="form-input">
									<select name="menu" id="select-menu-to-export">
												<option value="">Select Menu</option>
																				<?php
																							foreach ( $this->exportviewdata as $navmenu ){
																							  	 if(isset($navmenu->term_id) && isset($navmenu->name))
																								   echo '<option value="'.$navmenu->term_id.'">'.$navmenu->name.'</option>';
																							}
																				?>
											</select>
						</div>
		</div>
			<div class="form-group">
						<div class="form-label">
						<span class="submit-btn"><input type="submit" id="dsp-export-menus" class="button button-primary button-large" value="Export"></span>
						<img class="dsp-export-loader hidden" src="<?php echo admin_url();?>/images/wpspin_light.gif" />
						<div class="get-export-response"></div>
						</div>
		</div>
			
		</form>
	</div>
<?php   
}
else
{
   echo '<div id="dsp_export" class="tabcontent"><div class="dsp-export-error"><h3>No menu items were found!</h3></div></div>';
} 
?>
	<div id="dsp_import" class="tabcontent hidden manage-menus">
			<div id="uploadFormLayer">
						<form id="uploadForm" class="admin_form" action="<?php echo admin_url( 'admin.php?page=dsp_export_import_menus' ); ?>" method="post" enctype="multipart/form-data">
								    <div class="form-group">
															<div class="form-label">
																<label>Upload JSON File</label>
															</div>
															<div class="form-input">
																		<input name="menusfile" type="file" class="inputFile" accept=".json"/><br/>
																		<input type="hidden" class="dspimportmenustask" name="dspmenustask" value="dspImportMenus">
																		<input type="hidden" name="dspimportfilename" class="dsp-import-filename" value="">
																		<input type="hidden" name="curntmenupos" class="dsp-curntmenupos" value="0">
															</div>
											</div>
												<div class="form-group">
															<div class="form-label">
																<label>Menu Name</label>
															</div>
															<div class="form-input">
																		<input type="text" name="dspmenuname" class="dsp-menuname menu-name regular-text menu-item-textbox">
																	
															</div>
											</div>
												<div class="form-group">
															<div class="form-label">
																	<input type="submit" disabled="disabled" id="dsp-import-menus" class="button button-primary button-large" value="Import Menus">
																		<img class="dsp-import-loader hidden" src="<?php echo admin_url();?>/images/wpspin_light.gif" />
															</div>
											</div>
						</form>
				<div class="dsps-import-notice" id="dsp-import-response"></div>
    </div>
	</div>	
</div>