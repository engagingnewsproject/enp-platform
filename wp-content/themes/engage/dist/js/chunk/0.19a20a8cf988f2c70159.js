webpackJsonp([0],{

/***/ 7:
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

// Original JavaScript code by Chirp Internet: www.chirp.com.au
// Please acknowledge use of this code by including this header.
// Modified very, very heavily by Jeremy Jones: https://jeremyjon.es


var Orbit = function () {
  function Orbit() {
    _classCallCheck(this, Orbit);

    this.field = document.getElementById('orbit-field');
    this.ballsEl = document.getElementById('orbit-balls');
    this.gravitationalPull = 80;

    this.gravityText = document.getElementById('orbit-current-gravity');
    this.increasePullBtn = document.getElementById('orbit-increase-pull');
    this.decreasePullBtn = document.getElementById('orbit-decrease-pull');
    this.toggleAnimateBtn = document.getElementById('orbit-toggle-animate');
    this.balls = null;
    this.animate = true;

    this.ballSettings = {
      num: 80,
      minSize: 4,
      maxSize: 12
    };

    this.start = 0;

    this.init(80);
  }

  _createClass(Orbit, [{
    key: 'init',
    value: function init(ballsNum) {
      this.balls = this.createBalls(ballsNum);
      window.requestAnimationFrame(this.step.bind(this));
      this.setPullButtons(this.gravitationalPull);
      this.increasePullBtn.addEventListener('click', this.increaseGravitationalPull.bind(this));
      this.decreasePullBtn.addEventListener('click', this.decreaseGravitationalPull.bind(this));
      this.toggleAnimateBtn.addEventListener('click', this.toggleAnimate.bind(this));

      // uncomment to have planet track cursor
      // document.onmousemove = getCursorXY;
    }
  }, {
    key: 'createBalls',
    value: function createBalls() {
      var size = void 0;
      for (var i = 0; i < this.ballSettings.num; i++) {
        // get random size between setting sizes
        size = Math.ceil(this.ballSettings.minSize + Math.random() * (this.ballSettings.maxSize - this.ballSettings.minSize));
        this.createBall(size);
      }

      // return all the balls
      return document.querySelectorAll('.orbit-ball');
    }
  }, {
    key: 'createBall',
    value: function createBall(size) {
      var newBall = void 0,
          stretchDir = void 0;

      newBall = document.createElement("div");
      stretchDir = Math.round(Math.random() * 1) ? 'x' : 'y';
      newBall.classList.add('orbit-ball');
      newBall.style.width = size + 'px';
      newBall.style.height = size + 'px';
      newBall.style.background = this.getRandomColor();
      newBall.setAttribute('data-stretch-dir', stretchDir); // either x or y

      // TODO: Decrease the 'data-stretch-val' attribute to decrease the spread of the balls
      newBall.setAttribute('data-stretch-val', 1 + Math.random() * 5);
      newBall.setAttribute('data-grid', this.field.offsetWidth + Math.round(Math.random() * 100)); // min orbit = 30px, max 130
      newBall.setAttribute('data-duration', 3.5 + Math.round(Math.random() * 8)); // min duration = 3.5s, max 8s
      newBall.setAttribute('data-start', 0);
      this.ballsEl.appendChild(newBall);
    }
  }, {
    key: 'callStep',
    value: function callStep(timestamp) {
      return this.step(timestamp);
    }
  }, {
    key: 'step',
    value: function step(timestamp) {
      var progress = void 0,
          x = void 0,
          y = void 0,
          stretch = void 0,
          gridSize = void 0,
          duration = void 0,
          start = void 0,
          xPos = void 0,
          yPos = void 0;
      for (var i = 0; i < this.balls.length; i++) {

        start = this.balls[i].getAttribute('data-start');
        if (start == 0) {
          start = timestamp;
          this.balls[i].setAttribute('data-start', start);
        }

        gridSize = this.balls[i].getAttribute('data-grid');
        duration = this.balls[i].getAttribute('data-duration');
        progress = (timestamp - start) / duration / 1000; // percent
        stretch = this.balls[i].getAttribute('data-stretch-val');

        if (this.balls[i].getAttribute('data-stretch-dir') === 'x') {
          x = stretch * Math.sin(progress * 2 * Math.PI) * (1.05 - this.gravitationalPull / 100); // x = ƒ(t)
          y = Math.cos(progress * 2 * Math.PI); // y = ƒ(t)
        } else {
          x = Math.sin(progress * 2 * Math.PI); // x = ƒ(t)
          y = stretch * Math.cos(progress * 2 * Math.PI) * (1.05 - this.gravitationalPull / 100); // y = ƒ(t)
        }

        xPos = this.field.clientWidth / 2 + gridSize * x;
        yPos = this.field.clientHeight / 2 + gridSize * y;
        this.balls[i].style.transform = 'translate3d(' + xPos + 'px, ' + yPos + 'px, 0)';

        // if these are true, then it's behind the planet
        if (this.balls[i].getAttribute('data-stretch-dir') === 'x' && (this.field.offsetWidth / 2 - this.balls[i].offsetWidth) * -1 < xPos && xPos < this.field.offsetWidth / 2 + this.balls[i].offsetWidth || this.balls[i].getAttribute('data-stretch-dir') === 'y' && (this.field.offsetWidth / 2 - this.balls[i].offsetWidth) * -1 < yPos && yPos < this.field.offsetWidth / 2 + this.balls[i].offsetWidth) {
          // backside of the moon
          this.balls[i].style.zIndex = '-1';
        } else {
          // ...front side of the moon
          this.balls[i].style.zIndex = '9';
        }

        if (progress >= 1) {
          this.balls[i].setAttribute('data-start', 0); // reset to start position
        }
      }
      if (this.animate == true) {
        window.requestAnimationFrame(this.step.bind(this));
      }
    }
  }, {
    key: 'toggleAnimate',
    value: function toggleAnimate() {

      this.animate = !this.animate;
      if (this.animate) {
        this.toggleAnimateBtn.innerHTML = '<i class="fas fa-pause"></i>';
        // resume the animation
        window.requestAnimationFrame(this.step.bind(this));
      } else {
        this.toggleAnimateBtn.innerHTML = '<i class="fas fa-play"></i>';
      }
    }

    // since I don't know physics, this is an approriximation

  }, {
    key: 'setGravitationalPull',
    value: function setGravitationalPull(percent) {
      var _this = this;

      var step = void 0,
          steps = void 0,
          time = void 0,
          direction = void 0;

      this.disablePullButtons();

      if (percent < 0) {
        return;
      }

      if (100 < percent) {
        return;
      }

      if (percent === this.gravitationalPull) {
        return;
      }

      steps = 20;
      step = Math.abs(percent - this.gravitationalPull) / steps;
      direction = percent < this.gravitationalPull ? '-' : '+';

      // get the current pull and step it down over 20 steps so it's smoother than jumping straight there
      for (var i = 0; i < steps; i++) {
        // set the time this will fire
        time = i * (i / Math.PI);
        // minimum time span
        if (time < 4) {
          time = 4;
        }
        // set the function
        setTimeout(function () {
          if (direction === '-') {
            _this.gravitationalPull -= step;
          } else {
            _this.gravitationalPull += step;
          }
        }, time);

        // on our last one, set the gravitationalPull to its final, nicely rounded number
        if (i === steps - 1) {
          setTimeout(function () {
            _this.gravitationalPull = Math.round(_this.gravitationalPull);
            _this.setPullButtons();
          }, time + 20);
        }
      }
    }
  }, {
    key: 'setPullButtons',
    value: function setPullButtons() {
      if (this.gravitationalPull <= 0) {
        this.decreasePullBtn.disabled = true;
        this.increasePullBtn.disabled = false;
      } else if (100 <= this.gravitationalPull) {
        this.decreasePullBtn.disabled = false;
        this.increasePullBtn.disabled = true;
      } else {
        this.decreasePullBtn.disabled = false;
        this.increasePullBtn.disabled = false;
      }

      this.gravityText.innerHTML = this.gravitationalPull;
    }
  }, {
    key: 'disablePullButtons',
    value: function disablePullButtons() {
      this.decreasePullBtn.disabled = true;
      this.increasePullBtn.disabled = true;
    }
  }, {
    key: 'increaseGravitationalPull',
    value: function increaseGravitationalPull() {
      this.setGravitationalPull(this.gravitationalPull + 10);
    }
  }, {
    key: 'decreaseGravitationalPull',
    value: function decreaseGravitationalPull() {
      this.setGravitationalPull(this.gravitationalPull - 10);
    }

    // if you want the planet to track the cursor

  }, {
    key: 'getCursorXY',
    value: function getCursorXY(e) {
      var cursorPos = {
        x: window.Event ? e.pageX : event.clientX + (document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft),
        y: window.Event ? e.pageY : event.clientY + (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop)
      };

      this.field.style.left = cursorPos.x - this.field.offsetWidth / 2 + "px";
      this.field.style.top = cursorPos.y - this.field.offsetHeight / 2 + "px";
    }
  }, {
    key: 'getRandomColor',
    value: function getRandomColor() {
      var colors = ['#00a9b7', '#005f86', '#d9534f', '#f8971f', '#BF5700', '#a6cd57'];

      return colors[Math.floor(Math.random() * colors.length)];
    }
  }]);

  return Orbit;
}();

/* harmony default export */ __webpack_exports__["default"] = (Orbit);

/***/ })

});