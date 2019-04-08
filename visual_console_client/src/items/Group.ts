import { LinkedVisualConsoleProps, UnknownObject } from "../types";
import { linkedVCPropsDecoder, parseIntOr } from "../lib";
import Item, { ItemProps, itemBasePropsDecoder, ItemType } from "../Item";

export type GroupProps = {
  type: ItemType.GROUP_ITEM;
  imageSrc: string; // URL?
  groupId: number;
} & ItemProps &
  LinkedVisualConsoleProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the group props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function groupPropsDecoder(data: UnknownObject): GroupProps | never {
  if (typeof data.imageSrc !== "string" || data.imageSrc.length === 0) {
    throw new TypeError("invalid image src.");
  }
  if (parseIntOr(data.groupId, null) === null) {
    throw new TypeError("invalid group Id.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.GROUP_ITEM,
    imageSrc: data.imageSrc,
    groupId: parseInt(data.groupId),
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class Group extends Item<GroupProps> {
  public createDomElement(): HTMLElement {
    const img: HTMLImageElement = document.createElement("img");
    img.className = "group";
    img.src = this.props.imageSrc;

    return img;
  }
}
