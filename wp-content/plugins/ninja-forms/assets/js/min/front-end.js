(function () {
/**
 * @license almond 0.3.1 Copyright (c) 2011-2014, The Dojo Foundation All Rights Reserved.
 * Available via the MIT or new BSD license.
 * see: http://github.com/jrburke/almond for details
 */
//Going sloppy to avoid 'use strict' string cost, but strict practices should
//be followed.
/*jslint sloppy: true */
/*global setTimeout: false */

var requirejs, require, define;
(function (undef) {
    var main, req, makeMap, handlers,
        defined = {},
        waiting = {},
        config = {},
        defining = {},
        hasOwn = Object.prototype.hasOwnProperty,
        aps = [].slice,
        jsSuffixRegExp = /\.js$/;

    function hasProp(obj, prop) {
        return hasOwn.call(obj, prop);
    }

    /**
     * Given a relative module name, like ./something, normalize it to
     * a real name that can be mapped to a path.
     * @param {String} name the relative name
     * @param {String} baseName a real name that the name arg is relative
     * to.
     * @returns {String} normalized name
     */
    function normalize(name, baseName) {
        var nameParts, nameSegment, mapValue, foundMap, lastIndex,
            foundI, foundStarMap, starI, i, j, part,
            baseParts = baseName && baseName.split("/"),
            map = config.map,
            starMap = (map && map['*']) || {};

        //Adjust any relative paths.
        if (name && name.charAt(0) === ".") {
            //If have a base name, try to normalize against it,
            //otherwise, assume it is a top-level require that will
            //be relative to baseUrl in the end.
            if (baseName) {
                name = name.split('/');
                lastIndex = name.length - 1;

                // Node .js allowance:
                if (config.nodeIdCompat && jsSuffixRegExp.test(name[lastIndex])) {
                    name[lastIndex] = name[lastIndex].replace(jsSuffixRegExp, '');
                }

                //Lop off the last part of baseParts, so that . matches the
                //"directory" and not name of the baseName's module. For instance,
                //baseName of "one/two/three", maps to "one/two/three.js", but we
                //want the directory, "one/two" for this normalization.
                name = baseParts.slice(0, baseParts.length - 1).concat(name);

                //start trimDots
                for (i = 0; i < name.length; i += 1) {
                    part = name[i];
                    if (part === ".") {
                        name.splice(i, 1);
                        i -= 1;
                    } else if (part === "..") {
                        if (i === 1 && (name[2] === '..' || name[0] === '..')) {
                            //End of the line. Keep at least one non-dot
                            //path segment at the front so it can be mapped
                            //correctly to disk. Otherwise, there is likely
                            //no path mapping for a path starting with '..'.
                            //This can still fail, but catches the most reasonable
                            //uses of ..
                            break;
                        } else if (i > 0) {
                            name.splice(i - 1, 2);
                            i -= 2;
                        }
                    }
                }
                //end trimDots

                name = name.join("/");
            } else if (name.indexOf('./') === 0) {
                // No baseName, so this is ID is resolved relative
                // to baseUrl, pull off the leading dot.
                name = name.substring(2);
            }
        }

        //Apply map config if available.
        if ((baseParts || starMap) && map) {
            nameParts = name.split('/');

            for (i = nameParts.length; i > 0; i -= 1) {
                nameSegment = nameParts.slice(0, i).join("/");

                if (baseParts) {
                    //Find the longest baseName segment match in the config.
                    //So, do joins on the biggest to smallest lengths of baseParts.
                    for (j = baseParts.length; j > 0; j -= 1) {
                        mapValue = map[baseParts.slice(0, j).join('/')];

                        //baseName segment has  config, find if it has one for
                        //this name.
                        if (mapValue) {
                            mapValue = mapValue[nameSegment];
                            if (mapValue) {
                                //Match, update name to the new value.
                                foundMap = mapValue;
                                foundI = i;
                                break;
                            }
                        }
                    }
                }

                if (foundMap) {
                    break;
                }

                //Check for a star map match, but just hold on to it,
                //if there is a shorter segment match later in a matching
                //config, then favor over this star map.
                if (!foundStarMap && starMap && starMap[nameSegment]) {
                    foundStarMap = starMap[nameSegment];
                    starI = i;
                }
            }

            if (!foundMap && foundStarMap) {
                foundMap = foundStarMap;
                foundI = starI;
            }

            if (foundMap) {
                nameParts.splice(0, foundI, foundMap);
                name = nameParts.join('/');
            }
        }

        return name;
    }

    function makeRequire(relName, forceSync) {
        return function () {
            //A version of a require function that passes a moduleName
            //value for items that may need to
            //look up paths relative to the moduleName
            var args = aps.call(arguments, 0);

            //If first arg is not require('string'), and there is only
            //one arg, it is the array form without a callback. Insert
            //a null so that the following concat is correct.
            if (typeof args[0] !== 'string' && args.length === 1) {
                args.push(null);
            }
            return req.apply(undef, args.concat([relName, forceSync]));
        };
    }

    function makeNormalize(relName) {
        return function (name) {
            return normalize(name, relName);
        };
    }

    function makeLoad(depName) {
        return function (value) {
            defined[depName] = value;
        };
    }

    function callDep(name) {
        if (hasProp(waiting, name)) {
            var args = waiting[name];
            delete waiting[name];
            defining[name] = true;
            main.apply(undef, args);
        }

        if (!hasProp(defined, name) && !hasProp(defining, name)) {
            throw new Error('No ' + name);
        }
        return defined[name];
    }

    //Turns a plugin!resource to [plugin, resource]
    //with the plugin being undefined if the name
    //did not have a plugin prefix.
    function splitPrefix(name) {
        var prefix,
            index = name ? name.indexOf('!') : -1;
        if (index > -1) {
            prefix = name.substring(0, index);
            name = name.substring(index + 1, name.length);
        }
        return [prefix, name];
    }

    /**
     * Makes a name map, normalizing the name, and using a plugin
     * for normalization if necessary. Grabs a ref to plugin
     * too, as an optimization.
     */
    makeMap = function (name, relName) {
        var plugin,
            parts = splitPrefix(name),
            prefix = parts[0];

        name = parts[1];

        if (prefix) {
            prefix = normalize(prefix, relName);
            plugin = callDep(prefix);
        }

        //Normalize according
        if (prefix) {
            if (plugin && plugin.normalize) {
                name = plugin.normalize(name, makeNormalize(relName));
            } else {
                name = normalize(name, relName);
            }
        } else {
            name = normalize(name, relName);
            parts = splitPrefix(name);
            prefix = parts[0];
            name = parts[1];
            if (prefix) {
                plugin = callDep(prefix);
            }
        }

        //Using ridiculous property names for space reasons
        return {
            f: prefix ? prefix + '!' + name : name, //fullName
            n: name,
            pr: prefix,
            p: plugin
        };
    };

    function makeConfig(name) {
        return function () {
            return (config && config.config && config.config[name]) || {};
        };
    }

    handlers = {
        require: function (name) {
            return makeRequire(name);
        },
        exports: function (name) {
            var e = defined[name];
            if (typeof e !== 'undefined') {
                return e;
            } else {
                return (defined[name] = {});
            }
        },
        module: function (name) {
            return {
                id: name,
                uri: '',
                exports: defined[name],
                config: makeConfig(name)
            };
        }
    };

    main = function (name, deps, callback, relName) {
        var cjsModule, depName, ret, map, i,
            args = [],
            callbackType = typeof callback,
            usingExports;

        //Use name if no relName
        relName = relName || name;

        //Call the callback to define the module, if necessary.
        if (callbackType === 'undefined' || callbackType === 'function') {
            //Pull out the defined dependencies and pass the ordered
            //values to the callback.
            //Default to [require, exports, module] if no deps
            deps = !deps.length && callback.length ? ['require', 'exports', 'module'] : deps;
            for (i = 0; i < deps.length; i += 1) {
                map = makeMap(deps[i], relName);
                depName = map.f;

                //Fast path CommonJS standard dependencies.
                if (depName === "require") {
                    args[i] = handlers.require(name);
                } else if (depName === "exports") {
                    //CommonJS module spec 1.1
                    args[i] = handlers.exports(name);
                    usingExports = true;
                } else if (depName === "module") {
                    //CommonJS module spec 1.1
                    cjsModule = args[i] = handlers.module(name);
                } else if (hasProp(defined, depName) ||
                           hasProp(waiting, depName) ||
                           hasProp(defining, depName)) {
                    args[i] = callDep(depName);
                } else if (map.p) {
                    map.p.load(map.n, makeRequire(relName, true), makeLoad(depName), {});
                    args[i] = defined[depName];
                } else {
                    throw new Error(name + ' missing ' + depName);
                }
            }

            ret = callback ? callback.apply(defined[name], args) : undefined;

            if (name) {
                //If setting exports via "module" is in play,
                //favor that over return value and exports. After that,
                //favor a non-undefined return value over exports use.
                if (cjsModule && cjsModule.exports !== undef &&
                        cjsModule.exports !== defined[name]) {
                    defined[name] = cjsModule.exports;
                } else if (ret !== undef || !usingExports) {
                    //Use the return value from the function.
                    defined[name] = ret;
                }
            }
        } else if (name) {
            //May just be an object definition for the module. Only
            //worry about defining if have a module name.
            defined[name] = callback;
        }
    };

    requirejs = require = req = function (deps, callback, relName, forceSync, alt) {
        if (typeof deps === "string") {
            if (handlers[deps]) {
                //callback in this case is really relName
                return handlers[deps](callback);
            }
            //Just return the module wanted. In this scenario, the
            //deps arg is the module name, and second arg (if passed)
            //is just the relName.
            //Normalize module name, if it contains . or ..
            return callDep(makeMap(deps, callback).f);
        } else if (!deps.splice) {
            //deps is a config object, not an array.
            config = deps;
            if (config.deps) {
                req(config.deps, config.callback);
            }
            if (!callback) {
                return;
            }

            if (callback.splice) {
                //callback is an array, which means it is a dependency list.
                //Adjust args if there are dependencies
                deps = callback;
                callback = relName;
                relName = null;
            } else {
                deps = undef;
            }
        }

        //Support require(['a'])
        callback = callback || function () {};

        //If relName is a function, it is an errback handler,
        //so remove it.
        if (typeof relName === 'function') {
            relName = forceSync;
            forceSync = alt;
        }

        //Simulate async callback;
        if (forceSync) {
            main(undef, deps, callback, relName);
        } else {
            //Using a non-zero value because of concern for what old browsers
            //do, and latest browsers "upgrade" to 4 if lower value is used:
            //http://www.whatwg.org/specs/web-apps/current-work/multipage/timers.html#dom-windowtimers-settimeout:
            //If want a value immediately, use require('id') instead -- something
            //that works in almond on the global level, but not guaranteed and
            //unlikely to work in other AMD implementations.
            setTimeout(function () {
                main(undef, deps, callback, relName);
            }, 4);
        }

        return req;
    };

    /**
     * Just drops the config on the floor, but returns req in case
     * the config return value is used.
     */
    req.config = function (cfg) {
        return req(cfg);
    };

    /**
     * Expose module registry for debugging and tooling
     */
    requirejs._defined = defined;

    define = function (name, deps, callback) {
        if (typeof name !== 'string') {
            throw new Error('See almond README: incorrect module build, no module name');
        }

        //This module may not have dependencies
        if (!deps.splice) {
            //deps is not an array, so probably means
            //an object literal or factory function for
            //the value. Adjust args.
            callback = deps;
            deps = [];
        }

        if (!hasProp(defined, name) && !hasProp(waiting, name)) {
            waiting[name] = [name, deps, callback];
        }
    };

    define.amd = {
        jQuery: true
    };
}());

define("../lib/almond", function(){});

define( 'models/fieldErrorModel',[], function() {
	var model = Backbone.Model.extend( {

	} );
	
	return model;
} );
define( 'models/fieldErrorCollection',['models/fieldErrorModel'], function( errorModel ) {
	var collection = Backbone.Collection.extend( {
		model: errorModel
	} );
	return collection;
} );
define( 'models/fieldModel',['models/fieldErrorCollection'], function( fieldErrorCollection ) {
	var model = Backbone.Model.extend( {
		defaults: {
			placeholder: '',
			value: '',
			label_pos: '',
			classes: 'ninja-forms-field',
			reRender: false,
			mirror_field: false,
			confirm_field: false,
			clean: true,
			disabled: '',
			visible: true,
			invalid: false
		},

		initialize: function() {
			var type = this.get('type');

			this.set( 'formID', this.collection.options.formModel.get( 'id' ) );
			this.listenTo( nfRadio.channel( 'form-' + this.get( 'formID' ) ), 'reset', this.resetModel );

    		this.bind( 'change', this.changeModel, this );
    		this.bind( 'change:value', this.changeValue, this );
    		this.set( 'errors', new fieldErrorCollection() );

			if (type === 'listimage') {
				this.get = this.listimageGet;
				this.set = this.listimageSet;
			}

    		/*
			 * Trigger an init event on two channels:
			 * 
			 * fields
			 * field-type
			 *
			 * This lets specific field types modify model attributes before anything uses them.
			 */
			nfRadio.channel( 'fields' ).trigger( 'init:model', this );
			nfRadio.channel( this.get( 'type' ) ).trigger( 'init:model', this );
			nfRadio.channel( 'fields-' + this.get( 'type' ) ).trigger( 'init:model', this );

			if( 'undefined' != typeof this.get( 'parentType' ) ){
				nfRadio.channel( this.get( 'parentType' ) ).trigger( 'init:model', this );
			}

			/*
			 * When we load our form, fire another event for this field.
			 */
			this.listenTo( nfRadio.channel( 'form-' + this.get( 'formID' ) ), 'loaded', this.formLoaded );
		
			/*
			 * Before we submit our form, send out a message so that this field can be modified if necessary.
			 */
			this.listenTo( nfRadio.channel( 'form-' + this.get( 'formID' ) ), 'before:submit', this.beforeSubmit );
		},

		listimageGet: function(attr) {
            if(attr === 'options') {
					attr = 'image_options';
			}

            return Backbone.Model.prototype.get.call(this, attr);
		},
		
		listimageSet: function(attributes, options) {
			if ('options' === attributes) {
				attributes = 'image_options';
			}
			return Backbone.Model.prototype.set.call(this, attributes, options);
		},

		changeModel: function() {
			nfRadio.channel( 'field-' + this.get( 'id' ) ).trigger( 'change:model', this );
			nfRadio.channel( this.get( 'type' ) ).trigger( 'change:model', this );
			nfRadio.channel( 'fields' ).trigger( 'change:model', this );
		},

		changeValue: function() {
			nfRadio.channel( 'field-' + this.get( 'id' ) ).trigger( 'change:modelValue', this );
			nfRadio.channel( this.get( 'type' ) ).trigger( 'change:modelValue', this );
			nfRadio.channel( 'fields' ).trigger( 'change:modelValue', this );
		},

		addWrapperClass: function( cl ) {
			this.set( 'addWrapperClass', cl );
		},

		removeWrapperClass: function( cl ) {
			this.set( 'removeWrapperClass', cl );
		},

		setInvalid: function( invalid ) {
			this.set( 'invalid', invalid );
		},

		formLoaded: function() {
			nfRadio.channel( 'fields' ).trigger( 'formLoaded', this );
			nfRadio.channel( 'fields-' + this.get( 'type' ) ).trigger( 'formLoaded', this );
		},

		beforeSubmit: function( formModel ) {
			nfRadio.channel( this.get( 'type' ) ).trigger( 'before:submit', this );
			nfRadio.channel( 'fields' ).trigger( 'before:submit', this );
		},

		/**
		 * Return the value of this field.
		 * This method exists so that more complex fields can return more than just the field value.
		 * Those advanced fields should create their own method with this name.
		 * 
		 * @since  3.5
		 * @return {string} Value of this field.
		 */
		getValue: function() {
			return this.get( 'value' );
		}

	} );

	return model;
} );

define( 'models/fieldCollection',['models/fieldModel'], function( fieldModel ) {
	var collection = Backbone.Collection.extend( {
		model: fieldModel,
		comparator: 'order',

		initialize: function( models, options ) {
			this.options = options;
            this.on( 'reset', function( fieldCollection ){
                nfRadio.channel( 'fields' ).trigger( 'reset:collection', fieldCollection );
            }, this );
		},

		validateFields: function() {
			_.each( this.models, function( fieldModel ) {
				// added here for help with multi-part part validation
				fieldModel.set( 'clean', false );
				nfRadio.channel( 'submit' ).trigger( 'validate:field', fieldModel );
			}, this );
		},

		showFields: function() {
			this.invoke( 'set', { visible: true } );
            this.invoke( function() {
                this.trigger( 'change:value', this );
            });
		},

		hideFields: function() {
			this.invoke( 'set', { visible: false } );
            this.invoke( function() {
                this.trigger( 'change:value', this );
            });
		}
	} );
	return collection;
} );

define( 'models/formErrorModel',[], function() {
	var model = Backbone.Model.extend( {

	} );
	
	return model;
} );
define( 'models/formErrorCollection',['models/formErrorModel'], function( errorModel ) {
	var collection = Backbone.Collection.extend( {
		model: errorModel
	} );
	return collection;
} );
define( 'models/formModel',[
	'models/fieldCollection',
	'models/formErrorCollection'
	], function(
		FieldCollection,
		ErrorCollection
	) {
	var model = Backbone.Model.extend({
		defaults: {
			beforeForm: '',
			afterForm: '',
			beforeFields: '',
			afterFields: '',
			wrapper_class: '',
			element_class: '',
			hp: '',
			fieldErrors: {},
			extra: {}
		},

		initialize: function() {
			// Loop over settings and map to attributes
			_.each( this.get( 'settings' ), function( value, setting ) {
				this.set( setting, value );
			}, this );

			this.set( 'loadedFields', this.get( 'fields' ) );
			this.set( 'fields', new FieldCollection( this.get( 'fields' ), { formModel: this } ) );
			this.set( 'errors', new ErrorCollection() );

			/*
			 * Send out a radio message so that anyone who wants to filter our content data can register their filters.
			 */
			nfRadio.channel( 'form' ).trigger( 'before:filterData', this );

			/*
			 * Set our formContentData to our form setting 'formContentData'
			 */
			var formContentData = this.get( 'formContentData' );

			/*
			 * The formContentData variable used to be fieldContentsData.
			 * If we don't have a 'formContentData' setting, check to see if we have an old 'fieldContentsData'.
			 * 
			 * TODO: This is for backwards compatibility and should be removed eventually. 
			 */
			if ( ! formContentData ) {
				formContentData = this.get( 'fieldContentsData' );
			}
			
			var formContentLoadFilters = nfRadio.channel( 'formContent' ).request( 'get:loadFilters' );
			/* 
			* Get our first filter, this will be the one with the highest priority.
			*/
			var sortedArray = _.without( formContentLoadFilters, undefined );
			var callback = _.first( sortedArray );
			formContentData = callback( formContentData, this, this );
			
			this.set( 'formContentData', formContentData );

			nfRadio.channel( 'forms' ).trigger( 'init:model', this );
			nfRadio.channel( 'form-' + this.get( 'id' ) ).trigger( 'init:model', this );

			// Fields
			nfRadio.channel( 'form-' + this.get( 'id' ) ).reply( 'get:fieldByKey', this.getFieldByKey, this );

			// Form Errors
			nfRadio.channel( 'form-' + this.get( 'id' ) ).reply( 'add:error',    this.addError, this    );
			nfRadio.channel( 'form-' + this.get( 'id' ) ).reply( 'remove:error', this.removeError, this );

			// Extra Data
			nfRadio.channel( 'form-' + this.get( 'id' ) ).reply( 'get:extra',    this.getExtra,    this );
			nfRadio.channel( 'form-' + this.get( 'id' ) ).reply( 'add:extra',    this.addExtra,    this );
			nfRadio.channel( 'form-' + this.get( 'id' ) ).reply( 'remove:extra', this.removeExtra, this );
		
			// Respond to requests to get this model.
			nfRadio.channel( 'form-' + this.get( 'id' ) ).reply( 'get:form', 	 this.getForm, 	   this );

			nfRadio.channel( 'form' ).trigger( 'loaded', this );
			nfRadio.channel( 'form' ).trigger( 'after:loaded', this );
			nfRadio.channel( 'form-' + this.get( 'id' ) ).trigger( 'loaded', 	 this );
		},

		/*
		 |--------------------------------------------------------------------------
		 | Fields
		 |--------------------------------------------------------------------------
		 */

		getFieldByKey: function( key ) {
			return this.get( 'fields' ).findWhere( { key: key } );
		},

		/*
		 |--------------------------------------------------------------------------
		 | Form Errors
		 |--------------------------------------------------------------------------
		 */

		addError: function( id, msg ) {
			var errors = this.get( 'errors' );
			errors.add( { id: id, msg: msg } );
			nfRadio.channel( 'form-' + this.get( 'id' ) ).trigger( 'add:error', this, id, msg );
		},

		removeError: function( id ) {
			var errors = this.get( 'errors' );
			var errorModel = errors.get( id );
			errors.remove( errorModel );
			nfRadio.channel( 'form-' + this.get( 'id' ) ).trigger( 'remove:error', this, id );
		},

		/*
		 |--------------------------------------------------------------------------
		 | Extra Data
		 |--------------------------------------------------------------------------
		 */

		getExtra: function( key ) {
			var extraData = this.get( 'extra' );
			if( 'undefined' == typeof key ) return extraData;
			return extraData[ key ];
		},

		addExtra: function( key, value ) {
			var extraData = this.get( 'extra' );
			extraData[ key ] = value;
			nfRadio.channel( 'form-' + this.get( 'id' ) ).trigger( 'add:extra', this, key, value );
		},

		removeExtra: function( key ) {
			var extraData = this.get( 'extra' );
			delete extraData[ key ];
			nfRadio.channel( 'form-' + this.get( 'id' ) ).trigger( 'remove:extra', this, key );
		},

		/*
		 |--------------------------------------------------------------------------
		 | Get this form
		 |--------------------------------------------------------------------------
		 */
		getForm: function() {
			return this;
		}
	} );

	return model;
} );
define( 'models/formCollection',['models/formModel'], function( formModel ) {
	var collection = Backbone.Collection.extend( {
		model: formModel
	} );
	return collection;
} );
/*
 * Handles setting up our form.
 *
 * Holds a collection of our fields.
 * Replies to requests for field data.
 * Updates field models.
 */
define('controllers/formData',['models/formModel', 'models/formCollection', 'models/fieldCollection', 'models/formErrorCollection'], function( FormModel, FormCollection, FieldCollection, ErrorCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {

			/*
			 * Setup our field collections.
			 */
			var that = this;

			/*
			 * Initialize our form collection (incase we have multiple forms on the page)
			 */
			this.collection = new FormCollection( nfForms );

			nfRadio.channel( 'forms' ).trigger( 'loaded', this.collection );
			nfRadio.channel( 'app' ).trigger( 'forms:loaded', this.collection );

			nfRadio.channel( 'app' ).reply( 'get:form', this.getForm, this );
			nfRadio.channel( 'app' ).reply( 'get:forms', this.getForms, this );

			nfRadio.channel( 'fields' ).reply( 'get:field', this.getField, this );
		},

		getForm: function( id ) {
			return this.collection.get( id );
		},

		getForms: function() {
			return this.collection;
		},

		getField: function( id ) {
			var model = false;
			
			_.each( this.collection.models, function( form ) {
				if ( ! model ) {
					model = form.get( 'fields' ).get( id );	
				}			
			} );

			if(typeof model == "undefined"){
				model = nfRadio.channel( "field-repeater" ).request( 'get:repeaterFieldById', id );
			}
			
			return model;
		}
	});

	return controller;
} );

define('controllers/fieldError',['models/fieldErrorModel'], function( fieldErrorModel ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			nfRadio.channel( 'fields' ).reply( 'add:error', this.addError );
			nfRadio.channel( 'fields' ).reply( 'remove:error', this.removeError );
			nfRadio.channel( 'fields' ).reply( 'get:error', this.getError );
		},

		addError: function( targetID, id, msg ) {
			var model = nfRadio.channel( 'fields' ).request( 'get:field', targetID );

			if( 'undefined' == typeof model ) return;

			var errors = model.get( 'errors' );
			errors.add( { 'id': id, 'msg' : msg } );
			model.set( 'errors', errors );
			model.trigger( 'change:errors', model );
			model.set( 'clean', false );
			nfRadio.channel( 'fields' ).trigger( 'add:error', model, id, msg );
		},

		removeError: function( targetID, id ) {
			var model = nfRadio.channel( 'fields' ).request( 'get:field', targetID );

			if( 'undefined' == typeof model ) return;
			var errors = model.get( 'errors' );
			var targetError = errors.get( id );

			if ( 'undefined' != typeof targetError ) {
				errors.remove( targetError );
				model.set( 'errors', errors );
				model.trigger( 'change:errors', model );
				nfRadio.channel( 'fields' ).trigger( 'remove:error', model, id );
			}
		},

		getError: function( targetID, id ) {
			var model = nfRadio.channel( 'fields' ).request( 'get:field', targetID );
			var errors = model.get( 'errors' );
			var targetError = errors.get( id );
			if ( 'undefined' != targetError ) {
				return targetError;
			} else {
				return false;
			}
		}
	});

	return controller;
} );
/**
 * Controller responsible for replying to a Radio request stating that a field has been changed.
 *
 * This controller sends out a message to the field-specific channel, the field type channel,
 * and the public fields channel so that the data model can be updated.
 */

