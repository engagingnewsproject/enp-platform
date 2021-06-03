// jQuery File Tree Plugin
//
// Version 1.0
//
// Base on the work of Cory S.N. LaViska  A Beautiful Site (http://abeautifulsite.net/)
// Dual-licensed under the GNU General Public License and the MIT License
// Icons from famfamfam silk icon set thanks to http://www.famfamfam.com/lab/icons/silk/
//
// Usage : $('#jao').wpio_jaofoldertree(wpio_options);
//
// Author: Damien Barrère
// Website: http://www.crac-design.com

(function( $ ) {
  
    var wpio_options =  {
      'root'            : '/',
      'script'         : 'connectors/jaoconnector.php',
      'showroot'        : 'root',
      'onclick'         : function(elem,type,file){},
      'oncheck'         : function(elem,checked,type,file){},
      'usecheckboxes'   : true, //can be true files dirs or false
      'expandSpeed'     : 500,
      'collapseSpeed'   : 500,
      'expandEasing'    : null,
      'collapseEasing'  : null,
      'canselect'       : true
    };

    var wpio_methods = {
        init : function( o ) {
            if($(this).length==0){
                return;
            }
            $this = $(this);
            $.extend(wpio_options,o);

            if(wpio_options.showroot!=''){
                checkboxes = '';
                if(wpio_options.usecheckboxes===true || wpio_options.usecheckboxes==='dirs'){
                    checkboxes = '<input type="checkbox" /><span class="check" data-file="'+wpio_options.root+'" data-type="dir"></span>';
                }
                $this.html('<ul class="wpio_jaofoldertree"><li class="drive wpio_directory collapsed selected">'+checkboxes+'<a href="#" data-file="'+wpio_options.root+'" data-type="dir">'+wpio_options.showroot+'</a></li></ul>');
            }
            wpio_openfolder(wpio_options.root);
        },
        open : function(dir){
            wpio_openfolder(dir);
        },
        close : function(dir){
            wpio_closedir(dir);
        },
        getchecked : function(){
            var list = new Array();            
            var ik = 0;
            $this.find('input:checked + a').each(function(){
                list[ik] = {
                    type : $(this).attr('data-type'),
                    file : $(this).attr('data-file')
                }                
                ik++;
            });
	    return list;
        },
        getselected : function(){
            var list = new Array();            
            var ik = 0;
            $this.find('li.selected > a').each(function(){
                list[ik] = {
                    type : $(this).attr('data-type'),
                    file : $(this).attr('data-file')
                }                
                ik++;
            });
	    return list;
        }
    };

    wpio_openfolder = function(dir) {
	    if($this.find('a[data-file="'+dir+'"]').parent().hasClass('expanded')){
		return;
	    }
            var ret;
            ret = $.ajax({
                url : wpio_options.script,
                data : {dir : dir, action: 'wpio_getFolders'},
                context : $this,
		dataType: 'json',
                beforeSend : function(){this.find('a[data-file="'+dir+'"]').parent().addClass('wait');}
            }).done(function(datas) {
                ret = '<ul class="wpio_jaofoldertree" style="display: none">';
                for(ij=0; ij<datas.length; ij++){
                    if(datas[ij].type=='dir'){
                        classe = 'wpio_directory collapsed';
                        isdir = '/';
                    }else{
                        classe = 'file ext_'+datas[ij].ext;
                        isdir = '';
                    }
                    ret += '<li class="'+classe+'">'                    
                    if(wpio_options.usecheckboxes===true || (wpio_options.usecheckboxes==='dirs' && datas[ij].type=='dir') || (wpio_options.usecheckboxes==='files' && datas[ij].type=='file')){
                        ret += '<input type="checkbox" data-file="'+dir+datas[ij].file+isdir+'" data-type="'+datas[ij].type+'" />';                        
                       
                        testFolder = dir+datas[ij].file; 
                        if (testFolder.substring(0,1) ==  '/') {
                            testFolder = testFolder.substring(1,testFolder.length);
                        }
                        if(curFolders.indexOf(testFolder) > -1 ) {    
                            ret += '<span class="check checked" data-file="'+dir+datas[ij].file+isdir+'" data-type="'+datas[ij].type+'"></span>';
                        }else if(datas[ij].pchecked===true) {
                            ret += '<span class="check pchecked" data-file="'+dir+datas[ij].file+isdir+'" data-type="'+datas[ij].type+'" ></span>';
                        }else {
                            ret += '<span class="check" data-file="'+dir+datas[ij].file+isdir+'" data-type="'+datas[ij].type+'" ></span>';
                        }
                        
                    }
                    else{
//                        ret += '<input disabled="disabled" type="checkbox" data-file="'+dir+datas[ij].file+'" data-type="'+datas[ij].type+'"/>';
                    }
                    ret += '<a href="#" data-file="'+dir+datas[ij].file+isdir+'" data-type="'+datas[ij].type+'">'+datas[ij].file+'</a>';
                    ret += '</li>';
                }
                ret += '</ul>';
                
                this.find('a[data-file="'+dir+'"]').parent().removeClass('wait').removeClass('collapsed').addClass('expanded');
                this.find('a[data-file="'+dir+'"]').after(ret);
                this.find('a[data-file="'+dir+'"]').next().slideDown(wpio_options.expandSpeed,wpio_options.expandEasing);
                
                wpio_setevents();
                
                if(wpio_options.usecheckboxes){
                    this.find('a[data-file="'+dir+'"]').parent().find('li input[type="checkbox"]').attr('checked',null);
                    for(ij=0; ij<datas.length; ij++){
                        testFolder = dir+datas[ij].file;
                        if (testFolder.substring(0,1) ==  '/') {
                            testFolder = testFolder.substring(1,testFolder.length);
                        }
                        if( curFolders.indexOf(testFolder) > -1) {                                                            
                            this.find('input[data-file="'+dir+datas[ij].file+isdir+'"]').attr('checked','checked');
                        }
                    }
                    
                    if( this.find('input[data-file="'+dir+'"]').is(':checked')) {                        
                         this.find('input[data-file="'+dir+'"]').parent().find('li input[type="checkbox"]').each(function(){                              
                             $(this).prop('checked',true).trigger('change');
                         })                                 
                         this.find('input[data-file="'+dir+'"]').parent().find('li span.check').addClass("checked");
                    }
                   
                }

               
            }).done(function(){              
                //Trigger custom event
                $this.trigger('afteropen');
                $this.trigger('afterupdate');
            });
    }

    wpio_closedir = function(dir) {
            $this.find('a[data-file="'+dir+'"]').next().slideUp(wpio_options.collapseSpeed,wpio_options.collapseEasing,function(){$(this).remove();});
            $this.find('a[data-file="'+dir+'"]').parent().removeClass('expanded').addClass('collapsed');
            wpio_setevents();
            
            //Trigger custom event
            $this.trigger('afterclose');
            $this.trigger('afterupdate');
            
    }

    wpio_setevents = function(){
        $this.find('li a').unbind('click');
        //Bind userdefined function on click an element
        $this.find('li a').bind('click', function() {
            wpio_options.onclick(this, $(this).attr('data-type'),$(this).attr('data-file'));
            if(wpio_options.usecheckboxes && $(this).attr('data-type')=='file'){
                    $this.find('li input[type="checkbox"]').attr('checked',null);
                    $(this).prev(':not(:disabled)').attr('checked','checked');
                    $(this).prev(':not(:disabled)').trigger('check');
            }
            if(wpio_options.canselect){
                $this.find('li').removeClass('selected');
                $(this).parent().addClass('selected');
            }
            return false;
        });
        //Bind checkbox check/uncheck
        $this.find('li input[type="checkbox"]').bind('change', function() {
            wpio_options.oncheck(this,$(this).is(':checked'), $(this).next().attr('data-type'),$(this).next().attr('data-file'));
            if($(this).is(':checked')){
                $this.trigger('check');
            }else{
                $this.trigger('uncheck');
            }
        });
        //Bind for collapse or expand elements
        $this.find('li.wpio_directory.collapsed a').bind('click', function() {wpio_methods.open($(this).attr('data-file'));return false;});
        $this.find('li.wpio_directory.expanded a').bind('click', function() {wpio_methods.close($(this).attr('data-file'));return false;});        
    }

    $.fn.wpio_jaofoldertree = function( method ) {
        // Method calling logic
        if ( wpio_methods[method] ) {
            return wpio_methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            return wpio_methods.init.apply( this, arguments );
        } else {
            //error
        }    
  };
})( jQuery );
