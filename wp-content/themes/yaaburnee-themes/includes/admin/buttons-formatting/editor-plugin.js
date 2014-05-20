/**
 * This file contains all the main JavaScript functionality needed for the editor formatting buttons.
 * 
 * @author differentthemes
 * http://differentthemes.com
 */

/**
 * Define all the formatting buttons with the HTML code they set.
 */
var differentthemesButtons=[
		{
			id:'differentthemesbutton',
			image:'button.png',
			title:'Button',
			allowSelection:false,
			fields:[{id:'text', name:'Text'},{id:'href', name:'Link URL'},{id:'tooltip', name:'Tooltip, if needed:'},{id:'color', name:'Color', colorpalette:true},{id:'style', name:'Button Style', values:['Normal', 'Pill']},{id:'target', name:'Target', values:['Self', 'Blank']},{id:'icon', name:'Icon', values:df_icons()},{id:'size', name:'Size', values:['Default', 'Small', 'Large']}],
			generateHtml:function(obj){
			
				return '[button link="'+obj.href+'" target="'+obj.target.toLowerCase()+'" color="'+obj.color.toLowerCase()+'" icon="'+obj.icon.toLowerCase()+'" style="'+obj.style.toLowerCase()+'" size="'+obj.size.toLowerCase()+'" tooltip="'+obj.tooltip.toLowerCase()+'"]'+obj.text+'[/button]';

			}
		},	
		{
			id:'differentthemesteam',
			image:'team.png',
			title:'Team Box',
			allowSelection:false,
			fields:[{id:'title', name:'Title: '},{id:'subtitle', name:'Subtitle: '},{id:'img', name:'Photo Url: ',upload:true}, {id:'text', name:'Text: ', textarea:true},{id:'social-1', name:'Social', values:['AddThis', 'Behance', 'Blogger', 'Delicious', 'Deviantart', 'Digg', 'Dopplr', 'Dribbble', 'Evernote', 'Facebook', 'Flickr', 'Forrst', 'Github', 'Google', 'Grooveshark', 'Instagram', 'Lastfm', 'Linkedin', 'Mail', 'Myspace', 'Paypal', 'Picasa', 'Pinterest', 'Posterous', 'Reddit', 'Rss', 'Sharethis', 'Skype', 'Soundcloud', 'Spotify', 'Stumbleupon', 'Tumblr', 'Viddler', 'Vimeo', 'Virb', 'Windows', 'Wordpress', 'Youtube', 'Twitter']},{id:'url-1', name:'URL: ', team:true}],
			generateHtml:function(obj){
				var x = jQuery('#df-team').val();  
				var output = '[about';
				output+= ' img="'+obj.img+'"';
				output+= ' subtitle="'+obj.subtitle+'"';
				output+= ' title="'+obj.title+'"';
				for(e = 1; e <= x; e++) {
					output+= ' '+jQuery('#differentthemes-shortcode-social-'+e).val().toLowerCase()+'="'+jQuery('#differentthemes-shortcode-url-'+e).val()+'" ';
				}
				output+= ']';
				output+= obj.text;
				output+="[/about]";

				return output;
			}
		},
		{
			id:'differentthemesgallery',
			image:'btn-gallery.png',
			title:'Insert Gallery Preview',
			allowSelection:false,
			fields:[{id:'links', name:'Gallery Link' }],
			generateHtml:function(obj){
				return '[df-gallery url="'+obj.links+'"]';
			}
		},
		{

			id:'differentthemescolumns',
			image:'COLUMNS.png',
			title:'Columns',
			allowSelection:false,
			fields:[{id:'column', name:'Columns', values:['Half/Half', 'One Third/Two Thirds', 'Two Thirds/One Third','Fourth/Fourth/Fourth/Fourth', 'Third/Third/Third'],selesction:true}],
			generateHtml:function(obj){

				switch(obj.column) {
					case 'Half/Half':
						var content='[row][half]COLUMN CONTENT[/half][half]COLUMN CONTENT[/half][/row]';
					break;
					case 'Third/Third/Third':
						var content='[row][third]COLUMN CONTENT[/third][third]COLUMN CONTENT[/third][third]COLUMN CONTENT[/third][/row]';
					break;
					case 'One Third/Two Thirds':
						var content='[row][one-third]COLUMN CONTENT[/one-third][two-thirds]COLUMN CONTENT[/two-thirds][/row]';
					break;
					case 'Two Thirds/One Third':
						var content='[row][two-thirds]COLUMN CONTENT[/two-thirds][one-third]COLUMN CONTENT[/one-third][/row]';
					break;
					case 'Fourth/Fourth/Fourth/Fourth':
						var content='[row][four]COLUMN CONTENT[/four][four]COLUMN CONTENT[/four][four]COLUMN CONTENT[/four][four]COLUMN CONTENT[/four][/row]';
					break;

				}

				return content;

			}

		},
		{
			id:'differentthemesquote',
			image:'blockquotes.png',
			title:'Block text',
			allowSelection:false,
			fields:[{id:'blocktext', name:'Block text', textarea:true},{id:'align', name:'Position', values:['Left', 'Right']}],
			generateHtml:function(obj){
				return '[blocktext align="'+obj.align.toLowerCase()+'"]'+obj.blocktext+'[/blocktext]';
			}
		},
		{
			id:'differentthemesspacer',
			image:'spacer.png',
			title:'Spacer',
			allowSelection:false,
			fields:[{id:'style', name:'Style', values:['Spacer 1', 'Spacer 2', 'Spacer 3']}],
			generateHtml:function(obj){
				switch(obj.style) {
					case 'Spacer 1':
						return '[spacer style="1"]';
					break;
					case 'Spacer 2':
						return '[spacer style="2"]';
					break;
					case 'Spacer 3':
						return '[spacer style="3"]';
					break;
					default:
						return '[spacer style="1"]';
					break;


				}
			}
		},
		{
			id:'differentthemesvideo',
			image:'cpanel-btn-video.png',
			title:'Video',
			allowSelection:false,
			fields:[{id:'blocktext', name:'Embed Code', textarea:true}],
			generateHtml:function(obj){
				return obj.blocktext;
			}
		},
		{
			id:'differentthemesattention',
			image:'attention.png',
			title:'Information Box',
			allowSelection:false,
			fields:[{id:'information', name:'Box Text', textarea:true}],
			generateHtml:function(obj){
				return '[information]'+obj.information+'[/information]';
			}
		},
		{
			id:'differentthemesalert',
			image:'warning.png',
			title:'Alert',
			allowSelection:false,
			fields:[{id:'type', name:'Type', values:['Error', 'Success', 'Warning', 'Info']}, {id:'text', name:'Text', textarea:true}],
			generateHtml:function(obj){
				return '[alert type="'+obj.type.toLowerCase()+'"]'+obj.text+'[/alert]';
			}
		},
		{
			id:'differentthemespreformated',
			image:'preformated-text.png',
			title:'Preformated text',
			allowSelection:true,
			generateHtml:function(obj){
				return '<pre>'+obj+'</pre>';
			}

		},
		{
			id:'differentthemesmiscellaneous',
			image:'miscellaneous.png',
			title:'Miscellaneous',
			allowSelection:true,
			fields:[{id:'type', name:'Type', values:['Superscript', 'Subscript', 'Small'],selesction:true}],
			generateHtml:function(obj){
				return '[miscellaneous type="'+obj.type.toLowerCase()+'"]'+obj.selection+'[/miscellaneous]';
			}

		},
		{
			id:'differentthemesmarker',
			image:'highlights.png',
			title:'Highlights',
			allowSelection:true,
			fields:[{id:'type', name:'Type', values:['Background Color', 'Text Color']}, {id:'markercolor', name:'Color', color:"c24000", colorpalette:true,selesction:true}],
			generateHtml:function(obj){
				return '[textmarker color="'+obj.markercolor+'" type="'+obj.type.toLowerCase()+'"]'+obj.selection+'[/textmarker]';
			}

		},
		{
			id:'differentthemesheading',
			image:'headings.png',
			title:'Heading',
			allowSelection:true,
			generateHtml:function(obj){
				return '[heading style="subheader"]'+obj+'[/heading]';
			}

		},
		{
			id:'differentthemestooltip',
			image:'tooltips.png',
			title:'Tooltip',
			allowSelection:true,
			fields:[{id:'tooltip', name:'Tooltip Text:'},{id:'url', name:'Link:',selesction:true},{id:'target', name:'Button Target', values:['Self', 'Blank']}],
			generateHtml:function(obj){
				return '[tooltip text="'+obj.tooltip+'" url="'+obj.url+'"]'+obj.selection+'[/tooltip]';
			}

		},
		{
			id:'differentthemesicon',
			image:'cool-icons.png',
			title:'Icons',
			allowSelection:false,
			fields:[{id:'icon', name:'Icon', values:df_icons()},{id:'size', name:'Size', values:['1x', '2x', '3x', '4x', '5x']},{id:'color', name:'Color', color:"000000", colorpalette:true}],
			generateHtml:function(obj){
				return '[icon style="'+obj.icon+'" color="'+obj.color+'" size="'+obj.size+'"]';
			}

		},
		{
			id:'differentthemesdropcaps',
			image:'dropcaps.png',
			title:'Dropcaps',
			allowSelection:true,
			generateHtml:function(obj){
				return '[dropcaps]'+obj+'[/dropcaps]';
			}

		},
		{
			id:'differentthemestables',
			image:'table.png',
			title:'Create Table',
			allowSelection:false,
			fields:[{id:'table_row', name:'Rows Count'},{id:'table_columns', name:'Columns Count'}],
			generateHtml:function(obj){
				var $rows = obj.table_row;
				var $colomns = obj.table_columns;
				var $table = "<table class=\"table-bordered\">";
				$table += "<thead><tr>";
				for($i=1; $i<=$colomns; $i++) {

					$table += "<th>Main Header "+$i+"</th>";

				}
				$table += "</tr></thead>";
				$table += "<tbody>";

				for($i=1; $i<=$rows; $i++) {
					$table += "<tr>";
					for($ii=1; $ii<=$colomns; $ii++) {

						$table += "<td>Text "+$ii+"</td>";

					}
					$table += "</tr>";
				}

				$table += "</tbody>";
				$table += "</table>";

				return $table;

			}

		},

		{
			id:'differentthemeslist',
			image:'list.png',
			title:'Lists',
			allowSelection:false,
			fields:[{id:'type-1', name:'Icon', values:df_icons()},{id:'lists', name:'Text', lists:true}],
			generateHtml:function(obj){
				var x = jQuery('#df-lists').val();  
				var output = '[list]';
				for(e = 1; e <= x; e++) {
					var icon = jQuery('#differentthemes-shortcode-type-'+e).val().toLowerCase()
					output+= '[item icon="'+icon+'"]';
					output+= jQuery('#differentthemes-shortcode-lists-'+e).val();
					output+= '[/item]';
				}
				output+="[/list]";
				
				return output;
			}
		},
		{
			id:'differentthemessocial',
			image:'cpanel-btn-social.png',
			title:'Social Icons',
			allowSelection:false,
			fields:[{id:'type-1', name:'Type', values:['fa-rss', 'fa-github', 'fa-instagram', 'fa-tumblr', 'fa-flickr', 'fa-skype', 'fa-pinterest', 'fa-linkedin', 'fa-google-plus', 'fa-youtube-play', 'fa-dribbble', 'fa-facebook', 'fa-twitter']},{id:'social', name:'Account Url', social:true}],
			generateHtml:function(obj){
				var x = jQuery('#df-social').val();  
				var output = '[social]';
				for(e = 1; e <= x; e++) {
					var icon = jQuery('#differentthemes-shortcode-type-'+e).val().toLowerCase()
				
				
					output+= '[account icon="'+icon+'" ]';
					output+= jQuery('#differentthemes-shortcode-social-'+e).val();
					output+= '[/account]';
				}
				output+="[/social]";
				
				return output;
			}
		},		
		{
			id:'differentthemespricing',
			image:'btn-pricing.png',
			title:'Pricing Table',
			allowSelection:false,
			fields:[{id:'active', name:'Active:', values:['No', 'Yes']},{id:'title', name:'Title'},{id:'price', name:'Price'},{id:'subtitle', name:'Sub Title'},{id:'list', name:'List', unlimitedinput:true},{id:'btntext', name:'Button Text'},{id:'href', name:'Button Link'},{id:'target', name:'Button Target', values:['Self', 'Blank']}],
			generateHtml:function(obj){
				var x = jQuery('#df-list').val(); 

				var output= '[pricing active="'+obj.active+'" title="'+obj.title+'" price="'+obj.price+'" subtitle="'+obj.subtitle+'" btntext="'+obj.btntext+'" url="'+obj.href+'" target="'+obj.target.toLowerCase()+'"]';
				for(e = 1; e <= x; e++) {
					output+=jQuery('#differentthemes-shortcode-list-'+e).val()+",";
				}

				output+="[/pricing]";

				return output;

			}
		},		
		{
			id:'differentthemestabs',
			image:'tab.png',
			title:'Insert Tabs',
			allowSelection:false,
			fields:[{id:'title-1', name:'Title: '},{id:'text', name:'Text: ', tabs:true}],
			generateHtml:function(obj){
				var x = jQuery('#df-tabs').val();  
				var output = '[tabs]';
				for(e = 1; e <= x; e++) {
					output+= '[tab ';
					output+= 'title ="'+jQuery('#differentthemes-shortcode-title-'+e).val()+'"]';
					output+= jQuery('#differentthemes-shortcode-text-'+e).val();
					output+= '[/tab]';
				}
				output+="[/tabs]";
				
				return output;
			}
		},
		{
			id:'differentthemesaccordion',
			image:'btn-accordion.png',
			title:'Accordion Boxes',
			allowSelection:false,
			fields:[{id:'title-1', name:'Title: '},{id:'text', name:'Text: ', accordion:true}],
			generateHtml:function(obj){
				var x = jQuery('#df-accordion').val();  
				var output = '[accordion]';
				for(e = 1; e <= x; e++) {
					output+= '[acc ';
					output+= 'title="'+jQuery('#differentthemes-shortcode-title-'+e).val()+'"]';
					output+= jQuery('#differentthemes-shortcode-text-'+e).val();
					output+= '[/acc]';
				}
				output+="[/accordion]";

				return output;
			}
		},

		{
			id:'differentthemesclear',
			image:'cpanel-btn-break.png',
			title:'Clear',
			allowSelection:false,
			generateHtml:function(obj){
				var output = '[clear]';
				
				return output;
			}
		},

		
];