define('controllers/changeField',[], function() {
	var controller = Marionette.Object.extend( {

		initialize: function() {
			/*
			 * Reply to our request for changing a field.
			 */
			nfRadio.channel( 'nfAdmin' ).reply( 'change:field', this.changeField );

			/*
			 * If we blur our field, set the model attribute of 'clean' to false.
			 * 'clean' tracks whether or not the user has every interacted with this element.
			 * Some validation, like required, uses this to decide whether or not to add an error.
			 */
			this.listenTo( nfRadio.channel( 'fields' ), 'blur:field', this.blurField );
		},

		changeField: function( el, model ) {
			// Get our current value.
			var value = nfRadio.channel( model.get( 'type' ) ).request( 'before:updateField', el, model );
			value = ( 'undefined' != typeof value ) ? value : nfRadio.channel( model.get( 'parentType' ) ).request( 'before:updateField', el, model );
			value = ( 'undefined' != typeof value ) ? value : jQuery( el ).val();

			// Set our 'isUpdated' flag to false.
			model.set( 'isUpdated', false );

			// Set our 'clean' flag to false.
			model.set( 'clean', false );

			/*
			 * Send out a message saying that we've changed a field.
			 * The first channel is field id/key specific.
			 * The second channel is the field type, i.e. text, email, radio
			 * The third channel is a generic 'field' channel.
			 *
			 * If the submitted value you wish to store in the data model isn't the same as the value received above,
			 * you can set that model in the actions below and set the 'isUpdated' model attribute to true.
			 * i.e. model.set( 'isUpdated', true );
			 */
			nfRadio.channel( 'field-' + model.get( 'id' ) ).trigger( 'change:field', el, model );
			nfRadio.channel( model.get( 'type' ) ).trigger( 'change:field', el, model );
			nfRadio.channel( 'fields' ).trigger( 'change:field', el, model );

			/*
			 * Send a request out on our nfAdmin channel to update our field model.
			 * If the field model has a 'isUpdated' property of false, nothing will be updated.
			 */
			nfRadio.channel( 'nfAdmin' ).request( 'update:field', model, value );
		},

		blurField: function( el, model ) {
			// Set our 'clean' flag to false.
			model.set( 'clean', false );
		}
	});

	return controller;
} );
define('controllers/changeEmail',[], function() {
	var radioChannel = nfRadio.channel( 'email' );
	// var emailReg = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
	var emailReg = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	var errorID = 'invalid-email';

	var controller = Marionette.Object.extend( {

		initialize: function() {
			this.listenTo( radioChannel, 'change:modelValue', this.onChangeModelValue );
			this.listenTo( radioChannel, 'keyup:field', this.emailKeyup );
			this.listenTo( radioChannel, 'blur:field', this.onBlurField );
		},

		onChangeModelValue: function( model ) {
			var value = model.get( 'value' );
			var fieldID = model.get( 'id' );
			this.emailChange( value, fieldID );
		},

		onBlurField: function( el, model ) {
			var value = jQuery( el ).val();
			var fieldID = model.get( 'id' );
			this.emailChange( value, fieldID );
		},

		emailChange: function( value, fieldID ) {
			if ( 0 < value.length ) {
				if( emailReg.test( value ) ) {
					nfRadio.channel( 'fields' ).request( 'remove:error', fieldID, errorID );
				} else {
					var fieldModel = nfRadio.channel( 'fields' ).request( 'get:field', fieldID );
					var formModel  = nfRadio.channel( 'app'    ).request( 'get:form',  fieldModel.get( 'formID' ) );
					nfRadio.channel( 'fields' ).request( 'add:error', fieldID, errorID, formModel.get( 'settings' ).changeEmailErrorMsg );
				}				
			} else {
				nfRadio.channel( 'fields' ).request( 'remove:error', fieldID, errorID );
			}
		},

		/**
		 * When a user types inside of an email field, track their keypresses and add the appropriate class.
		 * If the value validates as an email, add a class of nf-pass
		 * If the value does not validate as email, add a class of nf-fail
		 * 
		 * @since  3.0
		 * @param  {object} el    Element that triggered the keyup event.
		 * @param  {object} model Model connected to the element that triggered the event
		 * @return {void}
		 */
		emailKeyup: function( el, model, keyCode ) {
			
			/*
			 * If we pressed the 'tab' key to get to this field, return false.
			 */
			if ( 9 == keyCode ) {
				return false;
			}
			/*
			 * Get the current value from our element.
			 */
			var value = jQuery( el ).val();

			/*
			 * Get our current ID
			 */
			var fieldID = model.get( 'id' );

			/*
			 * Check our value to see if it is a valid email.
			 */
			if ( 0 == value.length ) {
				nfRadio.channel( 'fields' ).request( 'remove:error', fieldID, errorID );
			} else if ( ! emailReg.test( value ) && ! model.get( 'clean' ) ) {

				var fieldModel = nfRadio.channel( 'fields' ).request( 'get:field', fieldID );
				var formModel  = nfRadio.channel( 'app'    ).request( 'get:form',  fieldModel.get( 'formID' ) );
				nfRadio.channel( 'fields' ).request( 'add:error', fieldID, errorID, formModel.get( 'settings' ).changeEmailErrorMsg );

				model.removeWrapperClass( 'nf-pass' );
			} else if ( emailReg.test( value ) ) {
				nfRadio.channel( 'fields' ).request( 'remove:error', fieldID, errorID );
				/*
				 * Add nf-pass class to the wrapper.
				 */
				model.addWrapperClass( 'nf-pass' );
				model.set( 'clean', false );
			}
		}
	});

	return controller;
} );
define('controllers/changeDate',[], function() {
	var radioChannel = nfRadio.channel( 'date' );
	var errorID = 'invalid-date';

	var controller = Marionette.Object.extend( {

		initialize: function() {
			this.listenTo( radioChannel, 'change:modelValue', this.onChangeModelValue );
			this.listenTo( radioChannel, 'keyup:field', this.dateKeyup );
			this.listenTo( radioChannel, 'blur:field', this.onBlurField );
			
			this.listenTo( radioChannel, 'change:extra', this.changeHoursMinutes, this)
		},

		onChangeModelValue: function( model ) {
			this.dateChange( model );
		},

		onBlurField: function( el, model ) {
			this.dateChange( model );
		},

		dateChange: function( model ) {
			var fieldID = model.get( 'id' );
			var value = model.get( 'value' );
			var format = model.get( 'date_format' );

			if( 'default' === format) {
				format = nfi18n.dateFormat;
			}

			// If we are dealing with purely a time field, bail early.
			if ( 'time_only' == model.get( 'date_mode' ) ) {
				return false;
			}

			if ( 0 < value.length ) {
				// use moment's isValid to check against the fields format setting
				if( moment( value, format ).isValid() ) {
					nfRadio.channel( 'fields' ).request( 'remove:error', fieldID, errorID );
				} else {
					var fieldModel = nfRadio.channel( 'fields' ).request( 'get:field', fieldID );
					var formModel  = nfRadio.channel( 'app'    ).request( 'get:form',  fieldModel.get( 'formID' ) );
					nfRadio.channel( 'fields' ).request( 'add:error', fieldID, errorID, formModel.get( 'settings' ).changeDateErrorMsg );
				}
			} else {
				nfRadio.channel( 'fields' ).request( 'remove:error', fieldID, errorID );
			}
		},

		/**
		 * When a user types inside of an dat field, track their keypresses
		 * and add the appropriate class.
		 * If the value validates as an date, add a class of nf-pass
		 * If the value does not validate as date, add a class of nf-fail
		 *
		 * @since  3.0
		 * @param  {object} el    Element that triggered the keyup event.
		 * @param  {object} model Model connected to the element that triggered the event
		 * @return {void}
		 */
		dateKeyup: function( el, model, keyCode ) {

			/*
			 * If we pressed the 'tab' key to get to this field, return false.
			 */
			if ( 9 == keyCode ) {
				return false;
			}
			/*
			 * Get the current value from our element.
			 */
			var value = jQuery( el ).val();

			/*
			 * Get our current ID
			 */
			var fieldID = model.get( 'id' );

			/*
			* Get our current date format
			 */
			var format = model.get( 'date_format' );

			if( 'default' === format) {
				format = nfi18n.dateFormat;
			}

			/*
			 * Check our value to see if it is a valid email.
			 */
			if ( 0 == value.length ) {
				nfRadio.channel( 'fields' ).request( 'remove:error', fieldID, errorID );
			}
			// use moment's isValid to check against the fields format setting
			else if ( ! moment( value, format ).isValid() && ! model.get( 'clean' ) ) {

				var fieldModel = nfRadio.channel( 'fields' ).request( 'get:field', fieldID );
				var formModel  = nfRadio.channel( 'app'    ).request( 'get:form',  fieldModel.get( 'formID' ) );
				nfRadio.channel( 'fields' ).request( 'add:error', fieldID, errorID, formModel.get( 'settings' ).changeDateErrorMsg );

				model.removeWrapperClass( 'nf-pass' );
			}
			// use moment's isValid to check against the fields format setting
			else if ( moment( value, format ).isValid() ) {
				nfRadio.channel( 'fields' ).request( 'remove:error', fieldID, errorID );
				/*
				 * Add nf-pass class to the wrapper.
				 */
				model.addWrapperClass( 'nf-pass' );
				model.set( 'clean', false );
			}
		},

		changeHoursMinutes: function( e, fieldModel ) {
			let type = '';
			let container = jQuery( e.target ).closest( '.nf-field-element' );

			// Set our hour, minute, and ampm
			let selected_hour = jQuery( container ).find( '.hour' ).val();
			let selected_minute = jQuery( container ).find( '.minute' ).val();
			let selected_ampm = jQuery( container ).find( '.ampm' ).val();

			fieldModel.set( 'selected_hour', selected_hour );
			fieldModel.set( 'selected_minute', selected_minute );
			fieldModel.set( 'selected_ampm', selected_ampm );
			// Trigger a change on our model.
			fieldModel.trigger( 'change:value', fieldModel );
		}
	});

	return controller;
} );
define('controllers/fieldCheckbox',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			/*
			 * When we init our checkbox model, register our renderClasses() function
			 */
			this.listenTo( nfRadio.channel( 'checkbox' ), 'init:model', this.registerRenderClasses );

			nfRadio.channel( 'checkbox' ).reply( 'validate:required', this.validateRequired );
			nfRadio.channel( 'checkbox' ).reply( 'validate:modelData', this.validateModelData );
            nfRadio.channel( 'checkbox' ).reply( 'before:updateField', this.beforeUpdateField, this );
            nfRadio.channel( 'checkbox' ).reply( 'get:calcValue', this.getCalcValue, this );
		},

		beforeUpdateField: function( el, model ) {
			var checked = jQuery( el ).prop( 'checked' );
			if ( checked ) {
				var value = 1;
				jQuery( el ).addClass( 'nf-checked' );
				jQuery( el ).closest( '.field-wrap' ).find( 'label[for="' + jQuery( el ).prop( 'id' ) + '"]' ).addClass( 'nf-checked-label' );
			} else {
				var value = 0;
				jQuery( el ).removeClass( 'nf-checked' );
				jQuery( el ).closest( '.field-wrap' ).find( 'label[for="' + jQuery( el ).prop( 'id' ) + '"]' ).removeClass( 'nf-checked-label' );
			}

			return value;
		},

		validateRequired: function( el, model ) {
			return el[0].checked;
		},

		validateModelData: function( model ) {
			return model.get( 'value' ) != 0;
		},

		getCalcValue: function( fieldModel ) {
			if ( 1 == fieldModel.get( 'value' ) ) {
				calcValue = fieldModel.get( 'checked_calc_value' );
			} else {
				calcValue = fieldModel.get( 'unchecked_calc_value' );
			}

			return calcValue;
		},

		registerRenderClasses: function( model ) {
			if ( 'checked' == model.get( 'default_value' ) ) {
				model.set( 'value', 1 );
			} else {
				model.set( 'value', 0 );
			}
			model.set( 'customClasses', this.customClasses );
			model.set( 'customLabelClasses', this.customLabelClasses );
			model.set( 'maybeChecked', this.maybeChecked );
		},

		customClasses: function( classes ) {
			if ( 1 == this.value || ( this.clean && 'undefined' != typeof this.default_value && 'checked' == this.default_value ) ) {
				classes += ' nf-checked';
			} else {
				classes.replace( 'nf-checked', '' );
			}
			return classes;
		},

		customLabelClasses: function( classes ) {
			if ( 1 == this.value || ( this.clean && 'undefined' != typeof this.default_value && 'checked' == this.default_value ) ) {
				classes += ' nf-checked-label';
			} else {
				classes.replace( 'nf-checked-label', '' );
			}
			return classes;
		},

		maybeChecked: function() {
			if ( 1 == this.value || ( this.clean && 'undefined' != typeof this.default_value && 'checked' == this.default_value ) ) {
				return ' checked';
			} else {
				return '';
			}
		}
	});

	return controller;
} );
define('controllers/fieldCheckboxList',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            this.listenTo( nfRadio.channel( 'listcheckbox' ), 'init:model', this.register );
            this.listenTo( nfRadio.channel( 'terms' ), 'init:model', this.register );
            nfRadio.channel( 'listcheckbox' ).reply( 'before:updateField', this.beforeUpdateField, this );
            nfRadio.channel( 'terms' ).reply( 'before:updateField', this.beforeUpdateField, this );
            nfRadio.channel( 'listcheckbox' ).reply( 'get:calcValue', this.getCalcValue, this );
            nfRadio.channel( 'terms' ).reply( 'get:calcValue', this.getCalcValue, this );
        },

        register: function( model ) {
            model.set( 'renderOptions', this.renderOptions );
            model.set( 'renderOtherText', this.renderOtherText );
            model.set( 'selected', [] );

            /*
             * When we init a model, we need to set our 'value' to the selected option's value.
             * This is the list equivalent of a 'default value'.
             */ 
            if ( 0 != model.get( 'options' ).length ) {
                var selected = _.filter( model.get( 'options' ), function( opt ) { return 1 == opt.selected } );
                selected = _.map( selected, function( opt ) { return opt.value } );
            }

            /*
            * This part is re-worked to take into account custom user-meta
            * values for fields.
             */
	        var savedVal = model.get( 'value' );
	        if( 'undefined' !== typeof savedVal && Array.isArray( savedVal ) ) {
		        model.set( 'value', savedVal );
	        } else if ( 'undefined' != typeof selected ) {
		        model.set( 'value', selected );
	        }
        },

        renderOptions: function() {
            var html = '';

            if ( '' == this.value || ( Array.isArray( this.value ) && 0 < this.value.length )
                || 0 < this.value.length ) {
                var valueFound = true;
            } else {
                var valueFound = false;
            }

            _.each( this.options, function( option, index ) {
                if( Array.isArray( this.value ) ) {
                	if( Array.isArray( this.value[ 0 ] ) && -1 !== _.indexOf( this.value[ 0 ], option.value ) ) {
                		valueFound = true;
	                }
                    else if( _.indexOf( this.value, option.value ) ) {
                        valueFound = true;
	                }
                }

                if ( option.value == this.value ) {
                    valueFound = true;
                }

                /*
                 * TODO: This is a bandaid fix for making sure that each option has a "visible" property.
                 * This should be moved to creation so that when an option is added, it has a visible property by default.
                 */
                if ( 'undefined' == typeof option.visible ) {
                    option.visible = true;
                }

                option.fieldID = this.id;
                option.classes = this.classes;
                option.index = index;

                var selected = false;
				/*
				* This part has been re-worked to account for values passed in
				* via custom user-meta ( a la User Mgmt add-on)
				 */
	            if( Array.isArray( this.value ) && 0 < this.value.length ) {
	            	if ( -1 !== _.indexOf( this.value[ 0 ].split( ',' ), option.value )
		                || -1 !== _.indexOf( this.value, option.value ) ) {
			            selected = true;
	            	}
	            } else if ( ! _.isArray( this.value ) && option.value == this.value ) {
		            selected = true;
	            } else if ( ( 1 == option.selected && this.clean ) && 'undefined' === typeof this.value ) {
		            selected = true;
	            }


                // else if( ( option.selected && "0" != option.selected ) && this.clean ){
	            //     isSelected = true;
	            // } else {
	            //     var testValues = _.map( this.value, function( value ) {
	            //         return value.toString();
	            //     } );
	            //
	            //     option.isSelected = ( -1 != testValues.indexOf( option.value.toString() ) );
	            // }
	            option.selected = selected;
	            option.isSelected = selected;
	            option.required = this.required;
                var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-listcheckbox-option' );
                html += template( option );
            }, this );

            if ( 1 == this.show_other ) {
                if ( 'nf-other' == this.value ) {
                    valueFound = false;
                }
                var data = {
                    fieldID: this.id,
                    classes: this.classes,
                    currentValue: this.value,
                    renderOtherText: this.renderOtherText,
                    valueFound: valueFound
                };

                var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-listcheckbox-other' );
                html += template( data );

            }

            return html;
        },

        renderOtherText: function() {
            if ( 'nf-other' == this.currentValue || ! this.valueFound ) {
                if ( 'nf-other' == this.currentValue ) {
                    this.currentValue = '';
                }
                var data = {
                    fieldID: this.fieldID,
                    classes: this.classes,
                    currentValue: this.currentValue
                };
                var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-listcheckbox-other-text' );
                return template( data );
            }
        },

        getCalcValue: function( fieldModel ) {
            var calc_value = 0;
            var options = fieldModel.get( 'options' );
            if ( 0 != options.length ) {
                _.each( fieldModel.get( 'value' ), function( val ) {
                    var tmp_opt = _.find( options, function( opt ) { return opt.value == val } );
                    calc_value = Number( calc_value ) + Number( tmp_opt.calc );
                } );
            }
            return calc_value;
        },

        beforeUpdateField: function( el, model ) {
            var selected = model.get( 'value' ) || [];
            if ( typeof selected == 'string' ) selected = [ selected ];

            var value = jQuery( el ).val();
            var checked = jQuery( el ).prop( 'checked' );
            if ( checked ) {
                selected.push( value );
                jQuery( el ).addClass( 'nf-checked' );
                jQuery( el ).parent().find( 'label[for="' + jQuery( el ).prop( 'id' ) + '"]' ).addClass( 'nf-checked-label' );
            } else {
                jQuery( el ).removeClass( 'nf-checked' );
                jQuery( el ).parent().find( 'label[for="' + jQuery( el ).prop( 'id' ) + '"]' ).removeClass( 'nf-checked-label' );
                var i = selected.indexOf( value );
                if( -1 != i ){
                    selected.splice( i, 1 );
                } else if ( Array.isArray( selected ) ) {
                	var optionArray = selected[0].split( ',' );
                	var valueIndex = optionArray.indexOf( value );
                	if( -1 !== valueIndex) {
                		optionArray.splice( valueIndex, 1 );
	                }
                	selected = optionArray.join( ',' );
                }
            }

            // if ( 1 == model.get( 'show_other' ) ) {
            //     model.set( 'reRender', true );
            // }

            return _.clone( selected );
        }
    });

    return controller;
} );
define('controllers/fieldImageList',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            this.listenTo( nfRadio.channel( 'listimage' ), 'init:model', this.register );
            nfRadio.channel( 'listimage' ).reply( 'before:updateField', this.beforeUpdateField, this );
            nfRadio.channel( 'listimage' ).reply( 'get:calcValue', this.getCalcValue, this );
        },

        register: function( model ) {
            model.set( 'renderOptions', this.renderOptions );
            model.set( 'renderOtherText', this.renderOtherText );
            model.set( 'selected', [] );

            /*
             * When we init a model, we need to set our 'value' to the selected option's value.
             * This is the list equivalent of a 'default value'.
             */ 
            if ( 0 != model.get( 'image_options' ).length ) {
                var selected = _.filter( model.get( 'image_options' ), function( opt ) { return 1 == opt.selected } );
                selected = _.map( selected, function( opt ) { return opt.value } );
            }

            /*
            * This part is re-worked to take into account custom user-meta
            * values for fields.
             */
	        var savedVal = model.get( 'value' );
	        if( 'undefined' !== typeof savedVal && Array.isArray( savedVal ) ) {
		        model.set( 'value', savedVal );
	        } else if ( 'undefined' != typeof selected ) {
		        model.set( 'value', selected );
	        }
        },

        renderOptions: function() {
            var html = '';
            
            if ( '' == this.value || ( Array.isArray( this.value ) && 0 < this.value.length )
                || 0 < this.value.length ) {
                var valueFound = true;
            } else {
                var valueFound = false;
            }

            if (this.allow_multi_select === 1) {
                this.old_classname = 'list-checkbox';
                this.image_type = 'checkbox';
            } else {
                this.image_type = 'radio';
            }

            if(this.list_orientation === 'horizontal') {
                this.flex_direction = 'row';
            } else {
                this.flex_direction = 'column';
            }
            var that = this;

            var num_columns = parseInt(this.num_columns) || 1;
            var current_column = 1;
            var current_row = 1;
            
            _.each( this.image_options, function( image, index ) {
                if (!this.show_option_labels) {
                    image.label = '';
                }
                if( Array.isArray( this.value ) ) {
                	if( Array.isArray( this.value[ 0 ] ) && -1 !== _.indexOf( this.value[ 0 ], image.value ) ) {
                		valueFound = true;
	                }
                    else if( _.indexOf( this.value, image.value ) ) {
                        valueFound = true;
	                }
                }

                if ( image.value == this.value ) {
                    valueFound = true;
                }

                /*
                 * TODO: This is a bandaid fix for making sure that each option has a "visible" property.
                 * This should be moved to creation so that when an option is added, it has a visible property by default.
                 */
                if ( 'undefined' == typeof image.visible ) {
                    image.visible = true;
                }
                
                if(that.list_orientation === 'horizontal' && current_column <= num_columns) {
                    image.styles = "margin:auto;grid-column: " + current_column + "; grid-row = " + current_row;

                    if(current_column === num_columns) {
                        current_column = 1;
                        current_row += 1;
                    } else {
                        current_column += 1;
                    }
                }

                image.image_type = that.image_type; 
                image.fieldID = this.id;
                image.classes = this.classes;
                image.index = index;

                var selected = false;
				/*
				* This part has been re-worked to account for values passed in
				* via custom user-meta ( a la User Mgmt add-on)
				 */
	            if( Array.isArray( this.value ) && 0 < this.value.length ) {
	            	if ( -1 !== _.indexOf( this.value[ 0 ].split( ',' ), image.value )
		                || -1 !== _.indexOf( this.value, image.value ) ) {
			            selected = true;
	            	}
	            } else if ( ! _.isArray( this.value ) && image.value == this.value ) {
		            selected = true;
	            } else if ( ( 1 == image.selected && this.clean ) && ('undefined' === typeof this.value || '' === this.value)) {
		            selected = true;
	            }

	            image.selected = selected;
	            image.isSelected = selected;
	            image.required = this.required;
                var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-listimage-option' );
                html += template( image );
            }, this );

            if ( 1 == this.show_other ) {
                if ( 'nf-other' == this.value ) {
                    valueFound = false;
                }
                var data = {
                    fieldID: this.id,
                    classes: this.classes,
                    value: this.value,
                    currentValue: this.value,
                    renderOtherText: this.renderOtherText,
                    valueFound: valueFound
                };

                var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-listimage-other' );
                html += template( data );

            }

            return html;
        },

        renderOtherText: function() {
            if ( 'nf-other' == this.currentValue || ! this.valueFound ) {
                if ( 'nf-other' == this.currentValue ) {
                    this.currentValue = '';
                }
                var data = {
                    fieldID: this.fieldID,
                    classes: this.classes,
                    currentValue: this.currentValue
                };
                var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-listimage-other-text' );
                return template( data );
            }
        },

        getCalcValue: function( fieldModel ) {
			var calc_value = 0;
			var options = fieldModel.get( 'options' );
			if ( 0 != options.length ) {
				/*
				 * Check to see if this is a multi-select list.
				 */
				if ( 1 == parseInt( fieldModel.get( 'allow_multi_select' ) ) ) {
					/*
					 * We're using a multi-select, so we need to check out any selected options and add them together.
					 */
					_.each( fieldModel.get( 'value' ), function( val ) {
						var tmp_opt = _.find( options, function( opt ) { return opt.value == val } );
						calc_value += Number( tmp_opt.calc );
					} );
				} else {
					/*
					 * We are using a single select, so our selected option is in the 'value' attribute.
					 */
					var selected = _.find( options, function( opt ) { return fieldModel.get( 'value' ) == opt.value } );
					/*
					 * If we have a selcted value, use it.
					 */
					if ( 'undefined' !== typeof selected ) {
                        calc_value = selected.calc;
					}	
				}
			}
			return calc_value;
        },

        beforeUpdateField: function( el, model ) {

            if(model.get('allow_multi_select') !== 1) {
                var selected = jQuery( el ).val();
                var options = model.get('image_options');
                _.each(options, function(option, index) {
                    if(option.value === selected) {
                        option.isSelected = true;
                        option.selected = true;
                    } else {
                        option.isSelected = false;
                        option.selected = false;
                    }
                    if(!option.isSelected) {
                        option.selected = false;
                        jQuery("#nf-field-" + option.fieldID + "-" + index).removeClass('nf-checked');
                        jQuery("#nf-label-field-" + option.fieldID + "-" + index).removeClass('nf-checked-label');
                    } else {
                        jQuery("#nf-field-" + option.fieldID + "-" + index).addClass('nf-checked');
                        jQuery("#nf-label-field-" + option.fieldID + "-" + index).addClass('nf-checked-label');
                    }
                });
            } else {
                var selected = model.get( 'value' ) || [];
                if ( typeof selected == 'string' ) selected = [ selected ];
                var value = jQuery( el ).val();
                var checked = jQuery( el ).prop( 'checked' );
                if ( checked ) {
                    selected.push( value );
                    jQuery( el ).addClass( 'nf-checked' );
                    jQuery( el ).parent().find( 'label[for="' + jQuery( el ).prop( 'id' ) + '"]' ).addClass( 'nf-checked-label' );
                } else {
                    jQuery( el ).removeClass( 'nf-checked' );
                    jQuery( el ).parent().find( 'label[for="' + jQuery( el ).prop( 'id' ) + '"]' ).removeClass( 'nf-checked-label' );
                    var i = selected.indexOf( value );
                    if( -1 != i ){
                        selected.splice( i, 1 );
                    } else if ( Array.isArray( selected ) ) {
                        var optionArray = selected[0].split( ',' );
                        var valueIndex = optionArray.indexOf( value );
                        if( -1 !== valueIndex) {
                            optionArray.splice( valueIndex, 1 );
                        }
                        selected = optionArray.join( ',' );
                    }
                }
            }

            return _.clone( selected );
        }
    });

    return controller;
} );
define('controllers/fieldRadio',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'listradio' ), 'change:modelValue', this.changeModelValue );
			this.listenTo( nfRadio.channel( 'listradio' ), 'init:model', this.register );
			nfRadio.channel( 'listradio' ).reply( 'get:calcValue', this.getCalcValue, this );
			
			this.listenTo( nfRadio.channel( 'listradio' ), 'change:field', this.updateCheckedClass, this );
		},

		register: function( model ) {
			model.set( 'renderOptions', this.renderOptions );
			model.set( 'renderOtherText', this.renderOtherText );
			/*
			 * When we init a model, we need to set our 'value' to the selected option's value.
			 * This is the list equivalent of a 'default value'.
			 */ 
			if ( 0 != model.get( 'options' ).length ) {
				/*
				 * Check to see if we have a selected value.
				 */
				var selected = _.find( model.get( 'options' ), function( opt ) { return 1 == opt.selected } );

				if ( 'undefined' != typeof selected ) {
					model.set( 'value', selected.value );
				}
			}
		},

		changeModelValue: function( model ) {
			if ( 1 == model.get( 'show_other' ) ) {
				// model.set( 'reRender', true );
				model.trigger( 'reRender');
			}
		},

		renderOptions: function() {
			var html = '';
			if ( '' == this.value ) {
				var valueFound = true;
			} else {
				var valueFound = false;
			}
			
			_.each( this.options, function( option, index ) {
				if ( option.value == this.value ) {
					valueFound = true;
				}

				/*
                 * TODO: This is a bandaid fix for making sure that each option has a "visible" property.
                 * This should be moved to creation so that when an option is added, it has a visible property by default.
                 */
                if ( 'undefined' == typeof option.visible ) {
                    option.visible = true;
                }

                option.selected = false;
				option.fieldID = this.id;
				option.classes = this.classes;
				option.currentValue = this.value;
				option.index = index;
				option.required = this.required;

				/*
				 * If we haven't edited this field yet, use the default checked
				 */
				if ( this.clean && 1 == this.selected ) {
					option.selected = true;
				} else if ( this.value == option.value ) {
					option.selected = true;
				} else {
					option.selected = false;
				}

				var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-listradio-option' );

				html += template( option );
			}, this );

			if ( 1 == this.show_other ) {
				if ( 'nf-other' == this.value ) {
					valueFound = false;
				}
				var data = {
					fieldID: this.id,
					classes: this.classes,
					currentValue: this.value,
					renderOtherText: this.renderOtherText,
					valueFound: valueFound
				};
				var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-listradio-other' );
				html += template( data );
			}

			return html;
		},

		renderOtherText: function() {
			if ( 'nf-other' == this.currentValue || ! this.valueFound ) {
				if ( 'nf-other' == this.currentValue ) {
					this.currentValue = '';
				}
				var data = {
					fieldID: this.fieldID,
					classes: this.classes,
					currentValue: this.currentValue
				};
				var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-listradio-other-text' );
				return template( data );
			}
		},

		getCalcValue: function( fieldModel ) {
			
            /*
             * Default to 0, in case we have no selection.
             */
            var calc_value = 0;
            
			if ( 0 != fieldModel.get( 'options' ).length ) {
				/*
				 * Check to see if we have a selected value.
				 */
				var selected = _.find( fieldModel.get( 'options' ), function( opt ) { return fieldModel.get( 'value' ) == opt.value } );
				if ( 'undefined' !== typeof selected ) {
                    calc_value = selected.calc;
				}

			}
			return calc_value;
		},

		updateCheckedClass: function( el, model ) {
			jQuery( '[name="' + jQuery( el ).attr( 'name' ) + '"]' ).removeClass( 'nf-checked' );
			jQuery( el ).closest( 'ul' ).find( 'label' ).removeClass( 'nf-checked-label' );
			jQuery( el ).addClass( 'nf-checked' );
			jQuery( el ).closest( 'li' ).find( 'label[for="' + jQuery( el ).prop( 'id' ) + '"]' ).addClass( 'nf-checked-label' );


		}

	});

	return controller;
} );
define('controllers/fieldNumber',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            this.listenTo( nfRadio.channel( 'number' ), 'init:model', this.maybeMinDefault );
            this.listenTo( nfRadio.channel( 'number' ), 'keyup:field', this.validateMinMax );
        },

        maybeMinDefault: function( model ) {

            if( '' == model.get( 'value' ) && '' == model.get( 'placeholder' ) ){
                var min = model.get( 'num_min' );
                model.set( 'placeholder', min );
            }
        },

        validateMinMax: function( el, model ) {
            var $el = jQuery( el );
            var value = parseFloat( $el.val() );
            var min = $el.attr( 'min' );
            var max = $el.attr( 'max' );
            var step = parseFloat( $el.attr( 'step' ) );

            if( min && value < min ){
                var fieldModel = nfRadio.channel( 'fields' ).request( 'get:field', model.get( 'id' ) );
                var formModel  = nfRadio.channel( 'app'    ).request( 'get:form',  fieldModel.get( 'formID' ) );
                nfRadio.channel( 'fields' ).request( 'add:error', model.get( 'id' ), 'number-min', formModel.get( 'settings' ).fieldNumberNumMinError );
            } else {
                nfRadio.channel( 'fields' ).request( 'remove:error', model.get( 'id' ), 'number-min' );
            }

            if ( max && value > max ){
                var fieldModel = nfRadio.channel( 'fields' ).request( 'get:field', model.get( 'id' ) );
                var formModel  = nfRadio.channel( 'app'    ).request( 'get:form',  fieldModel.get( 'formID' ) );
                nfRadio.channel( 'fields' ).request( 'add:error', model.get( 'id' ), 'number-max', formModel.get( 'settings' ).fieldNumberNumMaxError );
            } else {
                nfRadio.channel( 'fields' ).request( 'remove:error', model.get( 'id' ), 'number-max' );
            }

            var testValue = Math.round( parseFloat( value ) * 1000000000 );
            var testStep = Math.round( parseFloat( step ) * 1000000000  );

            if( value && 0 !== testValue % testStep ){
                var fieldModel = nfRadio.channel( 'fields' ).request( 'get:field', model.get( 'id' ) );
                var formModel  = nfRadio.channel( 'app'    ).request( 'get:form',  fieldModel.get( 'formID' ) );
                nfRadio.channel( 'fields' ).request( 'add:error', model.get( 'id' ), 'number-step', formModel.get( 'settings' ).fieldNumberIncrementBy + step );
            } else {
                nfRadio.channel( 'fields' ).request( 'remove:error', model.get( 'id' ), 'number-step' );
            }
        }

    });

    return controller;
} );
define( 'controllers/mirrorField',[], function() {
	var radioChannel = nfRadio.channel( 'fields' );

	var controller = Marionette.Object.extend( {
		listeningModel: '',

		initialize: function() {
			this.listenTo( radioChannel, 'init:model', this.registerMirror );
		},

		registerMirror: function( model ) {
			if ( model.get( 'mirror_field' ) ) {
				this.listeningModel = model;
				var targetID = model.get( 'mirror_field' );
				this.listenTo( nfRadio.channel( 'field-' + targetID ), 'change:modelValue', this.changeValue );
			}
		},

		changeValue: function( targetModel ) {
			this.listeningModel.set( 'value', targetModel.get( 'value' ) );
			// this.listeningModel.set( 'reRender', true );
			this.listeningModel.trigger( 'reRender' );
		}
	});

	return controller;
} );
define( 'controllers/confirmField',[], function() {
	var radioChannel = nfRadio.channel( 'fields' );
	var errorID = 'confirm-mismatch';

	var controller = Marionette.Object.extend( {

		initialize: function() {
			this.listenTo( radioChannel, 'init:model', this.registerConfirm );
			this.listenTo( radioChannel, 'keyup:field', this.confirmKeyup );
		},

		registerConfirm: function( confirmModel ) {
			if ( ! confirmModel.get( 'confirm_field' ) ) return;

			this.listenTo( nfRadio.channel( 'form' ), 'loaded', function( formModal ){
				this.registerConfirmListeners( confirmModel );
			});
		},

		registerConfirmListeners: function( confirmModel ) {
			
			var targetModel = nfRadio.channel( 'form-' + confirmModel.get( 'formID' ) ).request( 'get:fieldByKey', confirmModel.get( 'confirm_field' ) );

			//TODO: Add better handling for password confirm fields on the front end.
			if( 'undefined' == typeof targetModel ) return;

			targetModel.set( 'confirm_with', confirmModel.get( 'id' ) );
			this.listenTo( nfRadio.channel( 'field-' + targetModel.get( 'id' ) ), 'change:modelValue', this.changeValue );
			this.listenTo( nfRadio.channel( 'field-' + confirmModel.get( 'id' ) ), 'change:modelValue', this.changeValue );
		},

		changeValue: function( model ) {
			if ( 'undefined' == typeof model.get( 'confirm_with' ) ) {
				var confirmModel = model;
				var targetModel = nfRadio.channel( 'form-' + model.get( 'formID' ) ).request( 'get:fieldByKey', confirmModel.get( 'confirm_field' ) );
			} else {
				var targetModel = model;
				var confirmModel = radioChannel.request( 'get:field', targetModel.get( 'confirm_with' ) );
			}
			var targetID = targetModel.get( 'id' );
			var confirmID = confirmModel.get( 'id' );

			if ( '' == confirmModel.get( 'value' ) || confirmModel.get( 'value' ) == targetModel.get( 'value' ) ) {
				nfRadio.channel( 'fields' ).request( 'remove:error', confirmID, errorID );
			} else {
				var fieldModel = nfRadio.channel( 'fields' ).request( 'get:field', confirmID );
				var formModel  = nfRadio.channel( 'app'    ).request( 'get:form',  fieldModel.get( 'formID' ) );
				nfRadio.channel( 'fields' ).request( 'add:error', confirmID, errorID, formModel.get( 'settings' ).confirmFieldErrorMsg );
			}
		},
		
		confirmKeyup: function( el, model, keyCode ) {

			var currentValue = jQuery( el ).val();
			if ( model.get( 'confirm_field' ) ) {
				var confirmModel = model;
				var confirmID = model.get( 'id' );
				var targetModel = nfRadio.channel( 'form-' + model.get( 'formID' ) ).request( 'get:fieldByKey', confirmModel.get( 'confirm_field' ) );
				var compareValue = targetModel.get( 'value' );
				var confirmValue = currentValue;
			} else if ( model.get( 'confirm_with' ) ) {
				var confirmModel = nfRadio.channel( 'fields' ).request( 'get:field', model.get( 'confirm_with' ) );
				var confirmID = confirmModel.get( 'id' );
				var confirmValue = confirmModel.get( 'value' );
				var compareValue = confirmValue;
			}

			if ( 'undefined' !== typeof confirmModel ) {
				if ( '' == confirmValue ) {
					nfRadio.channel( 'fields' ).request( 'remove:error', confirmID, errorID );
				} else if ( currentValue == compareValue ) {
					nfRadio.channel( 'fields' ).request( 'remove:error', confirmID, errorID );
				} else {
					var fieldModel = nfRadio.channel( 'fields' ).request( 'get:field', confirmID );
					var formModel  = nfRadio.channel( 'app'    ).request( 'get:form',  fieldModel.get( 'formID' ) );
					nfRadio.channel( 'fields' ).request( 'add:error', confirmID, errorID, formModel.get( 'settings' ).confirmFieldErrorMsg );
				}
			}
		}
	});

	return controller;
} );
define('controllers/updateFieldModel',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			nfRadio.channel( 'nfAdmin' ).reply( 'update:field', this.updateField );
		},

		updateField: function( model, value ) {
			if ( ! model.get( 'isUpdated' ) ) {
				model.set( 'value', value );
				model.set( 'isUpdated', true );
				/*
				 * If we're working with an array, it won't trigger a change event on the value attribute.
				 * Instead, we have to manually trigger a change event.
				 */ 
				if ( _.isArray( value ) ) {
					model.trigger( 'change:value', model );
				}
			}
		}
	});

	return controller;
} );
define('controllers/submitButton',['controllers/submitButton'], function( submitButton ) {
	var controller = Marionette.Object.extend( {
		bound: {},

		initialize: function() {
			this.listenTo( nfRadio.channel( 'submit' ), 'init:model', this.registerHandlers );
		},

		registerHandlers: function( fieldModel ) {
			if ( 'undefined' != typeof this.bound[ fieldModel.get( 'id' ) ] ) {
				return false;
			}

			this.listenTo( nfRadio.channel( 'field-' + fieldModel.get( 'id' ) ), 'click:field', this.click, this );
			/*
			 * Register an interest in the 'before:submit' event of our form.
			 */
			fieldModel.listenTo( nfRadio.channel( 'form-' + fieldModel.get( 'formID' ) ), 'before:submit', this.beforeSubmit, fieldModel );
			fieldModel.listenTo( nfRadio.channel( 'form-' + fieldModel.get( 'formID' ) ), 'submit:failed', this.resetLabel, fieldModel );
			fieldModel.listenTo( nfRadio.channel( 'form-' + fieldModel.get( 'formID' ) ), 'submit:response', this.resetLabel, fieldModel );
			fieldModel.listenTo( nfRadio.channel( 'form-' + fieldModel.get( 'formID' ) ), 'enable:submit', this.maybeEnable, fieldModel );
			fieldModel.listenTo( nfRadio.channel( 'form-' + fieldModel.get( 'formID' ) ), 'disable:submit', this.maybeDisable, fieldModel );
			fieldModel.listenTo( nfRadio.channel( 'form-' + fieldModel.get( 'formID' ) ), 'processingLabel', this.processingLabel, fieldModel );

			fieldModel.listenTo( nfRadio.channel( 'fields' ), 'add:error', this.maybeDisable, fieldModel );
			fieldModel.listenTo( nfRadio.channel( 'fields' ), 'remove:error', this.maybeEnable, fieldModel );
			
			this.bound[ fieldModel.get( 'id') ] = true;
		},

		click: function( e, fieldModel ) {
			var formModel = nfRadio.channel( 'app' ).request( 'get:form', fieldModel.get( 'formID' ) );
			nfRadio.channel( 'form-' + fieldModel.get( 'formID' ) ).request( 'submit', formModel );
		},

		beforeSubmit: function() {
			this.set( 'disabled', true );
			nfRadio.channel( 'form-' + this.get( 'formID' ) ).trigger( 'processingLabel', this );
		},

		maybeDisable: function( fieldModel ) {

			if( 'undefined' != typeof fieldModel && fieldModel.get( 'formID' ) != this.get( 'formID' ) ) return;

			this.set( 'disabled', true );
			this.trigger( 'reRender' );
		},

		maybeEnable: function( fieldModel ) {
			/*
			 * If the field reporting the error is not on the same form as the submit button, return false;
			 */
			if ( 'undefined' != typeof fieldModel && fieldModel.get( 'formID' ) != this.get( 'formID' ) ) {
				return false;
			}
			
			var formModel = nfRadio.channel( 'app' ).request( 'get:form', this.get( 'formID' ) );
			if ( 0 == _.size( formModel.get( 'fieldErrors' ) ) ) {
				this.set( 'disabled', false );
				this.trigger( 'reRender' );
			}
		},

		processingLabel: function() {
			if ( this.get( 'label' ) == this.get( 'processing_label' ) ) return false;

			this.set( 'oldLabel', this.get( 'label' ) );
			this.set( 'label', this.get( 'processing_label' ) );
			this.trigger( 'reRender' );
		},

		resetLabel: function( response ) {
			if ( 'undefined' != typeof response.errors &&
				 'undefined' != typeof response.errors.nonce &&
				 _.size( response.errors.nonce ) > 0 ) {
				if( 'undefined' != typeof response.errors.nonce.new_nonce && 'undefined' != typeof response.errors.nonce.nonce_ts ) {
					// Do not reset label for nonce errors, which will re-submit the form.
					return;
				}
			}
			if ( 'undefined' != typeof this.get( 'oldLabel' ) ) {
				this.set( 'label', this.get( 'oldLabel' ) );
			}
			this.set( 'disabled', false );
			this.trigger( 'reRender' );
		}

	});

	return controller;
} );
define('controllers/submitDebug',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            this.listenTo( nfRadio.channel( 'forms' ), 'submit:response', this.submitDebug );
        },

        submitDebug: function( response, textStatus, jqXHR, formID ) {

            if( 'undefined' == typeof response.debug ) return;

            /* Form Debug Messages */
            if( 'undefined' != typeof response.debug.form ) {
                var debugMessages = document.createElement( 'span' );
                _.each(response.debug.form, function (message, index) {
                    var messageText = document.createTextNode( message );
                    debugMessages.appendChild( messageText );
                    debugMessages.appendChild(
                        document.createElement( 'br' )
                    );
                });
                jQuery('.nf-debug-msg').html( debugMessages );
            }

            /* Console Debug Messages */
            if( 'undefined' != typeof response.debug.console ) {
                var style = '';
                console.log( '%c%s', style, 'NINJA SUPPORT' );
                _.each(response.debug.console, function (message, index) {
                    console.log( message );
                });
                console.log( '%c%s', style, 'END NINJA SUPPORT' );
            }
        }

    });

    return controller;
} );

