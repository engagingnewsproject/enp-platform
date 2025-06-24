// Lightbox for all post images
// add flick to all post images
document.addEventListener("DOMContentLoaded", function () {
  // Find all images inside elements with the ".article" class
  var images = document.querySelectorAll(".article__content img");

  // Add the "flick" class to each found image
  images.forEach(function (image) {
    image.classList.add("flick");

    // Attach a click event handler to each image with the "flick" class
    image.addEventListener("click", openLightbox);
  });
});

// Create a lightbox container
var lightbox = document.createElement("div");
lightbox.className = "lightbox";
lightbox.id = "image-lightbox";

// Create a close button
var closeButton = document.createElement("span");
closeButton.className = "close-button";
closeButton.id = "close-lightbox";
closeButton.innerHTML = "&times;";

// Create an image element
var lightboxImage = document.createElement("img");
lightboxImage.alt = "Lightbox Image";
lightboxImage.id = "lightbox-image";

// Add the close button and image to the lightbox
lightbox.appendChild(closeButton);
lightbox.appendChild(lightboxImage);

// Add the lightbox to the document body
document.body.appendChild(lightbox);

// Function to open the lightbox
function openLightbox(event) {
  // Prevent the default click behavior (e.g., following links)
  event.preventDefault();

  // Set the image source for the lightbox
  lightboxImage.src = this.getAttribute("src");

  // Add the "lightbox-open" class to the lightbox
  lightbox.classList.add("lightbox-open");

  // Check if there is a figcaption element in the lightbox
  var existingFigcaption = lightbox.querySelector("figcaption");

  // Get the figcaption of the clicked image
  var clickedFigcaption = this.nextElementSibling;

  // If an existing figcaption is found, replace it with the clickedFigcaption
  if (existingFigcaption) {
    existingFigcaption.parentNode.removeChild(existingFigcaption);
  }

  // Clone and append the clickedFigcaption element to the lightbox
  if (clickedFigcaption && clickedFigcaption.tagName === "FIGCAPTION") {
    var clonedFigcaption = clickedFigcaption.cloneNode(true);
    clonedFigcaption.setAttribute("class", "lightbox-caption");
    lightbox.appendChild(clonedFigcaption);
  }

  // Add a click event listener to the lightbox background to close it
  lightbox.addEventListener("click", closeLightbox);
}

// Function to close the lightbox
function closeLightbox() {
  // Remove the "lightbox-open" class from the lightbox
  lightbox.classList.remove("lightbox-open");

  // Remove the click event listener to avoid multiple bindings
  lightbox.removeEventListener("click", closeLightbox);
}

// Function to handle the Escape key press
function handleEscapeKey(event) {
  if (event.key === "Escape") {
    closeLightbox();
  }
}

// Attach click event handler to the close button
closeButton.addEventListener("click", closeLightbox);

// Attach the keydown event listener to the document
document.addEventListener("keydown", handleEscapeKey);
