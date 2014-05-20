addLoadEvent(jscolor.init);


jQuery(document).ready(function() {
	jQuery('.tag_name_select').change(function() {
			var str="";
			$(".tag_name_select option:selected").each(function () {
				str += $(this).text();
			});
			
			if (str=="Custom") {
				$('.tag_name_input').css({ 'display': 'block'});
				$('.tag_name_select').attr('name','ot');
				$('.tag_name_input').attr('name',$('.tag_name_input').attr('id'));
			} else {
				$('.tag_name_input').css({ 'display': 'none'});
				$('.tag_name_select').attr('name',$('.tag_name_select').attr('id'));
			}

	});
});

	jQuery(function() {
		jQuery( "#tabs" ).tabs({
		    activate: function (e, ui) { 
		        jQuery.cookie('selected-tab', ui.newTab.index(), { path: '/', expires: 1 }); 
		    }, 
		    active: jQuery.cookie('selected-tab') 
		});
	});

	jQuery(function() {
		jQuery( ".sub_tabs" ).tabs({
		    activate: function (e, ui) { 
		        jQuery.cookie('selected-sub-tab', ui.newTab.index(), { path: '/', expires: 1 }); 
		    }, 
		    active: jQuery.cookie('selected-sub-tab') 
		});
	});
	




	jQuery(document).ready(function($){
		jQuery(".edit-sidebar").click(function() {
			var id = $(this).attr("id").substring(5);
			var $title = $("#text-"+id).attr("alt");
					
			jQuery("#name-"+id).remove(":contains(\'Sidebar Name:\')");
			jQuery("#edit-"+id).attr("style","display:none;");
			jQuery("#save-"+id).attr("style","display:inline;");

			jQuery("#text-"+id).append("<p id=\"name-"+id+"\"><b>Sidebar Name:</b></p><span class=\"input-text-1\"><input id=\"input-"+id+"\" name=\"sidebar-name\" value=\""+$title+"\" /></span>");

						
			jQuery("#input-"+id).keydown(function (e){
				if(e.keyCode == 13){
					
					var $title = $("#input-"+id).val();
					var $old_name = $("#text-"+id).attr("alt");
								
					save_sidebar(id,$title,$old_name);
								
				}
			})
						
		});
	});			
				
	jQuery(document).ready(function($){
		jQuery(".save-sidebar").click(function() {
			var id = $(this).attr("id").substring(5);
			var $title = $("#input-"+id).val();
			var $old_name = $("#text-"+id).attr("alt");
						
			save_sidebar(id,$title,$old_name);
		});
	});
	
	
	function makeid() {
		var text = "";
		var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

		for( var i=0; i < 5; i++ )
			text += possible.charAt(Math.floor(Math.random() * possible.length));

		return text;
	}
	
 /* -------------------------------------------------------------------------*
 * 								HOMEPAGE DRAG&DROP							*
 * -------------------------------------------------------------------------*/
	
	jQuery(document).ready(function($) {
		var adminUrl = scripts.adminUrl;
		var uploadHandler = scripts.uploadHandler;
		var themeUploadUrl = scripts.themeUploadUrl;
		
		
		jQuery("ul.inactive-blocks li.component").draggable({
			helper: function() {
				return jQuery(this).clone().appendTo("ul.inactive-blocks").css({
					"zIndex" : "5",
					"width" : "250px",
					"list-style-type": "none"
				});
			},
			cursor: "move",
			containment: "document"
		});
			
			
		jQuery("#active-homepage-blocks li").each(function () {
			if(jQuery(this).find("#unique-block").length != 0) {
				var blockId = jQuery(this).attr("rel");
				jQuery("#available-homepage-blocks li").each(function () {
					if( jQuery(this).attr("rel") == blockId) {
						jQuery(this).css("display", "none");
					}
				});
			
			}
		});
		
		jQuery(".ui-layout-center").droppable({
			activeClass: "ui-state-hover",
			accept: ".component",
			drop: function(event, ui) {
			
				if (!ui.draggable.hasClass("dropped")) {
					var randomID = makeid();
					jQuery(this).append(jQuery(ui.draggable).clone().addClass("dropped").toggleClass("inactive-block active-block").attr("id", "recordsArray_"+randomID));
								
					var postId = jQuery("#post_ID").val();
					var inputType = [];
					var count = new Array();
					var type = new Array();
					var i = 0;
					
					jQuery("li.active-block").each(function() {
						inputType.push(jQuery(this).find("div[rel]").map(function() {
							return this.getAttribute("rel");					
						}).get());
						count.push(inputType[i].length);
						var typeVal = jQuery(this).attr("rel");
						type.push(typeVal);
						i++;           
					});     
								
							
					var order = jQuery(this).sortable("serialize") + "&inputType="+inputType+"&count="+count+"&type="+type+"&action=update_homepage&post_id="+postId;
					jQuery.post(adminUrl, order, function(theResponse){
						var text = theResponse.slice(0,-1);
						//alert(text);			
					});
					
					jQuery("#recordsArray_"+randomID+" div.blocks-content a.move").html("Edit").attr({ id: "edit_"+randomID, class: "button edit"});
					jQuery("<a href=\"javascript:{}\" id=\"delete_"+randomID+"\" class=\"button delete del\">Delete</a>").insertAfter(jQuery("#edit_"+randomID));	
									
					jQuery("#recordsArray_"+randomID+" input,#recordsArray_"+randomID+" textarea,#recordsArray_"+randomID+" select,#recordsArray_"+randomID+" div.uploader span").each(function () {
						///add random id for the block
						if(jQuery(this).hasClass("btn-upload")) {
							var originalName = jQuery(this).attr("id");
							originalName = originalName.replace("_button", "");
							jQuery(this).attr("id", originalName+"_"+randomID+"_button");
						} else {
							var originalName = jQuery(this).attr("name");
							//check unlimited inputs
							var myregexp = /[0-9]/;
							if((originalName.substr(-7,1) == "n" && myregexp.exec(originalName.substr(-6,1))!=null)){
								var magicWordLeght = originalName.length;
								var wordBase = originalName.substr(0,(magicWordLeght-7));
								var wordEnd = originalName.substr(-7,7);	
								jQuery(this).attr("name", wordBase+randomID+"_"+wordEnd);   
							} else {
								jQuery(this).attr("name", originalName+"_"+randomID);   	
							}

							jQuery(this).parent("#scroller").attr("class", originalName+"_"+randomID);   
							jQuery(this).parent("#scroller").children("div.slider-range-min").attr("id", "slider-range-min-"+originalName+"_"+randomID);  

							jQuery( "."+originalName+"_"+randomID+" > #slider-range-min-"+originalName+"_"+randomID ).slider({
								range: "min",
								value: 10,
								min: 1,
								max: 50,
								slide: function( event, ui ) {
									jQuery(this).prev("input").val(ui.value);
								}
							});
							jQuery(this).prev("input").val(jQuery(this).attr( "value" ) );	
					
						}
					});
					
					jQuery("#active-homepage-blocks li").each(function () {
						if(jQuery(this).find("#unique-block").length != 0) {
							var blockId = jQuery(this).attr("rel");
							jQuery("#available-homepage-blocks li").each(function () {
								if( jQuery(this).attr("rel") == blockId) {
									jQuery(this).css("display", "none");
								}
							});
						
						}
					});
					
					addLoadEvent(jscolor.init);

					jQuery("#recordsArray_"+randomID+" .slider-range-min").each(function() {
						jQuery(this).children(".ui-slider-range-min:first").remove();
					});
				}
			}
		});
		
		jQuery(".dropped div a.del").live("click", function(){
			jQuery(this).closest("li.dropped").remove();
			var postId = jQuery("#post_ID").val();		
			var inputType = [];
			var count = new Array();
			var type = new Array();
			var i = 0;
			jQuery("li.active-block").each(function() {
				inputType.push(jQuery(this).find("div[rel]").map(function() {
					return this.getAttribute("rel");					
				}).get());
				count.push(inputType[i].length);
				var typeVal = jQuery(this).attr("rel");
				type.push(typeVal);
				i++;           
			});     
							
						
			var order = jQuery(".postbox").find(".ui-droppable").sortable("serialize") + "&inputType="+inputType+"&count="+count+"&type="+type+"&action=update_homepage&post_id="+postId;
			jQuery.post(adminUrl, order, function(theResponse){
				var text = theResponse.slice(0,-1);
				//alert(order);
			});
			
			var blockId = jQuery(this).parent().parent().attr("rel");
			jQuery("#available-homepage-blocks li").each(function () {
				if( jQuery(this).attr("rel") == blockId) {
					jQuery(this).css("display", "block");
				}
			});
			
		});
		
		jQuery("ul.ui-layout-center").sortable({ opacity: 0.6, cursor: "move", update: function() {
			var postId = jQuery("#post_ID").val();
			var inputType = [];
			var count = new Array();
			var type = new Array();
			var i = 0;
			jQuery("li.active-block").each(function() {
				inputType.push(jQuery(this).find("div[rel]").map(function() {
					return this.getAttribute("rel");					
				}).get());
				count.push(inputType[i].length);
				var typeVal = jQuery(this).attr("rel");
				type.push(typeVal);
				i++;           
			});     
								
							
			var order = jQuery(this).sortable("serialize") + "&inputType="+inputType+"&count="+count+"&type="+type+"&action=update_homepage&post_id="+postId;
			jQuery.post(adminUrl, order, function(theResponse){
				var text = theResponse.slice(0,-1);
				//alert(theResponse);
			});
		}
		});
	});
					
	

		
	jQuery(document).ready(function($){
		jQuery(".dropped div a.button").live("click", function(){
			jQuery(this).closest("li.dropped div.blocks-content").addClass("edit-wrapper");
			jQuery(this).addClass("close-div");
			jQuery(this).parent().children("div").css("display", "block");
			

		});	
			
		jQuery(".dropped div a.close-div").live("click", function(){
			jQuery(this).closest("li.dropped div.blocks-content").removeClass("edit-wrapper");
			jQuery(this).removeClass("close-div");
			jQuery(this).parent().children("div").css("display", "none");
		});		

	});
	
 /* -------------------------------------------------------------------------*
 * 					SAVE GET_OPTION() IN HOMEPAGE DRAG&DROP					*
 * -------------------------------------------------------------------------*/	
 jQuery(document).ready(function() {  

 	jQuery("#df-submit-home").live("click", function(){
 		jQuery("#df-submit-home").attr("value","Saving...");
 		var adminUrl = scripts.adminUrl;
 		var postId = jQuery("#post_ID").val();

		var order = jQuery("ul#active-homepage-blocks *").serialize()+"&action=df_save_options";
		jQuery.post(adminUrl,order, function(theResponse){

			//alert(theResponse);
			//alert(order);
		}).done(function() { 
			jQuery("#df-submit-home").attr("value","Saved");
			setTimeout(function(){
        		jQuery("#df-submit-home").attr("value","Save");
        	}, 2000);

		})

		
 		return false;
 	});
 });