define('controllers/getFormErrors',[], function() {
	var radioChannel = nfRadio.channel( 'fields' );
	var controller = Marionette.Object.extend( {
		initialize: function( model ) {
			nfRadio.channel( 'form' ).reply( 'get:errors', this.getFormErrors );
		},

		getFormErrors: function( formID ) {
			var formModel = nfRadio.channel( 'app' ).request( 'get:form', formID );
			var errors = false;
			
			if ( formModel ) {
				/*
				 * Check to see if we have any errors on our form model.
				 */
				if ( 0 !== formModel.get( 'errors' ).length ) {
					_.each( formModel.get( 'errors' ).models, function( error ) {
						errors = errors || {};
						errors[ error.get( 'id' ) ] = error.get( 'msg' );
					} );						
				}

				_.each( formModel.get( 'fields' ).models, function( field ) {
					if ( field.get( 'type' ) != 'submit' && field.get( 'errors' ).length > 0 ) {
						errors = errors || {};
						errors[ field.get( 'id' ) ] = field.get( 'errors' );
					}
				} );
			}
			return errors;
		},
	});

	return controller;
} );
define('controllers/validateRequired',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'fields' ), 'blur:field', this.validateRequired );
			this.listenTo( nfRadio.channel( 'fields' ), 'change:field', this.validateRequired );
			this.listenTo( nfRadio.channel( 'fields' ), 'keyup:field', this.validateKeyup );

			this.listenTo( nfRadio.channel( 'fields' ), 'change:modelValue', this.validateModelData );
			this.listenTo( nfRadio.channel( 'submit' ), 'validate:field', this.validateModelData );
		},
		
		validateKeyup: function( el, model, keyCode ) {
			if ( 1 != model.get( 'required' ) ) {
				return false;
			}

			if ( ! model.get( 'clean' ) ) {
				this.validateRequired( el, model );
			}
		},

		validateRequired: function( el, model ) {
			if ( 1 != model.get( 'required' ) || ! model.get( 'visible' ) ) {
				return false;
			}

			var currentValue = jQuery( el ).val();
			var customReqValidation = nfRadio.channel( model.get( 'type' ) ).request( 'validate:required', el, model );
			var defaultReqValidation = true;

			var maskPlaceholder = model.get( 'mask' );
			if ( maskPlaceholder ) {
				maskPlaceholder = maskPlaceholder.replace( /9/g, '_' );
				maskPlaceholder = maskPlaceholder.replace( /a/g, '_' );
				maskPlaceholder = maskPlaceholder.replace( /\*/g, '_' );
			}

            // If the field has a mask...
            // AND that mask is equal to the current value...            
            if ( maskPlaceholder && currentValue === maskPlaceholder ) {
                // If we have a pre-existing error...
                if ( 0 < model.get( 'errors' ).length ) {
                    // Persist that error.
                    defaultReqValidation = false;
                }
            }
            // If our value is an empty string...
            if ( ! jQuery.trim( currentValue ) ) {
                // Throw an error.
                defaultReqValidation = false;
            }

			if ( 'undefined' !== typeof customReqValidation ) {
				var valid = customReqValidation;
			} else {
				var valid = defaultReqValidation;
			}

			this.maybeError( valid, model );
		},

		validateModelData: function( model ) {

			if ( 1 != model.get( 'required' ) || ! model.get( 'visible' ) || model.get( 'clean' ) ) {
				return false;
			}

			/*
			 * If we already have a required error on this model, return false
			 */
			if ( model.get( 'errors' ).get( 'required-error' ) ) {
				return false;
			}

			currentValue = model.get( 'value' );

			var defaultReqValidation = true;

			if ( ! jQuery.trim( currentValue ) ) {
				defaultReqValidation = false;
			}

			var customReqValidation = nfRadio.channel( model.get( 'type' ) ).request( 'validate:modelData', model );
			if ( 'undefined' !== typeof customReqValidation ) {
				var valid = customReqValidation;
			} else {
				var valid = defaultReqValidation;
			}

			this.maybeError( valid, model );

		},

		maybeError: function( valid, model ) {
			if ( ! valid ) {

				var formModel  = nfRadio.channel( 'form-' + model.get( 'formID' ) ).request( 'get:form' );

				if( 'undefined' != typeof formModel ) {
					nfRadio.channel('fields').request('add:error', model.get('id'), 'required-error', formModel.get('settings').validateRequiredField);
				}
			} else {
				nfRadio.channel( 'fields' ).request( 'remove:error', model.get( 'id' ), 'required-error' );
			}			
		}
	});

	return controller;
} );

define('controllers/submitError',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'forms' ), 'submit:response', this.submitErrors );
		},

		submitErrors: function( response, textStatus, jqXHR, formID ) {

			// Check for nonce error.
			if ( _.size( response.errors.nonce ) > 0 ) {
				if( 'undefined' != typeof response.errors.nonce.new_nonce && 'undefined' != typeof response.errors.nonce.nonce_ts ) {
					// Update nonce from response.
					nfFrontEnd.ajaxNonce = response.errors.nonce.new_nonce;
					nfFrontEnd.nonce_ts = response.errors.nonce.nonce_ts;
					// Re-submit form.
					var formModel = nfRadio.channel( 'app' ).request( 'get:form', formID );
					nfRadio.channel( 'form-' + formID ).request( 'submit', formModel );
				}
			}

			if ( _.size( response.errors.fields ) > 0 ) {
				_.each( response.errors.fields, function( data, fieldID ) {
                    if ( typeof( data ) === 'object' ) {
                        nfRadio.channel( 'fields' ).request( 'add:error', fieldID, data.slug, data.message );
                    } else {
                        nfRadio.channel( 'fields' ).request( 'add:error', fieldID, 'required-error', data );
                    }
				} );
			}

			if ( _.size( response.errors.form ) > 0 ) {
				_.each( response.errors.form, function( msg, errorID ) {
					nfRadio.channel( 'form-' + formID ).request( 'remove:error', errorID );
					nfRadio.channel( 'form-' + formID ).request( 'add:error', errorID, msg );
				} );
			}

			if ( 'undefined' != typeof response.errors.last ) {
				if( 'undefined' != typeof response.errors.last.message ) {
					var style = 'background: rgba( 255, 207, 115, .5 ); color: #FFA700; display: block;';
					console.log( '%c NINJA FORMS SUPPORT: SERVER ERROR', style );
					console.log( response.errors.last.message );
					console.log( '%c END SERVER ERROR MESSAGE', style );
				}
			}

			/**
			 * TODO: This needs to be re-worked for backbone. It's not dynamic enough.
			 */
			/*
			 * Re-show any hidden fields during a form submission re-start.
			 */
			jQuery( '#nf-form-' + formID + '-cont .nf-field-container' ).show();
		}

	});

	return controller;
} );

