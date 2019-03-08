import { UnknownObject, Position } from "../types";
import { parseIntOr, notEmptyStringOr } from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

interface LineProps extends ItemProps {
  // Overrided properties.
  readonly type: ItemType.LINE_ITEM;
  label: null;
  isLinkEnabled: false;
  parentId: null;
  aclGroupId: null;
  // Clear Position & Size.
  x: 0;
  y: 0;
  width: 0;
  height: 0;
  // Custom properties.
  startPosition: Position;
  endPosition: Position;
  lineWidth: number;
  color: string | null;
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
export function linePropsDecoder(data: UnknownObject): LineProps | never {
  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.LINE_ITEM,
    label: null,
    isLinkEnabled: false,
    parentId: null,
    aclGroupId: null,
    // Clear Position & Size.
    x: 0,
    y: 0,
    width: 0,
    height: 0,
    // Custom properties.
    startPosition: {
      x: 0,
      y: 0
    },
    endPosition: {
      x: 0,
      y: 0
    },
    lineWidth: parseIntOr(data.lineWidth, 0),
    color: notEmptyStringOr(data.color, null)
  };
}

export default class Line extends Item<LineProps> {
  public createDomElement(): HTMLElement {
    const line: HTMLDivElement = document.createElement("div");
    line.className = "line";

    return line;
  }
}
