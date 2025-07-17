import { startStimulusApp } from "@symfony/stimulus-bridge";
import { registerReactControllerComponents } from "@symfony/ux-react";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap";

/*
 * Welcome to your app's main JavaScript file!
 * This file will be included onto the page via Webpack Encore.
 */
import "./styles/app.css";

console.log(
  "This log comes from assets/app.js - welcome to Webpack Encore! 🎉"
);

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(
  require.context(
    "@symfony/stimulus-bridge/lazy-controller-loader!./controllers",
    true,
    /\.(j|t)sx?$/
  )
);

// Register React components
registerReactControllerComponents(
  require.context("./react/controllers", true, /\.(j|t)sx?$/)
);