define('controllers/actionRedirect',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'forms' ), 'submit:response', this.actionRedirect );
		},

		actionRedirect: function( response ) {

			if ( 'undefined' != typeof response.data.halt && 'undefined' != typeof response.data.halt.redirect && '' != response.data.halt.redirect ) {
				window.location = response.data.halt.redirect;
			}

			if ( _.size( response.errors ) == 0 && 'undefined' != typeof response.data.actions ) {

				if ( 'undefined' != typeof response.data.actions.redirect && '' != response.data.actions.redirect ) {
					window.location = response.data.actions.redirect;
				}
			}
		}

	});

	return controller;
} );
define('controllers/actionSuccess',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'forms' ), 'submit:response', this.actionSubmit );
		},

		actionSubmit: function( response ) {
			if ( _.size( response.errors ) == 0 && 'undefined' != typeof response.data.actions ) {
				if ( 'undefined' != typeof response.data.actions.success_message && '' != response.data.actions.success_message ) {
					var form_id = response.data.form_id;
					var success_message = jQuery( '#nf-form-' + form_id + '-cont .nf-response-msg' );
					
					success_message.html( response.data.actions.success_message ).show();
					
					//Let's check if the success message is already fully visible in the viewport without scrolling
					var top_of_success_message = success_message.offset().top;
					var bottom_of_success_message = success_message.offset().top + success_message.outerHeight();
					var bottom_of_screen = jQuery(window).scrollTop() + jQuery(window).height();
					var top_of_screen = jQuery(window).scrollTop();

					var the_element_is_visible = ((bottom_of_screen > bottom_of_success_message) && (top_of_screen < top_of_success_message));

					if(!the_element_is_visible){
						//The element isn't visible, so let's scroll to the success message as in the previous release, but with a short animation
						jQuery('html, body').animate({
							scrollTop: ( success_message.offset().top - 50 )
						}, 300 );
					}	
				}
			}
		}

	});

	return controller;
} );

define('controllers/fieldSelect',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {

			this.listenTo( nfRadio.channel( 'fields' ), 'init:model', function( model ){
				if( 'list' == model.get( 'parentType' ) ) this.register( model );
			}, this );

			nfRadio.channel( 'listselect' ).reply( 'get:calcValue', this.getCalcValue, this );
			nfRadio.channel( 'listmultiselect' ).reply( 'get:calcValue', this.getCalcValue, this );
		},

		register: function( model ) {
			model.set( 'renderOptions', this.renderOptions );
			model.set( 'renderOtherAttributes', this.renderOtherAttributes );
			/*
			 * When we init a model, we need to set our 'value' to the selected option's value.
			 * This is the list equivalent of a 'default value'.
			 */ 
			if ( 0 != model.get( 'options' ).length ) {
				//Check to see if there is a value set for the field
				var savedVal = model.get( 'value' );

				/*
				 * Check to see if this is a multi-select list.
				 */
				if ( 'listmultiselect' == model.get( 'type' ) ) {
					/*
					 * We're using a multi-select, so we need to check out any selected options and add them together.
					 */
					var selected = _.filter( model.get( 'options' ), function( opt ) { return 1 == opt.selected } );
					selected = _.map( selected, function( opt ) { return opt.value } );
					var value = selected;
				} else if ( 'listradio' !== model.get( 'type' ) ) {
					/*
					 * Check to see if we have a selected value.
					 */
					var selected = _.find( model.get( 'options' ), function( opt ) { return 1 == opt.selected } );
					/*
					 * We don't have a selected value, so use our first option.
					 */
					if ( 'undefined' == typeof selected ) {
						selected = _.first( model.get( 'options' ) );
					}

					if ( 'undefined' != typeof selected
						&& 'undefined' != typeof selected.value ) {
						var value = selected.value;
					} else if ( 'undefined' != typeof selected ) {
						var value = selected.label;
					}	
				}

				/*
	            * This part is re-worked to take into account custom user-meta
	            * values for fields.
	             */
				if( 'undefined' !== typeof savedVal && '' !== savedVal
					&& Array.isArray( savedVal ) ) {
					model.set( 'value', savedVal );
				} else if ( 'undefined' != typeof selected ) {
					model.set( 'value', value );
				}
			}
		},

		renderOptions: function() {
			var html = '';

			_.each( this.options, function( option ) {
				/*
				* This part has been re-worked to account for values passed in
				* via custom user-meta ( a la User Mgmt add-on)
				 */
				if ( _.isArray( this.value ) ) {
                    // If we have a multiselect list...
                    // AND it has selected values...
					if( 'listmultiselect' === this.type && 0 < this.value.length &&
						-1 != _.indexOf( this.value[ 0 ].split( ',' ), option.value ) ) {
						var selected = true;
					} else if( -1 != _.indexOf( this.value, option.value ) ) {
						var selected = true;
					}
				} else if ( ! _.isArray( this.value ) && option.value == this.value ) {
					var selected = true;
				} else if ( ( 1 == option.selected && this.clean )
					&& 'undefined' === typeof this.value ) {
					var selected = true;
				} else {
					var selected = false;
				}

				/*
                 * TODO: This is a bandaid fix for making sure that each option has a "visible" property.
                 * This should be moved to creation so that when an option is added, it has a visible property by default.
                 */
                if ( 'undefined' == typeof option.visible ) {
                    option.visible = true;
                }

				option.selected = selected;
				option.fieldID = this.id;
				option.classes = this.classes;
				option.currentValue = this.value;

				var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-listselect-option' );
				html += template( option );
			}, this );

			return html;
		},

		renderOtherAttributes: function() {
			var otherAttributes = '';

			if( 'listmultiselect' == this.type ){
				otherAttributes = otherAttributes + ' multiple';

				var multiSize = this.multi_size || 5;
				otherAttributes = otherAttributes + ' size="' + multiSize + '"';
			}

			return otherAttributes;
		},

		getCalcValue: function( fieldModel ) {
			var calc_value = 0;
			var options = fieldModel.get( 'options' );
			if ( 0 != options.length ) {
				/*
				 * Check to see if this is a multi-select list.
				 */
				if ( 'listmultiselect' == fieldModel.get( 'type' ) ) {
					/*
					 * We're using a multi-select, so we need to check out any selected options and add them together.
					 */
					_.each( fieldModel.get( 'value' ), function( val ) {
						var tmp_opt = _.find( options, function( opt ) { return opt.value == val } );
						calc_value += Number( tmp_opt.calc );
					} );
				} else {
					/*
					 * We are using a single select, so our selected option is in the 'value' attribute.
					 */
					var selected = _.find( options, function( opt ) { return fieldModel.get( 'value' ) == opt.value } );
					/*
					 * We don't have a selected value, so use our first option.
					 */
					if ( 'undefined' == typeof selected ) {
						selected = fieldModel.get( 'options' )[0];
					}		
					calc_value  = selected.calc;			
				}
			}
			return calc_value;
		}

	});

	return controller;
} );

define('controllers/coreSubmitResponse',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'forms' ), 'submit:response', this.actionSubmit );
		},

		actionSubmit: function( response ) {
			var formModel = nfRadio.channel( 'app' ).request( 'get:form', response.data.form_id );
			/*
			 * If we have errors, don't hide or clear.
			 */
			if ( 0 != _.size( response.errors ) ) {
				return false;
			}

			if ( 1 == response.data.settings.clear_complete ) {
				// nfRadio.channel( 'form-' + response.data.form_id ).trigger( 'reset' );
				formModel.get( 'fields' ).reset( formModel.get( 'loadedFields' ) );
                if ( 1 != response.data.settings.hide_complete ) {
                    nfRadio.channel( 'captcha' ).trigger( 'reset' );
                }
			}

			if ( 1 == response.data.settings.hide_complete ) {
				/**
				 * TODO: This needs to be re-worked for backbone. It's not dynamic enough.
				 */
				formModel.trigger( 'hide' );
				// jQuery( '.nf-fields' ).hide();
				// jQuery( '.nf-form-title' ).hide();
			}
		}

	});

	return controller;
} );
define('controllers/fieldProduct',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            this.listenTo( nfRadio.channel( 'product' ), 'init:model', this.register );
            nfRadio.channel( 'product' ).reply( 'get:calcValue', this.getCalcValue, this );
        },

        register: function( model ) {
            model.set( 'renderProductQuantity', this.renderProductQuantity );
            model.set( 'renderProduct', this.renderProduct );
            model.set( 'renderOptions', this.renderOptions );
        },

        renderProduct: function(){
            switch( this.product_type ) {
                case 'user':
                    var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-textbox' );
                    return template( this );
                    break;
                case 'hidden':
                    var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-hidden' );
                    return template( this );
                    break;

                case 'dropdown':
                    var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-product-dropdown' );
                    return template( this );
                    break;
                default:
                    var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-product-single' );
                    return template( this );
            }
        },

        renderProductQuantity: function(){
            if ( 1 == this.product_use_quantity ) {
                var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-product-quantity' );
                return template( this );
            }
        },

        renderOptions: function() {
            var that = this;
            var html = '';
            _.each( this.options, function( option ) {
                if ( 1 == option.selected ) {
                    var selected = true;
                } else {
                    var selected = false;
                }

                option.selected = selected;
                option.fieldID = that.id;
                option.classes = that.classes;
                option.currentValue = that.value;

                var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-product-' + that.product_type + '-option' );
                html += template( option );
            } );

            return html;
        },

        getCalcValue: function( fieldModel ) {

            var product_price = fieldModel.get( 'product_price' );
            var product_quantity = fieldModel.get( 'value' );

            return product_price * product_quantity;
        }
    });

    return controller;
} );

define('controllers/fieldTotal',[], function() {
    var controller = Marionette.Object.extend( {

        totalModel: {},

        productTotals: {},

        initialize: function() {
            this.listenTo( nfRadio.channel( 'total' ), 'init:model', this.register );
            this.listenTo( nfRadio.channel( 'shipping' ), 'init:model', this.registerShipping );
        },

        register: function( totalModel ){
            this.totalModel = totalModel;

            var formID = totalModel.get( 'formID' );
            this.listenTo( nfRadio.channel( 'form-' + formID ), 'loaded', this.onFormLoaded );

            this.listenTo( nfRadio.channel( 'product' ), 'change:modelValue', this.onChangeProduct );
            this.listenTo( nfRadio.channel( 'quantity' ), 'change:modelValue', this.onChangeQuantity );
        },

        registerShipping: function( shippingModel ){
            this.shippingCost = shippingModel.get( 'shipping_cost' );
        },

        onFormLoaded: function( formModel ){

            var fieldModels = formModel.get( 'fields' ).models;

            var productFields = {};
            var quantityFields = {};

            for( var model in fieldModels ){

                var field = fieldModels[ model ];
                var fieldID = field.get( 'id' );

                // TODO: Maybe use switch
                if( 'product' == field.get( 'type' ) ){
                    productFields[ fieldID ] = field;
                } else if( 'quantity' == field.get( 'type' ) ){
                    var productID = field.get( 'product_assignment' );
                    quantityFields[ productID ] = field;
                }
            }

            for( var productID in productFields ){

                var product = productFields[ productID ];

                var productPrice = Number( product.get( 'product_price' ) );

                if( quantityFields[ productID ] ){

                    productPrice *= quantityFields[ productID ].get( 'value' );

                } else if( 1 == product.get( 'product_use_quantity' ) ){

                    productPrice *= product.get( 'value' );

                }

                this.productTotals[ productID ] = productPrice;
            }

            this.updateTotal();
        },

        onChangeProduct: function( model ){
            var productID = model.get( 'id' );
            var productPrice = Number( model.get( 'product_price' ) );
            var productQuantity = Number( model.get( 'value' ) );
            var newTotal = productQuantity * productPrice;
            this.productTotals[ productID ] = newTotal;

            this.updateTotal();
        },

        onChangeQuantity: function( model ){
            var productID = model.get( 'product_assignment' );
            var productField = nfRadio.channel( 'fields' ).request( 'get:field', productID );
            var productPrice = Number( productField.get( 'product_price' ) );

            var quantity = Number( model.get( 'value' ) );

            var newTotal = quantity * productPrice;

            this.productTotals[ productID ] = newTotal;

            this.updateTotal();
        },

        updateTotal: function(){

            var newTotal = 0;

            for( var product in this.productTotals ){
                newTotal += Number( this.productTotals[ product ] );
            }

            if( newTotal && this.shippingCost ) {
                // Only add shipping if there is a cost.
                newTotal += Number(this.shippingCost);
            }

            this.totalModel.set( 'value', newTotal.toFixed( 2 ) );
            this.totalModel.trigger( 'reRender' );
        }
    });

    return controller;
});
define('controllers/fieldQuantity',[], function() {
    var controller = Marionette.Object.extend( {

        initialize: function() {
            this.listenTo( nfRadio.channel( 'quantity' ), 'init:model', this.registerQuantity );
        },

        registerQuantity: function( model ){
            var productID = model.get( 'product_assignment' );
            var product = nfRadio.channel( 'fields' ).request( 'get:field', productID );

            if( product ) {
                product.set('product_use_quantity', 0);
            }
        },

    });

    return controller;
});
/**
 * Model that represents a calculation.
 *
 * On init, we trigger a radio message so that controllers can do things when a calc model inits.
 */
define( 'models/calcModel',[], function() {
	var model = Backbone.Model.extend( {
		initialize: function() {
			// Set our form id
			this.set( 'formID', this.collection.options.formModel.get( 'id' ) );
			// Set our initial fields object to empty. This will hold our key/value pairs.
			this.set( 'fields', {} );
			// Trigger a radio message to let controllers know we've inited this model.
			nfRadio.channel( 'calc' ).trigger( 'init:model', this );
			// When we change the value of this calculation, send out a radio message
			this.on( 'change:value', this.changeValue, this );
		},

		/**
		 * Trigger a radio message when a field present in our calculation changes
		 *
		 * The listener that triggers/calls this function is in controllers/calculations
		 * 
		 * @since  3.0
		 * @return void
		 */
		changeField: function( fieldModel ) {
			nfRadio.channel( 'calc' ).trigger( 'change:field', this, fieldModel );
		},

		changeCalc: function( targetCalcModel ) {
			nfRadio.channel( 'calc' ).trigger( 'change:calc', this, targetCalcModel );
		},

		changeValue: function() {
			nfRadio.channel( 'calc' ).trigger( 'change:value', this );
		}
	} );

	return model;
} );

define( 'models/calcCollection',['models/calcModel'], function( CalcModel ) {
	var collection = Backbone.Collection.extend( {
		model: CalcModel,
		comparator: 'order',

		initialize: function( models, options ) {
			this.options = options;
            _.each( models, function( model ) {
            	if( 'undefined' == typeof model.dec ) return;
                if ( '' === model.dec.toString().trim() ) model.dec = 2;
                model.dec = parseInt( model.dec );
            } );
			/*
			 * Respond to requests for our calc model
			 */
			nfRadio.channel( 'form-' + options.formModel.get( 'id' ) ).reply( 'get:calc', this.getCalc, this );
		},

		getCalc: function( key ) {
			return this.findWhere( { name: key } );
		}
	} );
	return collection;
} );
/**
 * Controller responsible for keeping up with calculations.
 */
