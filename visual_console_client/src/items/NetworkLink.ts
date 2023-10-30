import { AnyObject, Position, ItemMeta } from "../lib/types";
import { debounce, notEmptyStringOr, parseIntOr } from "../lib";
import { ItemType } from "../Item";
import Line, { LineProps, linePropsDecoder } from "./Line";

const svgNS = "http://www.w3.org/2000/svg";

export interface NetworkLinkProps extends LineProps {
  // Overrided properties.
  type: number;
  labelStart: string;
  labelEnd: string;
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
export function networkLinkPropsDecoder(
  data: AnyObject
): NetworkLinkProps | never {
  return {
    ...linePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.NETWORK_LINK,
    viewportOffsetX: 0,
    viewportOffsetY: 0,
    labelEnd: notEmptyStringOr(data.labelEnd, ""),
    labelEndWidth: parseIntOr(data.labelEndWidth, 0),
    labelEndHeight: parseIntOr(data.labelEndHeight, 0),
    labelStart: notEmptyStringOr(data.labelStart, ""),
    labelStartWidth: parseIntOr(data.labelStartWidth, 0),
    labelStartHeight: parseIntOr(data.labelStartHeight, 0)
  };
}

export default class NetworkLink extends Line {
  /**
   * @override
   */
  public constructor(props: NetworkLinkProps, meta: ItemMeta) {
    /*
     * We need to override the constructor cause we need to obtain the
     * box size and position from the start and finish points of the line.
     */
    super(
      {
        ...props
      },
      {
        ...meta
      }
    );

    this.render();
  }

  /**
   * @override
   */
  protected debouncedStartPositionMovementSave = debounce(
    50, // ms.
    (x: Position["x"], y: Position["y"]) => {
      this.isMoving = false;

      const startPosition = { x, y };

      // Re-Paint after move.
      this.render();

      // Emit the movement event.
      this.lineMovedEventManager.emit({
        item: this,
        startPosition,
        endPosition: this.props.endPosition
      });
    }
  );

  protected debouncedEndPositionMovementSave = debounce(
    50, // ms.
    (x: Position["x"], y: Position["y"]) => {
      this.isMoving = false;
      const endPosition = { x, y };

      // Re-Paint after move.
      this.render();

      // Emit the movement event.
      this.lineMovedEventManager.emit({
        item: this,
        endPosition,
        startPosition: this.props.startPosition
      });
    }
  );