/* -------------------------------------------------------------------------*
 * 							SAVE/EDIT/DELETE SIDEBAR						*
 * -------------------------------------------------------------------------*/

	OT_sidebar_edit();	
	
	function save_sidebar(new_name,old_name,old_name_id,new_name_id) {
		var adminUrl = scripts.adminUrl;
		var themeName = scripts.themeName;
		new_name = new_name.replace(/\s+/g, '-').toLowerCase();
		jQuery.ajax({
			url: adminUrl,
			type:"POST",
			data:"action=edit_sidebar&sidebar_name="+new_name+"&old_name="+old_name,
			success:function(results) {
				jQuery("#save-"+old_name_id).parent(".edit-wrapper").removeClass("edit-wrapper").addClass("blocks-content");
				jQuery("#recordsArray_"+old_name_id).html("<div class=\"blocks-content clearfix\" style=\"text-align: left;\">Sidebar name: <b>"+new_name+"</b> <a href=\"javascript:{}\" class=\"button edit sidebar-edit\" id=\"edit-"+new_name_id+"\" rel=\""+new_name+"\">Edit</a><a href=\"javascript:{}\" class=\"button delete sidebar-delete\" id=\"delete-"+new_name_id+"\">Delete</a></div>");
				jQuery("#recordsArray_"+old_name_id).attr("id", "recordsArray_"+new_name_id);
				var text = results.slice(0,-1);
				jQuery("#"+themeName+"_sidebar_names").val(text);

				jQuery(document).ready(function($){
					var adminUrl = scripts.adminUrl;
					var themeName = scripts.themeName;
					jQuery(".sidebar-delete").click(function() {

						var id = $(this).attr("id");
						id = id.replace('delete-', '');
						jQuery.ajax({
							url:adminUrl,
							type:"POST",
							data:"action=delete_sidebar&sidebar_name=" + id,
							success:function(results) {
								//alert(results);
								//$("#contentRight").html(results);
								var text = results.slice(0,-1);
								jQuery("#"+themeName+"_sidebar_names").val(text);
								jQuery("#recordsArray_"+id).remove();
							}
						});
					});
				});
				
				OT_sidebar_edit();

			}
		});
		
	}



	
