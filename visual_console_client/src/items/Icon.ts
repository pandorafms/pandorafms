import { LinkedVisualConsoleProps, AnyObject } from "../lib/types";
import { linkedVCPropsDecoder } from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type IconProps = {
  type: ItemType.ICON;
  image: string;
  imageSrc: string; // URL?
} & ItemProps &
  LinkedVisualConsoleProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the icon props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function iconPropsDecoder(data: AnyObject): IconProps | never {
  if (typeof data.imageSrc !== "string" || data.imageSrc.length === 0) {
    throw new TypeError("invalid image src.");
  }

  if (typeof data.image !== "string" || data.image.length === 0) {
    throw new TypeError("invalid image.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.ICON,
    image: data.image,
    imageSrc: data.imageSrc,
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class Icon extends Item<IconProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "icon " + this.props.image;
    element.style.backgroundImage = `url(${this.props.imageSrc})`;
    element.style.backgroundRepeat = "no-repeat";
    element.style.backgroundSize = "contain";
    element.style.backgroundPosition = "center";

    return element;
  }

  /**
   * To update the content element.
   * @override Item.updateDomElement
   */
  protected updateDomElement(element: HTMLElement): void {
    element.style.backgroundImage = `url(${this.props.imageSrc})`;
  }
}