  protected updateDomElement(element: HTMLElement): void {
    if (this.itemProps.ratio != null) {
      this.itemProps.x /= this.itemProps.ratio;
      this.itemProps.y /= this.itemProps.ratio;
    }

    super.updateDomElement(element);

    let {
      x, // Box x
      y, // Box y
      lineWidth, // Line thickness
      viewportOffsetX, // viewport width,
      viewportOffsetY, // viewport heigth,
      startPosition, // Line start position
      endPosition, // Line end position
      color, // Line color
      labelEnd,
      labelStart,
      labelEndWidth,
      labelEndHeight,
      labelStartWidth,
      labelStartHeight
    } = this.props;

    if (this.itemProps.ratio != null) {
      this.itemProps.x *= this.itemProps.ratio;
      this.itemProps.y *= this.itemProps.ratio;
    }

    const svgs = element.getElementsByTagName("svg");
    let line;
    let svg;

    if (svgs.length > 0) {
      svg = svgs.item(0);

      if (svg != null) {
        // Set SVG size.
        const lines = svg.getElementsByTagNameNS(svgNS, "line");
        let groups = svg.getElementsByTagNameNS(svgNS, "g");
        while (groups.length > 0) {
          groups[0].remove();
        }

        if (lines.length > 0) {
          line = lines.item(0);
        }
      }
    } else {
      // No line or svg, no more actions are required.
      return;
    }

    if (svg == null || line == null) {
      // No more actionas are required.
      return;
    }

    // Font size and text adjustments.
    const fontsize = 10;
    const adjustment = 25;

    const lineX1 = startPosition.x - x + lineWidth / 2 + viewportOffsetX / 2;
    const lineY1 = startPosition.y - y + lineWidth / 2 + viewportOffsetY / 2;
    const lineX2 = endPosition.x - x + lineWidth / 2 + viewportOffsetX / 2;
    const lineY2 = endPosition.y - y + lineWidth / 2 + viewportOffsetY / 2;

    let x1 = startPosition.x - x + lineWidth / 2 + viewportOffsetX / 2;
    let y1 = startPosition.y - y + lineWidth / 2 + viewportOffsetY / 2;
    let x2 = endPosition.x - x + lineWidth / 2 + viewportOffsetX / 2;
    let y2 = endPosition.y - y + lineWidth / 2 + viewportOffsetY / 2;

    // Calculate angle (rotation).
    let rad = Math.atan2(lineY2 - lineY1, lineX2 - lineX1);
    let g = (rad * 180) / Math.PI;

    // Calculate effective 'text' box sizes.
    const fontheight = 25;
    if (labelStartWidth <= 0) {
      let lines = labelStart.split("<br>");
      labelStartWidth = 0;
      lines.forEach(l => {
        if (l.length > labelStartWidth) {
          labelStartWidth = l.length * fontsize;
        }
      });
      if (labelStartHeight <= 0) {
        labelStartHeight = lines.length * fontheight;
      }
    }

    if (labelEndWidth <= 0) {
      let lines = labelEnd.split("<br>");
      labelEndWidth = 0;
      lines.forEach(l => {
        if (l.length > labelEndWidth) {
          labelEndWidth = l.length * fontsize;
        }
      });
      if (labelEndHeight <= 0) {
        labelEndHeight = lines.length * fontheight;
      }
    }

    if (x1 < x2) {
      // x1 on left of x2.
      x1 += adjustment;
      x2 -= adjustment + labelEndWidth;
    }

    if (x1 > x2) {
      // x1 on right of x2.
      x1 -= adjustment + labelStartWidth;
      x2 += adjustment;
    }

    if (y1 < y2) {
      // y1 on y2.
      y1 += adjustment;
      y2 -= adjustment + labelEndHeight;
    }

    if (y1 > y2) {
      // y1 under y2.
      y1 -= adjustment + labelStartHeight;
      y2 += adjustment;
    }

    if (typeof color == "undefined") {
      color = "#000";
    }

    // Clean.
    if (element.parentElement !== null) {
      const labels = element.parentElement.getElementsByClassName(
        "vc-item-nl-label"
      );
      while (labels.length > 0) {
        const label = labels.item(0);
        if (label) label.remove();
      }

      const arrows = element.parentElement.getElementsByClassName(
        "vc-item-nl-arrow"
      );
      while (arrows.length > 0) {
        const arrow = arrows.item(0);
        if (arrow) arrow.remove();
      }
    }

    let arrowSize = lineWidth * 2;

    let arrowPosX = lineX1 + (lineX2 - lineX1) / 2 - arrowSize;
    let arrowPosY = lineY1 + (lineY2 - lineY1) / 2 - arrowSize;

    let arrowStart: HTMLElement = document.createElement("div");
    arrowStart.classList.add("vc-item-nl-arrow");
    arrowStart.style.position = "absolute";
    arrowStart.style.border = `${arrowSize}px solid transparent`;
    arrowStart.style.borderBottom = `${arrowSize}px solid ${color}`;
    arrowStart.style.left = `${arrowPosX}px`;
    arrowStart.style.top = `${arrowPosY}px`;
    arrowStart.style.transform = `rotate(${90 + g}deg)`;

    let arrowEnd: HTMLElement = document.createElement("div");
    arrowEnd.classList.add("vc-item-nl-arrow");
    arrowEnd.style.position = "absolute";
    arrowEnd.style.border = `${arrowSize}px solid transparent`;
    arrowEnd.style.borderBottom = `${arrowSize}px solid ${color}`;
    arrowEnd.style.left = `${arrowPosX}px`;
    arrowEnd.style.top = `${arrowPosY}px`;
    arrowEnd.style.transform = `rotate(${270 + g}deg)`;

    if (element.parentElement !== null) {
      element.parentElement.appendChild(arrowStart);
      element.parentElement.appendChild(arrowEnd);
    }

    if (labelStart != "") {
      let htmlLabelStart: HTMLElement = document.createElement("div");

      try {
        htmlLabelStart.innerHTML = labelStart;
        htmlLabelStart.style.position = "absolute";
        htmlLabelStart.style.left = `${x1}px`;
        htmlLabelStart.style.top = `${y1}px`;
        htmlLabelStart.style.width = `${labelStartWidth}px`;
        htmlLabelStart.style.border = `2px solid ${color}`;

        htmlLabelStart.classList.add("vc-item-nl-label", "label-start");
      } catch (error) {
        console.error(error);
      }

      if (element.parentElement !== null) {
        element.parentElement.appendChild(htmlLabelStart);
      }
    }

    if (labelEnd != "") {
      let htmlLabelEnd: HTMLElement = document.createElement("div");

      try {
        htmlLabelEnd.innerHTML = labelEnd;
        htmlLabelEnd.style.position = "absolute";
        htmlLabelEnd.style.left = `${x2}px`;
        htmlLabelEnd.style.top = `${y2}px`;
        htmlLabelEnd.style.width = `${labelEndWidth}px`;
        htmlLabelEnd.style.border = `2px solid ${color}`;

        htmlLabelEnd.classList.add("vc-item-nl-label", "label-end");
      } catch (error) {
        console.error(error);
      }

      if (element.parentElement !== null) {
        element.parentElement.appendChild(htmlLabelEnd);
      }
    }
  }
}
