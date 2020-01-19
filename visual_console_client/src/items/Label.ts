import { LinkedVisualConsoleProps, AnyObject } from "../lib/types";
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
export function labelPropsDecoder(data: AnyObject): LabelProps | never {
  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.LABEL,
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class Label extends Item<LabelProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "label";
    element.innerHTML = this.getLabelWithMacrosReplaced();

    return element;
  }

  /**
   * @override Item.createLabelDomElement
   * Create a new label for the visual console item.
   * @return Item label.
   */
  public createLabelDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "visual-console-item-label";
    // Always return an empty label.
    return element;
  }
}
