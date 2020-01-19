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
    element.className = "module-graph";
    element.style.backgroundImage = `url(${this.props.html})`;
    element.style.backgroundRepeat = "no-repeat";
    element.style.backgroundSize = `${this.props.width}px ${
      this.props.height
    }px`;

    return element;
  }

  protected updateDomElement(element: HTMLElement): void {
    element.style.backgroundImage = `url(${this.props.html})`;
    element.style.backgroundRepeat = "no-repeat";
    element.style.backgroundSize = `${this.props.width}px ${
      this.props.height
    }px`;
  }
}
