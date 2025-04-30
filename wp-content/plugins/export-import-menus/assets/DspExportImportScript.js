//Export and import tabs
function dsp_menu_tabs(evt, tabname) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" nav-tab-active", "");
    }
    document.getElementById(tabname).style.display = "block";
    evt.currentTarget.className += " nav-tab-active";
}

//Import nav item AJAX
function DspImportMenus(params)
{
    params.append('security', dspexportmenus.nonce_verify);
    jQuery(".dsp-menuname").prop('disabled', true);
    jQuery(".dsp-import-loader").show();
    jQuery.ajax({			 	
        url: dspexportmenus.ajaxurl,
        dataType: 'json',
        type: "POST",
        data: params,
        contentType: false,
        cache: false,
        processData: false,
        success: function(data)
         {
            if (data === undefined ) {
                jQuery("#dsp-import-response").html("<p class='alert-error'>Unable to Process.</p>");
                jQuery(".dsp-import-loader").hide();	
            }else{
                var err ='';
                if(data.status === 0)
                {
                    err = 'alert-error';
                }else
                {
                    err = 'alert-success';
                }
                jQuery("#dsp-import-response").html("<p class="+err+">"+data.response+"</p>");
                if(data.isContinue !== undefined && data.isContinue == 1)
                {

                    var fdata = new FormData();
                    var menuName = jQuery(".dsp-menuname").val();
                    fdata.append('action','dspImportMenus');
                    fdata.append('dspmenuname',menuName);
                    fdata.append('dspmenustask','dspImportMenus');
                    fdata.append('nextMenuPos', data.nextMenuPos);
                    fdata.append('fileurl', data.fileurl);
                    fdata.append('oldIds', JSON.stringify(data.oldIds));
                    fdata.append('newIds', JSON.stringify(data.newIds));
                    fdata.append('menuId', data.menuId);
                    fdata.append('curntmenupos', data.nextMenuPos);
                    fdata.append('isFileTypeChecked', data.isFileTypeChecked);
                    DspImportMenus(fdata); 
                }
                else
                {
                  jQuery("#dsp-import-menus").prop('disabled', false);
                  jQuery(".dsp-menuname").prop('disabled', false);
                  jQuery(".dsp-import-loader").hide();
                }
            }
            
         },
        error: function(jqXHR, textStatus, errorThrown) 
        {
            jQuery("#dsp-import-response").html("<p class='alert-error'> Error in processing!</p>");
            console.log("error. textStatus: %s  errorThrown: %s jqXHR: %s", textStatus, errorThrown, JSON.stringify(jqXHR));
            jQuery(".dsp-import-loader").hide();
        } 	        
    });
}

jQuery(document).ready(function(){
    jQuery('.dsp-menuname').on('keyup', function() {
            var menuName = jQuery(".dsp-menuname").val().replace(/ /g,'');
            if(menuName === '')
            {        
                jQuery("#dsp-import-menus").prop("disabled",true);
            }
            else
            {
                jQuery("#dsp-import-menus").prop("disabled",false);
            }
    });
    jQuery("#uploadForm").on('submit',(function(e) {
        e.preventDefault();
        var fdata = new FormData(this);
        fdata.append('action', 'dspImportMenus');       
        DspImportMenus(fdata);
    }));
});