/**
 * Contains the main formatting buttons functionality.
 */
differentthemesButtonManager={
	dialog:null,
	idprefix:'differentthemes-shortcode-',
	ie:false,
	opera:false,
		
	/**
	 * Init the formatting button functionality.
	 */
	init:function(){
			
		var length=differentthemesButtons.length;
		for(var i=0; i<length; i++){
		
			var btn = differentthemesButtons[i];
			differentthemesButtonManager.loadButton(btn);
			
		}
		
		if (jQuery.browser.opera){
			differentthemesButtonManager.opera=true;
		}
		
	},
	
	/**
	 * Loads a button and sets the functionality that is executed when the button has been clicked.
	 */
	loadButton:function(btn){
		
		tinymce.create('tinymce.plugins.'+btn.id, {
	        init : function(ed, url) {
			        ed.addButton(btn.id, {
	                title : btn.title,
	                image : url+'/buttons/'+btn.image,
	                onclick : function() {
			        	
			           var selection = ed.selection.getContent();
	                   if(btn.allowSelection && selection && btn.fields){
							
	                	   //there are inputs to fill in, show a dialog to fill the required data
	                	   differentthemesButtonManager.showDialog(btn, ed);
	                   }else if(btn.allowSelection && selection){
							
	                	   //modification via selection is allowed for this button and some text has been selected
							selection = btn.generateHtml(selection);
							ed.selection.setContent(selection);
	                   }else if(btn.fields){
	                	   //there are inputs to fill in, show a dialog to fill the required data
	                	   differentthemesButtonManager.showDialog(btn, ed);
	                   }else if(btn.list){
	                	   ed.dom.remove('differentthemescaret');
		           		    ed.execCommand('mceInsertContent', false, '&nbsp;');	
	           			
	                	    //this is a list
	                	    var list, dom = ed.dom, sel = ed.selection;
	                	    
		               		// Check for existing list element
		               		list = dom.getParent(sel.getNode(), 'ul');
		               		
		               		// Switch/add list type if needed
		               		ed.execCommand('InsertUnorderedList');
		               		
		               		// Append styles to new list element
		               		list = dom.getParent(sel.getNode(), 'ul');
		               		
		               		if (list) {
		               			dom.addClass(list, btn.list);
		               		}
	                   }else{
	                	   //no data is required for this button, insert the generated HTML
	                	   ed.execCommand('mceInsertContent', true, btn.generateHtml());
	                   }
					  
					   addLoadEvent(jscolor.init);

	                }
	            });
	        }
	    });
		
	    tinymce.PluginManager.add(btn.id, tinymce.plugins[btn.id]);
	},
	
	/**
	 * Displays a dialog that contains fields for inserting the data needed for the button.
	 */
	showDialog:function(btn, ed){
		
		var html='<div>';
		var selection = ed.selection;
		var selectedvalue = ed.selection.getContent();
		
		for(var i=0, length=btn.fields.length; i<length; i++){
			var field=btn.fields[i], inputHtml='';
			if(btn.fields[i].selesction){
				//this field should be a text area
				if(selectedvalue){ 
					// unlimited input
					html+='<div class="differentthemes-shortcode-field"><label>Selected Text</label><input type="text" value="'+selectedvalue+'" id="'+differentthemesButtonManager.idprefix+"selection"+'"></div><div>';
				} 
				
			}
			if(btn.fields[i].colorpalette){
				//this field should be a text area
				inputHtml+='<input type="text" class="color" value="'+btn.fields[i].color+'" id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'">';
				
			} else if (btn.fields[i].values){
				//this is a select list
				inputHtml='<select id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'">';
				jQuery.each(btn.fields[i].values, function(index, value){
					inputHtml+='<option value="'+value+'">'+value+'</option>';
				});
				inputHtml+='</select>';
			} else {
				if(btn.fields[i].textarea && !differentthemesButtonManager.opera){
					//this field should be a text area
					inputHtml='<textarea id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'" ></textarea>';
				} else if(btn.fields[i].upload && !differentthemesButtonManager.opera){ 
					// upload input
					inputHtml='<input type="text" id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'" class="df-upload-field"/><a href="#" class="df-upload-button">Button</a>';
				} else if(btn.fields[i].unlimitedinput && !differentthemesButtonManager.opera){ 
					// unlimited input
					inputHtml='<input type="text" class="dflist" id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'-1" /><input type="text" id="df-list" value="1" hidden /><br /><br /><strong>To add new field press Enter</strong>';
				} else if(btn.fields[i].lists && !differentthemesButtonManager.opera){ 
					// unlimited input
					inputHtml='<input type="text" class="lists" id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'-1" /><input type="text" id="df-lists" value="1" hidden /><br /><br /><strong>To add new field press Enter</strong>';
				} else if(btn.fields[i].social && !differentthemesButtonManager.opera){ 
					// unlimited input
					inputHtml='<input type="text" class="social" id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'-1" /><input type="text" id="df-social" value="1" hidden /><br /><br /><strong>To add new field press Enter</strong>';
				} else if(btn.fields[i].tabs && !differentthemesButtonManager.opera){ 
					// unlimited input
					inputHtml='<textarea id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'-1"  class="tabs" ></textarea><input type="text" id="df-tabs" value="1" hidden /><br /><br /><strong>To add new field press Enter</strong>';
				} else if(btn.fields[i].team && !differentthemesButtonManager.opera){ 
					// unlimited input
					inputHtml='<input type="text" class="team" id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'-1" /><input type="text" id="df-team" value="1" hidden /><br /><br /><strong>To add new field press Enter</strong>';
				} else if(btn.fields[i].skill && !differentthemesButtonManager.opera){ 
					// unlimited input
					inputHtml='<input type="text" id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'-1"  class="skill" ><input type="text" id="df-skill" value="1" hidden /><br /><br /><br /><strong>To add new field press Enter</strong>';
				} else if(btn.fields[i].accordion && !differentthemesButtonManager.opera){ 
					// unlimited input
					inputHtml='<textarea id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'-1"  class="accordion" ></textarea><input type="text" id="df-accordion" value="1" hidden /><br /><br /><strong>To add new field press Enter</strong>';
				} else{
					//this field should be a normal input
					inputHtml='<input type="text" id="'+differentthemesButtonManager.idprefix+btn.fields[i].id+'" />';
				}
			}
			html+='<div class="differentthemes-shortcode-field"><label>'+btn.fields[i].name+'</label>'+inputHtml+'</div>';
		}
		html+='<a href="" id="insertbtn" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button"><span class="ui-button-text">Insert</span></a></div>';
				
		var dialog = jQuery(html).dialog({
							 title:btn.title, 
							 modal:true,
							 close:function(event, ui){
								jQuery(this).html('').remove();
							 }
							 });
		
		differentthemesButtonManager.dialog=dialog;
		
		//set a click handler to the insert button
		dialog.find('#insertbtn').click(function(event){
			event.preventDefault();
			differentthemesButtonManager.executeCommand(ed,btn,selection);
		});

			dialog.keyup(function(){
			  if(event.keyCode == 13 && jQuery(".dflist").is(":focus")) {
				var i = jQuery('#df-list').val();
				var n = Number(i)+Number(1);
				jQuery('<input type="text" class="otlist" id="differentthemes-shortcode-list-'+n+'" />').insertAfter("#differentthemes-shortcode-list-"+i);    
				jQuery('#df-list').val(n);
			  }
			});
			
			dialog.keyup(function(){
				if(event.keyCode == 13 && jQuery(".tabs").is(":focus") && jQuery('#df-tabs').val() <5) {
					var i = jQuery('#df-tabs').val();
					var n = Number(i)+Number(1);
					jQuery('<div class="differentthemes-shortcode-field"><label>Title: </label><input type="text" id="differentthemes-shortcode-title-'+n+'"></div><div class="differentthemes-shortcode-field"><label>Text: </label><textarea id="differentthemes-shortcode-text-'+n+'" class="tabs"></textarea></div>').insertBefore("#insertbtn");    
					jQuery('#df-tabs').val(n);
				}
			});		

			dialog.keyup(function(){
			  if(event.keyCode == 13 && jQuery(".team").is(":focus")) {
				var i = jQuery('#df-team').val();
				var n = Number(i)+Number(1);
				jQuery('<div class="differentthemes-shortcode-field"><label>Social</label><select id="differentthemes-shortcode-social-'+n+'"><option value="Twitter">Twitter</option><option value="Facebook">Facebook</option><option value="Dribbble">Dribbble</option><option value="Youtube">Youtube</option><option value="Google-Plus">Google-Plus</option><option value="Linkedin">Linkedin</option><option value="Pinterest">Pinterest</option><option value="Skype">Skype</option><option value="Flickr">Flickr</option><option value="Tumblr">Tumblr</option><option value="Instagram">Instagram</option><option value="Github">Github</option><option value="Rss">Rss</option></select></div><div class="differentthemes-shortcode-field"><label>URL</label><input type="text" class="team" id="differentthemes-shortcode-url-'+n+'"></div>').insertBefore("#insertbtn");    
				jQuery('#df-team').val(n);
			  }
			});

			dialog.keyup(function(){
				if(event.keyCode == 13 && jQuery(".accordion").is(":focus") && jQuery('#df-accordion').val() <5 ) {
					var i = jQuery('#df-accordion').val();
					var n = Number(i)+Number(1);
					jQuery('<div class="differentthemes-shortcode-field"><label>Title: </label><input type="text" id="differentthemes-shortcode-title-'+n+'"></div><div class="differentthemes-shortcode-field"><label>Text: </label><textarea id="differentthemes-shortcode-text-'+n+'" class="accordion"></textarea></div>').insertBefore("#insertbtn");    
					jQuery('#df-accordion').val(n);
				}
			});

			dialog.keyup(function(){
				if(event.keyCode == 13 && jQuery(".skill").is(":focus") && jQuery('#df-skill').val() <5) {
					var i = jQuery('#df-skill').val();
					var n = Number(i)+Number(1);
					jQuery('<div class="differentthemes-shortcode-field"><label>Skill Title: </label><input type="text" id="differentthemes-shortcode-title-'+n+'"></div><div class="differentthemes-shortcode-field"><label>Skill Level in Precentage: </label><input type="text" id="differentthemes-shortcode-level-'+n+'" class="skill"></div>').insertBefore("#insertbtn");    
					jQuery('#df-skill').val(n);
				}
			});

			dialog.keyup(function(){
			  if(event.keyCode == 13 && jQuery(".lists").is(":focus")) {
				var i = jQuery('#df-lists').val();
				var n = Number(i)+Number(1);
				var ii;
				var icons = df_icons();
				var iconsHTML='';
				for (ii = 0; ii < icons.length; ++ii) {
				    iconsHTML+= "<option name='"+icons[ii]+"'>"+icons[ii]+"</option>";
				}
				jQuery('<div class="differentthemes-shortcode-field"><label>Type</label><select id="differentthemes-shortcode-type-'+n+'">'+iconsHTML+'</select></div><div class="differentthemes-shortcode-field"><label>Text</label><input type="text" class="lists" id="differentthemes-shortcode-lists-'+n+'"></div>').insertBefore("#insertbtn");    
				jQuery('#df-lists').val(n);
			  }
			});

			dialog.keyup(function(){
				if(event.keyCode == 13 && jQuery(".social").is(":focus")) {
					var i = jQuery('#df-social').val();
					var n = Number(i)+Number(1);
					jQuery('<div class="differentthemes-shortcode-field"><label>Type</label><select id="differentthemes-shortcode-type-'+n+'"><option value="fa-rss">fa-rss</option><option value="fa-github">fa-github</option><option value="fa-instagram">fa-instagram</option><option value="fa-tumblr">fa-tumblr</option><option value="fa-flickr">fa-flickr</option><option value="fa-skype">fa-skype</option><option value="fa-pinterest">fa-pinterest</option><option value="fa-linkedin">fa-linkedin</option><option value="fa-google-plus">fa-google-plus</option><option value="fa-youtube-play">fa-youtube-play</option><option value="fa-dribbble">fa-dribbble</option><option value="fa-facebook">fa-facebook</option><option value="fa-twitter">fa-twitter</option></select></div><div class="differentthemes-shortcode-field"><label>Account Url</label><input type="text" class="social" id="differentthemes-shortcode-social-'+n+'"></div>').insertBefore("#insertbtn");    
					jQuery('#df-social').val(n);
				}
			});
	},

	/**
	 * Executes a command when the insert button has been clicked.
	 */
	executeCommand:function(ed, btn, selection){

    		var values={}, html='';
    		var selection = ed.selection.getContent();
    		if(!btn.allowSelection){
    			//the button doesn't allow selection, generate the values as an object literal
	    		for(var i=0, length=btn.fields.length; i<length; i++){
	        		var id=btn.fields[i].id,
	        			value=jQuery('#'+differentthemesButtonManager.idprefix+id).val();
	        		
	    			values[id]=value;
	    		}
	    		html = btn.generateHtml(values);
    		}else{
				var values={};
    			//the button allows selection - only one value is needed for the formatting, so
    			//return this value only (not an object literal)
    			values[btn.fields[0].id]=jQuery('#'+differentthemesButtonManager.idprefix+btn.fields[0].id).attr("value");
				if(btn.fields.length>=2) {
					values[btn.fields[1].id]=jQuery('#'+differentthemesButtonManager.idprefix+btn.fields[1].id).attr("value");
				}
				values["selection"]= jQuery('#'+differentthemesButtonManager.idprefix+"selection").attr("value");

    			html = btn.generateHtml(values);
    		}
    		
    	differentthemesButtonManager.dialog.remove();

  		ed.execCommand('mceInsertContent', false, html);
    	
	}
};

