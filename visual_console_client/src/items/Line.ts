import { AnyObject, Position, Size, ItemMeta } from "../lib/types";
import { parseIntOr, notEmptyStringOr } from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

interface LineProps extends ItemProps {
  // Overrided properties.
  readonly type: ItemType.LINE_ITEM;
  label: null;
  isLinkEnabled: false;
  parentId: null;
  aclGroupId: null;
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
export function linePropsDecoder(data: AnyObject): LineProps | never {
  const props: LineProps = {
    ...itemBasePropsDecoder({ ...data, width: 1, height: 1 }), // Object spread. It will merge the properties of the two objects.
    type: ItemType.LINE_ITEM,
    label: null,
    isLinkEnabled: false,
    parentId: null,
    aclGroupId: null,
    // Initialize Position & Size.
    x: 0,
    y: 0,
    width: 0,
    height: 0,
    // Custom properties.
    startPosition: {
      x: parseIntOr(data.startX, 0),
      y: parseIntOr(data.startY, 0)
    },
    endPosition: {
      x: parseIntOr(data.endX, 0),
      y: parseIntOr(data.endY, 0)
    },
    lineWidth: parseIntOr(data.lineWidth || data.borderWidth, 1),
    color: notEmptyStringOr(data.borderColor || data.color, null)
  };

  /*
   * We need to enhance the props with the extracted size and position
   * of the box cause there are missing at the props update. A better
   * solution would be overriding the props setter to do it there, but
   * the language doesn't allow it while targetting ES5.
   * TODO: We need to figure out a more consistent solution.
   */

  return {
    ...props,
    // Enhance the props extracting the box size and position.
    // eslint-disable-next-line @typescript-eslint/no-use-before-define
    ...Line.extractBoxSizeAndPosition(props)
  };
}

export default class Line extends Item<LineProps> {
  /**
   * @override
   */
  public constructor(props: LineProps, meta: ItemMeta) {
    /*
     * We need to override the constructor cause we need to obtain
     * the
     * box size and position from the start and finish points
     * of the line.
     */
    super(
      {
        ...props,
        ...Line.extractBoxSizeAndPosition(props)
      },
      {
        ...meta,
        editMode: false
      }
    );
  }

  /**
   * Clasic and protected version of the setter of the `meta` property.
   * Useful to override it from children classes.
   * @param newProps
   * @override Item.setMeta
   */
  public setMeta(newMetadata: ItemMeta) {
    super.setMeta({
      ...newMetadata,
      editMode: false
    });
  }

  /**
   * @override
   * To create the item's DOM representation.
   * @return Item.
   */
  protected createDomElement(): HTMLElement {
    const element: HTMLDivElement = document.createElement("div");
    element.className = "line";

    const svgNS = "http://www.w3.org/2000/svg";
    // SVG container.
    const svg = document.createElementNS(svgNS, "svg");
    // Set SVG size.
    svg.setAttribute(
      "width",
      (this.props.width + this.props.lineWidth).toString()
    );
    svg.setAttribute(
      "height",
      (this.props.height + this.props.lineWidth).toString()
    );
    const line = document.createElementNS(svgNS, "line");
    line.setAttribute(
      "x1",
      `${this.props.startPosition.x - this.props.x + this.props.lineWidth / 2}`
    );
    line.setAttribute(
      "y1",
      `${this.props.startPosition.y - this.props.y + this.props.lineWidth / 2}`
    );
    line.setAttribute(
      "x2",
      `${this.props.endPosition.x - this.props.x + this.props.lineWidth / 2}`
    );
    line.setAttribute(
      "y2",
      `${this.props.endPosition.y - this.props.y + this.props.lineWidth / 2}`
    );
    line.setAttribute("stroke", this.props.color || "black");
    line.setAttribute("stroke-width", this.props.lineWidth.toString());

    svg.append(line);
    element.append(svg);

    return element;
  }

  /**
   * Extract the size and position of the box from
   * the start and the finish of the line.
   * @param props Item properties.
   */
  public static extractBoxSizeAndPosition(props: LineProps): Size & Position {
    return {
      width: Math.abs(props.startPosition.x - props.endPosition.x),
      height: Math.abs(props.startPosition.y - props.endPosition.y),
      x: Math.min(props.startPosition.x, props.endPosition.x),
      y: Math.min(props.startPosition.y, props.endPosition.y)
    };
  }
}
