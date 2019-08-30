import { arc as arcFactory } from "d3-shape";

import {
  LinkedVisualConsoleProps,
  AnyObject,
  WithModuleProps
} from "../lib/types";
import {
  linkedVCPropsDecoder,
  modulePropsDecoder,
  notEmptyStringOr,
  parseIntOr,
  parseFloatOr
} from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type PercentileProps = {
  type: ItemType.PERCENTILE_BAR;
  percentileType:
    | "progress-bar"
    | "bubble"
    | "circular-progress-bar"
    | "circular-progress-bar-alt";
  valueType: "percent" | "value";
  minValue: number | null;
  maxValue: number | null;
  color: string | null;
  labelColor: string | null;
  value: number | null;
  unit: string | null;
} & ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Extract a valid enum value from a raw type value.
 * @param type Raw value.
 */
function extractPercentileType(
  type: unknown
): PercentileProps["percentileType"] {
  switch (type) {
    case "progress-bar":
    case "bubble":
    case "circular-progress-bar":
    case "circular-progress-bar-alt":
      return type;
    default:
    case ItemType.PERCENTILE_BAR:
      return "progress-bar";
    case ItemType.PERCENTILE_BUBBLE:
      return "bubble";
    case ItemType.CIRCULAR_PROGRESS_BAR:
      return "circular-progress-bar";
    case ItemType.CIRCULAR_INTERIOR_PROGRESS_BAR:
      return "circular-progress-bar-alt";
  }
}

/**
 * Extract a valid enum value from a raw value type value.
 * @param type Raw value.
 */
