import { AnyObject, Position, ItemMeta } from "../lib/types";
import {
  debounce,
  addMovementListener,
  notEmptyStringOr,
  parseIntOr
} from "../lib";
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
    viewportOffsetX: 300,
    viewportOffsetY: 300,
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
      const endPosition = {
        x,
        y
      };

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

    if (labelStart == "" && labelEnd == "") {
      // No more actions are required.
      return;
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
    const fontsize = 7.4;
    const adjustment = 50;

    let x1 = startPosition.x - x + lineWidth / 2 + viewportOffsetX / 2;
    let y1 = startPosition.y - y + lineWidth / 2 + viewportOffsetY / 2;
    let x2 = endPosition.x - x + lineWidth / 2 + viewportOffsetX / 2;
    let y2 = endPosition.y - y + lineWidth / 2 + viewportOffsetY / 2;

    // Calculate angle (rotation).
    let g = (Math.atan((y2 - y1) / (x2 - x1)) * 180) / Math.PI;

    if (Math.abs(g) > 0) {
      g = 0;
    }

    // Calculate effective 'text' box sizes.
    const fontheight = 23;
    if (labelStartWidth <= 0) {
      labelStartWidth = labelStart.length * fontsize;
    }
    if (labelEndWidth <= 0) {
      labelEndWidth = labelEnd.length * fontsize;
    }
    if (labelStartHeight <= 0) {
      labelStartHeight = fontheight;
    }
    if (labelEndHeight <= 0) {
      labelEndHeight = fontheight;
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

    if (labelStart != "") {
      let start = document.createElementNS(svgNS, "g");
      start.setAttribute("x", `${x1}`);
      start.setAttribute("y", `${y1}`);
      start.setAttribute("width", `${labelStartWidth + fontsize * 2}`);
      start.setAttribute("height", `${labelStartHeight}`);
      start.setAttribute("transform", `rotate(${g} ${x1} ${y1})`);

      let sr = document.createElementNS(svgNS, "rect");
      sr.setAttribute("x", `${x1}`);
      sr.setAttribute("y", `${y1}`);
      sr.setAttribute("width", `${labelStartWidth}`);
      sr.setAttribute("height", `${labelStartHeight}`);
      sr.setAttribute("stroke", `${color}`);
      sr.setAttribute("stroke-width", "2");
      sr.setAttribute("fill", "#FFF");
      start.append(sr);

      let st = document.createElementNS(svgNS, "text");
      st.setAttribute("x", `${x1 + fontsize}`);
      st.setAttribute("y", `${y1 + (fontheight * 2) / 3}`);
      st.setAttribute("fill", "#000");
      st.textContent = labelStart;
      st.setAttribute("transform", `rotate(${g} ${x1} ${y1})`);
      start.append(st);

      svg.append(start);
    }

    if (labelEnd != "") {
      let end = document.createElementNS(svgNS, "g");
      let er = document.createElementNS(svgNS, "rect");
      er.setAttribute("x", `${x2}`);
      er.setAttribute("y", `${y2}`);
      er.setAttribute("width", `${labelEndWidth + fontsize * 2}`);
      er.setAttribute("height", `${labelEndHeight}`);
      er.setAttribute("stroke", `${color}`);
      er.setAttribute("stroke-width", "2");
      er.setAttribute("fill", "#FFF");
      er.setAttribute("transform", `rotate(${g} ${x1} ${y1})`);
      end.append(er);

      let et = document.createElementNS(svgNS, "text");
      et.setAttribute("x", `${x2 + fontsize}`);
      et.setAttribute("y", `${y2 + (fontheight * 2) / 3}`);
      et.setAttribute("fill", "#000");
      et.textContent = labelEnd;
      et.setAttribute("transform", `rotate(${g} ${x1} ${y1})`);
      end.append(et);

      svg.append(end);
    }
  }
}