define('controllers/calculations',['models/calcCollection'], function( CalcCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.calcs = {};
			this.displayFields = {};
			// When our form initialises, check to see if there are any calculations that need to be tracked.
			this.listenTo( nfRadio.channel( 'form' ), 'loaded', this.registerCalcs );
            
            // When our collection gets reset, reset calculation tracking as well.
            this.listenTo( nfRadio.channel( 'fields' ), 'reset:collection', this.resetCalcs );

			// When a calc model is initialised, run a setup function.
			// this.listenTo( nfRadio.channel( 'calc' ), 'init:model', this.setupCalc );

			// When a field referenced by a calc model changes, update our calc.
			this.listenTo( nfRadio.channel( 'calc' ), 'change:field', this.changeField );

			// When a calculation referenced by a calc model changes, update our calc.
			this.listenTo( nfRadio.channel( 'calc' ), 'change:calc', this.changeCalc );

			/*
			 * Listen to our field model init for fields that want to display calc values.
			 * If that field has a calc merge tag, replace it with the default calc value.
			 */
			var that = this;
			_.each( nfFrontEnd.use_merge_tags.calculations, function( fieldType ) {
				that.listenTo( nfRadio.channel( 'fields-' + fieldType ), 'init:model', that.initDisplayField );
			} );
			
			// When we change our calc value, update any display fields.
			this.listenTo( nfRadio.channel( 'calc' ), 'change:value', this.updateDisplayFields );

			// Set an init variable so that we only call reRender on the display field on change, not on init.
			this.init = {};
		},
        
        /**
         * Passthrough function to reset tracking of calculations when the fieldCollection is reset.
         * 
         * @since 3.2
         * @param backbone.collection fieldCollection
         * @return void
         */
        resetCalcs: function( fieldCollection ) {
            if( 'undefined' != typeof( fieldCollection.options.formModel ) ) {
                this.registerCalcs( fieldCollection.options.formModel );  
            }
        },

		/**
		 * When our form loads, create a collection out of any calculations.
		 * 
		 * @since  3.0
		 * @param  backbone.model formModel
		 * @return void
		 */
		registerCalcs: function( formModel ) {
			var calcCollection = new CalcCollection( formModel.get( 'settings' ).calculations, { formModel: formModel } );
			this.calcs[ formModel.get( 'id' ) ] = calcCollection;
			var that = this;

			_.each( calcCollection.models, function( calcModel ) {
				/*
				 * We set a property on our init variable for the calc model we're looping over.
				 * This property is set to true so that when we make changes to the calc model on the next line
				 * the field view doesn't try to redraw itself.
				 * If we don't do this, the 'reRender' attribute of the model will be set before the view is initialized,
				 * which means that setting 'reRender' to true will never re-render the view.
				 */
				that.init[ calcModel.get( 'name' ) ] = true;
				// Setup our calculation models with initial values and register listeners for calc-related fields.
				that.setupCalc( calcModel );
			} );
		},

		/**
		 * When a calculation model is instantiated from the registerCalcs function:
		 *
		 * Use a regex to get an array of the field keys
		 * Setup an initial key/values array
		 * Check for any references to other calculations
		 * Set the initial value of our calculation
		 * 
		 * @since  3.0
		 * @param  backbone.model calcModel
		 * @return void
		 */
		setupCalc: function( calcModel ) {
			// Setup our that var so we can access 'this' context in our loop.
			var that = this;
			// Get our equation
			var eq = calcModel.get( 'eq' );
			// We want to keep our original eq intact, so we use a different var for string replacment.
			var eqValues = eq;
            // Store the name for debugging later.
            var calcName = calcModel.get( 'name' );

			/* TODO:
			 * It might be possible to refactor these two if statements.
			 * The difficulty is that each has a different method of retreiving the specific data model.
			 */
			// Check to see if we have any field merge tags in our equation.
			var fields = eq.match( new RegExp( /{field:(.*?)}/g ) );
			if ( fields ) {
				/*
				 * fields is now an array of field keys that looks like:
				 * ['{field:key'], ['{field:key'], etc.
				 *
				 * We need to run a function with each of our field keys to setup our field key array and hook up our field change listner.
				 */
				
				fields = fields.map( function( field ) {
					// field will be {field:key}
					var key = field.replace( ':calc}', '' ).replace( '}', '' ).replace( '{field:', '' );

					// Get our field model
					fieldModel = nfRadio.channel( 'form-' + calcModel.get( 'formID' ) ).request( 'get:fieldByKey', key );

                    if( 'undefined' == typeof fieldModel ) return;

                    fieldModel.set( 'clean', false );

					// Register a listener in our field model for value changes.
					fieldModel.on( 'change:value', calcModel.changeField, calcModel );
					// Get our calc value from our field model.
					var calcValue = that.getCalcValue( fieldModel );
					// Add this field to our internal key/value object.
					that.updateCalcFields( calcModel, key, calcValue );
					// Update the string tracking our merged eq with the calc value.
					eqValues = that.replaceKey( 'field', key, calcValue, eqValues );
				} );
			}

			// Check to see if we have any calc merge tags in our equation.
			var calcs = eq.match( new RegExp( /{calc:(.*?)}/g ) );
			if ( calcs ) {
				/*
				 * calcs is now an array of calc keys that looks like:
				 * ['{calc:key'], ['{calc:key'], etc.
				 *
				 * We need to run a function with each of our calc keys to setup our calc key array and hook up our calc change listner.
				 */
				
				calcs = calcs.map( function( calc ) {
					// calc will be {calc:name}
					var name = calc.replace( '}', '' ).replace( '{calc:', '' );
					// Get our calc model
					var targetCalcModel = calcModel.collection.findWhere( { name: name } );

					if( 'undefined' == typeof targetCalcModel ) return;

					// Listen for changes on our calcluation, since we need to update our calc when it changes.
					targetCalcModel.on( 'change:value', calcModel.changeCalc, calcModel );
					// // Get our calc value from our calc model.
					var calcValue = targetCalcModel.get( 'value' );
					// Update the string tracking our merged eq with the calc value.
					eqValues = that.replaceKey( 'calc', name, calcValue, eqValues );
				} );

			}

            // Scrub unmerged tags (ie deleted/nox-existent fields/calcs, etc).
            eqValues = eqValues.replace( /{([a-zA-Z0-9]|:|_|-)*}/g, 0 );
            // Scrub line breaks.
            eqValues = eqValues.replace( /\r?\n|\r/g, '' );
			// Evaluate the equation and update the value of this model.
			try {
				this.debug('Calculation Decoder ' + eqValues + ' -> ' + this.localeDecodeEquation(eqValues) + ' (Setup)');
				calcModel.set( 'value', Number( mexp.eval( this.localeDecodeEquation(eqValues) ) ).toFixed( calcModel.get( 'dec' ) ) );
			} catch( e ) {
                //console.log( calcName );
				console.log( e );
			}
            
            // If for whatever reason, we got NaN, reset that to 0.
            if( calcModel.get( 'value' ) === 'NaN' ) calcModel.set( 'value', '0' );

			// Debugging console statement.
			// console.log( eqValues + ' = ' + calcModel.get( 'value' ) );
		},

		/**
		 * Update an item in our key/value pair that represents our fields and calc values.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	calcModel
		 * @param  string 			key
		 * @param  string 			calcValue
		 * @return void
		 */
		updateCalcFields: function( calcModel, key, calcValue ) {
			var fields = calcModel.get( 'fields' );
			fields[ key ] = calcValue;
			calcModel.set( 'fields', fields );
		},

		/**
		 * Get a calc value from a field model.
		 *
		 * Sends a request to see if there's a special calc value
		 * Uses the value of the field if there is not.
		 * 
		 * @since  3.0
		 * @param  backbone.model fieldModel
		 * @return value
		 */
		getCalcValue: function( fieldModel ) {
			/*
			 * Send out a request on the field type and parent type channel asking if they need to modify the calc value.
			 * This is helpful for fields like lists that can have a different calc_value than selected value.
			 */
			var value = nfRadio.channel( fieldModel.get( 'type' ) ).request( 'get:calcValue', fieldModel );

			var localeConverter = new nfLocaleConverter(nfi18n.siteLocale, nfi18n.thousands_sep, nfi18n.decimal_point);
			

			var calcValue = value || fieldModel.get( 'value' );
			var machineNumber = localeConverter.numberDecoder(calcValue);
			var formattedNumber = localeConverter.numberEncoder(calcValue);

			if ( 'undefined' !== typeof machineNumber && jQuery.isNumeric( machineNumber ) ) {
				value = formattedNumber;
			} else {
				value = 0;
			}
			// }

			if ( ! fieldModel.get( 'visible' ) ) {
				value = 0;
			}
		
			return value;
		},

		/**
		 * Replace instances of key with calcValue. This is used to replace one key at a time.
		 *
		 * If no eq is passed, use calcModel eq.
		 *
		 * Returns a string with instances of key replaced with calcValue.
		 * 
		 * @since  version
		 * @param  string 	key       
		 * @param  string 	calcValue 
		 * @param  string 	eq        
		 * @return string 	eq      
		 */
		replaceKey: function( type, key, calcValue, eq ) {
			eq = eq || calcModel.get( 'eq' );

			tag = '{' + type + ':' + key + '}';
			var reTag = new RegExp( tag, 'g' );

			calcTag = '{' + type + ':' + key + ':calc}';
			var reCalcTag = new RegExp( calcTag, 'g' );

			eq = eq.replace( reTag, calcValue );
			eq = eq.replace( reCalcTag, calcValue );

			return eq;
		},

		/**
		 * Takes a calcModel and returns a string eq with all keys replaced by their appropriate calcValues.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	calcModel
		 * @return string			eq
		 */
		replaceAllKeys: function( calcModel ) {
			var eq = calcModel.get( 'eq' );
			var that = this;
			_.each( calcModel.get( 'fields' ), function( value, key ) {
				eq = that.replaceKey( 'field', key, value, eq );
			} );

			// If we have any calc merge tags, replace those as well.
			var calcs = eq.match( new RegExp( /{calc:(.*?)}/g ) );
			if ( calcs ) {
				_.each( calcs, function( calc ) {
					// calc will be {calc:key}
					var name = calc.replace( '}', '' ).replace( '{calc:', '' );
					var targetCalcModel = calcModel.collection.findWhere( { name: name } );
                    if( 'undefined' == typeof targetCalcModel ) return;
					var re = new RegExp( calc, 'g' );
					eq = eq.replace( re, targetCalcModel.get( 'value' ) );
				} );
			}

			return eq;
		},

		/**
		 * Function that's called when a field within the calculation changes.
		 * 
		 * @since  3.0
		 * @param  backbone.model calcModel
		 * @param  backbone.model fieldModel
		 * @return void
		 */
		changeField: function( calcModel, fieldModel ) {
		
			var key = fieldModel.get( 'key' );
			var value = this.getCalcValue( fieldModel );
			
			this.updateCalcFields( calcModel, key, value );
			var eqValues = this.replaceAllKeys( calcModel );

            // Scrub unmerged tags (ie deleted/nox-existent fields/calcs, etc).
            eqValues = eqValues.replace( /{([a-zA-Z0-9]|:|_|-)*}/g, '0' );
            eqValues = eqValues.replace( /\r?\n|\r/g, '' );
            try {
				this.debug('Calculation Decoder ' + eqValues + ' -> ' + this.localeDecodeEquation(eqValues) + ' (Change Field)');
			     calcModel.set( 'value', Number( mexp.eval( this.localeDecodeEquation(eqValues) ) ).toFixed( calcModel.get( 'dec' ) ) );
            } catch( e ) {
                if(this.debug())console.log( e );
            }
            if( calcModel.get( 'value' ) === 'NaN' ) calcModel.set( 'value', '0' );

			// Debugging console statement.
			// console.log( eqValues + ' = ' + calcModel.get( 'value' ) );		
		},

		initDisplayField: function( fieldModel ) {

			if( ! fieldModel.get( 'default' ) || 'string' != typeof fieldModel.get( 'default' ) ) return;

			var calcs = fieldModel.get( 'default' ).match( new RegExp( /{calc:(.*?)}/g ) );
			if ( calcs ) {
				_.each( calcs, function( calcName ) {
					calcName = calcName.replace( '{calc:', '' ).replace( '}', '' ).replace( ':2', '' );
					this.displayFields[ calcName ] = this.displayFields[ calcName ] || [];
					this.displayFields[ calcName ].push( fieldModel );
				}, this );
			}
		},

		updateDisplayFields: function( calcModel ) {
			var that = this;
			if ( 'undefined' != typeof this.displayFields[ calcModel.get( 'name' ) ] ) {
				_.each( this.displayFields[ calcModel.get( 'name' ) ], function( fieldModel ) {

					var value = '';

					/**
					 * if we have a html field, we want to use the actual
					 * value and re-evaluate
				    **/
					if( "html" === fieldModel.get( 'type' ) ) {
						value = fieldModel.get( 'value' );
					} else {
						// if not a html field, use default to re-evaluate
						value = fieldModel.get( 'default' );
					}

					/*
					 This is a fix for the issue of the merge tags being
					 display'd
					 */

					// Find spans with calc data-key values
					var spans = value.match( new RegExp( /<span data-key="calc:(.*?)<\/span>/g ));
					_.each( spans, function( spanVar ) {
						// transform the span back into a merge tag
						var tmpCalcTag = "{" + spanVar.replace("<span" +
							" data-key=\"", "" ).replace( /">(.*?)<\/span>/, "" ) + "}";

						value = value.replace( spanVar, tmpCalcTag );
					} );
					var calcs = value.match( new RegExp( /{calc:(.*?)}/g ) );
					_.each( calcs, function( calc ) {
//						var rounding = false;
						// calc will be {calc:key} or {calc:key:2}
						var name = calc.replace( '}', '' ).replace( '{calc:', '' ).replace( ':2', '' );

						/*
						 * TODO: Bandaid for rounding calculations to two decimal places when displaying the merge tag.
						 * Checks to see if we have a :2. If we do, remove it and set our rounding variable to true.
						 */
//						if ( -1 != name.indexOf( ':2' ) ) {
//							rounding = true;
//							name = name.replace( ':2', '' );
//						}

						var calcModel = that.calcs[ fieldModel.get( 'formID' ) ].findWhere( { name: name } );
						var re = new RegExp( calc, 'g' );
						var calcValue = calcModel.get( 'value' ) ;
//						if ( rounding ) {
//							calcValue = calcValue.toFixed( 2 );
//							rounding = false;
//						}
						
                        if( 'undefined' != typeof( calcValue ) ) {
                            calcValue = that.applyLocaleFormatting( calcValue, calcModel );
						}
                        /*
                         * We replace the merge tag with the value
						 * surrounded by a span so that we can still find it
						 * and not affect itself or other field merge tags
						 *
						 * Unless this isn't a html field, then we just set
						  * value to calcValue
						*/
                        if( "html" === fieldModel.get( 'type' ) ) {
	                        value = value.replace(re, "<span data-key=\"calc:" + name + "\">"
		                        + calcValue + "</span>");
                        } else {
                        	value = calcValue;
                        }
					} );
					
					fieldModel.set( 'value', value );
					if ( ! that.init[ calcModel.get( 'name' ) ] ) {
						// fieldModel.set( 'reRender', true );
						fieldModel.trigger( 'reRender' );
					}
					that.init[ calcModel.get( 'name' ) ] = false;
				} );
			}
		},

		getCalc: function( name, formID ) {
			return this.calcs[ formID ].findWhere( { name: name } );
		},

		changeCalc: function( calcModel, targetCalcModel ) {
			var eqValues = this.replaceAllKeys( calcModel );
			
			eqValues = eqValues.replace( '[', '' ).replace( ']', '' );
            eqValues = eqValues.replace( /\r?\n|\r/g, '' );
            try {
				this.debug('Calculation Decoder ' + eqValues + ' -> ' + this.localeDecodeEquation(eqValues) + ' (Change Calc)');
			     calcModel.set( 'value', Number( mexp.eval( this.localeDecodeEquation( eqValues ) ) ).toFixed( calcModel.get( 'dec' ) ) );
            } catch( e ) {
                console.log( e );
            }
            if( calcModel.get( 'value' ) === 'NaN' ) calcModel.set( 'value', '0' );
		},
        
        /**
         * Function to apply Locale Formatting to Calculations
         * @since Version 3.1
         * @param Str number
         * 
         * @return Str
         */
        applyLocaleFormatting: function( number, calcModel ) {

			var localeConverter = new nfLocaleConverter(nfi18n.siteLocale, nfi18n.thousands_sep, nfi18n.decimal_point);

			var formattedNumber = localeConverter.numberEncoder(number, calcModel.get('dec'));
            
            // // Split our string on the decimal to preserve context.
            // var splitNumber = number.split('.');
            // // If we have more than one element (if we had a decimal point)...
            // if ( splitNumber.length > 1 ) {
            //     // Update the thousands and remerge the array.
            //     splitNumber[ 0 ] = splitNumber[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, nfi18n.thousands_sep );
            //     var formattedNumber = splitNumber.join( nfi18n.decimal_point );
            // }
            // // Otherwise (we had no decimal point)...
            // else {
            //     // Update the thousands.
            //     var formattedNumber = number.replace( /\B(?=(\d{3})+(?!\d))/g, nfi18n.thousands_sep );
            // }
            return formattedNumber;
		},
		
		localeDecodeEquation: function( eq ) {
			var result = '';
			var expression = '';
			var pattern = /[0-9.,]/;
			var localeConverter = new nfLocaleConverter(nfi18n.siteLocale, nfi18n.thousands_sep, nfi18n.decimal_point);
			// This pattern accounts for all whitespace characters (including thin space).
			eq = eq.replace( /\s/g, '' );
			eq = eq.replace( /&nbsp;/g, '' );
			var characters = eq.split('');
			// foreach ( characters as character ) {
			characters.forEach( function( character ) {
				// If the character is numeric or '.' or ','
				if (pattern.test(character)) {
					expression = expression + character;
				} else {
					// If we reach an operator char, append the expression to the result
					if ( 0 < expression.length ) {
						result = result + localeConverter.numberDecoder( expression );
						expression = '';
					}
					result = result + character;
				}
			});
			// The following catches the case of the last character being a digit.
			if ( 0 < expression.length ) {
				result = result + localeConverter.numberDecoder( expression );
			}
			return result;
		},

		debug: function(message) {
			if ( window.nfCalculationsDebug || false ) console.log(message);
		}
	
	});

	return controller;
} );

define('controllers/dateBackwardsCompat',[], function() {
    var controller = Marionette.Object.extend({

        initialize: function () {
            this.listenTo( Backbone.Radio.channel( 'pikaday-bc' ), 'init', this.dateBackwardsCompat );	
        },

        dateBackwardsCompat: function( dateObject, fieldModel ) {
           
            /**
             * Start backwards compatibility for old pikaday customisation
             */
            // Legacy properties
            dateObject.pikaday = {};
            dateObject.pikaday._o = {};

            //Old hook for Pikaday Custom code
            nfRadio.channel( 'pikaday' ).trigger( 'init', dateObject, fieldModel );

            // If we've set a disableDayFn property in custom code, hook it up to Flatpickr
            if ( typeof dateObject.pikaday._o.disableDayFn !== 'undefined') {
                dateObject.set( 'disable', [ dateObject.pikaday._o.disableDayFn ] );
            }

            //Compatibility for i18n pikaday function
            if ( typeof dateObject.pikaday._o.i18n !== 'undefined' || typeof dateObject.pikaday._o.firstDay !== 'undefined') {

                let locale = dateObject.config.locale;

                if ( typeof dateObject.pikaday._o.firstDay !== 'undefined') {
                    locale.firstDayOfWeek = dateObject.pikaday._o.firstDay;
                }

                if ( typeof dateObject.pikaday._o.i18n !== 'undefined') {
                    if ( typeof dateObject.pikaday._o.i18n.weekdays !== 'undefined') {
                        locale.weekdays.longhand = dateObject.pikaday._o.i18n.weekdays;
                    }

                    if ( typeof dateObject.pikaday._o.i18n.weekdaysShort !== 'undefined') {
                        locale.weekdays.shorthand = dateObject.pikaday._o.i18n.weekdaysShort;
                    }
                    
                    if ( typeof dateObject.pikaday._o.i18n.months !== 'undefined') {
                        jQuery( '.flatpickr-monthDropdown-months > option' ).each( function() {
                            this.text = dateObject.pikaday._o.i18n.months[ this.value ];
                        } );
                    }
                }

                dateObject.set( 'locale', locale );
                
            }

            if ( Object.keys(dateObject.pikaday._o).length > 0 ) {
                console.log("%cDeprecated Ninja Forms Pikaday custom code detected.", "color: Red; font-size: large");
                console.log("You are using deprecated Ninja Forms Pikaday custom code. Support for this custom code will be removed in a future version of Ninja Forms. Please contact Ninja Forms support for more details.");
            }

        }

    });

    return controller;
});
define('controllers/fieldDate',[], function() {
    var controller = Marionette.Object.extend({

        initialize: function () {
            this.listenTo( nfRadio.channel( 'date' ), 'init:model', this.registerFunctions );
            this.listenTo( nfRadio.channel( 'date' ), 'render:view', this.initDatepicker );
        },

        registerFunctions: function( model ) {
            model.set( 'renderHourOptions', this.renderHourOptions );
            model.set( 'renderMinuteOptions', this.renderMinuteOptions );
            model.set( 'maybeRenderAMPM', this.maybeRenderAMPM );
            model.set( 'customClasses', this.customClasses );
            // Overwrite the default getValue() method.
            model.getValue = this.getValue;
        },

        renderHourOptions: function() {
            return this.hours_options;
        },

        renderMinuteOptions: function() {
            return this.minutes_options;
        },

        maybeRenderAMPM: function() {
            if ( 'undefined' == typeof this.hours_24 || 1 == this.hours_24 ) {
                return;
            }

            return '<div style="float:left;"><select class="ampm extra"><option value="am">AM</option><option value="pm">PM</option></select></div>';
        },

        initDatepicker: function ( view ) {
            view.model.set( 'el', view.el );
            var el = jQuery( view.el ).find( '.nf-element' )[0];
            view.listenTo( nfRadio.channel( 'form-' + view.model.get( 'formID' ) ), 'before:submit', this.beforeSubmit, view );

            // If we are using a time_only date_mode, then hide the date input.
            if ( 'undefined' != typeof view.model.get( 'date_mode' ) && 'time_only' == view.model.get( 'date_mode' ) ) {
                jQuery( el ).hide();
                return false;
            }

            var dateFormat = view.model.get( 'date_format' );
    
            // For "default" date format, convert PHP format to JS compatible format.
            if( '' == dateFormat || 'default' == dateFormat ){
                dateFormat = this.convertDateFormat( nfi18n.dateFormat );
            }

            var dateSettings = {
                classes: jQuery( el ).attr( "class" ),
                placeholder: view.model.get( 'placeholder' ),
                parseDate: function (datestr, format) {
                    return moment(datestr, format, true).toDate();
                },
                formatDate: function (date, format, locale) {
                    return moment(date).format(format);
                },
                dateFormat: dateFormat,
                altFormat: dateFormat,
                altInput: true,
                ariaDateFormat: dateFormat,
                mode: "single",
                allowInput: true,
                disableMobile: "true",
                locale: {
                    months: {
                        shorthand: nfi18n.monthsShort,
                        longhand: nfi18n.months
                    },
                    weekdays: {
                        shorthand: nfi18n.weekdaysShort,
                        longhand: nfi18n.weekdays
                    },
                    firstDayOfWeek: nfi18n.startOfWeek,
                }
            }; 
           
            // Filter our datepicker settings object.
            let filteredDatePickerSettings = nfRadio.channel( 'flatpickr' ).request( 'filter:settings', dateSettings, view );
            if ( 'undefined' != typeof filteredDatePickerSettings ) {
                dateSettings = filteredDatePickerSettings;
            }

            var dateObject = flatpickr( el, dateSettings );

            if ( 1 == view.model.get( 'date_default' ) ) {
                dateObject.setDate( moment().format(dateFormat) );
                view.model.set( 'value', moment().format(dateFormat) );
            }

            //Trigger Pikaday backwards compatibility
            nfRadio.channel( 'pikaday-bc' ).trigger( 'init', dateObject, view.model, view );

            nfRadio.channel( 'flatpickr' ).trigger( 'init', dateObject, view.model, view );
        },

        beforeSubmit: function( formModel ) {
            if ( 'date_only' == this.model.get( 'date_mode' ) ) {
                return false;
            }
            let hour = jQuery( this.el ).find( '.hour' ).val();
            let minute = jQuery( this.el ).find( '.minute' ).val();
            let ampm = jQuery( this.el ).find( '.ampm' ).val();
            let current_value = this.model.get( 'value' );
            let date = false;

            if ( _.isObject( current_value ) ) {
                date = current_value.date;
            } else {
                date = current_value;
            }

            let date_value = {
                date: date,
                hour: hour,
                minute: minute,
                ampm: ampm,
            };

            this.model.set( 'value', date_value );
        },

        getYearRange: function( fieldModel ) {
            var yearRange      = 10;
            var yearRangeStart = fieldModel.get( 'year_range_start' );
            var yearRangeEnd   = fieldModel.get( 'year_range_end'   );

            if( yearRangeStart && yearRangeEnd ){
                return [ yearRangeStart, yearRangeEnd ];
            } else if( yearRangeStart ) {
                yearRangeEnd = yearRangeStart + yearRange;
                return [ yearRangeStart, yearRangeEnd ];
            } else if( yearRangeEnd ) {
                yearRangeStart = yearRangeEnd - yearRange;
                return [ yearRangeStart, yearRangeEnd ];
            }

            return yearRange;
        },

        getMinDate: function( fieldModel ) {
            var minDate = null;
            var yearRangeStart = fieldModel.get( 'year_range_start' );

            if( yearRangeStart ) {
                return new Date( yearRangeStart, 0, 1 );
            }

            return minDate;
        },

        getMaxDate: function( fieldModel ) {
            var maxDate = null;
            var yearRangeEnd   = fieldModel.get( 'year_range_end' );

            if( yearRangeEnd ) {
                return new Date( yearRangeEnd, 11, 31 );
            }

            return maxDate;
        },
        
        convertDateFormat: function( dateFormat ) {
            // http://php.net/manual/en/function.date.php
            // https://github.com/dbushell/Pikaday/blob/master/README.md#formatting  **** Switched to flatpickr ***
            // Note: Be careful not to add overriding replacements. Order is important here.

            /** Day */
            dateFormat = dateFormat.replace( 'D', 'ddd' ); // @todo Ordering issue?
            dateFormat = dateFormat.replace( 'd', 'DD' );
            dateFormat = dateFormat.replace( 'l', 'dddd' );
            dateFormat = dateFormat.replace( 'j', 'D' );
            dateFormat = dateFormat.replace( 'N', '' ); // Not Supported
            dateFormat = dateFormat.replace( 'S', '' ); // Not Supported
            dateFormat = dateFormat.replace( 'w', 'd' );
            dateFormat = dateFormat.replace( 'z', '' ); // Not Supported

            /** Week */
            dateFormat = dateFormat.replace( 'W', 'W' );

            /** Month */
            dateFormat = dateFormat.replace( 'M', 'MMM' ); // "M" before "F" or "m" to avoid overriding.
            dateFormat = dateFormat.replace( 'F', 'MMMM' );
            dateFormat = dateFormat.replace( 'm', 'MM' );
            dateFormat = dateFormat.replace( 'n', 'M' );
            dateFormat = dateFormat.replace( 't', '' );  // Not Supported

            // Year
            dateFormat = dateFormat.replace( 'L', '' ); // Not Supported
            dateFormat = dateFormat.replace( 'o', 'YYYY' );
            dateFormat = dateFormat.replace( 'Y', 'YYYY' );
            dateFormat = dateFormat.replace( 'y', 'YY' );

            // Time - Not supported
            dateFormat = dateFormat.replace( 'a', '' );
            dateFormat = dateFormat.replace( 'A', '' );
            dateFormat = dateFormat.replace( 'B', '' );
            dateFormat = dateFormat.replace( 'g', '' );
            dateFormat = dateFormat.replace( 'G', '' );
            dateFormat = dateFormat.replace( 'h', '' );
            dateFormat = dateFormat.replace( 'H', '' );
            dateFormat = dateFormat.replace( 'i', '' );
            dateFormat = dateFormat.replace( 's', '' );
            dateFormat = dateFormat.replace( 'u', '' );
            dateFormat = dateFormat.replace( 'v', '' );

            // Timezone - Not supported
            dateFormat = dateFormat.replace( 'e', '' );
            dateFormat = dateFormat.replace( 'I', '' );
            dateFormat = dateFormat.replace( 'O', '' );
            dateFormat = dateFormat.replace( 'P', '' );
            dateFormat = dateFormat.replace( 'T', '' );
            dateFormat = dateFormat.replace( 'Z', '' );

            // Full Date/Time - Not Supported
            dateFormat = dateFormat.replace( 'c', '' );
            dateFormat = dateFormat.replace( 'r', '' );
            dateFormat = dateFormat.replace( 'u', '' );

            return dateFormat;
        },

        customClasses: function( classes ) {
            if ( 'date_and_time' == this.date_mode ) {
                classes += ' date-and-time';
            }
            return classes;
        },

        // This function is called whenever we want to know the value of the date field.
        // Since it could be a date/time field, we can't return just the value.
        getValue: function() {

            if ( 'date_only' == this.get( 'date_mode' ) ) {
                return this.get( 'value' );
            }

            let el = this.get( 'el' );
            let hour = jQuery( el ).find( '.hour' ).val();
            let minute = jQuery( el ).find( '.minute' ).val();
            let ampm = jQuery( el ).find( '.ampm' ).val();
            let current_value = this.get( 'value' );
            let date = false;

            if ( _.isObject( current_value ) ) {
                date = current_value.date;
            } else {
                date = current_value;
            }

            let value = '';

            if ( 'undefined' != typeof date ) {
                value += date;
            }

            if ( 'undefined' != typeof hour && 'undefined' != typeof minute ) {
                value += ' ' + hour + ':' + minute;
            }

            if ( 'undefined' != typeof ampm ) {
                value += ' ' + ampm;
            }

            return value;

            // let date_value = {
            //     date: date,
            //     hour: hour,
            //     minute: minute,
            //     ampm: ampm,
            // };

            // this.model.set( 'value', date_value );
        }
    });

    return controller;
});

define('controllers/fieldRecaptcha',[], function() {
    var controller = Marionette.Object.extend({

        initialize: function () {
            this.listenTo( nfRadio.channel( 'recaptcha' ), 'init:model',      this.initRecaptcha  );
            this.listenTo( nfRadio.channel( 'forms' ),     'submit:response', this.resetRecaptcha );
        },

       	initRecaptcha: function ( model ) {
       		nfRadio.channel( 'recaptcha' ).reply( 'update:response', this.updateResponse, this, model.id );
        },

        updateResponse: function( response, fieldID ) {
        	var model = nfRadio.channel( 'fields' ).request( 'get:field', fieldID );
			model.set( 'value', response );
            nfRadio.channel( 'fields' ).request( 'remove:error', model.get( 'id' ), 'required-error' );
        },

        resetRecaptcha: function() {
			var recaptchaID = 0;
			jQuery( '.g-recaptcha' ).each( function() {
				try {
					grecaptcha.reset( recaptchaID );
				} catch( e ){
					console.log( 'Notice: Error trying to reset grecaptcha.' );
				}
				recaptchaID++;
			} );
        }
    });

    return controller;
} );
define('controllers/fieldRecaptchaV3',[], function() {
    var controller = Marionette.Object.extend({

        initialize: function () {
            this.listenTo( nfRadio.channel( 'recaptcha_v3' ), 'init:model', this.initRecaptcha  );
        },

       	initRecaptcha: function ( model ) {
	        let formID = model.get( 'formID' );
	        nfRadio.channel( 'form-' + formID ).trigger( 'disable:submit', model );
	        grecaptcha.ready( function() {
		        grecaptcha.execute( model.get( 'site_key' ), {
			        action: 'register'
		        } ).then( function( token ) {
			        model.set( 'value', token );
			        nfRadio.channel( 'form-' + formID ).trigger( 'enable:submit', model );
		        } );
	        } );
        },
    });

    return controller;
} );
define('controllers/fieldHTML',[], function() {
    var controller = Marionette.Object.extend({

        htmlFields: [],
        trackedMergeTags: [],

        initialize: function () {
            this.listenTo( Backbone.Radio.channel( 'fields-html' ), 'init:model', this.setupFieldMergeTagTracking );
        },

        setupFieldMergeTagTracking: function( fieldModel ) {
            this.htmlFields.push( fieldModel );

            var formID = fieldModel.get( 'formID' );

            this.listenTo( nfRadio.channel( 'form-' + formID ), 'init:model', function( formModel ){

                var mergeTags = fieldModel.get( 'default' ).match( new RegExp( /{field:(.*?)}/g ) );
                if ( ! mergeTags ) return;

                _.each( mergeTags, function( mergeTag ) {
                    var fieldKey = mergeTag.replace( '{field:', '' ).replace( '}', '' );
                    var fieldModel = formModel.get( 'fields' ).findWhere({ key: fieldKey });
                    if( 'undefined' == typeof fieldModel ) return;

                    this.trackedMergeTags.push( fieldModel );
                    this.listenTo( nfRadio.channel( 'field-' + fieldModel.get( 'id' ) ), 'change:modelValue', this.updateFieldMergeTags );
                }, this );

                // Let's get this party started!
                this.updateFieldMergeTags();
            }, this );
        },

        updateFieldMergeTags: function( fieldModel ) {
            _.each( this.htmlFields, function( htmlFieldModel ){
                var value = htmlFieldModel.get( 'value' );
               _.each( this.trackedMergeTags, function( fieldModel ){

                   /* Search the value for any spans with mergetag data-key
                   * values
                   */
                   var spans = value.match( new RegExp( /<span data-key="field:(.*?)<\/span>/g ) );
	               _.each( spans, function( spanVar ) {
	                   /* See if the span string contains the current
                       * fieldModel's key. If so replace the span with a
                       * merge tag for evaluation.
                       */
                       if( -1 < spanVar.indexOf( "data-key=\"field:" + fieldModel.get( 'key' ) ) ) {
	                       value = value.replace( spanVar, "{field:" + fieldModel.get( 'key' ) + "}" );
                       }
	               } );

                    var mergeTag = '{field:' + fieldModel.get( 'key' ) + '}';
	               /* We replace the merge tag with the value
	               * surrounded by a span so that we can still find it
	               * and not affect itself or other field merge tags
	               */
                    value = value.replace( mergeTag, "<span data-key=\"field:"
                        + fieldModel.get( 'key' ) + "\">"
                        + fieldModel.getValue() + "</span>" );
               }, this ) ;
               htmlFieldModel.set( 'value', value );
               htmlFieldModel.trigger( 'reRender' );
            }, this );
        }

    });

    return controller;
});

/**
 * When a form is loaded, enable any help text that appears on the page.
 */
define('controllers/helpText',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'form' ), 'render:view', this.initHelpText );

			nfRadio.channel( 'form' ).reply( 'init:help', this.initHelpText );
		},

		initHelpText: function( view ) {
			jQuery( view.el ).find( '.nf-help' ).each( function() {
				var jBox = jQuery( this ).jBox( 'Tooltip', {
					theme: 'TooltipBorder',
					content: jQuery( this ).data( 'text' )
				});
			} );
		}
	});

	return controller;
} );
define('controllers/fieldTextbox',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
            nfRadio.channel( 'textbox' ).reply( 'get:calcValue', this.getCalcValue, this );
		},

		getCalcValue: function( fieldModel ) {
            if('currency' == fieldModel.get('mask')){
                var form = nfRadio.channel( 'app' ).request( 'get:form', fieldModel.get( 'formID' ) );
                var currencySymbol = ('undefined' !== typeof form) ? form.get( 'currencySymbol' ) : '';
                var currencySymbolDecoded = jQuery('<textarea />').html(currencySymbol).text();
                return fieldModel.get( 'value' ).replace(currencySymbolDecoded, '');
            }

			return fieldModel.get( 'value' );
		},
	});

	return controller;
} );
/**
 * When a form is loaded, enable any rtes in textareas.
 */
