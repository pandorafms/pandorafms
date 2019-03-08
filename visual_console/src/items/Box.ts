import { UnknownObject } from "../types";
import { parseIntOr, notEmptyStringOr } from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

interface BoxProps extends ItemProps {
  // Overrided properties.
  readonly type: ItemType.BOX_ITEM;
  label: null;
  isLinkEnabled: false;
  parentId: null;
  aclGroupId: null;
  // Custom properties.
  borderWidth: number;
  borderColor: string | null;
  fillColor: string | null;
}

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the item props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function boxPropsDecoder(data: UnknownObject): BoxProps | never {
  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.BOX_ITEM,
    label: null,
    isLinkEnabled: false,
    parentId: null,
    aclGroupId: null,
    // Custom properties.
    borderWidth: parseIntOr(data.borderWidth, 0),
    borderColor: notEmptyStringOr(data.borderColor, null),
    fillColor: notEmptyStringOr(data.fillColor, null)
  };
}

export default class Box extends Item<BoxProps> {
  public createDomElement(): HTMLElement {
    const box: HTMLDivElement = document.createElement("div");
    box.className = "box";
    // To prevent this item to expand beyond its parent.
    box.style.boxSizing = "border-box";

    if (this.props.fillColor) {
      box.style.backgroundColor = this.props.fillColor;
    }

    // Border.
    if (this.props.borderWidth > 0) {
      box.style.borderStyle = "solid";
      box.style.borderWidth = `${this.props.borderWidth}px`;

      if (this.props.borderColor) {
        box.style.borderColor = this.props.borderColor;
      }
    }

    return box;
  }
}
