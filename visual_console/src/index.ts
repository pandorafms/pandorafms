/*
 * Useful resources.
 * http://es6-features.org/
 * http://exploringjs.com/es6
 * https://www.typescriptlang.org/
 */

import VisualConsole, {
  visualConsolePropsDecoder,
  VisualConsoleProps
} from "./VisualConsole";

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
    backgroundColor: "#000000",
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
    width: 100,
    height: 100,
    // Agent props.
    agentId: null,
    agentName: null,
    // Module props.
    moduleId: null,
    moduleName: null,
    // Custom props.
    imageSrc:
      "https://brutus.artica.lan:8081/uploads/-/system/project/avatar/1/1.png",
    showLastValueTooltip: "default"
  };

  const colorCloudRawProps = {
    // Generic props.
    id: 2,
    type: 20, // Static graph = 0
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

  try {
    const visualConsole = new VisualConsole(
      container,
      visualConsolePropsDecoder(rawProps),
      [staticGraphRawProps, colorCloudRawProps]
    );
    console.log(visualConsole);
  } catch (error) {
    console.log("ERROR", error.message);
  }
}