define('controllers/fieldTextareaRTE',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'textarea' ), 'render:view', this.initTextareaRTEs );
			this.listenTo( nfRadio.channel( 'textarea' ), 'click:extra', this.clickExtra );

			// Instantiates the variable that holds the media library frame.
			this.meta_image_frame;

			this.currentContext = {};

			if( 'undefined' == typeof jQuery.summernote ) return;

			jQuery.summernote.options.icons = {
		        'align': 'dashicons dashicons-editor-alignleft',
		        'alignCenter': 'dashicons dashicons-editor-aligncenter',
		        'alignJustify': 'dashicons dashicons-editor-justify',
		        'alignLeft': 'dashicons dashicons-editor-alignleft',
		        'alignRight': 'dashicons dashicons-editor-alignright',
		        'indent': 'dashicons dashicons-editor-indent',
		        'outdent': 'dashicons dashicons-editor-outdent',
		        // 'arrowsAlt': 'dashicons fa-arrows-alt',
		        'bold': 'dashicons dashicons-editor-bold',
		        'caret': 'dashicons dashicons-arrow-down',
		        // 'circle': 'dashicons fa-circle',
		        'close': 'dashicons dashicons-dismiss',
		        'code': 'dashicons dashicons-editor-code',
		        'eraser': 'dashicons dashicons-editor-removeformatting',
		        // 'font': 'dashicons fa-font',
		        // 'frame': 'dashicons fa-frame',
		        'italic': 'dashicons dashicons-editor-italic',
		        'link': 'dashicons dashicons-admin-links',
		        'unlink': 'dashicons dashicons-editor-unlink',
		        'magic': 'dashicons dashicons-editor-paragraph',
		        // 'menuCheck': 'dashicons fa-check',
		        'minus': 'dashicons dashicons-minus',
		        'orderedlist': 'dashicons dashicons-editor-ol',
		        // 'pencil': 'dashicons fa-pencil',
		        // 'picture': 'dashicons fa-picture-o',
		        // 'question': 'dashicons fa-question',
		        'redo': 'dashicons dashicons-redo',
		        'square': 'dashicons fa-square',
		        // 'strikethrough': 'dashicons fa-strikethrough',
		        // 'subscript': 'dashicons fa-subscript',
		        // 'superscript': 'dashicons fa-superscript',
		        'table': 'dashicons dashicons-editor-table',
		        // 'textHeight': 'dashicons fa-text-height',
		        // 'trash': 'dashicons fa-trash',
		        'underline': 'dashicons dashicons-editor-underline',
		        'undo': 'dashicons dashicons-undo',
		        'unorderedlist': 'dashicons dashicons-editor-ul',
		        // 'video': 'dashicons fa-youtube-play'
		      };

		},

		initTextareaRTEs: function( view ) {
			if ( 1 != view.model.get( 'textarea_rte' ) ) {
				return false;
			}
			/*
			 * Custom Button for links
			 */
			var that = this;
			// var linkButton = this.linkButton();
			var linkButton = function( context ) {
				return that.linkButton( context );
			}
			var mediaButton = function( context ) {
				return that.mediaButton( context );
			}

			var toolbar = [
				[ 'paragraphStyle', ['style'] ],
				[ 'fontStyle', [ 'bold', 'italic', 'underline','clear' ] ],
				[ 'lists', [ 'ul', 'ol' ] ],
			    [ 'paragraph', [ 'paragraph' ] ],
			    [ 'customGroup', [ 'linkButton', 'unlink' ] ],
			    [ 'table', [ 'table' ] ],
			    [ 'actions', [ 'undo', 'redo' ] ],
			];

			if ( 1 == view.model.get( 'textarea_media' ) && 0 != userSettings.uid ) {
				toolbar.push( [ 'tools', [ 'mediaButton' ] ] );
			}

			jQuery( view.el ).find( '.nf-element' ).summernote( {
				toolbar: toolbar,
				buttons: {
					linkButton: linkButton,
					mediaButton: mediaButton
				},
				height: 150,   //set editable area's height
				codemirror: { // codemirror options
				    theme: 'monokai',
				    lineNumbers: true
				},
				prettifyHtml: true,
				callbacks: {
					onChange: function( e ) {
						view.model.set( 'value', jQuery( this ).summernote( 'code' ) );
					}
				}
			} );

			var linkMenu = jQuery( view.el ).find( '.link-button' ).next( '.dropdown-menu' ).find( 'button' );
			linkMenu.replaceWith(function () {
			    return jQuery( '<div/>', {
			        class: jQuery( linkMenu ).attr( 'class' ),
			        html: this.innerHTML
			    } );
			} );
		},

		linkButton: function( context ) {
			var that = this;
			var ui = jQuery.summernote.ui;
			var linkButton = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-rte-link-button' );
			var linkDropdown = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-rte-link-dropdown' );
			return ui.buttonGroup([
				ui.button({
	            className: 'dropdown-toggle link-button',
	            contents: linkButton({}),
	            tooltip: nfi18n.fieldTextareaRTEInsertLink,
	            click: function( e ) {
	            	that.clickLinkButton( e, context );
	            },
	            data: {
	              toggle: 'dropdown'
	            }
	          }),
				ui.dropdown([
	            ui.buttonGroup({
	              children: [
	                ui.button({
	                  contents: linkDropdown({}),
	                  tooltip: ''
	                }),
	              ]
	            })
	          ])
			]).render();
		},

		mediaButton: function( context ) {
			var that = this;
			var ui = jQuery.summernote.ui;
			var mediaButton = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-rte-media-button' );
			return ui.button({
	            className: 'dropdown-toggle',
	            contents: mediaButton({}),
	            tooltip: nfi18n.fieldTextareaRTEInsertMedia,
	            click: function( e ) {
	            	that.openMediaManager( e, context );
	            }
	          }).render();
		},

		openMediaManager: function( e, context ) {
			context.invoke( 'editor.saveRange' );
			// If the frame already exists, re-open it.
			if ( this.meta_image_frame ) {
				this.meta_image_frame.open();
				return;
			}

			// Sets up the media library frame
			this.meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
				title: nfi18n.fieldTextareaRTESelectAFile,
				button: { text:  'insert' }
			});

			var that = this;

			// Runs when an image is selected.
			this.meta_image_frame.on('select', function(){

				// Grabs the attachment selection and creates a JSON representation of the model.
				var media_attachment = that.meta_image_frame.state().get('selection').first().toJSON();
				that.insertMedia( media_attachment, context );
			});

			// Opens the media library frame.
			this.meta_image_frame.open();
		},

		clickLinkButton: function ( e, context ) {
			var range = context.invoke( 'editor.createRange' );
			context.invoke( 'editor.saveRange' );
			var text = range.toString()
			this.currentContext = context;

			jQuery( e.target ).closest( '.note-customGroup > .note-btn-group' ).on ('hide.bs.dropdown', function ( e ) {
				return false;
			});

			jQuery( e.target ).closest( '.note-customGroup > .note-btn-group' ).on ('shown.bs.dropdown', function ( e ) {
				jQuery( e.target ).parent().parent().find( '.link-text' ).val( text );
				jQuery( e.target ).parent().parent().find( '.link-url' ).focus();
			});
		},

		clickExtra: function( e ) {
			var textEl = jQuery( e.target ).parent().find( '.link-text' );
			var urlEl = jQuery( e.target ).parent().find( '.link-url' );
			var isNewWindowEl = jQuery( e.target ).parent().find( '.link-new-window' );
			this.currentContext.invoke( 'editor.restoreRange' );
			if ( jQuery( e.target ).hasClass( 'insert-link' ) ) {
				var text = textEl.val();
				var url = urlEl.val();
				var isNewWindow = ( isNewWindowEl.prop( 'checked' ) ) ? true: false;
				if ( 0 != text.length && 0 != url.length ) {
					this.currentContext.invoke( 'editor.createLink', { text:text, url: url, isNewWindow: isNewWindow } );
				}
			}
			textEl.val( '' );
			urlEl.val( '' );
			isNewWindowEl.prop( 'checked', false );
			jQuery( e.target ).closest( 'div.note-btn-group.open' ).removeClass( 'open' );
		},

		insertMedia: function( media, context ) {
			context.invoke( 'editor.restoreRange' );
			if ( 'image' == media.type ) {
				context.invoke( 'editor.insertImage', media.url );
			} else {
				context.invoke( 'editor.createLink', { text: media.filename, url: media.url } );
			}

		}
	});

	return controller;
} );
define('controllers/fieldStarRating',[], function() {
    var controller = Marionette.Object.extend( {

        initialize: function() {
        	this.listenTo( nfRadio.channel( 'starrating' ), 'init:model', this.register );
            this.listenTo( nfRadio.channel( 'starrating' ), 'render:view', this.initRating );
        },

        register: function( model ) {
			model.set( 'renderRatings', this.renderRatings );
		},

        initRating: function( view ){
            jQuery( view.el ).find( '.starrating' ).rating();

        },

        renderRatings: function() {
        	var html = document.createElement( 'span' );
        	// changed from 'default' to 'number_of_stars'
        	for (var i = 0; i <= this.number_of_stars - 1; i++) {
                var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-starrating-star' );
                var num = i + 1;
                var checked = '';

                // Check to see if current 'star' matches the default value
		        if ( this.value == num ) {
		        	checked = 'checked';
		        }
                var htmlFragment = template( { id: this.id, classes: this.classes, num: num, checked: checked, required: this.required } );
                html.appendChild(
                    document.createRange().createContextualFragment( htmlFragment )
                );
        	}
        	return html.innerHTML;
        }

    });

    return controller;
});

define('controllers/fieldTerms',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            this.listenTo( nfRadio.channel( 'terms' ), 'init:model', this.register );
        },

        register: function( model ) {
            // nfRadio.channel( 'field-' + this.model.get( 'id' ) ).trigger( 'click:extra', e, this.model );
            this.listenTo( nfRadio.channel( 'field-' + model.get( 'id' ) ), 'click:extra', this.clickExtra );
            this.listenTo( nfRadio.channel( 'field-' + model.get( 'id' ) ), 'keyup:field', this.keyUpExtra );
        },
        
        clickExtra: function( e, model ) {
            var el = jQuery( e.currentTarget );
            var value = el.parent().find( '.extra-value' ).val();
            this.addOption( model, value );
        },

        keyUpExtra: function( el, model, keyCode ) {
            if( 13 != keyCode ) return;
            this.addOption( model, el.val() );
        },

        addOption: function( model, value ) {
            if( ! value ) return;
            var options = model.get( 'options' );
            var new_option = {
                label: value,
                value: value,
                selected: 0,
            };
            options.push( new_option );

            var selected = model.get( 'value' );
            selected.push( value );

            // model.set( 'reRender', true );
            model.trigger( 'reRender' );
        }
        
    });

    return controller;
} );
/**
 * Before we display our form content, ask if anyone wants to give us a different view.
 * Before we do anything with the data, pass it through any loading filters.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2016 WP Ninjas
 * @since 3.0
 */
define( 'controllers/formContentFilters',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			/*
			 * Init our fieldContent view and load filter arrays.
			 */
			this.viewFilters = [];
			this.loadFilters = [];

			/*
			 * Listen for requests to add new formContent filters.
			 */
			nfRadio.channel( 'formContent' ).reply( 'add:viewFilter', this.addViewFilter, this );
			nfRadio.channel( 'formContent' ).reply( 'add:loadFilter', this.addLoadFilter, this );

			/*
			 * Listen for requests to get our formContent filters.
			 */
			nfRadio.channel( 'formContent' ).reply( 'get:viewFilters', this.getViewFilters, this );
			nfRadio.channel( 'formContent' ).reply( 'get:loadFilters', this.getLoadFilters, this );

			/*
			 * -- DEPRECATED RADIO REPLIES --
			 * 
			 * The 'fieldContents' channel has been deprecated as of 3.0 (it was present in the RC) in favour of 'formContent'.
			 * Listen for requests to add new fieldContent filters.
			 * 
			 * TODO: These radio listeners on the 'fieldContents' channels are here for backwards compatibility and should be removed eventually.
			 */
			nfRadio.channel( 'fieldContents' ).reply( 'add:viewFilter', this.addViewFilter, this );
			nfRadio.channel( 'fieldContents' ).reply( 'add:loadFilter', this.addLoadFilter, this );

			/*
			 * Listen for requests to get our fieldContent filters.
			 * TODO: Remove eventually.
			 */
			nfRadio.channel( 'fieldContents' ).reply( 'get:viewFilters', this.getViewFilters, this );
			nfRadio.channel( 'fieldContents' ).reply( 'get:loadFilters', this.getLoadFilters, this );
		
			/*
			 * -- END DEPRECATED --
			 */
		},

		addViewFilter: function( callback, priority ) {
			this.viewFilters[ priority ] = callback;
		},

		getViewFilters: function() {
			return this.viewFilters;
		},

		addLoadFilter: function( callback, priority ) {
			this.loadFilters[ priority ] = callback;
		},

		getLoadFilters: function() {
			return this.loadFilters;
		}

	});

	return controller;
} );
define( 'views/fieldItem',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',

		initialize: function() {
    		this.listenTo( this.model, 'reRender', this.render, this );
    		this.listenTo( this.model, 'change:addWrapperClass', this.addWrapperClass, this );
    		this.listenTo( this.model, 'change:removeWrapperClass', this.removeWrapperClass, this );
    		this.listenTo( this.model, 'change:invalid', this.toggleAriaInvalid, this );

    		this.template = '#tmpl-nf-field-' + this.model.get( 'wrap_template' );
		},

		test: function( model ) {
			console.log( 'firing from trigger 1' );
		},

		addWrapperClass: function() {
			var cl = this.model.get( 'addWrapperClass' );
			if ( '' != cl ) {
				jQuery( this.el ).addClass( cl );
				this.model.set( 'addWrapperClass', '' );
			}
		},

		removeWrapperClass: function() {
			var cl = this.model.get( 'removeWrapperClass' );
			if ( '' != cl ) {
				jQuery( this.el ).removeClass( cl );
				this.model.set( 'removeWrapperClass', '' );
			}
		},

		toggleAriaInvalid: function() {
			var invalid = this.model.get( 'invalid' );
			jQuery( '[aria-invalid]', this.el ).attr( 'aria-invalid', JSON.stringify( invalid ) );
		},

		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );

	   		/*
    		 * If we have an input mask, init that mask.
    		 * TODO: Move this to a controller so that the logic isn't in the view.
    		 */
    		if ( 'undefined' != typeof this.model.get( 'mask' ) && '' != jQuery.trim( this.model.get( 'mask' ) ) ) {
    			if ( 'custom' == this.model.get( 'mask') ) {
    				var mask = this.model.get( 'custom_mask' );
    			} else {
    				var mask = this.model.get( 'mask' );
    			}

				/* POLYFILL */ Number.isInteger = Number.isInteger || function(value) { return typeof value === "number" && isFinite(value) && Math.floor(value) === value; };
    			if ( Number.isInteger( mask ) ) {
    				mask = mask.toString();
    			}

				if ( 'currency' == mask ) {
					var form = nfRadio.channel( 'app' ).request( 'get:form', this.model.get( 'formID' ) );

					var thousands_sep = form.get( 'thousands_sep' );
					/*
					 * TODO: if we have a &nbsp; , replace it with a string with a space
					 */
					if ( '&nbsp;' == thousands_sep || 160==thousands_sep.charCodeAt(0) ) {
						thousands_sep = ' ';
					}
					var currencySymbol = jQuery( '<div/>' ).html( form.get( 'currencySymbol' ) ).text();
					thousands_sep = jQuery( '<div/>' ).html( thousands_sep ).text();
					var decimal_point = jQuery( '<div/>' ).html( form.get( 'decimal_point' ) ).text();
					
					/*
					 * TODO: Currently, these options use the plugin-wide defaults for locale.
					 * When per-form locales are implemented, these will need to be revisited.
					 */
					var autoNumericOptions = {
					    digitGroupSeparator        : thousands_sep,
					    decimalCharacter           : decimal_point,
					    currencySymbol             : currencySymbol
					};

					// Initialization
					var autoN_el = jQuery(jQuery( this.el ).find( '.nf-element' )[ 0 ]);
					new AutoNumeric( jQuery( this.el ).find( '.nf-element' )[ 0 ], autoNumericOptions );
					// update the value for the model so it gets saved to
					// the database properly
					var context = this;
					autoN_el.on( 'change', function( e ) {
						context.model.set( 'value', e.target.value );
					})
				} else {
					jQuery( this.el ).find( '.nf-element' ).mask( mask );
				} 			
	   		}

			nfRadio.channel( this.model.get( 'type' ) ).trigger( 'render:view', this );
			nfRadio.channel( 'fields' ).trigger( 'render:view', this );
		},

		templateHelpers: function () {
			var that = this;
	    	return {

				renderElement: function(){
					var tmpl = _.find( this.element_templates, function( tmpl ) {
						if ( 0 < jQuery( '#tmpl-nf-field-' + tmpl ).length ) {
							return true;
						}
					} );
					var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-' + tmpl );
					
					return template( this );
				},

				renderLabel: function() {
					var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-field-label' );
					return template( this );
				},

				renderLabelClasses: function () {
					var classes = '';
					if ( 'undefined' != typeof this.customLabelClasses ) {
						classes = this.customLabelClasses( classes );
					}
					return classes;
				},

				renderPlaceholder: function() {
					var placeholder = this.placeholder;

					if ( 'undefined' != typeof this.customPlaceholder ) {
						placeholder = this.customPlaceholder( placeholder );
					}

					if( '' != jQuery.trim( placeholder ) ) {
						return 'placeholder="' + placeholder + '"';
					} else {
						return '';
					}
				},

				renderWrapClass: function() {
					var wrapClass = 'field-wrap ' + this.type + '-wrap';

					// Check if type and parentType are different. If, so
					// then add appropriate parentType wrap class
					if ( this.type !== this.parentType ) {
						wrapClass = wrapClass + ' ' + this.parentType + '-wrap';
					}
					// If we have an old_classname defined, output wrap class for backward compatibility
					if ( 'undefined' != typeof this.old_classname && 0 < jQuery.trim( this.old_classname ).length ) {
						wrapClass += ' ' + this.old_classname + '-wrap';
					}

					if ( 'undefined' != typeof customWrapClass ) {
						wrapClass = customWrapClass( wrapClass );
					}

					return wrapClass;
				},

				renderClasses: function() {
					var classes = this.classes;

					if ( this.error ) {
						classes += ' nf-error';
					} else {
						classes = classes.replace( 'nf-error', '' );
					}

					if ( 'undefined' != typeof this.element_class && 0 < jQuery.trim( this.element_class ).length ) {
						classes += ' ' + this.element_class;
					}

					/*
					 * If we have a function for adding extra classes, add those.
					 */

					if ( 'undefined' != typeof this.customClasses ) {
						classes = this.customClasses( classes );
					}
					
					return classes;
				},

				maybeDisabled: function() {
					if ( 1 == this.disable_input ) {
						return 'disabled';
					} else {
						return '';
					}
				},
                
                maybeRequired: function() {
                    if ( 1 == this.required ) {
                        return 'required';
                    } else {
                        return '';
                    }
                },

				maybeDisableAutocomplete: function() {
					if ( 1 == this.disable_browser_autocomplete ) {
						return 'autocomplete="off"';
					} else {
						return '';
					}
				},

				maybeInputLimit: function() {
					if ( 'characters' == this.input_limit_type && '' != jQuery.trim( this.input_limit ) ) {
						return 'maxlength="' + this.input_limit + '"';
					} else {
						return '';
					}
				},

				getHelpText: function() {
					// this.help_text = jQuery( this.help_text ).html();
					// return ( 'undefined' != typeof this.help_text ) ? this.help_text.replace(/"/g, "&quot;") : '';
					return ( 'undefined' != typeof this.help_text ) ? this.help_text : '';
				},

				maybeRenderHelp: function() {

					// use jQuery().text() to make sure help_text has actual
					// text and not just HTML tags.
					var check_text_par = document.createElement( 'p' );
                    check_text_par.innerHTML = this.help_text;

                    var shouldRenderHelpText = false;
                    // Check for text or image tags
					if ( 0 != jQuery.trim( jQuery( check_text_par ).text() ).length
						|| 0 < jQuery( check_text_par ).find('img').length ) {
                    	shouldRenderHelpText = true;
                    }

					if ( 'undefined' != typeof this.help_text && shouldRenderHelpText ) {
						var icon = document.createElement( 'span' );
						icon.classList.add( 'fa', 'fa-info-circle', 'nf-help' );
						icon.setAttribute( 'data-text', this.getHelpText() );
						return icon.outerHTML;
					} else {
						return '';
					}
				},

				renderDescText: function() {
					if ( 'undefined' == typeof this.desc_text ) {
						return '';
					}

					// Creates an element so we can check to see if the text is empty.
					var text = document.createElement( 'p' );
					text.innerHTML = this.desc_text;
					if( 0 == jQuery.trim( text.innerText ).length ) return '';

                    var check, checkText;
					checkText = document.createTextNode( this.desc_text );
					check = document.createElement( 'p' );
					check.appendChild( checkText );
					if ( 0 != jQuery.trim( jQuery( check ).text() ).length ) {
						var descriptionText, fieldDescription;
                        descriptionText  = document.createRange().createContextualFragment( this.desc_text );
                        fieldDescription  = document.createElement( 'div' );
						fieldDescription.classList.add( 'nf-field-description' );
						fieldDescription.appendChild( descriptionText );
						return fieldDescription.outerHTML;
					} else {
						return '';
					}
				},
                
                renderNumberDefault: function() {
                    // If the field is clean...
                    if ( this.clean ) {
                        // If we have a default...
                        if ( this.default ) {
                            return this.default;
                        } // If we do not have a placeholder...
                        else if ( ! this.placeholder ) {
                            return this.value;
                        } // Otherwise...
                        else {
                            return '';
                        }
                    } // Otherwise... (The field is not clean.)
                    else {
                        return this.value;
                    }
                },

				renderCurrencyFormatting: function( number ) {
					/*
					 * Our number will have a . as a decimal point. We want to replace that with our locale decimal, nfi18n.decimal_point.
					 */
					var replacedDecimal = number.toString().replace( '.', '||' );
					/*
					 * Add thousands separator. Our original number var won't have thousands separators.
					 */
					var replacedThousands = replacedDecimal.replace( /\B(?=(\d{3})+(?!\d))/g, nfi18n.thousands_sep );
					var formattedNumber = replacedThousands.replace( '||', nfi18n.decimal_point );

					var form = nfRadio.channel( 'app' ).request( 'get:form', that.model.get( 'formID' ) );
					var currency_symbol = form.get( 'settings' ).currency_symbol;
					return currency_symbol + formattedNumber;
				},

				maybeRenderTime: function() {
					if ( 'time_only' == this.date_mode || 'date_and_time' == this.date_mode ) {
						return true;
					}
					return false;
				},
			};
		},

		events: {
			'change .nf-element': 'fieldChange',
			'keyup .nf-element': 'fieldKeyup',
			'click .nf-element': 'fieldClick',
			'click .extra': 'extraClick',
			'change .extra': 'extraChange',
			'blur .nf-element': 'fieldBlur'
		},

		fieldChange: function( e ) {
			var el = jQuery( e.currentTarget );
			var response = nfRadio.channel( 'nfAdmin' ).request( 'change:field', el, this.model );
		},

		fieldKeyup: function( e ) {
			var el = jQuery( e.currentTarget );
			var keyCode = e.keyCode;
			nfRadio.channel( 'field-' + this.model.get( 'id' ) ).trigger( 'keyup:field', el, this.model, keyCode );
			nfRadio.channel( this.model.get( 'type' ) ).trigger( 'keyup:field', el, this.model, keyCode );
			nfRadio.channel( 'fields' ).trigger( 'keyup:field', el, this.model, keyCode );
		},

		fieldClick: function( e ) {
			var el = jQuery( e.currentTarget );
			nfRadio.channel( 'field-' + this.model.get( 'id' ) ).trigger( 'click:field', el, this.model );
			nfRadio.channel( this.model.get( 'type' ) ).trigger( 'click:field', el, this.model );
			nfRadio.channel( 'fields' ).trigger( 'click:field', el, this.model );
		},

		extraClick: function( e ) {
			nfRadio.channel( 'field-' + this.model.get( 'id' ) ).trigger( 'click:extra', e, this.model );
			nfRadio.channel( this.model.get( 'type' ) ).trigger( 'click:extra', e, this.model );
			nfRadio.channel( 'fields' ).trigger( 'click:extra', e, this.model );
		},

		extraChange: function( e ) {
			nfRadio.channel( 'field-' + this.model.get( 'id' ) ).trigger( 'change:extra', e, this.model );
			nfRadio.channel( this.model.get( 'type' ) ).trigger( 'change:extra', e, this.model );
			nfRadio.channel( 'fields' ).trigger( 'change:extra', e, this.model );
		},

		fieldBlur: function( e ) {
			var el = jQuery( e.currentTarget );
			nfRadio.channel( 'field-' + this.model.get( 'id' ) ).trigger( 'blur:field', el, this.model );
			nfRadio.channel( this.model.get( 'type' ) ).trigger( 'blur:field', el, this.model );
			nfRadio.channel( 'fields' ).trigger( 'blur:field', el, this.model );
		},

		onAttach: function() {
			nfRadio.channel( this.model.get( 'type' ) ).trigger( 'attach:view', this );
		}
	});

	return view;
} );

