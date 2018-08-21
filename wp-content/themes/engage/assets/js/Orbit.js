// Original JavaScript code by Chirp Internet: www.chirp.com.au
// Please acknowledge use of this code by including this header.
// Modified very, very heavily by Jeremy Jones: https://jeremyjon.es


class Orbit {
  constructor() {
    this.field = document.getElementById('orbit-field')
    this.ballsEl = document.getElementById('orbit-balls')
    this.gravitationalPull = 80

    this.gravityText = document.getElementById('orbit-current-gravity')
    this.increasePullBtn = document.getElementById('orbit-increase-pull')
    this.decreasePullBtn = document.getElementById('orbit-decrease-pull')
    this.balls = null

    this.ballSettings = {
      num: 80,
      minSize: 4,
      maxSize: 18,
    }

    this.start = 0

    this.init(80)
  }

  init(ballsNum) {
    this.balls = this.createBalls(ballsNum)
    window.requestAnimationFrame(this.step.bind(this))
    this.setPullButtons(this.gravitationalPull)
    this.increasePullBtn.addEventListener('click', this.increaseGravitationalPull.bind(this))
    this.decreasePullBtn.addEventListener('click', this.decreaseGravitationalPull.bind(this))
    

    // uncomment to have planet track cursor
    // document.onmousemove = getCursorXY;

  }

  createBalls() {
    let size;
    for(let i = 0; i < this.ballSettings.num; i++) {
      // get random size between setting sizes
      size = Math.ceil(this.ballSettings.minSize + (Math.random() * (this.ballSettings.maxSize - this.ballSettings.minSize)))
      this.createBall(size)
    }

    // return all the balls
    return document.querySelectorAll('.orbit-ball');
  }

  createBall(size) {
    let newBall, stretchDir

    newBall = document.createElement("div")
    stretchDir = (Math.round((Math.random() * 1)) ? 'x' : 'y') 
    newBall.classList.add('orbit-ball')
    newBall.style.width = size + 'px'
    newBall.style.height = size + 'px'
    newBall.style.background = this.getRandomColor();
    newBall.setAttribute('data-stretch-dir', stretchDir) // either x or y
    newBall.setAttribute('data-stretch-val',  1 + (Math.random() * 5))
    newBall.setAttribute('data-grid', this.field.offsetWidth + Math.round((Math.random() * 100))) // min orbit = 30px, max 130
    newBall.setAttribute('data-duration', 3.5 + Math.round((Math.random() * 8))) // min duration = 3.5s, max 8s
    newBall.setAttribute('data-start', 0)
    this.ballsEl.appendChild(newBall)
  }

  callStep(timestamp) {
    return this.step(timestamp)
  }

  step(timestamp) {
    let progress, x, y, stretch, gridSize, duration, start, xPos, yPos

    for(let i = 0; i < this.balls.length; i++) {

      start = this.balls[i].getAttribute('data-start')
      if(start == 0) {
        start = timestamp
        this.balls[i].setAttribute('data-start', start)
      }

      gridSize = this.balls[i].getAttribute('data-grid')
      duration = this.balls[i].getAttribute('data-duration')
      progress = (timestamp - start) / duration / 1000 // percent
      stretch = this.balls[i].getAttribute('data-stretch-val')

      if(this.balls[i].getAttribute('data-stretch-dir') === 'x') {
        x = ((stretch * Math.sin(progress * 2 * Math.PI)) * (1.05 - (this.gravitationalPull/100)))// x = ƒ(t)
        y = Math.cos(progress * 2 * Math.PI) // y = ƒ(t)
      } else {
        x = Math.sin(progress * 2 * Math.PI) // x = ƒ(t)
        y = ((stretch * Math.cos(progress * 2 * Math.PI)) * (1.05 - (this.gravitationalPull/100))) // y = ƒ(t)
      }

      xPos = this.field.clientWidth/2 + (gridSize * x)
      yPos = this.field.clientHeight/2 + (gridSize * y)
      this.balls[i].style.transform = 'translate3d('+xPos + 'px, '+yPos + 'px, 0)'

      // if these are true, then it's behind the planet
      if(((this.balls[i].getAttribute('data-stretch-dir') === 'x') && (((this.field.offsetWidth/2) - this.balls[i].offsetWidth) * -1) < xPos && xPos < ((this.field.offsetWidth/2) + this.balls[i].offsetWidth)) || ((this.balls[i].getAttribute('data-stretch-dir') === 'y') && (((this.field.offsetWidth/2) - this.balls[i].offsetWidth) * -1) < yPos && yPos < ((this.field.offsetWidth/2) + this.balls[i].offsetWidth))) {
        // backside of the moon
        this.balls[i].style.zIndex = '-1'
      } else {
        // ...front side of the moon
        this.balls[i].style.zIndex = '9'
      }

      if(progress >= 1) {
        this.balls[i].setAttribute('data-start', 0) // reset to start position
      }
    }

    window.requestAnimationFrame(this.step.bind(this))
  }


