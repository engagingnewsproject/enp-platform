!function(i){var o={common:{init:function(){},finalize:function(){}},home:{init:function(){},finalize:function(){}},about_us:{init:function(){}}},n={fire:function(i,n,e){var a,t=o;n=void 0===n?"init":n,a=""!==i,a=a&&t[i],a=a&&"function"==typeof t[i][n],a&&t[i][n](e)},loadEvents:function(){n.fire("common"),i.each(document.body.className.replace(/-/g,"_").split(/\s+/),function(i,o){n.fire(o),n.fire(o,"finalize")}),n.fire("common","finalize")}};i(document).ready(n.loadEvents)}(jQuery),function(i){var o={init:function(){i(document).on("click",'[data-toggle="collapse"]',function(){o.click(this)})},click:function(n){console.log("click!",n);var e=i(n).data("target");i(e).each(function(){o.toggle(this)})},toggle:function(n){console.log("toggle",n),i(n).hasClass("show")?o.hide(n):o.show(n)},show:function(n){console.log("show",n),i(n).addClass("show"),i(n).removeClass("hide"),i(n).removeClass("hiding"),o.ariaShow(n),i(n).addClass("showing"),setTimeout(function(){i(n).removeClass("showing")},500)},hide:function(n){console.log("hide",n),i(n).removeClass("show"),i(n).removeClass("showing"),i(n).addClass("hide"),o.ariaHidden(n),i(n).addClass("hiding"),setTimeout(function(){i(n).removeClass("hiding")},900)},ariaShow:function(o){console.log("ariaShow",o),i(o).attr("aria-hidden",!1)},ariaHidden:function(o){console.log("ariaHidden",o),i(o).attr("aria-hidden",!0)}};i(document).ready(o.init)}(jQuery);
//# sourceMappingURL=main.js.map
