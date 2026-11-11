const teamFilters = document.querySelector(".filter--team-menu");

// Show or hide the past-interns list for a given slug (e.g. "alumni").
function toggleSemester(arg) {
  const class_name_1 = "past-interns-title__" + arg;
  const class_name_2 = "past-interns-list__" + arg;

  const title = document.getElementsByClassName(class_name_1);
  const content = document.getElementsByClassName(class_name_2);

  if (!title[0] || !content[0]) {
    return;
  }

  var x = title[0].getAttribute("aria-expanded");
  var y = content[0].getAttribute("aria-hidden");

  if (x == "true") {
    x = "false";
    y = "true";
    content[0].style.visibility = "hidden";
    content[0].style.marginTop = "0px";
    content[0].style.marginBottom = "0px";
    content[0].style.maxHeight = 0;
    content[0].style.overflow = "hidden";
  } else {
    x = "true";
    y = "false";
    content[0].style.visibility = "visible";
    content[0].style.marginTop = "20px";
    content[0].style.marginBottom = "20px";
    content[0].style.maxHeight = 100 + "%";
    content[0].style.overflow = "auto";
  }

  title[0].setAttribute("aria-expanded", x);
  content[0].setAttribute("aria-hidden", y);
}

// Flip the arrow on a past-interns title for the given slug.
function changeArrowDirection(arg) {
  const class_name = "past-interns-title__" + arg;
  const title = document.getElementsByClassName(class_name);
  if (!title[0]) {
    return;
  }
  var x = title[0].getAttribute("aria-expanded");
  if (x == "true") {
    title[0].setAttribute("data-toggle-arrow", "\u25bc");
  } else {
    title[0].setAttribute("data-toggle-arrow", "\u25ba");
  }
}

// Bind every past-interns title on the page; slug comes from past-interns-title__{slug}.
document.querySelectorAll(".past-interns-title").forEach(function (titleElement) {
  const slugClass = Array.from(titleElement.classList).find(function (className) {
    return className.indexOf("past-interns-title__") === 0;
  });
  if (!slugClass) {
    return;
  }
  const semester = slugClass.replace("past-interns-title__", "");

  titleElement.addEventListener(
    "click",
    function () {
      toggleSemester(semester);
      changeArrowDirection(semester);
    },
    false,
  );
  titleElement.addEventListener(
    "keydown",
    function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        toggleSemester(semester);
        changeArrowDirection(semester);
      }
    },
    false,
  );
});

// Modal pause video
jQuery(function () {
  jQuery("a[data-modal]").on("click", function () {
    jQuery(jQuery(this).data("modal")).modal();
    jQuery(".current, .close-modal").on("click", function (event) {
      jQuery("video").each(function (index) {
        jQuery(this).get(0).pause();
      });
    });
    jQuery(document).on("keyup", function (event) {
      if (event.key == "Escape") {
        jQuery("video").each(function (index) {
          jQuery(this).get(0).pause();
        });
      }
    });
    return false;
  });
});

if (teamFilters) {
  const boardItem = document.querySelector(".filter__item--board");
  const teamCategories = document.querySelector(".filters--team-menu");
  teamCategories.removeChild(boardItem);
  teamCategories.appendChild(boardItem);
}
