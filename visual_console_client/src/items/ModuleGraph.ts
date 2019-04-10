import {
  LinkedVisualConsoleProps,
  UnknownObject,
  WithModuleProps
} from "../types";
import {
  linkedVCPropsDecoder,
  modulePropsDecoder,
  decodeBase64,
  stringIsEmpty
} from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type ModuleGraphProps = {
  type: ItemType.MODULE_GRAPH;
  html: string;
} & ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

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
  data: UnknownObject
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
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class ModuleGraph extends Item<ModuleGraphProps> {
  public createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.innerHTML = this.props.html;

    return element;
  }
}
