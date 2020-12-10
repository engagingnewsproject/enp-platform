(window.webpackJsonp=window.webpackJsonp||[]).push([[1],{"./assets/js/orbit.js":
/*!****************************!*\
  !*** ./assets/js/orbit.js ***!
  \****************************/
/*! exports provided: default */function(t,e,i){"use strict";function a(t,e){for(var i=0;i<e.length;i++){var a=e[i];a.enumerable=a.enumerable||!1,a.configurable=!0,"value"in a&&(a.writable=!0),Object.defineProperty(t,a.key,a)}}i.r(e);var l=function(){function t(){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.field=document.getElementById("orbit-field"),this.ballsEl=document.getElementById("orbit-balls"),this.gravitationalPull=80,this.gravityText=document.getElementById("orbit-current-gravity"),this.increasePullBtn=document.getElementById("orbit-increase-pull"),this.decreasePullBtn=document.getElementById("orbit-decrease-pull"),this.toggleAnimateBtn=document.getElementById("orbit-toggle-animate"),this.balls=null,this.animate=!0,this.ballSettings={num:80,minSize:4,maxSize:12},this.start=0,this.init(80)}var e,i,l;return e=t,(i=[{key:"init",value:function(t){this.balls=this.createBalls(t),window.requestAnimationFrame(this.step.bind(this)),this.setPullButtons(this.gravitationalPull),this.increasePullBtn.addEventListener("click",this.increaseGravitationalPull.bind(this)),this.decreasePullBtn.addEventListener("click",this.decreaseGravitationalPull.bind(this)),this.toggleAnimateBtn.addEventListener("click",this.toggleAnimate.bind(this))}},{key:"createBalls",value:function(){for(var t,e=0;e<this.ballSettings.num;e++)t=Math.ceil(this.ballSettings.minSize+Math.random()*(this.ballSettings.maxSize-this.ballSettings.minSize)),this.createBall(t);return document.querySelectorAll(".orbit-ball")}},{key:"createBall",value:function(t){var e,i;e=document.createElement("div"),i=Math.round(1*Math.random())?"x":"y",e.classList.add("orbit-ball"),e.style.width=t+"px",e.style.height=t+"px",e.style.background=this.getRandomColor(),e.setAttribute("data-stretch-dir",i),e.setAttribute("data-stretch-val",1+5*Math.random()),e.setAttribute("data-grid",this.field.offsetWidth+Math.round(100*Math.random())),e.setAttribute("data-duration",3.5+Math.round(8*Math.random())),e.setAttribute("data-start",0),this.ballsEl.appendChild(e)}},{key:"callStep",value:function(t){return this.step(t)}},{key:"step",value:function(t){for(var e,i,a,l,s,n,r,o,h=0;h<this.balls.length;h++)0==(n=this.balls[h].getAttribute("data-start"))&&(n=t,this.balls[h].setAttribute("data-start",n)),s=this.balls[h].getAttribute("data-grid"),e=(t-n)/this.balls[h].getAttribute("data-duration")/1e3,l=this.balls[h].getAttribute("data-stretch-val"),"x"===this.balls[h].getAttribute("data-stretch-dir")?(i=l*Math.sin(2*e*Math.PI)*(1.05-this.gravitationalPull/100),a=Math.cos(2*e*Math.PI)):(i=Math.sin(2*e*Math.PI),a=l*Math.cos(2*e*Math.PI)*(1.05-this.gravitationalPull/100)),r=this.field.clientWidth/2+s*i,o=this.field.clientHeight/2+s*a,this.balls[h].style.transform="translate3d("+r+"px, "+o+"px, 0)","x"===this.balls[h].getAttribute("data-stretch-dir")&&-1*(this.field.offsetWidth/2-this.balls[h].offsetWidth)<r&&r<this.field.offsetWidth/2+this.balls[h].offsetWidth||"y"===this.balls[h].getAttribute("data-stretch-dir")&&-1*(this.field.offsetWidth/2-this.balls[h].offsetWidth)<o&&o<this.field.offsetWidth/2+this.balls[h].offsetWidth?this.balls[h].style.zIndex="-1":this.balls[h].style.zIndex="9",e>=1&&this.balls[h].setAttribute("data-start",0);1==this.animate&&window.requestAnimationFrame(this.step.bind(this))}},{key:"toggleAnimate",value:function(){this.animate=!this.animate,this.animate?(this.toggleAnimateBtn.innerHTML='<i class="fas fa-pause"></i>',window.requestAnimationFrame(this.step.bind(this))):this.toggleAnimateBtn.innerHTML='<i class="fas fa-play"></i>'}},{key:"setGravitationalPull",value:function(t){var e,i,a,l=this;if(this.disablePullButtons(),!(t<0||100<t||t===this.gravitationalPull)){e=Math.abs(t-this.gravitationalPull)/20,a=t<this.gravitationalPull?"-":"+";for(var s=0;s<20;s++)(i=s*(s/Math.PI))<4&&(i=4),setTimeout((function(){"-"===a?l.gravitationalPull-=e:l.gravitationalPull+=e}),i),19===s&&setTimeout((function(){l.gravitationalPull=Math.round(l.gravitationalPull),l.setPullButtons()}),i+20)}}},{key:"setPullButtons",value:function(){this.gravitationalPull<=0?(this.decreasePullBtn.disabled=!0,this.increasePullBtn.disabled=!1):100<=this.gravitationalPull?(this.decreasePullBtn.disabled=!1,this.increasePullBtn.disabled=!0):(this.decreasePullBtn.disabled=!1,this.increasePullBtn.disabled=!1),this.gravityText.innerHTML=this.gravitationalPull}},{key:"disablePullButtons",value:function(){this.decreasePullBtn.disabled=!0,this.increasePullBtn.disabled=!0}},{key:"increaseGravitationalPull",value:function(){this.setGravitationalPull(this.gravitationalPull+10)}},{key:"decreaseGravitationalPull",value:function(){this.setGravitationalPull(this.gravitationalPull-10)}},{key:"getCursorXY",value:function(t){var e=window.Event?t.pageX:event.clientX+(document.documentElement.scrollLeft?document.documentElement.scrollLeft:document.body.scrollLeft),i=window.Event?t.pageY:event.clientY+(document.documentElement.scrollTop?document.documentElement.scrollTop:document.body.scrollTop);this.field.style.left=e-this.field.offsetWidth/2+"px",this.field.style.top=i-this.field.offsetHeight/2+"px"}},{key:"getRandomColor",value:function(){var t=["#00a9b7","#005f86","#d6d2c4","#f8971f","#BF5700","#d9534f"];return t[Math.floor(Math.random()*t.length)]}}])&&a(e.prototype,i),l&&a(e,l),t}();e.default=l}}]);