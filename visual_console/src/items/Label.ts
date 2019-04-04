import { LinkedVisualConsoleProps, UnknownObject } from "../types";
import { linkedVCPropsDecoder } from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type LabelProps = {
  type: ItemType.LABEL;
} & ItemProps &
  LinkedVisualConsoleProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the label props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function labelPropsDecoder(data: UnknownObject): LabelProps | never {
  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.LABEL,
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class Label extends Item<LabelProps> {
  public createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "label";
    // Size to 0, as the item content is managed using the label.
    element.style.width = "0";
    element.style.height = "0";
    // The content of this item is already shown into the label container.
    // element.innerHTML = this.props.label || "";

    return element;
  }

  /**
   * @override Item.resize
   * To resize the item.
   */
  public resizeElement(): void {
    // Size to 0, as the item content is managed using the label.
    this.childElementRef.style.width = `0`;
    this.childElementRef.style.height = `0`;
  }
}
