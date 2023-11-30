/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.twig",
    "./assets/js/app.js",
    "./assets/js/homepage.js",
    "./assets/js/Orbit.js",
  ],
  theme: {
    colors: {
      orange: "#bf5700",
      tealLight: "#02B2BF",
    },
    extend: {
      fontSize: {
        xs: ".75rem", // Extra Small
        sm: ".875rem", // Small
        base: "1rem", // Default/Base
        lg: "1.125rem", // Large
      },
      backgroundImage: {
        annualReportDotsTopRight:
          "url('/wp-content/themes/engage/assets/img/header-top-right-graphic.svg')",
        annualReportDotsBottomLeft:
          "url('/wp-content/themes/engage/assets/img/dots-cut-header.svg')",
      },
      teal: {
        DEFAULT: "#00a9b7",
        light: "#02B2BF",
        faded: "#e9f3f0",
      },
      verticalColors: {
        journalism: "#00a9b7",
        "journalism-light": "#e9f3f0",
        "journalism-faded": "#e9f3f0",
        "science-communication": "#cca562",
        "media-ethics": "#579d42",
        "social-platforms": "#a6cd57",
        propaganda: "#f8971f",
        "center-leadership": "#ffd600",
      },
      keyframes: {
        slideIn: {
          "0%": {
            transform: "translate3d(0, 40px, 0)",
            opacity: "0.5",
          },
          "100%": {
            transform: "translate3d(0, 0, 0)",
            opacity: "1",
          },
        },
      },
      animation: {
        "slide-in": "slideIn 0.6666666667s ease-out",
      },
      backgroundColor: {
        // Generate utility classes for background colors
        ...(theme) => theme("verticalColors"),
      },
      textColor: {
        // Generate utility classes for text colors
        ...(theme) => theme("verticalColors"),
      },
    },
  },
  plugins: [],
};