/**
 * Init the formatting functionality.
 */
(function() {
	
	differentthemesButtonManager.init();
    
})();

function df_icons() {
	return ['Select a Icon','fa-glass',
'fa-music',
'fa-search',
'fa-envelope-o',
'fa-heart',
'fa-star',
'fa-star-o',
'fa-user',
'fa-film',
'fa-th-large',
'fa-th',
'fa-th-list',
'fa-check',
'fa-times',
'fa-search-plus',
'fa-search-minus',
'fa-power-off',
'fa-signal',
'fa-cog',
'fa-trash-o',
'fa-home',
'fa-file-o',
'fa-clock-o',
'fa-road',
'fa-download',
'fa-arrow-circle-o-down',
'fa-arrow-circle-o-up',
'fa-inbox',
'fa-play-circle-o',
'fa-repeat',
'fa-refresh',
'fa-list-alt',
'fa-lock',
'fa-flag',
'fa-headphones',
'fa-volume-off',
'fa-volume-down',
'fa-volume-up',
'fa-qrcode',
'fa-barcode',
'fa-tag',
'fa-tags',
'fa-book',
'fa-bookmark',
'fa-print',
'fa-camera',
'fa-font',
'fa-bold',
'fa-italic',
'fa-text-height',
'fa-text-width',
'fa-align-left',
'fa-align-center',
'fa-align-right',
'fa-align-justify',
'fa-list',
'fa-outdent',
'fa-indent',
'fa-video-camera',
'fa-picture-o',
'fa-pencil',
'fa-map-marker',
'fa-adjust',
'fa-tint',
'fa-pencil-square-o',
'fa-share-square-o',
'fa-check-square-o',
'fa-arrows',
'fa-step-backward',
'fa-fast-backward',
'fa-backward',
'fa-play',
'fa-pause',
'fa-stop',
'fa-forward',
'fa-fast-forward',
'fa-step-forward',
'fa-eject',
'fa-chevron-left',
'fa-chevron-right',
'fa-plus-circle',
'fa-minus-circle',
'fa-times-circle',
'fa-check-circle',
'fa-question-circle',
'fa-info-circle',
'fa-crosshairs',
'fa-times-circle-o',
'fa-check-circle-o',
'fa-ban',
'fa-arrow-left',
'fa-arrow-right',
'fa-arrow-up',
'fa-arrow-down',
'fa-share',
'fa-expand',
'fa-compress',
'fa-plus',
'fa-minus',
'fa-asterisk',
'fa-exclamation-circle',
'fa-gift',
'fa-leaf',
'fa-fire',
'fa-eye',
'fa-eye-slash',
'fa-exclamation-triangle',
'fa-plane',
'fa-calendar',
'fa-random',
'fa-comment',
'fa-magnet',
'fa-chevron-up',
'fa-chevron-down',
'fa-retweet',
'fa-shopping-cart',
'fa-folder',
'fa-folder-open',
'fa-arrows-v',
'fa-arrows-h',
'fa-bar-chart-o',
'fa-twitter-square',
'fa-facebook-square',
'fa-camera-retro',
'fa-key',
'fa-cogs',
'fa-comments',
'fa-thumbs-o-up',
'fa-thumbs-o-down',
'fa-star-half',
'fa-heart-o',
'fa-sign-out',
'fa-linkedin-square',
'fa-thumb-tack',
'fa-external-link',
'fa-sign-in',
'fa-trophy',
'fa-github-square',
'fa-upload',
'fa-lemon-o',
'fa-phone',
'fa-square-o',
'fa-bookmark-o',
'fa-phone-square',
'fa-twitter',
'fa-facebook',
'fa-github',
'fa-unlock',
'fa-credit-card',
'fa-rss',
'fa-hdd-o',
'fa-bullhorn',
'fa-bell',
'fa-certificate',
'fa-hand-o-right',
'fa-hand-o-left',
'fa-hand-o-up',
'fa-hand-o-down',
'fa-arrow-circle-left',
'fa-arrow-circle-right',
'fa-arrow-circle-up',
'fa-arrow-circle-down',
'fa-globe',
'fa-wrench',
'fa-tasks',
'fa-filter',
'fa-briefcase',
'fa-arrows-alt',
'fa-users',
'fa-link',
'fa-cloud',
'fa-flask',
'fa-scissors',
'fa-files-o',
'fa-paperclip',
'fa-floppy-o',
'fa-square',
'fa-bars',
'fa-list-ul',
'fa-list-ol',
'fa-strikethrough',
'fa-underline',
'fa-table',
'fa-magic',
'fa-truck',
'fa-pinterest',
'fa-pinterest-square',
'fa-google-plus-square',
'fa-google-plus',
'fa-money',
'fa-caret-down',
'fa-caret-up',
'fa-caret-left',
'fa-caret-right',
'fa-columns',
'fa-sort',
'fa-sort-asc',
'fa-sort-desc',
'fa-envelope',
'fa-linkedin',
'fa-undo',
'fa-gavel',
'fa-tachometer',
'fa-comment-o',
'fa-comments-o',
'fa-bolt',
'fa-sitemap',
'fa-umbrella',
'fa-clipboard',
'fa-lightbulb-o',
'fa-exchange',
'fa-cloud-download',
'fa-cloud-upload',
'fa-user-md',
'fa-stethoscope',
'fa-suitcase',
'fa-bell-o',
'fa-coffee',
'fa-cutlery',
'fa-file-text-o',
'fa-building-o',
'fa-hospital-o',
'fa-ambulance',
'fa-medkit',
'fa-fighter-jet',
'fa-beer',
'fa-h-square',
'fa-plus-square',
'fa-angle-double-left',
'fa-angle-double-right',
'fa-angle-double-up',
'fa-angle-double-down',
'fa-angle-left',
'fa-angle-right',
'fa-angle-up',
'fa-angle-down',
'fa-desktop',
'fa-laptop',
'fa-tablet',
'fa-mobile',
'fa-circle-o',
'fa-quote-left',
'fa-quote-right',
'fa-spinner',
'fa-circle',
'fa-reply',
'fa-github-alt',
'fa-folder-o',
'fa-folder-open-o',
'fa-smile-o',
'fa-frown-o',
'fa-meh-o',
'fa-gamepad',
'fa-keyboard-o',
'fa-flag-o',
'fa-flag-checkered',
'fa-terminal',
'fa-code',
'fa-reply-all',
'fa-mail-reply-all',
'fa-star-half-o',
'fa-location-arrow',
'fa-crop',
'fa-code-fork',
'fa-chain-broken',
'fa-question',
'fa-info',
'fa-exclamation',
'fa-superscript',
'fa-subscript',
'fa-eraser',
'fa-puzzle-piece',
'fa-microphone',
'fa-microphone-slash',
'fa-shield',
'fa-calendar-o',
'fa-fire-extinguisher',
'fa-rocket',
'fa-maxcdn',
'fa-chevron-circle-left',
'fa-chevron-circle-right',
'fa-chevron-circle-up',
'fa-chevron-circle-down',
'fa-html5',
'fa-css3',
'fa-anchor',
'fa-unlock-alt',
'fa-bullseye',
'fa-ellipsis-h',
'fa-ellipsis-v',
'fa-rss-square',
'fa-play-circle',
'fa-ticket',
'fa-minus-square',
'fa-minus-square-o',
'fa-level-up',
'fa-level-down',
'fa-check-square',
'fa-pencil-square',
'fa-external-link-square',
'fa-share-square',
'fa-compass',
'fa-caret-square-o-down',
'fa-caret-square-o-up',
'fa-caret-square-o-right',
'fa-eur',
'fa-gbp',
'fa-usd',
'fa-inr',
'fa-jpy',
'fa-rub',
'fa-krw',
'fa-btc',
'fa-file',
'fa-file-text',
'fa-sort-alpha-asc',
'fa-sort-alpha-desc',
'fa-sort-amount-asc',
'fa-sort-amount-desc',
'fa-sort-numeric-asc',
'fa-sort-numeric-desc',
'fa-thumbs-up',
'fa-thumbs-down',
'fa-youtube-square',
'fa-youtube',
'fa-xing',
'fa-xing-square',
'fa-youtube-play',
'fa-dropbox',
'fa-stack-overflow',
'fa-instagram',
'fa-flickr',
'fa-adn',
'fa-bitbucket',
'fa-bitbucket-square',
'fa-tumblr',
'fa-tumblr-square',
'fa-long-arrow-down',
'fa-long-arrow-up',
'fa-long-arrow-left',
'fa-long-arrow-right',
'fa-apple',
'fa-windows',
'fa-android',
'fa-linux',
'fa-dribbble',
'fa-skype',
'fa-foursquare',
'fa-trello',
'fa-female',
'fa-male',
'fa-gittip',
'fa-sun-o',
'fa-moon-o',
'fa-archive',
'fa-bug',
'fa-vk',
'fa-weibo',
'fa-renren',
'fa-pagelines',
'fa-stack-exchange',
'fa-arrow-circle-o-right',
'fa-arrow-circle-o-left',
'fa-caret-square-o-left',
'fa-dot-circle-o',
'fa-wheelchair',
'fa-vimeo-square',
'fa-try',
'fa-plus-square-o'];
}
