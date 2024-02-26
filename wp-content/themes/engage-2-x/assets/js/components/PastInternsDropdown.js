const teamFilters = document.querySelector(".filter--team-menu");

// This handleclick deals with the execution of show and hide the past interns of a semester
function toggleSemester(arg) {
  const class_name_1 = "past-interns-title__" + arg;
  const class_name_2 = "past-interns-list__" + arg;

  const title = document.getElementsByClassName(class_name_1);
  const content = document.getElementsByClassName(class_name_2);

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

// this change the direction of the arrow of a semester of past interns
function changeArrowDirection(arg) {
  const class_name = "past-interns-title__" + arg;
  const title = document.getElementsByClassName(class_name);
  var x = title[0].getAttribute("aria-expanded");
  if (x == "true") {
    title[0].setAttribute("data-toggle-arrow", "\u25bc");
  } else {
    title[0].setAttribute("data-toggle-arrow", "\u25ba");
  }
}

// these values are to be manaully added or deleted to ensure the semester selected are on file
var semesters = [
  "2022-2023",
  "2021-2022",
  "2020-2021",
  "2019-2020",
  "2018-2019",
  "spring-2018",
  "alumni",
  "journalisim",
];

// In this forEach(), every iteration deals with one semester of past MEI interns
semesters.forEach(function (semester) {
  const class_name = "past-interns-title__" + semester;
  const title_element = document.getElementsByClassName(class_name);
  if (title_element.length > 0) {
    title_element[0].addEventListener(
      "click",
      function () {
        toggleSemester(semester);
        changeArrowDirection(semester);
      },
      false,
    );
  }
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