function OT_sidebar_edit() {
	jQuery(".sidebar-edit").click(function() {
		var old_name_id = jQuery(this).attr("id").replace("edit-", "");
		var old_name = jQuery(this).attr("rel");
		jQuery(this).parent(".blocks-content").removeClass("blocks-content").addClass("edit-wrapper");
		jQuery(this).parent(".edit-wrapper").html("Sidebar name: <input type=\"text\" id=\"input-"+old_name_id+"\" name=\"sidebar-name\" value=\""+old_name+"\" class=\"text\" /><a href=\"javascript:void(0)\" class=\"button edit\" id=\"save-"+old_name_id+"\">Save</a><a href=\"javascript:void(0)\" class=\"button delete sidebar-delete\" id=\"delete-"+old_name_id+"\">Delete</a>")

		jQuery("#input-"+old_name_id).keydown(function (e){
		if(e.keyCode == 13){
			var new_name = jQuery("#input-"+old_name_id).val();
			new_name = new_name.replace(/\s+/g, '-').toLowerCase();
			var new_name_id=new_name.replace(/ /g,"");
			new_name_id=new_name_id.toLowerCase();
			
			
				save_sidebar(new_name,old_name,old_name_id,new_name_id);
			}
		});
		jQuery("#save-"+old_name_id).click(function() {
			var new_name = jQuery("#input-"+old_name_id).val();
			new_name = new_name.replace(/\s+/g, '-').toLowerCase();
			var new_name_id=new_name.replace(/ /g,"");
			new_name_id=new_name_id.toLowerCase();
			save_sidebar(new_name,old_name,old_name_id,new_name_id);
		});	

			var adminUrl = scripts.adminUrl;
			var themeName = scripts.themeName;
			jQuery(".sidebar-delete").click(function() {

				var id = $(this).attr("id");
				id = id.replace('delete-', '');
				jQuery.ajax({
					url:adminUrl,
					type:"POST",
					data:"action=delete_sidebar&sidebar_name=" + id,
					success:function(results) {
						//alert(results);
						//$("#contentRight").html(results);
						var text = results.slice(0,-1);
						jQuery("#"+themeName+"_sidebar_names").val(text);
						jQuery("#recordsArray_"+id).remove();
					}
				});
			});		

	});
}	
	
	jQuery(document).ready(function($){
		var adminUrl = scripts.adminUrl;
		var themeName = scripts.themeName;
		jQuery(function() {
			jQuery("ul#sidebar_order").sortable({ opacity: 0.6, cursor: 'move', update: function() {
				var order = $(this).sortable("serialize") + '&action=update_sidebar';
				jQuery.post(adminUrl, order, function(theResponse){
					//$("#contentRight").html(theResponse);
					var text = theResponse.slice(0,-1);
					jQuery("#"+themeName+"_sidebar_names").val(text);
				});
			}
			});
		});
	});
				
	jQuery(document).ready(function($){
		var adminUrl = scripts.adminUrl;
		var themeName = scripts.themeName;
		jQuery(".sidebar-delete").click(function() {
					
			var id = $(this).attr("id");
			id = id.replace('delete-', '');
			jQuery.ajax({
				url:adminUrl,
				type:"POST",
				data:"action=delete_sidebar&sidebar_name=" + id,
				success:function(results) {
					//alert(results);
					//$("#contentRight").html(results);
					var text = results.slice(0,-1);
					jQuery("#"+themeName+"_sidebar_names").val(text);
					jQuery("#recordsArray_"+id).remove();
				}
			});
		});
	});	
	
