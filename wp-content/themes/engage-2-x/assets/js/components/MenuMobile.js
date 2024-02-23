import debounce from "lodash.debounce";
import Collapse from "./Collapse";

var mainNav = document.getElementById("main-nav");
var secondaryNav = document.getElementById("secondary-nav");
var menuToggle = document.getElementById("menu-toggle");
var filters = document.getElementsByClassName("filters");

var collapsibles = [];

if (mainNav) {
  collapsibles.push({
    id: "menu",
    breakpoint: { min: 0, max: 800 },
    button: menuToggle,
    els: [mainNav, secondaryNav],
    collapsible: null,
  });
}

// Just need to set up collapsibles for filters then this should work
if (filters.length > 0) {
  let filterItems, filterParent, filterSublist;

  for (let filter of filters) {
    filterItems = filter.getElementsByClassName("filter__item--top-item");
    console.log(filterItems);
    for (let filterItem of filterItems) {
      // the .filter__link--parent
      filterParent = filterItem.getElementsByClassName(
        "filter__link--parent",
      )[0];
      console.log(filterParent);
      filterSublist = filterItem.getElementsByClassName("filter__sublist")[0];
      collapsibles.push({
        id: "filter",
        breakpoint: { min: 0, max: 800 },
        button: filterParent,
        els: [filterSublist],
        collapsible: null,
      });
    }
  }
}

function addOrDestroyMenu() {
  const w = window.innerWidth;
  for (let item in collapsibles) {
    if (
      collapsibles[item].breakpoint.min < w &&
      w < collapsibles[item].breakpoint.max &&
      collapsibles[item].collapsible === null
    ) {
      collapsibles[item].collapsible = new Collapse(
        collapsibles[item].button,
        collapsibles[item].els,
      );
    } else if (
      (collapsibles[item].breakpoint.min > w ||
        w > collapsibles[item].breakpoint.max) &&
      collapsibles[item].collapsible !== null
    ) {
      collapsibles[item].collapsible.destroy();
      collapsibles[item].collapsible = null;
    }
  }
}

// set-up our collapsing things, like the main menus
addOrDestroyMenu();

window.addEventListener(
  "resize",
  debounce(function () {
    addOrDestroyMenu();
  }, 250),
);
