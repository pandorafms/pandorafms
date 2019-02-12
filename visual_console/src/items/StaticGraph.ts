import {
  WithModuleProps,
  LinkedVisualConsoleProps,
  UnknownObject
} from "../types";

import { modulePropsDecoder } from "../lib";

import VisualConsoleItem, {
  VisualConsoleItemProps,
  itemPropsDecoder
} from "../VisualConsoleItem";

export type StaticGraphProps = {
  imageSrc: string; // URL?
  showLastValueTooltip: "default" | "enabled" | "disabled";
} & VisualConsoleItemProps &
  (WithModuleProps | LinkedVisualConsoleProps);

/**
 * Extract a valid enum value from a raw unknown value.
 * @param showLastValueTooltip Raw value.
 */
const parseShowLastValueTooltip = (showLastValueTooltip: any) => {
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
  data: UnknownObject
): StaticGraphProps | never {
  if (typeof data.imageSrc !== "string" || data.imageSrc.length === 0) {
    throw new TypeError("invalid image src.");
  }

  return {
    imageSrc: data.imageSrc,
    showLastValueTooltip: parseShowLastValueTooltip(data.showLastValueTooltip),
    ...itemPropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...modulePropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class StaticGraph extends VisualConsoleItem<StaticGraphProps> {
  createDomElement(): HTMLElement {
    const img: HTMLImageElement = document.createElement("img");
    img.className = "static-graph";
    img.src = this.props.imageSrc;

    return img;
  }
}