/* -------------------------------------------------------------------------*
 * 									SLIDE ORDER								*
 * -------------------------------------------------------------------------*/
	jQuery(document).ready(function($){
	var adminUrl = scripts.adminUrl;
		jQuery(function() {
			jQuery("ul.slider-sequence").sortable({ opacity: 0.6, cursor: 'move', update: function() {
				var order = $(this).sortable("serialize") + '&action=update_slider';
				jQuery.post(adminUrl, order, function(theResponse){
					//alert(theResponse);
				});
			}
			});
		});
	});	
		
		
/* -------------------------------------------------------------------------*
 * 								CUSTOM SELECT								*
 * -------------------------------------------------------------------------*/
	jQuery(document).ready(function(){
	
	
		jQuery('.otpost-type').each(function(index) {
			jQuery(this).parent().find(".ppid").css("display", "none");
			jQuery(this).parent().find(".aid").css("display", "none");
			jQuery(this).parent().find(".fid").css("display", "none");
			
			
			
			switch(jQuery(this).find("select").first().val()) {
				case "post":
				jQuery(this).parent().find(".ppid").css("display", "block");
				break;
				case "accommodation":
				jQuery(this).parent().find(".aid").css("display", "block");
				break;
				case "features":
				jQuery(this).parent().find(".fid").css("display", "block");
				break;
			}
				
		});

		jQuery(".otpost-type select").live("change",function() {
		
			var selectField = jQuery(this).children("option:selected").text();
			jQuery(this).parent().parent().parent().parent().find(".ppid").css("display", "none");
			jQuery(this).parent().parent().parent().parent().find(".aid").css("display", "none");
			jQuery(this).parent().parent().parent().parent().find(".fid").css("display", "none");
			
			

			switch(selectField) {
				case "Post":
				jQuery(this).parent().parent().parent().parent().find(".ppid").css("display", "block");
				break;
				case "Features":
				jQuery(this).parent().parent().parent().parent().find(".fid").css("display", "block");
				break;
				case "Accommodation":
				jQuery(this).parent().parent().parent().parent().find(".aid").css("display", "block");
				break;
			}
		});
		
		
	});	
	
	jQuery(".addtabbox").live("click",function(){

		var $curr = jQuery(this).prev();

		$curr.clone().insertAfter($curr);

		$curr = jQuery(this).prev();

		$curr.find('input').val("");			

		var name = $curr.find('input').attr("name");

		var n = String(name.match(/_n[0-9]+_/g));			

		var nr = n.match(/n[0-9]/g);		

		var currentNr = parseInt(jQuery(this).attr("rel"))+parseInt(1);

		

		jQuery(this).attr("rel", currentNr);

		jQuery(this).prev().children(".delete-tab-box").attr("rel", currentNr-1);

		jQuery(this).prev().children(".delete-tab-box").css("display","block");

		$curr.find('input').attr("name",name.replace(nr,"n"+currentNr))



	}); 

	

	jQuery(".delete-tab-box").live("click",function(){

		jQuery(this).parent().remove();

	});

	

	jQuery(document).ready(function($){

		jQuery(".delete-tab-box").each(function() {

       		if(jQuery(this).attr("rel") < 1 ) {

				jQuery(this).css("display","none");

			}

		});   

	});