define( 'views/beforeField',[], function() {
    var view = Marionette.ItemView.extend({
        tagName: 'nf-section',
        template: '#tmpl-nf-field-before'
    });

    return view;
} );
define( 'views/fieldErrorItem',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'nf-section',
		template: '#tmpl-nf-field-error',

		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );
		},
	});

	return view;
} );
define( 'views/fieldErrorCollection',['views/fieldErrorItem'], function( fieldErrorItem ) {
	var view = Marionette.CollectionView.extend({
		tagName: "nf-errors",
		childView: fieldErrorItem,

		initialize: function( options ) {
			this.fieldModel = options.fieldModel;
		},

		onRender: function() {
			if ( 0 == this.fieldModel.get( 'errors' ).models.length ) {
                this.fieldModel.removeWrapperClass( 'nf-error' );
                this.fieldModel.removeWrapperClass( 'nf-fail' );
                this.fieldModel.addWrapperClass( 'nf-pass' );
                this.fieldModel.setInvalid( false );
            } else {
                this.fieldModel.removeWrapperClass( 'nf-pass' );
                this.fieldModel.addWrapperClass( 'nf-fail' );
                this.fieldModel.addWrapperClass( 'nf-error' );
                this.fieldModel.setInvalid( true );
            }

		}
	});

	return view;
} );

define( 'views/inputLimit',[], function() {
    var view = Marionette.ItemView.extend({
        tagName: 'nf-section',
        template: '#tmpl-nf-field-input-limit',

        initialize: function() {
        	this.listenTo( nfRadio.channel( 'field-' + this.model.get( 'id' ) ), 'keyup:field', this.updateCount );
        	this.count = this.model.get( 'input_limit' );
        	this.render();
        },

        updateCount: function( el, model ) {
            var value = jQuery( el ).val();
            var regex = /\s+/gi;
            var words = value.trim().replace(regex, ' ').split(' ');
            var wordCount = words.length;
            var charCount = value.length;
            
            /**
             * PHP Config has 'char' instead of 'characters', so I changed it to
             * 'characters', but added 'char' here so existing form fields will
             * act correctly
             **/
            if ( 'characters' == this.model.get( 'input_limit_type' )
                    || 'char' == this.model.get( 'input_limit_type' ) ) {
                jQuery( el ).attr( 'maxlength', this.model.get( 'input_limit' ) );
                this.count = this.model.get( 'input_limit' ) - charCount;
            } else {
                this.count = this.model.get( 'input_limit' ) - wordCount;
                var limit = this.model.get( 'input_limit' );
                if( wordCount > limit ){
                    jQuery( el ).val( words.slice( 0, limit).join( ' ' ) );
                }
            }

        	this.render();
        },

        templateHelpers: function() {
        	var that = this;
        	return {
        		currentCount: function() {
        			return that.count;
        		}
        	}
        }

    });

    return view;
} );
define( 'views/afterField',['views/fieldErrorCollection', 'views/inputLimit'], function( fieldErrorCollection, InputLimitView ) {
    var view = Marionette.ItemView.extend({
        tagName: 'nf-section',
        template: '#tmpl-nf-field-after',

        initialize: function() {
    		this.model.on( 'change:errors', this.changeError, this );
        },

        onRender: function() {
        	/*
        	 * If we have an error, render our error view.
        	 * TODO: Perhaps move to a controller?
        	 */
        	var errorEl = jQuery( this.el ).children( '.nf-error-wrap' );
    		this.errorCollectionView = new fieldErrorCollection( { el: errorEl, collection: this.model.get( 'errors' ), fieldModel: this.model } );
            if ( 0 < this.model.get( 'errors' ).length ) {
               this.errorCollectionView.render(); 
            }
            
    		/*
    		 * If we have an input limit set, render the view that contains our counter
    		 * TODO: Move this to a controller so that the logic isn't in the view.
    		 */
    		if ( 'undefined' != typeof this.model.get( 'input_limit' ) && '' != jQuery.trim( this.model.get( 'input_limit' ) ) ){
    			var inputLimitEl = jQuery( this.el ).children( '.nf-input-limit');
    			this.inputLimitView = new InputLimitView( { el: inputLimitEl, model: this.model } );
    		}
        },

        changeError: function() {
			this.errorCollectionView.render();
		},

    });

    return view;
} );
define( 'views/fieldRepeaterFieldLayout',['views/fieldItem', 'views/beforeField', 'views/afterField'], function( fieldItem, beforeField, afterField ) {

    var view = Marionette.LayoutView.extend({
        tagName: 'nf-field',

        regions: {
            beforeField: '.nf-before-field',
            field: '.nf-field',
            afterField: '.nf-after-field',
        },

        initialize: function() {
            this.listenTo( this.model, 'change:visible', this.render, this );
        },

        getTemplate: function() {
            if ( this.model.get( 'visible' ) ) {
                return '#tmpl-nf-field-layout';
            } else {
                return '#tmpl-nf-empty';
            }
        },

        onRender: function() {
            if ( this.model.get( 'visible' ) ) {
                this.beforeField.show( new beforeField( { model: this.model } ) );
                this.field.show( new fieldItem( { model: this.model } ) ); 
                this.afterField.show( new afterField( { model: this.model } ) );
            }
        },

        templateHelpers: function() {
            return {
                renderContainerClass: function() {
                    var containerClass = ' label-' + this.label_pos + ' ';
                    // If we have a description position, add that to our container.
                    if ( 'undefined' != typeof this.desc_pos ) {
                        containerClass += 'desc-' + this.desc_pos + ' ';
                    }
                    // if we have a container_class field setting, add that to our container.
                    if ( 'undefined' != typeof this.container_class && 0 < jQuery.trim( this.container_class ).length ) {
                        containerClass += this.container_class + ' ';
                    }

                    //check if the parent type and type are different. If
                    // so, add parent type container styling
                    
                    if( this.type !== this.parentType ) {
                        containerClass += ' ' + this.parentType + '-container';
                    }
                    return containerClass;
                }
            }
        }
    });

    return view;
} );

define( 'views/fieldRepeaterFieldCollection',['views/fieldRepeaterFieldLayout'], function( fieldLayout ) {
	var view = Marionette.CollectionView.extend({
		tagName: 'nf-fields-wrap',
		childView: fieldLayout,
	});

	return view;
} );
define( 'views/fieldRepeaterSetLayout',[ 'views/fieldRepeaterFieldCollection' ], function( fieldCollection ) {
    var view = Marionette.LayoutView.extend({
        tagName: 'fieldset',
        template: '#tmpl-nf-field-repeater-set',

        regions: {
            fields: '.nf-repeater-fieldset',
        },

        onRender: function() {
            this.fields.show( new fieldCollection( { collection: this.model.get( 'fields' ) } ) );
        },

        events: {
            'click .nf-remove-fieldset': 'removeSet',
        },

        removeSet: function() {
            nfRadio.channel( "field-repeater" ).trigger( 'remove:fieldset',  this.model )
        }

    });

    return view;
} );
define( 'views/fieldRepeaterSetCollection',['views/fieldRepeaterSetLayout'], function( repeaterSetLayout ) {
	var view = Marionette.CollectionView.extend({
		tagName: 'div',
		childView: repeaterSetLayout,
	});

	return view;
} );
define( 'views/fieldRepeaterLayout',[ 'views/fieldRepeaterSetCollection' ], function( repeaterSetCollection ) {

    var view = Marionette.LayoutView.extend({
        tagName: 'div',
        template: '#tmpl-nf-field-repeater',

        regions: {
            sets: '.nf-repeater-fieldsets',
        },

        initialize: function() {

            this.collection = this.model.get( 'sets' );

            nfRadio.channel( 'field-repeater' ).on( 'rerender:fieldsets', this.render, this );

            this.listenTo( nfRadio.channel( 'form-' + this.model.get( 'formID' ) ), 'before:submit', this.beforeSubmit );

        },

        onRender: function() { 
            this.sets.show( new repeaterSetCollection( { collection: this.collection } ) );
        },

        events: {
            'click .nf-add-fieldset': 'addSet'
        },

        addSet: function( e ) {
            nfRadio.channel( 'field-repeater' ).trigger( 'add:fieldset', e );       
        },

        beforeSubmit: function() {
			this.collection.beforeSubmit( this.model.get( 'sets' ) );
		}
        

    });

    return view;
} );
define( 'views/fieldLayout',['views/fieldItem', 'views/beforeField', 'views/afterField', 'views/fieldRepeaterLayout'], function( fieldItem, beforeField, afterField, repeaterFieldLayout ) {

    var view = Marionette.LayoutView.extend({
        tagName: 'nf-field',

        regions: {
            beforeField: '.nf-before-field',
            field: '.nf-field',
            afterField: '.nf-after-field',
        },

        initialize: function() {
            this.listenTo( this.model, 'change:visible', this.render, this );
        },

        getTemplate: function() {
            if ( this.model.get( 'visible' ) ) {
                return '#tmpl-nf-field-layout';
            } else {
                return '#tmpl-nf-empty';
            }
        },

        onRender: function() {
            if ( this.model.get( 'visible' ) ) {
                this.beforeField.show( new beforeField( { model: this.model } ) );
                if ( 'repeater' == this.model.get( 'type' ) ) {
                    this.field.show( new repeaterFieldLayout( { model: this.model } ) );
                } else {
                    this.field.show( new fieldItem( { model: this.model } ) ); 
                }
                this.afterField.show( new afterField( { model: this.model } ) );
            }
        },

        templateHelpers: function() {
            return {
                renderContainerClass: function() {
                    var containerClass = ' label-' + this.label_pos + ' ';
                    // If we have a description position, add that to our container.
                    if ( 'undefined' != typeof this.desc_pos ) {
                        containerClass += 'desc-' + this.desc_pos + ' ';
                    }
                    // if we have a container_class field setting, add that to our container.
                    if ( 'undefined' != typeof this.container_class && 0 < jQuery.trim( this.container_class ).length ) {
                        containerClass += this.container_class + ' ';
                    }

                    //check if the parent type and type are different. If
                    // so, add parent type container styling
                    
                    if( this.type !== this.parentType ) {
                        containerClass += ' ' + this.parentType + '-container';
                    }

                    return containerClass;
                }
            }
        }

    });

    return view;
} );

/**
 * Return views that might be used in extensions.
 * These are un-instantiated views.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/loadViews',['views/fieldItem', 'views/fieldLayout'], function( fieldItemView, fieldLayoutView ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Reply to requests for our field item view.
			nfRadio.channel( 'views' ).reply( 'get:fieldItem', this.getFieldItem );

			nfRadio.channel( 'views' ).reply( 'get:fieldLayout', this.getFieldLayout );
		},

		getFieldItem: function( model ) {
			return fieldItemView;
		},

		getFieldLayout: function() {
			return fieldLayoutView;
		}

	});

	return controller;
} );
/**
 * If a form has at least one field error, we should disable the submit button and add a form error.
 * If a form had errors, but all the field errors have been removed, we should remove the form error.
 *
 * @since 3.0
 */
define('controllers/formErrors',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			/*
			 * Listen for error messages being added to and removed from fields.
			 */
			this.listenTo( nfRadio.channel( 'fields' ), 'add:error', this.addError );
			this.listenTo( nfRadio.channel( 'fields' ), 'remove:error', this.removeError );

			/*
			 * Respond to requests to get form errors
			 */
			nfRadio.channel( 'form' ).reply( 'get:errors', this.getFormErrors );
		},

		addError: function( fieldModel, errorID, errorMsg ) {
			var formModel = nfRadio.channel( 'app' ).request( 'get:form', fieldModel.get( 'formID' ) );
			/*
			 * We store our errors in this object by field ID so that we don't have to loop over all our fields when we're testing for errors.
			 * They are stored as an object within an array, using the field ID as the key.
			 *
			 * If we haven't setup an array item for this field, set it as an object.
			 */
			if ( 'undefined' == typeof formModel.get( 'fieldErrors' )[ fieldModel.get( 'id' ) ] ) {
				formModel.get( 'fieldErrors' )[ fieldModel.get( 'id' ) ] = {};
			}
			// Add an error to our tracking array
			formModel.get( 'fieldErrors' )[ fieldModel.get( 'id' ) ][ errorID ] = errorMsg;
			/*
			 * We have at least one field error, so submmission should be prevented.
			 * Add a form error.
			 */
			nfRadio.channel( 'form-' + fieldModel.get( 'formID' ) ).request( 'add:error', 'field-errors', formModel.get( 'settings' ).formErrorsCorrectErrors );
		},

		removeError: function( fieldModel, errorID ) {
			var formModel = nfRadio.channel( 'app' ).request( 'get:form', fieldModel.get( 'formID' ) );
			// Remove this error ID from our tracking array.
			formModel.get( 'fieldErrors' )[ fieldModel.get( 'id' ) ] = _.omit( formModel.get( 'fieldErrors' )[ fieldModel.get( 'id' ) ], errorID );
			/*
			 * If we don't have any more error IDs on this field, then we need to remove this field from the array.
			 *
			 * Then, if the fieldErrors tracking array has a length of 0, we remove our form error, because all field errors have been dealt with.
			 */
			if ( 0 == _.size( formModel.get( 'fieldErrors' )[ fieldModel.get( 'id' ) ] ) ) {
				delete formModel.get( 'fieldErrors' )[ fieldModel.get( 'id' ) ];
			}

			if ( 0 == _.size( formModel.get( 'fieldErrors' ) ) ) {
				// Remove our form error.
				nfRadio.channel( 'form-' + fieldModel.get( 'formID' ) ).request( 'remove:error', 'field-errors' );
			}
		},

		getFormErrors: function( formID ) {
			var formModel = nfRadio.channel( 'app' ).request( 'get:form', formID );
			var errors = false;
			
			if ( formModel ) {
				/*
				 * Check to see if we have any errors on our form model.
				 */
				if ( 0 !== formModel.get( 'errors' ).length ) {
					_.each( formModel.get( 'errors' ).models, function( error ) {
						errors = errors || {};
						errors[ error.get( 'id' ) ] = error.get( 'msg' );
					} );						
				}

				
			}
			return errors;
		}
	});

	return controller;
} );
/**
 * Handles submission of our form.
 */
define('controllers/submit',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'forms' ), 'init:model', this.registerSubmitHandler );
		},

		/**
		 * Register the submission handler function.
		 *
		 * @since  3.0
		 * @param  Backbone.model 	formModel
		 * @return void
		 */
		registerSubmitHandler: function( formModel ) {
			nfRadio.channel( 'form-' + formModel.get( 'id' ) ).reply( 'submit', this.submit );
		},

		/**
		 * Handles the actual submission of our form.
		 * When we submit:
		 *
		 * 1) Send out a message saying that we're about to begin form submission.
		 * 2) Check the form for errors
		 * 3) Submit the data
		 * 4) Send out a message with our response
		 *
		 * @since  3.0
		 * @param  Backbone.model 	formModel
		 * @return void
		 */
		submit: function( formModel ) {

			/*
			 * Send out a radio message saying that we're about to begin submitting.
			 * First we send on the generic forms channel, and then on the form-specific channel.
			 */
			nfRadio.channel( 'forms' ).trigger( 'before:submit', formModel );
			nfRadio.channel( 'form-' + formModel.get( 'id' ) ).trigger( 'before:submit', formModel );

			/*
			 * Validate our field models.
			 */
			var validate = nfRadio.channel( 'forms' ).request( 'maybe:validate', formModel );
		 	if( false !== validate ){

                // When validating all fields, set clean to false to force validation.
                _.each( formModel.get( 'fields' ).models, function( fieldModel ) {
                    fieldModel.set( 'clean', false );
                } );

				/*
				 * This method is defined in our models/fieldCollection.js file,
				 * except where overridden by an add-on (ie Layout & Styles).
				 */
				formModel.get( 'formContentData' ).validateFields();
			}

			var submit = nfRadio.channel( 'form-' + formModel.get( 'id' ) ).request( 'maybe:submit', formModel );
			if ( false == submit ) {
				nfRadio.channel( 'forms' ).trigger( 'submit:cancel', formModel );
				nfRadio.channel( 'form-' + formModel.get( 'id' ) ).trigger( 'submit:cancel', formModel );
				return;
			}

			if( false !== validate ){

				// Ignore non-blocking errors.
				var blockingFormErrors = _.filter( formModel.get( 'errors' ).models, function( error ){

					// Ignore email action related errors.
					if( 'invalid_email' == error.get( 'id' ) || 'email_not_sent' == error.get( 'id' ) ) return false;

					return true; // Error is blocking.
				});

				/*
				 * Make sure we don't have any form errors before we submit.
				 * Return false if we do.
				 */
				if ( 0 != _.size( blockingFormErrors ) ) {
					nfRadio.channel( 'forms' ).trigger( 'submit:failed', formModel );
					nfRadio.channel( 'form-' + formModel.get( 'id' ) ).trigger( 'submit:failed', formModel );
					return false;
				}
			}

			/*
			 * Send out a radio message saying that we're about to begin submitting.
			 * First we send on the generic forms channel, and then on the form-specific channel.
			 */
			nfRadio.channel( 'forms' ).trigger( 'after:submitValidation', formModel );
			nfRadio.channel( 'form-' + formModel.get( 'id' ) ).trigger( 'after:submitValidation', formModel );

			/*
			 * Actually submit our form, and send out a message with our response.
			 */

 			var formID = formModel.get( 'id' );
			var fields = {};
			_.each( formModel.get( 'fields' ).models, function( field ) {
				var fieldDataDefaults = { value:field.get( 'value' ), id:field.get( 'id' ) };

				// Add field data at the field ID for efficient access.
				fields[ field.get( 'id' ) ] = nfRadio.channel( field.get( 'type' ) ).request( 'get:submitData', fieldDataDefaults, field ) || fieldDataDefaults;;
			} );
			var extra = formModel.get( 'extra' );
			var settings = formModel.get( 'settings' );
			delete settings.formContentData;
			var formData = JSON.stringify( { id: formID, fields: fields, settings: settings, extra: extra } );
			var data = {
				'action': 'nf_ajax_submit',
				'security': nfFrontEnd.ajaxNonce,
				'nonce_ts': nfFrontEnd.nonce_ts,
				'formData': formData
			}

			var that = this;

			jQuery.ajax({
			    url: nfFrontEnd.adminAjax,
			    type: 'POST',
			    data: data,
			    cache: false,
			   	success: function( data, textStatus, jqXHR ) {
			   		try {
				   		var response = data;
				        nfRadio.channel( 'forms' ).trigger( 'submit:response', response, textStatus, jqXHR, formModel.get( 'id' ) );
				    	nfRadio.channel( 'form-' + formModel.get( 'id' ) ).trigger( 'submit:response', response, textStatus, jqXHR );
				    	jQuery( document ).trigger( 'nfFormSubmitResponse', { response: response, id: formModel.get( 'id' ) } );
			   		} catch( e ) {
			   			console.log( e );
			   			console.log( 'Parse Error' );
						console.log( e );
			   		}

			    },
			    error: function( jqXHR, textStatus, errorThrown ) {
			        // Handle errors here
			        console.log('ERRORS: ' + errorThrown);
					console.log( jqXHR );

					try {
						var response = jQuery.parseJSON( jqXHR.responseText );
						nfRadio.channel( 'forms' ).trigger( 'submit:response', response, textStatus, jqXHR, formModel.get( 'id' ) );
						nfRadio.channel( 'form-' + formModel.get( 'id' ) ).trigger( 'submit:response', response, textStatus, jqXHR );
					} catch( e ) {
						console.log( 'Parse Error' );
					}

			        // STOP LOADING SPINNER
					nfRadio.channel( 'forms' ).trigger( 'submit:response', 'error', textStatus, jqXHR, errorThrown );
			    }
			});

		}

	});

	return controller;
} );

define( 'views/fieldCollection',['views/fieldLayout'], function( fieldLayout ) {
	var view = Marionette.CollectionView.extend({
		tagName: 'nf-fields-wrap',
		childView: fieldLayout

	});

	return view;
} );
/**
 * Default filters
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/defaultFilters',[ 'views/fieldCollection', 'models/fieldCollection' ], function( FieldCollectionView, FieldCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'form' ), 'before:filterData', this.registerDefaultDataFilter );
		},

		registerDefaultDataFilter: function( formModel ) {
			/*
			 * Set our default formContent load filter
			 */
			nfRadio.channel( 'formContent' ).request( 'add:loadFilter', this.defaultFormContentLoad, 10, this );
			/*
			 * Set our default formContentView.
			 */
			nfRadio.channel( 'formContent' ).request( 'add:viewFilter', this.defaultFormContentView, 10, this );
		},

		defaultFormContentLoad: function( formContentData, formModel, context ) {
			var fieldCollection = formModel.get( 'fields' );
			/*
			 * If we only have one load filter, we can just return the field collection.
			 */
			var formContentLoadFilters = nfRadio.channel( 'formContent' ).request( 'get:loadFilters' );
			var sortedArray = _.without( formContentLoadFilters, undefined );
			if ( 1 == sortedArray.length || 'undefined' == typeof formContentData || true === formContentData instanceof Backbone.Collection ) return formModel.get( 'fields' );

        	var fieldModels = _.map( formContentData, function( key ) {
        		return formModel.get( 'fields' ).findWhere( { key: key } );
        	}, this );

        	var currentFieldCollection = new FieldCollection( fieldModels );

        	fieldCollection.on( 'reset', function( collection ) {
        		var resetFields = [];
        		currentFieldCollection.each( function( fieldModel ) {
        			if ( 'submit' != fieldModel.get( 'type' ) ) {
        				resetFields.push( collection.findWhere( { key: fieldModel.get( 'key' ) } ) );
        			} else {
        				resetFields.push( fieldModel );
        			}
        		} );

                currentFieldCollection.options = { formModel: formModel };
        		currentFieldCollection.reset( resetFields );
        	} );

        	return currentFieldCollection;
        },

        defaultFormContentView: function() {
        	return FieldCollectionView;
        }

	});

	return controller;
} );
/**
 * Controller responsible for removing unique field errors.
 */

