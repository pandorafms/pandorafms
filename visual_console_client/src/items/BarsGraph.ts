import { AnyObject, WithModuleProps } from "../lib/types";
import { modulePropsDecoder, decodeBase64, stringIsEmpty, t } from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type BarsGraphProps = {
  type: ItemType.BARS_GRAPH;
  html: string;
  backgroundColor: "white" | "black" | "transparent";
  typeGraph: "horizontal" | "vertical";
  gridColor: string;
} & ItemProps &
  WithModuleProps;

/**
 * Extract a valid enum value from a raw unknown value.
 * @param BarsGraphProps Raw value.
 */
const parseBarsGraphProps = (
  backgroundColor: unknown
): BarsGraphProps["backgroundColor"] => {
  switch (backgroundColor) {
    case "white":
    case "black":
    case "transparent":
      return backgroundColor;
    default:
      return "transparent";
  }
};

/**
 * Extract a valid enum value from a raw unknown value.
 * @param typeGraph Raw value.
 */
const parseTypeGraph = (typeGraph: unknown): BarsGraphProps["typeGraph"] => {
  switch (typeGraph) {
    case "horizontal":
    case "vertical":
      return typeGraph;
    default:
      return "vertical";
  }
};

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the bars graph props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function barsGraphPropsDecoder(data: AnyObject): BarsGraphProps | never {
  if (stringIsEmpty(data.html) && stringIsEmpty(data.encodedHtml)) {
    throw new TypeError("missing html content.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.BARS_GRAPH,
    html: !stringIsEmpty(data.html)
      ? data.html
      : decodeBase64(data.encodedHtml),
    backgroundColor: parseBarsGraphProps(data.backgroundColor),
    typeGraph: parseTypeGraph(data.typeGraph),
    gridColor: stringIsEmpty(data.gridColor) ? "#000000" : data.gridColor,
    ...modulePropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class BarsGraph extends Item<BarsGraphProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "bars-graph";
    element.style.backgroundImage = `url(${this.props.html})`;
    element.style.backgroundRepeat = "no-repeat";
    element.style.backgroundSize = `${this.props.width}px ${this.props.height}px`;

    if (
      this.props.agentDisabled === true ||
      this.props.moduleDisabled === true
    ) {
      element.style.opacity = "0.2";
    }

    return element;
  }

  protected updateDomElement(element: HTMLElement): void {
    element.style.backgroundImage = `url(${this.props.html})`;
    element.style.backgroundRepeat = "no-repeat";
    element.style.backgroundSize = `${this.props.width}px ${this.props.height}px`;

    if (
      this.props.agentDisabled === true ||
      this.props.moduleDisabled === true
    ) {
      element.style.opacity = "0.2";
    }
  }
}
