/*                       __  __           _     
       WordPress Plugin |  \/  |         | |    
  _ __   __ _  __ _  ___| \  / | __ _ ___| |__  
 | '_ \ / _` |/ _` |/ _ \ |\/| |/ _` / __| '_ \ 
 | |_) | (_| | (_| |  __/ |  | | (_| \__ \ | | |
 | .__/ \__,_|\__, |\___|_|  |_|\__,_|___/_| |_|
 | |           __/ |  Author: Joel Starnes
 |_|          |___/   URL: postMash.joelstarnes.co.uk
 
 >>Main javascript include
*/

window.addEvent('domready', function(){ 
	// If user doesn't have Firebug, create empty functions for the console.
	if (!window.console || !console.firebug)
	{
	    var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml",
	    "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];
	
	    window.console = {};
	    for (var i = 0; i < names.length; ++i)
	        window.console[names[i]] = function() {}
	}
});

/* clicking links should not start the drag, but fire the link's event */
var MySortables = Sortables.extend({
	start: function(event, element) {
		if (event.target.tagName != 'A' && event.target.tagName != 'IMG') {
			this.parent(event, element); 
		}	
	}
});

/* add timeout to Ajax class */
Ajax = Ajax.extend({
	request: function(){
	if (this.options.timeout) {
		this.timeoutTimer=window.setTimeout(this.callTimeout.bindAsEventListener(this), this.options.timeout);
		this.addEvent('onComplete', this.removeTimer);
	}
	this.parent();
	},
	callTimeout: function () {
		this.transport.abort();
		this.onFailure();
		if (this.options.onTimeout) {
			this.options.onTimeout();

		}
	},
	removeTimer: function() {
		window.clearTimeout(this.timeoutTimer);
	}
});
/* function to retrieve list data and send to server in JSON format */
var SaveList = function() {
	var theDump = mySort.serialize();
	console.group('Database Update');
	console.time('Update Chronometer');
	new Ajax('../wp-content/plugins/postmash/saveList.php', {
		method: 'post',
		postBody: 'm='+Json.toString(theDump), 
		update: "debug_list", 
		onComplete: function() {
			$('update_status').setText('Database Updated');
			new Fx.Style($('update_status'), 'opacity', {duration: 500}).start(0,1).chain(function() {
				new Fx.Style($('update_status'), 'opacity', {duration: 1500}).start(1,0);
			});
			console.log('Database Successfully Updated');
			console.timeEnd('Update Chronometer');
			console.groupEnd();
		},
		timeout: 8500, 
		onTimeout: function() {
			$('update_status').setText('Error: Update Timeout');
			new Fx.Style($('update_status'), 'opacity', {duration: 200}).start(0,1);
			console.timeEnd('Update Chronometer');
			console.error('Error: update confirmation not recieved');
			console.groupEnd();
		}
	}).request();
};
/* toggle the remove class of grandparent */
	var toggleRemove = function(el) {
		el.parentNode.parentNode.parentNode.toggleClass('remove');
		console.log("Page: '%s' has been %s", $E('span.title', el.parentNode.parentNode.parentNode).innerHTML, (el.parentNode.parentNode.parentNode.hasClass('remove') ? 'HIDDEN': 'MADE VISIBLE' ));
	}


/* ******** dom ready ******** */
window.addEvent('domready', function(){
	mySort = new MySortables($('postMash_pages'), {
		cloneOpacity:.2,
		onComplete: function(){
			/* alternate list colour & if($instantUpdateFeature) ajax db update */
			mySort.altColor();
		}
	});
	Sortables.implement({
		serialize: function(listEl) {
			var serial = [];
			if (!listEl) listEl = this.list;
			$$(listEl.childNodes).each(function(node, i) {
				kids = $E('ul', node); /* set 'this.options.parentTag' straight to 'ul' to avoid safari bug */
				serial[i] = {
					id: node.id
				};
				if (node.hasClass('remove'))  serial[i].hide = true;
				/* if (node.hasClass('renamed')) serial[i].renamed = $E('span.title', node).innerHTML;  */ /* there is no rename feature in postMash */
			}.bind(this));
			return serial;
		},
		altColor: function(){
			/* alternate the list colour */
			var odd = 1;
			this.list.getChildren().each(function(element, i){
				if(odd==1){
					odd=0;
					element.setStyle('background-color', '#CFE8A8');
				}else{
					odd=1;
					element.setStyle('background-color', '#D8E8E6');
				}
			});
		}
	});
	
	mySort.altColor();
	$('postMash_submit').addEvent('click', function(e){
		e = new Event(e);
		SaveList();
		e.stop();
	});

	var postMashInfo = new Fx.Slide('postMashInfo');
	$('postMashInfo_toggle').addEvent('click', function(e){
		e = new Event(e);
		postMashInfo.toggle();
		e.stop();
		switch($('postMashInfo_toggle').getText()) {
			case "Show Further Info":
				$('postMashInfo_toggle').setText('Hide Further Info');
			  break    
			case "Hide Further Info":
				$('postMashInfo_toggle').setText('Show Further Info');
			  break
		}
	});
	postMashInfo.hide();
	$('postMashInfo_toggle').setText('Show Further Info');
	
	$('show_debug_list').addEvent('click', function(e){
		e = new Event(e);
		$('debug_list').setStyle('display','block');
		e.stop();
	});

	/* disable drag text-selection for IE */
	if (typeof document.body.onselectstart!="undefined")
		document.body.onselectstart=function(){return false}
	
	console.info("We're all up and running.")
}); /* close dom ready */