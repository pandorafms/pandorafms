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
  t
} from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type DonutGraphProps = {
  type: ItemType.DONUT_GRAPH;
  html: string;
  legendBackgroundColor: string;
} & ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the donut graph props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function donutGraphPropsDecoder(
  data: AnyObject
): DonutGraphProps | never {
  if (stringIsEmpty(data.html) && stringIsEmpty(data.encodedHtml)) {
    throw new TypeError("missing html content.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.DONUT_GRAPH,
    html: !stringIsEmpty(data.html)
      ? data.html
      : decodeBase64(data.encodedHtml),
    legendBackgroundColor: stringIsEmpty(data.legendBackgroundColor)
      ? "#000000"
      : data.legendBackgroundColor,
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class DonutGraph extends Item<DonutGraphProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "donut-graph";
    element.innerHTML = this.props.html;

    if (
      this.props.agentDisabled === true ||
      this.props.moduleDisabled === true
    ) {
      element.style.opacity = "0.2";
    }

    // Hack to execute the JS after the HTML is added to the DOM.
    const scripts = element.getElementsByTagName("script");
    for (let i = 0; i < scripts.length; i++) {
      setTimeout(() => {
        if (scripts[i].src.length === 0) eval(scripts[i].innerHTML.trim());
      }, 0);
    }

    return element;
  }

  protected updateDomElement(element: HTMLElement): void {
    element.innerHTML = this.props.html;

    // Hack to execute the JS after the HTML is added to the DOM.
    const aux = document.createElement("div");
    aux.innerHTML = this.props.html;
    const scripts = aux.getElementsByTagName("script");
    for (let i = 0; i < scripts.length; i++) {
      if (scripts[i].src.length === 0) {
        eval(scripts[i].innerHTML.trim());
      }
    }
  }
}
