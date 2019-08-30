import {
  WithModuleProps,
  LinkedVisualConsoleProps,
  AnyObject
} from "../lib/types";

import {
  modulePropsDecoder,
  linkedVCPropsDecoder,
  notEmptyStringOr
} from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type StaticGraphProps = {
  type: ItemType.STATIC_GRAPH;
  imageSrc: string; // URL?
  showLastValueTooltip: "default" | "enabled" | "disabled";
  statusImageSrc: string | null; // URL?
  lastValue: string | null;
} & ItemProps &
  (WithModuleProps | LinkedVisualConsoleProps);

/**
 * Extract a valid enum value from a raw unknown value.
 * @param showLastValueTooltip Raw value.
 */
const parseShowLastValueTooltip = (
  showLastValueTooltip: unknown
): StaticGraphProps["showLastValueTooltip"] => {
  switch (showLastValueTooltip) {
    case "default":
    case "enabled":
    case "disabled":
      return showLastValueTooltip;
    default:
      return "default";
  }
};

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the static graph props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function staticGraphPropsDecoder(
  data: AnyObject
): StaticGraphProps | never {
  if (typeof data.imageSrc !== "string" || data.imageSrc.length === 0) {
    throw new TypeError("invalid image src.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.STATIC_GRAPH,
    imageSrc: data.imageSrc,
    showLastValueTooltip: parseShowLastValueTooltip(data.showLastValueTooltip),
    statusImageSrc: notEmptyStringOr(data.statusImageSrc, null),
    lastValue: notEmptyStringOr(data.lastValue, null),
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class StaticGraph extends Item<StaticGraphProps> {
  protected createDomElement(): HTMLElement {
    const imgSrc = this.props.statusImageSrc || this.props.imageSrc;
    const element = document.createElement("div");
    element.className = "static-graph";
    element.style.background = `url(${imgSrc}) no-repeat`;
    element.style.backgroundSize = "contain";
    element.style.backgroundPosition = "center";

    // Show last value in a tooltip.
    if (
      this.props.lastValue !== null &&
      this.props.showLastValueTooltip !== "disabled"
    ) {
      element.className = "static-graph image forced_title";
      element.setAttribute("data-use_title_for_force_title", "1");
      element.setAttribute("data-title", this.props.lastValue);
    }

    return element;
  }
}