  // since I don't know physics, this is an approriximation
  setGravitationalPull(percent) {
    let step, steps, time, direction

    this.disablePullButtons()

    if(percent < 0) {
      return
    }

    if(100 < percent) {
      return
    }

    if(percent === this.gravitationalPull) {
      return
    }

    steps = 20
    step = Math.abs(percent - this.gravitationalPull)/steps
    direction = (percent < this.gravitationalPull ? '-' : '+')

    // get the current pull and step it down over 20 steps so it's smoother than jumping straight there
    for(let i = 0; i < steps; i++) {
      // set the time this will fire
      time = i * (i/Math.PI)
      // minimum time span
      if(time < 4) {
        time = 4
      }
      // set the function
      setTimeout(()=>{
        if(direction === '-') {
          this.gravitationalPull -= step 
        } else {
          this.gravitationalPull += step 
        }
        
      }, time);

      // on our last one, set the gravitationalPull to its final, nicely rounded number
      if(i === steps - 1) {
        setTimeout(()=>{
          this.gravitationalPull = Math.round(this.gravitationalPull)
          this.setPullButtons()
        }, time + 20)
      }
    }

  }


  setPullButtons() {
    if(this.gravitationalPull <= 0) {
      this.decreasePullBtn.disabled = true
      this.increasePullBtn.disabled = false
    } 

    else if(100 <= this.gravitationalPull) {
      this.decreasePullBtn.disabled = false
      this.increasePullBtn.disabled = true
    } 

    else {
      this.decreasePullBtn.disabled = false
      this.increasePullBtn.disabled = false
    }

    this.gravityText.innerHTML = this.gravitationalPull
  }

  disablePullButtons() {
    this.decreasePullBtn.disabled = true
    this.increasePullBtn.disabled = true
  }

  increaseGravitationalPull() {
    this.setGravitationalPull(this.gravitationalPull + 10)
  }

  decreaseGravitationalPull() {
    this.setGravitationalPull(this.gravitationalPull - 10)
  }

  // if you want the planet to track the cursor
  getCursorXY(e) {
    let cursorPos = {
      x: (window.Event) ? e.pageX : event.clientX + (document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft),
      y: (window.Event) ? e.pageY : event.clientY + (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop)
    }
    
    this.field.style.left = cursorPos.x - (this.field.offsetWidth/2) + "px"
    this.field.style.top = cursorPos.y - (this.field.offsetHeight/2) + "px"
  }

  getRandomColor() {
    let colors = [
      '#0ebeff',
      '#0ebeff', // I want more of the blue ones
      '#59C9A5',
      '#EDCA04',
      '#BF5700',
      '#00a9b7'
    ]
    
    return colors[Math.floor((Math.random() * colors.length))]
  }
} 

export default Orbit;