function extractValueType(valueType: unknown): PercentileProps["valueType"] {
  switch (valueType) {
    case "percent":
    case "value":
      return valueType;
    default:
      return "percent";
  }
}

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the percentile props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function percentilePropsDecoder(
  data: AnyObject
): PercentileProps | never {
  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.PERCENTILE_BAR,
    percentileType: extractPercentileType(data.percentileType || data.type),
    valueType: extractValueType(data.valueType),
    minValue: parseIntOr(data.minValue, null),
    maxValue: parseIntOr(data.maxValue, null),
    color: notEmptyStringOr(data.color, null),
    labelColor: notEmptyStringOr(data.labelColor, null),
    value: parseFloatOr(data.value, null),
    unit: notEmptyStringOr(data.unit, null),
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

const svgNS = "http://www.w3.org/2000/svg";

export default class Percentile extends Item<PercentileProps> {
  protected createDomElement(): HTMLElement {
    const colors = {
      background: "#000000",
      progress: this.props.color || "#F0F0F0",
      text: this.props.labelColor || "#444444"
    };
    // Progress.
    const progress = this.getProgress();
    // Main element.
    const element = document.createElement("div");
    // SVG container.
    const svg = document.createElementNS(svgNS, "svg");

    var formatValue;
    if (this.props.value != null) {
      if (Intl) {
        formatValue = Intl.NumberFormat("en-EN").format(this.props.value);
      } else {
        formatValue = this.props.value;
      }
    }

    switch (this.props.percentileType) {
      case "progress-bar":
        {
          const backgroundRect = document.createElementNS(svgNS, "rect");
          backgroundRect.setAttribute("fill", colors.background);
          backgroundRect.setAttribute("fill-opacity", "0.5");
          backgroundRect.setAttribute("width", "100");
          backgroundRect.setAttribute("height", "20");
          backgroundRect.setAttribute("rx", "5");
          backgroundRect.setAttribute("ry", "5");
          const progressRect = document.createElementNS(svgNS, "rect");
          progressRect.setAttribute("fill", colors.progress);
          progressRect.setAttribute("fill-opacity", "1");
          progressRect.setAttribute("width", `${progress}`);
          progressRect.setAttribute("height", "20");
          progressRect.setAttribute("rx", "5");
          progressRect.setAttribute("ry", "5");
          const text = document.createElementNS(svgNS, "text");
          text.setAttribute("text-anchor", "middle");
          text.setAttribute("alignment-baseline", "middle");
          text.setAttribute("font-size", "12");
          text.setAttribute("font-family", "arial");
          text.setAttribute("font-weight", "bold");
          text.setAttribute("transform", "translate(50 11)");
          text.setAttribute("fill", colors.text);

          if (this.props.valueType === "value") {
            text.style.fontSize = "6pt";

            text.textContent = this.props.unit
              ? `${formatValue} ${this.props.unit}`
              : `${formatValue}`;
          } else {
            text.textContent = `${progress}%`;
          }

          // Auto resize SVG using the view box magic: https://css-tricks.com/scale-svg/
          svg.setAttribute("viewBox", "0 0 100 20");
          svg.append(backgroundRect, progressRect, text);
        }
        break;
      case "bubble":
      case "circular-progress-bar":
      case "circular-progress-bar-alt":
        {
          // Auto resize SVG using the view box magic: https://css-tricks.com/scale-svg/
          svg.setAttribute("viewBox", "0 0 100 100");

          if (this.props.percentileType === "bubble") {
            // Create and append the circles.
            const backgroundCircle = document.createElementNS(svgNS, "circle");
            backgroundCircle.setAttribute("transform", "translate(50 50)");
            backgroundCircle.setAttribute("fill", colors.background);
            backgroundCircle.setAttribute("fill-opacity", "0.5");
            backgroundCircle.setAttribute("r", "50");
            const progressCircle = document.createElementNS(svgNS, "circle");
            progressCircle.setAttribute("transform", "translate(50 50)");
            progressCircle.setAttribute("fill", colors.progress);
            progressCircle.setAttribute("fill-opacity", "1");
            progressCircle.setAttribute("r", `${progress / 2}`);

            svg.append(backgroundCircle, progressCircle);
          } else {
            // Create and append the circles.
            const arcProps = {
              innerRadius:
                this.props.percentileType === "circular-progress-bar" ? 30 : 0,
              outerRadius: 50,
              startAngle: 0,
              endAngle: Math.PI * 2
            };
            const arc = arcFactory();

            const backgroundCircle = document.createElementNS(svgNS, "path");
            backgroundCircle.setAttribute("transform", "translate(50 50)");
            backgroundCircle.setAttribute("fill", colors.background);
            backgroundCircle.setAttribute("fill-opacity", "0.5");
            backgroundCircle.setAttribute("d", `${arc(arcProps)}`);
            const progressCircle = document.createElementNS(svgNS, "path");
            progressCircle.setAttribute("transform", "translate(50 50)");
            progressCircle.setAttribute("fill", colors.progress);
            progressCircle.setAttribute("fill-opacity", "1");
            progressCircle.setAttribute(
              "d",
              `${arc({
                ...arcProps,
                endAngle: arcProps.endAngle * (progress / 100)
              })}`
            );

            svg.append(backgroundCircle, progressCircle);
          }

          // Create and append the text.
          const text = document.createElementNS(svgNS, "text");
          text.setAttribute("text-anchor", "middle");
          text.setAttribute("alignment-baseline", "middle");
          text.setAttribute("font-size", "16");
          text.setAttribute("font-family", "arial");
          text.setAttribute("font-weight", "bold");
          text.setAttribute("fill", colors.text);

          if (this.props.valueType === "value" && this.props.value != null) {
            // Show value and unit in 1 (no unit) or 2 lines.
            if (this.props.unit && this.props.unit.length > 0) {
              const value = document.createElementNS(svgNS, "tspan");
              value.setAttribute("x", "0");
              value.setAttribute("dy", "1em");
              value.textContent = `${formatValue}`;
              value.style.fontSize = "8pt";
              const unit = document.createElementNS(svgNS, "tspan");
              unit.setAttribute("x", "0");
              unit.setAttribute("dy", "1em");
              unit.textContent = `${this.props.unit}`;
              unit.style.fontSize = "8pt";
              text.append(value, unit);
              text.setAttribute("transform", "translate(50 33)");
            } else {
              text.textContent = `${formatValue}`;
              text.style.fontSize = "8pt";
              text.setAttribute("transform", "translate(50 50)");
            }
          } else {
            // Percentage.
            text.textContent = `${progress}%`;
            text.setAttribute("transform", "translate(50 50)");
          }

          svg.append(text);
        }
        break;
    }

    element.append(svg);

    return element;
  }

  private getProgress(): number {
    const minValue = this.props.minValue || 0;
    const maxValue = this.props.maxValue || 100;
    const value = this.props.value == null ? 0 : this.props.value;

    if (value <= minValue) return 0;
    else if (value >= maxValue) return 100;
    else return Math.trunc(((value - minValue) / (maxValue - minValue)) * 100);
  }
}
