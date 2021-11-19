import {
  LinkedVisualConsoleProps,
  AnyObject,
  WithModuleProps
} from "../lib/types";
import {
  linkedVCPropsDecoder,
  modulePropsDecoder,
  decodeBase64,
  stringIsEmpty,
  parseIntOr
} from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type ModuleGraphProps = {
  type: ItemType.MODULE_GRAPH;
  html: string;
  backgroundType: "white" | "black" | "transparent";
  graphType: "line" | "area";
  period: number | null;
  customGraphId: number | null;
} & ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Extract a valid enum value from a raw unknown value.
 * @param backgroundType Raw value.
 */
const parseBackgroundType = (
  backgroundType: unknown
): ModuleGraphProps["backgroundType"] => {
  switch (backgroundType) {
    case "white":
    case "black":
    case "transparent":
      return backgroundType;
    default:
      return "transparent";
  }
};

/**
 * Extract a valid enum value from a raw unknown value.
 * @param graphType Raw value.
 */
const parseGraphType = (graphType: unknown): ModuleGraphProps["graphType"] => {
  switch (graphType) {
    case "line":
    case "area":
      return graphType;
    default:
      return "line";
  }
};

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the module graph props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function moduleGraphPropsDecoder(
  data: AnyObject
): ModuleGraphProps | never {
  if (stringIsEmpty(data.html) && stringIsEmpty(data.encodedHtml)) {
    throw new TypeError("missing html content.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.MODULE_GRAPH,
    html: !stringIsEmpty(data.html)
      ? data.html
      : decodeBase64(data.encodedHtml),
    backgroundType: parseBackgroundType(data.backgroundType),
    period: parseIntOr(data.period, null),
    graphType: parseGraphType(data.graphType),
    customGraphId: parseIntOr(data.customGraphId, null),
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class ModuleGraph extends Item<ModuleGraphProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");

    element.innerHTML = this.props.html;
    element.className = "module-graph";
    if (
      this.props.agentDisabled === true ||
      this.props.moduleDisabled === true
    ) {
      element.style.opacity = "0.2";
    }

    // Remove the overview graph.
    const legendP = element.getElementsByTagName("p");
    for (let i = 0; i < legendP.length; i++) {
      legendP[i].style.margin = "0px";
    }

    // Remove the overview graph.
    const overviewGraphs = element.getElementsByClassName("overview_graph");
    for (let i = 0; i < overviewGraphs.length; i++) {
      overviewGraphs[i].remove();
    }

    // Hack to execute the JS after the HTML is added to the DOM.
    const scripts = element.getElementsByTagName("script");
    for (let i = 0; i < scripts.length; i++) {
      if (scripts[i].src.length === 0) {
        setTimeout(() => {
          try {
            eval(scripts[i].innerHTML.trim());
          } catch (ignored) {} // eslint-disable-line no-empty
        }, 0);
      }
    }

    return element;
  }

  protected updateDomElement(element: HTMLElement): void {
    element.innerHTML = this.props.html;

    // Remove the overview graph.
    const legendP = element.getElementsByTagName("p");
    for (let i = 0; i < legendP.length; i++) {
      legendP[i].style.margin = "0px";
    }

    // Remove the overview graph.
    const overviewGraphs = element.getElementsByClassName("overview_graph");
    for (let i = 0; i < overviewGraphs.length; i++) {
      overviewGraphs[i].remove();
    }

    // Hack to execute the JS after the HTML is added to the DOM.
    const scripts = element.getElementsByTagName("script");
    for (let i = 0; i < scripts.length; i++) {
      if (scripts[i].src.length === 0) {
        eval(scripts[i].innerHTML.trim());
      }
    }
  }
}
