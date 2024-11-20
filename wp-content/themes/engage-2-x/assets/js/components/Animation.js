document.addEventListener("DOMContentLoaded", function() {
  const observerOptions = {
    root: null,  // Observes the viewport by default
    rootMargin: '0px 0px -20px 0px',  // Adjust the trigger point if needed
    threshold: 0 // Trigger when 95% of the element is visible
  }

  const fadeInAndUp = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        // Add the 'fade_in_and_up' class to all animating elements
        entry.target.classList.add('fade_in_and_up');

        // The delay class should already be applied based on DOM index
      }
    });
  }, observerOptions);

  // Select all elements with either 'fade-in-up-1' or 'fade-in-up-2' class
  const elementsToFadeInAndUp = document.querySelectorAll('.fade-in-up-1, .fade-in-up-2');
  elementsToFadeInAndUp.forEach(element => {
    fadeInAndUp.observe(element);  // Observe each element
  });
  
  // Other animations:
  
  const parallaxBarScale = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('parallax_bar_scale');
      }
    });
  }, observerOptions);

  const elementsToParallaxBarScale = document.querySelectorAll('.item-parallax-bar-scale');
  elementsToParallaxBarScale.forEach(element => {
    parallaxBarScale.observe(element);
  });
  
  const slideInFadeIn = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('slide_in_fade_in');
      }
    });
  }, observerOptions);

  const elementsToSlideInFadeIn = document.querySelectorAll('.item-slide-in-fade-in');
  elementsToSlideInFadeIn.forEach(element => {
    slideInFadeIn.observe(element);
  });

  const scaleUp = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('scale_up');
      }
    });
  }, observerOptions);

  const elementsToScaleUp = document.querySelectorAll('.item-scale-up');
  elementsToScaleUp.forEach(element => {
    scaleUp.observe(element);
  });
  
  // Select all grid items and apply staggered animations
  const gridItems = document.querySelectorAll('.item-animate');
  gridItems.forEach((item, index) => {
    // Add staggered delay class based on DOM index (index + 1)
    item.classList.add(`delay_${(index % 4) + 1}`); // Cycles through delay_1 to delay_4
    fadeInAndUp.observe(item);  // Observe each grid item
  });
});






/*
The bottom margin is set to -100px, meaning the visibility trigger happens 100px before the element actually enters the viewport. You can adjust this value to control how early (negative values) or late (positive values) the animation is triggered.

If you want the animation to trigger after the element has fully entered the viewport, you could use something like '0px 0px 100px 0px'.

Threshold:
This ensures the is-visible class is added as soon as any part of the element enters the viewport.

threshold in IntersectionObserver
The threshold value can range between 0 and 1, where:

0 threshold: The observer will trigger as soon as any part of the element is visible in the viewport, even if it's just a single pixel.
1 threshold: The observer will trigger only when the entire element is fully within the viewport (100% visible).
Values in between (like 0.5): The observer will trigger when 50% of the element is visible in the viewport.


*/
