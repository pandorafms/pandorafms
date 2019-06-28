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
     * We need to override the constructor cause we need to obtain the
     * box size and position from the start and finish points of the line.
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
   * Classic and protected version of the setter of the `meta` property.
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

    const {
      x, // Box x
      y, // Box y
      width, // Box width
      height, // Box height
      lineWidth, // Line thickness
      startPosition, // Line start position
      endPosition, // Line end position
      color // Line color
    } = this.props;

    const startIsLeft = startPosition.x - endPosition.x <= 0;
    const startIsTop = startPosition.y - endPosition.y <= 0;

    const x1 = startPosition.x - x + lineWidth / 2;
    const y1 = startPosition.y - y + lineWidth / 2;
    const x2 = endPosition.x - x + lineWidth / 2;
    const y2 = endPosition.y - y + lineWidth / 2;

    const svgNS = "http://www.w3.org/2000/svg";
    // SVG container.
    const svg = document.createElementNS(svgNS, "svg");
    // Set SVG size.
    svg.setAttribute("width", `${width + lineWidth}`);
    svg.setAttribute("height", `${height + lineWidth}`);
    const line = document.createElementNS(svgNS, "line");
    line.setAttribute("x1", `${x1}`);
    line.setAttribute("y1", `${y1}`);
    line.setAttribute("x2", `${x2}`);
    line.setAttribute("y2", `${y2}`);
    line.setAttribute("stroke", color || "black");
    line.setAttribute("stroke-width", `${lineWidth}`);

    svg.append(line);
    element.append(svg);
    console.log(this.moveMode);
    if (this.moveMode) {
      const startC = document.createElement("div");
      startC.style.width = "16px";
      startC.style.height = "16px";
      startC.style.borderRadius = "50%";
      startC.style.backgroundColor = "white";
      startC.style.position = "absolute";
      startC.style.left = startIsLeft ? "-8px" : `${width + lineWidth - 8}px`;
      startC.style.top = startIsTop ? "-8px" : `${height + lineWidth - 8}px`;

      const endC = document.createElement("div");
      endC.style.width = "16px";
      endC.style.height = "16px";
      endC.style.borderRadius = "50%";
      endC.style.backgroundColor = "white";
      endC.style.position = "absolute";
      endC.style.left = startIsLeft ? `${width + lineWidth - 8}px` : "-8px";
      endC.style.top = startIsTop ? `${height + lineWidth - 8}px` : "-8px";

      element.append(startC);
      element.append(endC);
    }

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
