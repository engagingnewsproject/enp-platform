class Collapse {
  constructor(button, els) {
    this.button = button
    this.els = els
    this.onClick = this.click.bind(this)

    // We need collapse.js for the navbar burger toggle thing
    this.init()

  }

  init() {
    // look for all collapse buttons and close them and set click listener on them
    this.button.addEventListener('click', this.onClick);
    // setup initial classes
    for(let el of this.els) {
      el.classList.add('is-hidden')
      this.ariaHidden(el)
    }
    this.button.classList.add('is-closed')
   /* $(document).on('click', '[data-toggle="collapse"]', function() {
        Collapse.click(this)
    })*/
  }

  destroy() {
    for (let el of this.els) {
      el.classList.remove('is-hidden')
      el.classList.remove('is-open')
      el.removeAttribute("aria-hidden")
    }
    this.button.classList.remove('is-closed')
    this.button.classList.remove('is-open')
    this.button.removeEventListener('click', this.onClick);
  }

  click(e) {
    // TODO: we need to do a prev default ONLY if it is larger than our MQ
    if(window.innerWidth < 800) {
      e.preventDefault();
    }
    console.log(window.innerWidth)


    if(this.button.tagName === 'A') {
      if(this.button.classList.contains('is-open')) {
        // send them on their way
        window.location = this.button.getAttribute('href')
      }
    }
    // toggle the button state
    this.toggleButton()

    for(let el of this.els) {
      this.toggle(el)
    }
  }

  toggleButton() {
    if(this.button.classList.contains('is-open')) {
      this.button.classList.remove(...['is-opening', 'is-open']);
      this.button.classList.add(...['is-closing', 'is-closed']);
      setTimeout(() => {
          this.button.classList.remove('is-closing');
      }, 600);
    } else {
      this.button.classList.remove(...['is-closing', 'is-closed']);
      this.button.classList.add(...['is-opening', 'is-open']);
      setTimeout(() => {
          this.button.classList.remove('is-opening');
      }, 600);
    }
  }
  toggle(el) {
    if(el.classList.contains('is-open')) {
        this.hide(el);
    } else {
        this.show(el);
    }
  }
  show(el) {
    el.classList.add('is-open');
    el.classList.remove(...['is-hidden', 'is-hiding']);

    this.ariaShow(el);
    el.classList.add('is-opening');
    setTimeout(() => {
        el.classList.remove('is-opening');
    }, 600);
  }
  hide(el) {
    el.classList.remove(...['is-open', 'is-opening']);
    el.classList.add(...['is-hidden', 'is-hiding']);
    this.ariaHidden(el);
    setTimeout(() => {
        el.classList.remove('is-hiding');
    }, 600);

  }
  ariaShow(el) {
    el.setAttribute("aria-hidden", false)
  }
  ariaHidden(el) {
    el.setAttribute("aria-hidden", true)
  }
}

export default Collapse;
