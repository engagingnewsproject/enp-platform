webpackJsonp([0],{6:function(t,i,s){"use strict";Object.defineProperty(i,"__esModule",{value:!0});var e=function(){function t(t,i){for(var s=0;s<i.length;s++){var e=i[s];e.enumerable=e.enumerable||!1,e.configurable=!0,"value"in e&&(e.writable=!0),Object.defineProperty(t,e.key,e)}}return function(i,s,e){return s&&t(i.prototype,s),e&&t(i,e),i}}();var n=function(){function t(i,s){!function(t,i){if(!(t instanceof i))throw new TypeError("Cannot call a class as a function")}(this,t),this.button=i,this.els=s,this.init()}return e(t,[{key:"init",value:function(){this.button.addEventListener("click",this.click.bind(this));var t=!0,i=!1,s=void 0;try{for(var e,n=this.els[Symbol.iterator]();!(t=(e=n.next()).done);t=!0){var o=e.value;o.classList.add("is-hidden"),this.ariaHidden(o)}}catch(t){i=!0,s=t}finally{try{!t&&n.return&&n.return()}finally{if(i)throw s}}this.button.classList.add("is-closed")}},{key:"click",value:function(t){t.preventDefault(),console.log(this.button.tagName),"A"===this.button.tagName&&this.button.classList.contains("is-open")&&(window.location=this.button.getAttribute("href")),this.toggleButton();var i=!0,s=!1,e=void 0;try{for(var n,o=this.els[Symbol.iterator]();!(i=(n=o.next()).done);i=!0){var a=n.value;this.toggle(a)}}catch(t){s=!0,e=t}finally{try{!i&&o.return&&o.return()}finally{if(s)throw e}}}},{key:"toggleButton",value:function(){var t,i,s,e,n=this;this.button.classList.contains("is-open")?((t=this.button.classList).remove.apply(t,["is-opening","is-open"]),(i=this.button.classList).add.apply(i,["is-closing","is-closed"]),setTimeout(function(){n.button.classList.remove("is-closing")},600)):((s=this.button.classList).remove.apply(s,["is-closing","is-closed"]),(e=this.button.classList).add.apply(e,["is-opening","is-open"]),setTimeout(function(){n.button.classList.remove("is-opening")},600))}},{key:"toggle",value:function(t){t.classList.contains("is-open")?this.hide(t):this.show(t)}},{key:"show",value:function(t){var i;t.classList.add("is-open"),(i=t.classList).remove.apply(i,["is-hidden","is-hiding"]),this.ariaShow(t),t.classList.add("is-opening"),setTimeout(function(){t.classList.remove("is-opening")},600)}},{key:"hide",value:function(t){var i,s;(i=t.classList).remove.apply(i,["is-open","is-opening"]),(s=t.classList).add.apply(s,["is-hidden","is-hiding"]),this.ariaHidden(t),setTimeout(function(){t.classList.remove("is-hiding")},600)}},{key:"ariaShow",value:function(t){t.setAttribute("aria-hidden",!1)}},{key:"ariaHidden",value:function(t){t.setAttribute("aria-hidden",!0)}}]),t}();i.default=n}});