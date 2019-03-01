/*
 * Useful resources.
 * http://es6-features.org/
 * http://exploringjs.com/es6
 * https://www.typescriptlang.org/
 */

import VisualConsole, { visualConsolePropsDecoder } from "./VisualConsole";

// declare global {
//   interface Window {
//     VisualConsole: VisualConsole;
//   }
// }

// window.VisualConsole = VisualConsole;

const container = document.getElementById("visual-console-container");

if (container != null) {
  const rawProps = {
    id: 1,
    groupId: 0,
    name: "Test Visual Console",
    width: 800,
    height: 300,
    backgroundURL: null,
    backgroundColor: "rgb(86, 86, 86)",
    isFavorite: false
  };

  const staticGraphRawProps = {
    // Generic props.
    id: 1,
    type: 0, // Static graph = 0
    label: null,
    isLinkEnabled: false,
    isOnTop: false,
    parentId: null,
    aclGroupId: null,
    // Position props.
    x: 100,
    y: 50,
    // Size props.
    width: 70,
    height: 70,
    // Agent props.
    agentId: null,
    agentName: null,
    // Module props.
    moduleId: null,
    moduleName: null,
    // Custom props.
    imageSrc:
      "http://localhost/pandora_console/images/console/icons/bridge_ok.png",
    showLastValueTooltip: "default"
  };

  const colorCloudRawProps = {
    // Generic props.
    id: 2,
    type: 20, // Color cloud = 20
    label: null,
    labelText: "CLOUD",
    isLinkEnabled: false,
    isOnTop: false,
    parentId: null,
    aclGroupId: null,
    // Position props.
    x: 300,
    y: 50,
    // Size props.
    width: 150,
    height: 150,
    // Agent props.
    agentId: null,
    agentName: null,
    // Module props.
    moduleId: null,
    moduleName: null,
    // Custom props.
    color: "rgb(100, 50, 245)"
  };

  const digitalClockRawProps = {
    // Generic props.
    id: 3,
    type: 19, // Clock = 19
    label: null,
    isLinkEnabled: false,
    isOnTop: false,
    parentId: null,
    aclGroupId: null,
    // Position props.
    x: 60,
    y: 150,
    // Size props.
    width: 300,
    height: 150,
    // Custom props.
    clockType: "digital",
    clockFormat: "datetime",
    clockTimezone: "Madrid",
    clockTimezoneOffset: 60,
    showClockTimezone: true,
    color: "white"
  };

  const digitalClockRawProps2 = {
    // Generic props.
    id: 4,
    type: 19, // Clock = 19
    label: null,
    isLinkEnabled: false,
    isOnTop: false,
    parentId: null,
    aclGroupId: null,
    // Position props.
    x: 10,
    y: 250,
    // Size props.
    width: 100,
    height: 50,
    // Custom props.
    clockType: "digital",
    clockFormat: "datetime",
    clockTimezone: "Madrid",
    clockTimezoneOffset: 60,
    showClockTimezone: true,
    color: "#82B92E"
  };

  const analogicClockRawProps = {
    // Generic props.
    id: 5,
    type: 19, // Clock = 19
    label: null,
    isLinkEnabled: false,
    isOnTop: false,
    parentId: null,
    aclGroupId: null,
    // Position props.
    x: 500,
    y: 50,
    // Size props.
    width: 200,
    height: 200,
    // Custom props.
    clockType: "analogic",
    clockFormat: "datetime",
    clockTimezone: "Copenhagen",
    clockTimezoneOffset: 60,
    showClockTimezone: true
  };

  try {
    const visualConsole = new VisualConsole(
      container,
      visualConsolePropsDecoder(rawProps),
      [
        staticGraphRawProps,
        colorCloudRawProps,
        digitalClockRawProps,
        digitalClockRawProps2,
        analogicClockRawProps
      ]
    );
    console.log(visualConsole);
  } catch (error) {
    console.log("ERROR", error.message);
  }
}
