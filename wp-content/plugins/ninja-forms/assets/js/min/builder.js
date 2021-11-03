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

/**
 * Renders an application menu item from a domain model.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/menuItem',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-app-menu-item',

		initialize: function() {
			// Listen for domain changes and re-render when we detect one.
			this.listenTo( nfRadio.channel( 'app' ), 'change:currentDomain', this.render );
			// When we change the model (to disable it, for example), re-render.
			this.model.on( 'change', this.render, this );
		},

		/**
		 * When we render this view, remove the extra <div> tag created by backbone.
		 * 
		 * @since  3.0
		 * @return void
		 */
		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );
		},

		// Listen for clicks on our app menu.
		events: {
			'click a': 'clickAppMenu'
		},

		/**
		 * When we click on a menu item, fire a radio event.
		 * This lets us separate the logic from the click event and view.
		 * We pass this.model so that we know what item was clicked.
		 * 
		 * @since  3.0
		 * @param  Object	e event
		 * @return return
		 */
		clickAppMenu: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:menu', e, this.model );
		},

		/**
		 * These functions are available to templates, and help us to remove logic from template files.
		 * 
		 * @since  3.0
		 * @return Object
		 */
		templateHelpers: function() {
			return {
				/**
				 * If we have any dashicons in our model, render them.
				 * 
				 * @since  3.0
				 * @return string
				 */
				renderDashicons: function() {
					if ( ! this.dashicons ) return '';

					var icon = document.createElement( 'span' );
					icon.classList.add( 'dashicons' );
					icon.classList.add( this.dashicons );

					return icon.outerHTML;
				},
				/**
				 * Render classes for our menu item, including active.
				 * 
				 * @since  3.0
				 * @return string
				 */
				renderClasses: function() {
					var classes = this.classes;
					var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
					if ( currentDomain.get( 'id' ) == this.id ) {
						classes += ' active';
					}
					return classes;
				},
				/**
				 * If our menu is a link (like preview), render its url.
				 * 
				 * @since  3.0
				 * @return string
				 */
				renderUrl: function() {
					if ( '' != this.url ) {
						var formModel = nfRadio.channel( 'app' ).request( 'get:formModel' );
						return this.url + formModel.get( 'id' );
					} else {
						return '#';
					}
				},
				/**
				 * If our menu is a link (like preview), render its target.
				 * 
				 * @since  3.0
				 * @return string
				 */
				renderTarget: function() {
					if ( '' != this.url ) {
						return '_blank';
					} else {
						return '_self';
					}
				},

				/**
				 * If our menu item is disabled, output 'disabled'
				 * 
				 * @since  3.0
				 * @return string
				 */
				renderDisabled: function() {
					if ( this.disabled ) {
						return 'disabled';
					} else {
						return '';
					}
				}
			}
		}

	});

	return view;
} );

/**
 * Collection view that takes our app menu items and renders an individual view for each.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/menu',['views/app/menuItem'], function( appMenuItemView ) {
	var view = Marionette.CollectionView.extend( {
		tagName: 'div',
		childView: appMenuItemView,

		/**
		 * When we show this view, get rid of the extra <div> tag added by backbone.
		 * 
		 * @since  3.0
		 * @return void
		 */
		onShow: function() {
			jQuery( this.el ).find( 'li:last' ).unwrap();
		}
	} );

	return view;
} );
/**
 * Renders the action buttons to the right of the app menu. i.e. Publish
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/menuButtons',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'span',
		template: '#tmpl-nf-app-header-action-button',

		initialize: function() {
			// Listen to changes on the app 'clean' state. When it changes, re-render.
			this.listenTo( nfRadio.channel( 'app' ), 'change:clean', this.render, this );
			this.listenTo( nfRadio.channel( 'app' ), 'change:loading', this.render, this );

			this.listenTo( nfRadio.channel( 'app' ), 'response:updateDB', this.bounceIcon, this );
		},

		/**
		 * These functions are available to templates, and help us to remove logic from template files.
		 * 
		 * @since  3.0
		 * @return Object
		 */
		templateHelpers: function () {
			var that = this;
	    	return {

	    		/**
	    		 * Render our Publish button. If we're loading, render the loading version.
	    		 *
	    		 * @since  3.0
	    		 * @return string
	    		 */
	    		renderPublish: function() {
	    			if ( that.publishWidth ) {
	    				this.publishWidth = that.publishWidth + 'px';
	    			} else {
	    				this.publishWidth = 'auto';
	    			}

	    			if ( nfRadio.channel( 'app' ).request( 'get:setting', 'loading' ) ) {
	    				var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-add-header-publish-loading' );
	    			} else {
	    				var template = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-app-header-publish-button' );
	    			}
	    			return template( this );
	    		},

	    		/**
	    		 * If our app state is clean, disable publish.
	    		 * 
	    		 * @since  3.0
	    		 * @return string
	    		 */
	    		maybeDisabled: function() {
	    			if ( nfRadio.channel( 'app' ).request( 'get:setting', 'clean' ) ) {
	    				return 'disabled';
	    			} else {
	    				return '';
	    			}
	    		},

	    		/**
	    		 * [DEPRECATED] If our app isn't clean, render our 'viewChanges' button.
	    		 * @since  version
	    		 * @return {[type]} [description]
	    		 */
	    		maybeRenderCancel: function() {
					return '';
				},

	    		renderPublicLink: function() {
						// Don't show public link if the form has a temp ID
						var formModel = Backbone.Radio.channel('app').request('get:formModel');
						if (isNaN(formModel.get('id'))) { return };
						// Otherwise, display normally
	    			var publicLink = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-app-header-public-link' );
	    				return publicLink( this );
	    		},
			};
		},

		onShow: function() {
			var publishEL = jQuery( this.el ).find( '.publish' );
			// this.publishWidth = jQuery( publishEL ).outerWidth( true );
		},

		/**
		 * Listen for clicks on the Publish or view changes button.
		 * @type {Object}
		 */
		events: {
			'click .publish': 'clickPublish',
			'click .viewChanges': 'clickViewChanges',
			'click .publicLink': 'clickPublicLink',
		},

		/**
		 * When we click publish, trigger a radio event.
		 * This lets us separate the logic from the click event and view.
		 * 
		 * @since  3.0
		 * @param  Object 	e event
		 * @return void
		 */
		clickPublish: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:publish', e );
		},

		/**
		 * When we click view changes, trigger a radio event.
		 * This lets us separate the logic from the click event and view.
		 * 
		 * @since  3.0
		 * @param  Object 	e event
		 * @return void
		 */
		clickViewChanges: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:viewChanges', e );
		},

		clickPublicLink: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:publicLink', e );
		},

		bounceIcon: function( changeModel ) {
			jQuery( this.el ).find( '.dashicons-backup' ).effect( 'bounce', { times: 3 }, 600 );
		}

	});

	return view;
} );

/**
 * Renders the action buttons to the right of the app menu. i.e. Publish
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/mobileMenuButton',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'span',
		template: '#tmpl-nf-mobile-menu-button',

		initialize: function() {
			// Listen to changes on the app 'clean' state. When it changes, re-render.
			this.listenTo( nfRadio.channel( 'app' ), 'change:clean', this.render, this );
		},

		/**
		 * These functions are available to templates, and help us to remove logic from template files.
		 * 
		 * @since  3.0
		 * @return Object
		 */
		templateHelpers: function () {
			var that = this;
	    	return {
	    		/**
	    		 * If our app state is clean, disable button.
	    		 * 
	    		 * @since  3.0
	    		 * @return string
	    		 */
	    		maybeDisabled: function() {
	    			if ( nfRadio.channel( 'app' ).request( 'get:setting', 'clean' ) ) {
	    				return 'disabled';
	    			} else {
	    				return '';
	    			}
	    		}
			};
		},

		/**
		 * Listen for clicks on the mobile menu button.
		 * @type {Object}
		 */
		events: {
			'click .nf-mobile-menu': 'clickMobileMenu'
		},

		/**
		 * When we click publish, trigger a radio event.
		 * This lets us separate the logic from the click event and view.
		 * 
		 * @since  3.0
		 * @param  Object 	e event
		 * @return void
		 */
		clickMobileMenu: function( e) {
			var builderEl = nfRadio.channel( 'app' ).request( 'get:builderEl' );
			jQuery( builderEl ).toggleClass( 'nf-menu-expand' );
		}
	});

	return view;
} );
/**
 * Main application header. Includes links to all of our domains.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/header',['views/app/menu', 'views/app/menuButtons', 'views/app/mobileMenuButton'], function( appMenuCollectionView, appMenuButtonsView, mobileMenuButtonView ) {
	var view = Marionette.LayoutView.extend( {
		tagName: 'div',
		template: '#tmpl-nf-app-header',

		regions: {
			// Menu is our main app menu.
			menu: '.nf-app-menu',
			// Buttons represents the 'view changes' and 'Publish' buttons.
			buttons: '.nf-app-buttons',
			mobileMenuButton: '.nf-mobile-menu-button'
		},

		/**
		 * Since this is a layout region, we need to fill the two areas: menu and buttons whenever we show this view.
		 * 
		 * @since  3.0
		 * @return void
		 */
		onRender: function() {
			// Get our domains
			var appDomainCollection = nfRadio.channel( 'app' ).request( 'get:domainCollection' );
			// show the menu area using the appropriate view, passing our domain collection.
			this.menu.show( new appMenuCollectionView( { collection: appDomainCollection } ) );
			this.buttons.show( new appMenuButtonsView() );
			this.mobileMenuButton.show( new mobileMenuButtonView() );
		},

		events: {
			'click #nf-logo': 'clickLogo'
		},

		clickLogo: function( e ) {
			
		}

	} );

	return view;
} );
/**
 * Renders our sub-header. i.e. add new field, add new action, etc.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/subHeader',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-sub-header'
	});

	return view;
} );
/**
 * Renders our builder header.
 *
 * This is a layout view and handles two regions:
 * app - menu/buttons
 * subapp - title, add new field, etc.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/builderHeader',[ 'views/app/header', 'views/app/subHeader' ], function( appHeaderView, appSubHeaderView ) {

	var view = Marionette.LayoutView.extend({
		tagName: "div",
		template: "#tmpl-nf-header",

		regions: {
			app: "#nf-app-header",
			formTitle: "#nf-app-form-title",
			appSub: "#nf-app-sub-header"
		},

		initialize: function() {
			this.listenTo( nfRadio.channel( 'app' ), 'change:currentDomain', this.changeSubHeader );
		},

		onShow: function() {
			this.app.show( new appHeaderView() );

			var formData = nfRadio.channel( 'app' ).request( 'get:formModel' );
			var formSettings = formData.get( 'settings' );

			var formTitleView = nfRadio.channel( 'views' ).request( 'get:formTitle' );
			this.formTitle.show( new formTitleView( { model: formSettings } ) );

			this.changeSubHeader();
		},

		changeSubHeader: function() {
			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
			var subHeaderView = currentDomain.get( 'getSubHeaderView' ).call( currentDomain );
			this.appSub.show( subHeaderView );
		}
	});

	return view;
} );
/**
 * Renders our builder.
 *
 * This is a layout view and handles three regions:
 * gutterLeft - gutter to the left of our main content area
 * body - main content area
 * gutterRight - gutter to the right of our main content area
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2016 WP Ninjas
 * @since 3.0
 */
define( 'views/app/main',[], function() {

	var view = Marionette.LayoutView.extend({
		tagName: 'div',
		template: '#tmpl-nf-main',
		className: 'nf-main-test',
		maybeDone: false,

		offsetRight: false,
		offsetLeft: false,

		regions: {
			gutterLeft: '#nf-main-gutter-left',
			body: '#nf-main-body',
			gutterRight: '#nf-main-gutter-right'
		},

		initialize: function() {
			this.listenTo( nfRadio.channel( 'app' ), 'change:currentDomain', this.render );
			nfRadio.channel( 'app' ).reply( 'get:mainEl', this.getMainEl, this );

			/*
			 * Make sure that our gutters resize to match our screen upon resize or drawer open/close.
			 */
			jQuery( window ).on( 'resize', { context: this }, this.resizeBothGutters );
			this.listenTo( nfRadio.channel( 'drawer' ), 'before:open', this.setBothGuttersAbsolute );
			this.listenTo( nfRadio.channel( 'drawer' ), 'opened', this.setBothGuttersFixed );
			this.listenTo( nfRadio.channel( 'drawer' ), 'before:close', this.setBothGuttersAbsolute );
			this.listenTo( nfRadio.channel( 'drawer' ), 'closed', this.setBothGuttersFixed );
			// ... or Domain Change.
            this.listenTo( nfRadio.channel( 'app' ), 'change:currentDomain', function(){
                // @todo Using a timeout feels like a hack, but there may be a timing issue here.
            	setTimeout(function(){
                    nfRadio.channel( 'app' ).request( 'update:gutters' );
				}, 300, this );
			}, this );


			/*
			 * Reply to messages requesting that we resize our gutters.
			 */
			nfRadio.channel( 'app' ).reply( 'update:gutters', this.updateGutters, this );
		},

		onShow: function() {
			nfRadio.channel( 'main' ).trigger( 'show:main', this );
		},

		onRender: function() {
			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
			var bodyView = currentDomain.get( 'getMainContentView' ).call( currentDomain );
			this.body.show( bodyView );

			var gutterLeftView = currentDomain.get( 'getGutterLeftView' ).call( currentDomain );
			this.gutterLeft.show( gutterLeftView );

			var gutterRightView = currentDomain.get( 'getGutterRightView' ).call( currentDomain );
			this.gutterRight.show( gutterRightView );
			
			nfRadio.channel( 'main' ).trigger( 'render:main' );
		},

		getMainEl: function() {
			return jQuery( this.el ).parent();
		},

		onAttach: function() {
			this.initialGutterResize();
		},

		onBeforeDestroy: function() {
			jQuery( window ).off( 'resize', this.resize );
		},

		initialGutterResize: function() {
			this.resizeGutter( this.gutterLeft.el );
			this.resizeGutter( this.gutterRight.el );
			this.setBothGuttersFixed( this );
		},

		resizeBothGutters: function( e ) {
			var context = ( e ) ? e.data.context : this;

			var leftEl = context.gutterLeft.el;
			var rightEl = context.gutterRight.el;
			
			context.resizeGutter( leftEl, context );
			context.resizeGutter( rightEl, context );

			context.setBothGuttersAbsolute( context );

			/*
			 * Clear our timeout. If the timeout runs, it means we've stopped resizing.
			 */	
			clearTimeout( context.maybeDone );
			/*
			 * Add our timeout.
			 */
			context.maybeDone = setTimeout( context.setBothGuttersFixed, 100, context );
		},

		resizeGutter: function( el, context ) {
			var top = jQuery( el ).offset().top;
			var viewHeight = jQuery( window ).height();
			var height = viewHeight - top;
			jQuery( el ).height( height );
		},

		setBothGuttersFixed: function( context ) {
			context = context || this;

			var offsetLeft = jQuery( context.gutterLeft.el ).offset();
			var topLeft = offsetLeft.top;
			var leftLeft = offsetLeft.left;

			jQuery( context.gutterLeft.el ).css( { position: 'fixed', left: leftLeft, top: topLeft } );			var offsetLeft = jQuery( context.gutterLeft.el ).offset();
			
			var offsetRight = jQuery( context.gutterRight.el ).offset();
			var topRight = offsetRight.top;
			var leftRight = offsetRight.left;

			jQuery( context.gutterRight.el ).css( { position: 'fixed', left: leftRight, top: topRight } );
		},

		setBothGuttersAbsolute: function( context ) {
			context = context || this;

			var offsetLeft = jQuery( context.gutterLeft.el ).offset();
			var offsetRight = jQuery( context.gutterRight.el ).offset();

			var scrollTop = jQuery( '#nf-main' ).scrollTop();

			jQuery( context.gutterLeft.el ).css( { position: 'absolute', left: 0, top: scrollTop } );
			jQuery( context.gutterRight.el ).css( { position: 'absolute', top: scrollTop, right: 0, left: 'auto' } );
		},

		updateGutters: function() {
			this.resizeBothGutters();
		}

	});

	return view;
} );

/**
 * Renders an application menu item from a domain model.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/mobileMenuItem',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-mobile-menu-item',

		/**
		 * When we render this view, remove the extra <div> tag created by backbone.
		 * 
		 * @since  3.0
		 * @return void
		 */
		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );
		},

		// Listen for clicks on our app menu.
		events: {
			'click a': 'clickAppMenu'
		},

		/**
		 * When we click on a menu item, fire a radio event.
		 * This lets us separate the logic from the click event and view.
		 * We pass this.model so that we know what item was clicked.
		 * 
		 * @since  3.0
		 * @param  Object	e event
		 * @return return
		 */
		clickAppMenu: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:menu', e, this.model );
		},

		/**
		 * These functions are available to templates, and help us to remove logic from template files.
		 * 
		 * @since  3.0
		 * @return Object
		 */
		templateHelpers: function() {
			return {
				/**
				 * If we have any dashicons in our model, render them.
				 * 
				 * @since  3.0
				 * @return string
				 */
				renderDashicons: function() {
					if ( ! this.mobileDashicon ) return '';

                    var icon = document.createElement( 'span' );
                    icon.classList.add( 'dashicons' );
                    icon.classList.add( this.mobileDashicon );

                    return icon.outerHTML;
				},
				/**
				 * Render classes for our menu item, including active.
				 * 
				 * @since  3.0
				 * @return string
				 */
				renderClasses: function() {
					var classes = this.classes;
					var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
					if ( currentDomain.get( 'id' ) == this.id ) {
						classes += ' active';
					}
					return classes;
				},
				/**
				 * If our menu is a link (like preview), render its url.
				 * 
				 * @since  3.0
				 * @return string
				 */
				renderUrl: function() {
					if ( '' != this.url ) {
						var formModel = nfRadio.channel( 'app' ).request( 'get:formModel' );
						return this.url + formModel.get( 'id' );
					} else {
						return '#';
					}
				},
				/**
				 * If our menu is a link (like preview), render its target.
				 * 
				 * @since  3.0
				 * @return string
				 */
				renderTarget: function() {
					if ( '' != this.url ) {
						return '_blank';
					} else {
						return '_self';
					}
				},

				/**
				 * If our menu item is disabled, output 'disabled'
				 * 
				 * @since  3.0
				 * @return string
				 */
				renderDisabled: function() {
					if ( this.disabled ) {
						return 'disabled';
					} else {
						return '';
					}
				}
			}
		}
	});

	return view;
} );

/**
 * Single item view used for the menu drawer.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/mobileMenu',['views/app/mobileMenuItem'], function( mobileMenuItemView ) {
	var view = Marionette.CompositeView.extend({
		tagName: 'div',
		template: '#tmpl-nf-mobile-menu',
		childView: mobileMenuItemView,

		initialize: function() {
			// Listen to changes on the app 'clean' state. When it changes, re-render.
			this.listenTo( nfRadio.channel( 'app' ), 'change:clean', this.render, this );
		},

		attachHtml: function( collectionView, childView ) {
			jQuery( collectionView.el ).find( '.secondary' ).append( childView.el );
		},

		templateHelpers: function() {
			return {
				/**
	    		 * If our app state is clean, disable button.
	    		 * 
	    		 * @since  3.0
	    		 * @return string
	    		 */
	    		maybeDisabled: function() {
	    			if ( nfRadio.channel( 'app' ).request( 'get:setting', 'clean' ) ) {
	    				return 'disabled';
	    			} else {
	    				return '';
	    			}
	    		}
			};
		},

		events: {
			'click .nf-publish': 'clickPublish'
		},

		/**
		 * When we click publish, trigger a radio event.
		 * This lets us separate the logic from the click event and view.
		 * 
		 * @since  3.0
		 * @param  Object 	e event
		 * @return void
		 */
		clickPublish: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:publish', e );
			var builderEl = nfRadio.channel( 'app' ).request( 'get:builderEl' );
			jQuery( builderEl ).toggleClass( 'nf-menu-expand' );
		},
	});

	return view;
} );
/**
 * Empty drawer content view.
 * Called before we close the drawer.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/contentEmpty',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-empty'
	});

	return view;
} );
/**
 * Renders our drawer region
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer',['views/app/drawer/contentEmpty'], function( drawerEmptyView ) {

	var view = Marionette.LayoutView.extend( {
		template: '#tmpl-nf-drawer',

		regions: {
			header: '#nf-drawer-header',
			content: '#nf-drawer-content',
			footer: '#nf-drawer-footer'
		},

		initialize: function() {
			nfRadio.channel( 'app' ).reply( 'get:drawerEl', this.getEl, this );
			nfRadio.channel( 'drawer' ).reply( 'load:drawerContent', this.loadContent, this );
			nfRadio.channel( 'drawer' ).reply( 'empty:drawerContent', this.emptyContent, this );
		},

		onShow: function() {
			jQuery( this.el ).parent().perfectScrollbar();
		},

		loadContent: function( drawerID, data ) {
			var drawer = nfRadio.channel( 'app' ).request( 'get:drawer', drawerID );
			var contentView = drawer.get( 'getContentView' ).call( drawer, data );
			var headerView = drawer.get( 'getHeaderView' ).call( drawer, data );
			var footerView = drawer.get( 'getFooterView' ).call( drawer, data );

			this.header.show( headerView );
			this.content.show( contentView );
			this.footer.show( footerView );

		},

		emptyContent: function() {
			this.header.empty();
			this.content.empty();
			this.footer.empty();
		},

		getEl: function() {
			return jQuery( this.el ).parent();
		},

		events: {
			'click .nf-toggle-drawer': 'clickToggleDrawer'
		},

		clickToggleDrawer: function() {
			nfRadio.channel( 'app' ).trigger( 'click:toggleDrawerSize' );
		}

	} );

	return view;
} );

/**
 * Single item view used for merge tags.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/mergeTagItem',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'li',

		template: '#tmpl-nf-merge-tags-item',

		onBeforeDestroy: function() {
			this.model.off( 'change:active', this.render );
		},

		initialize: function() {
			this.model.on( 'change:active', this.render, this );
		},

		events: {
			'click a': 'clickTag'
		},

		clickTag: function( e ) {
			nfRadio.channel( 'mergeTags' ).trigger( 'click:mergeTag', e, this.model );
		},

		templateHelpers: function() {
			return {
				renderClasses: function() {
					if ( this.active ) {
						return 'active';
					}
				}				
			}
		}
	});

	return view;
} );
/**
 * Merge tags popup section
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/mergeTagsSection',['views/app/drawer/mergeTagItem'], function( mergeTagItemView ) {
	var view = Marionette.CompositeView.extend({
		tagName: 'div',
		childView: mergeTagItemView,
		template: '#tmpl-nf-merge-tags-section',

		initialize: function() {
			this.collection = this.model.get( 'tags' );
			this.model.on( 'change', this.render, this );
			if ( 'fields' == this.model.get( 'id' ) ) {
				// var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
				// fieldCollection.on( 'all', this.updateFields, this );
			}
		},

		onBeforeDestroy: function() {
			this.model.off( 'change', this.render );
			if ( 'fields' == this.model.get( 'id' ) ) {
				var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
				fieldCollection.off( 'all', this.updateFields, this );
			}
		},

		attachHtml: function( collectionView, childView ) {
			jQuery( collectionView.el ).find( '.merge-tags' ).append( childView.el );
		},

		updateFields: function() {
			var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
			this.model.set( 'tags', fieldCollection );
		}
	});

	return view;
} );
/**
 * Model that represents our merge tags.
 * 
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/mergeTagModel',[], function() {
	var model = Backbone.Model.extend( {
		defaults: {
			active: false,
			exclude: false
		}
	} );
	
	return model;
} );
/**
 * Collections of merge tags.
 * 
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/mergeTagCollection',['models/app/mergeTagModel'], function( mergeTagModel ) {
	var collection = Backbone.Collection.extend( {
		model: mergeTagModel
	} );
	return collection;
} );
/**
 * Merge tags popup
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/mergeTagsContent',['views/app/drawer/mergeTagsSection', 'models/app/mergeTagCollection'], function( mergeTagsSectionView, MergeTagCollection ) {
	var view = Marionette.CollectionView.extend({
		tagName: 'div',
		template: '#tmpl-nf-merge-tags-content',
		childView: mergeTagsSectionView,

		initialize: function() {
			nfRadio.channel( 'mergeTags' ).reply( 'get:view', this.getMergeTagsView, this );
		},

		reRender: function( settingModel ) {
			var mergeTagCollection = nfRadio.channel( 'mergeTags' ).request( 'get:collection' );
			var defaultGroups = mergeTagCollection.where( { default_group: true } );

			/*
			 * For the Actions Domain, Add Calc Merge Tags as a Default Group.
			 */
			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
			if( 'actions' == currentDomain.get( 'id' ) ){
				var calcMergeTagGroup = mergeTagCollection.where( { id: 'calcs' } );
                defaultGroups = defaultGroups.concat( calcMergeTagGroup );
            }

			this.collection = new MergeTagCollection( defaultGroups );
			var that = this;
			var useMergeTags = settingModel.get( 'use_merge_tags' );
			if ( 'object' == typeof useMergeTags ) {
				if ( 'undefined' != typeof useMergeTags.exclude ) {
					_.each( useMergeTags.exclude, function( exclude ) {
						that.collection.remove( exclude )
					} );
				}

				if ( 'undefined' != typeof useMergeTags.include ) {
					_.each( mergeTagCollection.models, function( sectionModel ) {
						if ( -1 != useMergeTags.include.indexOf( sectionModel.get( 'id' ) ) ) {
							// console.log( sectionModel );
							that.collection.add( sectionModel );
						}
					} );
				}
			}

			this.render();
		},

		getMergeTagsView: function() {
			return this;
		}
	});

	return view;
} );
/**
 * Builder view.
 *
 * This layout view has regions that represent our application areas:
 * header
 * main
 * menuDrawer - Mobile side-menu
 * drawer
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/builder',['views/app/builderHeader', 'views/app/main', 'views/app/mobileMenu', 'views/app/drawer', 'views/app/drawer/mergeTagsContent'], function( headerView, mainView, mobileMenuView, drawerView, mergeTagsContentView ) {
	var view = Marionette.LayoutView.extend( {
		template: "#tmpl-nf-builder",
		el: '#nf-builder',

		regions: {
			header: "#nf-header",
			main: "#nf-main",
			menuDrawer: "#nf-menu-drawer",
			drawer: "#nf-drawer",
			mergeTagsContent: '.merge-tags-content'
		},

		initialize: function() {
			// Respond to requests asking for the builder dom element.
			nfRadio.channel( 'app' ).reply( 'get:builderEl', this.getBuilderEl, this );
			// Respond to requests asking for the builder view
			nfRadio.channel( 'app' ).reply( 'get:builderView', this.getBuilderView, this );
			// Layout views aren't self-rendering.
			this.render();
			var mergeTags = nfRadio.channel( 'mergeTags' ).request( 'get:collection' );
			var mergeTagsClone = mergeTags.clone();
			this.mergeTagsContent.show( new mergeTagsContentView( { collection: mergeTagsClone } ) );
			// Show our header.
			this.header.show( new headerView() );
			// Show our main content.
			this.main.show( new mainView() );
			// Show our mobile menu
			var appDomainCollection = nfRadio.channel( 'app' ).request( 'get:domainCollection' );
			this.menuDrawer.show( new mobileMenuView( { collection: appDomainCollection } ) );
			// Show our drawer.
			this.drawer.show( new drawerView() );


		},

		onRender: function() {

		},

		getBuilderEl: function() {
			return this.el;
		},

		getBuilderView: function() {
			return this;
		},

		// Listen for clicks
		events: {
			'click .nf-open-drawer': 'openDrawer',
			'click .nf-change-domain': 'changeDomain',
			'click .nf-close-drawer': 'closeDrawer'
		},

		/**
		 * Someone clicked to open a drawer, so fire a radio event.
		 * This lets us separate the logic from the click event and view.
		 *
		 * @since  3.0
		 * @param  Object 	e 	event
		 * @return void
		 */
		openDrawer: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:openDrawer', e );
		},
		/**
		 * Someone clicked to close a drawer, so fire a radio event.
		 * This lets us separate the logic from the click event and view.
		 *
		 * @since  3.0
		 * @return void
		 */
		closeDrawer: function() {
			nfRadio.channel( 'app' ).trigger( 'click:closeDrawer' );
		},
		/**
		 * Someone clicked to change the domain, so fire a radio event.
		 * This lets us separate the logic from the click event and view.
		 *
		 * @since  3.0
		 * @param  Object 	e 	event
		 * @return void
		 */
		changeDomain: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:menu', e );
		}

	} );

	return view;
} );


define( 'controllers/app/remote',[], function() {
    return Marionette.Object.extend( {
        initialize: function() {
            this.listenTo( nfRadio.channel( 'setting' ), 'remote', this.addListener );
        },

        addListener: function( model, dataModel ) {

            var listenTo = model.get( 'remote' ).listen;

            // TODO: Change seems to be triggering twice on each update.
            this.listenTo( nfRadio.channel( 'fieldSetting-' + listenTo ), 'update:setting', this.updateSetting );
            this.listenTo( nfRadio.channel( 'actionSetting-' + listenTo ), 'update:setting', this.updateSetting );

            this.listenTo( nfRadio.channel( 'setting-type-' + model.get( 'type' ) ), 'click:extra', this.clickExtra );

            model.listenTo( nfRadio.channel( 'setting-remote' ), 'get:remote', this.getRemote, model );

            // Auto-trigger get:remote on drawer load.
            nfRadio.channel( 'setting-remote' ).trigger( 'get:remote', dataModel );
        },

        clickExtra: function( e, settingModel, dataModel, settingView ) {
            jQuery( e.srcElement ).addClass( 'spin' );
            nfRadio.channel( 'setting-remote' ).trigger( 'get:remote', dataModel );
        },

        updateSetting: function( dataModel, settingModel ) {
            nfRadio.channel( 'setting-remote' ).trigger( 'get:remote', dataModel );
        },

        getRemote: function( dataModel ) {

            var remote = this.get( 'remote' );

            var data = {
                parentValue: dataModel.get( remote.listen ),
                action: remote.action,
                security: ( remote.security ) ? remote.security : nfAdmin.ajaxNonce
            };

            // TODO: Disable setting and lock drawer while updating.
            var that = this;
            jQuery.post( ajaxurl, data, function( response ){
                var response = JSON.parse( response );

                if( 'textbox' == that.get( 'type' ) ) {
                    dataModel.set( that.get('name'), response.value );
                }

                if( 'select' == that.get( 'type' ) ) {
                    that.set( 'options', response.options );
                    that.trigger( 'rerender' );
                }
            });
        },

    });
} );
/**
 * Handles opening and closing our drawer. This is where we display settings for fields, actions, and settings.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/drawer',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen to our drawer-related click events.
			this.listenTo( nfRadio.channel( 'app' ), 'click:openDrawer', this.clickOpenDrawer );
			this.listenTo( nfRadio.channel( 'app' ), 'click:closeDrawer', this.closeDrawer );
			this.listenTo( nfRadio.channel( 'app' ), 'click:toggleDrawerSize', this.toggleDrawerSize );

			// Reply to direct requests to open or close the drawer.
			nfRadio.channel( 'app' ).reply( 'open:drawer', this.openDrawer, this );
			nfRadio.channel( 'app' ).reply( 'close:drawer', this.closeDrawer, this );

			/*
			 * When we close the drawer, we have to figure out what the right position should be.
			 * This listens to requests from other parts of our app asking what the closed right position is.
			 */
			nfRadio.channel( 'drawer' ).reply( 'get:closedRightPos', this.getClosedDrawerPos, this );
			
			// Reply to requests to prevent our drawer from closing
			nfRadio.channel( 'drawer' ).reply( 'prevent:close', this.preventClose, this );
			// Reply to requests to enable drawer closing
			nfRadio.channel( 'drawer' ).reply( 'enable:close', this.enableClose, this );
			// Reply to requests for our disabled/enabled state.
			nfRadio.channel( 'drawer' ).reply( 'get:preventClose', this.maybePreventClose, this );

			/*
			 * Object that holds our array of 'prevent close' values.
			 * We use an array so that registered requests can unregister and not affect each other.
			 */
			this.objPreventClose = {};

			/*
			 *  Listen to focus events on the filter and stop our interval when it happens.
			 *  This is to fix a bug that can cause the filter to gain focus every few seconds.
			 */
			this.listenTo( nfRadio.channel( 'drawer' ), 'filter:focused', this.filterFocused );
		},

		/**
		 * Handles closing our drawer
		 * @since  3.0
		 * @return void
		 */
		closeDrawer: function() {
			// Get our current domain.
			var currentDrawer = nfRadio.channel( 'app' ).request( 'get:currentDrawer' );
            if ( ! currentDrawer || this.maybePreventClose() ) {
                return false;
            }

			// Triggers the before close drawer action on our current domain's drawer channel.
			nfRadio.channel( 'drawer-' + currentDrawer.get( 'id' ) ).trigger( 'before:closeDrawer' );
			/*
			 * The 'before:closeDrawer' message is deprecated as of version 3.0 in favour of 'before:close'.
			 * TODO: Remove this radio message in the future.
			 */
			nfRadio.channel( 'drawer' ).trigger( 'before:closeDrawer' );
			nfRadio.channel( 'drawer' ).trigger( 'before:close' );
			// Send a message to our drawer to empty its contents.
			nfRadio.channel( 'drawer' ).request( 'empty:drawerContent' );

			// To close our drawer, we have to add our closed class to the builder and remove the opened class.
			var builderEl = nfRadio.channel( 'app' ).request( 'get:builderEl' );
			jQuery( builderEl ).addClass( 'nf-drawer-closed' ).removeClass( 'nf-drawer-opened' );
			jQuery( builderEl ).removeClass( 'disable-main' );

			// Get the right position of our closed drawer. Should be container size in -px.
			var rightClosed = this.getClosedDrawerPos();

			// Get our drawer element and give change the 'right' property to our closed position.
			var drawerEl = nfRadio.channel( 'app' ).request( 'get:drawerEl' );
			jQuery( drawerEl ).css( { 'right': rightClosed } );

			// In order to access properties in 'this' context in our interval below, we have to set it here.	
			var that = this;

			/*
			 * Since jQuery can't bind to a CSS change, we poll every .15 seconds to see if we've closed the drawer.
			 *
			 * Once our drawer is closed, we:
			 * clear our interval
			 * request that the app change it's current drawer to false
			 * trigger a drawer closed message
			 */
			this.checkCloseDrawerPos = setInterval( function() {
	        	if ( rightClosed == jQuery( drawerEl ).css( 'right' ) ) {
	        		clearInterval( that.checkCloseDrawerPos );
		    		nfRadio.channel( 'app' ).request( 'update:currentDrawer', false );
		    		nfRadio.channel( 'drawer' ).trigger( 'closed' );
		    		/*
		    		 * Reset the add new button z-index to 98.
		    		 */
		    		jQuery( '.nf-master-control' ).css( 'z-index', 98 );
		    		// jQuery( drawerEl ).scrollTop( 0 );
	        	}
			}, 150 );
		},

		/**
		 * Click handler for our 'open drawer' event.
		 * @since  3.0
		 * @param  e jQuery event
		 * @return void
		 */
		clickOpenDrawer: function( e ) {
			var drawerID = jQuery( e.target ).data( 'drawerid' );
			this.openDrawer( drawerID );
		},

		/**
		 * Open our drawer.
		 * 
		 * @since  3.0
		 * @param  string drawerID 	ID of the drawer we want to open.
		 * @param  object data     	Optional data that we want to pass to the drawer.
		 * @return void
		 */
		openDrawer: function( drawerID, data ) {
			if ( this.maybePreventClose() ) {
				return false;
			}

			// If we haven't sent a data object, set the variable to an empty object.
			data = data || {};

			/*
			 * If we're dealing with something that has a model, set the proper active state.
			 *
			 * TODO: Make this more dynamic. I'm not sure that it fits in the drawer controller.
			 */
			if ( 'undefined' != typeof data.model ) {
				var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
				var currentDomainID = currentDomain.get( 'id' );
				nfRadio.channel( currentDomainID ).request( 'clear:editActive' );
				data.model.set( 'editActive', true );
				this.dataModel = data.model;
			}

			// Send out a message requesting our drawer view to load the content for our drawer ID.
			nfRadio.channel( 'drawer' ).request( 'load:drawerContent', drawerID, data );
			nfRadio.channel( 'drawer' ).trigger( 'before:open' );
			
			// To open our drawer, we have to add our opened class to our builder element and remove the closed class.
			var builderEl = nfRadio.channel( 'app' ).request( 'get:builderEl' );
			jQuery( builderEl ).addClass( 'nf-drawer-opened' ).removeClass( 'nf-drawer-closed' );
			
			// To open our drawer, we have to set the right position of our drawer to 0px.
			var drawerEl = nfRadio.channel( 'app' ).request( 'get:drawerEl' );
			jQuery( drawerEl ).css( { 'right': '0px' } );
			
			// In order to access properties in 'this' context in our interval below, we have to set it here.	
			var that = this;

			/*
			 * Since jQuery can't bind to a CSS change, we poll every .15 seconds to see if we've opened the drawer.
			 *
			 * Once our drawer is opened, we:
			 * clear our interval
			 * focus our filter
			 * request that the app update its current drawer to the one we opened
			 * trigger a drawer opened message
			 */
			this.hasFocus = false;

			/*
			 * Set our add new button z-index to 0;
			 */
			jQuery( '.nf-master-control' ).css( 'z-index', 0 );

			this.checkOpenDrawerPos = setInterval( function() {
	        	if ( '0px' == jQuery( drawerEl ).css( 'right' ) ) {
	        		clearInterval( that.checkOpenDrawerPos );
					if ( ! that.hasFocus ) {
		        		that.focusFilter();
						that.hasFocus = true;
			    		nfRadio.channel( 'app' ).request( 'update:currentDrawer', drawerID );
			    		jQuery( drawerEl ).scrollTop( 0 );
			    		nfRadio.channel( 'drawer' ).trigger( 'opened' );
					}   		
	        	}
			}, 150 );
		},

		/**
		 * Toggle the drawer from half to full screen and vise-versa
		 * @since  3.0
		 * @return void
		 */
		toggleDrawerSize: function() {
			// Get our drawer element.
			var drawerEl = nfRadio.channel( 'app' ).request( 'get:drawerEl' );
			// toggle our drawer size class.
			jQuery( drawerEl ).toggleClass( 'nf-drawer-expand' );
		},

		/**
		 * Focus our filter
		 * @since  3.0
		 * @return void
		 */
        focusFilter: function() {
        	// Get our filter element
        	var filterEl = nfRadio.channel( 'drawer' ).request( 'get:filterEl' );
        	// Focus
        	jQuery( filterEl ).focus();
        },

        /**
         * Get the CSS right position (in px) of the closed drawer element.
         * This is calculated by:
         * getting the width of the builder element
         * add 300 pixels
         * make it negative
         * 
         * @since  3.0
         * @return void
         */
        getClosedDrawerPos: function() {
			var builderEl = nfRadio.channel( 'app' ).request( 'get:builderEl' );
			var closedPos = jQuery( builderEl ).width() + 300;
			return '-' + closedPos + 'px';
        },

        /**
         * Check to see if anything has registered a prevent close key.
         * 
         * @since  3.0
         * @return boolean
         */
        maybePreventClose: function() {
        	if ( 0 == Object.keys( this.objPreventClose ).length ) {
        		return false;
        	} else {
        		return true;
        	}
        },

        /**
         * Register a prevent close key.
         * 
         * @since  3.0
         * @param  string 	key unique id for our 'prevent close' setting.
         * @return void
         */
        preventClose: function( key ) {
        	this.objPreventClose[ key ] = true;
        	/*
        	 * When we disable closing the drawer, add the disable class.
        	 */
        	// Get our current drawer.
			this.dataModel.set( 'drawerDisabled', true );
        },

        /**
         * Remove a previously registered prevent close key.
         * 
         * @since  3.0
         * @param  string 	key unique id for our 'prevent close' setting.
         * @return void
         */
        enableClose: function( key ) {
        	delete this.objPreventClose[ key ];
        	 /*
        	 * When we remove all of our disables preventing closing the drawer, remove the disable class.
        	 */
        	if ( ! this.maybePreventClose() && 'undefined' != typeof this.dataModel ) {
	        	// Get our current drawer.
				this.dataModel.set( 'drawerDisabled', false );        		
        	}
        },

        /**
         * When we focus our filter, make sure that our open drawer interval is cleared.
         * 
         * @since  3.0
         * @return void
         */
        filterFocused: function() {
        	clearInterval( this.checkOpenDrawerPos );
        },

        getPreventClose: function() {
        	return this.objPreventClose;
        }
	});

	return controller;
} );
/**
 * Default drawer header.
 *
 * Includes our filter/search and 'Done' button.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/headerDefault',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-header-default',

		initialize: function() {
			if ( this.model ) {
				// Listen for our drawer being disabled.
				this.model.on( 'change:drawerDisabled', this.render, this );				
			}
		},

		/**
		 * When we render, remove the extra div added by backbone and add listeners related to our filter.
		 * 
		 * @since  3.0
		 * @return void
		 */
		onRender: function() {
			// Remove extra wrapping div.
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );
			// Respond to requests related to our filter.
			nfRadio.channel( 'drawer' ).reply( 'clear:filter', this.clearFilter, this );
			nfRadio.channel( 'drawer' ).reply( 'blur:filter', this.blurFilter, this );
			nfRadio.channel( 'drawer' ).reply( 'get:filterEl', this.getEl, this );
		},

		onBeforeDestroy: function() {
			if ( this.model ) {
				this.model.off( 'change:drawerDisabled', this.render );
			}
		},

		events: {
			'keyup .nf-filter'	: 'maybeChangeFilter',
			'input .nf-filter'	: 'changeFilter',
			'focus input'		: 'getFocus'
		},

		/**
		 * When the filter text is changed, trigger an event on our current drawer.
		 * This lets us keep the logic separate from the click event and view.
		 * 
		 * @since  3.0
		 * @param  Object 	e event
		 * @return void
		 */
		changeFilter: function( e ) {
			var currentDrawer = nfRadio.channel( 'app' ).request( 'get:currentDrawer' );
			nfRadio.channel( 'drawer-' + currentDrawer.get( 'id' ) ).trigger( 'change:filter', e.target.value, e );
		},

		/**
		 * The user pressed a key. If it's the enter key, then run the change filter function.
		 * 
		 * @since  3.0
		 * @param  Object 	e event
		 * @return void
		 */
		maybeChangeFilter: function( e ) {
			if ( 13 == e.keyCode ) {
				e.addObject = true;
				this.changeFilter( e );			
			}
		},

		/**
		 * Clear our filter.
		 *
		 * This triggers 'input' on the field, which will trigger a change if necessary.
		 * 
		 * @since  3.0
		 * @return void
		 */
		clearFilter: function() {
			var filterEl =  jQuery( this.el ).find( '.nf-filter' );
			if ( '' != jQuery.trim( filterEl.val() ) ) {
				filterEl.val('');
				filterEl.trigger( 'input' );
				filterEl.focus();			
			}
		},

		/**
		 * Fire the 'blur' event on our filter. Used to force a change event when the user tabs.
		 * 
		 * @since  3.0
		 * @return void
		 */
		blurFilter: function() {
			jQuery( this.el ).find( '.nf-filter' ).blur();
		},

		/**
		 * Return our filter dom element.
		 * 
		 * @since  3.0
		 * @return Object
		 */
		getEl: function() {
			return jQuery( this.el ).find( '.nf-filter' );
		},

		getFocus: function() {
			nfRadio.channel( 'drawer' ).trigger( 'filter:focused' );
		},

		templateHelpers: function() {
			return {
				renderDisabled: function() {
					// Get our current domain.
					if ( this.drawerDisabled ) {
						return 'disabled';
					} else {
						return '';
					}
				}
			}
		}
	});

	return view;
} );
/**
 * Default drawer footer
 * 
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/footerDefault',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-empty'
	});

	return view;
} );
define( 'models/app/drawerModel',['views/app/drawer/headerDefault', 'views/app/drawer/footerDefault'], function( defaultHeaderView, defaultFooterView ) {
	var model = Backbone.Model.extend( {
		defaults: {
			getHeaderView: function( data ) {
				return new defaultHeaderView( data );
			},

			getFooterView: function( data ) {
				return new defaultFooterView( data );
			}
		}
	} );
	
	return model;
} );
/**
 * Collection that holds all of our drawer models.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/drawerCollection',['models/app/drawerModel'], function( drawerModel ) {
	var collection = Backbone.Collection.extend( {
		model: drawerModel
	} );
	return collection;
} );
define( 'views/fields/drawer/stagedField',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-staged-field',

		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );
		},

		events: {
			'click .dashicons-dismiss': 'removeStagedField'
		},

		removeStagedField: function( el ) {
			nfRadio.channel( 'drawer-addField' ).trigger( 'click:removeStagedField', el, this.model );
		}
	});

	return view;
} );

define( 'views/fields/drawer/stagingEmpty',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-staged-fields-empty',

		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );
		}
	});

	return view;
} );
define( 'views/fields/drawer/stagingCollection',['views/fields/drawer/stagedField', 'views/fields/drawer/stagingEmpty'], function( stagedFieldView, stagedFieldsEmptyView ) {
	var view = Marionette.CollectionView.extend( {
		tagName: 'div',
		childView: stagedFieldView,
		emptyView: stagedFieldsEmptyView,

		activeClass: 'nf-staged-fields-active', // CSS Class for showing the reservoir.

		initialize: function() {
			nfRadio.channel( 'app' ).reply( 'get:stagedFieldsEl', this.getStagedFieldsEl, this );
		},

		onShow: function() {

			this.$el = jQuery( this.el ).parent();
			jQuery( this.$el ).find( 'span:first' ).unwrap();
			this.setElement( this.$el );

			var that = this;

			jQuery( this.el ).sortable( {
				placeholder: 'nf-staged-fields-sortable-placeholder',
				helper: 'clone',
				tolerance: 'pointer',
				over: function( e, ui ) {
					nfRadio.channel( 'drawer-addField' ).trigger( 'over:stagedFields', e, ui );
				},

				out: function( e, ui ) {
					nfRadio.channel( 'drawer-addField' ).trigger( 'out:stagedFields', ui );
				},

				receive: function( e, ui ) {
					nfRadio.channel( 'drawer-addField' ).trigger( 'receive:stagedFields', ui );
				},

				update: function( e, ui ) {
					nfRadio.channel( 'fields' ).request( 'sort:staging' );
				},

				start: function( e, ui ) {
					nfRadio.channel( 'drawer-addField' ).trigger( 'start:stagedFields', ui );

				},

				stop: function( e, ui ) {
					nfRadio.channel( 'drawer-addField' ).trigger( 'stop:stagedFields', ui );
				}
			} );

			jQuery( this.el ).parent().draggable( {
				opacity: 0.9,
				connectToSortable: '.nf-field-type-droppable',
				appendTo: '#nf-main',
				refreshPositions: true,
				grid: [ 3, 3 ],
				tolerance: 'pointer',

				helper: function( e ) {
					var width = jQuery( e.target ).parent().width();
					var height = jQuery( e.target ).parent().height();
					var element = jQuery( e.target ).parent().clone();
					var left = width / 4;
					var top = height / 2;
					jQuery( this ).draggable( 'option', 'cursorAt', { top: top, left: left } );
					jQuery( element ).css( 'z-index', 1000 );
					return element;
				},

				start: function( e, ui ) {
					nfRadio.channel( 'drawer-addField' ).trigger( 'startDrag:fieldStaging', this, ui );
				},
				stop: function( e, ui ) {
					nfRadio.channel( 'drawer-addField' ).trigger( 'stopDrag:fieldStaging', this, ui );
				}
			} );
		},

		getStagedFieldsEl: function() {
			return jQuery( this.el );
		},

		onAddChild: function() {
			jQuery( this.el ).addClass( this.activeClass );
		},

		onRemoveChild: function() {
			if( this.hasStagedFields() ) return;
			jQuery( this.el ).removeClass( this.activeClass );
		},

		hasStagedFields: function() {
			return  0 != this.collection.length;
		}

	} );

	return view;
} );
/**
 * Model for our staged field.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/fields/stagingModel',[], function() {
	var model = Backbone.Model.extend( {
	} );
	
	return model;
} );
/**
 * Collection of staged fields.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/fields/stagingCollection',['models/fields/stagingModel'], function( stagingModel ) {
	var collection = Backbone.Collection.extend( {
		model: stagingModel,
		comparator: 'order'
	} );
	return collection;
} );
define( 'views/fields/drawer/typeSection',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-field-type-section',

		initialize: function() {
			_.bindAll( this, 'render' );
			nfRadio.channel( 'fields' ).reply( 'get:typeSection', this.getTypeSection, this );
		},

		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );

			this.dragging = false;
			var that = this;
			/*
			 * If we're on a mobile device, we don't want to enable dragging for our field type buttons.
			 */
			if ( ! nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
				jQuery( this.el ).find( 'div.nf-field-type-draggable' ).draggable( {
					opacity: 0.9,
					tolerance: 'pointer',
					connectToSortable: '.nf-field-type-droppable',
					refreshPositions: true,
					grid: [ 5, 5 ],
					appendTo: '#nf-builder',

					helper: function( e ) {
						var width = jQuery( e.target ).parent().width();
						var height = jQuery( e.target ).parent().height();
						var element = jQuery( e.target ).parent().clone();
						var left = width / 4;
						var top = height / 2;
						jQuery( this ).draggable( 'option', 'cursorAt', { top: top, left: left } );
						jQuery( element ).css( 'z-index', 1000 );
						return element;
					},

					start: function( e, ui ) {
						that.dragging = true;
						nfRadio.channel( 'drawer-addField' ).trigger( 'startDrag:type', this, ui );
					},

					stop: function( e, ui ) {
						that.dragging = false;
						nfRadio.channel( 'drawer-addField' ).trigger( 'stopDrag:type', this, ui );
					},

					drag: function(e, ui) {
						nfRadio.channel( 'drawer-addField' ).trigger( 'drag:type', this, ui, e );	
					}

				} ).disableSelection();

				jQuery( this.el ).find( '.nf-item' ).focus( function() {
			    	jQuery( this ).addClass( 'active' );
			    } ).blur( function() {
			    	jQuery( this ).removeClass( 'active' );
			    } );
			}
		},

		events: {
			'click .nf-item': 'clickFieldType',
			'keydown .nf-item': 'maybeClickFieldType',
			'mousedown .nf-item': 'mousedownFieldType'
		},

		clickFieldType: function( e ) {
			if ( ! this.dragging ) {
				nfRadio.channel( 'drawer' ).trigger( 'click:fieldType', e );
			}
		},

		mousedownFieldType: function( e ) {
			jQuery( e.target).addClass( 'clicked' );
			setTimeout( function() {
				jQuery( e.target ).removeClass( 'clicked' );
			}, 1500 );
		},

		maybeClickFieldType: function( e ) {
			if ( 13 == e.keyCode ) {
				this.clickFieldType( e );
				nfRadio.channel( 'drawer' ).request( 'clear:filter' );
			}
		},

		templateHelpers: function() {
			return {
				renderFieldTypes: function() {
			        var html = document.createElement( 'span' );
			        var that = this;
			        _.each( this.fieldTypes, function( id ) {
			            var type = nfRadio.channel( 'fields' ).request( 'get:type', id );
			            var nicename = type.get( 'nicename' );
			            var icon = type.get( 'icon' );
			            var renderType = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-drawer-field-type-button' );
			            var templateHTML = renderType( { id: id, nicename: nicename, icon: icon, type: type, savedField: that.savedField } );
                        var htmlFragments = document.createRange().createContextualFragment( templateHTML );
                        html.appendChild( htmlFragments );
			        } );
			        return html.innerHTML;
				},

				savedField: function() {
					if( this.type.get( 'savedField' ) ) {
						return 'nf-saved';
					} else {
						return '';
					}
				}
			}
		},

		getTypeSection: function() {
			return this.el;
		}
	});

	return view;
} );

define( 'views/fields/drawer/typeSectionCollection',['views/fields/drawer/typeSection'], function( fieldTypeSectionView ) {
	var view = Marionette.CollectionView.extend( {
		tagName: 'div',
		childView: fieldTypeSectionView,

		onShow: function() {
			jQuery( this.el ).find( '.nf-settings' ).unwrap();
			nfRadio.channel( 'fields' ).request( 'clear:editActive' );
		}
	} );

	return view;
} );
define( 'views/fields/drawer/addField',['views/fields/drawer/stagingCollection', 'models/fields/stagingCollection', 'views/fields/drawer/typeSectionCollection'], function( drawerStagingView, StagingCollection, fieldTypeSectionCollectionView ) {

	var view = Marionette.LayoutView.extend( {
		template: '#tmpl-nf-drawer-content-add-field',

		regions: {
			staging: '#nf-drawer-staging .nf-reservoir',
			primary: '#nf-drawer-primary',
			secondary: '#nf-drawer-secondary'
		},

		initialize: function() {
			this.listenTo( nfRadio.channel( 'drawer' ), 'filter:fieldTypes', this.filterFieldTypes );
			this.listenTo( nfRadio.channel( 'drawer' ), 'clear:filter', this.removeFieldTypeFilter );

			this.savedCollection = nfRadio.channel( 'fields' ).request( 'get:savedFields' );
			this.primaryCollection = this.savedCollection;

			this.fieldTypeSectionCollection = nfRadio.channel( 'fields' ).request( 'get:typeSections' );
			this.secondaryCollection = this.fieldTypeSectionCollection;

		},

		onShow: function() {
			var stagingCollection = nfRadio.channel( 'fields' ).request( 'get:staging' );
			this.staging.show( new drawerStagingView( { collection: stagingCollection } ) );

			this.primary.show( new fieldTypeSectionCollectionView( { collection: this.primaryCollection } ) );
			this.secondary.show( new fieldTypeSectionCollectionView( { collection: this.secondaryCollection } ) );
		},

		getEl: function() {
			return jQuery( this.el ).parent();
		},

		filterFieldTypes: function( filteredSectionCollection ) {
			this.primary.reset();
			this.secondary.reset();
			this.filteredSectionCollection = filteredSectionCollection;
			this.primary.show( new fieldTypeSectionCollectionView( { collection: this.filteredSectionCollection } ) );
		},

		removeFieldTypeFilter: function () {
			this.primary.show( new fieldTypeSectionCollectionView( { collection: this.savedCollection } ) );
			this.secondary.show( new fieldTypeSectionCollectionView( { collection: this.fieldTypeSectionCollection } ) );
		}

	} );

	return view;
} );
define( 'views/app/drawer/itemSettingCollection',[], function() {
	var view = Marionette.CollectionView.extend( {
		tagName: 'div',

		initialize: function( data ) {
			this.childViewOptions = { dataModel: data.dataModel };
		},

		getChildView: function( model ) {
			return nfRadio.channel( 'app' ).request( 'get:settingChildView', model );
		}
	} );

	return view;
} );
define( 'views/app/drawer/itemSettingGroup',['views/app/drawer/itemSettingCollection'], function( itemSettingCollectionView ) {
	var view = Marionette.LayoutView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-content-edit-field-setting-group',
		
		regions: {
			settings: '.nf-field-settings'
		},

		initialize: function( data ) {
			this.model.on( 'change', this.render, this );
			this.dataModel = data.dataModel;
		},

		onBeforeDestroy: function() {
			this.model.off( 'change', this.render );
		},

		onRender: function() {

			this.settings.show( new itemSettingCollectionView( { collection: this.model.get( 'settings' ), dataModel: this.dataModel } ) );

			if(!nfAdmin.devMode) {
				// Only check if not for calculations.
				if(0 == this.$el.find('.calculations').length){
					var visibleSettings = false;
					this.$el.find('.nf-setting').each(function(index, setting) {
						if( 'none' !== setting.style.display ){
							visibleSettings = true;
							return false; //Exit jQuery each loop.
						}
					});
					if(!visibleSettings) {
						this.$el.hide();
					}
				}
			}

			if ( this.model.get( 'display' ) ) {
				// ...
			} else {
				this.settings.empty();
			}

			nfRadio.channel( 'drawer' ).trigger( 'render:settingGroup', this );
		},

		events: {
			'click .toggle': 'clickToggleGroup'
		},

		clickToggleGroup: function( e ) {
			nfRadio.channel( 'drawer' ).trigger( 'click:toggleSettingGroup', e, this.model );
		},

		templateHelpers: function() {
			return {
				renderLabel: function() {
					if ( '' != this.label ) {
						var groupLabel = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-drawer-content-edit-setting-group-label' );
						return groupLabel( this );
					} else {
						return '';
					}
				},

				renderArrowDir: function() {
					if ( this.display ) {
						return 'down';
					} else {
						return 'right';
					}
				}
			}
		}
	});

	return view;
} );
define( 'views/app/drawer/itemSettingGroupCollection',['views/app/drawer/itemSettingGroup'], function( itemSettingGroupView ) {
	var view = Marionette.CollectionView.extend( {
		tagName: 'div',
		childView: itemSettingGroupView,

		initialize: function( data ) {
			this.childViewOptions = { dataModel: data.dataModel };
		}
	} );

	return view;
} );
define( 'views/app/drawer/editSettings',['views/app/drawer/itemSettingGroupCollection'], function( itemSettingGroupCollectionView ) {
	var view = Marionette.LayoutView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-content-edit-settings',

		regions: {
			settingTitle: '.nf-setting-title',
			settingGroups: '.nf-setting-groups'
		},

		initialize: function( data ) {
			this.dataModel = data.model;
			this.groupCollection = data.groupCollection;
		},

		onRender: function() {
			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
			var titleView = currentDomain.get( 'getSettingsTitleView' ).call( currentDomain, { model: this.model } );

			this.settingTitle.show( titleView );
			this.settingGroups.show( new itemSettingGroupCollectionView( { collection: this.groupCollection, dataModel: this.dataModel } ) );
		},

		templateHelpers: function () {
	    	return {
	    		maybeRenderTitle: function() {
	    			if ( 'undefined' !== typeof this.type ) {
	    				var title = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-drawer-content-edit-settings-title' );
	    				return title( this );
	    			} else {
	    				return '';
	    			}
	    		},

	    		renderTypeNicename: function() {
	    			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
					var domainID = currentDomain.get( 'id' );
	    			var type = nfRadio.channel( domainID ).request( 'get:type', this.type );
	    			return type.get( 'nicename' );
				},
			};
		},
	});

	return view;
} );
/**
 * Edit Settings drawer header.
 *
 * Includes our 'Done' button.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/headerEditSettings',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-header-edit-settings',

		initialize: function() {
			if ( this.model ) {
				// Listen for our drawer being disabled.
				this.model.on( 'change:drawerDisabled', this.render, this );				
			}
		},

		onBeforeDestroy: function() {
			if ( this.model ) {
				this.model.off( 'change:drawerDisabled', this.render );
			}
		},

		templateHelpers: function() {
			return {
				renderDisabled: function() {
					// Get our current domain.
					if ( this.drawerDisabled ) {
						return 'disabled';
					} else {
						return '';
					}
				}
			}
		}
	});

	return view;
} );
/**
 * Button to add an action to the form.
 *
 * TODO: make dynamic
 * 
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/actions/drawer/typeButton',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-action-type-button',

		onRender: function() {
			
			jQuery( this.el ).disableSelection();
			
			if ( 'installed' == this.model.get( 'section') ) {
				var that = this;
				jQuery( this.el ).draggable( {
					opacity: 0.9,
					tolerance: 'intersect',
					scroll: false,
					helper: 'clone',

					start: function( e, ui ) {
						that.dragging = true;
						nfRadio.channel( 'drawer-addAction' ).trigger( 'startDrag:type', this, ui );
					},

					stop: function( e, ui ) {
						that.dragging = false;
						nfRadio.channel( 'drawer-addAction' ).trigger( 'stopDrag:type', this, ui );
					}

				} );
			}
			
		},

		events: {
			'click .nf-item': 'clickAddAction'
		},

		clickAddAction: function( e ) {
			if ( ! this.dragging ) {
				if ( 'installed' == this.model.get( 'section' ) ) { // Is this an installed action?
					nfRadio.channel( 'actions' ).trigger( 'click:addAction', this.model );
				} else { // This isn't an installed action
					var modalContent = this.model.get( 'modal_content' );

					var actionModal = new jBox( 'Modal', {
					  content: modalContent,
					  zIndex:99999999,
					  closeButton: 'box',
					  overlay: true,
					  width: 600,
					  repositionOnOpen: true,
					  reposition: true
					});

					actionModal.open();
					// window.open( this.model.get( 'link' ), '_blank' );
				}
			}
		},

		templateHelpers: function() {
			return {
				renderClasses: function() {
					var classes = 'nf-item';
					if ( '' != jQuery.trim( this.image ) ) {
						classes += ' nf-has-img';
					}

					if ( 'installed' == this.section ) {
						classes += ' nf-action-type';
					}
					return classes;
				},

				renderStyle: function() {
					if ( '' != jQuery.trim( this.image ) ) {

						// This is being used in a template, so carefully consider the order of double/single quotes.
						return "background-image: url('" + jQuery.trim( this.image ) + "')";
					} else {
						return '';
					}
				}
			}
		}
	});

	return view;
} );

define( 'views/actions/drawer/typeCollection',['views/actions/drawer/typeButton'], function( actionTypeButtonView ) {
	var view = Marionette.CompositeView.extend( {
		template: '#tmpl-nf-drawer-action-type-section',
		childView: actionTypeButtonView,

		templateHelpers: function() {
			var that = this;
			return {
				hasContents: function() {
					return that.collection.length > 0;
				},

				renderNicename: function() {
					return that.collection.nicename;
				},

				renderClasses: function() {
					return that.collection.slug;
				}
			}
		},

		attachHtml: function( collectionView, childView ) {
			jQuery( collectionView.el ).find( '.action-types' ).append( childView.el );
		}
	} );

	return view;
} );
/**
 * Model that represents our setting.
 *
 * When the model is created, we trigger the init event in two radio channels.
 *
 * This lets specific types of settings modify the model before anything uses it.
 *
 * Fieldset, for instance, uses this hook to instantiate its settings as a collection.
 * 
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/settingModel',[], function() {
	var model = Backbone.Model.extend( {
		defaults: {
			settings: false,
			hide_merge_tags: false,
			error: false
		},

		initialize: function() {
			// Send out two messages saying that we've initialized a setting model.
			nfRadio.channel( 'app' ).trigger( 'init:settingModel', this );
			nfRadio.channel( this.get( 'type' ) ).trigger( 'init:settingModel', this );
			nfRadio.channel( 'setting-name-' + this.get( 'name' ) ).trigger( 'init:settingModel', this );
			this.on( 'change:error', this.maybePreventUI, this );

			/*
			 * If we have an objectType set on our collection, then we're creating a model for the generic settings collection.
			 * If we're using merge tags in this setting
			 */
			if( 'undefined' == typeof this.collection ) return;

			if ( this.get( 'use_merge_tags' ) && 'undefined' != typeof this.collection.options.objectType ) {
				this.listenTo( nfRadio.channel( 'app' ), 'update:fieldKey', this.updateKey );
			}
		},

		/**
		 * When a field key is updated, send out a radio message requesting that this setting be checked for the old key.
		 * We want to send the message on the objectType channel.
		 * This means that if this setting is for fields, it will trigger on the fields channel, actions, etc.
		 * 
		 * @since  3.0
		 * @param  Backbone.Model 	keyModel data model representing the field for which the key just changed
		 * @return void
		 */
		updateKey: function( keyModel ) {
			nfRadio.channel( 'app' ).trigger( 'fire:updateFieldKey', keyModel, this );
		},

		maybePreventUI: function() {
			if ( this.get( 'error' ) ) {
				nfRadio.channel( 'drawer' ).request( 'prevent:close', 'setting-' + this.get( 'name' ) + '-error' );
				nfRadio.channel( 'app' ).request( 'prevent:changeDomain', 'setting-' + this.get( 'name' ) + '-error' );				
			} else {
				nfRadio.channel( 'drawer' ).request( 'enable:close', 'setting-' + this.get( 'name' ) + '-error' );
				nfRadio.channel( 'app' ).request( 'enable:changeDomain', 'setting-' + this.get( 'name' ) + '-error' );
			}
		}
	} );
	
	return model;
} );
/**
 * Collections of settings for each field type.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/settingCollection',['models/app/settingModel'], function( settingModel ) {
	var collection = Backbone.Collection.extend( {
		model: settingModel,

		initialize: function( models, options ) {
			this.options = options || {};
		}
	} );
	return collection;
} );
/**
 * Model that represents our type settings groups.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/settingGroupModel',[ 'models/app/settingCollection' ], function( SettingCollection ) {
	var model = Backbone.Model.extend( {
		defaults: {
			display: false
		},

		initialize: function( options ) {
			if ( false == this.get( 'settings' ) instanceof Backbone.Collection ) {
				this.set( 'settings', new SettingCollection( this.get( 'settings' ) ) );
			}
		}
	} );
	
	return model;
} );
/**
 * Collection of our type settings groups.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/settingGroupCollection',['models/app/settingGroupModel'], function( settingGroupModel ) {
	var collection = Backbone.Collection.extend( {
		model: settingGroupModel
	} );
	return collection;
} );
/**
 * Model for our field type
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/typeModel',[ 'models/app/settingGroupCollection' ], function( SettingGroupCollection ) {
	var model = Backbone.Model.extend( {
		initialize: function() {
			if ( false === this.get( 'settingGroups' ) instanceof Backbone.Collection ) {
				this.set( 'settingGroups', new SettingGroupCollection( this.get( 'settingGroups' ) ) );
			}
			
			nfRadio.channel( 'fields' ).trigger( 'init:typeModel', this );
		}
	} );
	
	return model;
} );
/**
 * Collection that holds our field type models. 
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/typeCollection',['models/app/typeModel'], function( typeModel ) {
	var collection = Backbone.Collection.extend( {
		model: typeModel,
		type: false,

		initialize: function( models, options ) {
			_.each( options, function( option, key ) {
				this[ key ] = option;
			}, this );
		}
	} );
	return collection;
} );
/**
 * Add action drawer.
 *
 * TODO: make dynamic
 * 
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/actions/drawer/addAction',['views/actions/drawer/typeCollection', 'models/app/typeCollection'], function( actionTypeCollectionView, actionTypeCollection ) {

	var view = Marionette.LayoutView.extend( {
		template: '#tmpl-nf-drawer-content-add-action',

		regions: {
			primary: '#nf-drawer-primary',
			
			payments: '#nf-drawer-secondary-payments',
			marketing: '#nf-drawer-secondary-marketing',
			management: '#nf-drawer-secondary-management',
			workflow: '#nf-drawer-secondary-workflow',
			notifications: '#nf-drawer-secondary-notifications',
			misc: '#nf-drawer-secondary-misc',
		},

		initialize: function() {
			this.listenTo( nfRadio.channel( 'drawer' ), 'filter:actionTypes', this.filteractionTypes );
			this.listenTo( nfRadio.channel( 'drawer' ), 'clear:filter', this.removeactionTypeFilter );
		
			this.installedActions = nfRadio.channel( 'actions' ).request( 'get:installedActions' );
			this.primaryCollection = this.installedActions;

			this.availableActions = nfRadio.channel( 'actions' ).request( 'get:availableActions' );
			this.updateAvailableActionGroups();
		},

		onShow: function() {
			this.primary.show( new actionTypeCollectionView( { collection: this.primaryCollection } ) );

			this.payments.show( new actionTypeCollectionView( { collection: this.paymentsCollection } ) );
			this.marketing.show( new actionTypeCollectionView( { collection: this.marketingCollection } ) );
			this.management.show( new actionTypeCollectionView( { collection: this.managementCollection } ) );
			this.workflow.show( new actionTypeCollectionView( { collection: this.workflowCollection } ) );
			this.notifications.show( new actionTypeCollectionView( { collection: this.notificationsCollection } ) );
			this.misc.show( new actionTypeCollectionView( { collection: this.miscCollection } ) );		
		},

		getEl: function() {
			return jQuery( this.el ).parent();
		},

		filteractionTypes: function( filteredInstalled, filteredAvailable ) {
			this.primary.reset().show( new actionTypeCollectionView( { collection: filteredInstalled } ) );

			this.availableActions = filteredAvailable;
			this.updateAvailableActionGroups();
			this.payments.reset().show( new actionTypeCollectionView( { collection: this.paymentsCollection } ) );
			this.marketing.reset().show( new actionTypeCollectionView( { collection: this.marketingCollection } ) );
			this.management.reset().show( new actionTypeCollectionView( { collection: this.managementCollection } ) );
			this.workflow.reset().show( new actionTypeCollectionView( { collection: this.workflowCollection } ) );
			this.notifications.reset().show( new actionTypeCollectionView( { collection: this.notificationsCollection } ) );
			this.misc.reset().show( new actionTypeCollectionView( { collection: this.miscCollection } ) );	
			
		},

		removeactionTypeFilter: function () {
			this.primary.show( new actionTypeCollectionView( { collection: this.primaryCollection } ) );

			this.availableActions = nfRadio.channel( 'actions' ).request( 'get:availableActions' );
			this.updateAvailableActionGroups();
			this.payments.show( new actionTypeCollectionView( { collection: this.paymentsCollection } ) );
			this.marketing.show( new actionTypeCollectionView( { collection: this.marketingCollection } ) );
			this.management.show( new actionTypeCollectionView( { collection: this.managementCollection } ) );
			this.workflow.show( new actionTypeCollectionView( { collection: this.workflowCollection } ) );
			this.notifications.show( new actionTypeCollectionView( { collection: this.notificationsCollection } ) );
			this.misc.show( new actionTypeCollectionView( { collection: this.miscCollection } ) );
		},

		updateAvailableActionGroups: function() {
			this.paymentsCollection = new actionTypeCollection(
				this.availableActions.where({group: 'payments'}),
				{
					slug: 'payments',
					nicename: nfi18n.paymentsActionNicename
				} 
			);

			this.marketingCollection = new actionTypeCollection(
				this.availableActions.where({group: 'marketing'}),
				{
					slug: 'marketing',
					nicename: nfi18n.marketingActionNicename
				} 
			);

			this.managementCollection = new actionTypeCollection(
				this.availableActions.where({group: 'management'}),
				{
					slug: 'management',
					nicename: nfi18n.managementActionNicename
				} 
			);

			this.workflowCollection = new actionTypeCollection(
				this.availableActions.where({group: 'workflow'}),
				{
					slug: 'workflow',
					nicename: nfi18n.workflowActionNicename
				} 
			);

			this.notificationsCollection = new actionTypeCollection(
				this.availableActions.where({group: 'notifications'}),
				{
					slug: 'notifications',
					nicename: nfi18n.notificationsActionNicename
				} 
			);

			this.miscCollection = new actionTypeCollection(
				this.availableActions.where({group: 'misc'}),
				{
					slug: 'misc',
					nicename: nfi18n.miscActionNicename
				} 
			);
		}

	} );

	return view;
} );
/**
 * Individual change item.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/contentViewChangesItem',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-content-view-changes-item',

		initialize: function() {
			this.model.on( 'change:disabled', this.render, this );
		},

		onBeforeDestroy: function() {
			this.model.off( 'change:disabled', this.render );
		},

		/**
		 * When we render this element, remove the extra wrapping <div> that backbone creates.
		 * 
		 * @since  3.0
		 * @return void
		 */
		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );
		},

		events: {
			'click .undoSingle': 'undoSingle'
		},

		undoSingle: function( e ) {
			nfRadio.channel( 'drawer' ).trigger( 'click:undoSingle', this.model );
		}
	});

	return view;
} );
/**
 * Changes collection view.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/contentViewChanges',['views/app/drawer/contentViewChangesItem'], function( viewChangesItem ) {
	var view = Marionette.CollectionView.extend( {
		tagName: 'table',
        className: 'nf-changes',
		childView: viewChangesItem
	} );

	return view;
} );

/**
 * Handles clicks on the 'view changes' button in the header.
 * 
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/headerViewChanges',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-header-view-changes',

		events: {
			'click .undoChanges': 'clickUndoChanges'
		},

		clickUndoChanges: function( e ) {
			nfRadio.channel( 'drawer' ).trigger( 'click:undoChanges' );
		}
	});

	return view;
} );
/**
 * Error view used for settings.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/settingError',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-edit-setting-error'
	});

	return view;
} );
define( 'views/app/drawer/itemSetting',['views/app/drawer/mergeTagsContent', 'views/app/drawer/settingError'], function( mergeTagsContentView, settingErrorView ) {
	var view = Marionette.LayoutView.extend({
		tagName: 'div',
		template: '#tmpl-nf-edit-setting-wrap',

		regions: {
			error: '.nf-setting-error'
		},

		initialize: function( data ) {
			this.dataModel = data.dataModel;
			/*
			 * Send out a request on the setting-type-{type} channel asking if we should render on dataModel change.
			 * Defaults to false.
			 * This lets specific settings, like RTEs, say that they don't want to be re-rendered when their data model changes.
			 */
			var renderOnChange = ( 'undefined' == typeof nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).request( 'renderOnChange' ) ) ? false : nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).request( 'renderOnChange' );
			
			if ( renderOnChange ) {
				this.dataModel.on( 'change:' + this.model.get( 'name' ), this.render, this );
			}

			this.model.on( 'change:error', this.renderError, this );
			this.model.on( 'change:warning', this.renderWarning, this );

			var deps = this.model.get( 'deps' );
			if ( deps ) {
				// If we don't have a 'settings' property, this is a legacy depdency setup.
				if ( 'undefined' == typeof deps.settings ) {
					deps.settings = [];
					_.each(deps, function(dep, name){
						if( 'settings' !== name ) {
							deps.settings.push( { name: name, value: dep } );
						}
					});
					deps.match = 'all';
				}

				for (var i = deps.settings.length - 1; i >= 0; i--) {
					let name = deps.settings[i].name;
					this.dataModel.on( 'change:' + name, this.render, this );
				}
			}

            /**
			 * For settings that require a remote refresh
			 *   add an "update"/refresh icon to the label.
             */
            var remote = this.model.get( 'remote' );
			if( remote ) {
                if( 'undefined' != typeof remote.refresh || remote.refresh ) {
					var labelText, updateIcon, updateLink, labelWrapper;

                    labelText = document.createTextNode( this.model.get('label') );

                    updateIcon = document.createElement( 'span' );
                    updateIcon.classList.add( 'dashicons', 'dashicons-update' );

                    updateLink = document.createElement( 'a' );
                    updateLink.classList.add( 'extra' );
                    updateLink.appendChild( updateIcon );

                    // Wrap the label text and icon/link in a parent element.
                    labelWrapper = document.createElement( 'span' );
                    labelWrapper.appendChild( labelText );
                    labelWrapper.appendChild( updateLink );

                    // The model expects a string value.
                    this.model.set('label', labelWrapper.innerHTML );
                }

				nfRadio.channel( 'setting' ).trigger( 'remote', this.model, this.dataModel, this );
				this.model.on( 'rerender', this.render, this );
			}

			/*
			 * When our drawer opens, send out a radio message on our setting type channel.
			 */
			this.listenTo( nfRadio.channel( 'drawer' ), 'opened', this.drawerOpened );

			/*
			 * When our drawer closes, send out a radio message on our setting type channel.
			 */
			this.listenTo( nfRadio.channel( 'drawer' ), 'closed', this.drawerClosed );
		},

		onBeforeDestroy: function() {
			this.dataModel.off( 'change:' + this.model.get( 'name' ), this.render );
			this.model.off( 'change:error', this.renderError );

			var deps = this.model.get( 'deps' );
			if ( deps ) {
				for (var i = deps.settings.length - 1; i >= 0; i--) {
					let name = deps.settings[i].name;
					this.dataModel.off( 'change:' + name, this.render );
				}
			}

			if( this.model.get( 'remote' ) ) {
				this.model.off( 'rerender', this.render, this );
			}

			/*
			 * Send out a radio message.
			 */
			nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'destroy:setting', this.model, this.dataModel, this );
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'destroy:setting', this.model, this.dataModel, this );
		
			/*
			 * Unescape any HTML being saved if we are a textbox.
			 */
			if ( 'textbox' == this.model.get( 'type' ) ) {
				var setting = this.model.get( 'name' );
				var value = this.dataModel.get( setting );
				this.dataModel.set( setting, _.unescape( value ), { silent: true } );
			}

		},

		onBeforeRender: function() {
			/*
			 * We want to escape any HTML being output if we are a textbox.
			 */
			if ( 'textbox' == this.model.get( 'type' ) ) {
				var setting = this.model.get( 'name' );
				var value = this.dataModel.get( setting );
				this.dataModel.set( setting, _.escape( value ), { silent: true } );
			}
			
			nfRadio.channel( 'app' ).trigger( 'before:renderSetting', this.model, this.dataModel );
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'before:renderSetting', this.model, this.dataModel, this );
			nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'before:renderSetting', this.model, this.dataModel, this );
		},

		onRender: function() {
			this.mergeTagsContentView = false;
			var that = this;

			/*
			 * Send out a radio message.
			 */
			nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'render:setting', this.model, this.dataModel, this );
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'render:setting', this.model, this.dataModel, this );

			jQuery( this.el ).find( '.nf-help' ).each(function() {
				var content = jQuery(this).next('.nf-help-text');
				jQuery( this ).jBox( 'Tooltip', {
					content: content,
					maxWidth: 200,
					theme: 'TooltipBorder',
					trigger: 'click',
					closeOnClick: true
				})
		    });
			
		    if ( this.model.get( 'use_merge_tags' ) ) {
		    	nfRadio.channel( 'mergeTags' ).request( 'init', this );
		    }

			/*
			 * Apply Setting Field Masks
			 */
			var mask = this.model.get( 'mask' );

			if( typeof mask != "undefined" ){

				var input = jQuery( this.$el ).find( 'input' );
				jQuery( input ).attr( 'contentEditable', true );
				switch( mask.type ){
					case 'numeric':
						input.autoNumeric({
							aSep: thousandsSeparator,
							aDec: decimalPoint
						});
						break;
					case 'currency':

						var currency = nfRadio.channel( 'settings' ).request( 'get:setting', 'currency' );
						var currencySymbol = nfAdmin.currencySymbols[ currency ] || '';

						input.autoNumeric({
							aSign:  jQuery('<div />').html(currencySymbol).text(),
							aSep: thousandsSeparator,
							aDec: decimalPoint
						});
						break;
					case 'custom':
						if( mask.format ) input.mask( mask.format );
						break;
					default:
						// TODO: Error Logging.
						console.log( 'Notice: Mask type of "' + mask.type + '" is not supported.' );
				}
			}
			
			this.renderError();
		},

		onShow: function() {		
			/*
			 * Send out a radio message.
			 */
			nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'show:setting', this.model, this.dataModel, this );
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'show:setting', this.model, this.dataModel, this );
		},

		onAttach: function() {	
			/*
			 * Send out a radio message.
			 */
			nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'attach:setting', this.model, this.dataModel, this );
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'attach:setting', this.model, this.dataModel, this );
		},

		renderError: function() {
			if ( this.model.get( 'error' ) ) {
				jQuery( this.el ).find( '.nf-setting' ).addClass( 'nf-error' );
				this.error.show( new settingErrorView( { model: this.model } ) );
			} else {
				jQuery( this.el ).find( '.nf-setting' ).removeClass( 'nf-error' );
				this.error.empty();
			}
		},

        renderWarning: function() {
            if ( this.model.get( 'warning' ) ) {
                jQuery( this.el ).find( '.nf-setting' ).addClass( 'nf-warning' );
                this.error.show( new settingErrorView( { model: this.model } ) );
            } else {
                jQuery( this.el ).find( '.nf-setting' ).removeClass( 'nf-warning' );
                this.error.empty();
            }
        },

		templateHelpers: function () {
			var that = this;
	    	return {

	    		renderVisible: function() {

					if(!nfAdmin.devMode){
						if('Action' == that.dataModel.get('objectType') && 'email' == that.dataModel.get('type')){
							if('cc' == this.name) return 'style="display:none;"';
							if('bcc' == this.name) return 'style="display:none;"';
							if('from_name' == this.name) return 'style="display:none;"';
							if('from_address' == this.name) return 'style="display:none;"';
							if('email_format' == this.name) return 'style="display:none;"';
						}
						
						if('Action' == that.dataModel.get('objectType') && 'save' == that.dataModel.get('type')){
							if('submitter_email' == this.name) return 'style="display:none;"';
						}

						if('label_pos' == this.name) return 'style="display:none;"';
						if('input_limit' == this.name) return 'style="display:none;"';
						if('input_limit_type' == this.name) return 'style="display:none;"';
						if('input_limit_msg' == this.name) return 'style="display:none;"';
						if('help_text' == this.name) return 'style="display:none;"';
						if('disable_input' == this.name) return 'style="display:none;"';
						if('disable_browser_autocomplete' == this.name) return 'style="display:none;"';
						if('mask' == this.name) return 'style="display:none;"';
						if('custom_mask' == this.name) return 'style="display:none;"';
						if('custom_name_attribute' == this.name) return 'style="display:none;"';
						if('personally_identifiable' == this.name) return 'style="display:none;"';
						
						// "administration" settings
						if('key' == this.name) return 'style="display:none;"';
						if('admin_label' == this.name) return 'style="display:none;"';
						if('num_sort' == this.name) return 'style="display:none;"';
						if('user_state' == this.name) return 'style="display:none;"';

						
						if('checkbox' == that.dataModel.get('type')){
							if('checked_value' == this.name) return 'style="display:none;"';
							if('unchecked_value' == this.name) return 'style="display:none;"';
							if('checked_calc_value' == this.name) return 'style="display:none;"';
							if('unchecked_calc_value' == this.name) return 'style="display:none;"';
						}

						if('starrating' == that.dataModel.get('type')){
							if('default' == this.name) return 'style="display:none;"';
						}

						if('listmultiselect' == that.dataModel.get('type')){
							if('box_size' == this.name) return 'style="display:none;"';
						}

						if('date' == that.dataModel.get('type')){
							if('year_range_start' == this.name) return 'style="display:none;"';
							if('year_range_end' == this.name) return 'style="display:none;"';
						}
					}

					return nfRadio.channel( 'settings' ).request( 'check:deps', this, that );
	    		},

	    		renderSetting: function(){
	    			if ( 'undefined' != typeof that.dataModel.get( this.name ) ) {
	    				this.value = that.dataModel.get( this.name );
	    			} else if ( 'undefined' == typeof this.value ) {
	    				this.value = '';
	    			}
	    			var setting = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-edit-setting-' + this.type );
					return setting( this );
				},

				renderLabelClasses: function() {
					var classes = '';
					if ( this.use_merge_tags ) {
						classes += ' has-merge-tags';
					}
					if ( 'rte' == this.type ) {
						classes += ' rte';
					}

					return classes;
				},

				renderClasses: function() {
					var classes = 'nf-setting ';
					if ( 'undefined' != typeof this.width ) {
						classes += 'nf-' + this.width;
					} else {
						classes += ' nf-one-half';
					}

					if ( this.error ) {
						classes += ' nf-error';
					}

					return classes;
				},

				renderTooltip: function() {
					if ( ! this.help ) return '';
					var helpText, helpTextContainer, helpIcon, helpIconLink, helpTextWrapper;

					helpText = document.createElement( 'div' );
					helpText.innerHTML = this.help;
					
					helpTextContainer = document.createElement( 'div' );
					helpTextContainer.classList.add( 'nf-help-text' );
					helpTextContainer.appendChild( helpText );

					helpIcon = document.createElement( 'span' );
					helpIcon.classList.add( 'dashicons', 'dashicons-admin-comments' );
                    helpIconLink = document.createElement( 'a' );
                    helpIconLink.classList.add( 'nf-help' );
                    helpIconLink.setAttribute( 'href', '#' );
                    helpIconLink.setAttribute( 'tabindex', '-1' );
                    helpIconLink.appendChild( helpIcon );

                    helpTextWrapper = document.createElement( 'span' );
                    helpTextWrapper.appendChild( helpIconLink );
                    helpTextWrapper.appendChild( helpTextContainer );

                    // The template expects a string value.
					return helpTextWrapper.innerHTML;
				},

			    /*
			     * Render a select element with only the email fields on the
			      * form
			     */
			    renderEmailFieldOptions: function() {
				    var fields = nfRadio.channel( 'fields' ).request( 'get:collection' );

				    initialOption = document.createElement( 'option' );
				    initialOption.value = '';
				    initialOption.label = '--';
				    initialOption.innerHTML = '--';

				    var select_value = '';
				    var select = document.createElement( 'select' );
				    select.classList.add( 'setting' );
				    select.setAttribute( 'data-id', 'my_seledt' );
				    select.appendChild( initialOption );

				    var index = 0;
				    var that = this;
				    fields.each( function( field ) {
					    // Check for the field type in our lookup array and...
					    if( 'email' != field.get( 'type' ) ) {
						    // Return if the type is in our lookup array.
						    return '';
					    }

					    var option = document.createElement( 'option' );

					    option.value = field.get( 'key' );
					    option.innerHTML = field.get( 'label' );
					    option.label = field.get( 'label' );
					    
					    if( that.value === field.get( 'key' ) ) {
						    option.setAttribute( 'selected', 'selected' );
					    }
					    select.appendChild( option );
					    index = index + 1;
				    });

				    label = document.createElement( 'label' );
				    label.classList.add( 'nf-select' );

				    label.appendChild( select );

				    // Select Lists need an empty '<div></div>' for styling purposes.
				    emptyContainer = document.createElement( 'div' );
				    label.appendChild( emptyContainer );

				    // The template requires a string.
				    return label.innerHTML;
			    },

				renderMergeTags: function() {
					if ( this.use_merge_tags && ! this.hide_merge_tags ) {
						return '<span class="dashicons dashicons-list-view merge-tags"></span>';
					} else {
						return '';
					}
				},

			    /**
			     * Renders min and/or max attributes for the number input
			     *
			     * @returns {string}
			     */
			    renderMinMax: function() {
					var minMaxStr = '';
					// if we have a min value set, then output it
					if( 'undefined' != typeof this.min_val && null != this.min_val && jQuery.isNumeric( this.min_val ) ) {
						minMaxStr = minMaxStr + "min='" + this.min_val + "'";
					}

					// if we have a max value set, then output it
				    if( 'undefined' != typeof this.max_val && '' != this.max_val && jQuery.isNumeric( this.max_val ) ) {
					    minMaxStr = minMaxStr + " max='" + this.max_val + "'";
				    }

				    // if we have a step size set, then output it
				    if( 'undefined' != typeof this.step && '' != this.step && jQuery.isNumeric( this.step ) ) {
					    minMaxStr = minMaxStr + " step='" + this.step + "'";
				    }

				    return minMaxStr;
			    },

			    /**
			     * Returns a string to let the user know the min and/or max
			     * value for the field
			     *
			     * @returns {string}
			     */
			    renderMinMaxHelper: function() {
				    var minMaxHelperStr = '';
				    // if we have a min value output it to the helper text
				    if( 'undefined' != typeof this.min_val && null != this.min_val && jQuery.isNumeric( this.min_val ) ) {
				    	// empty string? then add '('
				    	if( 0 == minMaxHelperStr.length ) {
				    		minMaxHelperStr = "(";
					    }
					    minMaxHelperStr = minMaxHelperStr +  nfi18n.minVal + ": " + this.min_val;
				    }

				    // if we have a max value output it to the helper text
				    if( 'undefined' != typeof this.max_val && '' != this.max_val && jQuery.isNumeric( this.max_val ) ) {
					    // empty string? then add '('
					    if( 0 == minMaxHelperStr.length ) {
						    minMaxHelperStr = "(";
					    } else {
					    	// else, we know we have a min so add a comma
					    	minMaxHelperStr = minMaxHelperStr + ", ";
					    }
					    minMaxHelperStr = minMaxHelperStr + nfi18n.maxVal + ": " + this.max_val;
				    }

				    // if not an empty string, then add ')'
				    if( 0 < minMaxHelperStr.length ) {
					    minMaxHelperStr = minMaxHelperStr + ")";
				    }

				    return minMaxHelperStr;
				},
			}
		},

		events: {
			'change .setting': 'changeSetting',
			'keyup .setting': 'keyUpSetting',
			'click .setting': 'clickSetting',
			'click .extra': 'clickExtra'
		},

		changeSetting: function( e ) {
			//Check characters set in custom classes match sanitize_html_class
			if ( 'textbox' == this.model.get( 'type' ) &&  this.model.get('name').endsWith("_class" )) {
				const regexp = /^[a-zA-Z 0-9-_]+$/;
				if(e.target.value.search(regexp) === -1 &&  ''!== e.target.value){
					this.model.set('error', "HTML classes only allow - _ and alphanumeric characters." )
				} else if(e.target.value.search(regexp) === 0 || ''=== e.target.value){
					this.model.unset('error');
				}
			}
			nfRadio.channel( 'app' ).trigger( 'change:setting', e, this.model, this.dataModel );
		},

		keyUpSetting: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'keyup:setting', e, this.model, this.dataModel );
			nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'keyup:setting', e, this.model, this.dataModel );
		},

		clickSetting: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:setting', e, this.model, this.dataModel );
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'click:setting', e, this.model, this.dataModel, this );
		},

		clickExtra: function( e ) {
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'click:extra', e, this.model, this.dataModel, this );
			nfRadio.channel( 'setting-type-' + this.model.get( 'name' ) ).trigger( 'click:extra', e, this.model, this.dataModel, this );
			nfRadio.channel( 'setting-name-' + this.model.get( 'name' ) ).trigger( 'click:extra', e, this.model, this.dataModel, this );
		},

		drawerOpened: function() {
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'drawer:opened', this.model, this.dataModel, this );
		},

		drawerClosed: function() {
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'drawer:closed', this.model, this.dataModel, this );
		}
	});

	return view;
} );

/**
 * Changes collection view.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/contentPublicLink',['views/app/drawer/itemSetting'], function( itemSettingView) {
	var view = Marionette.LayoutView.extend( {
		tagName: 'div',
        template: '#tmpl-nf-drawer-content-public-link',
        
		regions: {
            embedForm: '.embed-form',
			enablePublicLink: '.enable-public-link',
            copyPublicLink: '.copy-public-link',
        },

		onRender: function() {
            var formModel = Backbone.Radio.channel('app').request('get:formModel');
            var formSettingsDataModel = nfRadio.channel( 'settings' ).request( 'get:settings' );

            var allowPublicLinkSettingModel = nfRadio.channel( 'settings' ).request( 'get:settingModel', 'allow_public_link' );
            this.enablePublicLink.show( new itemSettingView( { model: allowPublicLinkSettingModel, dataModel: formSettingsDataModel } ) );
            
            var embedForm = "[ninja_form id='{FORM_ID}']".replace('{FORM_ID}', formModel.get('id'));
            formSettingsDataModel.set('embed_form', embedForm);

            var embedFormSettingModel = nfRadio.channel( 'settings' ).request( 'get:settingModel', 'embed_form' );
            this.embedForm.show( new itemSettingView( { model: embedFormSettingModel, dataModel: formSettingsDataModel } ) );

            var public_link_key = formSettingsDataModel.get('public_link_key');
            
            /**
             * Generate a public link key which is follows the format:
             * Form Id + 4 consecutive base 36 numbers
             */
            if (!public_link_key) {
                public_link_key = nfRadio.channel('app').request('generate:publicLinkKey');
            }

            // apply public link url to settings (ending with key)
            var publicLink = nfAdmin.publicLinkStructure.replace('[FORM_ID]', public_link_key);
            formSettingsDataModel.set('public_link', publicLink);
            
            // Display public link
            var publicLinkSettingModel = nfRadio.channel( 'settings' ).request( 'get:settingModel', 'public_link' );
            this.copyPublicLink.show(new itemSettingView( { model: publicLinkSettingModel, dataModel: formSettingsDataModel } ));
        },

		events: {
			'click #embed_form + .js-click-copytext': 'copyFormEmbedHandler',
			'click #public_link + div > .js-click-copytext': 'copyPublicLinkHandler',
			'click #public_link + div > .js-click-resettext': 'confirmResetPublicLinkHandler',
			'click #public_link + div > .js-click-confirm': 'resetPublicLinkHandler',
			'click #public_link + div > .js-click-cancel': 'cancelResetPublicLinkHandler'
		},

		copyFormEmbedHandler: function( e ) {

            document.getElementById('embed_form').select();
            document.execCommand('copy');

            e.target.innerHTML = 'Copied!';
            setTimeout(function(){ e.target.innerHTML = 'Copy'; }, 1500);
		},

		copyPublicLinkHandler: function( e ) {

            document.getElementById('public_link').select();
            document.execCommand('copy');

            e.target.innerHTML = 'Copied!';
            setTimeout(function(){ e.target.innerHTML = 'Copy'; }, 1500);
        },
        
        confirmResetPublicLinkHandler: function( e ) {
            _.each( e.target.parentNode.children, function( node ) {
                if ( node.classList.contains( 'js-click-copytext' ) || node.classList.contains( 'js-click-resettext' ) ) {
                    node.style.display = 'none';
                } else {
                    node.style.display = 'inline-block';
                }
            } );
        },

        resetPublicLinkHandler: function ( e ) {
            // Generate a new link.
            var public_link_key = nfRadio.channel('app').request('generate:publicLinkKey');
            var publicLink = nfAdmin.publicLinkStructure.replace('[FORM_ID]', public_link_key);
            var formSettingsDataModel = nfRadio.channel( 'settings' ).request( 'get:settings' );
            formSettingsDataModel.set('public_link', publicLink);
            // Reset the buttons.
            this.cancelResetPublicLinkHandler( e );
            _.each( e.target.parentNode.children, function( node ) {
                if ( node.classList.contains( 'js-click-resettext' ) ) {
                    node.style.display = 'inline-block';
                    node.classList.add('primary');
                    node.classList.remove('secondary');
                    node.innerHTML = 'Link Reset!';
                    setTimeout(function(){
                        node.classList.add('secondary');
                        node.classList.remove('primary');
                        node.innerHTML = 'Reset';
                    }, 1500);
                } else {
                    node.style.display = 'none';
                }
                if ( node.classList.contains( 'js-click-copytext' ) ) {
                    setTimeout(function(){
                        node.style.display = 'inline-block';
                    }, 1500);
                }
            } );
            // Update the visible public link.
            jQuery('#public_link').val( publicLink );
        },

        cancelResetPublicLinkHandler: function ( e ) {
            _.each( e.target.parentNode.children, function( node ) {
                if ( node.classList.contains( 'js-click-cancel' ) || node.classList.contains( 'js-click-confirm' ) ) {
                    node.style.display = 'none';
                } else {
                    node.style.display = 'inline-block';
                }
            } );
        }
	} );

	return view;
} );

/**
 * Handles clicks on the 'view changes' button in the header.
 * 
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/headerPublicLink',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-header-public-link'
	});

	return view;
} );
/**
 * Changes collection view.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/contentNewForm',['views/app/drawer/itemSetting'], function( itemSettingView) {
	var view = Marionette.LayoutView.extend( {
		tagName: 'div',
		template: '#tmpl-nf-drawer-content-new-form',

		regions: {
			formName: '.new-form-name',
			formSubmit: '.new-form-submit'
		},

		onRender: function() {
			var titleSettingModel = nfRadio.channel( 'settings' ).request( 'get:settingModel', 'title' );
			var addSubmitSettingModel = nfRadio.channel( 'settings' ).request( 'get:settingModel', 'add_submit' );
			var dataModel = nfRadio.channel( 'settings' ).request( 'get:settings' );
			this.formName.show( new itemSettingView( { model: titleSettingModel, dataModel: dataModel } ) );
			/*
			 * If we don't have any submit buttons on the form, prompt the user to add one on publish.
			 */
			var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
			var submitButtons = fieldCollection.findWhere( { type: 'submit' } );
			if ( 'undefined' == typeof submitButtons ) {
				this.formSubmit.show( new itemSettingView( { model: addSubmitSettingModel, dataModel: dataModel } ) );
			} else {
				dataModel.set( 'add_submit', 0 );
			}
		},

		events: {
			'click .publish': 'clickPublish'
		},

		clickPublish: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:confirmPublish', e );
		}
	} );

	return view;
} );

/**
 * Handles clicks on the 'view changes' button in the header.
 * 
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/headerNewForm',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-header-new-form'
	});

	return view;
} );
/**
 * Config file for our app drawers.
 *
 * this.collection represents all of our registered drawers.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/drawerConfig',[
	'models/app/drawerCollection',
	'views/fields/drawer/addField',
	'views/app/drawer/editSettings',
	'views/app/drawer/headerEditSettings',
	'views/actions/drawer/addAction',
	'views/app/drawer/contentViewChanges',
	'views/app/drawer/headerViewChanges',
	'views/app/drawer/contentPublicLink',
	'views/app/drawer/headerPublicLink',
	'views/app/drawer/contentNewForm',
	'views/app/drawer/headerNewForm'
	], function(
		drawerCollection,
		addFieldView,
		editSettingsView,
		editSettingsHeaderView,
		addActionView,
		viewChangesView,
		viewChangesHeaderView,
		publicLinkView,
		publicLinkHeaderView,		
		newFormView,
		newFormHeaderView,
		mobileItemControlsView
	) {
	var controller = Marionette.Object.extend( {
		initialize: function() {

			this.collection = new drawerCollection( [
				{
					id: 'addField',

					getContentView: function( data ) {
						return new addFieldView( data );
					}
				},
				{
					id: 'addAction',

					getContentView: function( data ) {
						return new addActionView( data );
					}
				},				
				{
					id: 'editSettings',

					/*
					 * TODO: Add filtering when editing settings. For now, removing them from settings.
					 */
					getHeaderView: function( data ) {
						/*
						 * Get a custom setting header view if one is set.
						 * TODO: Currently, this only works for advanced settings.
						 * This could be used to replace the need for a single config file.
						 */
						if ( 'undefined' != typeof data.typeModel ) {
							var view = nfRadio.channel( data.typeModel.get( 'id' ) ).request( 'get:drawerHeaderView' ) || editSettingsHeaderView;
						} else {
							var view = editSettingsHeaderView;
						}
						return new view( data );
					},

					getContentView: function( data ) {
						return new editSettingsView( data );
					}
				},
				{
					id: 'viewChanges',

					// getHeaderView() is defined by default, but we need to override it for the viewChanges drawer.
					getHeaderView: function( data ) {
						return new viewChangesHeaderView( data );
					},

					getContentView: function( data ) {
						return new viewChangesView( data );
					}
				},
				{
					id: 'publicLink',

					// getHeaderView() is defined by default, but we need to override it for the publicLink drawer.
					getHeaderView: function( data ) {
						return new publicLinkHeaderView( data );
					},

					getContentView: function( data ) {
						return new publicLinkView( data );
					}
				},
				{
					id: 'newForm',

					// getHeaderView() is defined by default, but we need to override it for the newForm drawer.
					getHeaderView: function( data ) {
						return new newFormHeaderView( data );
					},

					getContentView: function( data ) {
						return new newFormView( data );
					}
				}
			] );

			// Listen for requests for our drawer collection.
			nfRadio.channel( 'app' ).reply( 'get:drawerCollection', this.getDrawerCollection, this );
			// Listen for requests for specific drawer models.
			nfRadio.channel( 'app' ).reply( 'get:drawer', this.getDrawer, this );
		},

		getDrawerCollection: function() {
			return this.collection;
		},

		getDrawer: function( id ) {
			return this.collection.get( id );
		}

	});

	return controller;
} );
/**
 * Default settings title view.
 * 
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/defaultSettingsTitle',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-content-edit-settings-title-default',

		templateHelpers: function () {
	    	return {
	    		renderTypeNicename: function() {
	    			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
					var domainID = currentDomain.get( 'id' );
	    			var type = nfRadio.channel( domainID ).request( 'get:type', this.type );
	    			if ( 'undefined' != typeof type ) {
	    				return type.get( 'nicename' );
	    			} else {
	    				return '';
	    			}
				}
			};
		},
	});

	return view;
} );
/**
 * Empty view.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/empty',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-empty'
	});

	return view;
} );
/**
 * Model for our individual domains.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/domainModel',[ 'views/app/drawer/defaultSettingsTitle', 'views/app/empty' ], function( DefaultSettingsTitleView, EmptyView ) {
	var model = Backbone.Model.extend( {
		defaults: {
			dashicons: '',
			classes: '',
			active: false,
			url: '',
			hotkeys: false,
			disabled: false,

			getSettingsTitleView: function( data ) {
				return new DefaultSettingsTitleView( data );
			},

			getDefaultSettingsTitleView: function( data ) {
				return new DefaultSettingsTitleView( data );
			},

			getGutterLeftView: function( data ) {
				/*
				 * Return empty view
				 */
				return new EmptyView();
			},

			getGutterRightView: function( data ) {
				/* 
				 * Return empty view
				 */
				return new EmptyView();
			}
		}
	} );
	
	return model;
} );
/**
 * Holds all of our domain models.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/domainCollection',['models/app/domainModel'], function( domainModel ) {
	var collection = Backbone.Collection.extend( {
		model: domainModel
	} );
	return collection;
} );
define( 'views/fields/subHeader',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-sub-header-fields'
	});

	return view;
} );
define( 'views/fields/mainContentFieldCollection',[], function() {
	var view = Marionette.CollectionView.extend( {
		tagName: 'div',
		reorderOnSort: true,

		getChildView: function() {
			return nfRadio.channel( 'views' ).request( 'get:fieldItem' );
		},

		getEmptyView: function() {
			return nfRadio.channel( 'views' ).request( 'get:mainContentEmpty' );
		},

		initialize: function() {
			nfRadio.channel( 'fields' ).reply( 'get:sortableEl', this.getSortableEl, this );
			nfRadio.channel( 'fields' ).reply( 'init:sortable', this.initSortable, this );
			nfRadio.channel( 'fields' ).reply( 'destroy:sortable', this.destroySortable, this );
		},

		onRender: function() {
			if ( this.collection.models.length > 0 ) {
				jQuery( this.el ).addClass( 'nf-field-type-droppable' ).addClass( 'nf-fields-sortable' );
				var that = this;
				/* TODO: There's a bug with some Android phones and chrome. The fix below hasn't been implement.

				 * Instantiate our sortable field list, but only if we aren't on a mobile device.
				 *
				 * On Android, our sortable list isn't scrollable if it's instantiated at render.
				 * Instead, for mobile, we need to instantiate our sortable when the user tapholds and then
				 * destroy it when the drag stops.
				 */
				// if ( ! nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
					this.initSortable();
				// }
			}
			nfRadio.channel( 'app' ).trigger( 'render:fieldsSortable', this );
		},

		getSortableEl: function() {
			return this.el;
		},

		initSortable: function() {
			if ( nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
				var tolerance = 'pointer';
			} else {
				var tolerance = 'intersect';
			}

			jQuery( this.el ).sortable( {
				containment: 'parent',
				helper: 'clone',
				cancel: '.nf-item-controls',
				placeholder: 'nf-fields-sortable-placeholder',
				opacity: 0.95,
				grid: [ 5, 5 ],
				// scroll: false,
				appendTo: '#nf-main',
				scrollSensitivity: 10,
				//connectWith would allow drag and drop between fields already in the builder and the repeatable fieldset ( this is currently an issue until we deal with existing data stored)
				//connectWith: '.nf-fields-sortable', 

				receive: function( e, ui ) {
					if ( ui.item.dropping || jQuery(ui.item).hasClass("nf-over-repeater") ) return;
					nfRadio.channel( 'app' ).request( 'receive:fieldsSortable', ui );
				},

				over: function( e, ui ) {
					if ( ui.item.dropping ) return;
					nfRadio.channel( 'app' ).request( 'over:fieldsSortable', ui );
				},

				out: function( e, ui ) {
					if ( ui.item.dropping ) return;
					nfRadio.channel( 'app' ).request( 'out:fieldsSortable', ui );
				},

				start: function( e, ui ) {
					if ( ui.item.dropping ) return;
					nfRadio.channel( 'app' ).request( 'start:fieldsSortable', ui );
				},

				update: function( e, ui ) {
					if ( ui.item.dropping ) return;
					nfRadio.channel( 'app' ).request( 'update:fieldsSortable', ui, this );
				},

				stop: function( e, ui ) {
					if ( ui.item.dropping ) return;
					nfRadio.channel( 'app' ).request( 'stop:fieldsSortable', ui );
				}
			} );
		},

		destroySortable: function() {
			jQuery( this.el ).sortable( 'destroy' );
		},

		onAddChild: function( childView ) {
			if ( nfRadio.channel( 'fields' ).request( 'get:adding' ) ) {
				childView.$el.hide().show( 'clip' );
				nfRadio.channel( 'fields' ).request( 'set:adding', false );
			}
		}
		
	} );

	return view;
} );

define( 'views/fields/drawer/addSavedField',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-add-saved-field',

		initialize: function() {
			this.model.on( 'change:addSavedLoading', this.renderAddButton, this );
		},

		onRender: function() {
			this.renderAddButton();
		},

		renderAddButton: function() {
			if ( this.model.get( 'addSavedLoading' ) ) {
				var button = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-add-saved-field-loading' );
			} else {
				var button = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-add-saved-field-button' );
			}
			jQuery( this.el ).find( '.add-button' ).html( button( this ) );
		},

		onBeforeDestroy: function() {
			this.model.off( 'change:addSavedLoading', this.render );
		},

		events: {
			'click .nf-button': 'clickAddSavedField'
		},

		clickAddSavedField: function( e ) {
			nfRadio.channel( 'drawer' ).trigger( 'click:addSavedField', e, this.model );
		}
	});

	return view;
} );

/**
 * Fields settings title view.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/fields/drawer/settingsTitle',['views/fields/drawer/addSavedField'], function( addSavedFieldView ) {
	var view = Marionette.LayoutView.extend({
		tagName: 'div',
		template: '#tmpl-nf-drawer-content-edit-settings-title-fields',

		initialize: function() {
			this.model.on( 'change:saved', this.render, this );
			this.model.on( 'change:label', this.renderjBoxContent, this );
		},

		regions: {
			addSaved: '.nf-add-saved-field'
		},

		onBeforeDestroy: function() {
			this.model.off( 'change:saved', this.render );
			this.addSavedjBox.destroy();
			this.model.unset( 'jBox', { silent: true } );
		},

		onRender: function() {
			this.renderjBoxContent();
			var that = this;
			this.addSavedjBox = new jBox( 'Tooltip', {
				trigger: 'click',
				title: 'Add to Favorite Fields',
				position: {
					x:'left',
					y:'center'
				},
				outside:'x',
				closeOnClick: 'body',

				onCreated: function() {
					this.setContent( jQuery( that.el ).find( '.nf-add-saved-field' ) );
				}
			} );
			this.addSavedjBox.attach( jQuery( this.el ).find( '.dashicons') );
			this.model.set( 'jBox', this.addSavedjBox, { silent: true } );
		},

		renderjBoxContent: function() {
			if ( this.addSaved ) {
				this.addSaved.show( new addSavedFieldView( { model: this.model } ) );
			}
		},

		templateHelpers: function () {
	    	return {
	    		renderTypeNicename: function() {
	    			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
					var domainID = currentDomain.get( 'id' );
	    			var type = nfRadio.channel( domainID ).request( 'get:type', this.type );
	    			var displayName = type.get( 'nicename' );

	    			if ( this.saved ) {
	    				var realType = nfRadio.channel( domainID ).request( 'get:type', type.get( 'type' ) );
	    				displayName += ' - ' + realType.get( 'nicename' );
	    			}
	    			return displayName;
				},
				
				renderSavedStar: function() {
					if ( this.saved ) {
						var star = 'filled';
					} else {
						var star = 'empty';
					}
					return '<span class="dashicons dashicons-star-' + star + '"></span>'
				}
			};
		}
	});

	return view;
} );
/**
 * Add main header.
 *
 * TODO: make dynamic
 * 
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/actions/mainHeader',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-main-header-actions',

		initialize: function() {
			var actionCollection = nfRadio.channel( 'actions' ).request( 'get:collection' );
			this.listenTo( actionCollection, 'add', this.render );
			this.listenTo( actionCollection, 'remove', this.render );
		},

		onRender: function() {
			var actionCollection = nfRadio.channel( 'actions' ).request( 'get:collection' );
			if ( actionCollection.models.length == 0 ) {
				jQuery( this.el ).hide();
			} else {
				jQuery( this.el ).show();
			}
		}
	});

	return view;
} );
/**
 * Actions subheader view.
 *
 * TODO: make dynamic
 * 
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/actions/subHeader',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-sub-header-actions'
	});

	return view;
} );
/**
 * Renders an application menu item from a domain model.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/itemControls',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-item-controls',

		initialize: function() {
			// Listen for domain changes and re-render when we detect one.
			// this.listenTo( nfRadio.channel( 'app' ), 'change:currentDomain', this.render );
		},

		/**
		 * When we render this view, remove the extra <div> tag created by backbone.
		 * 
		 * @since  3.0
		 * @return void
		 */
		onRender: function() {
			// this.$el = this.$el.children();
			// this.$el.unwrap();
			// this.setElement( this.$el );
			// 
			this.currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
		},

		events: {
			'mouseover .nf-item-control': 'mouseoverItemControl',
			'click .nf-edit-settings': 'clickEdit',
			'singletap .nf-item-control': 'singleTapEdit',
			'click .nf-item-delete': 'clickDelete',
			'click .nf-item-duplicate': 'clickDuplicateField'
		},

		clickEdit: function( e ) {
			if ( ! nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
				nfRadio.channel( 'app' ).trigger( 'click:edit', e, this.model );
			}
		},

		singleTapEdit: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:edit', e, this.model );
		},

		clickDelete: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:delete', e, this.model );
		},

		clickDuplicateField: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'click:duplicate', e, this.model );
		},

		mouseoverItemControl: function( e ) {
			nfRadio.channel( 'app' ).trigger( 'mouseover:itemControl', e, this.model );
		}
	});

	return view;
} );
/**
 * Single action table row
 *
 * TODO: make dynamic
 *
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/actions/actionItem',['views/app/itemControls'], function( itemControlsView ) {
	var view = Marionette.LayoutView.extend({
		tagName: 'tr',
		template: '#tmpl-nf-action-item',

		regions: {
			itemControls: '.nf-item-controls'
		},

		initialize: function() {
			this.template = nfRadio.channel( 'actions' ).request( 'get:actionItemTemplate' ) || this.template;
			this.model.on( 'change:label', this.render, this );
			this.model.on( 'change:editActive', this.render, this );
			this.model.on( 'change:active', this.maybeDeactivate, this );
		},

		onBeforeDestroy: function() {
			this.model.off( 'change:label', this.render );
			this.model.off( 'change:editActive', this.render );
			this.model.off( 'change:active', this.maybeDeactivate );
		},

		onRender: function() {
			if ( this.model.get( 'editActive' ) ) {
				jQuery( this.el ).addClass( 'active' );
			} else {
				jQuery( this.el ).removeClass( 'active' );
			}

			this.maybeDeactivate();

			this.itemControls.show( new itemControlsView( { model: this.model } ) );
		},

		maybeDeactivate: function() {
			if ( 0 == this.model.get( 'active' ) ) {
				jQuery( this.el ).addClass( 'deactivated' );
			} else {
				jQuery( this.el ).removeClass( 'deactivated' );
			}
		},

		events: {
			'change input': 'changeToggle',
			'click': 'maybeClickEdit'
		},

		maybeClickEdit: function( e ) {
			if ( 'TR' == jQuery( e.target ).parent().prop( 'tagName' ) ) {
				nfRadio.channel( 'app' ).trigger( 'click:edit', e, this.model );
			}
		},

		changeToggle: function( e ) {
			var setting = jQuery( e.target ).data( 'setting' );
			var settingModel = nfRadio.channel( 'actions' ).request( 'get:settingModel', setting );
			nfRadio.channel( 'app' ).request( 'change:setting', e, settingModel, this.model );
			nfRadio.channel( 'app' ).request( 'update:db' );
		},

		templateHelpers: function() {
			return {
				renderToggle: function( settingName ) {
					this.settingName = settingName || 'active';
					var actionLabel = this.label;
					this.label = '';
					this.value = this[ this.settingName ];
					this.name = this.id + '-' + this.settingName;
					var html = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-edit-setting-toggle' );
					html = html( this );
					this.label = actionLabel;
					return html;
				},

				renderTypeNicename: function() {
					var type = nfRadio.channel( 'actions' ).request( 'get:type', this.type );
					if ( 'undefined' == typeof type ) return;

					return type.get( 'nicename' );
				},

                /**
				 * [Deprecated] Tooltips are not currently implemented in the context of the action list.
				 *   However, the template uses a nested template which requires the helper method.
                 * @returns {string}
                 */
				renderTooltip: function() {
					return '';
				},

				renderMergeTags: function() {
					if ( this.use_merge_tags ) {
						return '<span class="dashicons dashicons-list-view merge-tags"></span>';
					} else {
						return '';
					}
				}
			}
		}
	});

	return view;
} );

define( 'views/actions/mainContentEmpty',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-main-content-actions-empty',

		onBeforeDestroy: function() {
			jQuery( this.el ).parent().parent().removeClass( 'nf-actions-empty' );
			// jQuery( this.el ).parent().removeClass( 'nf-fields-empty-droppable' ).droppable( 'destroy' );
		},

		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );
		},

		onShow: function() {
			jQuery( this.el ).parent().parent().addClass( 'nf-actions-empty' );
			// if ( jQuery( this.el ).parent().hasClass( 'ui-sortable' ) ) {
			// 	jQuery( this.el ).parent().sortable( 'destroy' );
			// }
			// jQuery( this.el ).parent().addClass( 'nf-fields-empty-droppable' );
			// jQuery( this.el ).parent().droppable( {
			// 	accept: function( draggable ) {
			// 		if ( jQuery( draggable ).hasClass( 'nf-stage' ) || jQuery( draggable ).hasClass( 'nf-field-type-button' ) ) {
			// 			return true;
			// 		}
			// 	},
			// 	hoverClass: 'nf-droppable-hover',
			// 	tolerance: 'pointer',
			// 	over: function( e, ui ) {
			// 		ui.item = ui.draggable;
			// 		nfRadio.channel( 'app' ).request( 'over:fieldsSortable', ui );
			// 	},
			// 	out: function( e, ui ) {
			// 		ui.item = ui.draggable;
			// 		nfRadio.channel( 'app' ).request( 'out:fieldsSortable', ui );
			// 	},
			// 	drop: function( e, ui ) {
			// 		ui.item = ui.draggable;
			// 		nfRadio.channel( 'app' ).request( 'receive:fieldsSortable', ui );
			// 		var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
			// 		fieldCollection.trigger( 'reset', fieldCollection );
			// 	},
			// } );
		}
	});

	return view;
} );
/**
 * Main content view for our actions.
 *
 * TODO: make dynamic
 *
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/actions/mainContent',['views/actions/actionItem', 'views/actions/mainContentEmpty'], function( actionView, emptyView ) {
	var view = Marionette.CompositeView.extend({
		template: '#tmpl-nf-action-table',
		childView: actionView,
		emptyView: emptyView,

		initialize: function() {
			this.template = nfRadio.channel( 'actions' ).request( 'get:mainContentTemplate' ) || this.template;
		},

		onRender: function() {
			jQuery( this.el ).droppable( {
				accept: '.nf-action-type-draggable',
				activeClass: 'nf-droppable-active',
				hoverClass: 'nf-droppable-hover',
				drop: function( e, ui ) {
					nfRadio.channel( 'app' ).request( 'drop:actionType', e, ui );
				}
			} );
		},

		attachHtml: function( collectionView, childView ) {
			if ( 'undefined' == typeof nfRadio.channel( 'actions' ).request( 'get:type', childView.model.get( 'type' ) ) ) return;

			jQuery( collectionView.el ).find( 'tbody' ).append( childView.el );
		},
	});

	return view;
} );

define( 'views/advanced/mainHeader',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-main-header-settings'
	});

	return view;
} );
define( 'views/advanced/subHeader',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-sub-header-settings'
	});

	return view;
} );
define( 'views/advanced/settingItem',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-form-setting-type',

		onBeforeDestroy: function() {
			this.model.off( 'change:editActive', this.updateActiveClass );
		},

		initialize: function() {
			this.model.on( 'change:editActive', this.updateActiveClass, this );
		},

		events: {
			'click': 'clickEdit'
		},

		clickEdit: function( e ) {
			nfRadio.channel( 'settings' ).trigger( 'click:edit', e, this.model );
		},

		templateHelpers: function() {
			return {
				renderClasses: function() {
					var classes = 'nf-setting-wrap ' + this.id;
	    			if ( this.editActive ) {
	    				classes += ' active';
	    			}
	    			return classes;
				}
			}
		},

		updateActiveClass: function() {
			if ( this.model.get( 'editActive' ) ) {
				jQuery( this.el ).find( '.nf-setting-wrap' ).addClass( 'active' );
			} else {
				jQuery( this.el ).find( '.nf-setting-wrap' ).removeClass( 'active' );
			}
		}
	});

	return view;
} );
define( 'views/advanced/mainContent',['views/advanced/settingItem'], function( settingItem ) {
	var view = Marionette.CompositeView.extend({
		childView: settingItem,
		template: '#tmpl-nf-advanced-main-content',

		attachHtml: function( collectionView, childView ) {
			jQuery( collectionView.el ).find( '.child-view-container' ).append( childView.el );
		}
	});

	return view;
} );
/**
 * Model that represents our form fields.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/fields/fieldModel',[], function() {
	var model = Backbone.Model.extend( {
		defaults: {
			objectType: 'Field',
			objectDomain: 'fields',
			editActive: false,
			order: 999,
			idAttribute: 'id'
		},

		initialize: function() {
			var type = this.get('type');
			if ( 'undefined' == typeof type ) return;

			// Listen for model attribute changes
			this.on( 'change', this.changeSetting, this );

			// Get our parent field type.
			var fieldType = nfRadio.channel( 'fields' ).request( 'get:type', this.get( 'type' ) );
			var parentType = fieldType.get( 'parentType' );

			// Loop through our field type "settingDefaults" and add any default settings.
			_.each( fieldType.get( 'settingDefaults' ), function( val, key ) {
				if ( 'undefined' == typeof this.get( key ) ) {
					this.set( key, val, { silent: true } );
				}
			}, this );

			/*
			 * If our field type is a saved field, set our field type to the actual field type
			 */
			if ( 'saved' == fieldType.get( 'section' ) ) {
				this.set( 'type', fieldType.get( 'type' ) );
			}

			if (type === 'listimage') {
				this.get = this.listimageGet;
				this.set = this.listimageSet;
			}

			/*
			 * Trigger an init event on three channels:
			 * 
			 * fields
			 * fields-parentType
			 * field-type
			 *
			 * This lets specific field types modify model attributes before anything uses them.
			 */ 
			nfRadio.channel( 'fields' ).trigger( 'init:fieldModel', this );
			nfRadio.channel( 'fields-' + parentType ).trigger( 'init:fieldModel', this );
			nfRadio.channel( 'fields-' + this.get( 'type' ) ).trigger( 'init:fieldModel', this );

			this.listenTo( nfRadio.channel( 'app' ), 'fire:updateFieldKey', this.updateFieldKey );
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

		/**
		 * Fires an event on the fieldSetting-{name} channel saying we've updated a setting.
		 * When we change the model attributes, fire an event saying we've changed something.
		 * 
		 * @since  3.0
		 * @return void
		 */
		changeSetting: function( model, options ) {
			nfRadio.channel( 'fieldSetting-' + _.keys( model.changedAttributes() )[0] ).trigger( 'update:setting', this, options.settingModel ) ;
			nfRadio.channel( 'fields' ).trigger( 'update:setting', this, options.settingModel );
			nfRadio.channel( 'app' ).trigger( 'update:setting', this, options.settingModel );
		},

		updateFieldKey: function( keyModel, settingModel ) {
			nfRadio.channel( 'app' ).trigger( 'replace:fieldKey', this, keyModel, settingModel );
		},
        
        /**
         * Function used to get the formatted lable of the fieldModel.
         * 
         * @since 3.3.3
         * @return String
         */
        formatLabel: function() {
            // Try to use admin label.
            var label = this.get( 'admin_label' );
            // If our admin label is empty...
            if ( '' == label ) {
                // Use the field label instead.
                label = this.get( 'label' );
            }
            return label;
        }
	} );
	
	return model;
} );
/**
 * Collection that holds our field models.
 * This is the actual field data created by the user.
 *
 * We listen to the add and remove events so that we can push the new id to either the new fields or removed fields property.
 *
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/fields/fieldCollection',['models/fields/fieldModel'], function( fieldModel ) {
	var collection = Backbone.Collection.extend( {
		model: fieldModel,
		comparator: function( model ){
			return parseInt( model.get( 'order' ) );
		},
		tmpNum: 1,

		initialize: function() {
			this.on( 'add', this.addField, this );
			this.on( 'remove', this.removeField, this );

			this.listenTo( this, 'add:field', this.addNewField );
			this.listenTo( this, 'append:field', this.appendNewField );
			this.listenTo( this, 'remove:field', this.removeFieldResponse );
			this.newIDs = [];
		},

		/**
		 * When we add a field, push the id onto our new fields property.
		 * This lets us tell the server that this is a new field to be added rather than a field to be updated.
		 *
		 * @since 3.0
		 * @param void
		 */
		addField: function( model ) {
			this.newIDs.push( model.get( 'id' ) );
		},

		/**
		 * When we remove a field, push the id onto our removed fields property.
		 *
		 * @since 3.0
		 * @param void
		 */
		removeField: function( model ) {
			this.removedIDs = this.removedIDs || {};
			this.removedIDs[ model.get( 'id' ) ] = model.get( 'id' );
		},

		addNewField: function( model ) {
			this.add( model );
		},

		appendNewField: function( model ) {
			if ( 0 == this.length ) {
				var order = 0;
			} else {
				var order = this.at( this.length -1 ).get( 'order' ) + 1;
			}

			model.set( 'order', order, { silent: true } );
			this.add( model );
		},

		removeFieldResponse: function( model ) {
			this.remove( model );
		},

		fieldExists: function( fieldModel ) {
			return -1 != this.indexOf( fieldModel );
		}
	} );
	return collection;
} );

/**
 * Config file for our app domains.
 * 
 * this.collection represents all of our app domain (fields, actions, settings) information.
 *
 * This doesn't store the current domain, but rather all the data about each.
 * 
 * This data includes:
 * hotkeys
 * header view
 * subheader view
 * content view
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/domainConfig',[
	// Require our domain collection
	'models/app/domainCollection',
	// Require our fields domain files
	'views/fields/subHeader',
	'views/fields/mainContentFieldCollection',
	'views/fields/drawer/settingsTitle',
	// Require our actions domain files
	'views/actions/mainHeader', 
	'views/actions/subHeader',
	'views/actions/mainContent',
	// Require our settings domain files
	'views/advanced/mainHeader',
	'views/advanced/subHeader',
	'views/advanced/mainContent',
	// Empty View
	'views/app/empty',
	// FieldCollection: used by the default formContentData filter
	'models/fields/fieldCollection'
	], 
	function( 
		appDomainCollection,
		fieldsSubHeaderView,
		FieldsMainContentFieldCollectionView,
		fieldsSettingsTitleView,
		actionsMainHeaderView,
		actionsSubHeaderView,
		actionsMainContentView,
		settingsMainHeaderView,
		settingsSubHeaderView,
		settingsMainContentView,
		EmptyView,
		FieldCollection
	) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			/*
			 * Add our default formContentView filter.
			 */
			nfRadio.channel( 'formContent' ).request( 'add:viewFilter', this.defaultFormContentView, 10, this );
			
			/*
			 * Add our default formContentData filter.
			 */
			nfRadio.channel( 'formContent' ).request( 'add:loadFilter', this.defaultFormContentLoad, 10, this );

			/*
			 * Add our default formContentGutterView filters.
			 */
			nfRadio.channel( 'formContentGutters' ).request( 'add:leftFilter', this.defaultFormContentGutterView, 10, this );
			nfRadio.channel( 'formContentGutters' ).request( 'add:rightFilter', this.defaultFormContentGutterView, 10, this );

			// Define our app domains
			this.collection = new appDomainCollection( [
				{
					id: 'fields',
					nicename: nfi18n.domainFormFields,
					hotkeys: {
						'Esc'				: 'close:drawer',
						'Ctrl+Shift+n'		: 'add:newField',
						'Ctrl+Shift+a'		: 'changeDomain:actions',
						'Ctrl+Shift+s'		: 'changeDomain:settings',
						'Alt+Ctrl+t'		: 'open:mergeTags',
						'up'				: 'up:mergeTags',
						'down'				: 'down:mergeTags',
						'Shift+return'		: 'return:mergeTags'
					},
					mobileDashicon: 'dashicons-menu',

					getSubHeaderView: function() {
						return new fieldsSubHeaderView();
					},

					/**
					 * Get the formContent view that should be used in our builder.
					 * Uses two filters:
					 * 1) One for our formContentData
					 * 2) One for our formContentView
					 *
					 * If we don't have any view filters, we use the default formContentView.
					 * 
					 * @since  3.0
					 * @return formContentView backbone view.
					 */
					getMainContentView: function( collection ) {
						var formContentData = nfRadio.channel( 'settings' ).request( 'get:setting', 'formContentData' );

						/*
						 * As of version 3.0, 'fieldContentsData' has deprecated in favour of 'formContentData'.
						 * If we don't have this setting, then we check for this deprecated value.
						 * 
						 * Set our fieldContentsData to our form setting 'fieldContentsData'
						 *
						 * TODO: Remove this backwards compatibility eventually.
						 */
						if ( ! formContentData ) {
							formContentData = nfRadio.channel( 'settings' ).request( 'get:setting', 'fieldContentsData' );
						}
						
						/*
						 * If we don't have a filter for our formContentData, default to fieldCollection.
						 */
						var formContentLoadFilters = nfRadio.channel( 'formContent' ).request( 'get:loadFilters' );
						
						/* 
						* Get our first filter, this will be the one with the highest priority.
						*/
						var sortedArray = _.without( formContentLoadFilters, undefined );
						var callback = _.first( sortedArray );
						formContentData = callback( formContentData, nfRadio.channel( 'app' ).request( 'get:formModel' ), true );
						
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

						nfRadio.channel( 'settings' ).request( 'update:setting', 'formContentData', formContentData, true );
						return new formContentView( { collection: formContentData } );
					},

					getSettingsTitleView: function( data ) {
						/*
						 * If we are dealing with a field model, return the fields settings view, otherwise, return the default.
						 */
						if ( 'fields' == data.model.get( 'objectDomain' ) ) {
							return new fieldsSettingsTitleView( data );
						} else {
							return this.get( 'getDefaultSettingsTitleView' ).call( this, data );
						}
						
					},

					getGutterLeftView: function( data ) {
						/*
						 * Check our fieldContentViewsFilter to see if we have any defined.
						 * If we do, overwrite our default with the view returned from the filter.
						 */
						var gutterFilters = nfRadio.channel( 'formContentGutters' ).request( 'get:leftFilters' );

						/* 
						* Get our first filter, this will be the one with the highest priority.
						*/
						var sortedArray = _.without( gutterFilters, undefined );
						var callback = _.first( sortedArray );
						gutterView = callback();

						return new gutterView(); 
					},

					getGutterRightView: function() {
						/*
						 * Check our fieldContentViewsFilter to see if we have any defined.
						 * If we do, overwrite our default with the view returned from the filter.
						 */
						var gutterFilters = nfRadio.channel( 'formContentGutters' ).request( 'get:rightFilters' );
						
						/* 
						* Get our first filter, this will be the one with the highest priority.
						*/
						var sortedArray = _.without( gutterFilters, undefined );
						var callback = _.first( sortedArray );
						gutterView = callback();

						return new gutterView(); 
					}

				},
				{
					id: 'actions',
					nicename: nfi18n.domainActions,
					hotkeys: {
						'Esc'				: 'close:drawer',
						'Ctrl+Shift+n'		: 'add:newAction',
						'Ctrl+Shift+f'		: 'changeDomain:fields',
						'Ctrl+Shift+s'		: 'changeDomain:settings',
						'Alt+Ctrl+t'		: 'open:mergeTags',
						'up'				: 'up:mergeTags',
						'down'				: 'down:mergeTags',
						'Shift+return'		: 'return:mergeTags'
					},
					mobileDashicon: 'dashicons-external',

					getSubHeaderView: function() {
						return new actionsSubHeaderView();
					},
					
					getMainContentView: function() {
						var collection = nfRadio.channel( 'actions' ).request( 'get:collection' );
						return new actionsMainContentView( { collection: collection } );
					}
				},
				{
					id: 'settings',
					nicename: nfi18n.domainAdvanced,
					hotkeys: {
						'Esc'				: 'close:drawer',
						'Ctrl+Shift+f'		: 'changeDomain:fields',
						'Ctrl+Shift+a'		: 'changeDomain:actions',
						'Alt+Ctrl+t'		: 'open:mergeTags',
						'up'				: 'up:mergeTags',
						'down'				: 'down:mergeTags',
						'Shift+return'		: 'return:mergeTags'
					},
					mobileDashicon: 'dashicons-admin-generic',

					getSubHeaderView: function() {
						return new settingsSubHeaderView();
					},
					
					getMainContentView: function() {
						var collection = nfRadio.channel( 'settings' ).request( 'get:typeCollection' );
						return new settingsMainContentView( { collection: collection } );
					}
				},
				{
					id: 'preview',
					nicename: 'Preview Form',
					classes: 'preview',
					dashicons: 'dashicons-visibility',
					mobileDashicon: 'dashicons-visibility',
					url: nfAdmin.previewurl
				}
			] );

			/*
			 * Send out a radio message with our domain config collection.
			 */
			nfRadio.channel( 'app' ).trigger( 'init:domainCollection', this.collection );

			/*
			 * Respond to requests to get the app domain collection.
			 */
			nfRadio.channel( 'app' ).reply( 'get:domainCollection', this.getDomainCollection, this );
			nfRadio.channel( 'app' ).reply( 'get:domainModel', this.getDomainModel, this );
		},

		getDomainCollection: function() {
			return this.collection;
		},

		getDomainModel: function( id ) {
			return this.collection.get( id );
		},

		defaultFormContentView: function( formContentData ) {
			return FieldsMainContentFieldCollectionView;
		},

		defaultFormContentLoad: function( formContentData ) {
			var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
			/*
			 * If we only have one load filter, we can just return the field collection.
			 */
			var formContentLoadFilters = nfRadio.channel( 'formContent' ).request( 'get:loadFilters' );
			var sortedArray = _.without( formContentLoadFilters, undefined );

			if ( 1 == sortedArray.length || 'undefined' == typeof formContentData || true === formContentData instanceof Backbone.Collection ) return fieldCollection;

			/*
			 * If another filter is registered, we are calling this from somewhere else.
			 */

        	var fieldModels = _.map( formContentData, function( key ) {
        		return fieldCollection.findWhere( { key: key } );
        	}, this );

        	return new FieldCollection( fieldModels );
		},

		defaultFormContentGutterView: function( formContentData ) {
			return EmptyView;
		}

	});

	return controller;
} );
/**
 * Model for our app data.
 * Listens for changes to the 'clean' attribute and triggers a radio message when the state changes.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/appModel',[], function() {
	var model = Backbone.Model.extend( {
		defaults: {
			loading: false
		},

		initialize: function() {
			// Listen to changes to our 'clean' attribute.
			this.on( 'change:clean', this.changeStatus, this );
		},

		changeStatus: function() {
			// Send out a radio message when the 'clean' attribute changes.
			nfRadio.channel( 'app' ).trigger( 'change:clean', this.get( 'clean' ) );
		}
	} );
	
	return model;
} );
/**
 * Creates and stores a model that represents app-wide data. i.e. current domain, current drawer, clean, etc.
 *
 * clean is a boolean that represents whether or not changes have been made.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/data',['models/app/appModel'], function( appModel ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Get the collection that represents all the parts of our application.
			var appDomainCollection = nfRadio.channel( 'app' ).request( 'get:domainCollection' );
			// Setup our initial model.
			this.model = new appModel( {
				currentDrawer: false,
				currentDomain: appDomainCollection.get( 'fields' ),
				clean: true
			} );

			/*
			 * Set the mobile setting used to track whether or not we're on a mobile device.
			 */
			var mobile = ( 1 == nfAdmin.mobile ) ? true : false;
			this.model.set( 'mobile', mobile );

			/*
			 * Respond to requests to see if we are on mobile.
			 */
			nfRadio.channel( 'app' ).reply( 'is:mobile', this.isMobile, this );

			/*
			 * Respond to app channel requests for information about the state of our app.
			 */
			nfRadio.channel( 'app' ).reply( 'get:data', this.getData, this );
			nfRadio.channel( 'app' ).reply( 'get:setting', this.getSetting, this );
			nfRadio.channel( 'app' ).reply( 'get:currentDomain', this.getCurrentDomain, this );
			nfRadio.channel( 'app' ).reply( 'get:currentDrawer', this.getCurrentDrawer, this );
			nfRadio.channel( 'drawer' ).reply( 'get:current', this.getCurrentDrawer, this );

			/*
			 * Respond to app channel requests to update app settings.
			 */		
			nfRadio.channel( 'app' ).reply( 'update:currentDomain', this.updateCurrentDomain, this );
			nfRadio.channel( 'app' ).reply( 'update:currentDrawer', this.updateCurrentDrawer, this );
			nfRadio.channel( 'app' ).reply( 'update:setting', this.updateSetting, this );

			nfRadio.channel( 'settings' ).reply( 'check:deps', this.checkDeps, this );

		},
		
		/**
		 * A more robust settings dependency system.
		 * This allows you to have a setting only show when X AND Y are met or when X OR Y are met.
		 * 
		 * @since  
		 * @param  {object} setting Setting object
		 * @param  {object} context Object context for where this is being called.
		 * @return {bool}/{string}
		 */
		checkDeps: function( setting, context ) {
			if ( ! setting.deps ) {
				return '';
			}

			// If we do have a "settings" property, then this is a new dependency format.
			let deps_settings = setting.deps.settings;
			let match = setting.deps.match;
			
			let hide = false;
			
			for (var i = deps_settings.length - 1; i >= 0; i--) {
				let name = deps_settings[i].name;
				let value = deps_settings[i].value;

				// Use == here instead of === in order to avoid string => int comparison.
			    if ( context.dataModel.get( name ) == value ) {
		        	// If we're looking for "any" match, we can go ahead and return here. 
		        	if ( 'any' == match ) {
		        		hide = false;
		        		break;
		        	}
		        } else {
	        		hide = true;
		        }
			}

			if ( hide ) {
				return 'style="display:none;"';
			}
			
			return '';
		},

		updateCurrentDomain: function( model ) {
			this.updateSetting( 'currentDomain', model );
		},

		updateSetting: function( setting, value ) {
			this.model.set( setting, value );
			return true;
		},

		getSetting: function( setting ) {
			return this.model.get( setting );
		},

		getData: function() {
			return this.model;
		},

		getCurrentDomain: function() {
			return this.model.get( 'currentDomain' );
		},

		updateCurrentDrawer: function( drawerID ) {
			this.updateSetting( 'currentDrawer', drawerID );
			return true;
		},

		getCurrentDrawer: function() {
			var currentDrawerID = this.model.get( 'currentDrawer' );
			return nfRadio.channel( 'app' ).request( 'get:drawer', currentDrawerID );
		},

		isMobile: function() {
			return this.model.get( 'mobile' );
		}


	});

	return controller;
} );
/**
 * Listens for click events to expand/collapse setting groups.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - New Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/drawerToggleSettingGroup',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen for click events on our settings group.
			this.listenTo( nfRadio.channel( 'drawer' ), 'click:toggleSettingGroup', this.toggleSettingGroup );
		},

		/**
		 * Set the 'display' attribute of our group model to true or false to toggle.
		 * 
		 * @since  3.0
		 * @param  Object			e     	event
		 * @param  backbone.model 	model 	group setting model
		 * @return void
		 */
		toggleSettingGroup: function( e, model ) {
			if ( model.get( 'display' ) ) {
				/*
				 * Make sure that none of our settings have errors
				 */
				var errors = false;
				_.each( model.get( 'settings' ).models, function( setting ) {
					if ( setting.get( 'error' ) ) {
						errors = true;
					}
				} );
				if ( ! errors ) {
					model.set( 'display', false );
				}
			} else {
				model.set( 'display', true );
			}
		}
	});

	return controller;
} );
/**
 * Updates our database with our form data.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/updateDB',[], function() {
	var controller = Marionette.Object.extend( {

		initialize: function() {
			// Listen for the closing of the drawer and update when it's closed.
			this.listenTo( nfRadio.channel( 'drawer' ), 'closed', this.updateDB );
			// Respond to requests to update the database.
			nfRadio.channel( 'app' ).reply( 'update:db', this.updateDB, this );
			/*
			 * Register our default formContent save filter.
			 * This converts our collection into an array of keys.
			 */
			nfRadio.channel( 'formContent' ).request( 'add:saveFilter', this.defaultSaveFilter, 10, this );
		},

		/**
		 * Update our database.
		 * If action isn't specified, assume we're updating the preview.
		 * 
		 * @since  3.0
		 * @param  string 	action preview or publish
		 * @return void
		 */
		updateDB: function( action ) {

			// If our app is clean, dont' update.
			if ( nfRadio.channel( 'app' ).request( 'get:setting', 'clean' ) ) {
				return false;
			}

			// Default action to preview.
			action = action || 'preview';

			// Setup our ajax actions based on the action we're performing
			if ( 'preview' == action ) {
				var jsAction = 'nf_preview_update';
			} else if ( 'publish' == action ) {
				var jsAction = 'nf_save_form';
				// now using a different ajax action
				// var jsAction = 'nf_batch_process';
			}

			var formModel = nfRadio.channel( 'app' ).request( 'get:formModel' );

			/*
			 * There are pieces of data that are only needed for the builder and not for the front-end.
			 * We need to unset those.
			 * TODO: Make this more dynamic/filterable.
			 */
			_.each( formModel.get( 'fields' ).models, function( fieldModel, index ) {
				fieldModel.unset( 'jBox', { silent: true } );
			} );

			/*
			 * The main content of our form is called the formContent.
			 * In this next section, we check to see if any add-ons want to modify that contents before we save.
			 * If there aren't any filters found, we default to the field collection.
			 * 
			 */
			
			var formContentData = nfRadio.channel( 'settings' ).request( 'get:setting', 'formContentData' );
			/*
			 * As of version 3.0, 'fieldContentsData' has deprecated in favour of 'formContentData'.
			 * If we don't have this setting, then we check for this deprecated value.
			 * 
			 * Set our fieldContentsData to our form setting 'fieldContentsData'
			 *
			 * TODO: Remove this backwards compatibility eventually.
			 */
			if ( ! formContentData ) {
				formContentData = nfRadio.channel( 'settings' ).request( 'get:setting', 'fieldContentsData' );
			}

			var formContentSaveDataFilters = nfRadio.channel( 'formContent' ).request( 'get:saveFilters' );
						
			/* 
			* Get our first filter, this will be the one with the highest priority.
			*/
			var sortedArray = _.without( formContentSaveDataFilters, undefined );
			var callback = _.first( sortedArray );
			/*
			 * Set our formContentData to the callback specified in the filter, passing our current formContentData.
			 */
			formContentData = callback( formContentData );
			
			if ( 'publish' == action && formModel.get( 'show_publish_options' ) ) {
				nfRadio.channel( 'app' ).request( 'open:drawer', 'newForm' );
				var builderEl = nfRadio.channel( 'app' ).request( 'get:builderEl' );
				jQuery( builderEl ).addClass( 'disable-main' );
				return false;
			}

			// Get our form data
			var formData = nfRadio.channel( 'app' ).request( 'get:formModel' );

			// Turn our formData model into an object
			var data = JSON.parse( JSON.stringify( formData ) );
			data.settings.formContentData = formContentData;

			/**
			 * Prepare fields for submission.
			 */
			
			// Get the field IDs that we've deleted.
			var removedIDs = formData.get( 'fields' ).removedIDs;

			/*
			 * data.fields is an array of objects like:
			 * field.label = blah
			 * field.label_pos = blah
			 * etc.
			 *
			 * And we need that format to be:
			 * field.settings.label = blah
			 * field.settings.label_pos = blah
			 *
			 * So, we loop through our fields and create a field.settings object.
			 */
			_.each( data.fields, function( field ) {
				var id = field.id;
				// We dont' want to update id or parent_id
				delete field.id;
				delete field.parent_id;
				var settings = {};
				// Loop through all the attributes of our fields
				for (var prop in field) {
				    if ( field.hasOwnProperty( prop ) ) {
				    	// If our field property isn't null, then...
                        if ( null !== field[ prop ] ) {
                            // Set our settings.prop value.
                            settings[prop] = field[prop];
                        }
                        // Delete the property from the field.
                        delete field[ prop ];
                    }
				}

				for( var setting in settings ){
					if( null === settings[ setting ] ) {
						delete settings[setting];
					}
				}

				// Update our field object.
				field.settings = settings;
				field.id = id;
			} );

			// Set our deleted_fields object so that we can know which fields were removed.
			data.deleted_fields = removedIDs;

			/**
			 * Prepare actions for submission.
			 */
			
			// Get the action IDs that we've deleted.
			var removedIDs = formData.get( 'actions' ).removedIDs;

			/*
			 * data.actions is an array of objects like:
			 * action.label = blah
			 * action.label_pos = blah
			 * etc.
			 *
			 * And we need that format to be:
			 * action.settings.label = blah
			 * action.settings.label_pos = blah
			 *
			 * So, we loop through our actions and create a field.settings object.
			 */
			_.each( data.actions, function( action ) {
				var id = action.id;
				// We dont' want to update id or parent_id
				delete action.id;
				delete action.parent_id;
				var settings = {};
				// Loop through all the attributes of our actions
				for (var prop in action) {
				    if ( action.hasOwnProperty( prop ) ) {
				    	//Removing null values
					    if( null !== action[ prop ] ) {
						    // Set our settings.prop value.
						    settings[ prop ] = action[ prop ];
					    }
				        // Delete the property from the action.
				        delete action[ prop ];
				    }
				}
				// Update our action object.
				action.settings = settings;
				action.id = id;
			} );

			for ( var setting in data.settings ) {
				if ( null === data.settings[ setting ] ) {
					delete data.settings[ setting ];
				}
			}

			// Set our deleted_actions object so that we can know which actions were removed.
			data.deleted_actions = removedIDs;

			// Turn our object into a JSON string.
			data = JSON.stringify( data );

			// Run anything that needs to happen before we update.
			nfRadio.channel( 'app' ).trigger( 'before:updateDB', data );

			if ( 'publish' == action ) {
				nfRadio.channel( 'app' ).request( 'update:setting', 'loading', true );
				nfRadio.channel( 'app' ).trigger( 'change:loading' );	

				// If we're on mobile, show a notice that we're publishing
				if ( nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
					nfRadio.channel( 'notices' ).request( 'add', 'publishing', 'Your Changes Are Being Published', { autoClose: false } );
				}
			}

			if ( 'nf_save_form' === jsAction ) {
				// if the form string is long than this, chunk it
				var chunk_size = 100000;
				var data_chunks = [];

				// Let's chunk this
				if( chunk_size < data.length ) {
					data_chunks = data.match(new RegExp('.{1,' + chunk_size + '}', 'g'));
				}
				// if we have chunks send them via the step processor
				if( 1 < data_chunks.length ) {
					// this function will make the ajax call for chunks
					this.saveChunkedForm(
						data_chunks,
						0,
						'nf_batch_process',
						action,
						formModel.get('id'),
						true
					);
				} else {
					// otherwise send it the regular way.
					var context = this;
					var responseData = null;

					jQuery.post( ajaxurl,
						{
							action: jsAction,
							form: data,
							security: nfAdmin.ajaxNonce
						},
						function( response ) {
							responseData = response;
							context.handleFinalResponse( responseData, action );
						}
					).fail( function( xhr, status, error ) {
						context.handleFinalFailure( xhr, status, error, action )
					} );
				}
			} else if ( 'nf_preview_update' === jsAction ) {
				var context = this;
				var responseData = null;
				jQuery.post( ajaxurl,
					{
						action: jsAction,
						form: data,
						security: nfAdmin.ajaxNonce
					},
					function( response ) {
						responseData = response;
						context.handleFinalResponse( responseData, action );
					}
				).fail( function( xhr, status, error ) {
					context.handleFinalFailure( xhr, status, error, action )
				} );
			}
		},
		/**
		 * Function to recursively send chunks until all chunks have been sent
		 *
		 * @param chunks
		 * @param currentIndex
		 * @param currentChunk
		 * @param jsAction
		 * @param action
		 */
		saveChunkedForm: function( chunks, currentChunk, jsAction, action, formId, new_publish ) {
			var total_chunks = chunks.length;
			var postObj = {
				action: jsAction,
				batch_type: 'chunked_publish',
				data: {
					new_publish: new_publish,
					chunk_total: total_chunks,
					chunk_current: currentChunk,
					chunk: chunks[ currentChunk ],
					form_id: formId
				},
				security: nfAdmin.batchNonce
			};

			var that = this;
			jQuery.post( ajaxurl, postObj )
				.then( function ( response ) {
					try {
						var res = JSON.parse(response);
						if ( 'success' === res.last_request && ! res.batch_complete) {
							console.log('Chunk ' + currentChunk + ' processed');

							// send the next chunk
							that.saveChunkedForm(chunks, res.requesting, jsAction, action, formId, false);
						} else if ( res.batch_complete ) {
							/**
							 * We need to respond with data to make the
							 * publish button return to gray
                             */
							that.handleFinalResponse(response, action);
						}
					} catch ( exception ) {
						console.log( 'There was an error in parsing the' +
							' response');
						console.log( exception );
					}
				}
				).fail( function( xhr, status, error ) {
					console.log( 'There was an error sending form data' );
					console.log( error );
					that.handleFinalFailure( xhr, status, error, action );
				});
		},

		handleFinalResponse: function( response, action ) {
			try {
				response = JSON.parse( response );
				response.action = action;

				// Run anything that needs to happen after we update.
				nfRadio.channel( 'app' ).trigger( 'response:updateDB', response );
				if ( ! nfRadio.channel( 'app' ).request( 'is:mobile' ) && 'preview' == action ) {
					// nfRadio.channel( 'notices' ).request( 'add', 'previewUpdate', 'Preview Updated'	);
				}
			} catch( exception ) {
				console.log( 'Something went wrong!' );
				console.log( exception );
			}
		},

		handleFinalFailure: function( xhr, status, error, action ) {
			// For previews, only log to the console.
			if( 'preview' == action ) {
				console.log( error );
				return;
			}
			// @todo Convert alert to jBox Modal.
			alert(xhr.status + ' ' + error + '\r\n' + 'An error on the server caused your form not to publish.\r\nPlease contact Ninja Forms Support with your PHP Error Logs.\r\nhttps://ninjaforms.com/contact');
		},

		defaultSaveFilter: function( formContentData ) {
			return formContentData.pluck( 'key' );
		}

	});

	return controller;
} );

/**
 * Model that represents our form data.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/formModel',[], function() {
	var model = Backbone.Model.extend( {
		initialize: function() {
			if ( ! jQuery.isNumeric( this.get( 'id' ) ) ) {
				this.set( 'show_publish_options', true, { silent: true } );
			} else {
				this.set( 'show_publish_options', false, { silent: true } );
			}
		}
	} );
	
	return model;
} );
/**
 * Stores our form data and responds to requests for it.
 * Form data stores fields, actions, and settings.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/formData',['models/app/formModel'], function( formModel) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Instantiate Form Model
			this.model = new formModel( { id: preloadedFormData.id } );
			// Set our field collection
			this.model.set( 'fields', nfRadio.channel( 'fields' ).request( 'get:collection' ) );
			// Set our actions collection
			this.model.set( 'actions', nfRadio.channel( 'actions' ).request( 'get:collection' ) );
			// Set our settings collection
			this.model.set( 'settings', nfRadio.channel( 'settings' ).request( 'get:settings' ) );
			// Respond to requests for form data.
			nfRadio.channel( 'app' ).reply( 'get:formModel', this.getFormModel, this );
		},

		/**
		 * Return form data model.
		 * 
		 * @since  3.0
		 * @return backbone.model
		 */
		getFormModel: function() {
			return this.model;
		}

	});

	return controller;
} );
/**
 * Handles changing our preview link when we change the 'clean' state of our app.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/previewLink',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen for events that would change our preview link
			this.listenTo( nfRadio.channel( 'app' ), 'before:sendChanges', this.disablePreview, this );
			this.listenTo( nfRadio.channel( 'app' ), 'response:sendChanges', this.enablePreview, this );
			this.listenTo( nfRadio.channel( 'app' ), 'change:clean', this.changePreviewNicename, this );
		},

		/**
		 * Disable our preview link before we send data to update our preview.
		 * 
		 * @since  3.0
		 * @return void
		 */
		disablePreview: function() {
			// Get our preview domain
			var appDomains = nfRadio.channel( 'app' ).request( 'get:domainCollection' );
			var preview = appDomains.get( 'preview' );
			// Set disabled to true. This will trigger the preview link view to redraw.
			preview.set( 'disabled', true );
		},

		/**
		 * Change the preview link text from "Preview Form" to "Preview Changes" or vice-versa
		 * 
		 * @since  3.0
		 * @param  boolean 	clean app data state
		 * @return void
		 */
		changePreviewNicename: function( clean ) {
			// Get our preview domain
			var appDomains = nfRadio.channel( 'app' ).request( 'get:domainCollection' );
			var preview = appDomains.get( 'preview' );

			// If we have unsaved changes, set our text to 'changes' otherwise, set it to 'form'
			if ( ! clean ) {
				var nicename = 'Preview Changes';
			} else {
				var nicename = 'Preview Form';
			}

			preview.set( 'nicename', nicename );
		},

		/**
		 * Enable our preview button.
		 * This is triggered when we get a response from our preview update.
		 * 
		 * @since  3.0
		 * @return void
		 */
		enablePreview: function() {
			// Get our preview domain
			var appDomains = nfRadio.channel( 'app' ).request( 'get:domainCollection' );
			var preview = appDomains.get( 'preview' );
			// Set disabled to false. This will trigger the preview link view to redraw.
			preview.set( 'disabled', false );
		}

	});

	return controller;
} );
/**
 * Listens to our app channel for requests to change the current domain.
 *
 * The app menu and the main submenu both contain clickable links that change the current domain.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/menuButtons',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'app' ), 'click:publish', this.publish );
			this.listenTo( nfRadio.channel( 'app' ), 'click:viewChanges', this.viewChanges );
			this.listenTo( nfRadio.channel( 'app' ), 'click:publicLink', this.publicLink );
		},

		publish: function() {
			nfRadio.channel( 'app' ).request( 'update:db', 'publish' );
		},

		viewChanges: function() {
			var changeCollection = nfRadio.channel( 'changes' ).request( 'get:collection' );
			nfRadio.channel( 'app' ).request( 'open:drawer', 'viewChanges', { collection: changeCollection } );
		},

		publicLink: function() {
			nfRadio.channel( 'app' ).request( 'open:drawer', 'publicLink' );
		}

	});

	return controller;
} );
/**
 * Model that represents our change data.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/changeModel',[], function() {
	var model = Backbone.Model.extend( {
		defaults: {
			disabled: false
		}
	} );
	
	return model;
} );
/**
 * Holds all of our change models.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/changeCollection',['models/app/changeModel'], function( domainModel ) {
	var collection = Backbone.Collection.extend( {
		model: domainModel,

		comparator: function( model ) {
			var id = parseInt( model.cid.replace( 'c', '' ) );
			return -id;
		}
	} );
	return collection;
} );
/**
 * Track settings changes across our app.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/trackChanges',['models/app/changeCollection', 'models/app/changeModel'], function( changeCollection, ChangeModel ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.collection = new changeCollection();
			// Respond to any requests to add a change directly.
			nfRadio.channel( 'changes' ).reply( 'register:change', this.registerChange, this );
			// Respond to requests for the change collection
			nfRadio.channel( 'changes' ).reply( 'get:collection', this.getCollection, this );
			// Listen for changes in our clean state. If it goes to clean, clear our collection.
			this.listenTo( nfRadio.channel( 'app' ), 'change:clean', this.maybeResetCollection );
		},

		registerChange: function( action, model, changes, label, data ) {
			var data = typeof data !== 'undefined' ? data : {};
			if ( 'undefined' == typeof label.dashicon ) {
				label.dashicon = 'admin-generic';
			}
			var changeModel = new ChangeModel({
				action: action,
				model: model,
				changes: changes,
				label: label,
				data: data		
			} );
			this.collection.add( changeModel );

			//loop through repeater fields to reset active state if needed
			nfRadio.channel( 'fields-repeater' ).trigger( 'clearEditActive', model );
			
			return changeModel;
		},

		getCollection: function() {
			return this.collection;
		},

		maybeResetCollection: function( clean ) {
			if ( clean ) {
				this.collection.reset();
			}
		}

	});

	return controller;
} );
define( 'controllers/app/undoChanges',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'drawer' ), 'click:undoChanges', this.undoChanges, this );
			this.listenTo( nfRadio.channel( 'drawer' ), 'click:undoSingle', this.undoSingle, this );
		},

		undoChanges: function() {
			var changeCollection = nfRadio.channel( 'changes' ).request( 'get:collection' );
			changeCollection.sort();
			var that = this;
			_.each( changeCollection.models, function( change ) {
				that.undoSingle( change, true );
			} );
			changeCollection.reset();
			// Update preview.
			nfRadio.channel( 'app' ).request( 'update:db' );			
			nfRadio.channel( 'app' ).request( 'update:setting', 'clean', true );
			nfRadio.channel( 'app' ).request( 'close:drawer' );
            this.dispatchClick();
		},

		undoSingle: function( change, undoAll ) {
			nfRadio.channel( 'changes' ).request( 'undo:' + change.get( 'action' ), change, undoAll );
            this.dispatchClick();
		},
        
        dispatchClick: function() {
            // If we already have a cookie, exit.
            if ( document.cookie.includes( 'nf_undo' ) ) return;
            // Otherwise, prepare our cookie.
            var cname = "nf_undo";
            var d = new Date();
            // Set expiration at 1 week.
            d.setTime( d.getTime() + ( 7*24*60*60*1000 ) );
            var expires = "expires="+ d.toUTCString();
            // Bake the cookie.
            document.cookie = cname + "=1;" + expires + ";path=/";
            var data = {
                action: 'nf_undo_click',
                security: nfAdmin.ajaxNonce
            }
            // Make our AJAX call.
            jQuery.post( ajaxurl, data );
        }

	});

	return controller;
} );
/**
 * Listens for our update:db response and replaces tmp ids with new ids if we were performing the publish action.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/publishResponse',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen to our app channel for the updateDB response.
			this.listenTo( nfRadio.channel( 'app' ), 'response:updateDB', this.publishResponse );
		},

		publishResponse: function( response ) {
			// If we aren't performing a publish action, bail.
			if ( 'publish' !== response.action ) {
				return false;
			}
			
			// Check to see if we have any new ids. 
			if ( 'undefined' != typeof response.data.new_ids ) {

				// If we have any new fields, update their models with the new id.
				if ( 'undefined' != typeof response.data.new_ids.fields ) {
					_.each( response.data.new_ids.fields, function( newID, oldID ) {
						var field = nfRadio.channel( 'fields' ).request( 'get:field', oldID );
						if ( field ) {
							field.set( 'id', newID );
						} else {
							field = nfRadio.channel( 'fields-repeater' ).request( 'get:childField', oldID, null, newID );
							field.set( 'id', newID );
						}
					} );
				}

				// If we have any new actions, update their models with the new id.
				if ( 'undefined' != typeof response.data.new_ids.actions ) {
					_.each( response.data.new_ids.actions, function( newID, oldID ) {
						var action = nfRadio.channel( 'actions' ).request( 'get:action', oldID );
						if ( action ) {
							action.set( 'id', newID );
						}
					} );
				}

				// If we have a new form id, update the model with the new id.
				if ( 'undefined' != typeof response.data.new_ids.forms ) {
					_.each( response.data.new_ids.forms, function( newID, oldID ) {
						var formModel = nfRadio.channel( 'app' ).request( 'get:formModel' );
						formModel.set( 'id', newID );
						history.replaceState( '', '', 'admin.php?page=ninja-forms&form_id=' + newID );
					} );
				}
			}

			nfRadio.channel( 'app' ).request( 'update:setting', 'loading', false );
			nfRadio.channel( 'app' ).trigger( 'change:loading' );

			// If we're on mobile, show a notice that we're publishing
			if ( nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
				nfRadio.channel( 'notices' ).request( 'close', 'publishing' );
			}
			// Add a notice that we've published.
//			nfRadio.channel( 'notices' ).request( 'add', 'published', 'Changes Published' );
			nfRadio.channel( 'app' ).trigger( 'app:published', response );

			// Mark our app as clean. This will disable the publish button and fire anything else that cares about the state.
			nfRadio.channel( 'app' ).request( 'update:setting', 'clean', true );
		}
		
	});

	return controller;
} );
/**
 * Listens to our app channel for requests to change the current domain.
 *
 * The app menu and the main submenu both contain clickable links that change the current domain.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/changeDomain',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen for both menu and submenu clicks.
			this.listenTo( nfRadio.channel( 'app' ), 'click:menu', this.changeAppDomain );
			// Reply to specific requests to change the domain
			nfRadio.channel( 'app' ).reply( 'change:currentDomain', this.changeAppDomain, this );

			// Reply to requests to prevent our drawer from closing
			nfRadio.channel( 'app' ).reply( 'prevent:changeDomain', this.preventChange, this );
			// Reply to requests to enable drawer closing
			nfRadio.channel( 'app' ).reply( 'enable:changeDomain', this.enableChange, this );

			/*
			 * Object that holds our array of 'prevent change' values.
			 * We use an array so that registered requests can unregister and not affect each other.
			 */
			this.objPreventChange = {};
		},

		changeAppDomain: function( e, model ) {
			/*
			 * If we have disabled movement between domains, return false.
			 */
			if ( this.maybePreventChange() ) {
				return false;
			}

			/*
			 * If we are passed a model, use that model.
			 * Otherwise, get the domain from the event target data.
			 */ 
			if ( 'undefined' == typeof model ) {
				var domainID = jQuery( e.target ).data( 'domain' );
				var model = nfRadio.channel( 'app' ).request( 'get:domainModel', domainID );
			}
			// If a drawer is open, close it.
			if ( nfRadio.channel( 'app' ).request( 'get:currentDrawer' ) ) {
				nfRadio.channel( 'app' ).request( 'close:drawer' );
			}
			/*
			 * If we aren't dealing with an external url (such as preview), update our app data
			 * and trigger a radio message saying we've changed the domain.
			 */ 
			if ( 0 == model.get( 'url' ).length ) {
				var mainEl = nfRadio.channel( 'app' ).request( 'get:mainEl' );
				nfRadio.channel( 'app' ).request( 'update:currentDomain', model );
				jQuery( mainEl ).scrollTop( 0 );				
				nfRadio.channel( 'app' ).trigger( 'change:currentDomain', model );
			}
		},

		/**
         * Check to see if anything has registered a key to prevent changing the domain.
         * 
         * @since  3.0
         * @return boolean
         */
        maybePreventChange: function() {
        	if ( 0 == Object.keys( this.objPreventChange ).length ) {
        		return false;
        	} else {
        		return true;
        	}
        },

        /**
         * Register a key to prevent changing the domain.
         * 
         * @since  3.0
         * @param  string 	key unique id for our 'prevent change domain' setting.
         * @return void
         */
        preventChange: function( key ) {
        	this.objPreventChange[ key ] = true;
        },

        /**
         * Remove a previously registered key that is preventing our domain from changing.
         * 
         * @since  3.0
         * @param  string 	key unique id for our 'prevent change domain' setting.
         * @return void
         */
        enableChange: function( key ) {
        	delete this.objPreventChange[ key ];
        },

	});

	return controller;
} );
/**
 * Modify the user's browser history when they click on a domain
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/pushstate',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'app' ), 'change:currentDomain', this.changePushState );
		},

		changePushState: function() {
			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
			history.pushState( null, null, window.location.href + '&domain=' + currentDomain.get( 'id' ) );
			var reExp = /domain=\\d+/;
			var url = window.location.toString();
			var newUrl = url.replace( reExp, '' );
			console.log( newUrl );
		}

	});

	return controller;
} );
/**
 * Handles our hotkey execution. Needs to be cleaned up and made more programmatic.
 * 
 * Our hotkeys are defined by the domain that we're currently viewing. In each domain's model, there is a hotkey object.
 * 
 * Currently too much hotkey data is hard-coded here.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/hotkeys',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// When we change our domain, change the hotkeys to those within that object.
			this.listenTo( nfRadio.channel( 'main' ), 'render:main', this.changeHotkeys );
			this.listenTo( nfRadio.channel( 'drawer' ), 'opened', this.changeHotkeys );
			this.listenTo( nfRadio.channel( 'drawer' ), 'render:settingGroup', this.changeHotkeys );
			// Currently, these are the functions that run when the new field or new action hotkey is pressed.
			// TODO: move these into a config module or into something more programmatic and scalable.
			this.listenTo( nfRadio.channel( 'hotkeys' ), 'add:newField', this.addNewField );
			this.listenTo( nfRadio.channel( 'hotkeys' ), 'add:newAction', this.addNewAction );
			// Same as above, these functions need to be moved into a more modular/programmatic solution.
			this.listenTo( nfRadio.channel( 'hotkeys' ), 'changeDomain:fields', this.changeDomainFields );
			this.listenTo( nfRadio.channel( 'hotkeys' ), 'changeDomain:actions', this.changeDomainActions );
			this.listenTo( nfRadio.channel( 'hotkeys' ), 'changeDomain:settings', this.changeDomainSettings );
			this.listenTo( nfRadio.channel( 'hotkeys' ), 'close:drawer', this.closeDrawer );
		},

		changeHotkeys: function() {
			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
			jQuery( document ).off( '.nfDomainHotkeys' );
			jQuery( 'input' ).off( '.nfDomainHotkeys' );
			if ( currentDomain.get( 'hotkeys' ) ) {
				jQuery.each( currentDomain.get( 'hotkeys' ), function( hotkey, msg ) {
					jQuery( document ).on( 'keydown.nfDomainHotkeys', null, hotkey, function( e ) {
						nfRadio.channel( 'hotkeys' ).trigger( msg, e );
					} );
					jQuery( 'input' ).on( 'keydown.nfDomainHotkeys', null, hotkey, function( e ) {
						nfRadio.channel( 'hotkeys' ).trigger( msg, e );
					} );
					jQuery( 'textarea' ).on( 'keydown.nfDomainHotkeys', null, hotkey, function( e ) {
						nfRadio.channel( 'hotkeys' ).trigger( msg, e );
					} );
				} );
			}
		},

		addNewField: function() {
			if ( 'addField' != nfRadio.channel( 'app' ).request( 'get:currentDrawer' ) ) {
				nfRadio.channel( 'app' ).request( 'open:drawer', 'addField' );
			} else {
				nfRadio.channel( 'app' ).request( 'close:drawer' );
			}
			
		},

		addNewAction: function() {
			if ( 'addAction' != nfRadio.channel( 'app' ).request( 'get:currentDrawer' ) ) {
				nfRadio.channel( 'app' ).request( 'open:drawer', 'addAction' );
			} else {
				nfRadio.channel( 'app' ).request( 'close:drawer' );
			}
		},

		changeDomainFields: function() {
			var appDomainCollection = nfRadio.channel( 'app' ).request( 'get:domainCollection' );
			var fieldsDomain = appDomainCollection.get( 'fields' );
			nfRadio.channel( 'app' ).request( 'change:currentDomain', {}, fieldsDomain );
		},

		changeDomainActions: function() {
			var appDomainCollection = nfRadio.channel( 'app' ).request( 'get:domainCollection' );
			var actionsDomain = appDomainCollection.get( 'actions' );
			nfRadio.channel( 'app' ).request( 'change:currentDomain', {}, actionsDomain );
		},

		changeDomainSettings: function() {
			var appDomainCollection = nfRadio.channel( 'app' ).request( 'get:domainCollection' );
			var settingsDomain = appDomainCollection.get( 'settings' );
			nfRadio.channel( 'app' ).request( 'change:currentDomain', {}, settingsDomain );
		},

		closeDrawer: function() {
			nfRadio.channel( 'app' ).request( 'close:drawer' );
		}

	});

	return controller;
} );
/**
 * Change the clean state of our app when settings are changed.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/cleanState',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			/*
			 * Set an array of field model attributes to ignore.
			 * This list will be filtered just before we ignore anything.
			 */ 
			this.ignoreAttributes = [
				'editActive'
			];

			this.listenTo( nfRadio.channel( 'app' ), 'update:setting', this.setAppClean );
		},

		setAppClean: function( model ) {
			for( var attr in model.changedAttributes() ) {
				var changedAttr = attr;
				var after = model.changedAttributes()[ attr ];
			}

			var ignoreAttributes = nfRadio.channel( 'undo-' + model.get( 'type' ) ).request( 'ignore:attributes', this.ignoreAttributes ) || this.ignoreAttributes;
			
			if ( -1 != this.ignoreAttributes.indexOf( attr ) ) {
				return false;
			}
			nfRadio.channel( 'app' ).request( 'update:setting', 'clean', false );

			//loop through repeater fields to reset active state if needed
			nfRadio.channel( 'fields-repeater' ).trigger( 'clearEditActive', model );
		}

	});

	return controller;
} );
/**
 * All of the core undo functions. Listens on the 'changes' channel for an undo request.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/coreUndo',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			nfRadio.channel( 'changes' ).reply( 'undo:changeSetting', this.undoChangeSetting, this );
			nfRadio.channel( 'changes' ).reply( 'undo:addObject', this.undoAddObject, this );
			nfRadio.channel( 'changes' ).reply( 'undo:removeObject', this.undoRemoveObject, this );
			nfRadio.channel( 'changes' ).reply( 'undo:duplicateObject', this.undoDuplicateObject, this );

			nfRadio.channel( 'changes' ).reply( 'undo:sortFields', this.undoSortFields, this );
			nfRadio.channel( 'changes' ).reply( 'undo:addListOption', this.undoAddListOption, this );
			nfRadio.channel( 'changes' ).reply( 'undo:removeListOption', this.undoRemoveListOption, this );
			nfRadio.channel( 'changes' ).reply( 'undo:sortListOptions', this.undoSortListOptions, this );
		},

		/**
		 * Undo settings that have been changed.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	change 	model of our change
		 * @param  boolean 			undoAll are we in the middle of an undo all action?
		 * @return void
		 */
		undoChangeSetting: function( change, undoAll ) {
			var fieldModel = change.get( 'model' );
			var changes = change.get( 'changes' );
			var attr = changes.attr;
			var before = changes.before;
			fieldModel.set( attr, before );
			this.maybeRemoveChange( change, undoAll );
		},

		/**
		 * Undo adding a field or an action.
		 * Loops through our change collection and removes any change models based upon the one we're removing.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	change 	model of our change
		 * @param  boolean 			undoAll are we in the middle of an undo all action?
		 * @return void
		 */
		undoAddObject: function( change, undoAll ) {
			var objectModel = change.get( 'model' );
			var collection = change.get( 'data' ).collection;

			if ( 'undefined' != typeof collection.newIDs ) {
				delete collection.newIDs[ objectModel.get( 'id' ) ];
			}
						
			if ( ! undoAll ) {
				var changeCollection = nfRadio.channel( 'changes' ).request( 'get:collection' );
				var results = changeCollection.where( { model: objectModel } );

				_.each( results, function( model ) {
					if ( model !== change ) {
						changeCollection.remove( model );
					}
				} );				
			}
			
			collection.remove( objectModel );
			this.maybeRemoveChange( change, undoAll );
		},		

		/**
		 * Undo adding a field or an action.
		 * Loops through our change collection and removes any change models based upon the one we're removing.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	change 	model of our change
		 * @param  boolean 			undoAll are we in the middle of an undo all action?
		 * @return void
		 */
		undoDuplicateObject: function( change, undoAll ) {
			var objectModel = change.get( 'model' );
			var objectCollection = change.get( 'data' ).collection;

			if ( ! undoAll ) {
				var changeCollection = nfRadio.channel( 'changes' ).request( 'get:collection' );
				var results = changeCollection.where( { model: objectModel } );

				_.each( results, function( model ) {
					if ( model !== change ) {
						changeCollection.remove( model );
					}
				} );
			}

			objectCollection.remove( objectModel );
			this.maybeRemoveChange( change, undoAll );
		},

		/**
		 * Undo removing a field or an action.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	change 	model of our change
		 * @param  boolean 			undoAll are we in the middle of an undo all action?
		 * @return void
		 */
		undoRemoveObject: function( change, undoAll ) {
			var dataModel = change.get( 'model' );
			var collection = change.get( 'data' ).collection;

			nfRadio.channel( dataModel.get( 'objectDomain' ) ).request( 'add', dataModel );

			delete collection.removedIDs[ dataModel.get( 'id' ) ];
			
			if ( ! undoAll ) {
				var changeCollection = nfRadio.channel( 'changes' ).request( 'get:collection' );
				var results = changeCollection.where( { model: dataModel } );

				_.each( results, function( model ) {
					if ( model !== change ) {
						model.set( 'disabled', false );
					}
				} );				
			}

			// Trigger a reset on our field collection so that our view re-renders
			collection.trigger( 'reset', collection );

			this.maybeRemoveChange( change, undoAll );
		},

		/**
		 * Undo field sorting.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	change 	model of our change
		 * @param  boolean 			undoAll are we in the middle of an undo all action?
		 * @return void
		 */
		undoSortFields: function( change, undoAll ) {
			var data = change.get( 'data' );
			var fields = data.fields;

			var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
			_.each( fields, function( changeModel ) {
				var before = changeModel.before;
				var fieldModel = changeModel.model;
				fieldModel.set( 'order', before );
				// console.log( 'set ' + fieldModel.get( 'label' ) + ' to ' + before );
			} );
			// console.log( fieldCollection.where( { label: 'Name' } ) );
			// console.log( fieldCollection.where( { label: 'Email' } ) );


			fieldCollection.sort();
			this.maybeRemoveChange( change, undoAll );
		},

		undoAddListOption: function( change, undoAll ) {
			var model = change.get( 'model' );

			if ( ! undoAll ) {
				var changeCollection = nfRadio.channel( 'changes' ).request( 'get:collection' );
				var results = changeCollection.where( { model: model } );

				_.each( results, function( changeModel ) {
					if ( changeModel !== change ) {
						changeCollection.remove( changeModel );
					}
				} );				
			}

			model.collection.remove( model );
			this.maybeRemoveChange( change, undoAll );
		},

		undoRemoveListOption: function( change, undoAll ) {
			var model = change.get( 'model' );
			var collection = change.get( 'data' ).collection;
			collection.add( model );

			if ( ! undoAll ) {
				var changeCollection = nfRadio.channel( 'changes' ).request( 'get:collection' );
				var results = changeCollection.where( { model: model } );

				_.each( results, function( model ) {
					if ( model !== change ) {
						model.set( 'disabled', false );
					}
				} );				
			}

			this.maybeRemoveChange( change, undoAll );
		},

		undoSortListOptions: function( change, undoAll ) {
			var data = change.get( 'data' );
			var collection = data.collection;
			
			var objModels = data.objModels;

			_.each( objModels, function( changeModel ) {
				var before = changeModel.before;
				var optionModel = changeModel.model;
				optionModel.set( 'order', before );
			} );				


			collection.sort();
			this.maybeRemoveChange( change, undoAll );
		},

		/**
		 * If our undo action was requested to 'remove' the change from the collection, remove it.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	change 	model of our change
		 * @param  boolean 			remove 	should we remove this item from our change collection
		 * @return void
		 */
		maybeRemoveChange: function( change, undoAll ) {			
			var undoAll = typeof undoAll !== 'undefined' ? undoAll : false;
			if ( ! undoAll ) {
				// Update preview.
				nfRadio.channel( 'app' ).request( 'update:db' );
				var changeCollection = nfRadio.channel( 'changes' ).request( 'get:collection' );
				changeCollection.remove( change );
				if ( 0 == changeCollection.length ) {
					nfRadio.channel( 'app' ).request( 'update:setting', 'clean', true );
					nfRadio.channel( 'app' ).request( 'close:drawer' );
				}
			}
		}

	});

	return controller;
} );
/**
 * Returns a clone of a backbone model with all the attributes looped through so that collections contained within are propely cloned.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/cloneModelDeep',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			nfRadio.channel( 'app' ).reply( 'clone:modelDeep', this.cloneModelDeep, this );
		},

		cloneModelDeep: function( model ) {
			// Temporary value used to store any new collections.
			var replace = {};
			// Loop over every model attribute and if we find a collection, clone each model and instantiate a new collection.
			_.each( model.attributes, function( val, key ) {
				if( val instanceof Backbone.Collection ) { // Is this a backbone collection?
					var clonedCollection = nfRadio.channel( 'app' ).request( 'clone:collectionDeep', val );
					replace[ key ] = clonedCollection;
				} else if ( val instanceof Backbone.Model ) { // Is this a backbone model?
					replace[ key ] = this.cloneModelDeep( val );
				}
			}, this );

			// Clone our original model
			var newModel = model.clone();
			// Overwrite any collections we created above.
			_.each( replace, function( val, key ) {
				newModel.set( key, val );
			} );

			return newModel;
		}
	});

	return controller;
} );
/**
 * Returns the appropriate child view for our settings drawer.
 *
 * This enables settings types to register custom childviews for their settings.
 * The option-repeater setting for the list field is an example.
 * 
 * @package Ninja Forms builder
 * @subpackage App - Edit Settings Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/getSettingChildView',['views/app/drawer/itemSetting'], function( itemSettingView ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Respond to requests for field settings child views.
			nfRadio.channel( 'app' ).reply( 'get:settingChildView', this.getSettingChildView, this );
		},

		/**
		 * Return the appropriate child setting view.
		 *
		 * @since  3.0
		 * @param  backbone.model	model 	Field setting
		 * @return backbone.view
		 */
		getSettingChildView: function( model ) {
			// Get our setting type.
			var type = model.get( 'type' );
			// Request a setting childview from our setting type channel. (Setting type, not field type)
			var settingChildView = nfRadio.channel( type ).request( 'get:settingChildView', model ) || itemSettingView;
			
			return settingChildView
		}

	});

	return controller;
} );
/**
 * Updates our model when the user changes a setting.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/changeSettingDefault',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Respond to requests to update settings.
			nfRadio.channel( 'app' ).reply( 'change:setting', this.changeSetting, this );

			// Listen on our app channel for the change setting event. Fired by the setting view.
			this.listenTo( nfRadio.channel( 'app' ), 'change:setting', this.changeSetting, this );
		},

		/**
		 * When we change our setting, update the model.
		 * 
		 * @since  3.0
		 * @param  Object 			e                event
		 * @param  backbone.model 	settingModel model that holds our field type settings info
		 * @param  backbone.model 	dataModel       model that holds our field settings
		 * @return void
		 */
		changeSetting: function( e, settingModel, dataModel, value ) {
			var name = settingModel.get( 'name' );
			var before = dataModel.get( name );
			var value = value || null;
			if ( ! value ) {
				// Sends out a request on the fields-type (fields-text, fields-checkbox, etc) channel to see if that field type needs to return a special value for saving.
				value = nfRadio.channel( settingModel.get( 'type' ) ).request( 'before:updateSetting', e, dataModel, name, settingModel );
			}

			if( 'undefined' == typeof value ){
			    value = jQuery( e.target ).val();
            }
			
			// Update our field model with the new setting value.
			dataModel.set( name, value, { settingModel: settingModel } );
			nfRadio.channel( 'setting-' + name ).trigger( 'after:updateSetting', dataModel, settingModel );
			// Register our setting change with our change tracker
			var after = value;
			
			var changes = {
				attr: name,
				before: before,
				after: after
			}

			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
			var currentDomainID = currentDomain.get( 'id' );

			var label = {
				object: dataModel.get( 'objectType' ),
				label: dataModel.get( 'label' ),
				change: 'Changed ' + settingModel.get( 'label' ) + ' from ' + before + ' to ' + after
			};

			nfRadio.channel( 'changes' ).request( 'register:change', 'changeSetting', dataModel, changes, label );
		}

	});

	return controller;
} );
define( 'views/app/drawer/typeSettingFieldset',['views/app/drawer/itemSetting'], function( itemSettingView ) {
	var view = Marionette.CompositeView.extend( {
		template: '#tmpl-nf-edit-setting-wrap',
		childView: itemSettingView,

		initialize: function( data ) {
			this.collection = this.model.get( 'settings' );
			this.childViewOptions = { dataModel: data.dataModel };
			this.dataModel = data.dataModel;
			var deps = this.model.get( 'deps' );
			if ( deps ) {
				// If we don't have a 'settings' property, this is a legacy depdency setup.
				if ( 'undefined' == typeof deps.settings ) {
					deps.settings = [];
					_.each(deps, function(dep, name){
						if( 'settings' !== name ) {
							deps.settings.push( { name: name, value: dep } );
						}
					});
					deps.match = 'all';
				}

				for (var i = deps.settings.length - 1; i >= 0; i--) {
					let name = deps.settings[i].name;
					this.dataModel.on( 'change:' + name, this.render, this );
				}
			}
			this.model.on( 'rerender', this.render, this );
		},

		onBeforeDestroy: function() {
			var deps = this.model.get( 'deps' );
			if ( deps ) {
				for (var i = deps.settings.length - 1; i >= 0; i--) {
					name = deps.settings[i].name;
					this.dataModel.off( 'change:' + name, this.render );
				}
			}
		},

		onBeforeRender: function() {
			nfRadio.channel( 'app' ).trigger( 'before:renderSetting', this.model, this.dataModel );
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'before:renderSetting', this.model, this.dataModel, this );
			nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'before:renderSetting', this.model, this.dataModel, this );
		},

		onRender: function() {
			/*
			 * Send out a radio message.
			 */
			nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'render:setting', this.model, this.dataModel, this );
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'render:setting', this.model, this.dataModel, this );
		},

		templateHelpers: function () {
			var that = this;
	    	return {
	    		renderVisible: function() {

					if(!nfAdmin.devMode){
						if('help' == this.name) return 'style="display:none;"';
						if('classes' == this.name) return 'style="display:none;"';
						if('input_limit_set' == this.name) return 'style="display:none;"';

						if('checkbox' == that.dataModel.get('type')){
							if('checkbox_values' == this.name) return 'style="display:none;"';
						}

						if('date' == that.dataModel.get('type')){
							if('year_range' == this.name) return 'style="display:none;"';
						}
					}

					return nfRadio.channel( 'settings' ).request( 'check:deps', this, that );
	    		},
	    		renderSetting: function(){
	    			var setting = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-edit-setting-' + this.type );
					return setting( this );
				},
				
				renderClasses: function() {
					var classes = '';
					if ( 'undefined' != typeof this.width ) {
						classes += this.width;
					} else {
						classes += ' one-half';
					}

					if ( this.error ) {
						classes += ' nf-error';
					}

					return classes;
				},

				renderError: function() {
					if ( this.error ) {
						return this.error;
					}
					return '';
				}
			}
		},

		attachHtml: function( collectionView, childView ) {
			jQuery( collectionView.el ).find( '.nf-field-sub-settings' ).append( childView.el );
		}
	} );

	return view;
} );
/**
 * Handles actions related to field settings that use a fieldset
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - Edit Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/fieldset',['views/app/drawer/typeSettingFieldset','models/app/settingCollection'], function( fieldsetView, settingCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			nfRadio.channel( 'fieldset' ).reply( 'get:settingChildView', this.getSettingChildView, this );
			// When a list type field is initialized, create an option collection.
			this.listenTo( nfRadio.channel( 'fieldset' ), 'init:settingModel', this.createSettingsCollection );
		},

		getSettingChildView: function( model ) {
			return fieldsetView;
		},

		/**
		 * Instantiate settings collection when a fieldset type is initialized.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	model 	field model being initialized
		 * @return void
		 */
		createSettingsCollection: function( model ) {
			model.set( 'settings', new settingCollection( model.get( 'settings' ) ) );
		},

	});

	return controller;
} );
/**
 * Handles actions related to our toggle field.
 * When we change the toggle, the setting value will be 'on' or ''.
 * We need to change this to 1 or 0.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - Edit Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/toggleSetting',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// We don't want the RTE setting to re-render when the value changes.
			nfRadio.channel( 'setting-type-toggle' ).reply( 'renderOnChange', function(){ return false } );

			// Respond to requests for field setting filtering.
			nfRadio.channel( 'toggle' ).reply( 'before:updateSetting', this.updateSetting, this );
		},

		/**
		 * Return either 1 or 0, depending upon the toggle position.
		 * 
		 * @since  3.0
		 * @param  Object 			e                event
		 * @param  backbone.model 	fieldModel       field model
		 * @param  string 			name             setting name
		 * @param  backbone.model 	settingTypeModel field type model
		 * @return int              1 or 0
		 */
		updateSetting: function( e, fieldModel, name, settingTypeModel ) {
			if ( jQuery( e.target ).prop( 'checked' ) ) {
				var value = 1;
			} else {
				var value = 0;
			}

			return value;
		}

	});

	return controller;
} );
/**
 * Handles actions related to our toggle field.
 * When we change the toggle, the setting value will be 'on' or ''.
 * We need to change this to 1 or 0.
 *
 * @package Ninja Forms builder
 * @subpackage Fields - Edit Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/buttonToggleSetting',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// We don't want the RTE setting to re-render when the value changes.
			nfRadio.channel( 'setting-type-button-toggle' ).reply( 'renderOnChange', function(){ return false; } );

			// Respond to requests for field setting filtering.
			nfRadio.channel( 'button-toggle' ).reply( 'before:updateSetting', this.updateSetting, this );
		},

		/**
		 * Return either 1 or 0, depending upon the toggle position.
		 *
		 * @since  3.0
		 * @param  Object 			e                event
		 * @param  backbone.model 	fieldModel       field model
		 * @param  string 			name             setting name
		 * @param  backbone.model 	settingTypeModel field type model
		 * @return int              1 or 0
		 */
		updateSetting: function( e, fieldModel, name, settingTypeModel ) {
			return e.target.value;
		}

	});

	return controller;
} );
/**
 * Handles actions related to number field settings.
 *
 * @package Ninja Forms builder
 * @subpackage Fields - Edit Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/numberSetting',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Respond to requests for field setting filtering.
			nfRadio.channel( 'number' ).reply( 'before:updateSetting', this.updateSetting, this );
		},

		/**
		 * Resets value if user enters value below min value or above max value
		 *
		 * @since  3.0
		 * @param  Object 			e                event
		 * @param  backbone.model 	fieldModel       field model
		 * @param  string 			name             setting name
		 * @param  backbone.model 	settingTypeModel field type model
		 * @return int              1 or 0
		 */
		updateSetting: function( e, fieldModel, name, settingTypeModel ) {
			var minVal = settingTypeModel.get( 'min_val' );
			var maxVal = settingTypeModel.get( 'max_val' );

			/*
			 * if we gave a min value set, revert to that if the user enters
			 * a lower number
			*/
			if( 'undefined' != typeof minVal && null !== minVal ){
				if ( e.target.value < minVal ) {
					fieldModel.set('value', minVal);
					e.target.value = minVal;
				}
			}
			/*
			 * if we gave a max value set, revert to that if the user enters
			 * a higher number
			*/
			if( 'undefined' != typeof maxVal && null !== maxVal ){
				if ( e.target.value > maxVal ) {
					fieldModel.set('value', maxVal);
					e.target.value = maxVal;
				}
			}

			return e.target.value;
		}

	});

	return controller;
} );

define( 'controllers/app/radioSetting',[], function() {
    var controller = Marionette.Object.extend({
        initialize: function () {
            // Respond to requests for field setting filtering.

            console.log( nfRadio.channel( 'radio' ) );
            nfRadio.channel('radio').reply( 'before:updateSetting', this.updateSetting, this);
        },


        updateSetting: function( e, fieldModel, name, settingTypeModel ) {
            console.log( 'test' );
        }
    });
    return controller;
} );
/**
 * Listens for clicks on our action item action buttons.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - Main Sortable
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/itemControls',[], function() {
	var controller = Marionette.Object.extend( {

		deleting: false, // block edit functionality while deleting field

		initialize: function() {
			// Listen for clicks to edit, delete, duplicate actions.
			this.listenTo( nfRadio.channel( 'app' ), 'click:edit', this.clickEdit );
			this.listenTo( nfRadio.channel( 'app' ), 'click:delete', this.maybeDelete );
			this.listenTo( nfRadio.channel( 'app' ), 'click:duplicate', this.clickDuplicate );

			// Listen for our drawer close and remove our active edit state
		},

		/**
		 * Open a drawer with our action model for editing settings.
		 * 
		 * @since  3.0
		 * @param  Object			e     	event
		 * @param  backbone.model 	model 	action model
		 * @return void
		 */
		clickEdit: function( e, model ) {
			// if we are deleting a field, we don't want to the edit drawer to open
			if( ! this.deleting ) {
				var currentDomain = nfRadio.channel('app').request('get:currentDomain');
				var currentDomainID = currentDomain.get('id');
				var type = nfRadio.channel(currentDomainID).request('get:type', model.get('type'));
				nfRadio.channel('app').request('open:drawer', 'editSettings', {
					model: model,
					groupCollection: type.get('settingGroups')
				});
				//loop through repeater fields to reset active state if needed
				nfRadio.channel( 'fields-repeater' ).trigger( 'clearEditActive', model );
			}
		},

		/**
		 * Let user know that all data will be lost before actually deleting
		 *
		 * @since  3.0
		 * @param  Object			e     	event
		 * @param  backbone.model 	model 	action model
		 * @return void
		 */
		maybeDelete: function( e, dataModel ) {
			// we set deleting to true, so the edit event doesn't open drawer
			this.deleting = true;
			var modelID = dataModel.get( 'id' );
			var modelType = dataModel.get( 'objectType' );

			// Build a lookup table for fields that we don't save
			var nonSaveFields = [ 'html', 'submit', 'hr',
				'recaptcha', 'spam', 'creditcard', 'creditcardcvc',
				'creditcardexpiration', 'creditcardfullname',
				'creditcardnumber', 'creditcardzip' ];

			/*
			* If this is a new field that hasn't been saved, then we don't
			 * need to check for data
			 */
			if( 'field' != modelType.toLowerCase() ) {
				this.clickDelete( e, dataModel );
			} else {
				/*
				* If the field has been saved, then we need to check for
				 * submission data for this field
				 */
				if( 'tmp' === modelID.toString().substring( 0, 3 )
					|| -1 != jQuery.inArray( dataModel.get( 'type' ), nonSaveFields ) ) {
					// not a saved field so proceed as normal
					this.clickDelete( e, dataModel );
				} else {
					// need the form id
					var formModel = Backbone.Radio.channel('app').request('get:formModel');
					var data = {
						'action': 'nf_maybe_delete_field',
						'security': nfAdmin.ajaxNonce,
						'formID': formModel.get('id'),
						'fieldKey': dataModel.get('key'),
						'fieldID': modelID
					};
					var that = this;

					// make call to see if field has submission data
					jQuery.post(ajaxurl, data)
						.done(function (response) {
							var res = JSON.parse(response);

							if (res.data.hasOwnProperty('errors')) {
								var errors = res.data.errors;
								var errorMsg = '';

								if (Array.isArray(errors)) {
									errors.forEach(function(error) {
										errors += error + "\n";
									})
								} else {
									errors = errors;
								}
								console.log('Maybe Delete Field  Errors: ', errors);
								alert(errors);
								return null;
							}

							if (res.data.field_has_data) {
								// if it does, show warning modal
								that.doDeleteFieldModal(e, dataModel);
								return false;
							} else {
								// if not, proceed like normal
								that.clickDelete(e, dataModel);
								return false;
							}
						});
				}
			}
		},

		/**
		 * Create the field delete warning modal
		 *
		 * @param e
		 * @param dataModel
		 */
		doDeleteFieldModal: function( e, dataModel ) {
			// Build warning modal to warn user a losing all data related to field
            var that = this;
            var modalData = {
                width: 400,
                closeOnClick: false,
                closeOnEsc: true,
                content: nfi18n.fieldDataDeleteMsg,
                btnPrimary: {
                    text: nfi18n.delete,
                    callback: function() {
                        // close and destory modal.
                        deleteModal.toggleModal( false );
                        deleteModal.destroy();
                        // proceed as normal, data will be deleted in backend on publish
                        that.clickDelete( e, dataModel );
                    }
                },
                btnSecondary: {
                    text: nfi18n.cancel,
                    callback: function() {
                        // close and destory modal
                        deleteModal.toggleModal( false );
                        deleteModal.destroy();
                        // set deleting to false so edit can work as normal
                        that.deleting = false;
                    }
                }
            };
            var deleteModal = new NinjaModal( modalData );
		},

		/**
		 * Delete a action model from our collection
		 * 
		 * @since  3.0
		 * @param  Object			e     	event
		 * @param  backbone.model 	model 	action model
		 * @return void
		 */
		clickDelete: function( e, dataModel ) {
			var newModel = nfRadio.channel( 'app' ).request( 'clone:modelDeep', dataModel );

			// Add our action deletion to our change log.
			var label = {
				object: dataModel.get( 'objectType' ),
				label: dataModel.get( 'label' ),
				change: 'Removed',
				dashicon: 'dismiss'
			};

			var data = {
				collection: dataModel.collection
			};

			var changeCollection = nfRadio.channel( 'changes' ).request( 'get:collection' );
			var results = changeCollection.where( { model: dataModel } );

			_.each( results, function( changeModel ) {
				var data = changeModel.get( 'data' );
				if ( 'undefined' != typeof data.fields ) {
					_.each( data.fields, function( field, index ) {
						if ( field.model == dataModel ) {
							data.fields[ index ].model = newModel;					
						}
					} );
				}
				changeModel.set( 'data', data );
				changeModel.set( 'model', newModel );
				changeModel.set( 'disabled', true );
			} );

			nfRadio.channel( 'changes' ).request( 'register:change', 'removeObject', newModel, null, label, data );
			
			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
			var currentDomainID = currentDomain.get( 'id' );
			nfRadio.channel( currentDomainID ).request( 'delete', dataModel );
			this.deleting = false;
		},

		/**
		 * Duplicate a action within our collection, adding the word "copy" to the label.
		 * 
		 * @since  3.0
		 * @param  Object			e     	event
		 * @param  backbone.model 	model 	action model
		 * @return void
		 */
		clickDuplicate: function( e, model ) {
			var newModel = nfRadio.channel( 'app' ).request( 'clone:modelDeep', model );
			var currentDomain = nfRadio.channel( 'app' ).request( 'get:currentDomain' );
			var currentDomainID = currentDomain.get( 'id' );

			// Change our label.
			// Make sure this update is silent to avoid triggering key change events down the waterfall.
			newModel.set( 'label', newModel.get( 'label' ) + ' Copy', {silent: true} );
			// Update our ID to the new tmp id.
			var tmpID = nfRadio.channel( currentDomainID ).request( 'get:tmpID' );
			newModel.set( 'id', tmpID );
			// Add new model.
			// Params are: model, silent, renderTrigger, action
			nfRadio.channel( currentDomainID ).request( 'add', newModel, false, false, 'duplicate' );
			
			// Add our action addition to our change log.
			var label = {
				object: model.get( 'objectType' ),
				label: model.get( 'label' ),
				change: 'Duplicated',
				dashicon: 'admin-page'
			};

			var data = {
				collection: nfRadio.channel( currentDomainID ).request( 'get:collection' )
			}

			nfRadio.channel( 'changes' ).request( 'register:change', 'duplicateObject', newModel, null, label, data );
			
			model.trigger( 'change:label', model );

			// Update preview.
			nfRadio.channel( 'app' ).request( 'update:db' );
		}

	});

	return controller;
} );
/**
 * Config file for our merge tags.
 *
 * this.collection represents all of our registered merge tags.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/mergeTags',[
	'models/app/mergeTagCollection'
	], function(
	mergeTagCollection
	) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.tagSectionCollection = new mergeTagCollection();
			var that = this;
			_.each( mergeTags, function( tagSection ) {
				if ( tagSection.tags ) {
					var tags = new mergeTagCollection( tagSection.tags );
				} else {
					var tags = '';
				}

				that.tagSectionCollection.add( {
					id: tagSection.id,
					label: tagSection.label,
					tags: tags,
					default_group: tagSection.default_group
				} );
			} );

			var fieldTags = this.tagSectionCollection.get( 'fields').get( 'tags' );

			var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
			_.each( fieldCollection.models, function( field ) {
				// TODO: Make this dynamic
				if ( 'submit' !== field.get( 'type' ) ) {
					fieldTags.add( {
						id: field.get( 'id' ),
						label: field.get( 'label' ),
						tag: that.getFieldKeyFormat( field.get( 'key' ) )
					} );					
				}
			} );

			var calcTags = new mergeTagCollection();

			var formModel = nfRadio.channel( 'app' ).request( 'get:formModel' );
			var calcCollection = formModel.get( 'settings' ).get( 'calculations' );
			_.each( calcCollection.models, function( calcModel ) {
				calcTags.add( {
					label: calcModel.get( 'name' ),
					tag: '{calc:' + calcModel.get( 'name' ) + '}'
				} );
			} );

			this.tagSectionCollection.get( 'calcs' ).set( 'tags', calcTags );

			this.currentElement = {};
			this.settingModel = {};
			this.open = false;

			// Unhook jBox Merge Tag stuff.
			// nfRadio.channel( 'mergeTags' ).reply( 'init', this.initMergeTags, this );

			this.listenTo( nfRadio.channel( 'mergeTags' ), 'click:mergeTag', this.clickMergeTag );
			this.listenTo( nfRadio.channel( 'fields' ), 'add:field', this.addFieldTags );
			this.listenTo( nfRadio.channel( 'fields' ), 'delete:field', this.deleteFieldTags );
			this.listenTo( nfRadio.channel( 'option-repeater-calculations' ), 'update:option', this.updateCalcTags );
			this.listenTo( nfRadio.channel( 'option-repeater-calculations' ), 'remove:option', this.updateCalcTags );

			
			nfRadio.channel( 'mergeTags' ).reply( 'update:currentElement', this.updateCurrentElement, this );
			nfRadio.channel( 'mergeTags' ).reply( 'update:currentSetting', this.updateCurrentSetting, this );

			// Listen for requests for our mergeTag collection.
			nfRadio.channel( 'mergeTags' ).reply( 'get:collection', this.getCollection, this );
			nfRadio.channel( 'mergeTags' ).reply( 'get:mergeTag', this.getSectionModel, this );

			// When a field's ID is changed (ie from a tmpID), update the merge tag.
            this.listenTo( nfRadio.channel( 'fieldSetting-id' ), 'update:setting', this.updateID );

			// When we edit a key, check for places that key might be used.
			this.listenTo( nfRadio.channel( 'fieldSetting-key' ), 'update:setting', this.updateKey );

			// Reply to requests to check a data model for a field key when one is updated.
			this.listenTo( nfRadio.channel( 'app' ), 'replace:fieldKey', this.replaceFieldKey );

			// Reply to requests to check a data model for a field key when one is updated.
			nfRadio.channel( 'app' ).reply( 'get:fieldKeyFormat', this.getFieldKeyFormat, this );

			/*
			 * TODO: Hotkey support for adding tags.
			 *
			
			this.listenTo( nfRadio.channel( 'hotkeys' ), 'open:mergeTags', this.openMergeTags );
			this.listenTo( nfRadio.channel( 'hotkeys' ), 'up:mergeTags', this.upMergeTags );
			this.listenTo( nfRadio.channel( 'hotkeys' ), 'down:mergeTags', this.downMergeTags );
			this.listenTo( nfRadio.channel( 'hotkeys' ), 'return:mergeTags', this.returnMergeTags );
			nfRadio.channel( 'mergeTags' ).reply( 'update:open', this.updateOpen, this );
			*/
		},

		/**
		 * Init merge tags within the passed view.
		 * @since  3.0
		 * @param  backbone.view view to be searched for merge tags.
		 * @return void
		 */
		initMergeTags: function( view ) {
			var mergeTagsView = nfRadio.channel( 'mergeTags' ).request( 'get:view' );
			var that = this;
			/*
			 * Apply merge tags jQuery plugin.
			 *
			 * Prevent jBox from being called multiple times on the same element
			 */
			this.jBoxes = {};
			var that = this;

			jQuery( view.el ).find( '.merge-tags' ).each(function() {
				if ( 'undefined' == typeof jQuery( this ).data( 'jBox-id' ) ) {
					var jBox = jQuery( this ).jBox( 'Tooltip', {
						title: 'Insert Merge Tag',
						trigger: 'click',
						position: {
							x: 'center',
							y: 'bottom'
						},
						closeOnClick: 'body',
						closeOnEsc: true,
						theme: 'TooltipBorder',
						maxHeight: 200,

						onOpen: function() {
							mergeTagsView.reRender( view.model );
							this.setContent( jQuery( '.merge-tags-content' ) );
							var currentElement = jQuery( this.target ).prev( '.setting' );
							if ( 0 == currentElement.length ) {
								currentElement = jQuery( view.el ).find( '.setting' );
							}
													
							that.updateCurrentSetting( view.model );
							that.updateCurrentElement( currentElement );
							// nfRadio.channel( 'drawer' ).request( 'prevent:close', 'merge-tags' );
						},
						onClose: function() {
							// nfRadio.channel( 'drawer' ).request( 'enable:close', 'merge-tags' );
						}
					});
					
					jQuery( this ).data( 'jBox-id', jBox.id );					
				}
		    });
		},

		clickMergeTag: function( e, tagModel ) {
			/*
			 * TODO: Make this more dynamic.
			 * Currently, the RTE is the only section that modifies how merge tags work,
			 * but another type of setting might need to do this in the future.
			 */

			if( 'undefined' != typeof this.settingModel.get( 'settingModel' ) && 'calculations' == this.settingModel.get( 'settingModel' ).get( 'name' ) ) {

				console.log( tagModel );

				var currentValue = jQuery( this.currentElement ).val();
				var currentPos = jQuery( this.currentElement ).caret();
				var newPos = currentPos + tagModel.get( 'tag' ).length;

				var tag = ( 'undefined' != typeof tagModel.get( 'calcTag' ) ) ? tagModel.get( 'calcTag' ) : tagModel.get( 'tag' );

				currentValue = currentValue.substr( 0, currentPos ) + tag + currentValue.substr( currentPos );
				jQuery( this.currentElement ).val( currentValue ).caret( newPos ).trigger( 'change' );
			} else if( 'rte' == this.settingModel.get( 'type' ) ) {
				jQuery( this.currentElement ).summernote( 'insertText', tagModel.get( 'tag' ) );
			} else {
				var currentValue = jQuery( this.currentElement ).val();
				var currentPos = jQuery( this.currentElement ).caret();
				var newPos = currentPos + tagModel.get( 'tag' ).length;
				currentValue = currentValue.substr( 0, currentPos ) + tagModel.get( 'tag' ) + currentValue.substr( currentPos );
				jQuery( this.currentElement ).val( currentValue ).caret( newPos ).trigger( 'change' );
			}
		},

		addFieldTags: function( fieldModel ) {
			// TODO: Make this dynamic
			if ( 'submit' !== fieldModel.get( 'type' ) ) {
				this.tagSectionCollection.get( 'fields' ).get( 'tags' ).add( {
					id: fieldModel.get( 'id' ),
					label: fieldModel.get( 'label' ),
					tag: this.getFieldKeyFormat( fieldModel.get( 'key' ) ),
					calcTag: this.getFieldKeyFormatCalc( fieldModel.get( 'key' ) )
				} );
			}
		},

		deleteFieldTags: function( fieldModel ) {
			var fieldID = fieldModel.get( 'id' );
			var tagModel = this.tagSectionCollection.get( 'fields' ).get( 'tags' ).get( fieldID );
			this.tagSectionCollection.get( 'fields' ).get( 'tags' ).remove( tagModel );
		},

		updateCalcTags: function( optionModel ) {
			var calcTags = new mergeTagCollection();

			var formModel = nfRadio.channel( 'app' ).request( 'get:formModel' );
			var calcCollection = formModel.get( 'settings' ).get( 'calculations' );

			_.each( calcCollection.models, function( calc ) {
				calcTags.add( {
					label: calc.get( 'name' ),
					tag: '{calc:' + calc.get( 'name' ) + '}'
				} );
			} );

			this.tagSectionCollection.get( 'calcs' ).set( 'tags', calcTags );
		},

		openMergeTags: function( e ) {
			if ( 'TEXTAREA' == jQuery( e.target )[0].tagName || 'INPUT' == jQuery( e.target )[0].tagName ) {
				jQuery( e.target ).parent().find( '.merge-tags' ).click();
			}
		},

		returnMergeTags: function( e ) {
			if ( this.open ) {
				e.preventDefault();
				var currentModel = this.fields.where( { 'active': true } )[0];
				if ( currentModel ) {
					this.clickMergeTag( e, currentModel );
				}
			}
		},

		upMergeTags: function( e ) {
			if ( this.open ) {
				e.preventDefault();
				this.changeActiveTag( 'up' );
			}
		},

		downMergeTags: function( e ) {
			if ( this.open ) {
				e.preventDefault();
				this.changeActiveTag( 'down' );
			}
		},

		changeActiveTag: function( dir ) {
			if ( 'down' == dir ) {
				var inc = 1;
			} else {
				var inc = -1
			}
			// First, check to see if a field is currently active.
			if( 0 < this.fields.where( { 'active': true } ).length ) {
				var currentModel = this.fields.where( { 'active': true } )[0];
				var currentIndex = this.fields.indexOf( currentModel );
				currentModel.set( 'active', false );

				var nextModel = this.fields.models[ currentIndex + inc ];
				if ( nextModel ) {
					nextModel.set( 'active', true );
				} else {

				}
				
			} else if ( 0 < this.fields.where( { 'active': true } ) ) { // There aren't any active fields. Check for active system tags.
				console.log( 'system' );
			} else if ( 0 < this.userInfo.where( { 'active': true } ) ) { // No active user info LIs.
				console.log( 'userinfo' );
			} else { // No active LIs. We haven't made any active yet, or we've gotten to the bottom of the list.
				// Make sure that we have fields
				if ( 0 < this.fields.models.length ) {
					// Set our first field to active.
					this.fields.models[0].set( 'active', true );
				} else {
					// Set our first system model to active.
					this.system.models[0].set( 'active', true );
				}
			}
		},

		updateCurrentElement: function( element ) {
			this.currentElement = element;
		},

		updateCurrentSetting: function( settingModel ) {
			this.settingModel = settingModel;
		},

		getCollection: function() {
			return this.tagSectionCollection;
		},

		getSectionModel: function( id ) {
			return this.tagSectionCollection.get( id );
		},

		updateOpen: function( open ) {
			this.open = open;
			_.each( this.tagSectionCollection.get( 'fields' ).models, function( model ) {
				model.set( 'active', false );
			} );
		},

		// When a field is published, update the merge tag with the newly assigned ID (as opposed to the tmpID).
        updateID: function( fieldModel ) {

			// Get the formatted merge tag for comparison.
			var targetTag = this.getFieldKeyFormat( fieldModel.get( 'key' ) );

			// Search the field tags for the matching merge tag to be updated.
			var oldTag = this.tagSectionCollection.get( 'fields' ).get( 'tags' ).find( function( fieldMergeTag ){
                return targetTag == fieldMergeTag.get( 'tag' );
            });

			// If no matching tag is found, return early.
			if( 'undefined' == typeof oldTag ) return;

			// Update the merge tag with the "published" field ID.
			oldTag.set( 'id', fieldModel.get( 'id' ) );
		},

		updateKey: function( fieldModel ) {
			var newKey = fieldModel.get( 'key' );
			var oldTag = this.tagSectionCollection.get( 'fields' ).get( 'tags' ).get( fieldModel.get( 'id' ) );
			if ( 'undefined' != typeof oldTag ) {
				oldTag.set( 'tag', this.getFieldKeyFormat( newKey ) );				
			}

		},

		getFieldKeyFormat: function( key ) {
			return '{field:' + key + '}';
		},

		getFieldKeyFormatCalc: function( key ) {
			return '{field:' + key + ':calc}';
		},

		replaceFieldKey: function( dataModel, keyModel, settingModel ) {
            var oldKey = this.getFieldKeyFormat( keyModel._previousAttributes[ 'key' ] );
			var newKey = this.getFieldKeyFormat( keyModel.get( 'key' ) );
			var settingName = settingModel.get( 'name' );
			var oldVal = dataModel.get( settingName );
            if(settingName == 'calculations' && 'undefined' != typeof(dataModel.get('calculations'))) {
                var calcModel = dataModel.get( 'calculations' );
                calcModel.each( function( model ) {
                    var oldCalcKey = oldKey.slice( 0, (oldKey.length - 1) ) + ':calc}';
                    var newCalcKey = newKey.slice( 0, (newKey.length - 1 ) ) + ':calc}';
                    oldVal = model.get( 'eq' );
                    if ( 'string' == typeof( oldVal ) ) {
                        var re = new RegExp( oldCalcKey, 'g' );
                        var newVal = oldVal.replace( re, newCalcKey );
                        re = new RegExp( oldKey, 'g' );
                        // TODO: We won't need this second replace when we no longer
                        // have to append :calc to merge tags.
                        newVal = newVal.replace( re, newKey );
                        model.set( 'eq', newVal );
                    }
                } );
                return false;
            }
			if ( 'string' == typeof oldVal ) {
				var re = new RegExp( oldKey, 'g' );
				newVal = oldVal.replace( re, newKey );
				dataModel.set( settingName, newVal );
			}
		}

	});

	return controller;
} );

/**
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/mergeTagLookupCollection',['models/app/mergeTagModel'], function( mergeTagModel ) {
    var collection = Backbone.Collection.extend( {
        model: mergeTagModel
    } );
    return collection;
} );
/**
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/mergeTag',[], function() {
    var view = Marionette.ItemView.extend({
        tagName: 'li',
        template: '#tmpl-nf-merge-tag-box-tag',

        events: {
            "click": "insertTag"
        },

        insertTag: function() {
            nfRadio.channel( 'mergeTags' ).request( 'insert:tag', this.model.get( 'tag' ) );
        }
    });

    return view;
} );
/**
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/mergeTagList',[ 'views/app/drawer/mergeTag' ], function( mergeTagView ) {
    var view = Marionette.CollectionView.extend({
        tagName: 'ul',
        childView: mergeTagView,
        calc: false,

        initialize: function() {
            nfRadio.channel( 'merge-tags' ).reply( 'update:taglist', this.sectionFilter, this );
            nfRadio.channel( 'merge-tags' ).reply( 'filtersearch', this.searchFilter, this );
        },

        filter: function( child, index, collection ){
            return 'fields' == child.get( 'section' );
        },

        sectionFilter: function( section, calc ){
            this.filter = function( child, index, collection ){
                return section == child.get( 'section' );
            }

            if ( calc ) {
                this.calc = true;
            }

            if ( this.calc ) {
                var fieldsToRemove = this.excludeFromCalcs();

                /**
                 * Filters our merge tags.
                 * Make sure that we're in the right section, and then check to see if the merge tag is in our remove tracker.
                 */
                this.filter = function( child, index, collection ) {
                    return section == child.get( 'section' ) && -1 == fieldsToRemove.indexOf( child.get( 'tag' ) );
                }
            }

            this.render();
            nfRadio.channel( 'merge-tags' ).trigger( 'after:filtersearch', section );
        },

        searchFilter: function( term ){
            if ( this.calc ) {
                var fieldsToRemove = this.excludeFromCalcs();
            }

            this.filter = function( child, index, collection ){
                var label = child.get( 'label' ).toLowerCase().indexOf( term.toLowerCase().replace( ':', '' ) ) >= 0;
                var tag   = child.get( 'tag' ).toLowerCase().indexOf( term.toLowerCase() ) >= 0;
                // If we are in a calculation setting and this tag is in our remove tracker, early return false.
                if ( this.calc && -1 != fieldsToRemove.indexOf( child.get( 'tag' ) ) ) {
                    return false;
                }
                return label || tag;
            }

            this.render();
            nfRadio.channel( 'merge-tags' ).trigger( 'after:filtersearch' );

        },

        /**
         * TODO: This is a wonky fix for removing Product and Quantity fields from calcuation merge tags.
         * Merge tags don't respect the "exclude" merge tag settings.
         * Ultimately, the fix might include updating merge tags to respect those settings.
         */
        excludeFromCalcs: function(){
            /**
             * Remove any unwanted fields if we are in a calculation.
             * Get a list of all fields, then filter out unwanted fields.
             */
            var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
            // Stores the keys of unwanted fields.
            var fieldsToRemove = [];
            // Declare blacklisted field types.
            var blacklist = ['product', 'quantity', 'total', 'shipping', 'date'];
            // Remove them from the merge tag selection box.
            _.each( fieldCollection.models, function( model ) {
                if ( -1 != blacklist.indexOf( model.get('type') ) ) {
                    fieldsToRemove.push( '{field:' + model.get( 'key' ) + '}' );
                }
            });
            return fieldsToRemove;
        }
    });

    return view;
} );
/**
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/mergeTagGroup',[], function() {
    var view = Marionette.ItemView.extend({
        tagName: 'li',
        template: '#tmpl-nf-merge-tag-box-section',
        events: {
            "click": "onClick"
        },

        initialize: function () {
            this.listenTo( nfRadio.channel( 'merge-tags' ), 'after:filtersearch', this.updateActive );
        },

        onClick: function(){
          this.updateTags();
        },

        updateTags: function() {
            nfRadio.channel( 'merge-tags' ).request( 'update:taglist', this.model.get( 'id' ) );
        },

        updateActive: function( section ) {
            this.$el.removeClass( 'active' );

            if ( section == this.model.get( 'id' ) ) {
                this.$el.addClass( 'active' );
            }
        },

        setActive: function(){
            this.$el.addClass( 'active' );
            this.$el.siblings().removeClass( 'active' );
        },

    });

    return view;
} );
/**
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/mergeTagGroupList',[ 'views/app/drawer/mergeTagGroup' ], function( mergeTagGroupView ) {
    var view = Marionette.CollectionView.extend({
        tagName: 'ul',
        childView: mergeTagGroupView,

        initialize: function(){
            this.listenTo( nfRadio.channel( 'merge-tags' ), 'open', this.render, this );
        },

        // TODO: Update filter when a new tag is added. ie Calculations.
        filter: function( child, index, collection ){
            return 0 < child.get( 'tags' ).length;
        },
    });

    return view;
} );
/**
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/mergeTagFilter',[], function() {
    var view = Marionette.ItemView.extend({
        template: '#tmpl-nf-merge-tag-box-filter',
        events: {
            "keyup input": "updateFilter",
        },
        updateFilter: function( event ) {

            if( /* ENTER */ 13 == event.keyCode ){ // Copied from Keyup Callback.
                // Get top listed merge tag.
                var firstFilteredTag = jQuery( '#merge-tags-box .merge-tag-list ul li span' ).first().data( 'tag' );

                nfRadio.channel( 'mergeTags' ).request( 'insert:tag', firstFilteredTag );

                // COPIED FROM BELOW
                jQuery( '#merge-tags-box' ).css( 'display', 'none' );
                jQuery( '#merge-tags-box' ).removeClass();
                jQuery( '.merge-tag-focus' ).removeClass( 'merge-tag-focus' );
                jQuery( '.merge-tag-focus-overlay' ).removeClass( 'merge-tag-focus-overlay' );
                return;
            }
            var value = this.$el.find( 'input' ).val();
            nfRadio.channel( 'merge-tags' ).request( 'filtersearch', value );
        }
    });

    return view;
} );
/**
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/drawer/mergeTagBox',[], function() {
    var view = Marionette.LayoutView.extend({
        el: '#merge-tags-box',
        template: "#tmpl-nf-merge-tag-box",

        regions: {
            filter:   '.merge-tag-filter',
            sections: '.merge-tag-sections',
            tags:     '.merge-tag-list'
        },
    });

    return view;
} );
/**
 * @package Ninja Forms builder
 * @subpackage Merge Tag Box
 * @copyright (c) 2017 WP Ninjas
 * @since 3.1
 */

define( 'controllers/app/mergeTagBox',[
    'models/app/mergeTagModel',
    'models/app/mergeTagLookupCollection',
    'views/app/drawer/mergeTag',
    'views/app/drawer/mergeTagList',
    'views/app/drawer/mergeTagGroup',
    'views/app/drawer/mergeTagGroupList',
    'views/app/drawer/mergeTagFilter',
    'views/app/drawer/mergeTagBox'
], function(
    MergeTagModel,
    MergeTagLookupCollection,
    MergeTagView,
    MergeTagListView,
    MergeTagGroupView,
    MergeTagGroupListView,
    MergeTagFilterView,
    MergeTagBoxLayout
) {
    var controller = Marionette.Object.extend( {

        caret: 0, // Track the caret position of the current setting's input.
        old: '', // THe old merge tag that will be replaced.

        initialize: function(){

            this.listenTo( nfRadio.channel( 'drawer' ), 'render:settingGroup', function(){
                jQuery( '.merge-tags' ).off( 'click' );
                jQuery( '.merge-tags' ).on( 'click', this.mergeTagsButtonClick );
            });

            this.listenTo( nfRadio.channel( 'app' ), 'after:appStart', this.afterAppStart );
            this.listenTo( nfRadio.channel( 'app' ), 'before:renderSetting', this.beforeRenderSetting );
            this.listenTo( nfRadio.channel( 'drawer' ), 'before:close', this.beforeDrawerClose );

            var that = this;
            nfRadio.channel( 'mergeTags' ).reply( 'set:caret', function( position ){
               that.caret = position;
            });
            nfRadio.channel( 'mergeTags' ).reply( 'get:caret', function(){
                return that.caret;
            });

            var that = this;
            nfRadio.channel( 'mergeTags' ).reply( 'set:old', function( value ){
                that.old = value;
            });
            nfRadio.channel( 'mergeTags' ).reply( 'get:old', function(){
                return that.old;
            });

            nfRadio.channel( 'mergeTags' ).reply( 'insert:tag', this.insertTag.bind( this ) );

            /** OPTION REPEATER */
            this.listenTo( nfRadio.channel( 'option-repeater' ), 'add:option', function( model ){
                var selector = '#' + model.cid + ' .has-merge-tags input.setting';
                jQuery( selector ).on( 'focus', function( event ){
                   that.focusCallback( event, selector, 'option-repeater' );
                });
                jQuery( selector ).on( 'keyup', function( event ){
                    that.keyupCallback( event, selector, 'option-repeater' );
                });
                jQuery( selector ).siblings( '.nf-list-options .merge-tags' ).off( 'click' );
                jQuery( selector ).siblings( '.nf-list-options .merge-tags' ).on( 'click', this.mergeTagsButtonClick );
            } );
            this.listenTo( nfRadio.channel( 'drawer' ), 'opened', function(){
                jQuery( '.nf-list-options .merge-tags' ).off( 'click' );
                jQuery( '.nf-list-options .merge-tags' ).on( 'click', this.mergeTagsButtonClick );
            } );

            /* CALCULATIONS */
            this.listenTo( nfRadio.channel( 'setting-calculations-option' ), 'render:setting', this.renderSetting );
            // this.listenTo( nfRadio.channel( 'setting-calculations-option' ), 'render:setting', function( settingModel, dataModel, view ){
            //     view.$el.find( '.merge-tags' ).on( 'click', this.mergeTagsButtonClick );
            // } );
            this.listenTo( nfRadio.channel( 'drawer' ), 'opened', function(){
                jQuery( '.nf-list-options.calculations .merge-tags' ).off( 'click' );
                jQuery( '.nf-list-options.calculations .merge-tags' ).on( 'click', this.mergeTagsButtonClick );
            } );

            /* SUMMERNOTE */
            this.listenTo( nfRadio.channel( 'summernote' ), 'focus', function( e, selector ) {
                that.focusCallback( false, selector, 'rte' );
            } );
            this.listenTo( nfRadio.channel( 'summernote' ), 'keydown', function( e, selector ){
                jQuery( selector ).closest( '.nf-setting' ).find( '.setting' ).summernote( 'saveRange' );
            } );
            this.listenTo( nfRadio.channel( 'summernote' ), 'keyup', function( e, selector ){
                that.keyupCallback( e, selector, 'rte' );
            } );

            // When an RTE setting is shown, make sure merge tags are hooked up.
            this.listenTo( nfRadio.channel( 'setting-type-rte' ), 'render:setting', function(){
                jQuery( '.note-editor .merge-tags' ).off( 'click' );
                jQuery( '.note-editor .merge-tags' ).on( 'click', this.mergeTagsButtonClick );
            } );

            this.listenTo( nfRadio.channel( 'drawer' ), 'opened', function(){
                jQuery( '.note-editor .merge-tags' ).off( 'click' );
                jQuery( '.note-editor .merge-tags' ).on( 'click', this.mergeTagsButtonClick );
            } );

            jQuery( document ).on( 'keyup', function( event ){
                if( 27 == event.keyCode ){
                    nfRadio.channel( 'mergeTags' ).request( 'insert:tag', '' );
                    // Copied from KeyupCallback.
                    jQuery( '#merge-tags-box' ).css( 'display', 'none' );
                    nfRadio.channel( 'drawer' ).request( 'enable:close' );
                    jQuery( '#merge-tags-box' ).removeClass();
                    jQuery( '.merge-tag-focus' ).blur();
                    jQuery( '.merge-tag-focus' ).removeClass( 'merge-tag-focus' );
                    jQuery( '.merge-tag-focus-overlay' ).removeClass( 'merge-tag-focus-overlay' );
                }
            });

            /**
             * Listen to the Field Changes (add, delete, update) and update the Merge Tags.
             */
            this.listenTo( Backbone.Radio.channel( 'fields' ), 'add:field',    this.afterAppStart );
            this.listenTo( Backbone.Radio.channel( 'fields' ), 'delete:field', this.afterAppStart );
            this.listenTo( Backbone.Radio.channel( 'fieldSetting-key' ), 'update:setting', this.afterAppStart );

            /** ... and Calc updates. */
            this.listenTo( Backbone.Radio.channel( 'calcs' ), 'update:calc', this.afterAppStart );

            this.listenTo( Backbone.Radio.channel( 'app' ), 'change:currentDomain', this.afterAppStart );
        },

        afterAppStart: function() {

            var currentDomain = Backbone.Radio.channel( 'app' ).request( 'get:currentDomain' );

            var mergeTagCollection = nfRadio.channel( 'mergeTags' ).request( 'get:collection' );
            var mergeTags = [];
            mergeTagCollection.each( function( section ){

                section.get( 'tags' ).each( function( tag ){

                    if( 'fields' == currentDomain.get( 'id' ) && '{submission:sequence}' == tag.get( 'tag' ) ) return;

                    mergeTags.push({
                        label: tag.get( 'label' ),
                        tag:   tag.get( 'tag' ),
                        section: section.get( 'id' )
                    });
                });
            });
            var layout = new MergeTagBoxLayout();
            layout.render();
            var tagCollection = new MergeTagLookupCollection( mergeTags );
            var mergeTagListView = new MergeTagListView({
                collection: tagCollection
            });
            var mergeTagGroupListView = new MergeTagGroupListView({
                collection: mergeTagCollection
            });

            layout.getRegion('tags').show(mergeTagListView);
            layout.getRegion('sections').show(mergeTagGroupListView);
            layout.getRegion('filter').show(new MergeTagFilterView);
        },

        beforeRenderSetting: function( settingModel, dataModel ){
            if( 'undefined' == typeof settingModel.get( 'use_merge_tags' ) ) return;
            if( ! settingModel.get( 'use_merge_tags' ) ) return;
            var name = settingModel.get( 'name' );
            this.listenTo( nfRadio.channel( 'setting-' + name ), 'render:setting', this.renderSetting );
        },

        renderSetting: function( settingModel, dataModel, view ){

            view.$el.find( '.merge-tags' ).off( 'click' );
            view.$el.find( '.merge-tags' ).on( 'click', this.mergeTagsButtonClick );

            if( 0 == jQuery( '#merge-tags-box' ).length ) this.afterAppStart();

            // Track Scrolling.
            jQuery( '#nf-drawer' ).on( 'scroll', function(){
               // COPIED AND MODIFIED FROM FOCUS
                if( 0 == jQuery( '.merge-tag-focus' ).length ) return;

                var rteEditor = jQuery( '.merge-tag-focus' ).closest( '.nf-setting' ).find( '.note-editor' );
                if( 0 != rteEditor.length ){
                    var posY = rteEditor.offset().top - jQuery(window).scrollTop();
                    var height = rteEditor.outerHeight();
                } else {
                    var posY = jQuery('.merge-tag-focus').offset().top - jQuery(window).scrollTop();
                    var height = jQuery('.merge-tag-focus').outerHeight();
                }

	            // Find out if merge tag box will go below bottom of the page.
	            var tagBoxY = posY + height;
	            var windowHeight = window.innerHeight;
	            var tagBoxHeight = jQuery( '#merge-tags-box' ).outerHeight();

	            // If merge tag box will render below the bottom of the page,
	            // change it to render above the field

	            if ( ( tagBoxY + tagBoxHeight ) > windowHeight ) {
                    tagBoxY = posY - tagBoxHeight;
                }

                if ( 0 > tagBoxY ) {
                    tagBoxY = posY;
                }

                jQuery( '#merge-tags-box' ).css( 'top', tagBoxY );

                var boxHeight = jQuery( '#merge-tags-box' ).outerHeight();
                jQuery( '#nf-drawer' ).css( 'padding-bottom', boxHeight + 'px' );

                var repeaterRow = jQuery( '.merge-tag-focus' ).closest( '.nf-list-options-tbody' );
                if( 0 != repeaterRow.length ){
                    var left = repeaterRow.offset().left - jQuery(window).scrollLeft();
                    jQuery( '#merge-tags-box' ).css( 'left', left );
                } else {
                    var posX = jQuery( '.merge-tag-focus' ).closest( '.nf-settings' ).offset().left - jQuery(window).scrollLeft();
                    jQuery( '#merge-tags-box' ).css( 'left', posX );
                    jQuery( '#merge-tags-box' ).css( 'width', jQuery( '.merge-tag-focus' ).closest( '.nf-settings' ).width() );
                }
            });

            // On input focus, move the Merge Tag Box into position.
            jQuery( view.el ).find( '.setting' ).on( 'focus', this.focusCallback );

            // TODO: Maybe move to view events.
            // On input keyup, maybe show Merge Tag Box.
            jQuery( view.el ).find( '.setting' ).on( 'keyup', this.keyupCallback );
        },

        // TODO: Maybe move to view class.
        beforeDrawerClose: function(){
            jQuery( '#merge-tags-box' ).css( 'display', 'none' );
            nfRadio.channel( 'drawer' ).request( 'enable:close' );
            // jQuery( 'body' ).append( jQuery( '#merge-tags-box' ) );
        },

        insertTag: function( tag ) {

            var $input = jQuery( '.merge-tag-focus' );

            if( 0 != $input.closest( '.nf-setting' ).first().find( '.note-editable' ).length ){
                $input = $input.closest( '.nf-setting' ).first().find( '.note-editable' );
            }

            if( 1 < $input.length ){ $input = $input.first(); }

            if( $input.hasClass( 'note-editable' ) ){
                var str = $input.closest( '.nf-setting' ).find( '.setting' ).summernote( 'code' );
            } else {
                var str = $input.val();
            }

            var find = nfRadio.channel( 'mergeTags' ).request( 'get:old' );
            var replace = tag;
            var caretPos = nfRadio.channel( 'mergeTags' ).request( 'get:caret' );

            var patt = /{([a-zA-Z0-9]|:|_||-})*/g;

            // Loop through matches to find insert/replace index range.
            // Reference: http://codepen.io/kjohnson/pen/36c3a782644dfff40fe3c1f05f8739d9?editors=0012
            while (match = patt.exec(str)) {
                if (find != match[0]) continue; // This isn't the match you are looking for...
                var string = str.slice(0, match.index) + replace + str.slice(patt.lastIndex); // Fancy replace for the specifc match, using the index/position.

                if( $input.hasClass( 'note-editable' ) ){
                    $input.closest( '.nf-setting' ).find( '.setting' ).summernote( 'code', string );

                    // Reposition the caret. http://stackoverflow.com/a/6249440 TODO: Determine the appropriate childNode.
                    var el = $input;
                    var childNode = null; // Default to first childNode.
                    _.each( el[0].childNodes, function( node, index ){
                        if( childNode ) return;
                        if( ! node.nodeValue && ! node.innerHTML ) return;
                        if( node.nodeValue ) {
                            var value = node.nodeValue;
                        } else if( node.innerHTML ){
                            var value = node.innerHTML;
                        }

                        if( -1 == value.indexOf(replace) ) return; // Replace not found in this node.

                        value = value.replace( /&nbsp;/g, ' ' );
                        var position = value.indexOf(replace) + find.length;

                        /*
                         * If no caretPos, determine based on the node. ie Merge Tag Button context.
                         * Note: We can't just check for '{', because they could just be inserting the first tag.
                         */
                        if( -1 == caretPos ){
                            caretPos = value.indexOf( replace ) + 1;
                        }

                        if (caretPos == position) childNode = el[0].childNodes[index];
                    });
                    if( ! childNode ) childNode = el[0].childNodes[0];
                    var offset = caretPos - find.length + replace.length;
                    var range = document.createRange();
                    var sel = window.getSelection();
                    if( 0 != childNode.childNodes.length ) {
                        try{
                           range.setStart(childNode.childNodes[0], offset); 
                        } catch( err ) {
                            console.log( childNode );
                            console.log( 'error' );
                        }
                        
                    } else {
                        try {
                            range.setStart(childNode, offset);
                        } catch( err ) {
                            console.log( 'error' );
                        }
                        
                    }
                    range.collapse(true);
                    sel.removeAllRanges();
                    sel.addRange(range);


                } else {
                    $input.val(string); // Update input value with parsed string.
                    $input.change(); // Trigger a change event after inserting the merge tag so that it saves to the model.
                    $input.caret(caretPos - find.length + replace.length); // Update Carept Position.
                }

            }

            jQuery( '#merge-tags-box' ).css( 'display', 'none' );
            nfRadio.channel( 'drawer' ).request( 'enable:close' );
            $input.removeClass( 'merge-tag-focus' );
            $input.closest( '.merge-tag-focus-overlay' ).removeClass( 'merge-tag-focus-overlay' );
        },

        mergeTagsButtonClick: function( e ){
            var $this = jQuery( this );

            if ($this.hasClass('open-media-manager')) {
                return;
            }

            if( $this.siblings().hasClass( 'merge-tag-focus' ) ){
                nfRadio.channel( 'mergeTags' ).request( 'insert:tag', '' );
                jQuery( '#merge-tags-box' ).css( 'display', 'none' );
                nfRadio.channel( 'drawer' ).request( 'enable:close' );
                jQuery( '.merge-tag-focus' ).removeClass( 'merge-tag-focus' );
                jQuery( '.merge-tag-focus-overlay' ).removeClass( 'merge-tag-focus-overlay' );
                return;
            }

            if( 0 !== $this.closest( '.nf-setting, .nf-table-row' ).find( '.note-tools' ).length ){
                var $inputSetting = $this.closest( '.note-editor' ).siblings( '.setting' ).first();
                $this.closest( '.nf-setting' ).find( '.setting' ).summernote( 'insertText', '{' );
                // Since we haven't determined the caretPos, set to -1 as a flag to determine later.
                nfRadio.channel('mergeTags').request( 'set:caret', -1 );
            } else {
                var $inputSetting = $this.siblings( '.setting' ).first();
                var text = $inputSetting.val() || '';
                $inputSetting.val( text + '{' ).change();
                nfRadio.channel('mergeTags').request('set:caret', text.length + 1 );
            }

            if( $this.parent().hasClass( 'note-tools' ) ){
                // $this.closest( '.nf-setting' ).find( '.setting' ).summernote( 'insertText', '{' );
            }

            nfRadio.channel('mergeTags').request('set:old', '{' );

            $inputSetting.addClass( 'merge-tag-focus' );

            // Disable browser autocomplete.
            var autocomplete = $this.attr( 'autocomplete' );
            $this.attr( 'autocomplete', 'off' );
            $this.data( 'autocomplete', autocomplete );

            var $overlayElement = $this.closest( '.nf-setting, .nf-table-row' );
            if( 0 != $overlayElement.find( '.note-editor' ).length ){
                $overlayElement.find('.note-editor' ).addClass('merge-tag-focus-overlay');
            } else {
                $overlayElement.addClass('merge-tag-focus-overlay');
            }

            /**
             * TODO: This is a wonky work around for removing Product and Quantity fields from calculation merge tags.
             * The merge tag system doesn't currently respect "exclude" merge tag settings.
             *
             * If 'eq' is the textarea next to the merge tag icon, then we're in a calculation setting.
             */
            if ( 'eq' == jQuery( e.target ).prev( 'textarea' ).data( 'id' ) ) {
                var calc = true;
            } else {
                var calc = false;
            }

            // Request that our merge tag box update its tag list, passing whether or not we're in a calculation setting.
            nfRadio.channel( 'merge-tags' ).request( 'update:taglist', 'fields', calc );
            
            jQuery( '#merge-tags-box' ).css( 'display', 'block' );
            nfRadio.channel( 'drawer' ).request( 'prevent:close' );

            jQuery( '.merge-tag-focus-overlay' ).off( 'click' );
            jQuery( '.merge-tag-focus-overlay' ).on( 'click', function( e ) {
                if ( jQuery( e.target ).hasClass( 'note-editor' ) ) {
                    nfRadio.channel( 'mergeTags' ).request( 'insert:tag', '' );
                    jQuery( '#merge-tags-box' ).css( 'display', 'none' );
                    nfRadio.channel( 'drawer' ).request( 'enable:close' );
                    jQuery( '#merge-tags-box' ).removeClass();
                    jQuery( '.merge-tag-focus' ).removeClass( 'merge-tag-focus' );
                    jQuery( '.merge-tag-focus-overlay' ).removeClass( 'merge-tag-focus-overlay' );
                }
            } );

            setTimeout(function(){
                jQuery( '#merge-tags-box' ).find( '.merge-tag-filter' ).find( 'input' ).focus();
            }, 500 );
        },

        focusCallback: function( e, target, type ){

            var type = type || 'setting';
            var $this = ( 'undefined' == typeof target ) ? jQuery( this ) : jQuery( target );

            jQuery( '.merge-tag-focus' ).each(function(index, el){
                if( this == el ) return;
                el.removeClass( 'merge-tag-focus' );
            });

            if( 'rte' == type ) {
                var posY = $this.closest( '.nf-setting' ).find( '.note-editor' ).offset().top - jQuery(window).scrollTop();
                var height = $this.closest( '.nf-setting' ).find( '.note-editor' ).outerHeight();
            } else {
                var posY = $this.offset().top - jQuery(window).scrollTop();
                var height = $this.outerHeight();
            }

            // Find out if merge tag box will go below bottom of the page.
	        var tagBoxY = posY + height;
	        var windowHeight = window.innerHeight;
	        var tagBoxHeight = jQuery( '#merge-tags-box' ).outerHeight();

	        // If merge tag box will render below the bottom of the page,
            // change it to render above the field

	        if ( ( tagBoxY + tagBoxHeight ) > windowHeight ) {
		        tagBoxY = posY - tagBoxHeight;
	        }

            if ( 0 > tagBoxY ) {
                tagBoxY = posY;
            }

            jQuery( '#merge-tags-box' ).css( 'top', tagBoxY );

            var repeaterRow = $this.closest( '.nf-list-options-tbody' );
            if( 0 != repeaterRow.length ) {
                var left = repeaterRow.offset().left - jQuery(window).scrollLeft();
                jQuery( '#merge-tags-box' ).css( 'left', left );
            } else if( 'rte' == type ) {
                var posX = $this.closest( '.nf-setting' ).find( '.note-editor' ).offset().left - jQuery(window).scrollLeft();
                jQuery( '#merge-tags-box' ).css( 'left', posX );
                jQuery( '#merge-tags-box' ).css( 'width', $this.closest( '.nf-setting' ).find( '.note-editor' ).width() );
            }
            else
            {
                var posX = jQuery( this ).closest( '.nf-settings' ).offset().left - jQuery(window).scrollLeft();
                jQuery( '#merge-tags-box' ).css( 'left', posX );
                jQuery( '#merge-tags-box' ).css( 'width', $this.closest( '.nf-settings' ).width() );
            }

            var dataID = jQuery( this ).data( 'id' );
            if( dataID && 'eq' != dataID ) return;

            // var offset = jQuery( view.el ).find( '.setting' ).parent().outerHeight();
            // jQuery( view.el ).find( '.setting' ).parent().append( jQuery( '#merge-tags-box' ) );
            // jQuery( '#merge-tags-box' ).css( 'top', offset );
        },

        keyupCallback: function( event, target, type ){
            var type = type || 'setting';

            if( /* ENTER */ 13 == event.keyCode ){

                // Get top listed merge tag.
                var firstFilteredTag = jQuery( '#merge-tags-box .merge-tag-list ul li span' ).first().data( 'tag' );

                nfRadio.channel( 'mergeTags' ).request( 'insert:tag', firstFilteredTag );

                // COPIED FROM BELOW
                jQuery( '#merge-tags-box' ).css( 'display', 'none' );
                nfRadio.channel( 'drawer' ).request( 'enable:close' );
                jQuery( '#merge-tags-box' ).removeClass();
                jQuery( '.merge-tag-focus' ).removeClass( 'merge-tag-focus' );
                jQuery( '.merge-tag-focus-overlay' ).removeClass( 'merge-tag-focus-overlay' );

                return;
            }

            // Get the value.
            // var value = jQuery( summernote ).summernote( 'code' );
            // Update the value.
            // jQuery( summernote ).closest( '.nf-setting' ).find( '.note-editable' ).html( value );

            if( 'undefined' != typeof target ) {
                var $this = jQuery(target);
            } else {
                var $this = jQuery( this );
            }

            // TODO: Disable Browser Autocomplete
            // $this.attr()


            var dataID = jQuery( this ).data( 'id' );
            if( dataID && 'eq' == dataID ) return;

            // Store the current caret position.
            if( 'rte' == type ){
                var range = $this.summernote('createRange');
                if( range ) {
                    var caretPos = range.so; // or .eo?
                } else {
                    var caretPos = 0;
                }
                $this.closest( '.nf-setting' ).find( '.setting' ).summernote( 'saveRange' );
            } else {
                var caretPos = $this.caret();
            }
            nfRadio.channel( 'mergeTags' ).request( 'set:caret', caretPos );

            // Find merge tags.
            if( 'rte' == type ) {
                var mergetags = $this.summernote( 'code' ).match(new RegExp(/{([a-zA-Z0-9]|:|_|-|})*/g));
            } else {
                var mergetags = $this.val().match(new RegExp(/{([a-zA-Z0-9]|:|_|-|})*/g));
            }

            // Filter out closed merge tags.
            mergetags = _.filter(mergetags, function(mergetag) {
                return -1 == mergetag.indexOf( '}' ); // Filter out "closed" merge tags.
            });

            // If an open merge tag is found, show the Merge Tag Box, else hide.
            if( 0 !== mergetags.length ) {

                nfRadio.channel( 'mergeTags' ).request( 'set:old', mergetags[0] );
                
                jQuery('#merge-tags-box').css( 'display', 'block' );
                nfRadio.channel( 'drawer' ).request( 'prevent:close' );
                $this.addClass('merge-tag-focus');

                var boxHeight = jQuery( '#merge-tags-box' ).outerHeight();
                jQuery( '#nf-drawer' ).css( 'padding-bottom', boxHeight + 'px' );

                // Disable browser autocomplete.
                var autocomplete = $this.attr( 'autocomplete' );
                $this.attr( 'autocomplete', 'off' );
                $this.data( 'autocomplete', autocomplete );

                var $overlayElement = $this.closest( '.nf-setting, .nf-table-row' );
                if( 0 != $overlayElement.find( '.note-editor' ).length ){
                    $overlayElement.find('.note-editor' ).addClass('merge-tag-focus-overlay');
                } else {
                    $overlayElement.addClass('merge-tag-focus-overlay');
                }

                $overlayElement.off( 'click' );
                $overlayElement.on( 'click', function( event ){
                    var elementClasses = jQuery( event.target ).attr( 'class' ) || [];
                    if( -1 !== elementClasses.indexOf( 'merge-tag-focus-overlay' ) ){
                        nfRadio.channel( 'mergeTags' ).request( 'insert:tag', '' );
                        jQuery( '#merge-tags-box' ).css( 'display', 'none' );
                        nfRadio.channel( 'drawer' ).request( 'enable:close' );
                        jQuery( '#merge-tags-box' ).removeClass();
                        jQuery( '.merge-tag-focus' ).removeClass( 'merge-tag-focus' );
                        jQuery( '.merge-tag-focus-overlay' ).removeClass( 'merge-tag-focus-overlay' );
                    }
                });

                var value = mergetags[0].replace( '{', '' );
            } else {
                jQuery( '#merge-tags-box' ).css( 'display', 'none' );
                nfRadio.channel( 'drawer' ).request( 'enable:close' );
                jQuery( '#merge-tags-box' ).removeClass();
                jQuery( '.merge-tag-focus' ).removeClass( 'merge-tag-focus' );
                jQuery( '.merge-tag-focus-overlay' ).removeClass( 'merge-tag-focus-overlay' );
            }
        }

    } );

    return controller;
} );

/**
 * Listens to our app channel for settings views being rendered.
 *
 * If we're about to render a setting model that's a select and has 'fields' as the 'fill' setting, add all our field models to its options.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/itemSettingFill',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen for messages that are fired before a setting view is rendered.
			this.listenTo( nfRadio.channel( 'app' ), 'before:renderSetting', this.beforeRenderSetting );
		},

		beforeRenderSetting: function( settingModel, dataModel ) {
			if ( 'fields' == settingModel.get( 'fill' ) ) {
				
			}
		}

	});

	return controller;
} );
/**
 * Modify the user's browser history when they click on a domain
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/confirmPublish',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'app' ), 'click:confirmPublish', this.confirmPublish );
		},

		confirmPublish: function() {
			var formModel = nfRadio.channel( 'app' ).request( 'get:formModel' );
			// Check to see if we need to add a submit button.
			if ( 1 == formModel.get( 'settings' ).get( 'add_submit' ) ) {
				nfRadio.channel( 'fields' ).request( 'add', { type: 'submit', label: 'Submit', order: 9999 } );
			}
			formModel.set( 'show_publish_options', false );
			nfRadio.channel( 'app' ).request( 'update:db', 'publish' );
		}

	});

	return controller;
} );
/**
 * Handles actions related to settings that utilise the Rich Text Editor
 *
 * @package Ninja Forms builder
 * @subpackage App - Settings Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/rte',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// We don't want the RTE setting to re-render when the value changes.
			nfRadio.channel( 'setting-type-rte' ).reply( 'renderOnChange', function(){ return false } );

			this.listenTo( nfRadio.channel( 'rte' ), 'init:settingModel', this.initSettingModel );

			// When an RTE setting is shown, re-render RTE.
			this.listenTo( nfRadio.channel( 'setting-type-rte' ), 'render:setting', this.renderSetting );

			// When an RTE setting view is destroyed, remove our RTE.
			this.listenTo( nfRadio.channel( 'setting-type-rte' ), 'destroy:setting', this.destroySetting );

			// When an element within the RTE is clicked, check to see if we should insert a link.
			this.listenTo( nfRadio.channel( 'setting-type-rte' ), 'click:extra', this.clickExtra );

			// Instantiates the variable that holds the media library frame.
			this.meta_image_frame;

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
		      }

		      this.currentContext = {};
		},

		initSettingModel: function( settingModel ) {
			settingModel.set( 'hide_merge_tags', true );
		},

		initRTE: function( settingModel, dataModel, settingView ) {
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
			var mergeTags = this.mergeTags();

			var toolbar = [
				[ 'paragraphStyle', ['style'] ],
				[ 'fontStyle', [ 'bold', 'italic', 'underline','clear' ] ],
				[ 'lists', [ 'ul', 'ol' ] ],
			    [ 'paragraph', [ 'paragraph' ] ],
			    [ 'customGroup', [ 'linkButton', 'unlink' ] ],
			    [ 'table', [ 'table' ] ],
			    [ 'actions', [ 'undo', 'redo' ] ],
			    [ 'tools', [ 'mediaButton', 'mergeTags', 'codeview' ] ]
			];

			jQuery( settingView.el ).find( 'div.setting' ).summernote( {
				toolbar: toolbar,
				buttons: {
					linkButton: linkButton,
					mergeTags: mergeTags,
					mediaButton: mediaButton
				},
				height: 150,   //set editable area's height
				codemirror: { // codemirror options
				    theme: 'monokai',
				    lineNumbers: true,
                    lineWrapping: true,
				    callbacks: {
				    	onBlur: function( editor ) {
				    		var value = editor.getValue();
				    		that.updateDataModel( settingModel, dataModel, value );
				    	}
				    }
				},
				prettifyHtml: true,
				callbacks: {
					onBlur: function( e, context ) {
						var value = jQuery( this ).summernote( 'code' );
						that.updateDataModel( settingModel, dataModel, value );
                        nfRadio.channel( 'summernote' ).trigger( 'blur', settingModel, dataModel, value );
					},
                    onFocus: function( e, context ) {
                        nfRadio.channel( 'summernote' ).trigger( 'focus', e, this, context );
                    },
                    onKeydown: function( e, context ) {
                        nfRadio.channel( 'summernote' ).trigger( 'keydown', e, this, context );
                    },
                    onKeyup: function( e, context ) {
                        nfRadio.channel( 'summernote' ).trigger( 'keyup', e, this, context );
					}
				}
			} );
		},

		updateDataModel: function( settingModel, dataModel, value ) {
			var name = settingModel.get( 'name' );
			var before = dataModel.get( name );
			var after = value;

			var changes = {
				attr: name,
				before: before,
				after: after
			}

			var label = {
				object: dataModel.get( 'objectType' ),
				label: dataModel.get( 'label' ),
				change: 'Changed ' + settingModel.get( 'label' ) + ' from ' + before + ' to ' + after
			};

			nfRadio.channel( 'changes' ).request( 'register:change', 'changeSetting', dataModel, changes, label );

			dataModel.set( settingModel.get( 'name' ), after );
		},

		renderSetting: function( settingModel, dataModel, settingView ) {
			this.initRTE( settingModel, dataModel,settingView );
			var linkMenu = jQuery( settingView.el ).find( '.link-button' ).next( '.dropdown-menu' ).find( 'button' );
			linkMenu.replaceWith(function () {
			    return jQuery( '<div/>', {
			        class: jQuery( linkMenu ).attr( 'class' ),
			        html: this.innerHTML
			    } );
			} );
		},

		destroySetting: function( settingModel, dataModel, settingView ) {
			this.removeRTE( settingModel, dataModel, settingView );
		},

		removeRTE: function( settingModel, dataModel, settingView ) {
			jQuery( settingView.el ).find( 'div.setting' ).summernote( 'destroy' );
		},

		drawerOpened: function( settingModel, dataModel, settingView ) {
			this.initRTE( settingModel, dataModel, settingView );
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
	            tooltip: 'Insert Link',
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

		mergeTags: function( context ) {
			var ui = jQuery.summernote.ui;
			var mergeTagsButton = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-rte-merge-tags-button' );
			return ui.button({
				className: 'dropdown-toggle merge-tags',
				contents: mergeTagsButton({}),
				tooltip: 'Merge Tags'
			}).render();
		},

		mediaButton: function( context ) {
			var that = this;
			var ui = jQuery.summernote.ui;
			var mediaButton = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-rte-media-button' );
			return ui.button({
	            className: 'dropdown-toggle',
	            contents: mediaButton({}),
	            tooltip: 'Insert Media',
	            click: function( e ) {
	            	that.openMediaManager( e, context );
	            }
	          }).render();
		},

		openMediaManager: function( e, context ) {
			context.invoke( 'editor.createRange' );
			context.invoke( 'editor.saveRange' );
			this.currentContext = context;
			
			// If the frame already exists, re-open it.
			if ( this.meta_image_frame ) {
				this.meta_image_frame.open();
				return;
			}

			// Sets up the media library frame
			this.meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
				title: 'Select a file',
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

		clickExtra: function( e, settingModel, dataModel, settingView ) {
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
			this.currentContext.invoke( 'editor.restoreRange' );
			if ( 'image' == media.type ) {
				this.currentContext.invoke( 'editor.insertImage', media.url );
			} else {
				this.currentContext.invoke( 'editor.createLink', {
					text: media.title || media.filename,
					url: media.url
				} );
			}

		}
	});

	return controller;
} );

/**
 * Listens to our app channel for settings views being rendered.
 *
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/settingFieldSelect',[], function() {
    var controller = Marionette.Object.extend( {

        initialize: function() {

            // Bind field key listener to field-select setting type.
            this.listenTo( nfRadio.channel( 'field-select' ), 'init:settingModel', this.trackKeyChanges );

            // The first time settingModel and the dataModel meet.
            this.listenTo( nfRadio.channel( 'setting-type-field-select' ), 'before:renderSetting', this.beforeRender );

            // Add setting change listener only in drawers with a field-select setting.
            this.listenTo( nfRadio.channel( 'field-select' ), 'init:settingModel', function() {
                this.listenTo( nfRadio.channel( 'app' ), 'change:setting', this.maybeSwitchToFieldsDomain );
            });

            this.listenTo( nfRadio.channel( 'app' ), 'change:currentDomain', this.autoOpenDrawer );

            this.listenTo( nfRadio.channel( 'drawer' ), 'opened', this.filterDrawerContents );
            this.listenTo( nfRadio.channel( 'drawer' ), 'closed', this.SwitchToFieldsDomain );
        },

        trackKeyChanges: function( settingModel ) {
            settingModel.listenTo( nfRadio.channel( 'app' ), 'update:fieldKey', settingModel.updateKey );

            // Update selected field if the selected field's key changes.
            this.listenTo( nfRadio.channel( 'app' ), 'replace:fieldKey', this.updateFieldMap );
        },

        updateFieldMap: function( dataModel, keyModel, settingModel ) {

            var oldKey = keyModel._previousAttributes[ 'key' ];
            var newKey = keyModel.get( 'key' );

            if( 'field-select' == settingModel.get( 'type' ) && dataModel.get( settingModel.get( 'name' ) ) == oldKey ) {

                dataModel.set( settingModel.get( 'name' ), newKey );
            }
        },

        beforeRender: function( settingModel, dataModel ) {

            var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );

            var fieldTypes = settingModel.get( 'field_types' );

            var options = [
                {
                    label: '--',
                    value: 0
                }
            ];
            _.each( fieldCollection.models, function( field ){

                if( dataModel.cid == field.cid ) return;

                if( 'undefined' != typeof fieldTypes && 0 != fieldTypes.length && ! _.contains( fieldTypes, field.get( 'type' ) ) ) return;

                var fieldFilter = settingModel.get( 'field_filter' );
                if( fieldFilter && 'undefined' != typeof fieldFilter[ field.get( 'type' ) ] ) {
                    var bail = false;
                    _.each( fieldFilter[ field.get( 'type' ) ], function( value, setting ){
                        console.log( value + ":" + field.get( setting )  );
                        if( value != field.get( setting ) ) bail = true;
                    } );
                    if( bail ) return;
                }

                var value = field.get( 'key' );
                switch ( settingModel.get( 'field_value_format' ) ) {
                    case 'key':
                        value = field.get( 'key' );
                        break;
                    case 'merge_tag':
                    default:
                        value = '{field:' + field.get( 'key' ) + '}';
                }

                options.push({
                    label: field.get( 'label' ),
                    value: value
                });
            });

            if( 'undefined' != typeof fieldTypes && 0 != fieldTypes.length ) {
                _.each( fieldTypes, function( fieldType ){

                    var fieldTypeModel = nfRadio.channel( 'fields' ).request( 'get:type', fieldType );

                    options.push({
                        label: '-- Add ' + fieldTypeModel.get( 'nicename' ) + ' Field',
                        value: 'addField:' + fieldType,
                    });
                } );
            }

            settingModel.set( 'options', options );
        },

        maybeSwitchToFieldsDomain: function( e, model, dataModel ) {

            if( 'field-select' != model.get( 'type' ) ) return;

            var name = model.get( 'name' );
            var value = dataModel.get( name );

            if( ! value ) return;

            var rubble = value.split( ':' );

            if( 'addField' != rubble[0] ) return;

            this.openDrawer = 'addField';
            this.filterDrawer = rubble[1];

            dataModel.set( name, '' );

            this.switchDomain = true;
            nfRadio.channel( 'app' ).request( 'close:drawer' );
        },

        SwitchToFieldsDomain: function() {
            if( this.switchDomain ) {
                var fieldDomainModel = nfRadio.channel( 'app' ).request( 'get:domainModel', 'fields' );
                nfRadio.channel('app').request('change:currentDomain', null, fieldDomainModel);
                this.switchDomain = null;
            }
        },

        autoOpenDrawer: function() {
            if( this.openDrawer ) {
                nfRadio.channel( 'app' ).request( 'open:drawer', this.openDrawer );
                this.openDrawer = null;
            }
        },

        filterDrawerContents: function() {
            if( this.filterDrawer ) {
                nfRadio.channel('drawer-addField').trigger('change:filter', this.filterDrawer);
                this.filterDrawer = null;
            }
        }
    });

    return controller;
} );
/**
 * The Field List setting is a container of settings (like the Fieldset setting), in which its children are instantiated.
 * Unlike the Fieldset setting, Field List settings are dynamically created based on the list of form fields.
 *
 * Note: Field references in the dynamic setting names are based on field keys, which may change.
 * Unlike regular field key tracking, a new setting needs to be created with the same value as the previous.
 *
 * @package Ninja Forms builder
 * @subpackage Action Settings
 * @copyright (c) 2016 WP Ninjas
 * @author Kyle B. Johnson
 * @since 3.0
 */
define( 'controllers/app/settingFieldList',['views/app/drawer/typeSettingFieldset','models/app/settingCollection'], function( fieldsetView, settingCollection ) {
    return Marionette.Object.extend( {

        /**
         * A reference list of Field List setting models.
         */
        fieldListSettings: [],

        initialize: function() {
            this.listenTo( nfRadio.channel( 'field-list' ),       'init:settingModel',    this.registerFieldListSettings  );
            this.listenTo( nfRadio.channel( 'fields' ),           'update:setting',       this.updateFieldListSettingKeys );
                           nfRadio.channel( 'field-list' ).reply( 'get:settingChildView', this.getSettingChildView, this  );
        },

        /**
         * Build a reference list of Field List setting models for later reference.
         *
         * @param settingModel
         */
        registerFieldListSettings: function( settingModel ){
            this.fieldListSettings.push( settingModel.get( 'name' ) );
        },

        /**
         * Field List settings contain field keys in the setting names.
         * When a field key changes, so too must the Field List setting name.
         *
         * @param fieldModel
         */
        updateFieldListSettingKeys: function( fieldModel ){

            // We are only interested in field key changes.
            if( 'undefined' == typeof fieldModel.changed.key ) return;

            var oldKey = fieldModel._previousAttributes.key;
            var newKey = fieldModel.changed.key;

            /*
             * This is an absolute (functional) mess of nesting. I apologize to my future self, or Kenny.
             *
             * Each setting of each action model must be checked against each registered Field List setting.
             */
            var that = this;
            _.each( Backbone.Radio.channel( 'actions' ).request( 'get:collection' ).models, function( actionModel ) {
                _.each( actionModel.attributes, function( value, setting ) {
                    var lastChanged = ''; // Used to avoid resetting the change with a duplicate call.
                    _.each( that.fieldListSettings, function( prefix ) {
                        if( setting != prefix + '-' + oldKey || lastChanged == oldKey ) return;
                        var oldValue = actionModel.get( prefix + '-' + oldKey );
                        actionModel.set( prefix + '-' + newKey, oldValue );
                        actionModel.set( prefix + '-' + oldKey, 0 );
                        lastChanged = oldKey;
                    });
                });
            });
        },

        /**
         * Set the view for Field List sub-settings, just like the Fieldset setting.
         *
         * @param settingModel
         * @returns {*}
         */
        getSettingChildView: function( settingModel ) {

            /**
             * Dynamically build field-list settings as needed for the view.
             */

            // Filter fields based on the field_types setting property.
            var fields = _.filter( nfRadio.channel( 'fields' ).request( 'get:collection' ).models, function( field ) {
                return _.contains( settingModel.get( 'field_types' ), field.get( 'type' ) );
            });

            // Map fields into setting definitions.
            var settings = _.map( fields, function( field ) {
                return {
                    name: settingModel.get( 'name' ) + '-' + field.get( 'key' ),
                    type: 'toggle',
                    label: field.get( 'label' ),
                    width: 'full'
                };
            });

            settingModel.set( 'settings', new settingCollection( settings ) );

            // return the child view.
            return fieldsetView;
        },

    });
} );

/**
 * Listens to our app channel for settings views being rendered.
 *
 *
 *
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/settingHTML',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {

            // The first time settingModel and the dataModel meet.
            this.listenTo( nfRadio.channel( 'setting-type-html' ), 'before:renderSetting', this.init );
        },

        init: function( settingModel, dataModel ) {

            if( 'undefined' == settingModel.get( 'mirror' ) ) return;

            // Listen to a setting change inside of the dataModel.
            dataModel.on( 'change:' + settingModel.get( 'mirror' ), this.update, settingModel );
        },

        update: function( dataModel, changedSettingValue ) {

            // Mirror the default value setting value.
            dataModel.set( this.get( 'name' ), changedSettingValue );
        }
    });

    return controller;
} );
/**
 * Listens to our app channel for settings views being rendered.
 *
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/settingColor',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            // We don't want to re-render this setting type when the data changes.
            nfRadio.channel( 'setting-type-color' ).reply( 'renderOnChange', this.setRenderFalse );
            // We want to close any color pickers before we close our styling tab or drawer.
            this.listenTo( nfRadio.channel( 'setting-type-color' ), 'destroy:setting', this.closeColorPickers );

            // The first time settingModel and the dataModel meet.
            this.listenTo( nfRadio.channel( 'setting-type-color' ), 'render:setting', this.initColorPicker );
        },

        initColorPicker: function( settingModel, dataModel, view ) {

            var name = settingModel.get( 'name' );
            var el = jQuery( view.el ).find( 'input' );

            jQuery( el ).wpColorPicker( {
                change: function( event, ui ){
                    nfRadio.channel( 'app' ).request( 'change:setting', event, settingModel, dataModel, ui.color.toString() );
                }
            } );
        },

        setRenderFalse: function() {
            return false;
        },

        closeColorPickers: function( settingModel, dataModel, view ) {
            jQuery( view.el ).find( '.wp-color-picker' ).wpColorPicker( 'close' );
        }
    });

    return controller;
} );
/**
 * Listens to our app channel for the app to start.
 *
 * If the form is a new form, then highlight the Add New submenu item.
 * Otherwise, append an Edit Form submenu for context.
 *
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2016 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/changeMenu',[], function() {
    var controller = Marionette.Object.extend({

        editFormText: '',

        initialize: function () {
            this.editFormText = nfAdmin.editFormText || 'Edit Form';
            this.listenTo(nfRadio.channel('app'), 'after:appStart', this.changeMenu);
            this.listenTo( nfRadio.channel( 'app' ), 'response:updateDB', this.formPublish );
        },

        changeMenu: function () {
            var form = nfRadio.channel( 'app' ).request( 'get:formModel' );

            if ( this.isNewForm( form.id ) ) {
                this.highlightAddNew();
            } else {
                this.appendEditForm();
            }
        },

        isNewForm: function( form_id ) {
            return isNaN( form_id );
        },

        highlightAddNew: function() {
            jQuery( '.wp-submenu li' ).removeClass( 'current' );
            jQuery( 'a[href="admin.php?page=ninja-forms&form_id=new"]' ).parent().addClass( 'current' );
        },

        /**
         * Append 'Edit Form'
         * When editing a form, add an 'Edit Form' submenu item to
         *   the WordPress Admin Dashboard menu, specifically under
         *   the Ninja Forms Menu Item and after the 'Add New' item.
         */
        appendEditForm: function() {
            // Singleton check. Only add this menu item one time.
            if ( jQuery( 'li a:contains("' + this.editFormText + '")' ).length > 0 ) return;

            var editFormLinkText, editFormLink, editFormListItem;

            // Create the 'Edit Form' submenu item.
            editFormLinkText = document.createTextNode(this.editFormText);
            editFormLink = document.createElement("a");
            editFormLink.appendChild(editFormLinkText);

            editFormListItem = document.createElement("li");
            editFormListItem.appendChild(editFormLink);
            editFormListItem.classList.add("current");

            // Remove the `current` class from any existing list items.
            jQuery( '.wp-submenu li' ).removeClass( 'current' );

            // Insert the 'Edit Form' item after the 'Add New' item;
            jQuery( 'a[href="admin.php?page=ninja-forms#new-form"]' ).parent().after( editFormListItem );
        },

        formPublish: function( response ) {
            if ( 'publish' !== response.action ) return false;
            this.changeMenu();
        }
    });

    return controller;
});

/**
 * When we click on a domain link, close the mobile menu.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/mobile',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen for clicks on our app menu.
			this.listenTo( nfRadio.channel( 'app' ), 'click:menu', this.closeMobileMenu );
		},

		closeMobileMenu: function() {
			var builderEl = nfRadio.channel( 'app' ).request( 'get:builderEl' );
			jQuery( builderEl ).removeClass( 'nf-menu-expand' );
		}

	});

	return controller;
} );
/**
 * Add a jBox notice to the screen.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/notices',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			nfRadio.channel( 'notices' ).reply( 'add', this.addNotice, this );
			nfRadio.channel( 'notices' ).reply( 'close', this.closeNotice, this );
			this.notices = {};
		},

		addNotice: function( key, msg, options ) {

			var appDefaults = {
				content: msg,
				color: 'green',
				zIndex:10000000,
				constructOnInit: true,
				stack: true,
				animation: {
					open: 'flip',
					close: 'flip'
				}
			};

			var mobileDefaults = {
				position: {
					x: 'center',
					y: 'top'
				},
				animation: {
					open:'slide:top',
					close:'slide:left'
				},
				autoClose: 2000,
				offset: {
					x: 0,
					y: 55
				}
			};

			var desktopDefaults = {
				attributes: {
					x: 'left',
					y: 'bottom'
				},
				autoClose: 4000
			};

			if ( nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
				var defaults = mobileDefaults;	
			} else {
				var defaults = desktopDefaults;
			}
			defaults = jQuery.extend( defaults, appDefaults );

			var options = jQuery.extend( defaults, options );
			// console.log( options );
			this.notices[ key ] = new jBox( 'Notice', options );
		},

		closeNotice: function( key ) {
			if ( 'undefined' != typeof this.notices[ key ] ) {
				this.notices[ key ].close();
			}
		},

		openNotice: function( key ) {
			if ( 'undefined' != typeof this.notices[ key ] ) {
				this.notices[ key ].open();
			}
		}

	});

	return controller;
} );
/**
 * Prompt the user to save if they attempt to leave the page with unsaved changes.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2016 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/unloadCheck',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			jQuery( window ).bind( 'beforeunload', this.maybePrompt );
		},

		maybePrompt: function( model ) {
			// If our app is clean, don't show a warning.
			if ( ! nfRadio.channel( 'app' ).request( 'get:setting', 'clean' ) ) {
				return 'You have unsaved changes.';
			}
		}

	});

	return controller;
} );
/**
 * Before we save data to the database (on preview update or publish), we check to see if we have anyone
 * that wants to update the 'formContent' form setting. This setting is used on the front-end to allow
 * for custom display of form fields. i.e. layout rows.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/formContentFilters',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			/*
			 * Init our formContent view filter array.
			 */
			this.viewFilters = [];
			this.saveFilters = [];
			this.loadFilters = [];

			/*
		     * Listen for requests to add formContent filters.
			 */

			nfRadio.channel( 'formContent' ).reply( 'add:viewFilter', this.addViewFilter, this );
			nfRadio.channel( 'formContent' ).reply( 'add:saveFilter', this.addSaveFilter, this );
			nfRadio.channel( 'formContent' ).reply( 'add:loadFilter', this.addLoadFilter, this );

			/*
			 * Listen for requests to get our formContent filters.
			 */
			nfRadio.channel( 'formContent' ).reply( 'get:viewFilters', this.getViewFilters, this );
			nfRadio.channel( 'formContent' ).reply( 'get:saveFilters', this.getSaveFilters, this );
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
			nfRadio.channel( 'fieldContents' ).reply( 'add:saveFilter', this.addSaveFilter, this );
			nfRadio.channel( 'fieldContents' ).reply( 'add:loadFilter', this.addLoadFilter, this );

			/*
			 * Listen for requests to get our fieldContent filters.
			 */
			nfRadio.channel( 'fieldContents' ).reply( 'get:viewFilters', this.getViewFilters, this );
			nfRadio.channel( 'fieldContents' ).reply( 'get:saveFilters', this.getSaveFilters, this );
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

		addSaveFilter: function( callback, priority ) {
			this.saveFilters[ priority ] = callback;
		},

		getSaveFilters: function() {
			return this.saveFilters;
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
/**
 * Handles filters for our main content gutter views.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/formContentGutterFilters',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			/*
			 * Init our gutter view filter array.
			 */
			this.leftFilters = [];
			this.rightFilters = [];
			/*
		     * Listen for requests to add gutter filters.
			 */
			nfRadio.channel( 'formContentGutters' ).reply( 'add:leftFilter', this.addLeftFilter, this );
			nfRadio.channel( 'formContentGutters' ).reply( 'add:rightFilter', this.addRightFilter, this );

			/*
			 * Listen for requests to get our content gutter filters.
			 */
			nfRadio.channel( 'formContentGutters' ).reply( 'get:leftFilters', this.getLeftFilters, this );
			nfRadio.channel( 'formContentGutters' ).reply( 'get:rightFilters', this.getRightFilters, this );
		},

		addLeftFilter: function( callback, priority ) {
			this.leftFilters[ priority ] = callback;
		},

		addRightFilter: function( callback, priority ) {
			this.rightFilters[ priority ] = callback;
		},

		getLeftFilters: function() {
			return this.leftFilters;
		},

		getRightFilters: function() {
			return this.rightFilters;
		}

	});

	return controller;
} );
/**
 * Returns a clone of a backbone collection with all the models' attributes looped through so that collections contained within are propely cloned.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/cloneCollectionDeep',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			nfRadio.channel( 'app' ).reply( 'clone:collectionDeep', this.cloneCollectionDeep, this );
		},

		cloneCollectionDeep: function( collection ) {
			var models = [];
			// Loop through every model in our collection, clone it, and add it to our model array
			_.each( collection.models, function( model ) {
				var newModel = nfRadio.channel( 'app' ).request( 'clone:modelDeep', model );
				models.push( newModel );
			} );
			// Create a new instance of our collection
			return new collection.constructor( models, collection.options );
		}
	});

	return controller;
} );
/**
 * Tracks which keys have been pressed.
 * Currently only used by fields to see if they should duplicate or delete on click.
 * (Shift + D + click = delete) (Shift + C + click = duplicate)
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - Edit Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/trackKeyDown',[], function() {
	var controller = Marionette.Object.extend( {
		keys: [],

		initialize: function() {
			var that = this;
			/*
			 * Track keydowns and store the keys pressed.
			 */
			
			jQuery( document ).on( 'keydown', function( e ) {
				that.keyDown( e, that );
			} );

			jQuery( document ).on( 'keyup', function( e ) {
				that.keyUp( e, that );
			} );

			/*
			 * Get the keys currently being pressed, if any
			 */
			nfRadio.channel( 'app' ).reply( 'get:keydown', this.getKeyDown, this );
		},

		keyDown: function( e, context ) {
			/*
			 * Add our keycode to our keys array.
			 */
			context.keys[ e.keyCode ] = e.keyCode;
		},

		keyUp: function( e, context ) {
			/*
			 * Remove our keycode from our keys array.
			 */
			if ( -1 != context.keys.indexOf( e.keyCode ) ) {
				delete context.keys[ e.keyCode ];
			}
		},

		getKeyDown: function() {
			return this.keys;
		}
	});

	return controller;
} );
/**
 * Initialize the perfectscroll jQuery plugin
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/perfectScroll',[], function() {
	var controller = Marionette.Object.extend( {
		movedPos: false,

		initialize: function() {
			/*
			 * When we init the main view, init our perfectscroll
			 */
			this.listenTo( nfRadio.channel( 'main' ), 'show:main', this.initPerfectScroll );

			/*
			 * When our drawer opens and closes, change the position of our scroll rail.
			 */
			this.listenTo( nfRadio.channel( 'drawer' ), 'opened', this.moveRail );
			this.listenTo( nfRadio.channel( 'drawer' ), 'before:closeDrawer', this.resetRail );
		},

		initPerfectScroll: function( view ) {
			if ( ! nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
				jQuery( view.el ).parent().perfectScrollbar( {
					suppressScrollX: true
				} );
			}

			jQuery( 'head' ).append( '<style id="ps-scrollbar-css" type="text/css"></style>' );
		},

		moveRail: function() {
			var drawerEl = nfRadio.channel( 'app' ).request( 'get:drawerEl' );
			var movedPos = jQuery( drawerEl ).outerWidth();

			jQuery( '#ps-scrollbar-css' ).text( '.ps-scrollbar-moved { right: ' + movedPos + 'px !important; } ' );
			jQuery( '#nf-main .ps-scrollbar-y-rail' ).addClass( 'ps-scrollbar-moved ' );
			
		},

		resetRail: function() {
			jQuery( '.ps-scrollbar-y-rail' ).removeClass( 'ps-scrollbar-moved ' );
		}

	});

	return controller;
} );
/**
 * Returns a new setting group collection.
 * Used to settings drawers for custom data models (i.e. not fields, actions, or advanced)
 * 
 * @package Ninja Forms builder
 * @subpackage App - Edit Settings Drawer
 * @copyright (c) 2016 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/getNewSettingGroupCollection',[ 'models/app/settingGroupCollection' ], function( SettingGroupCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Respond to requests for a new setting group collection
			nfRadio.channel( 'app' ).reply( 'get:settingGroupCollectionDefinition', this.getNewSettingGroupCollection, this );
		},

		/**
		 * Return a new instance of the setting group collection.
		 *
		 * @since  3.0
		 * @return backbone.collection
		 */
		getNewSettingGroupCollection: function() {
			return SettingGroupCollection;
		}

	});

	return controller;
} );
/**
 *
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2017 WP Ninjas
 * @since 3.0.30
 */
define( 'controllers/app/settingMedia',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            // When the media button is clicked, open the media manager.
            this.listenTo( nfRadio.channel( 'setting-type-media' ), 'click:extra', this.clickExtra );
        },

        clickExtra: function( e, settingModel, dataModel, settingView ) {
            var textEl = jQuery( e.target ).parent().find( '.setting' );

            if ( jQuery( e.target ).hasClass( 'open-media-manager' ) ) {
                // If the frame already exists, re-open it.
                if ( this.meta_image_frame ) {
                    this.meta_image_frame.open();
                    return;
                }

                // Sets up the media library frame
                this.meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
                    title: 'Select a file',
                    button: { text:  'insert' }
                });

                var that = this;

                // Runs when an image is selected.
                this.meta_image_frame.on('select', function(){
                    // Grabs the attachment selection and creates a JSON representation of the model.
                    var media_attachment = that.meta_image_frame.state().get('selection').first().toJSON();
                    textEl.val( media_attachment.url ).change();
                });

                // Opens the media library frame.
                this.meta_image_frame.open();
            }
        },
    });

    return controller;
} );
/**
 * Handles changing our public link when we request a new one or when it's set improperly.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2019 WP Ninjas
 * @since UPDATE_VERSION_ON_MERGE
 */
define( 'controllers/app/publicLink',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'app' ), 'after:appStart', this.validatePublicLink, this );
            nfRadio.channel( 'app' ).reply( 'generate:publicLinkKey', this.newPublicLinkKey, this );
        },
        
        newPublicLinkKey: function() {
            var formSettingsDataModel = nfRadio.channel( 'settings' ).request( 'get:settings' );
            var public_link_key = nfRadio.channel('app').request('get:formModel').get('id');
            for (var i = 0; i < 4; i++) {
                var char = Math.random().toString(36).slice(-1);
                public_link_key += char;
            };
            // Apply the public link key to form settings
            formSettingsDataModel.set('public_link_key', public_link_key);
            return public_link_key;
        },

        validatePublicLink: function() {
            var formID = nfRadio.channel('app').request('get:formModel').get('id');
            var formSettingsDataModel = nfRadio.channel( 'settings' ).request( 'get:settings' );
            if ( 'undefined' === typeof formSettingsDataModel.get('public_link_key') ) return false;
            if ( 0 === formSettingsDataModel.get( 'public_link_key' ).indexOf( formID ) ) return false;
            var public_link_key = this.newPublicLinkKey();
            var publicLink = nfAdmin.publicLinkStructure.replace('[FORM_ID]', public_link_key);
            formSettingsDataModel.set('public_link', publicLink);
        }

	});

	return controller;
} );
/**
 * Model that represents our field type section on the add new field drawer.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/fields/typeSectionModel',[], function() {
	var model = Backbone.Model.extend( {
		defaults: {
			classes: ''
		}
	} );
	
	return model;
} );
/**
 * Collection that holds our field models.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/fields/typeSectionCollection',['models/fields/typeSectionModel'], function( typeSectionModel ) {
	var collection = Backbone.Collection.extend( {
		model: typeSectionModel
	} );
	return collection;
} );
/**
 * Creates and stores a collection of field types. This includes all of the settings shown when editing a field.
 *
 * 1) Create our settings sections config
 * 2) Loops over our preloaded data and adds that to our field type collection
 *
 * Also responds to requests for data about field types
 *
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/types',[
		'models/app/typeCollection',
		'models/fields/typeSectionCollection'
	],
	function(
		TypeCollection,
		SectionCollection
	) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Config for our settings sections
			this.sections = new SectionCollection( fieldTypeSections );
			this.listenTo( nfRadio.channel( 'fields' ), 'init:typeModel', this.registerSection );

			// Create our field type collection
			this.collection = new TypeCollection( fieldTypeData, { type: 'fields' } );

			// Respond to requests to get field type, collection, settings, and sections
			nfRadio.channel( 'fields' ).reply( 'get:type', this.getFieldType, this );
			nfRadio.channel( 'fields' ).reply( 'get:typeCollection', this.getTypeCollection, this );
			nfRadio.channel( 'fields' ).reply( 'get:typeSections', this.getTypeSections, this );
			nfRadio.channel( 'fields' ).reply( 'get:savedFields', this.getSavedFields, this );

			// Listen to clicks on field types
			this.listenTo( nfRadio.channel( 'drawer' ), 'click:fieldType', this.addField );
		},

		registerSection: function( typeModel ) {
			if ( 'fields' != typeModel.collection.type || ! typeModel.get( 'section' ) ) return;

			this.sections.get( typeModel.get( 'section' ) ).get( 'fieldTypes' ).push( typeModel.get( 'id' ) );
		},

		/**
		 * Return a field type by id
		 *
		 * @since  3.0
		 * @param  string 			id 	field type
		 * @return backbone.model    	field type model
		 */
		getFieldType: function( id ) {
        	return this.collection.get( id );
        },

        /**
         * Return the entire field type collection
         *
         * @since  3.0
         * @param  string 				id 	[description]
         * @return backbone.collection    	field type collection
         */
		getTypeCollection: function( id ) {
        	return this.collection;
        },

        /**
         * Add a field type to our fields sortable when the field type button is clicked.
         *
         * @since 3.0
         * @param Object e event
         * @return void
         */
        addField: function( e ) {
			var type = jQuery( e.target ).data( 'id' );

			if( e.shiftKey ){
				nfRadio.channel( 'fields' ).request( 'add:stagedField', type );
				return;
			}

        	var fieldModel = nfRadio.channel( 'fields' ).request( 'add', {
				type: type,

				label: nfRadio.channel( 'fields' ).request( 'get:type', type ).get( 'nicename' )
			});

			console.log( fieldModel );

			var label = {
				object: 'Field',
				label: fieldModel.get( 'label' ),
				change: 'Added',
				dashicon: 'plus-alt'
			};

			var data = {
				collection: nfRadio.channel( 'fields' ).request( 'get:collection' )
			}

			nfRadio.channel( 'changes' ).request( 'register:change', 'addObject', fieldModel, null, label, data );

			// Re-Draw the Field Collection
			nfRadio.channel( 'fields' ).request( 'redraw:collection' );
        },

        /**
         * Return our field type settings sections
         *
         * @since  3.0
         * @return backbone.collection field type settings sections
         */
        getTypeSections: function() {
            return this.sections;
        },

        /**
         * Return our saved fields
         *
         * @since  3.0
         * @return backbone.collection
         */
        getSavedFields: function() {
        	this.sections.get( 'saved' );
        }
	});

	return controller;
} );

/**
 * Handles the logic for our field type draggables.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - New Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/fieldTypeDrag',[], function( ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen to our field type draggables and run the appropriate function.
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'startDrag:type', this.startDrag );
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'stopDrag:type', this.stopDrag );
			/*
			 * Respond to requests for our helper clone.
			 * This is used by other parts of the application to modify what the user is dragging in real-time.
			 */ 
			nfRadio.channel( 'drawer-addField' ).reply( 'get:typeHelperClone', this.getCurrentDraggableHelperClone, this );
		},

		/**
		 * When we start dragging:
		 * get our drawer element
		 * set its overflow property to visible !important -> forces the type drag element to be on at the top of the z-index.
		 * get our main element
		 * est its overflow propery to visible !important -> forces the type drag element to be on top of the z-index.
		 * set our dragging helper clone
		 * 
		 * @since  3.0
		 * @param  object context 	This function is going to be called from a draggable. Context is the "this" reference to the draggable.
		 * @param  object ui      	Object sent by jQuery UI draggable.
		 * @return void
		 */
		startDrag: function( context, ui ) {
			this.drawerEl = nfRadio.channel( 'app' ).request( 'get:drawerEl' );
			this.mainEl = nfRadio.channel( 'app' ).request( 'get:mainEl' );
			jQuery( this.drawerEl )[0].style.setProperty( 'overflow', 'visible', 'important' );

			this.draggableHelperClone = jQuery( ui.helper ).clone();

		},

		/**
		 * When we stop dragging, reset our overflow property to hidden !important.
		 * 
		 * @since  3.0
		 * @param  object context 	This function is going to be called from a draggable. Context is the "this" reference to the draggable.
		 * @param  object ui      	Object sent by jQuery UI draggable.
		 * @return {[type]}         [description]
		 */
		stopDrag: function( context, ui ) {
			jQuery( this.drawerEl )[0].style.setProperty( 'overflow', 'hidden', 'important' );
		},

		getCurrentDraggableHelperClone: function() {
			return this.draggableHelperClone;
		}
	});

	return controller;
} );
/**
 * Handles the dragging of our field staging area
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - New Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/stagingDrag',[], function( ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen for the start and stop of our field staging dragging
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'startDrag:fieldStaging', this.startDrag );
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'stopDrag:fieldStaging', this.stopDrag );
		},

		/**
		 * When the user starts dragging the staging area, we have to:
		 * set the overflow property of the drawer to visible !important. If we don't, the button goes underneath the main section.
		 * set the overflow proerty of the main to visible !important. If we don't, the dragged element goes underneath the drawer.
		 * replace our helper with the stacked "x fields" template.
		 * 
		 * @since  3.0
		 * @param  Object	 context jQuery UI Draggable
		 * @param  Object	 ui      jQuery UI element
		 * @return void
		 */
		startDrag: function( context, ui ) {
			this.drawerEl = nfRadio.channel( 'app' ).request( 'get:drawerEl' );
			this.mainEl = nfRadio.channel( 'app' ).request( 'get:mainEl' );
			jQuery( this.drawerEl )[0].style.setProperty( 'overflow', 'visible', 'important' );
			// jQuery( this.mainEl )[0].style.setProperty( 'overflow', 'visible', 'important' );

			var stagedFields = nfRadio.channel( 'fields' ).request( 'get:staging' );
			var html = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-staged-fields-drag' );
			jQuery( ui.helper ).html( html( { num: stagedFields.models.length } ) );
			jQuery( ui.helper ).prop( 'id', 'nf-staged-fields-drag' );
			jQuery( ui.item ).css( 'opacity', '0.7' );
		},

		/**
		 * When we stop dragging the staging area, we have to set the overflow property to hidden !important
		 * 
		 * @since  3.0
		 * @param  Object	 context jQuery UI Draggable
		 * @param  Object	 ui      jQuery UI element
		 * @return void
		 */
		stopDrag: function( context, ui ) {
			jQuery( this.drawerEl )[0].style.setProperty( 'overflow', 'hidden', 'important' );
			// jQuery( this.mainEl )[0].style.setProperty( 'overflow', 'hidden', 'important' );
		}
	});

	return controller;
} );
/**
 * Handles most things related to our staging area:
 * 1) Creates a collection
 * 2) Listens for requests to CRUD items from the collection
 * 3) Adds our staged fields to the fields sortable when the drawer is closed
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - New Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/staging',['models/fields/stagingCollection'], function( stagingCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Create our staged fields collection
			this.collection = new stagingCollection();
			// Respond to requests related to our staging area.
		    nfRadio.channel( 'fields' ).reply( 'add:stagedField', this.addStagedField, this );
			nfRadio.channel( 'fields' ).reply( 'remove:stagedField', this.removeStagedField, this );
			nfRadio.channel( 'fields' ).reply( 'get:staging', this.getStagingCollection, this );
			nfRadio.channel( 'fields' ).reply( 'sort:staging', this.sortStagedFields, this );
			nfRadio.channel( 'fields' ).reply( 'clear:staging', this.clearStagedFields, this );
			// Listen to our remove staged field click event.
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'click:removeStagedField', this.removeStagedField );
			// Listen to our event that fires just before a drawer is closed.
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'before:closeDrawer', this.beforeCloseDrawer );
		},

		getStagingCollection: function() {
			return this.collection;
		},

		/**
		 * Add a field to our staging area
		 * 
		 * @since 3.0
		 * @param string type Type of field we're adding
		 * @return tmpID
		 */
		addStagedField: function( type, silent ) {
			var silent = silent || false;
			// Get our type model from the string.
			var fieldType = nfRadio.channel( 'fields' ).request( 'get:type', type );
			// Our tmp ID is a string with the time appended to make it unique.
			var tmpID = 'nf-staged-field-' + jQuery.now();
			// Object that will be added to our staging collection.
			var data = {
				id: tmpID,
				// i.e. firstname, textbox, etc.
				slug: fieldType.get( 'type' ),
				// i.e. First Name, Textbox, etc.
				nicename: fieldType.get( 'nicename' ),
				// i.e. calendar, envelope, etc.
				icon: fieldType.get( 'icon' )
			}
			// 
			var model = this.collection.add( data );

			if( ! silent ) nfRadio.channel( 'fields').trigger( 'add:stagedField', model );

			return tmpID;
		},

		/**
		 * Remove a field from staging
		 * 
		 * @since  3.0
		 * @param  Object 			e     	Event
		 * @param  Backbone.model 	model 	staged field model to remove
		 * @return void
		 */
		removeStagedField: function( e, model ) {
			this.collection.remove( model );
			nfRadio.channel( 'fields' ).trigger( 'remove:stagedField', model );
		},

		/**
		 * Adds our staged fields to the main fields sortable before the drawer is closed.
		 * 
		 * @since  3.0
		 * @return void
		 */
		beforeCloseDrawer: function() {
			if ( 0 != this.collection.models.length ) { // Make sure that we have models
				// Get our field collection.
				var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );

				var fields = [];
				// Loop through our staging collection
				_.each( this.collection.models, function( model ) {
					// Get a tmp ID for our new field.
					var tmpID = nfRadio.channel( 'fields' ).request( 'get:tmpID' );
					// Create an object that can be added as a model.
					var tmpField = { id: tmpID, label: model.get( 'nicename' ), type: model.get( 'slug' ) };
					// Add our new field.
					var newModel = nfRadio.channel( 'fields' ).request( 'add',  tmpField, false );
					// Add our field addition to our change log.
					var label = {
						object: 'Field',
						label: newModel.get( 'label' ),
						change: 'Added',
						dashicon: 'plus-alt'
					};
					var data = {
						collection: fieldCollection
					}
					nfRadio.channel( 'changes' ).request( 'register:change', 'addObject', newModel, null, label, data );
			
				} );
				// Trigger a reset on our field collection so that our view re-renders
				fieldCollection.trigger( 'reset', fieldCollection );
				// Empty the staging collection
				this.collection.reset();
			}
			// Sort our fields.
			nfRadio.channel( 'fields' ).request( 'sort:fields', null, null, false );
		},

		/**
		 * Sort our staging area by the 'order' attribute.
		 * 
		 * @since  3.0
		 * @return void
		 */
		sortStagedFields: function() {
			// Get our staged fields sortable.
			var sortableEl = nfRadio.channel( 'app' ).request( 'get:stagedFieldsEl' );
			// Get the current order using jQuery sortable. Will be an array of IDs: [tmp-blah, tmp-blah]
			var order = jQuery( sortableEl ).sortable( 'toArray' );
			// Loop through our models
			_.each( this.collection.models, function( field ) {
				// Search our order array for this field.
				var search = field.get( 'id' );
				var pos = order.indexOf( search );
				// Update our staged field model with the new order.
				field.set( 'order', pos );
			} );
			// Sort our staging collection.
			this.collection.sort();
		},

		clearStagedFields: function() {
			this.collection.reset();
		}

	});

	return controller;
} );
/**
 * Handles actions related to our staged fields sortable.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - New Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/stagingSortable',['models/fields/stagingCollection'], function( stagingCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen to our field type draggables
			// this.listenTo( nfRadio.channel( 'drawer-addField' ), 'startDrag:type', this.addActiveClass );
			// this.listenTo( nfRadio.channel( 'drawer-addField' ), 'stopDrag:type', this.removeActiveClass );
			// Listen to our sortable events
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'receive:stagedFields', this.receiveStagedFields );
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'over:stagedFields', this.overStagedFields );
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'out:stagedFields', this.outStagedFields );
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'start:stagedFields', this.startStagedFields );
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'stop:stagedFields', this.stopStagedFields );
		},

		/**
		 * Change our dropped field type helper so that it matches the other items in our sortable.
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI item
		 * @return void
		 */
		receiveStagedFields: function( ui ) {
			if( jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) ) {
				var type = jQuery( ui.item ).data( 'id' );
				var tmpID = nfRadio.channel( 'fields' ).request( 'add:stagedField', type );
				jQuery( ui.helper ).prop( 'id', tmpID );
				nfRadio.channel( 'fields' ).request( 'sort:staging' );
				jQuery( ui.helper ).remove();
				nfRadio.channel( 'drawer-addField' ).trigger( 'drop:fieldType', type );				
			}
		},

		/**
		 * Add an active class to our sortable when a field type item is dragged
		 * 
		 * @since 3.0
		 */
		addActiveClass: function() {
			var stagedFieldsEl = nfRadio.channel( 'app' ).request( 'get:stagedFieldsEl' );
			jQuery( stagedFieldsEl ).addClass( 'nf-droppable-active' );
		},

		/**
		 * Remove the active class from our sortable when the field type item is dropped.
		 * 
		 * @since  3.0
		 * @return void
		 */
		removeActiveClass: function() {
			var stagedFieldsEl = nfRadio.channel( 'app' ).request( 'get:stagedFieldsEl' );
			jQuery( stagedFieldsEl ).removeClass( 'nf-droppable-active' );
		},

		/**
		 * When the field type item is dragged over our sortable, we change the helper to match the sortable items.
		 * 
		 * @since  3.0
		 * @param  Object 	e  event
		 * @param  Object 	ui jQuery UI Element
		 * @return void
		 */
		overStagedFields: function( e, ui ) {
			if( jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) ) {
				var type = jQuery( ui.item ).data( 'id' );
				var fieldType = nfRadio.channel( 'fields' ).request( 'get:type', type );
				var nicename = fieldType.get( 'nicename' );
				this.currentHelper = ui.helper 
				jQuery( ui.helper ).html( nicename + '<span class="dashicons dashicons-dismiss"></span>' );
				jQuery( ui.helper ).removeClass( 'nf-field-type-button' ).addClass( 'nf-item-dock' ).css( { 'opacity': '0.8', 'width': '', 'height': '' } );
				var sortableEl = nfRadio.channel( 'app' ).request( 'get:stagedFieldsEl' );
				if ( jQuery( sortableEl ).hasClass( 'ui-sortable' ) ) {
					jQuery( sortableEl ).addClass( 'nf-droppable-hover' );
				}
			}
			
		},

		/**
		 * When a field type item is moved away from our sortable, we change the helper to its previous appearance
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		outStagedFields: function( ui ) {
			if( jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) ) {
				var helperClone = nfRadio.channel( 'drawer-addField' ).request( 'get:typeHelperClone' );	
				jQuery( this.currentHelper ).html( jQuery( helperClone ).html() );
				jQuery( this.currentHelper ).removeClass( 'nf-item-dock' ).addClass( 'nf-field-type-button' );
				var sortableEl = nfRadio.channel( 'app' ).request( 'get:stagedFieldsEl' );
				if ( jQuery( sortableEl ).hasClass( 'ui-sortable' ) ) {
					jQuery( sortableEl ).removeClass( 'nf-droppable-hover' );
				}
			}		
		},

		/**
		 * When a user starts to drag a sortable item, we need to set a few properties on the item and the helper.
		 * These keep the original item in place while dragging and changes the opacity of the helper.
		 * 
		 * @since  3.0
		 * @param  Object	 ui jQuery UI element
		 * @return void
		 */
		startStagedFields: function( ui ) {
			jQuery( ui.item ).show();
			jQuery( ui.item ).css( { 'display': 'inline', 'opacity': '0.7' } );
			jQuery( ui.helper ).css( 'opacity', '0.5' );
		},

		/**
		 * When we stop dragging a sortable item, remove our opacity setting and remove the helper item.
		 * 
		 * @since  3.0
		 * @param  Object	 ui jQuery UI element
		 * @return void
		 */
		stopStagedFields: function( ui ) {
			jQuery( ui.item ).css( 'opacity', '' );
			jQuery( ui.helper ).remove();
		}

	});

	return controller;
} );
/**
 * Filters our field type collection.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - New Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/filterTypes',['models/fields/typeSectionCollection'], function( fieldTypeSectionCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen to our change filter event.
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'change:filter', this.filterFieldTypes );
		},

		/**
		 * Filter our field types in the add new field drawer
		 * 
		 * Takes a search string and finds any field types that match either the name or alias.
		 * 
		 * @since  3.0
		 * @param  string	 search 	string being searched for
		 * @param  object 	 e      	Keyup event
		 * @return void
		 */
		filterFieldTypes: function( search, e ) {
			// Make sure that we aren't dealing with an empty string.
			if ( '' != jQuery.trim( search ) ) {
        		var filtered = [];
        		/**
        		 * Call the function that actually filters our collection,
        		 * and then loop through our collection, adding each model to our filtered array.
        		 */
        		_.each( this.filterCollection( search ), function( model ) {
        			filtered.push( model.get( 'id' ) );
        		} );

        		// Create a new Field Type Section collection with the filtered array.
        		var filteredSectionCollection = new fieldTypeSectionCollection( [
				{ 
					id: 'filtered',
					nicename: 'Filtered Fields',
					fieldTypes: filtered
				}
				] );
                
                // Declare array of fields to hide.
				var hiddenFields = nfRadio.channel( 'app' ).request( 'update:hiddenFields' ) || [];
				hiddenFields = hiddenFields.concat([
					'product',
					'quantity',
					'shipping',
					'total',
					'button'
				]);

                // Search our results of hidden fields.
                for ( var i = filteredSectionCollection.models[ 0 ].get( 'fieldTypes' ).length -1; i >= 0; i-- ) {
                    var target = hiddenFields.indexOf( filteredSectionCollection.models[ 0 ].get( 'fieldTypes' )[ i ] );
                    // If we find any...
                    if ( -1 < target ) {
                        // Remove them from the collection.
                        filteredSectionCollection.models[ 0 ].get( 'fieldTypes' ).splice( i, 1 );
                    }
                }

        		// Request that our field types filter be applied, passing the collection we created above.
        		nfRadio.channel( 'drawer' ).trigger( 'filter:fieldTypes', filteredSectionCollection );
        		// If we've pressed the 'enter' key, add the field to staging and clear the filter.
        		if ( 'undefined' != typeof e && e.addObject ) {
        			if ( 0 < filtered.length ) {
        				nfRadio.channel( 'fields' ).request( 'add:stagedField', filtered[0] );
        				nfRadio.channel( 'drawer' ).request( 'clear:filter' );
        			}
        		}
        	} else {
        		// Clear our filter if the search text is empty.
        		nfRadio.channel( 'drawer' ).trigger( 'clear:filter' );
        	}
        },

        /**
         * Search our field type collection for the search string.
         * 
         * @since  3.0
         * @param  string	 search 	string being searched for
         * @return backbone.collection
         */
        filterCollection: function( search ) {
        	search = search.toLowerCase();
        	// Get our list of field types
        	var collection = nfRadio.channel( 'fields' ).request( 'get:typeCollection' );
        	/*
        	 * Backbone collections have a 'filter' method that loops through every model,
        	 * waiting for you to return true or false. If you return true, the model is kept.
        	 * If you return false, it's removed from the filtered result.
        	 */
			var filtered = collection.filter( function( model ) {
				var found = false;
				
				// If we match either the ID or nicename, return true.
				if ( model.get( 'type' ).toLowerCase().indexOf( search ) != -1 ) {
					found = true;
				} else if ( model.get( 'nicename' ).toLowerCase().indexOf( search ) != -1 ) {
					found = true;
				}

				/*
				 * TODO: Hashtag searching. Doesn't really do anything atm.
				 */
				if ( model.get( 'tags' ) && 0 == search.indexOf( '#' ) ) {
					_.each( model.get( 'tags' ), function( tag ) {
						if ( search.replace( '#', '' ).length > 1 ) {
							if ( tag.toLowerCase().indexOf( search.replace( '#', '' ) ) != -1 ) {
								found = true;
							}							
						}
					} );
				}

				// If we match any of the aliases, return true.
				if ( model.get( 'alias' ) ) {
					_.each( model.get( 'alias' ), function( alias ) {
						if ( alias.toLowerCase().indexOf( search ) != -1 ) {
							found = true;
						}
					} );
				}

				return found;
			} );
			// Return our filtered collection.
			return filtered;
        }
	});

	return controller;
} );
define( 'views/fields/preview/element',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-field-input',

		initialize: function() {
			
			var type = this.model.get('type');

			this.model.set('value', this.model.get('default'));
			
			if('date' == type && this.model.get('date_default')){
				var format = this.model.get('date_format');
				if('default' == format || '' == format) format = this.convertDateFormat(nfAdmin.dateFormat);
				this.model.set('value', moment().format(format) );
			}

			if('phone' == type) type = 'tel';
			if('spam' == type) type = 'input';
			// if('date' == type) type = 'input';
			if('confirm' == type) type = 'input';
			if('password' == type) type = 'input';
			if('passwordconfirm' == type) type = 'input';
			if('quantity' == type) type = 'number';
			if('terms' == type) type = 'listcheckbox';
			if('liststate' == type) type = 'listselect';
			if('listcountry' == type) type = 'listselect';
			if('listmultiselect' == type) type = 'listselect';
			if('save' == type) type = 'submit';

			// If a builder-specific template exists for this type, use that.
			if ( 1 == jQuery( '#tmpl-nf-builder-field-' + type ).length ) {
				this.template = '#tmpl-nf-builder-field-' + type;
			} else {
				this.template = '#tmpl-nf-field-' + type;
			}			
		},

		onRender: function() {
			if(this.model.get('container_class').includes('two-col-list')) {
				jQuery(this.el).find('> ul').css('display', 'grid');
				jQuery(this.el).find('> ul').css('grid-template-columns', 'repeat(2, 1fr)');
			}
			if(this.model.get('container_class').includes('three-col-list')) {
				jQuery(this.el).find('> ul').css('display', 'grid');
				jQuery(this.el).find('> ul').css('grid-template-columns', 'repeat(3, 1fr)');
			}
			if(this.model.get('container_class').includes('four-col-list')) {
				jQuery(this.el).find('> ul').css('display', 'grid');
				jQuery(this.el).find('> ul').css('grid-template-columns', 'repeat(4, 1fr)');
			}
		},
        
		templateHelpers: function () {
	    	return {
	    		renderClasses: function() {
	    			// ...
                },
                renderPlaceholder: function() {
                    if('undefined' == typeof this.placeholder) return;
					return 'placeholder="' + jQuery.trim( this.placeholder ) + '"';
                },
                maybeDisabled: function() {
                    if('undefined' == typeof this.disable_input) return;
                    if(!this.disable_input) return;
                    return 'disabled="disabled"';
                },
                maybeRequired: function() {
					// ...
				},
				maybeInputLimit: function() {
					// ...
				},
				maybeDisableAutocomplete: function() {
					// ..
				},
				maybeChecked: function() {
					if('checked' == this.default_value) return ' checked="checked"';
				},
				renderOptions: function() {
					switch(this.type) {
						case 'terms':

							if( ! this.taxonomy ){
								return '(No taxonomy selected)';
							}

							var taxonomyTerms = fieldTypeData.find(function(typeData){
								return 'terms' == typeData.id;
							}).settingGroups.find(function(settingGroup){
								return 'primary' == settingGroup.id;
							}).settings.find(function(setting){
								return 'taxonomy_terms' == setting.name;
							}).settings;

							var attributes = Object.keys(this);
							var enabledTaxonomyTerms = attributes.filter(function(attribute){
								return 0 == attribute.indexOf('taxonomy_term_') && this[attribute];
							}.bind(this));

							if(0 == enabledTaxonomyTerms.length) {
								return '(No available terms selected)';
							}

							return enabledTaxonomyTerms.reduce(function(html, enabledTaxonomyTerm) {
								var term = taxonomyTerms.find(function(terms){
									return enabledTaxonomyTerm == terms.name;
								});
								if( 'undefined' == typeof term ) return html;
								return html += '<li><input type="checkbox"><div>' + term.label  + '</div></li>';
							}.bind(this), '');
						case 'liststate':
						case 'listselect':

							// Check if there are any options.
							if(0 == this.options.models.length) return '';

							// Filter by :selected" options.
							var options = this.options.models.filter(function(option){
								return option.get('selected');
							});

							// If no option set as "selected", then reset the previous filter.
							if(0 == options.length) options = this.options.models;

							// Set the first option to display in the field preview.
							return '<option>' + options[0].get('label') + '</option>';
						case 'listmultiselect':
							return this.options.models.reduce(function(html, option) {
								var selected = (option.get('selected')) ? ' selected="selected"' : '';
								return html += '<option' + selected + '>' + option.get('label')  + '</option>';
							}, '');
						case 'listcheckbox':
							return this.options.models.reduce(function(html, option) {
								var checked = (option.get('selected')) ? ' checked="checked"' : '';
								return html += '<li><input type="checkbox"' + checked + '><div>' + option.get('label')  + '</div></li>';
							}, '');
						case 'listradio':
							var checked = false; // External flag to only select one radio item.
							return this.options.models.reduce(function(html, option) {
								checked = (option.get('selected') && !checked) ? ' checked="checked"' : '';
								return html += '<li><input type="radio"' + checked + '><div>' + option.get('label')  + '</div></li>';
							}, '');
						case 'listcountry':
							var defaultValue = this.default;
							var defaultOption = window.fieldTypeData.find(function(data) {
								return 'listcountry' == data.id;
							}).settingGroups.find(function(group){
								return 'primary' == group.id;
							}).settings.find(function(setting){
								return 'default' == setting.name;
							}).options.find(function(option) {
								return defaultValue == option.value;
							});
							var optionLabel = ('undefined' !== typeof defaultOption ) ? defaultOption.label : '--';
							return '<option>' + optionLabel + '</option>';
						default:
							return '';
					}
				},
				renderOtherAttributes: function() {
					var attributes = [];
					if('listmultiselect' == this.type) {
						attributes.push('multiple');

						var multi_size = this.multi_size || '5';
						attributes.push('size="' + multi_size + '"');
					}

					return attributes.join(' ');
				},
				renderProduct: function() {
					// ...
				},
				renderNumberDefault: function() {
					return this.value;
				},
				renderCurrencyFormatting: function() {
					// ...
				},
				renderRatings: function() {
					var ratingOutput = '';
					for (var i = 0; i < this.number_of_stars; i++) {
						ratingOutput += '<i class="fa fa-star" aria-hidden="true"></i>&nbsp;';
					  }
					return ratingOutput;
				},
				renderHourOptions: function() {
            html = '';
            let hours = 12;

            if ( 'undefined' != typeof this.hours_24 && 1 == this.hours_24 ) {
                hours = 24;
            }

            for (var i = 0; i < hours; i++) {
                let value = label = i;

                if ( i < 10 ) {
                    value = label = '0' + i;
                }
                html += '<option value="' + value + '">' + label + '</option>';
                i = i++;
            }

            return html;
        },

        renderMinuteOptions: function() {
            var html = '';
            let minute_increment = 5;

            if ( 'undefined' != typeof this.minute_increment ) {
                minute_increment = this.minute_increment;
            }

            let i = 0;

            while( i < 60 ) {
                let value = label = i;

                if ( i < 10 ) {
                    value = label = '0' + i;
                }
                html += '<option value="' + value + '">' + label + '</option>';
                i = i + minute_increment;
            }

            return html;
        },

        maybeRenderAMPM: function() {
            if ( 'undefined' == typeof this.hours_24 || 1 == this.hours_24 ) {
                return;
            }

            return '<div style="float:left;"><select class="ampm" style="float:left;"><option value="am">AM</option><option value="pm">PM</option></select></div>'
        },
        				maybeRenderTime: function() {
					if ( 'time_only' == this.date_mode || 'date_and_time' == this.date_mode ) {
						return true;
					}
					return false;
				},

            }
		},
		
        convertDateFormat: function( dateFormat ) {
            // http://php.net/manual/en/function.date.php
            // https://github.com/dbushell/Pikaday/blob/master/README.md#formatting
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
        }

	});

	return view;
} );
/**
 * This is a copy of the 'views/fields/mainContentEmpty.js' file.
 * It is also the file that handles dropping new field types on our repeater field.
 * 
 */

define( 'views/fields/preview/repeaterElementEmpty',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-repeater-content-fields-empty',

		initialize: function( data ) {
			this.repeaterFieldModel = data.repeaterFieldModel;
		},

		onBeforeDestroy: function() {
			jQuery( this.el ).parent().removeClass( 'nf-fields-empty-droppable' ).droppable( 'destroy' );
		},

		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );
		},

		onShow: function() {
			if ( jQuery( this.el ).parent().hasClass( 'ui-sortable' ) ) {
				jQuery( this.el ).parent().sortable( 'destroy' );
			}
			jQuery( this.el ).parent().addClass( 'nf-fields-empty-droppable' );
			let that = this;
			jQuery( this.el ).parent().droppable( {
				accept: function( draggable ) {
					if ( jQuery( draggable ).hasClass( 'nf-stage' ) || jQuery( draggable ).hasClass( 'nf-field-type-button' ) ) {
						return true;
					}
				},
				activeClass: 'nf-droppable-active',
				hoverClass: 'nf-droppable-hover',
				tolerance: 'pointer',

				over: function( e, ui ) {	
					
					ui.item = ui.draggable;
					jQuery(ui.item).addClass("nf-over-repeater");
					nfRadio.channel( 'app' ).request( 'over:fieldsSortable', ui );
				},
				out: function( e, ui ) {
				
					ui.item = ui.draggable;
					jQuery(ui.item).removeClass("nf-over-repeater");
					nfRadio.channel( 'app' ).request( 'out:fieldsSortable', ui );
				},
				/**
				 * Handles the dropping of items into our EMPTY repeater field.
				 * 
				 */
				drop: function( e, ui ) {
					ui.item = null != ui.item ? ui.item : ui.draggable;
					nfRadio.channel( 'fields-repeater' ).request( 'add:childField', ui, that, e );
				},
			} );
		}
	});

	return view;
} );
/**
 * Collection View that outputs our repeater field collection to the screen.
 */
define( 'views/fields/preview/repeaterElementCollection',[ 'views/fields/preview/repeaterElementEmpty' ], function( emptyView ) {
	var view = Marionette.CollectionView.extend( {
		tagName: 'div',
		emptyView: emptyView,

		getChildView: function() {
			let view = nfRadio.channel( 'views' ).request( 'get:fieldItem' );
			return view;
		},

		initialize: function( data ) {
			this.emptyViewOptions = {
				repeaterFieldModel: data.repeaterFieldModel,
			};
			this.repeaterFieldModel = data.repeaterFieldModel;

			nfRadio.channel( 'fields-repeater' ).reply( 'init:sortable', this.initSortable, this );
			nfRadio.channel( 'fields-repeater' ).reply( 'get:sortableEl', this.getSortableEl, this );
			nfRadio.channel( 'fields-repeater' ).reply( 'get:repeaterFieldsCollection', this.getRepeaterFieldsCollection, this );
		},

		onRender: function() {
			if ( this.collection.models.length > 0 ) {
				jQuery( this.el ).addClass( 'nf-field-type-droppable' );
				var that = this;
				this.initSortable();
			}
		},

		/**
		 * This sortable is a copy with modifications of the main field list sortable.
		 * 
		 * @since  version
		 * @return {[type]} [description]
		 */
		initSortable: function() {
			// If the sortable has already been instantiated, return early.
			if ( 'undefined' != typeof jQuery( this.el ).sortable( 'instance' ) ) return false;

			jQuery( this.el ).addClass( 'nf-field-type-droppable' ).addClass( 'nf-fields-sortable' );

			let that = this;
			jQuery( this.el ).sortable( {
				containment: 'parent',
				helper: 'clone',
				cancel: '.nf-item-controls',
				placeholder: 'nf-fields-sortable-placeholder',
				opacity: 0.95,
				grid: [ 5, 5 ],
				appendTo: '#nf-main',
				scrollSensitivity: 10,
				//connectWith would allow drag and drop between fields already in the builder and the repeatable fieldset ( this is currently an issue until we deal with existing data stored)
				//connectWith: '.nf-fields-sortable', 

				receive: function( e, ui ) {
					nfRadio.channel( 'fields-repeater' ).request( 'receive:fields', ui, that, e );
				},

				over: function( e, ui ) {
					jQuery(ui.item).addClass("nf-over-repeater");
					if ( ui.item.dropping ) return;
					nfRadio.channel( 'fields-repeater' ).request( 'over:repeaterField', ui, that, e );
				},

				out: function( e, ui ) {
					jQuery(ui.item).removeClass("nf-over-repeater");
					if ( ui.item.dropping ) return;
					nfRadio.channel( 'fields-repeater' ).request( 'out:repeaterField', ui, that, e );
				},

				start: function( e, ui ) {
					if ( ui.item.dropping ) return;
					nfRadio.channel( 'fields-repeater' ).request( 'start:repeaterField', ui, that, e );
				},

				remove: function( e, ui ) {
					// The field is removed from repeater Fields collection and a new one is created for main Fields collection from controllers/fields/sortable/js
					let droppedFieldID = jQuery( ui.item ).data( 'id' );
					let collection = that.repeaterFieldModel.get( 'fields' );
					let droppedFieldModel = collection.get( droppedFieldID );
					
					// Remove the field from the repeater field collection making sure we alert the user the field data is being deleted
					nfRadio.channel( 'app' ).trigger( 'click:delete', e, droppedFieldModel );
				},
				
				// When we update the sort order of our repeater field children, run our sort function.
				update: function( e, ui ) {
					nfRadio.channel( 'fields-repeater' ).request( 'update:repeaterField', ui, that, e );
				},

				stop: function( e, ui ) {
					if ( ui.item.dropping ) return;
					nfRadio.channel( 'fields-repeater' ).request( 'stop:repeaterField', ui, that, e );
				}
			} );
		},

		destroySortable: function() {
			jQuery( this.el ).sortable( 'destroy' );
		},

		/**
		 * When we add our first child, we need to init the sortable.
		 * 
		 * @since  version
		 * @param  {[type]} childView [description]
		 * @return {[type]}           [description]
		 */
		onAddChild: function( childView ) {
			if ( nfRadio.channel( 'fields' ).request( 'get:adding' ) ) {
				childView.$el.hide().show( 'clip' );
				nfRadio.channel( 'fields' ).request( 'set:adding', false);
			}
		},

		/**
		 * Get Element holding child fields
		 */
		getSortableEl: function() {
			return this.el;
		},

		/**
		 * Getter for the repeater Fields collection
		 */
		getRepeaterFieldsCollection: function() {
			return this.repeaterFieldModel.get( 'fields' );
		}
		
	} );

	return view;
} );

define( 'views/fields/preview/repeaterElementLayout',[ 'views/fields/preview/repeaterElementCollection' ], function( previewRepeaterElementCollectionView ) {
	var view = Marionette.LayoutView.extend({
		tagName: 'div',
		template: '#tmpl-nf-field-repeater',

		regions: {
			fields: '.nf-repeater-fieldsets',
		},

		initialize: function( data ) {
			this.collection = data.collection;
			this.model = data.model;
		},

		onRender: function() {
			// Populate the fields region with our collection view.
			this.fields.show( new previewRepeaterElementCollectionView( { collection: this.collection, repeaterFieldModel: this.model } ) );
		},
	});

	return view;
} );
define( 'views/fields/preview/label',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-field-label',

		initialize: function( data ) {
			// this.$el = jQuery( data.itemView.el ).find( '.nf-realistic-field--label' );
		},

		onRender: function() {
			// ...
			// console.log( jQuery( this.$el ) );
		},
        
		templateHelpers: function () {
	    	return {
	    		renderLabelClasses: function() {
                    // ...
                },
                maybeRenderHelp: function() {
                    // ...
                }
            }
        }

	});

	return view;
} );
define( 'views/fields/fieldItem',['views/app/itemControls', 'views/fields/preview/element', 'views/fields/preview/repeaterElementLayout', 'views/fields/preview/label'], function( itemControlsView, previewElementView, previewRepeaterElementView, previewLabelView ) {
	var view = Marionette.LayoutView.extend({
		tagName: 'div',
		template: '#tmpl-nf-main-content-field',
		doingShortcut: false,

		regions: {
			itemControls: '.nf-item-controls',
			previewLabel: '.nf-realistic-field--label',
			previewElement: '.nf-realistic-field--element',
		},

		initialize: function() {
			this.model.on( 'change:editActive', this.render, this );
			this.model.on( 'change:label', this.render, this );
			this.model.on( 'change:required', this.render, this );
			this.model.on( 'change:id', this.render, this );
		},

		onBeforeDestroy: function() {
			this.model.off( 'change:editActive', this.render );
			this.model.off( 'change:label', this.render );
			this.model.off( 'change:required', this.render );
			this.model.off( 'change:id', this.render );
		},

		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );

			this.itemControls.show( new itemControlsView( { model: this.model } ) );
			jQuery( this.el ).disableSelection();

			var type = this.model.get('type');
			if('phone' == type) type = 'tel';
			if('spam' == type) type = 'input';
			// if('date' == type) type = 'input';
			if('confirm' == type) type = 'input';
			if('password' == type) type = 'input';
			if('passwordconfirm' == type) type = 'input';
			if('quantity' == type) type = 'number';
			if('terms' == type) type = 'listcheckbox';
			if('liststate' == type) type = 'listselect';
			if('listcountry' == type) type = 'listselect';
			if('listmultiselect' == type) type = 'listselect';
			if('save' == type) type = 'submit';

			// Only show preview / realisitic fields when not `html`, `hidden`, `note`, or `recaptcha`.
			var previewFieldTypeBlacklist = ['html', 'hidden', 'note', 'recaptcha'];
			var isFieldTypeTemplateAvailable = jQuery('#tmpl-nf-field-' + type).length;
			if(-1 == previewFieldTypeBlacklist.indexOf(this.model.get('type')) && isFieldTypeTemplateAvailable) {
				
				// If we have a repeater field, then we have to load a specific collection view.
				if ( 'repeater' == type ) {
					this.previewElement.show( new previewRepeaterElementView( { collection: this.model.get( 'fields' ), model: this.model } ) );
				} else {
					this.previewElement.show( new previewElementView( { model: this.model } ) );
				}
				
				// Only show the preview label when not `submit`, or `hr`.
				var showLabelFieldTypeBlacklist = ['submit', 'save', 'hr'];
				if(-1 == showLabelFieldTypeBlacklist.indexOf(this.model.get('type'))) {
					this.previewLabel.show( new previewLabelView( { model: this.model, itemView: this } ) );
				}

				jQuery( this.el ).find('.nf-placeholder-label').hide();
			}

			if ( nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
				jQuery( this.el ).on( 'taphold', function( e, touch ) {
					if ( ! jQuery( e.target ).hasClass( 'nf-edit-settings' ) ) {
						jQuery( this ).addClass( 'ui-sortable-helper drag-selected' );
						jQuery( this ).ClassyWiggle( 'start', { degrees: ['.65', '1', '.65', '0', '-.65', '-1', '-.65', '0'], delay: 50 } );
					}
				} );
			}

			nfRadio.channel( 'fields-' + type ).trigger( 'render:itemView', this );
		},

		templateHelpers: function () {
	    	return {
	    		renderClasses: function() {
	    			var classes = 'nf-field-wrap ' + this.type;
	    			if ( this.editActive ) {
	    				classes += ' active';
	    			}
	    			return classes;
	    		},
	    		renderRequired: function() {
	    			if ( 1 == this.required ) {
	    				return '<span class="required">*</span>';
	    			} else {
	    				return '';
	    			}
	    		},
	    		getFieldID: function() {
					if ( jQuery.isNumeric( this.id ) ) {
						return 'field-' + this.id;
					} else {
						return this.id;
					}
				},
				renderIcon: function() {
	    			var type, icon;

					type = nfRadio.channel( 'fields' ).request( 'get:type', this.type );

					icon = document.createElement( 'span' );
					icon.classList.add( 'fa', 'fa-' + type.get( 'icon' ) );

					return icon.outerHTML;
				},
				labelPosition: function() {
					return this.label_pos;
				},
				renderDescriptionText: function() {
					return jQuery.trim(this.desc_text);
				}
			};
		},

		events: {
			'mouseover .nf-item-control': 'mouseoverItemControl',
			'mousedown': 'maybeShortcut',
			'click': 'maybeClickEdit',
			'singletap': 'maybeTapEdit',
			'swipeleft': 'swipeLeft',
			'swiperight': 'swipeRight',
			'tapend': 'tapend'
		},

		maybeClickEdit: function( e ) {
			if ( this.doingShortcut ) {
				this.doingShortcut = false;
				return false;
			}

			if ( ( jQuery( e.target ).parent().hasClass( 'nf-fields-sortable' ) || jQuery( e.target ).parent().hasClass( 'nf-field-wrap' ) || jQuery( e.target ).hasClass( 'nf-field-wrap' ) ) && ! nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
				jQuery( ':focus' ).blur();
				nfRadio.channel( 'app' ).trigger( 'click:edit', e, this.model );
			}
		},

		maybeShortcut: function( e ) {
			var keys = nfRadio.channel( 'app' ).request( 'get:keydown' );
			/*
			 * If the shift key isn't held down, return.
			 */
			if ( -1 == keys.indexOf( 16 ) ) {
				return true;
			}
			/*
			 * If we are pressing D, delete this field.
			 */
			if ( -1 != keys.indexOf( 68 ) ) {
				nfRadio.channel( 'app' ).trigger( 'click:delete', e, this.model );
				this.doingShortcut = true;
				return false;
			} else if ( -1 != keys.indexOf( 67 ) ) {
				this.doingShortcut = true;
				nfRadio.channel( 'app' ).trigger( 'click:duplicate', e, this.model );
				return false;
			}
		},

		maybeTapEdit: function( e ) {
			if ( jQuery( e.target ).parent().hasClass( 'nf-fields-sortable' ) ) {
				nfRadio.channel( 'app' ).trigger( 'click:edit', e, this.model );
			}
		},

		swipeLeft: function( e, touch ) {
			jQuery( touch.startEvnt.target ).closest( 'div' ).find( '.nf-item-duplicate' ).show();
			jQuery( touch.startEvnt.target ).closest( 'div' ).find( '.nf-item-delete' ).show();
		},

		swipeRight: function( e, touch ) {
			jQuery( touch.startEvnt.target ).closest( 'div' ).find( '.nf-item-duplicate' ).hide();
			jQuery( touch.startEvnt.target ).closest( 'div' ).find( '.nf-item-delete' ).hide();
		},

		tapend: function( e, touch ) {
			jQuery( this.el ).ClassyWiggle( 'stop' );
			jQuery( this.el ).removeClass( 'ui-sortable-helper drag-selected' );
		},

		remove: function(){
			if ( nfRadio.channel( 'fields' ).request( 'get:removing' ) ) {
				this.$el.hide( 'clip', function(){
					jQuery( this ).remove();
				});
			} else {
				this.$el.remove();
			}

			nfRadio.channel( 'fields' ).request( 'set:removing', false );
		},

		mouseoverItemControl: function( e ) {
			jQuery( this.el ).find( '.nf-item-control' ).css( 'display', '' );
		}

	});

	return view;
} );
/**
 * Handles all the actions/functions related to our main field sortable.
 * All of the actual logic for our sortable is held here; the view just calls it using nfRadio.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/sortable',['models/fields/fieldModel', 'views/fields/fieldItem'], function(FieldModel, FieldItemView) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// When our field type buttons are dragged, we need to add or remove the active (blue) class.
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'startDrag:type', this.addActiveClass );
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'stopDrag:type', this.removeActiveClass );
			// When our field staging is dragged, we need to add or remove the active (blue) class.
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'startDrag:fieldStaging', this.addActiveClass );
			this.listenTo( nfRadio.channel( 'drawer-addField' ), 'stopDrag:fieldStaging', this.removeActiveClass );
			
			/*
			 * Handles all the events fired by our sortable:
			 * receive - dropped from type button or staging
			 * over - dragging within or over the sortable
			 * out - leaving the sortable
			 * stop - stopped sorting/dragging
			 * start - started sorting/dragging
			 * update - stopped sorting/dragging and order has changed
			 */
			nfRadio.channel( 'app' ).reply( 'receive:fieldsSortable', this.receiveFieldsSortable, this );
			nfRadio.channel( 'app' ).reply( 'over:fieldsSortable', this.overfieldsSortable, this );
			nfRadio.channel( 'app' ).reply( 'out:fieldsSortable', this.outFieldsSortable, this );
			nfRadio.channel( 'app' ).reply( 'stop:fieldsSortable', this.stopFieldsSortable, this );
			nfRadio.channel( 'app' ).reply( 'start:fieldsSortable', this.startFieldsSortable, this );
			nfRadio.channel( 'app' ).reply( 'update:fieldsSortable', this.updateFieldsSortable, this );
			nfRadio.channel( 'app' ).reply( 'receive:repeaterField', this.receiveRepeaterField, this );
		},

		/**
		 * Add the active class to our sortable so that its border is blue.
		 * 
		 * @since 3.0
		 * @return void
		 */
		addActiveClass: function() {
			var sortableEl = nfRadio.channel( 'fields' ).request( 'get:sortableEl' );
			jQuery( sortableEl ).addClass( 'nf-droppable-active' );	
		},

		/**
		 * Remove the active class from our sortable
		 * 
		 * @since  3.0
		 * @return void
		 */
		removeActiveClass: function() {
			var sortableEl = nfRadio.channel( 'fields' ).request( 'get:sortableEl' );
			jQuery( sortableEl ).removeClass( 'nf-droppable-active' );
		},

		/**
		 * Fires when we drop a field type button or staging onto our sortable
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		receiveFieldsSortable: function( ui ) {
			//Check for fields coming from a repeater field
			ui = this.receiveRepeaterField(ui);
			/*
			 * We have to do different things if we're dealing with a field type button or staging area.
			 */ 
			if( jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) ) { // Field Type Button
				// Get our type string
				var type = jQuery( ui.item ).data( 'id' );
				// Add a field (returns the tmp ID )
				var tmpID = this.addField( type, false );
				/*
				 * Update our helper id to the tmpID.
				 * We do this so that when we sort, we have the proper ID.
				 */ 
				jQuery( ui.helper ).prop( 'id', tmpID );
				nfRadio.channel( 'fields' ).request( 'sort:fields' );
				// Remove the helper. Gets rid of a weird type artifact.
				jQuery( ui.helper ).remove();
				// Trigger a drop field type event.
				nfRadio.channel( 'fields' ).trigger( 'drop:fieldType', type, tmpID );

			} else if ( jQuery( ui.item ).hasClass( 'nf-stage' ) ) { // Staging
				// Later, we want to reference 'this' context, so we define it here.
				var that = this;
				// Make sure that our staged fields are sorted properly.	
				nfRadio.channel( 'fields' ).request( 'sort:staging' );
				// Grab our staged fields.
				var stagedFields = nfRadio.channel( 'fields' ).request( 'get:staging' );
				// Get our current field order.
				var sortableEl = nfRadio.channel( 'fields' ).request( 'get:sortableEl' );
				
				if ( jQuery( sortableEl ).hasClass( 'ui-sortable' ) ) { // Sortable isn't empty
					// If we're dealing with a sortable that isn't empty, get the order.
					var order = jQuery( sortableEl ).sortable( 'toArray' );
				} else { // Sortable is empty
					// Sortable is empty, all we care about is our staged field draggable.
					var order = ['nf-staged-fields-drag'];
				}
				
				// Get the index of our droped element.
				var insertedAt = order.indexOf( 'nf-staged-fields-drag' );

				// Loop through each staged fields model and insert a field.
				var tmpIDs = [];
				_.each( stagedFields.models, function( field, index ) {
					// Add our field.
					var tmpID = that.addField( field.get( 'slug' ) );
					// Add this newly created field to our order array.
					order.splice( insertedAt + index, 0, tmpID );
				} );

				// Remove our dropped element from our order array.
				var insertedAt = order.indexOf( 'nf-staged-fields-drag' );
				order.splice( insertedAt, 1 );
				// Sort our fields
				nfRadio.channel( 'fields' ).request( 'sort:fields', order );
				// Clear our staging
				nfRadio.channel( 'fields' ).request( 'clear:staging' );
				// Remove our helper. Fixes a weird artifact.
				jQuery( ui.helper ).remove();
			}
		},

		/**
		 * Add a field.
		 * Builds the object necessary to add a field to the field model collection.
		 * 
		 * @since  3.0
		 * @param  string 	type   field type
		 * @param  boolean 	silent add silently
		 * @return string 	tmpID
		 */
		addField: function( type, silent ) {
			// Default to false
			silent = silent || false;
			// Get our field type model
			var fieldType = nfRadio.channel( 'fields' ).request( 'get:type', type ); 
			// Get our tmp ID
			var tmpID = nfRadio.channel( 'fields' ).request( 'get:tmpID' );
			// Add our field
			var newModel = nfRadio.channel( 'fields' ).request( 'add',  { id: tmpID, label: fieldType.get( 'nicename' ), type: type }, silent );
			// Add our field addition to our change log.
			var label = {
				object: 'Field',
				label: newModel.get( 'label' ),
				change: 'Added',
				dashicon: 'plus-alt'
			};

			var data = {
				collection: nfRadio.channel( 'fields' ).request( 'get:collection' )
			}

			nfRadio.channel( 'changes' ).request( 'register:change', 'addObject', newModel, null, label, data );

			return tmpID;
		},

		/**
		 * When the user drags a field type or staging over our sortable, we need to modify the helper.
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		overfieldsSortable: function( ui ) {
			if( jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) ) { // Field Type
				// String type
				var type = jQuery( ui.helper ).data( 'id' );
				// Get our field type model.
				var fieldType = nfRadio.channel( 'fields' ).request( 'get:type', type );
				// Get our field type nicename.
				var label = fieldType.get( 'nicename' );
				// Get our sortable element.
				var sortableEl = nfRadio.channel( 'fields' ).request( 'get:sortableEl' );
				// Get our fieldwidth.
				var fieldWidth = jQuery( sortableEl ).width();
				// Set our currentHelper to an object var so that we can access it later.
				this.currentHelper = ui.helper;

				// Render a fieldItemView using a mock fieldModel.
				var fieldModel = new FieldModel({ label: fieldType.get( 'nicename' ), type: type });
				var fieldItemView = new FieldItemView({model:fieldModel});
				var renderedFieldItemView = fieldItemView.render();
				var fieldTypeEl = renderedFieldItemView.$el[0];
				jQuery( ui.helper ).html( fieldTypeEl.outerHTML );

			} else if ( jQuery( ui.item ).hasClass( 'nf-stage' ) ) { // Staging
				// Get our sortable, and if it's initialized add our hover class.
				var sortableEl = nfRadio.channel( 'fields' ).request( 'get:sortableEl' );
				if ( jQuery( sortableEl ).hasClass( 'ui-sortable' ) ) {
					jQuery( sortableEl ).addClass( 'nf-droppable-hover' );
				}
			}
		},

		/**
		 * When the user moves a draggable outside of the sortable, we need to change the helper.
		 * This returns the item to its pre-over state.
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		outFieldsSortable: function( ui ) {
			if( jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) ) { // Field Type
				/*
				 * Get our helper clone.
				 * This will let us access the previous label and classes of our helper.
				 */ 
				var helperClone = nfRadio.channel( 'drawer-addField' ).request( 'get:typeHelperClone' );
				// Set our helper label, remove our sortable class, and add the type class back to the type draggable.
				jQuery( this.currentHelper ).html( jQuery( helperClone ).html() );
				jQuery( this.currentHelper ).removeClass( 'nf-field-wrap' ).addClass( 'nf-field-type-button' ).css( { 'width': '', 'height': '' } );
				// Get our sortable and if it has been intialized, remove the droppable hover class.
				var sortableEl = nfRadio.channel( 'fields' ).request( 'get:sortableEl' );
				if ( jQuery( sortableEl ).hasClass( 'ui-sortable' ) ) {
					jQuery( sortableEl ).removeClass( 'nf-droppable-hover' );
				}
			} else if ( jQuery( ui.item ).hasClass( 'nf-stage' ) ) { // Staging
				// If we've initialized our sortable, remove the droppable hover class.
				var sortableEl = nfRadio.channel( 'fields' ).request( 'get:sortableEl' );
				if ( jQuery( sortableEl ).hasClass( 'ui-sortable' ) ) {
					jQuery( sortableEl ).removeClass( 'nf-droppable-hover' );
				}
			}
		},

		/**
		 * When we stop dragging in the sortable:
		 * remove our opacity setting
		 * remove our ui helper
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		stopFieldsSortable: function( ui ) {
			jQuery( ui.item ).css( 'opacity', '' );
			jQuery( ui.helper ).remove();
			nfRadio.channel( 'fields' ).trigger( 'sortable:stop', ui );
		},

		/**
		 * When we start dragging in the sortable:
		 * add an opacity setting of 0.5
		 * show our item (jQuery hides the original item by default)
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		startFieldsSortable: function( ui ) {
			// If we aren't dragging an item in from types or staging, update our change log.
			if( ! jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) && ! jQuery( ui.item ).hasClass( 'nf-stage' ) ) { 
				
				// Maintain origional visibility during drag/sort.
				jQuery( ui.item ).show();

				// Determine helper based on builder/layout type.
				if(jQuery(ui.item).hasClass('nf-field-wrap')){
					var newHelper = jQuery(ui.item).clone();
				} else if(jQuery(ui.item).parent().hasClass('layouts-cell')) {
					var newHelper = $parentHelper.clone();
				} else {
					var newHelper = jQuery(ui.item).clone();
				}

				// Remove unecessary item controls from helper.
				newHelper.find('.nf-item-controls').remove();

				// Update helper with clone's content.
				jQuery( ui.helper ).html( newHelper.html() );

				jQuery( ui.helper ).css( 'opacity', '0.5' );
				
				// Add de-emphasize origional.
				jQuery( ui.item ).css( 'opacity', '0.25' );
			}
			nfRadio.channel( 'fields' ).trigger( 'sortable:start', ui );
		},

		/**
		 * Sort our fields when we change the order.
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		updateFieldsSortable: function( ui, sortable ) {
			
			nfRadio.channel( 'fields' ).request( 'sort:fields' );

			// If we aren't dragging an item in from types or staging, update our change log.
			if( ! jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) && ! jQuery( ui.item ).hasClass( 'nf-stage' ) ) { 

				var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
				var dragFieldID = jQuery( ui.item ).prop( 'id' ).replace( 'field-', '' );
				var dragModel = fieldCollection.get( dragFieldID );

				// Add our change event to the change tracker.
				var data = { fields: [] };
				_.each( fieldCollection.models, function( field ) {
					var oldPos = field._previousAttributes.order;
					var newPos = field.get( 'order' );
					
					data.fields.push( {
						model: field,
						attr: 'order',
						before: oldPos,
						after: newPos
					} );

				} );

				var label = {
					object: 'Field',
					label: dragModel.get( 'label' ),
					change: 'Re-ordered from ' + dragModel._previousAttributes.order + ' to ' + dragModel.get( 'order' ),
					dashicon: 'sort'
				};

				nfRadio.channel( 'changes' ).request( 'register:change', 'sortFields', dragModel, null, label, data );
			}

		},

		receiveRepeaterField: function( ui ){
			//If the field was already saved as a Repeater child field we'll delete it and create a new one for the main collection
			if( String( jQuery( ui.item ).data('id') ).indexOf('.') !== -1){
				jQuery( ui.item ).removeClass('nf-field-wrap');
				let type = jQuery( ui.item ).attr('class');
				jQuery( ui.item ).data('id', type);
				jQuery( ui.item ).addClass('nf-field-type-draggable');
			}

			return ui;

		}
	});

	return controller;
} );
/**
 * Handles interactions with our field collection.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/data',['models/fields/fieldCollection', 'models/fields/fieldModel'], function( fieldCollection, fieldModel ) {
	var controller = Marionette.Object.extend( {
		adding: false,
		removing: false,
		
		initialize: function() {
			// Load our field collection from our localized form data
			this.collection = new fieldCollection( preloadedFormData.fields );
			// Set our removedIDs to an empty object. This will be populated when a field is removed so that we can add it to our 'deleted_fields' object.
			this.collection.removedIDs = {};

			// Respond to requests for data about fields and to update/change/delete fields from our collection.
			nfRadio.channel( 'fields' ).reply( 'get:collection', this.getFieldCollection, this );
			nfRadio.channel( 'fields' ).reply( 'get:field', this.getField, this );
			nfRadio.channel( 'fields' ).reply( 'redraw:collection', this.redrawFieldCollection, this );
			nfRadio.channel( 'fields' ).reply( 'get:tmpID', this.getTmpFieldID, this );

			nfRadio.channel( 'fields' ).reply( 'add', this.addField, this );
			nfRadio.channel( 'fields' ).reply( 'delete', this.deleteField, this );
			nfRadio.channel( 'fields' ).reply( 'sort:fields', this.sortFields, this );

			/*
			 * Respond to requests to set our 'adding' and 'removing' state. This state is used to track whether or not
			 * we should run animations in our fields collection.
			 */
			nfRadio.channel( 'fields' ).reply( 'get:adding', this.getAdding, this );
			nfRadio.channel( 'fields' ).reply( 'set:adding', this.setAdding, this );
			nfRadio.channel( 'fields' ).reply( 'get:removing', this.getRemoving, this );
			nfRadio.channel( 'fields' ).reply( 'set:removing', this.setRemoving, this );
		},

		getFieldCollection: function() {
			return this.collection;
		},

		redrawFieldCollection: function() {
			this.collection.trigger( 'reset', this.collection );
		},

		getField: function( id ) {
			if ( this.collection.findWhere( { key: id } ) ) {
				/*
				 * First we check to see if a key matches what we were sent.
				 */				
				return this.collection.findWhere( { key: id } );
			} else {
				/*
				 * If it doesn't, we try to return an ID that matches.
				 */
				return this.collection.get( id );
			}
		},

		/**
		 * Add a field to our collection. If silent is passed as true, no events will trigger.
		 * 
		 * @since 3.0
		 * @param Object 	data 			field data to insert
		 * @param bool 		silent 			prevent events from firing as a result of adding
		 * @param bool  	renderTrigger	should this cause the view to re-render?
		 * @param string  	action			action context - are we performing a higher level action? i.e. duplicate
		 */
		addField: function( data, silent, renderTrigger, action ) {

			/*
			 * Set our fields 'adding' value to true. This enables our add field animation.
			 */
			nfRadio.channel( 'fields' ).request( 'set:adding', true );

			silent = silent || false;
			action = action || '';
			renderTrigger = ( 'undefined' == typeof renderTrigger ) ? true : renderTrigger;

			if ( false === data instanceof Backbone.Model ) {
				if ( 'undefined' == typeof ( data.id ) ) {
					data.id = this.getTmpFieldID();
				}
				var model = new fieldModel( data );
			} else {
				var model = data;
			}

			/*
			 * TODO: Add an nfRadio message filter for the model variable.
			 * Currently, we manually replace for saved fields; this should be moved to a separate controller.
			 * 
			 * If we're adding a saved field, make sure that we set the type to the parentType.
			 */

			if ( jQuery.isNumeric( model.get( 'type' ) ) ) {
				var savedType = nfRadio.channel( 'fields' ).request( 'get:type', model.get( 'type' ) );
				model.set( 'type', savedType.get( 'parentType' ) );
			}

			var newModel = this.collection.add( model, { silent: silent } );
			
			// Set our 'clean' status to false so that we get a notice to publish changes
			nfRadio.channel( 'app' ).request( 'update:setting', 'clean', false );
			nfRadio.channel( 'fields' ).trigger( 'add:field', model );
			if ( renderTrigger ) {
				nfRadio.channel( 'fields' ).trigger( 'render:newField', newModel, action );
			}
			if( 'duplicate' == action ){
                nfRadio.channel( 'fields' ).trigger( 'render:duplicateField', newModel, action );
			}
			nfRadio.channel( 'fields' ).trigger( 'after:addField', model );
			
			return model;
		},

		/**
		 * Update a field setting by ID
		 * 
		 * @since  3.0
		 * @param  int 		id    field id
		 * @param  string 	name  setting name
		 * @param  mixed 	value setting value
		 * @return void
		 */
		updateFieldSetting: function( id, name, value ) {
			var fieldModel = this.collection.get( id );
			fieldModel.set( name, value );
		},

		/**
		 * Get our fields sortable EL
		 * 
		 * @since  3.0
		 * @param  Array 	order optional order array like: [field-1, field-4, field-2]
		 * @return void
		 */
		sortFields: function( order, ui, updateDB ) {
			if ( null == updateDB ) {
				updateDB = true;
			}
			// Get our sortable element
			var sortableEl = nfRadio.channel( 'fields' ).request( 'get:sortableEl' );
			if ( jQuery( sortableEl ).hasClass( 'ui-sortable' ) ) { // Make sure that sortable is enabled
				// JS ternerary for setting our order
				var order = order || jQuery( sortableEl ).sortable( 'toArray' );

				// Loop through all of our fields and update their order value
				_.each( this.collection.models, function( field ) {
					// Get our current position.
					var oldPos = field.get( 'order' );
					var id = field.get( 'id' );
					if ( jQuery.isNumeric( id ) ) {
						var search = 'field-' + id;
					} else {
						var search = id;
					}
					
					// Get the index of our field inside our order array
					var newPos = order.indexOf( search ) + 1;
					field.set( 'order', newPos );
				} );
				this.collection.sort();

				if ( updateDB ) {
					// Set our 'clean' status to false so that we get a notice to publish changes
					nfRadio.channel( 'app' ).request( 'update:setting', 'clean', false );
					// Update our preview
					nfRadio.channel( 'app' ).request( 'update:db' );					
				}
			}
		},

		/**
		 * Delete a field from our collection.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	model 	field model to be deleted
		 * @return void
		 */
		deleteField: function( model ) {
			nfRadio.channel( 'fields' ).trigger( 'delete:field', model );
			this.removing = true;
			this.collection.remove( model );

			// Set our 'clean' status to false so that we get a notice to publish changes
			nfRadio.channel( 'app' ).request( 'update:setting', 'clean', false );
			nfRadio.channel( 'app' ).request( 'update:db' );

		},

		/**
		 * Return a new tmp id for our fields.
		 * Gets the field collection length, adds 1, then returns that prepended with 'tmp-'.
		 * 
		 * @since  3.0
		 * @return string
		 */
		getTmpFieldID: function() {
			var tmpNum = this.collection.tmpNum;
			this.collection.tmpNum++;
			return 'tmp-' + tmpNum;
		},

		getAdding: function() {
			return this.adding;
		},

		setAdding: function( val ) {
			this.adding = val;
		},

		getRemoving: function() {
			return this.removing;
		},

		setRemoving: function( val ) {
			this.removing = val;
		}
	});

	return controller;
} );
/**
 * Model for our repeater option.
 * 
 * @package Ninja App builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/optionRepeaterModel',[], function() {
	var model = Backbone.Model.extend( {
		defaults: {
			errors: {},
            max_options: 0,
		},

		initialize: function() {
			// When we add errors to the option row, run a function.
			this.on( 'change:errors', this.changeErrors, this );
		},

		/**
		 * When we change the errors on our model, check to see if we should add or remove 
		 * the error from the setting that this option is a part of.
		 *
		 * Adding an error to the setting model simply disables the drawer and other
		 * navigation. As long as we have one option with an error, it should be set to true.
		 * 
		 * @since  3.0
		 * @return void
		 */
		changeErrors: function( model ) {
			/*
			 * The errors attribute will be an object, so if we don't have any keys, it's empty.
			 * If we have an empty object, check to see if we can remove the error from our setting model.
			 */

			if ( 0 == _.size( model.get( 'errors' ) ) ) {
				/*
				 * Loop through our collection to see if we have any other errors.
				 */
				var errorsFound = false;
				_.each( model.collection.models, function( opt ) {
					if ( 0 != _.size( opt.get( 'errors' ) ) ) {
						errorsFound = true;
					}
				} );
				if ( ! errorsFound ) {
					model.collection.settingModel.set( 'error', false );
				}
			} else {
				/*
				 * We have errors, so make sure that the setting model has an error set.
				 */
				model.collection.settingModel.set( 'error', true );
			}
		}
	} );
	
	return model;
} );
/**
 * Model that represents our list options.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/app/optionRepeaterCollection',['models/app/optionRepeaterModel'], function( listOptionModel ) {
	var collection = Backbone.Collection.extend( {
		model: listOptionModel,
		comparator: function( model ){
			return parseInt( model.get( 'order' ) );
		},

		initialize: function( models, options ) {
			// Listen to the 'sort' event
			this.on( 'sort', this.changeCollection, this );
			// Listen to the 'add' event
			this.on( 'add', this.addOption, this );
			this.settingModel = options.settingModel;
		},

		changeCollection: function() {
			// Trigger a 'sort:options' event so that our field model can update
			nfRadio.channel( 'option-repeater' ).trigger( 'sort:options', this );

			if ('undefined' !== typeof this.settingModel ) {
				nfRadio.channel('option-repeater-' + this.settingModel.get('name')).trigger('sort:options', this);
			}
		},

		addOption: function( model, collection ) {
			model.set( 'settingModel', this.settingModel );
		}
	} );
	return collection;
} );
define( 'views/app/drawer/optionRepeaterError',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		className: 'nf-error',
		template: '#tmpl-nf-edit-setting-option-repeater-error',

		templateHelpers: function() {
			var that = this;
			return {
				renderErrors: function() {
				    if ( 'undefined' != typeof that.errors ) {
    					return that.errors[ Object.keys( errors )[0] ];
 					} else {
 						return '';
 					}
				}
			}
		}
	});

	return view;
} );
define( 'views/app/drawer/optionRepeaterOption',['views/app/drawer/optionRepeaterError'], function( ErrorView ) {
    var view = Marionette.LayoutView.extend({
        tagName: 'div',
        className: 'nf-table-row',
        template: '#tmpl-nf-edit-setting-option-repeater-default-row',
        id: function() {
            return this.model.cid;
        },

        regions: {
            error: '.nf-option-error'
        },

        initialize: function( data ) {
            this.settingModel = data.settingModel;
            this.dataModel = data.dataModel;
            this.collection = data.collection;
            this.columns = data.columns;
            this.parentView = data.parentView;
            this.model.on( 'change:errors', this.renderErrors, this );

            // Removed because the re-render was breaking tag insertion for merge tags.
            // this.model.on( 'change', this.render, this );

            if ( 'undefined' != typeof this.settingModel.get( 'tmpl_row' ) ) {
                this.template = '#' + this.settingModel.get( 'tmpl_row' );
            }

            this.hasErrors = false;
        },

        onBeforeDestroy: function() {    
            this.model.off( 'change', this.render );
            this.model.off( 'change:errors', this.renderErrors );
        },

        onBeforeRender: function() {
            /*
             * We want to escape any HTML being output for our label.
             */
            if ( this.model.get( 'label' ) ) {
                var label = this.model.get( 'label' );
                this.model.set( 'label', _.escape( label ), { silent: true } );
            }
            
        },

        onRender: function() {
            nfRadio.channel( 'mergeTags' ).request( 'init', this );
            /*
             * Send out a radio message.
             */
            nfRadio.channel( 'setting-' + this.settingModel.get( 'name' ) + '-option' ).trigger( 'render:setting', this.model, this.dataModel, this );
            /*
             * We want to unescape any HTML being output for our label.
             */
            if ( this.model.get( 'label' ) ) {
                var label = this.model.get( 'label' );
                this.model.set( 'label', _.unescape( label ), { silent: true } );
            }
        },

        onShow: function() {
            if ( this.model.get( 'new' ) ) {
                jQuery( this.el ).find( 'input:first' ).focus();
                this.model.set( 'new', false );
            }
        },

        events: {
            'change .setting': 'changeOption',
            'click .nf-delete': 'deleteOption',
            'keyup': 'keyupOption'
        },

        changeOption: function( e ) {
            nfRadio.channel( 'option-repeater' ).trigger( 'change:option', e, this.model, this.dataModel, this.settingModel, this );
        },

        deleteOption: function( e ) {
            nfRadio.channel( 'option-repeater' ).trigger( 'click:deleteOption', this.model, this.collection, this.dataModel, this );
        },

        keyupOption: function( e ) {
            this.maybeAddOption( e );
            nfRadio.channel( 'option-repeater' ).trigger( 'keyup:option', e, this.model, this.dataModel, this.settingModel, this )
            nfRadio.channel( 'option-repeater-' + this.settingModel.get( 'name' ) ).trigger( 'keyup:option', e, this.model, this.dataModel, this.settingModel, this )
        },

        maybeAddOption: function( e ) {
            if ( 13 == e.keyCode && 'calculations' != this.settingModel.get( 'name' ) ) {
                nfRadio.channel( 'option-repeater' ).trigger( 'click:addOption', this.collection, this.dataModel, this );
                jQuery( this.parentView.children.findByIndex(this.parentView.children.length - 1).el ).find( '[data-id="label"]' ).focus();
            }
        },

        renderErrors: function() {
            
            // if ( jQuery.isEmptyObject( this.model.get( 'errors' ) ) ) {
            //     return false;
            // }

            /*
             * We don't want to redraw the entire row, which would remove focus from the eq textarea,
             * so we add and remove error classes manually.
             */
            if ( 0 == Object.keys( this.model.get( 'errors' ) ) ) {
                if ( this.hasErrors ) {
                    this.error.empty();
                    jQuery( this.el ).removeClass( 'nf-error' );
                }
            } else {
                this.hasErrors = true;
                this.error.show( new ErrorView( { model: this.model } ) );
                jQuery( this.el ).addClass( 'nf-error' );
            }
        },

        templateHelpers: function() {
            var that = this;
            return {
                getColumns: function() {
                    var columns = that.columns;
                    if(!nfAdmin.devMode){
                        delete columns.value;
                        delete columns.calc;
                    }
                    return columns;
                },
                renderFieldSelect: function( dataID, value ){
                    var initialOption, select, emptyContainer, label;

                    var fields = nfRadio.channel( 'fields' ).request( 'get:collection' );

                    initialOption = document.createElement( 'option' );
                    initialOption.value = '';
                    initialOption.label = '--';
                    initialOption.innerHTML = '--';

                    select = document.createElement( 'select' );
                    select.classList.add( 'setting' );
                    select.setAttribute( 'data-id', dataID );
                    select.appendChild( initialOption );

                    fields.each( function( field ){
                        var option = document.createElement( 'option' );
                        if ( value == field.get( 'key' ) ) {
                            option.setAttribute( 'selected', 'selected' );
                        }
                        option.value = field.get( 'key' );
                        option.innerHTML = field.formatLabel();
                        option.label = field.formatLabel();
                        select.appendChild( option );
                    });

                    label = document.createElement( 'label' );
                    label.classList.add( 'nf-select' );
                    label.appendChild( select );

                    // Select Lists need an empty '<div></div>' for styling purposes.
                    emptyContainer = document.createElement( 'div' );
                    emptyContainer.style.bottom = '6px';
                    label.appendChild( emptyContainer );

                    // The template requires a string.
                    return label.innerHTML;
                },
                renderNonSaveFieldSelect: function( dataID, value ){
                    var initialOption, select, emptyContainer, label;

                    var fields = nfRadio.channel( 'fields' ).request( 'get:collection' );

                    initialOption = document.createElement( 'option' );
                    initialOption.value = '';
                    initialOption.label = '--';
                    initialOption.innerHTML = '--';

                    select = document.createElement( 'select' );
                    select.classList.add( 'setting' );
                    select.setAttribute( 'data-id', dataID );
                    select.appendChild( initialOption );

                    // Build a lookup table for fields we want to remove from our fields list.
                    var removeFieldsLookup = [ 'html', 'submit', 'hr',
                        'recaptcha', 'spam', 'creditcard', 'creditcardcvc',
                        'creditcardexpiration', 'creditcardfullname',
                        'creditcardnumber', 'creditcardzip' ];

                    fields.each( function( field ){
                        // Check for the field type in our lookup array and...
                        if( jQuery.inArray( field.get( 'type' ), removeFieldsLookup ) !== -1 ) {
                            // Return if the type is in our lookup array.
                            return '';
                        }

                        var option = document.createElement( 'option' );
                        if ( value == field.get( 'key' ) ) {
                            option.setAttribute( 'selected', 'selected' );
                        }
                        option.value = field.get( 'key' );
                        option.innerHTML = field.formatLabel();
                        option.label = field.formatLabel();
                        select.appendChild( option );
                    });

                    label = document.createElement( 'label' );
                    label.classList.add( 'nf-select' );
                    label.appendChild( select );

                    // Select Lists need an empty '<div></div>' for styling purposes.
                    emptyContainer = document.createElement( 'div' );
                    emptyContainer.style.bottom = '6px';
                    label.appendChild( emptyContainer );

                    // The template requires a string.
                    return label.innerHTML;
                },
                renderOptions: function( column, value ) {

                    if( 'undefined' == typeof that.options.columns[ column ] ) return;

                    var select = document.createElement( 'select' );
                    
                    _.each( that.options.columns[ column ].options, function( option ){
                        var optionNode = document.createElement( 'option' );
                        if ( value === option.value ) {
                            optionNode.setAttribute( 'selected', 'selected' );
                        }
                        optionNode.setAttribute( 'value', option.value );
                        optionNode.setAttribute( 'label', option.label );
                        optionNode.innerText = option.label;
                        select.appendChild( optionNode );
                    });

                    // The template only needs the options.
                    return select.innerHTML;
                }

            }
        }

    });

    return view;
} );

define( 'views/app/drawer/optionRepeaterEmpty',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'tr',
		template: '#tmpl-nf-edit-setting-option-repeater-empty'
	});

	return view;
} );
define( 'views/app/drawer/optionRepeaterComposite',['views/app/drawer/optionRepeaterOption', 'views/app/drawer/optionRepeaterEmpty', 'models/app/optionRepeaterCollection'], function( listOptionView, listEmptyView, listOptionCollection ) {
	var view = Marionette.CompositeView.extend( {
		template: '#tmpl-nf-edit-setting-option-repeater-wrap',
		childView: listOptionView,
		emptyView: listEmptyView,
		reorderOnSort: false,

		initialize: function( data ) {

			/*
			 * Our options are stored in our database as objects, not collections.
			 * Before we attempt to render them, we need to convert them to a collection if they aren't already one.
			 */ 
			var optionCollection = data.dataModel.get( this.model.get( 'name' ) );

			if ( false == optionCollection instanceof Backbone.Collection ) {
				optionCollection = new listOptionCollection( [], { settingModel: this.model } );
				optionCollection.add( data.dataModel.get( this.model.get( 'name' ) ) );
				data.dataModel.set( this.model.get( 'name' ), optionCollection, { silent: true } );
			}

			this.collection = optionCollection;
			this.dataModel = data.dataModel;
			this.childViewOptions = { parentView: this, settingModel: this.model, collection: this.collection, dataModel: data.dataModel, columns: this.model.get( 'columns' ) };

			var deps = this.model.get( 'deps' );
			if ( deps ) {
				// If we don't have a 'settings' property, this is a legacy depdency setup.
				if ( 'undefined' == typeof deps.settings ) {
					deps.settings = [];
					_.each(deps, function(dep, name){
						if( 'settings' !== name ) {
							deps.settings.push( { name: name, value: dep } );
						}
					});
					deps.match = 'all';
				}

				for (var i = deps.settings.length - 1; i >= 0; i--) {
					let name = deps.settings[i].name;
					this.dataModel.on( 'change:' + name, this.render, this );
				}
			}
            this.listenTo( nfRadio.channel( 'option-repeater' ), 'added:option', this.maybeHideNew );
            this.listenTo( nfRadio.channel( 'option-repeater' ), 'removed:option', this.maybeHideNew );
		},

		onBeforeDestroy: function() {
			var deps = this.model.get( 'deps' );
			if ( deps ) {
				for (var i = deps.settings.length - 1; i >= 0; i--) {
					name = deps.settings[i].name;
					this.dataModel.off( 'change:' + name, this.render );
				}
			}
		},

		onRender: function() {
			// this.$el = this.$el.children();
			// this.$el.unwrap();
			// this.setElement( this.$el );

			// this.$el = this.$el.children();
			// this.$el.unwrap();
			// this.setElement( this.$el );
		
			var that = this;
			jQuery( this.el ).find( '.nf-list-options-tbody' ).sortable( {
				handle: '.handle',
				helper: 'clone',
				placeholder: 'nf-list-options-sortable-placeholder',
				forcePlaceholderSize: true,
				opacity: 0.95,
				tolerance: 'pointer',

				start: function( e, ui ) {
					nfRadio.channel( 'option-repeater' ).request( 'start:optionSortable', ui );
				},

				stop: function( e, ui ) {
					nfRadio.channel( 'option-repeater' ).request( 'stop:optionSortable', ui );
				},

				update: function( e, ui ) {
					nfRadio.channel( 'option-repeater' ).request( 'update:optionSortable', ui, this, that );
				}
			} );

            that.setupTooltip();
            that.maybeHideNew( that.collection );

			/*
			 * Send out a radio message.
			 */
			nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'render:setting', this.model, this.dataModel, this );
		
		},

		onAttach: function() {
            
			var importLink = jQuery( this.el ).find( '.nf-open-import-tooltip' );
			var jBox = jQuery( importLink ).jBox( 'Tooltip', {
                title: '<h3>Please enter your options below:</h3>',
                content: ( "1" == nfAdmin.devMode ? jQuery( this.el ).find( '.nf-dev-import-options' ) : jQuery( this.el ).find( '.nf-import-options' ) ),
                trigger: 'click',
                closeOnClick: 'body',
                closeButton: 'box',
                offset: { x: 20, y: 0 },
                addClass: 'import-options',

                onOpen: function() {
                	var that = this;
                	setTimeout( function() { jQuery( that.content ).find( 'textarea' ).focus(); }, 200 );
                }
            } );

			jQuery( this.el ).find( '.nf-import' ).on( 'click', { view: this, jBox: jBox }, this.clickImport );

			/*
			 * Send out a radio message.
			 */
			nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'attach:setting', this.model, this.dataModel, this );
			nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'attach:setting', this.model, this.dataModel, this );
		},
        
        /**
         * Function to append jBox modals to each tooltip element in the option repeater.
         */
        setupTooltip: function() {
            // For each .nf-help in the option repeater...
            jQuery( this.el ).find( '.nf-list-options' ).find( '.nf-help' ).each(function() {
                // Get the content.
                var content = jQuery(this).next('.nf-help-text');
                // Declare the modal.
                jQuery( this ).jBox( 'Tooltip', {
                    content: content,
                    maxWidth: 200,
                    theme: 'TooltipBorder',
                    trigger: 'click',
                    closeOnClick: true
                })
            });
        },

		templateHelpers: function () {
			var that = this;

	    	return {
	    		renderHeaders: function() {
                    // If this is a Field...
                    // AND If the type includes 'list'...
                    if ( 'Field' == that.dataModel.get( 'objectType' ) && -1 !== that.dataModel.get( 'type' ).indexOf( 'list' ) ) {
                        // Declare help text.
                        var helpText, helpTextContainer, helpIcon, helpIconLink, helpTextWrapper;

                        helpText = document.createTextNode( nfi18n.valueChars );
                        helpTextContainer = document.createElement( 'div' );
                        helpTextContainer.classList.add( 'nf-help-text' );
                        helpTextContainer.appendChild( helpText );

                        helpIcon = document.createElement( 'span' );
                        helpIcon.classList.add( 'dashicons', 'dashicons-admin-comments' );
                        helpIconLink = document.createElement( 'a' );
                        helpIconLink.classList.add( 'nf-help' );
                        helpIconLink.setAttribute( 'href', '#' );
                        helpIconLink.setAttribute( 'tabindex', '-1' );
                        helpIconLink.appendChild( helpIcon );

                        helpTextWrapper = document.createElement( 'span' );
                        helpTextWrapper.appendChild( helpIconLink );
                        helpTextWrapper.appendChild( helpTextContainer );

						// Append the help text to the 'value' header.
						if('undefined' !== typeof that.model.get('columns') ){
							if('undefined' !== typeof that.model.get('columns').value ){
								if ( -1 == that.model.get('columns').value.header.indexOf( helpTextWrapper.innerHTML ) ) {
									that.model.get('columns').value.header += helpTextWrapper.innerHTML;
								}
							}
						}
                    }
	    			var columns, beforeColumns, afterColumns;

	    			beforeColumns = document.createElement( 'div' );

	    			columns = document.createElement( 'span' );
	    			columns.appendChild( beforeColumns );

					if(!nfAdmin.devMode){
						delete this.columns.value;
						delete this.columns.calc;
					}

	    			_.each( this.columns, function( col ) {
	    				var headerText, headerContainer;

	    				// Use a fragment to support HTML in the col.header property, ie Dashicons.
                        headerText = document.createRange().createContextualFragment( col.header );
	    				headerContainer = document.createElement( 'div' );
	    				headerContainer.appendChild( headerText );

	    				columns.appendChild( headerContainer );
	    			} );

                    afterColumns = document.createElement( 'div' );
                    columns.appendChild( afterColumns );

					return columns.innerHTML;
				},

	    		renderSetting: function() {
	    			var setting = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-edit-setting-' + this.type );
					return setting( this );
				},

				renderClasses: function() {
					var classes = '';
					if ( 'undefined' != typeof this.width ) {
						classes += this.width;
					} else {
						classes += ' one-half';
					}

					if ( this.error ) {
						classes += ' nf-error';
					}

					return classes;
				},

				renderVisible: function() {
					return nfRadio.channel( 'settings' ).request( 'check:deps', this, that );
	    		},

				renderError: function() {
					if ( this.error ) {
						return this.error;
					}
					return '';
				},

				renderFieldsetClasses: function() {
					return that.model.get( 'name' );
				},

				currencySymbol: function() {
					return nfRadio.channel( 'settings' ).request( 'get:setting', 'currency' ) || nfi18n.currency_symbol;
				}
			};
		},

		attachHtml: function( collectionView, childView ) {
			jQuery( collectionView.el ).find( '.nf-list-options-tbody' ).append( childView.el );
			nfRadio.channel( 'mergeTags' ).request( 'init', this );
		},

		events: {
			'click .nf-add-new': 'clickAddOption',
			'click .extra': 'clickExtra'
		},
        
        maybeHideNew: function( collection ) {
			if( 'undefined' == typeof collection.settingModel ) return false;
            var limit = collection.settingModel.get( 'max_options' );
            if( 0 !== limit && collection.models.length >= ( limit ) ) {
                jQuery(this.el).find('.nf-add-new').addClass('disabled');
            } else {
                jQuery(this.el).find('.nf-add-new').removeClass('disabled');
            }
        },

		clickAddOption: function( e ) {
			nfRadio.channel( 'option-repeater' ).trigger( 'click:addOption', this.collection, this.dataModel );
			jQuery( this.children.findByIndex(this.children.length - 1).el ).find( '[data-id="label"]' ).focus();
		},

		clickExtra: function( e ) {
			nfRadio.channel( 'option-repeater' ).trigger( 'click:extra', e, this.collection, this.dataModel );
			nfRadio.channel( 'option-repeater-' + this.model.get( 'name' ) ).trigger( 'click:extra', e, this.model, this.collection, this.dataModel );
		},

		clickImport: function( e ) {
			var textarea = jQuery( e.data.jBox.content ).find( 'textarea' );
			var value = textarea.val().trimLeft().trimRight();
			/*
			 * Return early if we have no strings.
			 */
			if ( 0 == value.length ) {
				e.data.jBox.close();
				return false;
			}			
			/*
			 * Split our value based on new lines.
			 */

			var lines = value.split(/\n/);
			if ( _.isArray( lines ) ) {
				/*
				 * Loop over 
				 */
				_.each( lines, function( line ) {
					var row = line.split( ',' );
					var label = row[0];
					var value = row[1] || jQuery.slugify( label, { separator: '-' } );
					var calc = row[2] || '';

					label = label.trimLeft().trimRight();
					value = value.trimLeft().trimRight();
					calc = calc.trimLeft().trimRight();
					/*
					 * Add our row to the collection
					 */
					var model = e.data.view.collection.add( { label: row[0], value: value, calc: calc } );
					// Add our field addition to our change log.
					var label = {
						object: 'field',
						label: row[0],
						change: 'Option Added',
						dashicon: 'plus-alt'
					};

					nfRadio.channel( 'changes' ).request( 'register:change', 'addListOption', model, null, label );
					nfRadio.channel( 'option-repeater-' + e.data.view.model.get( 'name' ) ).trigger( 'add:option', model );
					nfRadio.channel( 'option-repeater' ).trigger( 'add:option', model );
					nfRadio.channel( 'app' ).trigger( 'update:setting', model );
				}, this );
				/*
				 * Set our state to unclean so that the user can publish.
				 */
			} else {
				/*
				 * TODO: Error Handling Here
				 */
			}
			textarea.val( '' );
			e.data.jBox.close();
		},
	} );

	return view;
} );

/**
 * Handles tasks associated with our option-repeater.
 * 
 * Return our repeater child view.
 *
 * Also listens for changes to the options settings.
 * 
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/optionRepeater',['models/app/optionRepeaterModel', 'models/app/optionRepeaterCollection', 'views/app/drawer/optionRepeaterComposite'], function( listOptionModel, listOptionCollection, listCompositeView ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Respond to requests for the childView for list type fields.
			nfRadio.channel( 'option-repeater' ).reply( 'get:settingChildView', this.getSettingChildView, this );
			
			// Listen for changes to our list options.
			this.listenTo( nfRadio.channel( 'option-repeater' ), 'change:option', this.changeOption );
			this.listenTo( nfRadio.channel( 'option-repeater' ), 'click:addOption', this.addOption );
			this.listenTo( nfRadio.channel( 'option-repeater' ), 'click:deleteOption', this.deleteOption );

			// Respond to requests related to our list options sortable.
			nfRadio.channel( 'option-repeater' ).reply( 'update:optionSortable', this.updateOptionSortable, this );
			nfRadio.channel( 'option-repeater' ).reply( 'stop:optionSortable', this.stopOptionSortable, this );
			nfRadio.channel( 'option-repeater' ).reply( 'start:optionSortable', this.startOptionSortable, this );
		
			/**
			 * When we init our setting model, we need to convert our array/objects into collections/models
			 */
			this.listenTo( nfRadio.channel( 'option-repeater' ), 'init:dataModel', this.convertSettings );
		},

		/**
		 * Update an option value in our model.
		 * 
		 * @since  3.0
		 * @param  Object			e          event
		 * @param  backbone.model 	model      option model
		 * @param  backbone.model 	dataModel
		 * @return void
		 */
		changeOption: function( e, model, dataModel, settingModel, optionView ) {
			var name = jQuery( e.target ).data( 'id' );
			if ( 'selected' == name ) {
				if ( jQuery( e.target ).prop( 'checked' ) ) {
					var value = 1;
				} else {
					var value = 0;
				}
			} else {
				var value = jQuery( e.target ).val();
			}
			
			var before = model.get( name );
			
			model.set( name, value );
			// Trigger an update on our dataModel
			this.triggerDataModel( model, dataModel );

			var after = value;
			
			var changes = {
				attr: name,
				before: before,
				after: after
			}

			var label = {
				object: dataModel.get( 'objectType' ),
				label: dataModel.get( 'label' ),
				change: 'Option ' + model.get( 'label' ) + ' ' + name + ' changed from ' + before + ' to ' + after
			};

			nfRadio.channel( 'changes' ).request( 'register:change', 'changeSetting', model, changes, label );
			nfRadio.channel( 'option-repeater' ).trigger( 'update:option', model, dataModel, settingModel, optionView );
			nfRadio.channel( 'option-repeater-option-' + name  ).trigger( 'update:option', e, model, dataModel, settingModel, optionView );
			nfRadio.channel( 'option-repeater-' + settingModel.get( 'name' ) ).trigger( 'update:option', model, dataModel, settingModel, optionView );
		},

		/**
		 * Add an option to our list
		 * 
		 * @since 3.0
		 * @param backbone.collection 	collection 	list option collection
		 * @param backbone.model 		dataModel
		 * @return void
		 */
		addOption: function( collection, dataModel ) {
			var modelData = {
				order: collection.length,
				new: true,
				options: {}
			};
			/**
			 * If we don't actually have a 'settingModel' duplicated fields
			 * can't add options until publish and the builder is reloaded.
			 * If we ignore the code if we don't have settingsModel, then it
			 * works.
			 */
			if  ( 'undefined' !== typeof collection.settingModel ) {
				var limit = collection.settingModel.get( 'max_options' );
				if ( 0 !== limit && collection.models.length >= limit ) {
					return;
				}
				_.each( collection.settingModel.get( 'columns' ), function ( col, key ) {
					modelData[ key ] = col.default;

					if ( 'undefined' != typeof col.options ) {
						modelData.options[ key ] = col.options;
					}
				});
			}
			var model = new listOptionModel( modelData );
			collection.add( model );

			// Add our field addition to our change log.
			var label = {
				object: dataModel.get( 'objectType' ),
				label: dataModel.get( 'label' ),
				change: 'Option Added',
				dashicon: 'plus-alt'
			};

			nfRadio.channel( 'changes' ).request( 'register:change', 'addListOption', model, null, label );

			if ( 'undefined' !== typeof collection.settingModel ) {
				nfRadio.channel('option-repeater-' + collection.settingModel.get('name')).trigger('add:option', model);
			}
			nfRadio.channel( 'option-repeater' ).trigger( 'add:option', model );
			nfRadio.channel( 'option-repeater' ).trigger( 'added:option', collection );
			this.triggerDataModel( model, dataModel );
		},

		/**
		 * Delete an option from our list
		 * 
		 * @since  3.0
		 * @param backbone.model 		model       list option model
		 * @param backbone.collection 	collection 	list option collection
		 * @param backbone.model 		dataModel
		 * @return void
		 */
		deleteOption: function( model, collection, dataModel ) {
			var newModel = nfRadio.channel( 'app' ).request( 'clone:modelDeep', model );

			// Add our field deletion to our change log.
			var label = {
				object: dataModel.get( 'objectType' ),
				label: dataModel.get( 'label' ),
				change: 'Option ' + newModel.get( 'label' ) + ' Removed',
				dashicon: 'dismiss'
			};

			var data = {
				collection: collection
			}

			nfRadio.channel( 'changes' ).request( 'register:change', 'removeListOption', newModel, null, label, data );
			
			var changeCollection = nfRadio.channel( 'changes' ).request( 'get:collection' );
			var results = changeCollection.where( { model: model } );

			_.each( results, function( changeModel ) {
				if ( 'object' == typeof changeModel.get( 'data' ) ) {
					_.each( changeModel.get( 'data' ), function( dataModel ) {
						if ( dataModel.model == dataModel ) {
							dataModel.model = newModel;
						}
					} );
				}
				changeModel.set( 'model', newModel );
				changeModel.set( 'disabled', true );
			} );

			collection.remove( model );
			nfRadio.channel( 'option-repeater' ).trigger( 'remove:option', model );
			nfRadio.channel( 'option-repeater' ).trigger( 'removed:option', collection );
			nfRadio.channel( 'option-repeater-' + collection.settingModel.get( 'name' ) ).trigger( 'remove:option', model );
			this.triggerDataModel( model, dataModel );
		},

		/**
		 * Creates an arbitrary value on our collection, then clones and updates that collection.
		 * This forces a change event to be fired on the dataModel where the list option collection data is stored.
		 * 
		 * @since  3.0
		 * @param backbone.collection 	collection 	list option collection
		 * @param backbone.model 		dataModel
		 * @return void
		 */
		triggerDataModel: function( model, dataModel ) {
			nfRadio.channel( 'app' ).trigger( 'update:setting', model );	
		},

		/**
		 * Return our list composite view to the setting collection view.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	model 	settings model
		 * @return void
		 */
		getSettingChildView: function( model ) {
			return listCompositeView;
		},

		/**
		 * When we sort our list options, change the order in our option model and trigger a change.
		 * 
		 * @since  3.0
		 * @param  Object	 		sortable 	jQuery UI element
		 * @param  backbone.view 	setting  	Setting view
		 * @return void
		 */
		updateOptionSortable: function( ui, sortable, setting ) {
			var newOrder = jQuery( sortable ).sortable( 'toArray' );
			var dragModel = setting.collection.get( { cid: jQuery( ui.item ).prop( 'id' ) } );
			var data = {
				collection: setting.collection,
				objModels: []
			};

			_.each( newOrder, function( cid, index ) {
				var optionModel = setting.collection.get( { cid: cid } );
				var oldPos = optionModel.get( 'order' );
				optionModel.set( 'order', index );
				var newPos = index;

				data.objModels.push( {
					model: optionModel,
					attr: 'order',
					before: oldPos,
					after: newPos
				} );
			} );
			
			setting.collection.sort( { silent: true } );
			
			var label = {
				object: setting.dataModel.get( 'objectType' ),
				label: setting.dataModel.get( 'label' ),
				change: 'Option ' + dragModel.get( 'label' ) + ' re-ordered from ' + dragModel._previousAttributes.order + ' to ' + dragModel.get( 'order' ),
				dashicon: 'sort'
			};

			nfRadio.channel( 'changes' ).request( 'register:change', 'sortListOptions', dragModel, null, label, data );
			this.triggerDataModel( dragModel, setting.dataModel );
			nfRadio.channel( 'option-repeater' ).trigger( 'sort:option', dragModel, setting );
			nfRadio.channel( 'option-repeater-' + setting.model.get( 'name' ) ).trigger( 'sort:option', dragModel, setting );
		},

		/**
		 * When we stop sorting our list options, reset our item opacity.
		 * 
		 * @since  3.0
		 * @param  Object ui jQuery UI element
		 * @return void
		 */
		stopOptionSortable: function( ui ) {
			jQuery( ui.item ).css( 'opacity', '' );
		},

		/**
		 * When we start sorting our list options, remove containing divs and set our item opacity to 0.5
		 * 
		 * @since  3.0
		 * @param  Objects ui jQuery UI element
		 * @return void
		 */
		startOptionSortable: function( ui ) {
			jQuery( ui.placeholder ).find( 'div' ).remove();
			jQuery( ui.item ).css( 'opacity', '0.5' ).show();
		},

		/**
		 * Convert settings from an array/object to a collection/model
		 * 
		 * @since  3.0
		 * @param  Backbone.Model dataModel
		 * @param  Backbone.Model settingModel
		 * @return void
		 */
		convertSettings: function( dataModel, settingModel ) {
			/*
			 * Our options are stored in our database as objects, not collections.
			 * Before we attempt to render them, we need to convert them to a collection if they aren't already one.
			 */ 
			var optionCollection = dataModel.get( settingModel.get( 'name' ) );

			if ( false == optionCollection instanceof Backbone.Collection ) {
				optionCollection = new listOptionCollection( [], { settingModel: settingModel } );
				optionCollection.add( dataModel.get( settingModel.get( 'name' ) ) );
				dataModel.set( settingModel.get( 'name' ), optionCollection, { silent: true } );
			}
		}

	});

	return controller;
} );
define( 'views/app/drawer/imageOptionRepeaterOption',['views/app/drawer/optionRepeaterError'], function( ErrorView ) {
    var view = Marionette.LayoutView.extend({
        tagName: 'div',
        className: 'nf-table-row',
        template: '#tmpl-nf-edit-setting-image-option-repeater-default-row',
        id: function() {
            return this.model.cid;
        },

        regions: {
            error: '.nf-option-error'
        },

        initialize: function( data ) {
            this.settingModel = data.settingModel;
            this.dataModel = data.dataModel;
            this.collection = data.collection;
            this.columns = data.columns;
            this.parentView = data.parentView;
            this.model.on( 'change:errors', this.renderErrors, this );

            // Removed because the re-render was breaking tag insertion for merge tags.
            // this.model.on( 'change', this.render, this );

            if ( 'undefined' != typeof this.settingModel.get( 'tmpl_row' ) ) {
                this.template = '#' + this.settingModel.get( 'tmpl_row' );
            }

            this.listenTo( nfRadio.channel( 'image-option-repeater' ), 'click:extra', this.clickExtra );

            this.hasErrors = false;
        },

        onBeforeDestroy: function() {    
            this.model.off( 'change', this.render );
            this.model.off( 'change:errors', this.renderErrors );
        },

        onBeforeRender: function() {
            /*
             * We want to escape any HTML being output for our image.
             */
            if ( this.model.get( 'image' ) ) {
                var image = this.model.get( 'image' );
                this.model.set( 'image', _.escape( image ), { silent: true } );
            }
            
        },

        onRender: function() {
            nfRadio.channel( 'mergeTags' ).request( 'init', this );
            /*
             * Send out a radio message.
             */
            nfRadio.channel( 'setting-' + this.settingModel.get( 'name' ) + '-option' ).trigger( 'render:setting', this.model, this.dataModel, this );
            /*
             * We want to unescape any HTML being output for our image.
             */
            if ( this.model.get( 'image' ) ) {
                var image = this.model.get( 'image' );
                this.model.set( 'image', _.unescape( image ), { silent: true } );
            }
        },

        onShow: function() {
            if ( this.model.get( 'new' ) ) {
                jQuery( this.el ).find( 'input:first' ).focus();
                this.model.set( 'new', false );
            }
        },

        events: {
            'change .setting': 'changeOption',
            'click .nf-delete': 'deleteOption',
            'keyup': 'keyupOption',
            // 'click .open-media-manager': 'openMediaModal'
        },

        changeOption: function( e ) {
            nfRadio.channel( 'image-option-repeater' ).trigger( 'change:option', e, this.model, this.dataModel, this.settingModel, this );
        },

        deleteOption: function( e ) {
            nfRadio.channel( 'image-option-repeater' ).trigger( 'click:deleteOption', this.model, this.collection, this.dataModel, this );
        },

        keyupOption: function( e ) {
            this.maybeAddOption( e );
            nfRadio.channel( 'image-option-repeater' ).trigger( 'keyup:option', e, this.model, this.dataModel, this.settingModel, this )
            nfRadio.channel( 'image-option-repeater-' + this.settingModel.get( 'name' ) ).trigger( 'keyup:option', e, this.model, this.dataModel, this.settingModel, this )
        },

        maybeAddOption: function( e ) {
            if ( 13 == e.keyCode && 'calculations' != this.settingModel.get( 'name' ) ) {
                nfRadio.channel( 'image-option-repeater' ).trigger( 'click:addOption', this.collection, this.dataModel, this );
                jQuery( this.parentView.children.findByIndex(this.parentView.children.length - 1).el ).find( '[data-id="image"]' ).focus();
            }
        },

        clickExtra: function(e, settingModel, dataModel, settingView) {
            
            var textEl = jQuery(e.target).parent().find('.setting');
            var optionContainerDiv = jQuery(e.target).parent().parent().parent();

            var valueEl = jQuery(optionContainerDiv[0]).find('[data-id="value"]');

            var imageIdEl = jQuery(optionContainerDiv[0]).find('[data-id="image_id"]');

            var labelEl = jQuery(optionContainerDiv[0]).find('[data-id="label"]');
            
            if ( jQuery( e.target ).hasClass( 'open-media-manager' )
                && this.el.id === optionContainerDiv[0].id) {
                // If the frame already exists, re-open it.
                if ( this.meta_image_frame ) {
                    this.meta_image_frame.open();
                    return;
                }

                // Sets up the media library frame
                this.meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
                    title: 'Select a file',
                    button: { text:  'insert' }
                });

                var that = this;

                // Runs when an image is selected.
                this.meta_image_frame.on('select', function(){
                    // Grabs the attachment selection and creates a JSON representation of the model.
                    var media_attachment = that.meta_image_frame.state().get('selection').first().toJSON();
                    
                    textEl.val(media_attachment.url).change();
                    valueEl.val(media_attachment.filename).change();
                    labelEl.val(media_attachment.title).change();
                    imageIdEl.val(media_attachment.id).change();
                    var img_container = optionContainerDiv.find('.option-image-container');

                    if(img_container) {
                        $imgs = jQuery(img_container).find('img');
                        if($imgs.length > 0) {
                            jQuery($imgs[0]).attr('src', media_attachment.url);
                        } else {
                            var new_img = document.createElement('img');
                            new_img.style="max-width:100px;display:inline-block;";
                            new_img.src = media_attachment.url;
                            jQuery(img_container).append(new_img);
                        }
                    }
                });

                // Opens the media library frame.
                this.meta_image_frame.open();
            }
        },

        renderErrors: function() {
            
            // if ( jQuery.isEmptyObject( this.model.get( 'errors' ) ) ) {
            //     return false;
            // }

            /*
             * We don't want to redraw the entire row, which would remove focus from the eq textarea,
             * so we add and remove error classes manually.
             */
            if ( 0 == Object.keys( this.model.get( 'errors' ) ) ) {
                if ( this.hasErrors ) {
                    this.error.empty();
                    jQuery( this.el ).removeClass( 'nf-error' );
                }
            } else {
                this.hasErrors = true;
                this.error.show( new ErrorView( { model: this.model } ) );
                jQuery( this.el ).addClass( 'nf-error' );
            }
        },

        templateHelpers: function() {
            var that = this;
            return {
                getColumns: function() {
                    var columns = that.columns;
                    if(!nfAdmin.devMode){
                        delete columns.value;
                        delete columns.calc;
                    }
                    return columns;
                },
                renderFieldSelect: function( dataID, value ){
                    var initialOption, select, emptyContainer, image;

                    var fields = nfRadio.channel( 'fields' ).request( 'get:collection' );

                    initialOption = document.createElement( 'option' );
                    initialOption.value = '';
                    initialOption.image = '';
                    initialOption.innerHTML = '--';

                    select = document.createElement( 'select' );
                    select.classList.add( 'setting' );
                    select.setAttribute( 'data-id', dataID );
                    select.appendChild( initialOption );

                    fields.each( function( field ){
                        var option = document.createElement( 'option' );
                        if ( value == field.get( 'key' ) ) {
                            option.setAttribute( 'selected', 'selected' );
                        }
                        option.value = field.get( 'key' );
                        option.innerHTML = field.formatLabel();
                        option.image = field.formatLabel();
                        select.appendChild( option );
                    });

                    image = document.createElement( 'image' );
                    image.classList.add( 'nf-select' );
                    image.appendChild( select );

                    // Select Lists need an empty '<div></div>' for styling purposes.
                    emptyContainer = document.createElement( 'div' );
                    emptyContainer.style.bottom = '6px';
                    image.appendChild( emptyContainer );

                    // The template requires a string.
                    return image.innerHTML;
                },
                renderNonSaveFieldSelect: function( dataID, value ){
                    var initialOption, select, emptyContainer, image;

                    var fields = nfRadio.channel( 'fields' ).request( 'get:collection' );

                    initialOption = document.createElement( 'option' );
                    initialOption.value = '';
                    initialOption.image = '';
                    initialOption.innerHTML = '--';

                    select = document.createElement( 'select' );
                    select.classList.add( 'setting' );
                    select.setAttribute( 'data-id', dataID );
                    select.appendChild( initialOption );

                    // Build a lookup table for fields we want to remove from our fields list.
                    var removeFieldsLookup = [ 'html', 'submit', 'hr',
                        'recaptcha', 'spam', 'creditcard', 'creditcardcvc',
                        'creditcardexpiration', 'creditcardfullname',
                        'creditcardnumber', 'creditcardzip' ];

                    fields.each( function( field ){
                        // Check for the field type in our lookup array and...
                        if( jQuery.inArray( field.get( 'type' ), removeFieldsLookup ) !== -1 ) {
                            // Return if the type is in our lookup array.
                            return '';
                        }

                        var option = document.createElement( 'option' );
                        if ( value == field.get( 'key' ) ) {
                            option.setAttribute( 'selected', 'selected' );
                        }
                        option.value = field.get( 'key' );
                        option.innerHTML = field.formatLabel();
                        option.image = field.formatLabel();
                        select.appendChild( option );
                    });

                    image = document.createElement( 'image' );
                    image.classList.add( 'nf-select' );
                    image.appendChild( select );

                    // Select Lists need an empty '<div></div>' for styling purposes.
                    emptyContainer = document.createElement( 'div' );
                    emptyContainer.style.bottom = '6px';
                    image.appendChild( emptyContainer );

                    // The template requires a string.
                    return image.innerHTML;
                },
                renderOptions: function( column, value ) {

                    if( 'undefined' == typeof that.options.columns[ column ] ) return;

                    var select = document.createElement( 'select' );
                    
                    _.each( that.options.columns[ column ].options, function( option ){
                        var optionNode = document.createElement( 'option' );
                        if ( value === option.value ) {
                            optionNode.setAttribute( 'selected', 'selected' );
                        }
                        optionNode.setAttribute( 'value', option.value );
                        optionNode.setAttribute( 'image_id', option.image_id);
                        optionNode.setAttribute( 'image', option.image );
                        optionNode.innerText = option.image;
                        select.appendChild( optionNode );
                    });

                    // The template only needs the options.
                    return select.innerHTML;
                }

            }
        }

    });

    return view;
} );

define( 'views/app/drawer/imageOptionRepeaterComposite',['views/app/drawer/imageOptionRepeaterOption', 'views/app/drawer/optionRepeaterEmpty', 'models/app/optionRepeaterCollection'], function( listOptionView, listEmptyView, listOptionCollection ) {
	var view = Marionette.CompositeView.extend( {
		template: '#tmpl-nf-edit-setting-image-option-repeater-wrap',
		childView: listOptionView,
		emptyView: listEmptyView,
		reorderOnSort: false,

		initialize: function( data ) {

			/*
			 * Our options are stored in our database as objects, not collections.
			 * Before we attempt to render them, we need to convert them to a collection if they aren't already one.
			 */ 
			var optionCollection = data.dataModel.get( this.model.get( 'name' ) );

			if ( false == optionCollection instanceof Backbone.Collection ) {
				optionCollection = new listOptionCollection( [], { settingModel: this.model } );
				optionCollection.add( data.dataModel.get( this.model.get( 'name' ) ) );
				data.dataModel.set( this.model.get( 'name' ), optionCollection, { silent: true } );
			}

			this.collection = optionCollection;
			this.dataModel = data.dataModel;
			this.childViewOptions = { parentView: this, settingModel: this.model, collection: this.collection, dataModel: data.dataModel, columns: this.model.get( 'columns' ) };

			var deps = this.model.get( 'deps' );
			if ( deps ) {
				// If we don't have a 'settings' property, this is a legacy depdency setup.
				if ( 'undefined' == typeof deps.settings ) {
					deps.settings = [];
					_.each(deps, function(dep, name){
						if( 'settings' !== name ) {
							deps.settings.push( { name: name, value: dep } );
						}
					});
					deps.match = 'all';
				}

				for (var i = deps.settings.length - 1; i >= 0; i--) {
					let name = deps.settings[i].name;
					this.dataModel.on( 'change:' + name, this.render, this );
				}
			}
            this.listenTo( nfRadio.channel( 'image-option-repeater' ), 'added:option', this.maybeHideNew );
            this.listenTo( nfRadio.channel( 'image-option-repeater' ), 'removed:option', this.maybeHideNew );
		},

		onBeforeDestroy: function() {
			var deps = this.model.get( 'deps' );
			if ( deps ) {
				for (var i = deps.settings.length - 1; i >= 0; i--) {
					let name = deps.settings[i].name;
					this.dataModel.off( 'change:' + name, this.render );
				}
			}
		},

		onRender: function() {
			// this.$el = this.$el.children();
			// this.$el.unwrap();
			// this.setElement( this.$el );

			// this.$el = this.$el.children();
			// this.$el.unwrap();
			// this.setElement( this.$el );
		
			var that = this;
			jQuery( this.el ).find( '.nf-listimage-options-tbody' ).sortable( {
				handle: '.handle',
				helper: 'clone',
				placeholder: 'nf-listimage-options-sortable-placeholder',
				forcePlaceholderSize: true,
				opacity: 0.95,
				tolerance: 'pointer',

				start: function( e, ui ) {
					nfRadio.channel( 'image-option-repeater' ).request( 'start:optionSortable', ui );
				},

				stop: function( e, ui ) {
					nfRadio.channel( 'image-option-repeater' ).request( 'stop:optionSortable', ui );
				},

				update: function( e, ui ) {
					nfRadio.channel( 'image-option-repeater' ).request( 'update:optionSortable', ui, this, that );
				}
			} );

            that.setupTooltip();
            that.maybeHideNew( that.collection );

			/*
			 * Send out a radio message.
			 */
			nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'render:setting', this.model, this.dataModel, this );
		
		},

		onAttach: function() {
            
			// var importLink = jQuery( this.el ).find( '.nf-open-import-tooltip' );
			// var jBox = jQuery( importLink ).jBox( 'Tooltip', {
            //     title: '<h3>Please enter your options below:</h3>',
            //     content: ( "1" == nfAdmin.devMode ? jQuery( this.el ).find( '.nf-dev-import-options' ) : jQuery( this.el ).find( '.nf-import-options' ) ),
            //     trigger: 'click',
            //     closeOnClick: 'body',
            //     closeButton: 'box',
            //     offset: { x: 20, y: 0 },
            //     addClass: 'import-options',

            //     onOpen: function() {
            //     	var that = this;
            //     	setTimeout( function() { jQuery( that.content ).find( 'textarea' ).focus(); }, 200 );
            //     }
            // } );

			// jQuery( this.el ).find( '.nf-import' ).on( 'click', { view: this, jBox: jBox }, this.clickImport );

			// /*
			//  * Send out a radio message.
			//  */
			// nfRadio.channel( 'setting-' + this.model.get( 'name' ) ).trigger( 'attach:setting', this.model, this.dataModel, this );
			// nfRadio.channel( 'setting-type-' + this.model.get( 'type' ) ).trigger( 'attach:setting', this.model, this.dataModel, this );
		},
        
        /**
         * Function to append jBox modals to each tooltip element in the option repeater.
         */
        setupTooltip: function() {
            // For each .nf-help in the option repeater...
            jQuery( this.el ).find( '.nf-listimage-options' ).find( '.nf-help' ).each(function() {
                // Get the content.
                var content = jQuery(this).next('.nf-help-text');
                // Declare the modal.
                jQuery( this ).jBox( 'Tooltip', {
                    content: content,
                    maxWidth: 200,
                    theme: 'TooltipBorder',
                    trigger: 'click',
                    closeOnClick: true
                })
            });
        },

		templateHelpers: function () {
			var that = this;

	    	return {
	    		renderHeaders: function() {
                    // If this is a Field...
                    // AND If the type includes 'list'...
                    if ( 'Field' == that.dataModel.get( 'objectType' ) && -1 !== that.dataModel.get( 'type' ).indexOf( 'list' ) ) {
                        // Declare help text.
                        var helpText, helpTextContainer, helpIcon, helpIconLink, helpTextWrapper;

                        helpText = document.createTextNode( nfi18n.valueChars );
                        helpTextContainer = document.createElement( 'div' );
                        helpTextContainer.classList.add( 'nf-help-text' );
                        helpTextContainer.appendChild( helpText );

                        helpIcon = document.createElement( 'span' );
                        helpIcon.classList.add( 'dashicons', 'dashicons-admin-comments' );
                        helpIconLink = document.createElement( 'a' );
                        helpIconLink.classList.add( 'nf-help' );
                        helpIconLink.setAttribute( 'href', '#' );
                        helpIconLink.setAttribute( 'tabindex', '-1' );
                        helpIconLink.appendChild( helpIcon );

                        helpTextWrapper = document.createElement( 'span' );
                        helpTextWrapper.appendChild( helpIconLink );
                        helpTextWrapper.appendChild( helpTextContainer );

						// Append the help text to the 'value' header.
						if('undefined' !== typeof that.model.get('columns') ){
							if('undefined' !== typeof that.model.get('columns').value ){
								if ( -1 == that.model.get('columns').value.header.indexOf( helpTextWrapper.innerHTML ) ) {
									that.model.get('columns').value.header += helpTextWrapper.innerHTML;
								}
							}
						}
                    }
	    			var columns, beforeColumns, afterColumns;

	    			beforeColumns = document.createElement( 'div' );

	    			columns = document.createElement( 'span' );
	    			columns.appendChild( beforeColumns );

					if(!nfAdmin.devMode){
						delete this.columns.value;
						delete this.columns.calc;
					}

	    			_.each( this.columns, function( col ) {
	    				var headerText, headerContainer;

	    				// Use a fragment to support HTML in the col.header property, ie Dashicons.
                        headerText = document.createRange().createContextualFragment( col.header );
	    				headerContainer = document.createElement( 'div' );
	    				headerContainer.appendChild( headerText );

	    				columns.appendChild( headerContainer );
	    			} );

                    afterColumns = document.createElement( 'div' );
                    columns.appendChild( afterColumns );

					return columns.innerHTML;
				},

	    		renderSetting: function() {
	    			var setting = nfRadio.channel( 'app' ).request( 'get:template',  '#tmpl-nf-edit-setting-' + this.type );
					return setting( this );
				},

				renderClasses: function() {
					var classes = '';
					if ( 'undefined' != typeof this.width ) {
						classes += this.width;
					} else {
						classes += ' one-half';
					}

					if ( this.error ) {
						classes += ' nf-error';
					}

					return classes;
				},

				renderVisible: function() {
					return nfRadio.channel( 'settings' ).request( 'check:deps', this, that );
	    		},

				renderError: function() {
					if ( this.error ) {
						return this.error;
					}
					return '';
				},

				renderFieldsetClasses: function() {
					return that.model.get( 'name' );
				},

				currencySymbol: function() {
					return nfRadio.channel( 'settings' ).request( 'get:setting', 'currency' ) || nfi18n.currency_symbol;
				}
			};
		},

		attachHtml: function( collectionView, childView ) {
			jQuery( collectionView.el ).find( '.nf-listimage-options-tbody' ).append( childView.el );
			nfRadio.channel( 'mergeTags' ).request( 'init', this );
		},

		events: {
			'click .nf-add-new': 'clickAddOption',
			'click .extra': 'clickExtra'
		},
        
        maybeHideNew: function( collection ) {
			if( 'undefined' == typeof collection.settingModel ) return false;
            var limit = collection.settingModel.get( 'max_options' );
            if( 0 !== limit && collection.models.length >= ( limit ) ) {
                jQuery(this.el).find('.nf-add-new').addClass('disabled');
            } else {
                jQuery(this.el).find('.nf-add-new').removeClass('disabled');
            }
        },

		clickAddOption: function( e ) {
			nfRadio.channel( 'image-option-repeater' ).trigger( 'click:addOption', this.collection, this.dataModel );
			jQuery( this.children.findByIndex(this.children.length - 1).el ).find( '[data-id="image"]' ).focus();
		},

		clickExtra: function( e ) {
			nfRadio.channel( 'image-option-repeater' ).trigger( 'click:extra', e, this.collection, this.dataModel );
			nfRadio.channel( 'image-option-repeater-' + this.model.get( 'name' ) ).trigger( 'click:extra', e, this.model, this.collection, this.dataModel );
		},

		clickImport: function( e ) {
			var textarea = jQuery( e.data.jBox.content ).find( 'textarea' );
			var value = textarea.val().trimLeft().trimRight();
			/*
			 * Return early if we have no strings.
			 */
			if ( 0 == value.length ) {
				e.data.jBox.close();
				return false;
			}			
			/*
			 * Split our value based on new lines.
			 */

			var lines = value.split(/\n/);
			if ( _.isArray( lines ) ) {
				/*
				 * Loop over 
				 */
				_.each( lines, function( line ) {
					var row = line.split( ',' );
					var image = row[0];
					var value = row[1] || jQuery.slugify( image, { separator: '-' } );
					var calc = row[2] || '';

					image = image.trimLeft().trimRight();
					value = value.trimLeft().trimRight();
					calc = calc.trimLeft().trimRight();
					/*
					 * Add our row to the collection
					 */
					var model = e.data.view.collection.add( { image: row[0], value: value, calc: calc } );
					// Add our field addition to our change log.
					var image = {
						object: 'field',
						image: row[0],
						change: 'Option Added',
						dashicon: 'plus-alt'
					};

					nfRadio.channel( 'changes' ).request( 'register:change', 'addListOption', model, null, image );
					nfRadio.channel( 'image-option-repeater-' + e.data.view.model.get( 'name' ) ).trigger( 'add:option', model );
					nfRadio.channel( 'image-option-repeater' ).trigger( 'add:option', model );
					nfRadio.channel( 'app' ).trigger( 'update:setting', model );
				}, this );
				/*
				 * Set our state to unclean so that the user can publish.
				 */
			} else {
				/*
				 * TODO: Error Handling Here
				 */
			}
			textarea.val( '' );
			e.data.jBox.close();
		},
	} );

	return view;
} );

/**
 * Handles tasks associated with our option-repeater.
 * 
 * Return our repeater child view.
 *
 * Also listens for changes to the options settings.
 * 
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/app/imageOptionRepeater',['models/app/optionRepeaterModel', 'models/app/optionRepeaterCollection', 'views/app/drawer/imageOptionRepeaterComposite'], function( listOptionModel, listOptionCollection, listCompositeView ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Respond to requests for the childView for list type fields.
			nfRadio.channel( 'image-option-repeater' ).reply( 'get:settingChildView', this.getSettingChildView, this );
			
			// Listen for changes to our list options.
			this.listenTo( nfRadio.channel( 'image-option-repeater' ), 'change:option', this.changeOption );
			this.listenTo( nfRadio.channel( 'image-option-repeater' ), 'click:addOption', this.addOption );
			this.listenTo( nfRadio.channel( 'image-option-repeater' ), 'click:deleteOption', this.deleteOption );

			// Respond to requests related to our list options sortable.
			nfRadio.channel( 'image-option-repeater' ).reply( 'update:optionSortable', this.updateOptionSortable, this );
			nfRadio.channel( 'image-option-repeater' ).reply( 'stop:optionSortable', this.stopOptionSortable, this );
			nfRadio.channel( 'image-option-repeater' ).reply( 'start:optionSortable', this.startOptionSortable, this );
		
			/**
			 * When we init our setting model, we need to convert our array/objects into collections/models
			 */
			this.listenTo( nfRadio.channel( 'image-option-repeater' ), 'init:dataModel', this.convertSettings );
		},

		/**
		 * Update an option value in our model.
		 * 
		 * @since  3.0
		 * @param  Object			e          event
		 * @param  backbone.model 	model      option model
		 * @param  backbone.model 	dataModel
		 * @return void
		 */
		changeOption: function( e, model, dataModel, settingModel, optionView ) {
			var name = jQuery( e.target ).data( 'id' );
			if ( 'selected' == name ) {
				if ( jQuery( e.target ).prop( 'checked' ) ) {
					var value = 1;
				} else {
					var value = 0;
				}
			} else {
				var value = jQuery( e.target ).val();
			}
			
			var before = model.get( name );
			
			model.set( name, value );
			// Trigger an update on our dataModel
			this.triggerDataModel( model, dataModel );

			var after = value;
			
			var changes = {
				attr: name,
				before: before,
				after: after
			}
			
			var label = {
				object: dataModel.get( 'objectType' ),
				label: dataModel.get( 'label' ),
				change: 'Option ' + model.get( 'label' ) + ' ' + name + ' changed from ' + before + ' to ' + after
			};

			nfRadio.channel( 'changes' ).request( 'register:change', 'changeSetting', model, changes, label );
			nfRadio.channel( 'image-option-repeater' ).trigger( 'update:option', model, dataModel, settingModel, optionView );
			nfRadio.channel( 'image-option-repeater-option-' + name  ).trigger( 'update:option', e, model, dataModel, settingModel, optionView );
			nfRadio.channel( 'image-option-repeater-' + settingModel.get( 'name' ) ).trigger( 'update:option', model, dataModel, settingModel, optionView );
		},

		/**
		 * Add an option to our list
		 * 
		 * @since 3.0
		 * @param backbone.collection 	collection 	list option collection
		 * @param backbone.model 		dataModel
		 * @return void
		 */
		addOption: function( collection, dataModel ) {
			var modelData = {
				order: collection.length,
				new: true,
				options: {}
			};
			/**
			 * If we don't actually have a 'settingModel' duplicated fields
			 * can't add options until publish and the builder is reloaded.
			 * If we ignore the code if we don't have settingsModel, then it
			 * works.
			 */
			if  ( 'undefined' !== typeof collection.settingModel ) {
				var limit = collection.settingModel.get( 'max_options' );
				if ( 0 !== limit && collection.models.length >= limit ) {
					return;
				}
				_.each( collection.settingModel.get( 'columns' ), function ( col, key ) {
					modelData[ key ] = col.default;

					if ( 'undefined' != typeof col.options ) {
						modelData.options[ key ] = col.options;
					}
				});
			}
			var model = new listOptionModel( modelData );
			collection.add( model );

			// Add our field addition to our change log.
			var image = {
				object: dataModel.get( 'objectType' ),
				image: dataModel.get( 'image' ),
				change: 'Option Added',
				dashicon: 'plus-alt'
			};

			nfRadio.channel( 'changes' ).request( 'register:change', 'addListOption', model, null, image );

			if ( 'undefined' !== typeof collection.settingModel ) {
				nfRadio.channel('image-option-repeater-' + collection.settingModel.get('name')).trigger('add:option', model);
			}
			nfRadio.channel( 'image-option-repeater' ).trigger( 'add:option', model );
			nfRadio.channel( 'image-option-repeater' ).trigger( 'added:option', collection );
			this.triggerDataModel( model, dataModel );
		},

		/**
		 * Delete an option from our list
		 * 
		 * @since  3.0
		 * @param backbone.model 		model       list option model
		 * @param backbone.collection 	collection 	list option collection
		 * @param backbone.model 		dataModel
		 * @return void
		 */
		deleteOption: function( model, collection, dataModel ) {
			var newModel = nfRadio.channel( 'app' ).request( 'clone:modelDeep', model );

			// Add our field deletion to our change log.
			var image = {
				object: dataModel.get( 'objectType' ),
				image: dataModel.get( 'image' ),
				change: 'Option ' + newModel.get( 'image' ) + ' Removed',
				dashicon: 'dismiss'
			};

			var data = {
				collection: collection
			}

			nfRadio.channel( 'changes' ).request( 'register:change', 'removeListOption', newModel, null, image, data );
			
			var changeCollection = nfRadio.channel( 'changes' ).request( 'get:collection' );
			var results = changeCollection.where( { model: model } );

			_.each( results, function( changeModel ) {
				if ( 'object' == typeof changeModel.get( 'data' ) ) {
					_.each( changeModel.get( 'data' ), function( dataModel ) {
						if ( dataModel.model == dataModel ) {
							dataModel.model = newModel;
						}
					} );
				}
				changeModel.set( 'model', newModel );
				changeModel.set( 'disabled', true );
			} );

			collection.remove( model );
			nfRadio.channel( 'image-option-repeater' ).trigger( 'remove:option', model );
			nfRadio.channel( 'image-option-repeater' ).trigger( 'removed:option', collection );
			nfRadio.channel( 'image-option-repeater-' + collection.settingModel.get( 'name' ) ).trigger( 'remove:option', model );
			this.triggerDataModel( model, dataModel );
		},

		/**
		 * Creates an arbitrary value on our collection, then clones and updates that collection.
		 * This forces a change event to be fired on the dataModel where the list option collection data is stored.
		 * 
		 * @since  3.0
		 * @param backbone.collection 	collection 	list option collection
		 * @param backbone.model 		dataModel
		 * @return void
		 */
		triggerDataModel: function( model, dataModel ) {
			nfRadio.channel( 'app' ).trigger( 'update:setting', model );	
		},

		/**
		 * Return our list composite view to the setting collection view.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	model 	settings model
		 * @return void
		 */
		getSettingChildView: function( model ) {
			return listCompositeView;
		},

		/**
		 * When we sort our list options, change the order in our option model and trigger a change.
		 * 
		 * @since  3.0
		 * @param  Object	 		sortable 	jQuery UI element
		 * @param  backbone.view 	setting  	Setting view
		 * @return void
		 */
		updateOptionSortable: function( ui, sortable, setting ) {
			var newOrder = jQuery( sortable ).sortable( 'toArray' );
			var dragModel = setting.collection.get( { cid: jQuery( ui.item ).prop( 'id' ) } );
			var data = {
				collection: setting.collection,
				objModels: []
			};

			_.each( newOrder, function( cid, index ) {
				var optionModel = setting.collection.get( { cid: cid } );
				var oldPos = optionModel.get( 'order' );
				optionModel.set( 'order', index );
				var newPos = index;

				data.objModels.push( {
					model: optionModel,
					attr: 'order',
					before: oldPos,
					after: newPos
				} );
			} );
			
			setting.collection.sort( { silent: true } );
			
			var image = {
				object: setting.dataModel.get( 'objectType' ),
				image: setting.dataModel.get( 'image' ),
				change: 'Option ' + dragModel.get( 'image' ) + ' re-ordered from ' + dragModel._previousAttributes.order + ' to ' + dragModel.get( 'order' ),
				dashicon: 'sort'
			};

			nfRadio.channel( 'changes' ).request( 'register:change', 'sortListOptions', dragModel, null, image, data );
			this.triggerDataModel( dragModel, setting.dataModel );
			nfRadio.channel( 'image-option-repeater' ).trigger( 'sort:option', dragModel, setting );
			nfRadio.channel( 'image-option-repeater-' + setting.model.get( 'name' ) ).trigger( 'sort:option', dragModel, setting );
		},

		/**
		 * When we stop sorting our list options, reset our item opacity.
		 * 
		 * @since  3.0
		 * @param  Object ui jQuery UI element
		 * @return void
		 */
		stopOptionSortable: function( ui ) {
			jQuery( ui.item ).css( 'opacity', '' );
		},

		/**
		 * When we start sorting our list options, remove containing divs and set our item opacity to 0.5
		 * 
		 * @since  3.0
		 * @param  Objects ui jQuery UI element
		 * @return void
		 */
		startOptionSortable: function( ui ) {
			jQuery( ui.placeholder ).find( 'div' ).remove();
			jQuery( ui.item ).css( 'opacity', '0.5' ).show();
		},

		/**
		 * Convert settings from an array/object to a collection/model
		 * 
		 * @since  3.0
		 * @param  Backbone.Model dataModel
		 * @param  Backbone.Model settingModel
		 * @return void
		 */
		convertSettings: function( dataModel, settingModel ) {
			/*
			 * Our options are stored in our database as objects, not collections.
			 * Before we attempt to render them, we need to convert them to a collection if they aren't already one.
			 */ 
			var optionCollection = dataModel.get( settingModel.get( 'name' ) );

			if ( false == optionCollection instanceof Backbone.Collection ) {
				optionCollection = new listOptionCollection( [], { settingModel: settingModel } );
				optionCollection.add( dataModel.get( settingModel.get( 'name' ) ) );
				dataModel.set( settingModel.get( 'name' ), optionCollection, { silent: true } );
			}
		}

	});

	return controller;
} );
/**
 * Handles adding and removing the active class from a field currently being edited.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - Edit Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/editActive',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Respond to requests to remove the active class from all our fields.
			nfRadio.channel( 'fields' ).reply( 'clear:editActive', this.clearEditActive, this );
			// Listen for the closing drawer so that we can remove all of our active classes.
			this.listenTo( nfRadio.channel( 'drawer-editSettings' ), 'before:closeDrawer', this.clearEditActive );
		},

		/**
		 * Loops through our fields collection and sets editActive to false.
		 * 
		 * @since  3.0
		 * @return void
		 */
        clearEditActive: function() {
            var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
            _.each( fieldCollection.models, function( field ) {
				field.set( 'editActive', false );
            } );
        }
	});

	return controller;
} );

/**
 * Fetches settings models so that we can get setting information
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/fieldSettings',['models/app/settingCollection'], function( settingCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.collection = new settingCollection( fieldSettings, { objectType: 'fields' } );

			// Responds to requests for settings models.
			nfRadio.channel( 'fields' ).reply( 'get:settingModel', this.getSettingModel, this );
			
			// Responds to requests for our collection.
			nfRadio.channel( 'fields' ).reply( 'get:settingCollection', this.getSettingCollection, this );
		},

		getSettingModel: function( name ) {
			return this.collection.findWhere( { name: name } );
		},

		getSettingCollection: function() {
			return this.collection;
		}

	});

	return controller;
} );
/**
 * Listens to our app channel to add the individual Credit Card Fields.
 *
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/fieldCreditCard',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
          this.listenTo( nfRadio.channel( 'fields' ), 'after:addField', this.dropCreditCardField );
        },

        dropCreditCardField: function( fieldModel ) {

            if( 'creditcard' == fieldModel.get( 'type' ) ) {

                var order = fieldModel.get( 'order' );

                nfRadio.channel( 'fields' ).request( 'delete', fieldModel );

                _.each( [ 'creditcardfullname', 'creditcardnumber', 'creditcardcvc', 'creditcardexpiration', 'creditcardzip'], function( type ) {

                    var fieldType = nfRadio.channel( 'fields' ).request( 'get:type', type );

                    var newField = {
                        id: nfRadio.channel( 'fields' ).request( 'get:tmpID' ),
                        type: type,
                        label: fieldType.get( 'nicename' ),
                        order: order
                    };

                    nfRadio.channel( 'fields' ).request( 'add', newField );
                });
            }

        },

        stageCreditCardField: function( model ) {

            if( 'creditcard' == model.get( 'slug' ) ) {

                nfRadio.channel( 'fields' ).request( 'remove:stagedField', '', model );

                _.each( [ 'creditcardfullname', 'creditcardnumber', 'creditcardcvc', 'creditcardexpiration', 'creditcardzip'], function( type ) {
                    nfRadio.channel('fields').request('add:stagedField', type );
                });
            }
        }

    });

    return controller;
} );
/**
 * Listens to our app channel to add the individual List Fields.
 *
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/fieldList',[ 'models/app/optionRepeaterCollection' ], function( ListOptionCollection ) {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            this.listenTo( nfRadio.channel( 'option-repeater-option-label' ), 'update:option', this.updateOptionLabel );
            this.listenTo( nfRadio.channel( 'option-repeater-option-value' ), 'update:option', this.updateOptionValue );
            
            /*
             * When we init our model, convert our options from an array of objects to a collection of models.
             */
            this.listenTo( nfRadio.channel( 'fields-list' ), 'init:fieldModel', this.convertOptions );
        },

        updateOptionLabel: function( e, model, dataModel, settingModel, optionView ) {

            if( 'list' != _.findWhere( fieldTypeData, { id: dataModel.get( 'type' ) } ).parentType ) return;

            if( model.get( 'manual_value' ) ) return;

            value = jQuery.slugify( model.get( 'label' ), { separator: '-' } );

            model.set( 'value', value );
            model.trigger( 'change', model );

            // Set focus on value input
            jQuery( optionView.el ).find( '[data-id="value"]' ).focus().select();
        },

        updateOptionValue: function( e, model, dataModel, settingModel, optionView ) {
            if ( 'Field' == dataModel.get( 'objectType' ) ) {
                var newVal = model.get( 'value' );
                // Sanitize any unwanted special characters.
                // TODO: This assumes English is the standard language.
                //       We might want to allow other language characters through this check later.
                var pattern = /[^0-9a-zA-Z _@.-]/g;
                newVal = newVal.replace( pattern, '' );
                model.set( 'value', newVal );
                // Re-render the value.
                optionView.render();
            }
            
            var findWhere = _.findWhere( fieldTypeData, { id: dataModel.get( 'type' ) } );
            if( 'undefined' == typeof findWhere ) return;
            if( 'list' != findWhere.parentType ) return;

            model.set( 'manual_value', true );
            
            // Set focus on calc input
            jQuery( optionView.el ).find( '[data-id="calc"]' ).focus().select();
        },

        convertOptions: function( fieldModel ) {
            /*
             * Our options are stored in our database as objects, not collections.
             * Before we attempt to render them, we need to convert them to a collection if they aren't already one.
             */ 
            var options = fieldModel.get( 'options' );

            var settingModel = nfRadio.channel( 'fields' ).request( 'get:settingModel', 'options' );

            if ( false == options instanceof Backbone.Collection ) {
                options = new ListOptionCollection( [], { settingModel: settingModel } );
                options.add( fieldModel.get( 'options' ) );
                fieldModel.set( 'options', options, { silent: true } );
            }
        }

    });

    return controller;
} );
/**
 * Listens to our app channel to add the individual Credit Card Fields.
 *
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/fieldPassword',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            this.listenTo( nfRadio.channel( 'fields' ), 'after:addField', this.addField );
        },

        addField: function( model ) {

            if( 'password' == model.get( 'type' ) ) {

                var order = model.get( 'order' );

                var confirm = this.insertField( 'passwordconfirm', order + 1 );

                confirm.set( 'confirm_field', model.get( 'key' ) );
            }
        },

        insertField: function( type, order ) {
            var fieldType = nfRadio.channel( 'fields' ).request( 'get:type', type );

            var newField = {
                id: nfRadio.channel( 'fields' ).request( 'get:tmpID' ),
                type: type,
                label: fieldType.get( 'nicename' ),
                order: order
            };

            return nfRadio.channel('fields').request('add', newField );
        }
    });

    return controller;
} );
/**
 * Listens to our app channel for settings views being rendered.
 *
 * If we're rendering a product_assignment setting, add our products to the data model.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/fieldQuantity',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen for messages that are fired before a setting view is rendered.
			this.listenTo( nfRadio.channel( 'app' ), 'before:renderSetting', this.beforeRenderSetting );
		},

		beforeRenderSetting: function( settingModel, dataModel, view ) {
			if ( 'product_assignment' == settingModel.get( 'name' ) ) {
				var productFields = this.getProductFields( settingModel );
				settingModel.set( 'options', productFields );
			}
		},

		getProductFields: function( settingModel ) {
			var productFields = [ settingModel.get( 'select_product' ) ];
			// Update our dataModel with all of our product fields.
			var fields = nfRadio.channel( 'fields' ).request( 'get:collection' );
			_.each( fields.models, function( field ) {
				if ( 'product' == field.get( 'type' ) ) {
					productFields.push( { label: field.get( 'label' ), value: field.get( 'id' ) } );
				}
			} );
			return productFields;
		}

	});

	return controller;
} );
/**
 * Listens to our app channel for settings views being rendered.
 *
 * If we're rendering a product_assignment setting, add our products to the data model.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/fieldShipping',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'setting-shipping_options' ), 'render:setting', this.addMask );
			this.listenTo( nfRadio.channel( 'setting-shipping_options-option' ), 'render:setting', this.addMask );
		},

		addMask: function( settingModel, dataModel, view ) {
			jQuery( view.el ).find( '[data-id="value"]' ).each( function() {
				jQuery( this ).autoNumeric({
					aSign: '$', // TODO: Use form setting
					aSep: thousandsSeparator,
					aDec: decimalPoint
				});
			} );
		}
	});

	return controller;
} );
/**
 * When we add a new field, update its key.
 *
 * When we change the key, update any refs to the key.
 *
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/key',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// When we add a field, update its key.
			this.listenTo( nfRadio.channel( 'fields' ), 'add:field', this.newFieldKey );

			// When we edit a label, update our key.
			this.listenTo( nfRadio.channel( 'fieldSetting-label' ), 'update:setting', this.updateLabel );

			// When we edit a key, check for places that key might be used.
			this.listenTo( nfRadio.channel( 'fieldSetting-key' ), 'update:setting', this.updateKey );

			// When we type inside the admin key field, we need to save our manual_key setting.
			this.listenTo( nfRadio.channel( 'setting-key' ), 'keyup:setting', this.keyUp );
		},

		/**
		 * Add a key to our new field model.
		 *
		 * @since 3.0
		 * @param backbone.model model new field model
		 * @return void
		 */
		newFieldKey: function( model ) {
			var d = new Date();
			var n = d.valueOf();
			var key = this.slugify( model.get( 'type' ) + '_' + n );

			model.set( 'key', key, { silent: true } );

			if( 'undefined' == model.get( 'manual_key' ) ) {
				model.set('manual_key', false, {silent: true});
			}
		},

		updateLabel: function( model ) {

			/*
			 * If we haven't entered a key manually, update our key when our label changes.
			 */
			if ( ! model.get( 'manual_key' ) && 0 != jQuery.trim( model.get( 'label' ) ).length ) {
				/*
				 * When we're editing settings, we expect the edits to fire one at a time.
				 * Since we're calling this in the middle of our label update, anything that inquires about what has changed after we set our key will see both label and key.
				 * We need to remove the label from our model.changed property so that all that has changed is the key.
				 *
				 */
				delete model.changed.label;
				var d = new Date();
				var n = d.valueOf();
				var key = this.slugify( model.get( 'label' ) + '_' + n );
                // If our slug didn't setup correctly...
                // Force a valid entry.
                if ( -1 == key.indexOf( '_' ) ) key = 'field_' + key;
				model.set( 'key', key );
			}
		},

		/**
		 * When a field key is updated, find any merge tags using the key and update them.
		 *
		 * @since  3.0
		 * @param  backbone.model model field model
		 * @return void
		 */
		updateKey: function( dataModel ) {
			var key = dataModel.get( 'key' );
			this.settingModel = nfRadio.channel( 'fields' ).request( 'get:settingModel', 'key' );
			this.setError( key, dataModel );
		},

		keyUp: function( e, settingModel, dataModel ) {
			dataModel.set( 'manual_key', true );
			this.settingModel = settingModel;
			var key = jQuery( e.target ).val();
			this.setError( key, dataModel );
		},

		setError: function( key, dataModel ) {
			var error = false;
			if ( '' == jQuery.trim( key ) ) {
				error = 'Field keys can\'t be empty. Please enter a key.';
			} else if ( key != key.toLowerCase() ) {
				error = 'Field keys must be lowercase.';
			} else if ( key != key.replace( ' ', '_' ) ) {
				error = 'Field keys must cannot use spaces. Separate with "_" instead.';
			} else if ( '_' == key.slice( -1 ) ) {
				error = 'Field keys cannot end with a "_"';
			} else if ( key != this.slugify( key ) ) {
				error = 'Invalid Format.';
			} else if ( key != this.keyExists( key, dataModel ) ) {
				error = 'Field keys must be unique. Please enter another key.'
			}

			if ( error ) {
				this.settingModel.set( 'error', error );
			} else {
				nfRadio.channel( 'app' ).trigger( 'update:fieldKey', dataModel );
				this.settingModel.set( 'error', false );
			}
		},

		keyExists: function( key, dataModel ) {
			var newKey = this.slugify( key );
			if ( 0 != newKey.length ) {
				key = newKey;
			}
			var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
			var x = 1;
			var testKey = key;
			_.each( fieldCollection.models, function( field ) {
				if ( dataModel != field && testKey == field.get( 'key' ) ) {
					testKey = key + '_' + x;
					x++;
				}
			} );

			key = testKey;

			return key;
		},

		slugify: function( string ){
			return jQuery.slugify( string, { separator: '_' } )
		}
	});

	return controller;
} );

/**
 * Creates notices for our fields domain.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/notices',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'fields' ), 'add:stagedField', this.addStagedField );
		},

		addStagedField: function( model ) {
			nfRadio.channel( 'notices' ).request( 'add', 'addStagedField', model.get( 'nicename' ) + ' added to staging' );
		}
	});

	return controller;
} );
/**
 * Handles mobile-specific JS for our fields domain.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/mobile',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen for the start of our sorting.
			// this.listenTo( nfRadio.channel( 'app' ), 'render:fieldsSortable', this.initWiggle );
			// Listen for when we start sorting.
			this.listenTo( nfRadio.channel( 'fields' ), 'sortable:start', this.startWiggle );
			// Listen for when we stop sorting.
			this.listenTo( nfRadio.channel( 'fields' ), 'sortable:stop', this.stopWiggle );
		},

		initWiggle: function( view ) {
			if ( nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
				jQuery( view.el ).find( '.nf-field-wrap' ).on( 'taphold', function() {
					jQuery( this ).ClassyWiggle( 'start', { degrees: ['.65', '1', '.65', '0', '-.65', '-1', '-.65', '0'], delay: 50 } );
				} );
			}
		},

		startWiggle: function( ui ) {
			if ( nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
				jQuery( ui.item ).removeClass( 'ui-sortable-helper' ).ClassyWiggle( 'stop' );
				jQuery( ui.helper ).css( 'opacity', '0.75' ).ClassyWiggle( 'start', { degrees: ['.5', '1', '.5', '0', '-.5', '-1', '-.5', '0'] } );
			}
		},

		stopWiggle: function( ui ) {
			if ( nfRadio.channel( 'app' ).request( 'is:mobile' ) ) {
				jQuery( ui.helper ).ClassyWiggle( 'stop' );
				jQuery( ui.item ).removeClass( 'ui-sortable-helper drag-selected' );
			}
		}
	});

	return controller;
} );

/**
 * If we add a saved field to our form and then update it, set the "saved" flag to false.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/savedFields',[], function() {
	var controller = Marionette.Object.extend( {
		ignoreAttributes: [
			'editActive',
			'order',
			'saved',
			'jBox'
		],

		initialize: function() {
			this.listenTo( nfRadio.channel( 'fields' ), 'update:setting', this.updateField );
			// Listen to clicks on our add saved field button.
			this.listenTo( nfRadio.channel( 'drawer' ), 'click:addSavedField', this.clickAddSavedField, this );
		},

		updateField: function( dataModel ) {
			if ( dataModel.get( 'saved' ) ) {
				
				var modified = false;
				var changedAttributes = _.keys( dataModel.changedAttributes() );
				var that = this;
				_.each( changedAttributes, function( changed ) {
					if ( -1 == that.ignoreAttributes.indexOf( changed ) ) {
						modified = true;
					}
				} );
				
				if ( modified ) {
					dataModel.set( 'saved', false );
				}
			}
		},

		clickAddSavedField: function( e, dataModel ) {
			var modelClone = nfRadio.channel( 'app' ).request( 'clone:modelDeep', dataModel );

			var fieldData = modelClone.attributes;
			fieldData.saved = true;

			delete fieldData.jBox;
			delete fieldData.editActive;
			delete fieldData.created_at;
			delete fieldData.order;
			delete fieldData.id;
			delete fieldData.formID;
			delete fieldData.parent_id;
			
			var type = nfRadio.channel( 'fields' ).request( 'get:type', fieldData.type );
			var newType = _.clone( type.attributes );

			var nicename = jQuery( e.target ).parent().parent().find( 'input' ).val();
			console.log( nicename );
			newType.nicename = nicename;
			fieldData.label = nicename;
			fieldData.nicename = nicename;
			dataModel.set( 'addSavedLoading', true );
			var newTypeDefaults = JSON.stringify( fieldData );

			jQuery.post( ajaxurl, { action: 'nf_create_saved_field', field: newTypeDefaults, security: nfAdmin.ajaxNonce }, function( response ) {
				response = JSON.parse( response );
				newType.id = response.data.id;
				newType.nicename = nicename;
				newType.settingDefaults = fieldData;

				var typeCollection = nfRadio.channel( 'fields' ).request( 'get:typeCollection' );
				var newModel = typeCollection.add( newType );

				var typeSections = nfRadio.channel( 'fields' ).request( 'get:typeSections' );
				typeSections.get( 'saved' ).get( 'fieldTypes' ).push( newType.id );

				// dataModel.set( 'type', response.data.id );
				dataModel.set( 'addSavedLoading', false );
				dataModel.unset( 'addSavedLoading', { silent: true } );
				dataModel.get( 'jBox' ).close();
				// dataModel.set( 'saved', true );

				nfRadio.channel( 'notices' ).request( 'add', 'addSaved', 'Saved Field Added' );
			} );
		}
	});

	return controller;
} );
/**
 * Listens to our app channel for settings views being rendered.
 *
 * If we're rendering a datepicker setting, add our datepicker.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/fieldDatepicker',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'setting-type-datepicker' ), 'render:setting', this.addDatepicker );
		},

		addDatepicker: function( settingModel, dataModel, view ) {
			//Switch to flatpickr from pikaday
			let el = jQuery( view.el ).find( '.setting' )[0];
			let datePickerSettings = {};

			// Allow fields to add settings to the datepicker.
			let filteredDatePickerSettings = nfRadio.channel( 'setting-type-datepicker' ).request( 'filter:settings', datePickerSettings, settingModel, el );
			if ( 'undefined' != typeof filteredDatePickerSettings ) {
				datePickerSettings = filteredDatePickerSettings;
			}

			var dateObject = flatpickr( el, datePickerSettings );

			nfRadio.channel( 'setting-type-datepicker' ).trigger( 'loadComplete', dateObject, settingModel, dataModel, view );
		}
	});

	return controller;
} );
/**
 * Listens to our app channel for settings views being rendered.
 *
 * If we're rendering a product_assignment setting, add our products to the data model.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/fields/fieldDisplayCalc',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen for messages that are fired before a setting view is rendered.
			this.listenTo( nfRadio.channel( 'setting-calc_var' ), 'before:renderSetting', this.beforeRenderSetting );
		},

		beforeRenderSetting: function( settingModel, dataModel, view ) {
			// console.log( 'render!' );
		},

		getProductFields: function( settingModel ) {
			var productFields = [ settingModel.get( 'select_product' ) ];
			// Update our dataModel with all of our product fields.
			var fields = nfRadio.channel( 'fields' ).request( 'get:collection' );
			_.each( fields.models, function( field ) {
				if ( 'product' == field.get( 'type' ) ) {
					productFields.push( { label: field.get( 'label' ), value: field.get( 'id' ) } );
				}
			} );
			return productFields;
		}

	});

	return controller;
} );
/**
 * Handles specifics for our repeater field types.
 *
 */
define( 'controllers/fields/fieldRepeater',[ 'models/fields/fieldCollection' ], function( fieldCollection ) {
	var controller = Marionette.Object.extend( {

		initialize: function() {
			// Listen for repeater field models.
			this.listenTo( nfRadio.channel( 'fields-repeater' ), 'init:fieldModel', this.setupCollection, this );

			nfRadio.channel( 'fields-repeater' ).reply( 'add:childField', this.addChildField, this );
			nfRadio.channel( 'fields-repeater' ).reply( 'receive:fields', this.receiveFields, this );
			nfRadio.channel( 'fields-repeater' ).reply( 'get:childField', this.getChildField, this );
			nfRadio.channel( 'fields-repeater' ).reply( 'process:stagedField', this.processStagedFields, this );
			nfRadio.channel( 'fields-repeater' ).reply( 'sort:repeaterField', this.sortRepeaterField, this );
			nfRadio.channel( 'fields-repeater' ).reply( 'over:repeaterField', this.overRepeaterField, this );
			nfRadio.channel( 'fields-repeater' ).reply( 'out:repeaterField', this.outRepeaterField, this );
			nfRadio.channel( 'fields-repeater' ).reply( 'stop:repeaterField', this.stopRepeaterField, this );
			nfRadio.channel( 'fields-repeater' ).reply( 'start:repeaterField', this.startRepeaterField, this );
			nfRadio.channel( 'fields-repeater' ).reply( 'update:repeaterField', this.updateRepeaterField, this );
		},

		/**
		 * When we save repeater fields, their 'fields' content will be saved as an array of objects.
		 * When a repeater field model is created, we need to hyrdate the 'fields' settings and turn it into a Backbone Collection.
		 * 
		 * @since  version
		 * @param  {[type]} fieldModel [description]
		 * @return {[type]}            [description]
		 */
		setupCollection: function( fieldModel ) {
			// The fields var will be an array of field model data.
			let fields = fieldModel.get( 'fields' );

			// Only turn it into a collection if we haven't already.
			if ( false === fields instanceof Backbone.Collection ) {
				let collection =  new fieldCollection( fields );
				fieldModel.set( 'fields', collection );

				//Allows to loop through Repeater fields to reset correct state
				collection.listenTo( nfRadio.channel( 'fields-repeater' ), 'clearEditActive', this.clearEditActive, collection );
				collection.listenTo( nfRadio.channel( 'app' ), 'after:appStart', this.clearEditActive, collection );
				
				// Listen for radio messages that a field was deleted.
				collection.listenTo( nfRadio.channel( 'fields' ), 'delete:field', this.maybeDeleteField, collection );
			}	
		},

		/**
		 * In order to delete items from within a repeater field without creating a new convention, we listen to radio messages for field deletion.
		 * We just have to make sure that these fields weren't just added to our repeater field collection.
		 * 
		 * @since  version
		 * @param  {[type]} fieldModel [description]
		 * @return {[type]}            [description]
		 */
		maybeDeleteField: function( fieldModel ) {
			// Make sure that we didn't just add this field to our repeater.
			if ( ! fieldModel.get( 'droppedInRepeater' ) ) {
				this.remove( fieldModel );
			}
			// We're done dropping now.
			fieldModel.set( 'droppedInRepeater', false );
		},

		/**
		 * Loops through our fields collection and sets editActive to false.
		 * 
		 * @param  {[type]} fieldModel field that was clicked
		 * @return void
		 */
        clearEditActive: function( model ) {
            _.each( this.models, function( field ) {
				if( model.cid !== field.cid ){
					field.set( 'editActive', true );
					field.set( 'editActive', false );
				}
            } );
		},


		/**
		 * Receive fields in the repeater field sortable zone
		 * 
		 */
		receiveFields: function( ui, that, e ) {

			if( jQuery( ui.item ).hasClass( 'nf-stage' ) ) {
				this.processStagedFields( ui, that, e );
			} else {
				this.addChildField(ui, that, e);
			}

		},	
		
		/**
		 * Add a field in the repeater fields collection
		 * 
		 * @since  3.0
		 * @return void
		 */
		addChildField: function( ui, that, e ) {

			let type = typeof ui.item !== "undefined" ? jQuery( ui.item ).data( 'id' ) : ui.get('slug'),
			droppedFieldModel = nfRadio.channel( 'fields' ).request( 'get:field', type ),
			collection = that.repeaterFieldModel.get( 'fields' ),
			fieldModel;
			
			
			//Don't process another repeater field
			if(type === "repeater") return;
			
			//If a field Model exists and comes from the builder get the field Type and delete Field Model from main collection
			if(droppedFieldModel != null){
				//Reset type based on the model
				type = droppedFieldModel.attributes.type;
				// Remove the field from the main field collection.
				nfRadio.channel( 'app' ).trigger( 'click:delete', e, droppedFieldModel );
			}

			// Get our field type model
			fieldModel = nfRadio.channel( 'fields' ).request( 'get:type', type );

			// Get our tmp ID
			let elId = nfRadio.channel( 'fields' ).request( 'get:tmpID' ) != null ? nfRadio.channel( 'fields' ).request( 'get:tmpID' ) : "tmp";
			//Add field to collection
			newField = collection.add(  { id: elId , label: fieldModel.get( 'nicename' ), type: type, repeaterField: true} );

			//Sort fields
			let sortableEl = nfRadio.channel( 'fields-repeater' ).request( 'get:sortableEl' );
			if(! jQuery(sortableEl).hasClass('ui-sortable')){
				nfRadio.channel( 'fields-repeater' ).request( 'init:sortable' );
			}
			let sortableElArray = jQuery( sortableEl ).sortable( 'toArray' );
			_.each( sortableElArray, function( element, index ) {
				if(false === element.length > 0){
					sortableElArray[index] = elId;
				} else if (element === elId) {
					sortableElArray.splice( index, 1);
				}
			});
			nfRadio.channel( 'fields-repeater' ).request( 'sort:repeaterField', sortableElArray);

			// Add our field addition to our change log.
			var label = {
				object: 'Field',
				label: newField.get( 'label' ),
				change: 'Added',
				dashicon: 'plus-alt'
			};

			var data = {
				collection: collection
			}
			
			nfRadio.channel( 'changes' ).request( 'register:change', 'addObject', newField, null, label, data );

			
			if( typeof elId !== "undefined" && typeof ui.helper !== "undefined" ){
				/*
				* Update our helper id to the tmpID.
				* We do this so that when we sort, we have the proper ID.
				*/ 
				jQuery( ui.helper ).prop( 'id', elId );
				//Sort fields in repeater
				nfRadio.channel( 'app' ).request( 'stop:fieldsSortable', ui );
				// Remove the helper. Gets rid of a weird type artifact.
				jQuery( ui.helper ).remove();
				// Trigger a drop field type event.
				nfRadio.channel( 'fields' ).trigger( 'drop:fieldType', type, elId );
			}

			return elId;

		},

		/**
		 * Get a field from a repeater field collection
		 * 
		 * @return fieldModel
		 */
		getChildField: function( childFieldID, parentFieldModel, newID ) {

			if( typeof childFieldID === "undefined") return;
			//Prepare retuned variable
			let childFieldModel;
			//Allow to retrieve parentFieldModel by the newID that contains the parent Field ID ( USed to update a field ID after saving the form )
			if( parentFieldModel == null && typeof newID !== "undefined" ){
				const parentID = newID.split('.')[0];
				parentFieldModel = nfRadio.channel( 'fields' ).request( 'get:field', parentID );
			}
			
			//Check we have the Repeater Field Model
			if( parentFieldModel ) {
				//Get the fields collection in the repeater Field model
				let repeaterFieldsCollection = parentFieldModel.get( 'fields' );
				//Get the Child Field Model
				childFieldModel = repeaterFieldsCollection.get( childFieldID );
			}
			
			return childFieldModel;
		},

		/**
		 * Add Staged fields to repeater fieldset
		 * 
		 * @paran object event dropped
		 * @param object ui dropped element
		 */
		processStagedFields( ui, that, e) {

			// Make sure that our staged fields are sorted properly.	
			nfRadio.channel( 'fields' ).request( 'sort:staging' );
			// Grab our staged fields.
			var stagedFields = nfRadio.channel( 'fields' ).request( 'get:staging' );

			// Get our current field order.
			var sortableEl = nfRadio.channel( 'fields-repeater' ).request( 'get:sortableEl' );

			let order = [];
			if ( jQuery( sortableEl ).hasClass( 'repeater' ) ) { // Sortable isn't empty
				// If we're dealing with a sortable that isn't empty, get the order.
				order = jQuery( sortableEl ).sortable( 'toArray' );
			} else { // Sortable is empty
				// Sortable is empty, all we care about is our staged field draggable.
				order = ['nf-staged-fields-drag'];
			}    
			
			// Get the index of our droped element.
			let insertedAt = order.indexOf( 'nf-staged-fields-drag' );

			// Loop through each staged fields model and insert a field.
			_.each( stagedFields.models, function( field, index ) {
				// Add our field.
				var tmpID = nfRadio.channel( 'fields-repeater' ).request( 'add:childField', field, that, e );
				// Add this newly created field to our order array.
				order.splice( insertedAt + index, 0, tmpID );
				
			} );

			// Remove our dropped element from our order array.
			insertedAt = order.indexOf( 'nf-staged-fields-drag' );
			order.splice( insertedAt, 1 );

			// Sort our fields
			nfRadio.channel( 'fields' ).request( 'sort:fields', order );
			// Clear our staging
			nfRadio.channel( 'fields' ).request( 'clear:staging' );
			// Remove our helper. Fixes a weird artifact.
			jQuery( ui.helper ).remove();

		},

		/**
		 * Sort the fields in a repeater Field
		 * 
		 * @param  Array 	order optional order array like: [field-1, field-4, field-2]
		 * @return void
		 */
		sortRepeaterField: function( order, ui, updateDB ) {
			// Add the field to this repeatable collection.
			let collection = nfRadio.channel( 'fields-repeater' ).request( 'get:repeaterFieldsCollection' );

			if ( null == updateDB ) {
				updateDB = true;
			}
			// Get our sortable element
			var sortableEl = nfRadio.channel( 'fields-repeater' ).request( 'get:sortableEl' );
			if ( jQuery( sortableEl ).hasClass( 'ui-sortable' ) ) { // Make sure that sortable is enabled
				// JS ternerary for setting our order
				var order = order || jQuery( sortableEl ).sortable( 'toArray' );
				// Loop through all of our fields and update their order value
				_.each( collection.models, function( field ) {
					// Get our current position.
					var oldPos = field.get( 'order' );
					var id = field.get( 'id' );
					if ( jQuery.isNumeric( id ) ) {
						var search = 'field-' + id;
					} else {
						var search = id;
					}
					
					// Get the index of our field inside our order array
					var newPos = order.indexOf( search ) + 1;
					field.set( 'order', newPos );
				} );

				collection.sort();
				
				if ( updateDB ) {
					// Set our 'clean' status to false so that we get a notice to publish changes
					nfRadio.channel( 'app' ).request( 'update:setting', 'clean', false );
					// Update our preview
					nfRadio.channel( 'app' ).request( 'update:db' );					
				}
			}
		},

		/**
		 * When the user drags a field type or staging over our sortable, we need to modify the helper.
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		overRepeaterField: function( ui ) {
			if( jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) ) { // Field Type
				// String type
				var type = jQuery( ui.helper ).data( 'id' );
				// Get our field type model.
				var fieldType = nfRadio.channel( 'fields' ).request( 'get:type', type );
				// Get our field type nicename.
				var label = fieldType.get( 'nicename' );
				// Get our sortable element.
				var sortableEl = nfRadio.channel( 'fields-repeater' ).request( 'get:sortableEl' );

				// Set our currentHelper to an object var so that we can access it later.
				this.currentHelper = ui.helper;

			} else if ( jQuery( ui.item ).hasClass( 'nf-stage' ) ) { // Staging
				// Get our sortable, and if it's initialized add our hover class.
				var sortableEl = nfRadio.channel( 'fields-repeater' ).request( 'get:sortableEl' );
				if ( jQuery( sortableEl ).hasClass( 'ui-sortable' ) ) {
					jQuery( sortableEl ).addClass( 'nf-droppable-hover' );
				}
			}
		},

		/**
		 * When the user moves a draggable outside of the sortable, we need to change the helper.
		 * This returns the item to its pre-over state.
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		outRepeaterField: function( ui ) {
			if( jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) ) { // Field Type
				/*
				 * Get our helper clone.
				 * This will let us access the previous label and classes of our helper.
				 */ 
				var helperClone = nfRadio.channel( 'drawer-addField' ).request( 'get:typeHelperClone' );
				// Set our helper label, remove our sortable class, and add the type class back to the type draggable.
				jQuery( this.currentHelper ).html( jQuery( helperClone ).html() );
				jQuery( this.currentHelper ).removeClass( 'nf-field-wrap' ).addClass( 'nf-field-type-button' ).css( { 'width': '', 'height': '' } );
				// Get our sortable and if it has been intialized, remove the droppable hover class.
				var sortableEl = nfRadio.channel( 'fields-repeater' ).request( 'get:sortableEl' );
				if ( jQuery( sortableEl ).hasClass( 'ui-sortable' ) ) {
					jQuery( sortableEl ).removeClass( 'nf-droppable-hover' );
				}
			} else if ( jQuery( ui.item ).hasClass( 'nf-stage' ) ) { // Staging
				// If we've initialized our sortable, remove the droppable hover class.
				var sortableEl = nfRadio.channel( 'fields-repeater' ).request( 'get:sortableEl' );
				if ( jQuery( sortableEl ).hasClass( 'ui-sortable' ) ) {
					jQuery( sortableEl ).removeClass( 'nf-droppable-hover' );
				}
			}
		},

		/**
		 * When we stop dragging in the sortable:
		 * remove our opacity setting
		 * remove our ui helper
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		stopRepeaterField: function( ui ) {
			jQuery( ui.item ).css( 'opacity', '' );
			jQuery( ui.helper ).remove();
			//nfRadio.channel( 'fields' ).trigger( 'sortable:stop', ui );
		},

		/**
		 * When we start dragging in the sortable:
		 * add an opacity setting of 0.5
		 * show our item (jQuery hides the original item by default)
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		startRepeaterField: function( ui ) {
			// If we aren't dragging an item in from types or staging, update our change log.
			if( ! jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) && ! jQuery( ui.item ).hasClass( 'nf-stage' ) ) { 
				
				// Maintain origional visibility during drag/sort.
				jQuery( ui.item ).show();

				// Determine helper based on builder/layout type.
				if(jQuery(ui.item).hasClass('nf-field-wrap')){
					var newHelper = jQuery(ui.item).clone();
				} else if(jQuery(ui.item).parent().hasClass('layouts-cell')) {
					var newHelper = $parentHelper.clone();
				} else {
					var newHelper = jQuery(ui.item).clone();
				}

				// Remove unecessary item controls from helper.
				newHelper.find('.nf-item-controls').remove();

				// Update helper with clone's content.
				jQuery( ui.helper ).html( newHelper.html() );

				jQuery( ui.helper ).css( 'opacity', '0.5' );
				
				// Add de-emphasize origional.
				jQuery( ui.item ).css( 'opacity', '0.25' );
			}
			//nfRadio.channel( 'fields' ).trigger( 'sortable:start', ui );
		},

		/**
		 * Sort our fields when we change the order.
		 * 
		 * @since  3.0
		 * @param  Object 	ui jQuery UI element
		 * @return void
		 */
		updateRepeaterField: function( ui, sortable ) {

			nfRadio.channel( 'fields-repeater' ).request( 'sort:repeaterField' );

			// If we aren't dragging an item in from types or staging, update our change log.
			if( ! jQuery( ui.item ).hasClass( 'nf-field-type-draggable' ) && ! jQuery( ui.item ).hasClass( 'nf-stage' ) ) { 

				var fieldCollection = nfRadio.channel( 'fields-repeater' ).request( 'get:repeaterFieldsCollection' );
				var dragFieldID = jQuery( ui.item ).prop( 'id' ).replace( 'field-', '' );
				var dragModel = fieldCollection.get( dragFieldID );

				// Add our change event to the change tracker.
				var data = { fields: [] };
				_.each( fieldCollection.models, function( field ) {
					var oldPos = field._previousAttributes.order;
					var newPos = field.get( 'order' );
					
					data.fields.push( {
						model: field,
						attr: 'order',
						before: oldPos,
						after: newPos
					} );

				} );

				var label = {
					object: 'Field',
					label: dragModel.get( 'label' ),
					change: 'Re-ordered from ' + dragModel._previousAttributes.order + ' to ' + dragModel.get( 'order' ),
					dashicon: 'sort'
				};

				//nfRadio.channel( 'changes' ).request( 'register:change', 'sortFields', dragModel, null, label, data );
			}

		},

	});

	
	
	return controller;
} );
/**
 * Creates and stores a collection of action types. This includes all of the settings shown when editing a field.
 *
 * Loops over our preloaded data and adds that to our action type collection
 *
 * Also responds to requests for data about action types
 *
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/actions/types',[ 'models/app/typeCollection' ], function( TypeCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {

			/*
			 * Instantiate "installed" actions collection.
			 */
			this.installedActions = new TypeCollection(
				_.filter( actionTypeData, function( type ) {
					return type.section == 'installed';
					} 
				),
				{
					slug: 'installed',
					nicename: nfi18n.installed
				} 
			);

			this.availableActions = new TypeCollection(
				_.filter( actionTypeData, function( type ) {
					return type.section == 'available';
					} 
				),
				{
					slug: 'available',
					nicename: nfi18n.available
				}
			);

			// Respond to requests to get field type, collection, settings, and sections
			nfRadio.channel( 'actions' ).reply( 'get:type', this.getType, this );
			nfRadio.channel( 'actions' ).reply( 'get:installedActions', this.getInstalledActions, this );
			nfRadio.channel( 'actions' ).reply( 'get:availableActions', this.getAvailableActions, this );
		},

		/**
		 * Return a field type by id
		 *
		 * @since  3.0
		 * @param  string 			id 	field type
		 * @return backbone.model    	field type model
		 */
		getType: function( id ) {
			// Search our installed actions first
			var type = this.installedActions.get( id );
			if ( ! type ) {
				type = this.availableActions.get( id );
			}
        	return type;
        },

        /**
         * Return the installed action type collection
         *
         * @since  3.0
         * @return backbone.collection    	field type collection
         */
		getInstalledActions: function() {
        	return this.installedActions;
        },

        /**
         * Return the available action type collection
         *
         * @since  3.0
         * @return backbone.collection    	field type collection
         */
		getAvailableActions: function() {
        	return this.availableActions;
        },

        /**
         * Add a field type to our staging area when the field type button is clicked.
         *
         * @since 3.0
         * @param Object e event
         * @return void
         */
        addStagedField: function( e ) {
        	var type = jQuery( e.target ).data( 'id' );
        	nfRadio.channel( 'fields' ).request( 'add:stagedField', type );
        },

        /**
         * Return our field type settings sections
         *
         * @since  3.0
         * @return backbone.collection field type settings sections
         */
        getTypeSections: function() {
            return this.fieldTypeSections;
        }
	});

	return controller;
} );

/**
 * Model that represents our form action.
 * 
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/actions/actionModel',[], function() {
	var model = Backbone.Model.extend( {
		defaults: {
			objectType: 'Action',
			objectDomain: 'actions',
			editActive: false
		},

		initialize: function() {
			// Listen for model attribute changes
			this.on( 'change', this.changeSetting, this );

			// Get our parent field type.
			var actionType = nfRadio.channel( 'actions' ).request( 'get:type', this.get( 'type' ) );

			if( 'undefined' == typeof actionType ) return;

			// Loop through our action type "settingDefaults" and add any default settings.
			var that = this;
			_.each( actionType.get( 'settingDefaults' ), function( val, key ) {
				if ( ! that.get( key ) ) {
					that.set( key, val, { silent: true } );
				}
			} );
			
			/*
			 * Trigger an init event on three channels:
			 * 
			 * actions
			 * action-type
			 *
			 * This lets specific field types modify model attributes before anything uses them.
			 */ 
			nfRadio.channel( 'actions' ).trigger( 'init:actionModel', this );
			nfRadio.channel( 'actions-' + this.get( 'type' ) ).trigger( 'init:actionModel', this );

			this.listenTo( nfRadio.channel( 'app' ), 'fire:updateFieldKey', this.updateFieldKey );
		},

		/**
		 * When we change the model attributes, fire an event saying we've changed something.
		 * 
		 * @since  3.0
		 * @return void
		 */
		changeSetting: function( model, options ) {
            nfRadio.channel( 'actionSetting-' + _.keys( this.changedAttributes() )[0] ).trigger( 'update:setting', this, options.settingModel ) ;
			nfRadio.channel( 'actions').trigger( 'update:setting', this, options.settingModel );
            nfRadio.channel( 'app' ).trigger( 'update:setting', this, options.settingModel );
		},

		updateFieldKey: function( keyModel, settingModel ) {
			nfRadio.channel( 'app' ).trigger( 'replace:fieldKey', this, keyModel, settingModel );
		}
	} );
	
	return model;
} );
/**
 * Collection that holds our action models. 
 * This is the actual action data created by the user.
 *
 * We listen to the add and remove events so that we can push the new id to either the new action or removed action property.
 * 
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/actions/actionCollection',['models/actions/actionModel'], function( actionModel ) {
	var collection = Backbone.Collection.extend( {
		model: actionModel,
		comparator: 'order',
		tmpNum: 1,

		initialize: function() {
			this.on( 'add', this.addAction, this );
			this.on( 'remove', this.removeAction, this );
			this.newIDs = [];
		},

		/**
		 * When we add a field, push the id onto our new action property.
		 * This lets us tell the server that this is a new field to be added rather than a field to be updated.
		 * 
		 * @since 3.0
		 * @param void
		 */
		addAction: function( model ) {
			this.newIDs.push( model.get( 'id' ) );
		},

		/**
		 * When we remove a field, push the id onto our removed action property.
		 * 
		 * @since 3.0
		 * @param void
		 */
		removeAction: function( model ) {
			this.removedIDs[ model.get( 'id' ) ] = model.get( 'id' );
		}
	} );
	return collection;
} );
/**
 * Handles interactions with our actions collection.
 * 
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/actions/data',['models/actions/actionCollection', 'models/actions/actionModel'], function( actionCollection, actionModel ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Load our action collection from our localized form data
			this.collection = new actionCollection( preloadedFormData.actions );
			this.collection.tmpNum = 1;

			if ( 0 != this.collection.models.length ) {
				var that = this;
				_.each( this.collection.models, function( action ) {
					if ( ! jQuery.isNumeric( action.get( 'id' ) ) ) {
						that.collection.tmpNum++;
					}
				} );
			}
			// Set our removedIDs to an empty object. This will be populated when a action is removed so that we can add it to our 'deleted_actions' object.
			this.collection.removedIDs = {};

			// Respond to requests for data about actions and to update/change/delete actions from our collection.
			nfRadio.channel( 'actions' ).reply( 'get:collection', this.getCollection, this );
			nfRadio.channel( 'actions' ).reply( 'get:action', this.getAction, this );
			nfRadio.channel( 'actions' ).reply( 'get:tmpID', this.getTmpID, this );

			nfRadio.channel( 'actions' ).reply( 'add', this.addAction, this );
			nfRadio.channel( 'actions' ).reply( 'delete', this.deleteAction, this );
		},

		getCollection: function() {
			return this.collection;
		},

		getAction: function( id ) {
			return this.collection.get( id );
		},

		/**
		 * Add a action to our collection. If silent is passed as true, no events will trigger.
		 * 
		 * @since 3.0
		 * @param Object 	data 	action data to insert
		 * @param bool 		silent 	prevent events from firing as a result of adding	 	
		 */
		addAction: function( data, silent ) {
			silent = silent || false;

			if ( false === data instanceof Backbone.Model ) {
				var model = new actionModel( data );
			} else {
				var model = data;
			}

			this.collection.add( model, { silent: silent } );
			// Set our 'clean' status to false so that we get a notice to publish changes
			nfRadio.channel( 'app' ).request( 'update:setting', 'clean', false );

			return model;
		},

		/**
		 * Delete a action from our collection.
		 * 
		 * @since  3.0
		 * @param  backbone.model 	model 	action model to be deleted
		 * @return void
		 */
		deleteAction: function( model ) {
			this.collection.remove( model );
			// Set our 'clean' status to false so that we get a notice to publish changes
			nfRadio.channel( 'app' ).request( 'update:setting', 'clean', false );
			nfRadio.channel( 'app' ).request( 'update:db' );

		},


		/**
		 * Return a new tmp id for our actions.
		 * Gets the action collection length, adds 1, then returns that prepended with 'tmp-'.
		 * 
		 * @since  3.0
		 * @return string
		 */
		getTmpID: function() {
			var tmpNum = this.collection.tmpNum;
			this.collection.tmpNum++;
			return 'tmp-' + tmpNum;
		}
	});

	return controller;
} );
/**
 * Fetches settings models so that we can get setting information
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/actions/actionSettings',['models/app/settingCollection'], function( settingCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.collection = new settingCollection( actionSettings, { objectType: 'actions' } );

			// Responds to requests for settings models.
			nfRadio.channel( 'actions' ).reply( 'get:settingModel', this.getSettingModel, this );
		},

		getSettingModel: function( name ) {
			return this.collection.findWhere( { name: name } );
		}

	});

	return controller;
} );
/**
 * Handles adding and removing the active class from a action currently being edited.
 * 
 * @package Ninja Forms builder
 * @subpackage Actions - Edit Action Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/actions/editActive',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Respond to requests to remove the active class from all our actions.
			nfRadio.channel( 'actions' ).reply( 'clear:editActive', this.clearEditActive, this );
			// Listen for the closing drawer so that we can remove all of our active classes.
			this.listenTo( nfRadio.channel( 'drawer-editSettings' ), 'before:closeDrawer', this.clearEditActive );
		},

		/**
		 * Loops through our actions collection and sets editActive to false.
		 * 
		 * @since  3.0
		 * @return void
		 */
        clearEditActive: function() {
            var actionCollection = nfRadio.channel( 'actions' ).request( 'get:collection' );
            _.each( actionCollection.models, function( action ) {
				action.set( 'editActive', false );
            } );
        }
	});

	return controller;
} );

/**
 * @package Ninja Forms builder
 * @subpackage Actions - Action Settings Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/actions/emailFromSetting',[], function( ) {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            this.listenTo( nfRadio.channel( 'actionSetting-from_address' ), 'update:setting', this.updateFromAddress );
        },

        updateFromAddress: function( dataModel, settingModel ) {
            if( 'undefined' == typeof settingModel ) return;

            var value = dataModel.get( 'from_address' ).trim();

            if( '{wp:admin_email}' == value ) {
                return settingModel.set( 'warning', false );
            }

            if( value && ( ! this.isValidEmail( value ) ) || nfAdmin.home_url_host != value.replace(/.*@/, "") ){
                return settingModel.set( 'warning', nfi18n.errorInvalidEmailFromAddress );
            }

            return settingModel.set( 'warning', false );
        },

        isValidEmail: function(email) {
            return /^.+@.+\..+$/.test(email);
        }
    });
    return controller;
} );
/**
 * Handles clicks and dragging for our action types.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields - New Field Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/actions/addActionTypes',['models/actions/actionCollection', 'models/actions/actionModel'], function( actionCollection, actionModel ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'actions' ), 'click:addAction', this.addAction );

			nfRadio.channel( 'actions' ).reply( 'add:actionType', this.addAction, this );
		},

		/**
		 * Add an action to our collection. If silent is passed as true, no events will trigger.
		 * 
		 * @since 3.0
		 * @param Object 	data 	action data to insert
		 * @param bool 		silent 	prevent events from firing as a result of adding	 	
		 */
		addAction: function( type ) {

			var data = {
				id: nfRadio.channel( 'actions' ).request( 'get:tmpID' ),
				type: type.get( 'id' ),
				label: type.get( 'settingDefaults').label || type.get( 'nicename' )
			}

			var newModel = nfRadio.channel( 'actions' ).request( 'add', data );

			var label = {
				object: 'Action',
				label: newModel.get( 'label' ),
				change: 'Added',
				dashicon: 'plus-alt'
			};

			var data = {
				collection: nfRadio.channel( 'actions' ).request( 'get:collection' )
			}

			nfRadio.channel( 'changes' ).request( 'register:change', 'addObject', newModel, null, label, data );
			nfRadio.channel( 'app' ).trigger( 'click:edit', {}, newModel );
		}
	});

	return controller;
} );
/**
 * Handles the logic for our action type draggables.
 * 
 * @package Ninja Forms builder
 * @subpackage Actions - New Action Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/actions/typeDrag',[], function( ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen to our action type draggables and run the appropriate function.
			this.listenTo( nfRadio.channel( 'drawer-addAction' ), 'startDrag:type', this.startDrag );
			this.listenTo( nfRadio.channel( 'drawer-addAction' ), 'stopDrag:type', this.stopDrag );
			/*
			 * Respond to requests for our helper clone.
			 * This is used by other parts of the application to modify what the user is dragging in real-time.
			 */ 
			nfRadio.channel( 'drawer-addAction' ).reply( 'get:typeHelperClone', this.getCurrentDraggableHelperClone, this );
		},

		/**
		 * When we start dragging:
		 * get our drawer element
		 * set its overflow property to visible !important -> forces the type drag element to be on at the top of the z-index.
		 * get our main element
		 * est its overflow propery to visible !important -> forces the type drag element to be on top of the z-index.
		 * set our dragging helper clone
		 * 
		 * @since  3.0
		 * @param  object context 	This function is going to be called from a draggable. Context is the "this" reference to the draggable.
		 * @param  object ui      	Object sent by jQuery UI draggable.
		 * @return void
		 */
		startDrag: function( context, ui ) {
			this.drawerEl = nfRadio.channel( 'app' ).request( 'get:drawerEl' );
			this.mainEl = nfRadio.channel( 'app' ).request( 'get:mainEl' );
			jQuery( this.drawerEl )[0].style.setProperty( 'overflow', 'visible', 'important' );
			// jQuery( this.mainEl )[0].style.setProperty( 'overflow', 'visible', 'important' );

			this.draggableHelperClone = jQuery( ui.helper ).clone();

		},

		/**
		 * When we stop dragging, reset our overflow property to hidden !important.
		 * 
		 * @since  3.0
		 * @param  object context 	This function is going to be called from a draggable. Context is the "this" reference to the draggable.
		 * @param  object ui      	Object sent by jQuery UI draggable.
		 * @return {[type]}         [description]
		 */
		stopDrag: function( context, ui ) {
			jQuery( this.drawerEl )[0].style.setProperty( 'overflow', 'hidden', 'important' );
			// jQuery( this.mainEl )[0].style.setProperty( 'overflow', 'hidden', 'important' );
		},

		getCurrentDraggableHelperClone: function() {
			return this.draggableHelperClone;
		}
	});

	return controller;
} );
/**
 * Handles the logic for our action type droppable.
 * 
 * @package Ninja Forms builder
 * @subpackage Actions - New Action Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/actions/droppable',[], function( ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			/*
			 * Respond to requests for our helper clone.
			 * This is used by other parts of the application to modify what the user is dragging in real-time.
			 */ 
			nfRadio.channel( 'app' ).reply( 'drop:actionType', this.dropActionType, this );
		},

		dropActionType: function( e, ui ) {
			var type_slug = jQuery( ui.helper ).data( 'type' );
			var type = nfRadio.channel( 'actions' ).request( 'get:type', type_slug );
			nfRadio.channel( 'actions' ).request( 'add:actionType', type );
		}
	});

	return controller;
} );
/**
 * Model for our action type
 * 
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/actions/typeModel',[], function() {
	var model = Backbone.Model.extend( {

	} );
	
	return model;
} );
/**
 * Collection that holds our action type models. 
 * 
 * @package Ninja Forms builder
 * @subpackage Actions
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/actions/typeCollection',['models/actions/typeModel'], function( actionTypeModel ) {
	var collection = Backbone.Collection.extend( {
		model: actionTypeModel,
	} );
	return collection;
} );
/**
 * Filters our action type collection.
 * 
 * @package Ninja Forms builder
 * @subpackage Actions - New Action Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/actions/filterTypes',['models/actions/typeCollection'], function( typeCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen to our change filter event.
			this.listenTo( nfRadio.channel( 'drawer-addAction' ), 'change:filter', this.filterActionTypes );
		},

		/**
		 * Filter our action types in the add new action drawer
		 * 
		 * Takes a search string and finds any action types that match either the name or alias.
		 * 
		 * @since  3.0
		 * @param  string	 search 	string being searched for
		 * @param  object 	 e      	Keyup event
		 * @return void
		 */
		filterActionTypes: function( search, e ) {

			// Make sure that we aren't dealing with an empty string.
			if ( '' != jQuery.trim( search ) ) {

        		var filteredInstalled = [];
        		/**
        		 * Call the function that actually filters our collection,
        		 * and then loop through our collection, adding each model to our filteredInstalled array.
        		 */
				var installedActions = nfRadio.channel( 'actions' ).request( 'get:installedActions' );
        		_.each( this.filterCollection( search, installedActions ), function( model ) {
        			filteredInstalled.push( model );
        		} );

        		var filteredAvailable = [];
        		var availableActions = nfRadio.channel( 'actions' ).request( 'get:availableActions' );
        		_.each( this.filterCollection( search, availableActions ), function( model ) {
        			filteredAvailable.push( model );
        		} );

        		// Create a new Action Type Section collection with the filtered array.
        		var newInstalled = new typeCollection( filteredInstalled );
        		newInstalled.slug = 'installed';
        		newInstalled.nicename = 'Installed';

        		var newAvailable = new typeCollection( filteredAvailable );
        		newAvailable.slug = 'available';
				newAvailable.nicename = 'Available';

        		// Request that our action types filter be applied, passing the collection we created above.
        		nfRadio.channel( 'drawer' ).trigger( 'filter:actionTypes', newInstalled, newAvailable );
        		// If we've pressed the 'enter' key, add the action to staging and clear the filter.
        		if ( e.addObject ) {
        			if ( 0 < newInstalled.length ) {
        				nfRadio.channel( 'actions' ).request( 'add:actionType', newInstalled.models[0] );
        				nfRadio.channel( 'drawer' ).request( 'clear:filter' );
        			}
        		}
        	} else {
        		// Clear our filter if the search text is empty.
        		nfRadio.channel( 'drawer' ).trigger( 'clear:filter' );
        	}
        },

        /**
         * Search our action type collection for the search string.
         * 
         * @since  3.0
         * @param  string	 search 	string being searched for
         * @return backbone.collection
         */
        filterCollection: function( search, collection ) {
        	search = search.toLowerCase();
        	/*
        	 * Backbone collections have a 'filter' method that loops through every model,
        	 * waiting for you to return true or false. If you return true, the model is kept.
        	 * If you return false, it's removed from the filtered result.
        	 */
			var filtered = collection.filter( function( model ) {
				var found = false;
				
				// If we match either the ID or nicename, return true.
				if ( model.get( 'id' ).toLowerCase().indexOf( search ) != -1 ) {
					found = true;
				} else if ( model.get( 'nicename' ).toLowerCase().indexOf( search ) != -1 ) {
					found = true;
				}

				/*
				 * TODO: Hashtag searching. Doesn't really do anything atm.
				 */
				if ( model.get( 'tags' ) && 0 == search.indexOf( '#' ) ) {
					_.each( model.get( 'tags' ), function( tag ) {
						if ( search.replace( '#', '' ).length > 1 ) {
							if ( tag.toLowerCase().indexOf( search.replace( '#', '' ) ) != -1 ) {
								found = true;
							}							
						}
					} );
				}

				// If we match any of the aliases, return true.
				if ( model.get( 'alias' ) ) {
					_.each( model.get( 'alias' ), function( alias ) {
						if ( alias.toLowerCase().indexOf( search ) != -1 ) {
							found = true;
						}
					} );
				}

				return found;
			} );

			// Return our filtered collection.
			return filtered;
        }
	});

	return controller;
} );
/**
 * @package Ninja Forms builder
 * @subpackage Actions - New Action Drawer
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/actions/newsletterList',[], function( ) {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            this.listenTo( nfRadio.channel( 'setting-newsletter_list' ),             'show:setting',      this.defaultFields );
            this.listenTo( nfRadio.channel( 'setting-type-newsletter_list' ),        'click:extra',       this.clickListUpdate );
            this.listenTo( nfRadio.channel( 'actionSetting-newsletter_list' ),       'update:setting',    this.maybeRenderFields );
            this.listenTo( nfRadio.channel( 'actionSetting-newsletter_list' ),       'update:setting',    this.maybeRenderGroups );
            this.listenTo( nfRadio.channel( 'setting-name-newsletter_list_fields' ), 'init:settingModel', this.registerFieldsListener );
            this.listenTo( nfRadio.channel( 'setting-name-newsletter_list_groups' ), 'init:settingModel', this.registerGroupsListener );
        },

        defaultFields: function( settingModel, dataModel ) {
            this.maybeRenderFields( dataModel, settingModel );
            this.maybeRenderGroups( dataModel, settingModel );
        },

        registerFieldsListener: function ( model ) {
            model.listenTo( nfRadio.channel( 'newsletter_list_fields' ), 'update:fieldMapping', this.updateFieldMapping, model );
        },

        registerGroupsListener: function ( model ) {
            model.listenTo( nfRadio.channel( 'newsletter_list_groups' ), 'update:interestGroups', this.updateInterestGroups, model );
        },

        clickListUpdate: function( e, settingModel, dataModel, settingView ) {

            var data = {
                action: 'nf_' + dataModel.attributes.type + '_get_lists',
                security: nfAdmin.ajaxNonce
            };

            var that = this;
            jQuery( e.srcElement ).addClass( 'spin' );
            jQuery.post( ajaxurl, data, function( response ){
                var response = JSON.parse( response );
                that.updateLists( settingModel, response.lists, settingView, dataModel );
                dataModel.set( 'newsletter_list', response.lists[0].value, { settingModel: settingModel } );
            }).always( function() {
                jQuery( e.srcElement ).removeClass( 'spin' );
            });
        },

        updateLists: function( settingModel, lists, settingView, dataModel ) {
            settingModel.set( 'options', lists );
            settingView.render();
        },

        maybeRenderFields: function( dataModel, settingModel ) {

            if( 'undefined' == typeof settingModel ) return;

            var selectedList = dataModel.get( 'newsletter_list' );
            var lists = settingModel.get( 'options' );
            _.each( lists, function( list ) {
                if ( selectedList == list.value ) {
                    nfRadio.channel( 'newsletter_list_fields').trigger( 'update:fieldMapping', list.fields );
                }
            } );

            dataModel.set( 'newsletter_list_fields', 0 );
        },

        maybeRenderGroups: function( dataModel, settingModel ) {
            if( 'undefined' == typeof settingModel ) return;

            var selectedList = dataModel.get( 'newsletter_list' );
            var lists = settingModel.get( 'options' );
            _.each( lists, function( list ) {
                if ( selectedList == list.value ) {
                    nfRadio.channel( 'newsletter_list_groups').trigger( 'update:interestGroups', list.groups );
                }
            } );

            dataModel.set( 'newsletter_list_fields', 0 );
        },

        updateFieldMapping: function( fields ) {
           var settings = this.get( 'settings' );
            settings.reset();
            _.each( fields, function( field ){

                settings.add({
                    name: field.value,
                    type: 'textbox',
                    label: field.label,
                    width: 'full',
                    use_merge_tags: { exclude: [ 'user', 'post', 'system', 'querystrings' ] }
                });
            });
            this.set( 'settings', settings );
        },

        updateInterestGroups: function( groups ) {
            var settings = this.get( 'settings' );
            settings.reset();
            _.each( groups, function( group ){

                settings.add({
                    name: group.value,
                    type: 'toggle',
                    label: group.label,
                    width: 'full',
                });
            });
            this.set( 'settings', settings );
        },

    });

    return controller;
} );

/**
 * Listens to field deletion, removing any merge tags that reference the field.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2017 WP Ninjas
 * @since 3.1.7
 */
define( 'controllers/actions/deleteFieldListener',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			/*
			 * When we init an action model, register a listener for field deletion.
			 */
			this.listenTo( nfRadio.channel( 'actions' ), 'init:actionModel', this.registerListener );
		},

		registerListener: function( actionModel ) {
			actionModel.listenTo( nfRadio.channel( 'fields' ), 'delete:field', this.maybeUpdateSettings );
		},

		maybeUpdateSettings: function( fieldModel ) {
			var newObject, filteredCollection,
				fieldMergeTag = '{field:' + fieldModel.get( 'key' ) + '}';
			
			/*
			 * Loop through our action attributes to see if the field mergetag exists in our action.
			 *
			 * If it does:
			 * 	- Replace the field mergetag in strings with ''.
			 * 	- Remove any items with the field merge tag if they are in an array.
			 */
			_.each( this.attributes, function( attr, key ) {
				if ( _.isString( attr ) ) {
					// If our attribute is a string, replace any instances of the field merge tag with an empty string.
					this.set( key, attr.replace( fieldMergeTag, '' ) );
				} else if ( _.isArray( attr ) ) {
					// If our attribute is an array, search the contents for field merge tag and remove items that match.
					_.each( attr, function( val, index ) {
						if ( _.isString( val ) ) {
							// If val is a string, search it for the field mergetag.
							console.log( 'string replace' );
						} else if ( _.isArray( val ) ) {
							// If val is an array, search it for the field mergetag.
							console.log( 'array search' );
						} else if ( _.isObject( val ) ) {
							// If val is a object, search it for the field mergetag.
							newObject = _.mapObject( val, function( value, key ) {
								if ( _.isString( value ) ) {
									if ( -1 != value.indexOf( fieldMergeTag ) ) {
										attr.splice( index, 1 );
									}
								};

								return value;
							} );

							this.set( key, attr );
						}
					}, this );
				} else if ( attr instanceof Backbone.Collection ) {
					// This is a Backbone Collection, so we need to loop through the models and remove any that have an attribute containing the field merge tag.
					var filteredCollection = attr.filter( function ( model ) {
						// Make sure that EVERY model attribute does NOT reference the field merge tag.
					    return _.every( model.attributes, function( val ) {
					    	/*
					    	 * Currently only handles items that are one-level deep.
					    	 * TODO: Add support for further nesting of values.
					    	 */
					    	if ( _.isString( val ) ) {
					    		if ( -1 != val.indexOf( fieldMergeTag ) ) {
					    			return false;
					    		}
					    	}
					    	return true;
					    });;
					});
					// Update our key with the filtered collection value.
					this.set( key, filteredCollection );
				}
			}, this );
		}

	});

	return controller;
} );
/**
 * Listens to our app channel for settings views being rendered.
 *
 * If we're rendering a collect payment setting, add our number fields and total fields to the data model.
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2017 WP Ninjas
 * @since 3.1.7
 */
define( 'controllers/actions/collectPaymentFields',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Listen for messages that are fired before a setting view is rendered.
			this.listenTo( nfRadio.channel( 'app' ), 'before:renderSetting', this.beforeRenderSetting );
		},

		beforeRenderSetting: function( settingModel, dataModel, view ) {
			if ( 'field' != settingModel.get( 'total_type' ) ) return false;

			var fields = this.getFields( settingModel );

			/*
			 * If the field in the payment total isn't in our field list, add it.
			 *
			 * Remove the merge tag stuff to get the field key.
			 */
			
			var field_key = dataModel.get( 'payment_total' );
			field_key = field_key.replace( '{field:', '' );
			field_key = field_key.replace( '}', '' );
			var fieldModel = nfRadio.channel( 'fields' ).request( 'get:field', field_key );

			if ( 'undefined' != typeof fieldModel ) {
				if ( 'undefined' == typeof _.findWhere( fields, { value: dataModel.get( 'payment_total' ) } ) ) {
					fields.push( { label: fieldModel.get( 'label' ), value: '{field:' + fieldModel.get( 'key' ) + '}' } );
				}
			}
			
			/*
			 * Update our fields options.
			 */
			settingModel.set( 'options', fields );
			
		},

		getFields: function( settingModel ) {
			var returnFields = [ settingModel.get( 'default_options' ) ];
			// Update our dataModel with all of our product fields.
			var fields = nfRadio.channel( 'fields' ).request( 'get:collection' );
			_.each( fields.models, function( field ) {
				if ( 'number' == field.get( 'type' ) || 'total' == field.get( 'type' ) || 'checkbox' == field.get( 'type' ) ) {
					returnFields.push( { label: field.get( 'label' ), value: '{field:' + field.get( 'key' ) + '}' } );
				}
			} );

			returnFields = _.sortBy( returnFields, function( field ) { return field.label } );

			return returnFields;
		}

	});

	return controller;
} );
/**
 * Listens to our app channel for settings views being rendered.
 *
 * If we're rendering a collect payment setting, add our calculations to the data model.
 *
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2017 WP Ninjas
 * @since 3.1.7
 */
define( 'controllers/actions/collectPaymentCalculations',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            // Listen for messages that are fired before a setting view is rendered.
            this.listenTo( nfRadio.channel( 'app' ), 'before:renderSetting', this.beforeRenderSetting );
        },

        beforeRenderSetting: function( settingModel, dataModel, view ) {
            if ( 'calc' == settingModel.get( 'total_type' ) ) {
                var calcModels = nfRadio.channel( 'app' ).request( 'get:formModel' );
                var calcs = this.getCalcs( calcModels, settingModel );

                settingModel.set( 'options', calcs );
            }
        },

        getCalcs: function( calcModels, settingModel ) {
            var returnCalcs = [ settingModel.get( 'default_options' ) ];

            // Update our dataModel with all of our product fields.
            var calcs = calcModels.get( 'settings' ).get( 'calculations' );

            _.each( calcs.models, function( calc ) {
                returnCalcs.push( { label: calc.get( 'name' ), value: '{calc:' + calc.get( 'name' ) + '}' } );
            } );

            returnCalcs = _.sortBy( returnCalcs, function( calc ) { return calc.label } );

            return returnCalcs;
        }

    });

    return controller;
} );
/**
 * Listens to our app channel for settings views being rendered.
 *
 * If we haven't set a total_type, then set the total_type to fixed.
 *
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2017 WP Ninjas
 * @since 3.1.7
 */
define( 'controllers/actions/collectPaymentFixed',[], function() {
    var controller = Marionette.Object.extend( {
        initialize: function() {
            // Listen for messages that are fired before a setting view is rendered.
            this.listenTo( nfRadio.channel( 'app' ), 'before:renderSetting', this.beforeRenderSetting );
        },

        beforeRenderSetting: function( settingModel, dataModel, view ) {

            if ( 'payment_total_type' != settingModel.get( 'name' ) || _.isEmpty( dataModel.get( 'payment_total' ) ) ) return false;

            /*
             * If we don't have a payment total type and we have a payment total, set our total type to the appropriate total type.
             */
            if ( ( 'undefined' == dataModel.get( 'payment_total_type' ) || _.isEmpty( dataModel.get( 'payment_total_type' ) ) ) ) {
                /*
                 * If payment_total is a field merge tag, set payment_total_type to "field"
                 */

                if ( -1 != dataModel.get( 'payment_total' ).indexOf( '{field' ) ) {
                    dataModel.set( 'payment_total_type', 'field' );
                } else if ( -1 != dataModel.get( 'payment_total' ).indexOf( '{calc' ) ) {
                    dataModel.set( 'payment_total_type', 'calc' );
                } else {
                    dataModel.set( 'payment_total_type', 'fixed' );
                }   
            }
        },

    });

    return controller;
} );
/**
 * When we init a collect payment action, listen for calc changes
 * 
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2017 WP Ninjas
 * @since 3.1.7
 */
define( 'controllers/actions/collectPayment',[], function( settingCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			/*
			 * When we init a collect payment action model, register a listener for calc changes.
			 */
			this.listenTo( nfRadio.channel( 'actions-collectpayment' ), 'init:actionModel', this.initCollectPayment );
			
			/*
			 * Before we render our total field, we may want to update its value.
			 */
			this.listenTo( nfRadio.channel( 'app' ), 'before:renderSetting', this.maybeClearTotal );
		},

		/**
		 * When a collect payment action is init'd, register a listener for calc changes and update our data appropriately.
		 * @since  3.1.7
		 * @param  {backbone.model} actionModel 
		 * @return {void}
		 */
		initCollectPayment: function( actionModel )  {
			actionModel.listenTo( nfRadio.channel( 'calcs' ), 'update:calcName', this.maybeUpdateTotal );
        },

		//TODO: Add in an error that will not allow drawer to close until total type and total value is selected.
		maybeError: function(){},

		maybeUpdateTotal: function( optionModel, oldName ) {
			/*
			 * We have changed a calculation. Make sure that 'calc' is our payment total type.
			 */
			if ( 'calc' != this.get( 'payment_total_type' ) ) {
				return
			}
			
			/*
			 * Check our payment_total setting for the old merge tag and replace it with the new one.
			 */
			var newVal = this.get( 'payment_total' ).replace( '{calc:' + oldName + '}', '{calc:' + optionModel.get( 'name' ) + '}' );
			this.set( 'payment_total', newVal );
		},

		maybeClearTotal: function( settingModel, dataModel, view ) {
            /*
             * If our payment_total is a merge tag, clear it when we select the "fixed" option.
             */
            if ( 'fixed' == dataModel.get( 'payment_total_type' ) ) {
                if ( -1 != dataModel.get( 'payment_total' ).indexOf( '{field' ) || -1 != dataModel.get( 'payment_total' ).indexOf( '{calc' ) ) {
                    dataModel.set( 'payment_total', '' );
                }
            }
		}

	});

	return controller;
} );
/**
 * When we init a save action, listen for form changes
 *
 * @package Ninja Forms builder
 * @subpackage Main App
 * @copyright (c) 2017 WP Ninjas
 * @since 3.1.7
 */
define( 'controllers/actions/save',[], function( settingCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'actions-save' ), 'init:actionModel', this.initSave );
		},

		/**
		 * Set listeners up to listen for add/delete fields for Save action
		 */
		initSave: function( actionModel ) {

			this.model = actionModel;

			/*
			 * When we init a save action model, register a listener for new
			  * fields
			 */
			this.listenTo( Backbone.Radio.channel( 'fields' ), 'add:field',
				this.checkFieldAdded );

			/*
			 * When we init a save action model, register a listener for deleted
			  * fields
			 */
			this.listenTo( Backbone.Radio.channel( 'fields' ), 'delete:field',
				this.checkFieldDeleted );
		},

		/**
		 * When a save action is init'd, check to see if a new field added
		 * is an email and decide if it needs to be the 'submitter_email'
		 * for privacy regulation functionality
		 *
		 * @param  {backbone.model} actionModel
		 * @return {void}
		 */
		checkFieldAdded: function( newFieldModel ) {
			if( 'email' == newFieldModel.get( 'type' ) ) {
				var submitter_email = this.model.get('submitter_email');

				if( '' === submitter_email ) {
					this.model.set( 'submitter_email', newFieldModel.get( 'key' ) );
				}
			}
		},

		/**
		 * When a save action is init'd, check to see if a field that has been
		 * deleted is an email and rearrance the submitter email setting
		 * for privacy regulation functionality
		 *
		 * @param  {backbone.model} actionModel
		 * @return {void}
		 */
		checkFieldDeleted: function( fieldModel ) {
			var submitter_email = this.model.get( 'submitter_email' );
			
			if( submitter_email == fieldModel.get( 'key' ) ) {
				this.model.set( 'submitter_email', '' );
			}
		},

	});

	return controller;
} );
/**
 * Creates and stores a collection of form setting types. This includes all of the settings shown when editing a field.
 *
 * Loops over our preloaded data and adds that to our form setting type collection
 *
 * Also responds to requests for data about form setting types
 *
 * @package Ninja Forms builder
 * @subpackage Advanced
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/advanced/types',[
		'models/app/typeCollection'
	],
	function(
		TypeCollection
	) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Create our field type collection
			this.collection = new TypeCollection( formSettingTypeData );

			if(!nfAdmin.devMode){
				var calculations = this.collection.where({id:'calculations'});
				this.collection.remove(calculations);
			}

			// Respond to requests to get field type, collection, settings, and sections
			nfRadio.channel( 'settings' ).reply( 'get:type', this.getType, this );
			nfRadio.channel( 'settings' ).reply( 'get:typeCollection', this.getCollection, this );
		},

		/**
		 * Return a field type by id
		 *
		 * @since  3.0
		 * @param  string 			id 	field type
		 * @return backbone.model    	field type model
		 */
		getType: function( id ) {
			return this.collection.get( id );
        },

        /**
         * Return the installed action type collection
         *
         * @since  3.0
         * @return backbone.collection    	field type collection
         */
		getCollection: function() {
        	return this.collection;
        }
	});

	return controller;
} );

/**
 * Model that represents our form settings.
 * 
 * @package Ninja Forms builder
 * @subpackage Form Settings
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'models/advanced/settingsModel',[], function() {
	var model = Backbone.Model.extend( {
		defaults: {
			objectType: 'Form Setting',
			editActive: false
		},

		initialize: function() {
			// Listen for model attribute changes
			this.bind( 'change', this.changeSetting, this );
			/*
			 * Check to see if we have any setting defaults to set.
			 */
			var formSettings = nfRadio.channel( 'settings' ).request( 'get:collection' );
			_.each( formSettings.models, function( settingModel ) {
				if ( 'undefined' == typeof this.get( settingModel.get( 'name' ) ) ) {
					this.set( settingModel.get( 'name' ), settingModel.get( 'value' ), { silent: true } );
				}
				nfRadio.channel( settingModel.get( 'type' ) ).trigger( 'init:dataModel', this, settingModel );
			}, this );

			this.listenTo( nfRadio.channel( 'app' ), 'fire:updateFieldKey', this.updateFieldKey );
		},

		/**
		 * When we change the model attributes, fire an event saying we've changed something.
		 * 
		 * @since  3.0
		 * @return void
		 */
		changeSetting: function( model, options) {
			nfRadio.channel( 'app' ).trigger( 'update:setting', this, options.settingModel );
		},

		updateFieldKey: function( keyModel, settingModel ) {
			nfRadio.channel( 'app' ).trigger( 'replace:fieldKey', this, keyModel, settingModel );
		}
	} );
	
	return model;
} );
/**
 * Handles interactions with our form settings collection.
 * 
 * @package Ninja Forms builder
 * @subpackage Advanced
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/advanced/data',['models/advanced/settingsModel'], function( settingsModel ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Load our action collection from our localized form data
			this.model = new settingsModel( preloadedFormData.settings );

			nfRadio.channel( 'settings' ).reply( 'get:settings', this.getSettings, this );
			nfRadio.channel( 'settings' ).reply( 'get:setting', this.getSetting, this );
			nfRadio.channel( 'settings' ).reply( 'update:setting', this.updateSetting, this );
		},

		getSettings: function() {
			return this.model;
		},

		updateSetting: function( name, value, silent ) {
			silent = silent || false;
			this.model.set( name, value, { silent: silent } );
		},

		getSetting: function( name ) {
			return this.model.get( name );
		}
	});

	return controller;
} );
/**
 * Fetches settings models so that we can get setting information
 * 
 * @package Ninja Forms builder
 * @subpackage Advanced
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/advanced/formSettings',['models/app/settingCollection'], function( settingCollection ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.collection = new settingCollection( formSettings, { objectType: 'settings' } );
					
			// Responds to requests for settings models.
			nfRadio.channel( 'settings' ).reply( 'get:settingModel', this.getSettingModel, this );

			// Responds to requests for our setting collection
			nfRadio.channel( 'settings' ).reply( 'get:collection', this.getSettingCollection, this );
		},

		getSettingModel: function( name ) {
			return this.collection.findWhere( { name: name } );
		},

		getSettingCollection: function() {
			return this.collection;
		}

	});

	return controller;
} );
/**
 * Handles adding and removing the active class from form settings currently being edited.
 * 
 * @package Ninja Forms builder
 * @subpackage Advanced
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/advanced/editActive',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Respond to requests to remove the active class from all our fields.
			nfRadio.channel( 'settings' ).reply( 'clear:editActive', this.clearEditActive, this );
			// Listen for the closing drawer so that we can remove all of our active classes.
			this.listenTo( nfRadio.channel( 'drawer-editSettings' ), 'before:closeDrawer', this.clearEditActive );
		},

		/**
		 * Loops through our fields collection and sets editActive to false.
		 * 
		 * @since  3.0
		 * @return void
		 */
        clearEditActive: function() {
            var collection = nfRadio.channel( 'settings' ).request( 'get:typeCollection' );
            _.each( collection.models, function( field ) {
				field.set( 'editActive', false );
            } );
        }
	});

	return controller;
} );

/**
 * Listens for clicks on our form settings sections.
 * 
 * @package Ninja Forms builder
 * @subpackage Advanced
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/advanced/clickEdit',['models/advanced/settingsModel'], function( settingsModel ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			this.listenTo( nfRadio.channel( 'settings' ), 'click:edit', this.clickEdit );
		},

		clickEdit: function( e, typeModel ) {
			var model = nfRadio.channel( 'settings' ).request( 'get:settings' );
			nfRadio.channel( 'app' ).request( 'open:drawer', 'editSettings', { model: model, groupCollection: typeModel.get( 'settingGroups' ), typeModel: typeModel } );
			var preventClose = nfRadio.channel( 'drawer' ).request( 'get:preventClose' );
			if ( ! preventClose ) {
				typeModel.set( 'editActive', true );
			}
		}
	});

	return controller;
} );
/**
 * Makes sure that calculations don't reference calculations with a lower order.
 *
 * For example, our first caclulation can't reference the second, but the second can reference the first.
 * 
 * @package Ninja Forms builder
 * @subpackage Advanced
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'controllers/advanced/calculations',[], function() {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			/*
			 * When someone types in the "name" or "eq" portion of our calculation, we need to make sure
			 * that they haven't duplicated a name or made a bad EQ reference.
			 */
			this.listenTo( nfRadio.channel( 'option-repeater-calculations' ), 'keyup:option', this.keyUp );
			/*
			 * Same thing for when our calculation option is updated
			 */
			this.listenTo( nfRadio.channel( 'option-repeater-calculations' ), 'update:option', this.updateCalc );
			/*
			 * When we sort our calcluations, we need to make sure that we don't get any bad EQ
			 * references.
			 */
			this.listenTo( nfRadio.channel( 'option-repeater-calculations' ), 'sort:option', this.sortCalc );
		},

		keyUp: function( e, optionModel ) {
			// Get our current value
			var value = jQuery( e.target ).val();
			// Check to see if we're editing a name or eq
            var id = jQuery( e.target ).data( 'id' );
			if( 'name' == id ) { // We are editing the name field
				// Check to see if our name already exists.
				this.checkName( value, optionModel );
				this.checkEQ( optionModel.get( 'eq' ), optionModel );
			} else if( 'eq' == id ) { // We're editing the eq
				// Check to see if there are any calcs referenced in our eq
				this.checkEQ( value, optionModel );
			} else if( 'dec' == id ) { // We're editing the dec
                // Check to see that we have a non-negative integer
                this.checkDec( value, optionModel );
            }
		},

		updateCalc: function( optionModel ) {
			this.checkName( optionModel.get( 'name' ), optionModel, false );
			this.checkEQ( optionModel.get( 'eq' ), optionModel );
			this.checkDec( optionModel.get( 'dec' ), optionModel );

			Backbone.Radio.channel( 'calcs' ).trigger( 'update:calc', optionModel );
		},

		sortCalc: function( optionModel, setting ) {
			this.checkAllCalcs( setting.collection );
		},

		/**
		 * Check to see if a calc name exists.
		 * 
		 * @since  3.0
		 * @param  string 			name        calc name to check
		 * @param  backbone.model 	optionModel 
		 * @return void
		 */
		checkName: function( name, optionModel, silent ) {
			silent = silent || true;
			// Get our current errors, if any.
			var errors = optionModel.get( 'errors' );
			// Search our calc collection for our name
			var found = optionModel.collection.where( { name: jQuery.trim( name ) } );

			// If the name that was passed is the same as our current name, return false.
			if ( name == optionModel.get( 'name' ) ) {
				found = [];
			}

			// If our name exists, add an errors to the option model
			if ( 0 != found.length ) {
				errors.nameExists = 'Calculation names must be unique. Please enter another name.';
			} else {
				var oldName = optionModel.get( 'name' );
				optionModel.set( 'name', name, { silent: silent } );
				nfRadio.channel( 'calcs' ).trigger( 'update:calcName', optionModel, oldName );
				delete errors.nameExists;
			}

			optionModel.set( 'errors', errors );
			optionModel.trigger( 'change:errors', optionModel );
		},

		/**
		 * Check to see if an eq contains a reference to a calc at a lower priority.
		 *
		 * @since  3.0
		 * @param  string 			eq          our equation
		 * @param  backbone.model 	optionModel
		 * @return void
		 */
		checkEQ: function( eq, optionModel ) {
			// Get any current errors on our optionModel
			var errors = optionModel.get( 'errors' );
			/*
			 * We're looking for two errors:
			 * - Calculations that are below the current one can't be processed.
			 * - Calculations can't refer to themselves.
			 */ 
			var errorSelfRef = false;
			var errorFutureCalc = false;
			// Regex that searches for {calc:key}
			var calcs = eq.match( new RegExp( /{calc:(.*?)}/g ) );
			/*
			 * Calcs will be an array like:
			 * ['{calc:test}'], ['{calc:another}']
			 * 
			 * If we have any calcs in the eq, loop through them and search for the errors.
			 */
			if ( calcs ) {
				var calculations = optionModel.collection;
				// Maps a function to each item in our calcs array.
				calcs = calcs.map( function( calc ) {
					// calc will be {calc:name}
					var name = calc.replace( '}', '' ).replace( '{calc:', '' );
					// Get our optionModel from our calculations collection.
					var targetCalc = calculations.findWhere( { name: name } );
					if ( name == optionModel.get( 'name' ) ) {
						// If we already have a calc with this name, set an error.
						errors.selfRef = 'A calculation can\'t reference itself!';
						errorSelfRef = true;
					} else if ( targetCalc && targetCalc.get( 'order' ) > optionModel.get( 'order' ) ) {
						// If the calc is after this one, set an error. 
						errorFutureCalc = true;
						errors.futureCalc = 'Can\'t reference a future calculation!';
					}
				} );
			}

			// If we didn't find any self ref errors, remove the key.
			if ( ! errorSelfRef ) {
				delete errors.selfRef;
			}

			// If we didn't find any future calc errors, remove the key.
			if ( ! errorFutureCalc ) {
				delete errors.futureCalc;
			}

			// Set errors and trigger our optionModel change.
			optionModel.set( 'errors', errors );
			optionModel.trigger( 'change:errors', optionModel );

		},

        /**
         * Ceck to see if a dec is an integer value.
         * 
         * @since 3.1
         * @param string            dec         our decimal value
         * @param backbone.model    optionModel
         * @return void
         */
        checkDec: function( dec, optionModel ) {
            // If dec isn't defined, bail...
            if( 'undefined' === typeof(dec) ) return false;
			// Get our current errors, if any.
			var errors = optionModel.get( 'errors' );
            /**
             * We're looking for one error:
             * - dec is not a non-negative integer.
             */
            var errorNonIntDec = false;
            
            // Get our target value and see if it matches what we got.
            var checked = Math.abs( parseInt( dec.trim() ) );
            if ( dec.trim() !== '' && checked.toString() !== dec.trim() ) {
                errorNonIntDec = true;
                errors.nonIntDec = 'Decimals must be a non-negative integer!';
            }
            
            // If our dec value is a non-negative integer.
            if ( ! errorNonIntDec ) {
                delete errors.nonIntDec;
            }
            
			// Set errors and trigger our optionModel change.
			optionModel.set( 'errors', errors );
			optionModel.trigger( 'change:errors', optionModel );
            
        },
        
		checkAllCalcs: function( collection ) {
			var that = this;
			collection.models.map( function( opt ) {
				that.checkName( opt.get( 'name' ), opt );
				that.checkEQ( opt.get( 'eq' ), opt );
                that.checkDec( opt.get( 'dec' ), opt );
			} );
		}

	});

	return controller;
} );

/**
 * Loads all of our controllers using Require JS.
 * 
 * @package Ninja Forms builder
 * @subpackage Fields
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define(
	'controllers/loadControllers',[
		/*
		 * Application controllers
		 */
		'controllers/app/remote',
		'controllers/app/drawer',
		'controllers/app/drawerConfig',
		'controllers/app/domainConfig',
		'controllers/app/data',		
		'controllers/app/drawerToggleSettingGroup',
		'controllers/app/updateDB',
		'controllers/app/formData',
		'controllers/app/previewLink',
		'controllers/app/menuButtons',
		'controllers/app/trackChanges',
		'controllers/app/undoChanges',
		'controllers/app/publishResponse',
		'controllers/app/changeDomain',
		'controllers/app/pushstate',
		'controllers/app/hotkeys',
		'controllers/app/cleanState',
		'controllers/app/coreUndo',
		'controllers/app/cloneModelDeep',
		'controllers/app/getSettingChildView',
		'controllers/app/changeSettingDefault',
		'controllers/app/fieldset',
		'controllers/app/toggleSetting',
		'controllers/app/buttonToggleSetting',
		'controllers/app/numberSetting',
		'controllers/app/radioSetting',
		'controllers/app/itemControls',
		'controllers/app/mergeTags',
		'controllers/app/mergeTagBox',
		'controllers/app/itemSettingFill',
		'controllers/app/confirmPublish',
		'controllers/app/rte',
		'controllers/app/settingFieldSelect',
		'controllers/app/settingFieldList',
		'controllers/app/settingHTML',
		'controllers/app/settingColor',
		'controllers/app/changeMenu',
		'controllers/app/mobile',
		'controllers/app/notices',
		'controllers/app/unloadCheck',
		'controllers/app/formContentFilters',
		'controllers/app/formContentGutterFilters',
		'controllers/app/cloneCollectionDeep',
		'controllers/app/trackKeyDown',
		'controllers/app/perfectScroll',
		'controllers/app/getNewSettingGroupCollection',
		'controllers/app/settingMedia',
		'controllers/app/publicLink',
		/*
		 * Fields domain controllers
		 */
		'controllers/fields/types',
		'controllers/fields/fieldTypeDrag',
		'controllers/fields/stagingDrag',
		'controllers/fields/staging',
		'controllers/fields/stagingSortable',
		'controllers/fields/filterTypes',
		'controllers/fields/sortable',
		'controllers/fields/data',
		'controllers/app/optionRepeater',
		'controllers/app/imageOptionRepeater',
		'controllers/fields/editActive',
		'controllers/fields/fieldSettings',
		'controllers/fields/fieldCreditCard',
		'controllers/fields/fieldList',
		'controllers/fields/fieldPassword',
		'controllers/fields/fieldQuantity',
		'controllers/fields/fieldShipping',
		'controllers/fields/key',
		'controllers/fields/notices',
		'controllers/fields/mobile',
		'controllers/fields/savedFields',
		'controllers/fields/fieldDatepicker',
		'controllers/fields/fieldDisplayCalc',
		'controllers/fields/fieldRepeater',

		/*
		 * TODO: Actions domain controllers
		 */
		'controllers/actions/types',
		'controllers/actions/data',
		'controllers/actions/actionSettings',
		'controllers/actions/editActive',
		'controllers/actions/emailFromSetting',
		'controllers/actions/addActionTypes',
		'controllers/actions/typeDrag',
		'controllers/actions/droppable',
		'controllers/actions/filterTypes',
		'controllers/actions/newsletterList',
		'controllers/actions/deleteFieldListener',
		'controllers/actions/collectPaymentFields',
		'controllers/actions/collectPaymentCalculations',
		'controllers/actions/collectPaymentFixed',
		'controllers/actions/collectPayment',
		'controllers/actions/save',

		/*
		 * TODO: Settings domain controllers
		 */
		'controllers/advanced/types',
		'controllers/advanced/data',
		'controllers/advanced/formSettings',
		'controllers/advanced/editActive',
		'controllers/advanced/clickEdit',
		'controllers/advanced/calculations'
	],
	function(
		/*
		 * Application controllers
		 */
		Remote,
		Drawer,
		DrawerConfig,
		DomainConfig,
		AppData,
		DrawerToggleSettingGroup,
		UpdateDB,
		FormData,
		PreviewLink,
		AppMenuButtons,
		AppTrackChanges,
		AppUndoChanges,
		AppPublishResponse,
		AppChangeDomain,
		Pushstate,
		Hotkeys,
		CleanState,
		CoreUndo,
		CloneModelDeep,
		DrawerSettingChildView,
		ChangeSettingDefault,
		Fieldset,
		ToggleSetting,
		ButtonToggleSetting,
		NumberSetting,
		RadioSetting,
		ItemControls,
		MergeTags,
		MergeTagsBox,
		ItemSettingFill,
		ConfirmPublish,
		RTE,
		SettingFieldSelect,
		SettingFieldList,
		SettingHTML,
		SettingColor,
		ChangeMenu,
		AppMobile,
		AppNotices,
		AppUnloadCheck,
		FormContentFilters,
		FormContentGutterFilters,
		CloneCollectionDeep,
		TrackKeyDown,
		PerfectScroll,
		GetNewSettingGroupCollection,
		SettingMedia,
		PublicLink,
		/*
		 * Fields domain controllers
		 */
		FieldTypes,
		FieldTypeDrag,
		FieldStagingDrag,
		StagedFieldsData,
		StagedFieldsSortable,
		DrawerFilterFieldTypes,
		MainContentFieldsSortable,
		FieldData,
		OptionRepeater,
		imageOptionRepeater,
		FieldsEditActive,
		FieldSettings,
		FieldCreditCard,
		FieldList,
		FieldPassword,
		FieldQuantity,
		FieldShipping,
		FieldKey,
		Notices,
		FieldsMobile,
		SavedFields,
		FieldDatepicker,
		FieldDisplayCalc,
		FieldRepeater,
		/*
		 * TODO: Actions domain controllers
		 */
		ActionTypes,
		ActionData,
		ActionSettings,
		ActionEditActive,
		ActionEmailFromSetting,
		ActionAddTypes,
		ActionTypeDrag,
		ActionDroppable,
		ActionFilterTypes,
		ActionNewsletterList,
		ActionDeleteFieldListener,
		ActionCollectPaymentFields,
		ActionCollectPaymentCalculations,
		ActionCollectPaymentFixed,
		ActionCollectPayment,
		ActionSave,

		/*
		 * TODO: Settings domain controllers
		 */
		SettingTypes,
		SettingData,
		FormSettings,
		SettingsEditActive,
		SettingsClickEdit,
		AdvancedCalculations
		
	) {
		var controller = Marionette.Object.extend( {
			initialize: function() {
				/*
				 * Application controllers
				 */
				new FormContentFilters();
				new FormContentGutterFilters();
				new Hotkeys();
				new Remote();
				new Drawer();
				new DrawerConfig();
				new DomainConfig();
				new DrawerToggleSettingGroup();
				new PreviewLink();
				new AppMenuButtons();
				new AppTrackChanges();
				new AppUndoChanges();
				new AppPublishResponse();
				new AppChangeDomain();
				new CleanState();
				new CoreUndo();
				new CloneModelDeep();
				new ItemControls();
				new ConfirmPublish();
				new RTE();
				new SettingFieldSelect();
				new SettingFieldList();
				new SettingHTML();
				new SettingColor();
				new SettingMedia();
				new ChangeMenu();
				new AppMobile();
				new AppNotices();
				new AppUnloadCheck();
				new UpdateDB();
				new CloneCollectionDeep();
				new TrackKeyDown();
				new PerfectScroll();
				new GetNewSettingGroupCollection();
				new PublicLink();
				// new Pushstate();
				/*
				 * Fields domain controllers
				 * 
				 * Field-specific controllers should be loaded before our field type controller.
				 * This ensures that any 'init' hooks are properly registered.
				 */
				new Fieldset();
				new OptionRepeater();
				new imageOptionRepeater();
				new FieldTypes();
				new FieldTypeDrag();
				new FieldStagingDrag();
				new StagedFieldsData();
				new StagedFieldsSortable();
				new DrawerFilterFieldTypes();
				new MainContentFieldsSortable();
				new ChangeSettingDefault();
				new ToggleSetting();
				new ButtonToggleSetting();
				new NumberSetting();
				new RadioSetting();
				new DrawerSettingChildView();
				new FieldsEditActive();
				new FieldSettings();
				new FieldCreditCard();
				new FieldList();
				new FieldPassword;
				new FieldQuantity();
				new FieldShipping();
				new FieldKey();
				new Notices();
				new FieldsMobile();
				new SavedFields();
				new FieldDatepicker();
				new FieldDisplayCalc();
				new FieldRepeater();
				/*
				 * TODO: Actions domain controllers
				 */
				new ActionNewsletterList();
				new ActionDeleteFieldListener();
				new ActionCollectPaymentCalculations();
				new ActionCollectPayment();
				new ActionSave();
				new ActionTypes();
				new ActionData();
				new ActionSettings();
				new ActionEditActive();
				new ActionEmailFromSetting();
				new ActionAddTypes();
				new ActionTypeDrag();
				new ActionDroppable();
				new ActionFilterTypes();
				new ActionCollectPaymentFields();
				new ActionCollectPaymentFixed();

				/*
				 * TODO: Settings domain controllers
				 */
				new SettingTypes();
				new FormSettings();
				new AdvancedCalculations();
				new SettingData();
				new SettingsEditActive();
				new SettingsClickEdit();
				
				/*
				 * Data controllers need to be set after every other controller has been setup, even if they aren't domain-specific.
				 * AppData() was after FormData();
				 */
				new AppData();
				new FieldData();
				new FormData();
				new MergeTags();
				new MergeTagsBox();
				new ItemSettingFill();
			}
		});

		return controller;
} );

define( 'views/fields/mainContentEmpty',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-main-content-fields-empty',

		onBeforeDestroy: function() {
			jQuery( this.el ).parent().removeClass( 'nf-fields-empty-droppable' ).droppable( 'destroy' );
		},

		onRender: function() {
			this.$el = this.$el.children();
			this.$el.unwrap();
			this.setElement( this.$el );
		},

		onShow: function() {
			if ( jQuery( this.el ).parent().hasClass( 'ui-sortable' ) ) {
				jQuery( this.el ).parent().sortable( 'destroy' );
			}
			jQuery( this.el ).parent().addClass( 'nf-fields-empty-droppable' );
			jQuery( this.el ).parent().droppable( {
				accept: function( draggable ) {
					if ( jQuery( draggable ).hasClass( 'nf-stage' ) || jQuery( draggable ).hasClass( 'nf-field-type-button' ) ) {
						return true;
					}
				},
				activeClass: 'nf-droppable-active',
				hoverClass: 'nf-droppable-hover',
				tolerance: 'pointer',
				over: function( e, ui ) {
					ui.item = ui.draggable;
					nfRadio.channel( 'app' ).request( 'over:fieldsSortable', ui );
				},
				out: function( e, ui ) {
					ui.item = ui.draggable;
					nfRadio.channel( 'app' ).request( 'out:fieldsSortable', ui );
				},
				drop: function( e, ui ) {
					ui.item = ui.draggable;
					nfRadio.channel( 'app' ).request( 'receive:fieldsSortable', ui );
					var fieldCollection = nfRadio.channel( 'fields' ).request( 'get:collection' );
					fieldCollection.trigger( 'reset', fieldCollection );
				},
			} );
		}
	});

	return view;
} );
/**
 * Renders our form title.
 *
 * @package Ninja Forms builder
 * @subpackage App
 * @copyright (c) 2015 WP Ninjas
 * @since 3.0
 */
define( 'views/app/formTitle',[], function() {
	var view = Marionette.ItemView.extend({
		tagName: 'div',
		template: '#tmpl-nf-header-form-title',

		initialize: function() {
			// When we change the model (to disable it, for example), re-render.
			this.model.on( 'change:title', this.render, this );
		},

		/**
		 * These functions are available to templates, and help us to remove logic from template files.
		 * 
		 * @since  3.0
		 * @return Object
		 */
		templateHelpers: function() {
			var that = this;
	    	return {
	    		renderTitle: function(){
	    			var formData = nfRadio.channel( 'app' ).request( 'get:formModel' );
	    			return _.escape( formData.get( 'settings' ).get( 'title' ) );
				},
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
define( 'views/loadViews',[ 'views/fields/fieldItem', 'views/fields/mainContentEmpty', 'views/app/formTitle' ], function( fieldItemView, mainContentEmptyView, FormTitleView ) {
	var controller = Marionette.Object.extend( {
		initialize: function() {
			// Reply to requests for our field item view.
			nfRadio.channel( 'views' ).reply( 'get:fieldItem', this.getFieldItem );
		
			// Reply to requests for our empty content view.
			nfRadio.channel( 'views' ).reply( 'get:mainContentEmpty', this.getMainContentEmpty );
		
			// Reply to requests for our form title view.
			nfRadio.channel( 'views' ).reply( 'get:formTitle', this.getFormTitle );
		},

		getFieldItem: function( model ) {
			return fieldItemView;
		},

		getMainContentEmpty: function() {
			return mainContentEmptyView;
		},

		getFormTitle: function() {
			return FormTitleView;
		}

	});

	return controller;
} );
var nfRadio = Backbone.Radio;

jQuery( document ).ready( function( $ ) {
	require( ['views/app/builder', 'controllers/loadControllers', 'views/loadViews'], function( BuilderView, LoadControllers, LoadViews ) {

		var NinjaForms = Marionette.Application.extend( {

			initialize: function( options ) {

				var that = this;
				Marionette.Renderer.render = function(template, data){
					var template = that.template( template );
					return template( data );
				};

				// Trigger an event before we load our controllers.
				nfRadio.channel( 'app' ).trigger( 'before:loadControllers', this );
				// Load our controllers.
				var loadControllers = new LoadControllers();
				// Trigger an event after we load our controllers.
				nfRadio.channel( 'app' ).trigger( 'after:loadControllers', this );

				// Trigger an event before we load un-instantiated views
				nfRadio.channel( 'app' ).trigger( 'before:loadViews', this );
				var loadViews = new LoadViews();
				// Trigger an event after we load un-instantiated views.
				nfRadio.channel( 'app' ).trigger( 'after:loadViews', this );

				nfRadio.channel( 'app' ).reply( 'get:template', this.template );
			},

			onStart: function() {
				var builderView = new BuilderView();
				// Trigger our after start event.
				nfRadio.channel( 'app' ).trigger( 'after:appStart', this );

				/*
				 * If we're on the new forms builder, open the add fields drawer.
				 */
				if ( 0 == nfAdmin.formID ) {
					nfRadio.channel( 'app' ).request( 'open:drawer', 'addField' );
				}
			},

			template: function( template ) {
				return _.template( $( template ).html(),  {
					evaluate:    /<#([\s\S]+?)#>/g,
					interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
					escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
					variable:    'data'
				} );
			}
		} );
	
		var ninjaForms = new NinjaForms();
		ninjaForms.start();
	} );
} );
define("main", function(){});

}());
//# sourceMappingURL=builder.js.map
