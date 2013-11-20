//helper functions//
//do bind
function do_bind(sele,eve,f){jQuery(sele).bind(eve,f);}

//show dialog
function show_dialog(sele,setup){
	var defaults = {show: 'slide',hide: 'slide'};
	var settings = jQuery.extend({}, defaults, setup);
	jQuery(sele).dialog(settings);
}

//ajax helper
function aceAJAX(data,resF){jQuery.post(ajaxurl, data, resF);}

//add toolbar button
function addToolbarButton(html,cla,i,title,src,alt,f){
	var toolbar = jQuery('.ace_tool_bar');
	html = html || false;
	f = f || false;
	i = i || false;
	if (html){
		toolbar.append(html);
	}else{
		cla = cla || 'ace_tool_bar_button';
		title = title || '';
		src = src || false;
		alt = alt || '';
		if (src.indexOf('http') != -1 )
			var image = jQuery('<img>').attr('src',src).attr('alt',alt);
		else
			var image = jQuery('<img>').attr('src',ace_strings.imgURL+src).attr('alt',alt);
		var a = jQuery('<a>').addClass(cla).attr('id',i).attr('title',title).append(image);
		var li = jQuery('<li>').append(a);
		toolbar.append(li);
	}
	if (i && f){
		do_bind("#"+i,'click',f);
	}
}