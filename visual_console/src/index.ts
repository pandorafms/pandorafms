// import VisualConsole from "./VisualConsole";
import StaticGraphItem from "./items/StaticGraph";

// declare global {
//   interface Window {
//     VisualConsole: VisualConsole;
//   }
// }

// window.VisualConsole = VisualConsole;

const container = document.getElementById("visual-console-container");

if (container != null) {
  const item = new StaticGraphItem(container, {
    // Generic props.
    id: 1,
    type: 1,
    label: null,
    isLinkEnabled: false,
    isOnTop: false,
    parentId: null,
    aclGroupId: null,
    // Position props.
    x: 0,
    y: 0,
    // Size props.
    width: 50,
    height: 50,
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
  });
}
