import { LinkedVisualConsoleProps, UnknownObject } from "../types";
import {
  linkedVCPropsDecoder,
  parseIntOr,
  notEmptyStringOr,
  stringIsEmpty,
  decodeBase64,
  parseBoolean
} from "../lib";
import Item, { ItemProps, itemBasePropsDecoder, ItemType } from "../Item";

export type GroupProps = {
  type: ItemType.GROUP_ITEM;
  groupId: number;
  imageSrc: string | null; // URL?
  statusImageSrc: string | null;
  showStatistics: boolean;
  html?: string | null;
} & ItemProps &
  LinkedVisualConsoleProps;

function extractHtml(data: UnknownObject): string | null {
  if (!stringIsEmpty(data.html)) return data.html;
  if (!stringIsEmpty(data.encodedHtml)) return decodeBase64(data.encodedHtml);
  return null;
}

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
  if (
    (typeof data.imageSrc !== "string" || data.imageSrc.length === 0) &&
    data.encodedHtml === null
  ) {
    throw new TypeError("invalid image src.");
  }
  if (parseIntOr(data.groupId, null) === null) {
    throw new TypeError("invalid group Id.");
  }

  const showStatistics = parseBoolean(data.showStatistics);
  const html = showStatistics ? extractHtml(data) : null;

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.GROUP_ITEM,
    groupId: parseInt(data.groupId),
    imageSrc: notEmptyStringOr(data.imageSrc, null),
    statusImageSrc: notEmptyStringOr(data.statusImageSrc, null),
    showStatistics,
    html,
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class Group extends Item<GroupProps> {
  protected createDomElement(): HTMLElement {
    if (this.props.showStatistics) {
      const div = document.createElement("div");
      div.className = "group";
      if (this.props.html != null) {
        div.innerHTML = this.props.html;
      }

      return div;
    } else {
      const img: HTMLImageElement = document.createElement("img");
      img.className = "group";
      if (this.props.statusImageSrc !== null) {
        img.src = this.props.statusImageSrc;
      }

      return img;
    }
  }
}
