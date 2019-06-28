import {
  WithModuleProps,
  LinkedVisualConsoleProps,
  AnyObject
} from "../lib/types";
import { modulePropsDecoder, linkedVCPropsDecoder } from "../lib";
import Item, { itemBasePropsDecoder, ItemType, ItemProps } from "../Item";

export type ColorCloudProps = {
  type: ItemType.COLOR_CLOUD;
  color: string;
  // TODO: Add the rest of the color cloud values?
} & ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the static graph props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function colorCloudPropsDecoder(
  data: AnyObject
): ColorCloudProps | never {
  // TODO: Validate the color.
  if (typeof data.color !== "string" || data.color.length === 0) {
    throw new TypeError("invalid color.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.COLOR_CLOUD,
    color: data.color,
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

const svgNS = "http://www.w3.org/2000/svg";

export default class ColorCloud extends Item<ColorCloudProps> {
  protected createDomElement(): HTMLElement {
    const container: HTMLDivElement = document.createElement("div");
    container.className = "color-cloud";

    // Add the SVG.
    container.append(this.createSvgElement());

    return container;
  }

  protected resizeElement(width: number): void {
    super.resizeElement(width, width);
  }

  public createSvgElement(): SVGSVGElement {
    const gradientId = `grad_${this.props.id}`;
    // SVG container.
    const svg = document.createElementNS(svgNS, "svg");
    // Auto resize SVG using the view box magic: https://css-tricks.com/scale-svg/
    svg.setAttribute("viewBox", "0 0 100 100");

    // Defs.
    const defs = document.createElementNS(svgNS, "defs");
    // Radial gradient.
    const radialGradient = document.createElementNS(svgNS, "radialGradient");
    radialGradient.setAttribute("id", gradientId);
    radialGradient.setAttribute("cx", "50%");
    radialGradient.setAttribute("cy", "50%");
    radialGradient.setAttribute("r", "50%");
    radialGradient.setAttribute("fx", "50%");
    radialGradient.setAttribute("fy", "50%");
    // Stops.
    const stop0 = document.createElementNS(svgNS, "stop");
    stop0.setAttribute("offset", "0%");
    stop0.setAttribute(
      "style",
      `stop-color:${this.props.color};stop-opacity:0.9`
    );
    const stop100 = document.createElementNS(svgNS, "stop");
    stop100.setAttribute("offset", "100%");
    stop100.setAttribute(
      "style",
      `stop-color:${this.props.color};stop-opacity:0`
    );
    // Circle.
    const circle = document.createElementNS(svgNS, "circle");
    circle.setAttribute("fill", `url(#${gradientId})`);
    circle.setAttribute("cx", "50%");
    circle.setAttribute("cy", "50%");
    circle.setAttribute("r", "50%");

    // Append elements.
    radialGradient.append(stop0, stop100);
    defs.append(radialGradient);
    svg.append(defs, circle);

    return svg;
  }
}
