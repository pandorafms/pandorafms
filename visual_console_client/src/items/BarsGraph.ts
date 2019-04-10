import { UnknownObject, WithModuleProps } from "../types";
import { modulePropsDecoder, decodeBase64, stringIsEmpty } from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type BarsGraphProps = {
  type: ItemType.BARS_GRAPH;
  html: string;
} & ItemProps &
  WithModuleProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the bars graph props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function barsGraphPropsDecoder(
  data: UnknownObject
): BarsGraphProps | never {
  if (stringIsEmpty(data.html) && stringIsEmpty(data.encodedHtml)) {
    throw new TypeError("missing html content.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.BARS_GRAPH,
    html: !stringIsEmpty(data.html)
      ? data.html
      : decodeBase64(data.encodedHtml),
    ...modulePropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class BarsGraph extends Item<BarsGraphProps> {
  public createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.innerHTML = this.props.html;

    return element;
  }
}
