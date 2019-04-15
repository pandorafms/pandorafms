import { UnknownObject } from "../types";
import {
  stringIsEmpty,
  notEmptyStringOr,
  decodeBase64,
  parseIntOr
} from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type ServiceProps = {
  type: ItemType.SERVICE;
  serviceId: number;
  imageSrc: string | null;
  statusImageSrc: string | null;
  encodedTitle: string | null;
} & ItemProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the service props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function servicePropsDecoder(data: UnknownObject): ServiceProps | never {
  if (data.imageSrc !== null) {
    if (
      typeof data.statusImageSrc !== "string" ||
      data.imageSrc.statusImageSrc === 0
    ) {
      throw new TypeError("invalid status image src.");
    }
  } else {
    if (stringIsEmpty(data.encodedTitle)) {
      throw new TypeError("missing encode tittle content.");
    }
  }

  if (parseIntOr(data.serviceId, null) === null) {
    throw new TypeError("invalid service id.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.SERVICE,
    serviceId: data.serviceId,
    imageSrc: notEmptyStringOr(data.imageSrc, null),
    statusImageSrc: notEmptyStringOr(data.statusImageSrc, null),
    encodedTitle: notEmptyStringOr(data.encodedTitle, null)
  };
}

export default class Service extends Item<ServiceProps> {
  public createDomElement(): HTMLElement {
    const img: HTMLImageElement = document.createElement("img");
    if (this.props.statusImageSrc !== null) {
      img.className = "icon";
      img.src = this.props.statusImageSrc;
    } else {
      if (this.props.encodedTitle !== null) {
        const element = document.createElement("div");
        element.innerHTML = decodeBase64(this.props.encodedTitle);
        return element;
      }
    }
    return img;
  }
}