define('controllers/uniqueFieldError',[], function() {
	var controller = Marionette.Object.extend( {

		initialize: function() {
			/*
			 * Listen to keyup and field changes.
			 *
			 * If those fields have a unique field error, remove that error.
			 */
			this.listenTo( nfRadio.channel( 'fields' ), 'change:modelValue', this.removeError );
			this.listenTo( nfRadio.channel( 'fields' ), 'keyup:field', this.removeError );
			this.listenTo( nfRadio.channel( 'fields' ), 'blur:field', this.removeError );

		},

		removeError: function( el, model ) {
			model = model || el;
			/*
			 * Remove any unique field errors.
			 */
			nfRadio.channel( 'fields' ).request( 'remove:error', model.get( 'id' ), 'unique_field' );
		},

	});

	return controller;
} );
define( 'models/fieldRepeaterSetModel',[], function() {
	var model = Backbone.Model.extend( {

		initialize: function(fieldsets, options) {

			this.repeaterFieldModel = options.repeaterFieldModel;

			this.set( 'label', this.repeaterFieldModel.get('label') );

			nfRadio.channel( "field-repeater" ).reply( 'reset:repeaterFieldsets', this.resetRepeaterFieldsets, this );
			nfRadio.channel( "field-repeater" ).reply( 'get:repeaterFieldsets', this.getRepeaterFieldsets, this );
			nfRadio.channel( "field-repeater" ).reply( 'get:repeaterFields', this.getRepeaterFields, this );
			nfRadio.channel( "field-repeater" ).reply( 'get:repeaterFieldById', this.getRepeaterFieldById, this );
			
		},

		resetRepeaterFieldsets: function( models) {
			this.collection = {};
			this.collection.models = models;
		},

		getRepeaterFieldsets: function() {
			return this.collection.models;
		},

		getRepeaterFields: function() {
			let fieldsets = this.getRepeaterFieldsets();
			if(fieldsets.length <= 0 ) return;

			let fields = [];
			_.each(fieldsets, function(fieldset){
				const inFields = fieldset.get('fields');
				
				_.each( inFields.models, function( field ){
					fields.push( field );
				});
			});
			return fields;
		},

		getRepeaterFieldById: function( id ){
			let fields = this.getRepeaterFields();
			if(fields.length <= 0 ) return;

			let model;
			_.each(fields, function(field){
				if( field.id === id ){
					model = field;
				}
			});
			return model;
		}

	} );

	return model;
} );

define( 'models/fieldRepeaterSetCollection',['models/fieldRepeaterSetModel', 'models/fieldCollection' ], function( repeaterSetModel, fieldCollection ) {
	var collection = Backbone.Collection.extend( {
		model: repeaterSetModel,

		initialize: function( models, options ) {
			this.options = options;
		
			nfRadio.channel( "field-repeater" ).on( 'sort:fieldsets', this.sortIDs, this);
			nfRadio.channel( "field-repeater" ).on( 'remove:fieldset', this.removeSet, this );
			nfRadio.channel( "field-repeater" ).on( 'add:fieldset', this.addSet, this );

		},

		addSet: function(e) {
			//Get correct Field Model in case of multiple Repeater fields use
			const repeaterFieldID = jQuery(e.target).prev(".nf-repeater").data("field-id");
			const repeaterFieldModel = this.options.repeaterFieldModel.id === repeaterFieldID ? this.options.repeaterFieldModel : undefined;

			if(repeaterFieldModel !== undefined){
				//Create a new collection
				let fields = new fieldCollection( this.options.templateFields, { formModel: this.options.formModel, repeaterFieldModel: repeaterFieldModel } );
				//Add it th sets of collection
				this.add( { fields: fields }, {repeaterFieldModel: repeaterFieldModel } );
				//reset all fields IDs
				this.sortIDs();
			}
			
		},

		removeSet: function( fieldset ) {
			//Remove the fieldset
			this.remove( fieldset );
			//reset all fields IDs
			this.sortIDs();
		},

		sortIDs: function(){
			nfRadio.channel( "field-repeater" ).request( 'reset:repeaterFieldsets', this.models );
			//Reset repeater fields IDs when adding / removing a field
			_.each(this.models, function(fieldset, modelIndex){
				let fields = fieldset.get('fields');
				fieldset.set( 'index', modelIndex + 1 );
				_.each( fields.models, function( field ) {
					//Remove suffix if it has one
					cutEl = String(field.id).split('_')[0];
					//Update Suffix using fieldset index
					field.set("id", cutEl + "_" + modelIndex);					
				});
			});
			//Reload repeater field view ( collection of fieldsets updated )
			nfRadio.channel( 'field-repeater' ).trigger( 'rerender:fieldsets' );
		},

		beforeSubmit: function( sets ) {
			//Collect values of all fields in the repeater and create repeaterFieldValue object
			let fieldsetCollection = sets.models;
			if(fieldsetCollection.length > 0){
				let repeaterFieldValue = {};
				//Loop through fieldsets
				_.each( fieldsetCollection, function( fieldset ){
					let fields = fieldset.get('fields');
					//Loop through fields in each fieldsets
					_.each( fields.models, function( field ){
						//Get ID and Value to format and store them in the repeaterFieldValue object
						let value = field.get('value');
						let id = field.get('id');
						repeaterFieldValue[id] = {
							"value": value,
							"id": id
						}
					});
				});
				//Update repeater field value with repeaterFieldValue 
				nfRadio.channel( 'nfAdmin' ).request( 'update:field', this.options.repeaterFieldModel, repeaterFieldValue);
			}

		},

	} );
	return collection;
} );
define('controllers/fieldRepeater',[ 'models/fieldRepeaterSetCollection', 'models/fieldCollection' ], function( repeaterSetCollection, fieldCollection ) {
    var controller = Marionette.Object.extend({

        initialize: function () {
            this.listenTo( nfRadio.channel( 'repeater' ), 'init:model', this.initRepeater );
        },

        initRepeater: function ( model ) {
        	if ( 'undefined' == typeof model.collection.options.formModel ) {
        		return false;
        	}

        	let fields = new fieldCollection( model.get( 'fields' ), { formModel: model.collection.options.formModel } );
        	model.set( 'sets', new repeaterSetCollection( [ { fields: fields } ], { templateFields: model.get( 'fields' ), formModel: model.collection.options.formModel, repeaterFieldModel: model } ) );
        },

    });

    return controller;
});
define(
	'controllers/loadControllers',[
		'controllers/formData',
		'controllers/fieldError',
		'controllers/changeField',
		'controllers/changeEmail',
		'controllers/changeDate',
		'controllers/fieldCheckbox',
		'controllers/fieldCheckboxList',
		'controllers/fieldImageList',
		'controllers/fieldRadio',
		'controllers/fieldNumber',
		'controllers/mirrorField',
		'controllers/confirmField',
		'controllers/updateFieldModel',
		'controllers/submitButton',
		'controllers/submitDebug',
		'controllers/getFormErrors',
		'controllers/validateRequired',
		'controllers/submitError',
		'controllers/actionRedirect',
		'controllers/actionSuccess',
		'controllers/fieldSelect',
		'controllers/coreSubmitResponse',
		'controllers/fieldProduct',
		'controllers/fieldTotal',
		'controllers/fieldQuantity',
		'controllers/calculations',
		'controllers/dateBackwardsCompat',
		'controllers/fieldDate',
		'controllers/fieldRecaptcha',
		'controllers/fieldRecaptchaV3',
		'controllers/fieldHTML',
		'controllers/helpText',
		'controllers/fieldTextbox',
		'controllers/fieldTextareaRTE',
		'controllers/fieldStarRating',
		'controllers/fieldTerms',
		'controllers/formContentFilters',
		'controllers/loadViews',
		'controllers/formErrors',
		'controllers/submit',
		'controllers/defaultFilters',
		'controllers/uniqueFieldError',
		'controllers/fieldRepeater',
	],
	function(
		FormData,
		FieldError,
		ChangeField,
		ChangeEmail,
		ChangeDate,
		FieldCheckbox,
		FieldCheckboxList,
		FieldImageList,
		FieldRadio,
		FieldNumber,
		MirrorField,
		ConfirmField,
		UpdateFieldModel,
		SubmitButton,
		SubmitDebug,
		GetFormErrors,
		ValidateRequired,
		SubmitError,
		ActionRedirect,
		ActionSuccess,
		FieldSelect,
		CoreSubmitResponse,
		FieldProduct,
		FieldTotal,
		FieldQuantity,
		Calculations,
		DateBackwardsCompat,
		FieldDate,
		FieldRecaptcha,
		FieldRecaptchaV3,
		FieldHTML,
		HelpText,
		FieldTextbox,
		FieldTextareaRTE,
		FieldStarRating,
		FieldTerms,
		FormContentFilters,
		LoadViews,
		FormErrors,
		Submit,
		DefaultFilters,
		UniqueFieldError,
		FieldRepeater
	) {
		var controller = Marionette.Object.extend( {
			initialize: function() {

				/**
				 * App Controllers
				 */
				new LoadViews();
				new FormErrors();
				new Submit();
				
				/**
				 * Field type controllers
				 */
				new FieldCheckbox();
				new FieldCheckboxList();
				new FieldImageList();
				new FieldRadio();
				new FieldNumber();
				new FieldSelect();
				new FieldProduct();
				new FieldTotal();
				new FieldQuantity();
				new FieldRecaptcha();
				new FieldRecaptchaV3();
				new FieldHTML();
				new HelpText();
				new FieldTextbox();
				new FieldTextareaRTE();
				new FieldStarRating();
				new FieldTerms();
				new FormContentFilters();
				new UniqueFieldError();
				new FieldRepeater();
				
				/**
				 * Misc controllers
				 */
				new FieldError();
				new ChangeField();
				new ChangeEmail();
				new ChangeDate();
				
				new MirrorField();
				new ConfirmField();
				new UpdateFieldModel();
				new SubmitButton();
				new SubmitDebug();
				new GetFormErrors();
				new ValidateRequired();
				new SubmitError();
				new ActionRedirect();
				new ActionSuccess();
				
				new CoreSubmitResponse();
				new Calculations();

				new DefaultFilters();

				/**
				 * Data controllers
				 */
				new DateBackwardsCompat();
				new FieldDate();
				new FormData();
				
			}
		});

		return controller;
} );

define( 'views/beforeForm',[], function( ) {

	var view = Marionette.ItemView.extend({
		tagName: "nf-section",
		template: "#tmpl-nf-before-form",

	});

	return view;
} );
define( 'views/formErrorItem',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'nf-section',
		template: '#tmpl-nf-form-error',

		onRender: function() {
			// this.$el = this.$el.children();
			// this.$el.unwrap();
			// this.setElement( this.$el );
		},
	});

	return view;
} );
define( 'views/formErrorCollection',['views/formErrorItem'], function( formErrorItem ) {
	var view = Marionette.CollectionView.extend({
		tagName: "nf-errors",
		childView: formErrorItem
	});

	return view;
} );
define( 'views/honeyPot',[], function() {
    var view = Marionette.ItemView.extend({
        tagName: 'nf-section',
        template: '#tmpl-nf-form-hp',

        events: {
        	'keyup .nf-field-hp': 'maybeError',
            'change .nf-field-hp': 'maybeError'
        },

        maybeError: function( e ) {
            /*
             * If we have an empty honeyPot field, remove the honeypot form error.
             * If we do not have an empty honeyPot field, add the honeypot form error.
             */
            if ( 0 == jQuery( e.target ).val().length ) {
                nfRadio.channel( 'form-' + this.model.get( 'id' ) ).request( 'remove:error', 'honeyPot' );
            } else {
                var formModel  = nfRadio.channel( 'app'    ).request( 'get:form',  this.model.get( 'id' ) );
                nfRadio.channel( 'form-' + this.model.get( 'id' ) ).request( 'add:error', 'honeyPot', formModel.get( 'settings' ).honeypotHoneypotError );
            }
        }
    });

    return view;
} );
define( 'views/afterFormContent',['views/formErrorCollection', 'views/honeyPot'], function( FormErrors, HoneyPot ) {

    var view = Marionette.LayoutView.extend({
        tagName: "nf-section",
        template: "#tmpl-nf-after-fields",

		regions: {
			errors: ".nf-form-errors",
            hp: ".nf-form-hp"
		},

        onShow: function() {
        	this.errors.show( new FormErrors( { collection: this.model.get( 'errors' ) } ) );
            this.hp.show( new HoneyPot( { model: this.model } ) );
        }

    });

    return view;
} );
define( 'views/beforeFormContent',[], function( ) {

    var view = Marionette.ItemView.extend({
        tagName: "nf-section",
        template: "#tmpl-nf-before-fields",

        templateHelpers: function () {
            return {

                renderFieldsMarkedRequired: function() {

                    var requiredFields = this.fields.filter( { required: 1 } );
                    return ( requiredFields.length ) ? this.fieldsMarkedRequired : '';
                },
            };
        },

    });

    return view;
} );
define( 'views/formLayout',[ 'views/afterFormContent', 'views/beforeFormContent', 'models/fieldCollection' ], function( AfterFormContent, BeforeFormContent, FieldCollection ) {

	var view = Marionette.LayoutView.extend({
		tagName: "nf-section",
		template: "#tmpl-nf-form-layout",

		regions: {
			beforeFormContent: ".nf-before-form-content",
			formContent: ".nf-form-content",
			afterFormContent: ".nf-after-form-content"
		},

		initialize: function() {
			nfRadio.channel( 'form-' + this.model.get( 'id' ) ).reply( 'get:el', this.getEl, this );
			
			/*
			 * If we need to hide a form, set the visibility of this form to hidden.
			 */
			 this.listenTo( this.model, 'hide', this.hide );
		},

		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );
		},

		onShow: function() {
			this.beforeFormContent.show( new BeforeFormContent( { model: this.model } ) );
			
			/*
			 * Set our formContentData to our form setting 'formContentData'
			 */
			var formContentData = this.model.get( 'formContentData' );
			
			/*
			 * Check our fieldContentViewsFilter to see if we have any defined.
			 * If we do, overwrite our default with the view returned from the filter.
			 */
			var formContentViewFilters = nfRadio.channel( 'formContent' ).request( 'get:viewFilters' );
			
			/* 
			* Get our first filter, this will be the one with the highest priority.
			*/
			var sortedArray = _.without( formContentViewFilters, undefined );
			var callback = _.first( sortedArray );
			formContentView = callback();
			
			var options = {
				data: formContentData,
				formModel: this.model
			};
			
			/*
			 * If we have a collection, pass the returned data as the collection.
			 *
			 * If we have a model, pass the returned data as the collection.
			 */
			if ( false !== formContentData instanceof Backbone.Collection ) {
				options.collection = formContentData;
			} else if ( false !== formContentData instanceof Backbone.Model ) {
				options.model = formContentData;
			}

			this.formContent.show( new formContentView( options ) );
			this.afterFormContent.show( new AfterFormContent( { model: this.model } ) );
		},

		getEl: function() {
			return this.el;
		},

        templateHelpers: function () {
            return {

                renderClasses: function() {
                    return '';
                }

            };
        },

        hide: function() {
        	jQuery( this.el ).hide();
        }

	});

	return view;
} );
define( 'views/afterForm',[], function( ) {

	var view = Marionette.ItemView.extend({
		tagName: "nf-section",
		template: "#tmpl-nf-after-form",
		
	});

	return view;
} );
define( 'views/mainLayout',['views/beforeForm', 'views/formLayout', 'views/afterForm'], function( BeforeForm, FormLayout, AfterForm ) {

	var view = Marionette.LayoutView.extend({
		template: '#tmpl-nf-layout',

		regions: {
			responseMsg: '.nf-response-msg',
			beforeForm: '.nf-before-form',
			formLayout: '.nf-form-layout',
			afterForm: '.nf-after-form'
		},

		initialize: function() {
			this.$el = jQuery( '#nf-form-' + this.model.id + '-cont' );
			this.el = '#nf-form-' + this.model.id + '-cont';

			this.render();

			this.beforeForm.show( new BeforeForm( { model: this.model } ) );
			this.formLayout.show( new FormLayout( { model: this.model, fieldCollection: this.options.fieldCollection } ) );
			this.afterForm.show( new AfterForm( { model: this.model } ) );

			/*
			 * If we need to hide a form, set the visibility of this form to hidden.
			 */
			 this.listenTo( this.model, 'hide', this.hide );
		},

        hide: function() {
        	jQuery( this.el ).find( '.nf-form-title' ).hide();
        }

	});

	return view;
} );
// const Intl = require('intl');

// class nfLocaleConverter {
var nfLocaleConverter = function(newLocale, thousands_sep, decimal_sep) {

    // constructor(newLocale = 'en-US', thousands_sep, decimal_sep) {
        if ('undefined' !== typeof newLocale && 0 < newLocale.length) {
            this.locale = newLocale.split('_').join('-');
        } else {
            this.locale = 'en-US';
        }

        this.thousands_sep = thousands_sep || ',';
        this.decimal_sep = decimal_sep || '.';
    // }

    this.uniqueElememts = function( value, index, self ) {
        return self.indexOf(value) === index;
    }

    this.numberDecoder = function(num) {
        num = num.toString();
        // let thousands_sep = ',';
        var formatted = '';

        // Account for negative numbers.
        var negative = false;
        
        if ('-' === num.charAt(0)) {
            negative = true;
            num = num.replace( '-', '' );
        }
        
        // Account for a space as the thousands separator.
        // This pattern accounts for all whitespace characters (including thin space).
        num = num.replace( /\s/g, '' );
        num = num.replace( /&nbsp;/g, '' );

        // Determine what our existing separators are.
        var myArr = num.split('');
        var separators = myArr.filter(function(el) {
            return !el.match(/[0-9]/);
          });
          
        var final_separators = separators.filter(this.uniqueElememts);
        
        switch( final_separators.length ) {
            case 0:
                formatted = num;
                break;
            case 1:
                var replacer = '';
                if ( 1 == separators.length ) {
                    separator = separators.pop();
                    var sides = num.split(separator);
                    var last = sides.pop();
                    if ( 3 == last.length && separator == this.thousands_sep ) {
                        replacer = '';
                    } else {
                        replacer = '.';
                    }
                } else {
                    separator = final_separators.pop();
                }

                formatted = num.split(separator).join(replacer);
                break;
            case 2:
                var find_one = final_separators[0];
                var re_one;
                if('.' === find_one) {
                    re_one = new RegExp('[.]', 'g');
                } else {
                    re_one = new RegExp(find_one, 'g');
                }
                formatted = num.replace(re_one, '');
                
                var find_two = final_separators[1];
                
                var re_two;
                if('.' === find_two) {
                    re_two = new RegExp('[.]', 'g');
                } else {
                    re_two = new RegExp(find_two, 'g');
                }
                formatted = formatted.replace(re_two, '.' );
                break;
            default:
            return 'NaN';
        }

        if ( negative ) {
            formatted = '-' + formatted;
        }
        this.debug('Number Decoder ' + num + ' -> ' + formatted );
        return formatted;
    }

    this.numberEncoder = function(num, percision) {
        num = this.numberDecoder(num);

        return Intl.NumberFormat(this.locale, { minimumFractionDigits: percision, maximumFractionDigits: percision }).format(num);
    }

    this.debug = function(message) {
        if ( window.nfLocaleConverterDebug || false ) console.log(message);
    }
}

// module.exports = nfLocaleConverter;
define("../nfLocaleConverter", function(){});

/*
 * Because our backbone listens to .change() events on elements, changes made using jQuery .val() don't bubble properly.
 * This patch overwrites the default behaviour of jQuery .val() so that IF the item has an nf-element class, we fire a change event.
 */
( function( jQuery ) {
	/*
	 * Store our original .val() function.
	 */
    var originalVal = jQuery.fn.val;
    /*
     * Create our own .val() function.
     */
    jQuery.fn.val = function(){
        var prev;
        /* 
         * Store a copy of the results of the original .val() call.
         * We use this to make sure that we've actually changed something.
         */
        if( arguments.length > 0 ){
            prev = originalVal.apply( this,[] );
        }
        /*
         * Get the results of the original .val() call. 
         */
        var result = originalVal.apply( this, arguments );

        /*
         * If we have arguments, we have actually made a change, AND this has the nf-element class, trigger .change().
         */
        if( arguments.length > 0 && prev != originalVal.apply( this, [] ) && jQuery( this ).hasClass( 'nf-element' ) ) {
			jQuery(this).change();
        }

        return result;
    };
} ) ( jQuery );

jQuery( document ).ready( function( $ ) {
	require( [ 'models/formCollection', 'models/formModel', 'models/fieldCollection', 'controllers/loadControllers', 'views/mainLayout', '../nfLocaleConverter'], function( formCollection, FormModel, FieldCollection, LoadControllers, mainLayout ) {

		if( 'undefined' == typeof nfForms ) {
			/*
			 * nfForms is not defined. This means that something went wrong loading the form data.
			 * Bail form setup and empty the form containers to remove any loading animations.
			 */
			jQuery( '.nf-form-cont' ).empty();
			return;
		}

		var NinjaForms = Marionette.Application.extend({
			forms: {},
			initialize: function( options ) {
				var that = this;
				Marionette.Renderer.render = function(template, data){
					var template = that.template( template );
					return template( data );
				};

				// Underscore one-liner for getting URL Parameters
				this.urlParameters = _.object(_.compact(_.map(location.search.slice(1).split('&'), function(item) {  if (item) return item.split('='); })));

				if( 'undefined' != typeof this.urlParameters.nf_resume ) {
					this.listenTo(nfRadio.channel('form-' + this.urlParameters.nf_resume), 'loaded', this.restart);
				}

				nfRadio.channel( 'app' ).reply( 'locale:decodeNumber', this.decodeNumber);

				nfRadio.channel( 'app' ).reply( 'locale:encodeNumber',this.encodeNumber);

				var loadControllers = new LoadControllers();
				nfRadio.channel( 'app' ).trigger( 'after:loadControllers' );

				nfRadio.channel( 'app' ).reply( 'get:template', this.template );			},
			
			onStart: function() {
				var formCollection = nfRadio.channel( 'app' ).request( 'get:forms' );
				_.each( formCollection.models, function( form, index ) {
					var layoutView = new mainLayout( { model: form, fieldCollection: form.get( 'fields' ) } );			
					nfRadio.channel( 'form' ).trigger( 'render:view', layoutView );
					jQuery( document ).trigger( 'nfFormReady', layoutView );
				} );
			},

			restart: function( formModel ) {
				if( 'undefined' != typeof this.urlParameters.nf_resume ){
					var data = {
						'action': 'nf_ajax_submit',
						'security': nfFrontEnd.ajaxNonce,
						'nf_resume': this.urlParameters
					};

					nfRadio.channel( 'form-' + formModel.get( 'id' ) ).trigger( 'disable:submit' );
					nfRadio.channel( 'form-' + formModel.get( 'id' ) ).trigger( 'processingLabel' );

					this.listenTo( nfRadio.channel( 'form' ), 'render:view', function() {
						/**
						 * TODO: This needs to be re-worked for backbone. It's not dynamic enough.
						 */
						/*
						 * Hide form fields (but not the submit button).
						 */
						jQuery( '#nf-form-' + formModel.get( 'id' ) + '-cont .nf-field-container:not(.submit-container)' ).hide();
					});

					// TODO: Refactor Duplication
					jQuery.ajax({
						url: nfFrontEnd.adminAjax,
						type: 'POST',
						data: data,
						cache: false,
						success: function( data, textStatus, jqXHR ) {
							try {
						   		var response = data;
						        nfRadio.channel( 'forms' ).trigger( 'submit:response', response, textStatus, jqXHR, formModel.get( 'id' ) );
						    	nfRadio.channel( 'form-' + formModel.get( 'id' ) ).trigger( 'submit:response', response, textStatus, jqXHR );
							} catch( e ) {
								console.log( 'Parse Error' );
							}

					    },
					    error: function( jqXHR, textStatus, errorThrown ) {
					        // Handle errors here
					        console.log('ERRORS: ' + textStatus);
					        // STOP LOADING SPINNER
							nfRadio.channel( 'forms' ).trigger( 'submit:response', 'error', textStatus, jqXHR, errorThrown );
					    }
					});
				}
			},

			template: function( template ) {
				return _.template( $( template ).html(),  {
					evaluate:    /<#([\s\S]+?)#>/g,
					interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
					escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
					variable:    'data'
				} );
			},

			encodeNumber: function(num) {
				var localeConverter = new nfLocaleConverter(nfi18n.siteLocale, nfi18n.thousands_sep, nfi18n.decimal_point);

				return localeConverter.numberEncoder(num);
			},

			decodeNumber: function(num) {
				var localeConverter = new nfLocaleConverter(nfi18n.siteLocale, nfi18n.thousands_sep, nfi18n.decimal_point);

				return localeConverter.numberDecoder(num);
			}
		});
	
		var ninjaForms = new NinjaForms();
		ninjaForms.start();		
	} );
} );

define("main", function(){});

}());
//# sourceMappingURL=front-end.js.map
