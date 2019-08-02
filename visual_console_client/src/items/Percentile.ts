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
  parseFloatOr,
  t
} from "../lib";
import Item, {
  ItemType,
  ItemProps,
  itemBasePropsDecoder,
  LinkConsoleInputGroup
} from "../Item";
import { InputGroup, FormContainer } from "../Form";

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

/**
 * Class to add item to the percentile item form
 * This item consists of a label and a numeric type input.
 * Diameter is stored in the width property
 */
class DiameterInputGroup extends InputGroup<Partial<PercentileProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const diameterLabel = document.createElement("label");
    diameterLabel.textContent = t("Diameter");

    const diameterInput = document.createElement("input");
    diameterInput.type = "number";
    diameterInput.required = true;

    diameterInput.value = `${this.currentData.width || this.initialData.width}`;

    diameterInput.addEventListener("change", e => {
      this.updateData({
        width: parseIntOr((e.target as HTMLInputElement).value, 0)
      });
    });

    diameterLabel.appendChild(diameterInput);

    return diameterLabel;
  }
}

/**
 * Class to add item to the percentile item form
 * This item consists of a label and a numeric type input.
 * Minvalue is stored in the minValue property
 */
class MinValueInputGroup extends InputGroup<Partial<PercentileProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const minValueLabel = document.createElement("label");
    minValueLabel.textContent = t("Min Value");

    const minValueInput = document.createElement("input");
    minValueInput.type = "number";
    minValueInput.required = true;

    minValueInput.value = `${this.currentData.minValue ||
      this.initialData.minValue ||
      0}`;

    minValueInput.addEventListener("change", e => {
      this.updateData({
        minValue: parseIntOr((e.target as HTMLInputElement).value, 0)
      });
    });

    minValueLabel.appendChild(minValueInput);

    return minValueLabel;
  }
}

/**
 * Class to add item to the percentile item form
 * This item consists of a label and a numeric type input.
 * Maxvalue is stored in the maxValue property
 */
class MaxValueInputGroup extends InputGroup<Partial<PercentileProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const maxValueLabel = document.createElement("label");
    maxValueLabel.textContent = t("Max Value");

    const maxValueInput = document.createElement("input");
    maxValueInput.type = "number";
    maxValueInput.required = true;

    maxValueInput.value = `${this.currentData.maxValue ||
      this.initialData.maxValue ||
      0}`;

    maxValueInput.addEventListener("change", e => {
      this.updateData({
        maxValue: parseIntOr((e.target as HTMLInputElement).value, 0)
      });
    });

    maxValueLabel.appendChild(maxValueInput);

    return maxValueLabel;
  }
}

/**
 * Class to add item to the percentile item form
 * This item consists of a label and a select.
 * options for select: progress-bar, bubble, circular-progress-bar,
 * circular-progress-bar-alt.
 * Type is stored in the percentileType property
 */
class TypePercentileInputGroup extends InputGroup<Partial<PercentileProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const typeLabel = document.createElement("label");
    typeLabel.textContent = t("Max Value");

    const options: {
      value: PercentileProps["percentileType"];
      text: string;
    }[] = [
      { value: "progress-bar", text: t("Percentile") },
      { value: "bubble", text: t("Bubble") },
      {
        value: "circular-progress-bar",
        text: t("Circular porgress bar")
      },
      {
        value: "circular-progress-bar-alt",
        text: t("Circular progress bar (interior)")
      }
    ];

    const typeSelect = document.createElement("select");
    typeSelect.required = true;

    typeSelect.value =
      this.currentData.percentileType ||
      this.initialData.percentileType ||
      "progress-bar";

    options.forEach(option => {
      const optionElement = document.createElement("option");
      optionElement.value = option.value;
      optionElement.textContent = option.text;
      typeSelect.appendChild(optionElement);
    });

    typeSelect.addEventListener("change", event => {
      this.updateData({
        percentileType: extractPercentileType(
          (event.target as HTMLSelectElement).value
        )
      });
    });

    typeLabel.appendChild(typeSelect);

    return typeLabel;
  }
}

/**
 * Class to add item to the percentile item form
 * This item consists of a label and a select.
 * options for select: percent, value
 * Type value is stored in the valueType property
 */
class ValueToShowInputGroup extends InputGroup<Partial<PercentileProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const valueToShowLabel = document.createElement("label");
    valueToShowLabel.textContent = t("Value to show");

    const options: { value: PercentileProps["valueType"]; text: string }[] = [
      { value: "percent", text: t("Percent") },
      { value: "value", text: t("Value") }
    ];

    const valueToShowInput = document.createElement("select");
    valueToShowInput.required = true;
    valueToShowInput.value =
      this.currentData.valueType || this.initialData.valueType || "percent";

    options.forEach(option => {
      const optionElement = document.createElement("option");
      optionElement.value = option.value;
      optionElement.textContent = option.text;
      valueToShowInput.appendChild(optionElement);
    });

    valueToShowInput.addEventListener("change", event => {
      this.updateData({
        valueType: extractValueType((event.target as HTMLSelectElement).value)
      });
    });

    valueToShowLabel.appendChild(valueToShowInput);

    return valueToShowLabel;
  }
}

/**
 * Class to add item to the percentile item form
 * This item consists of a label and a color type input.
 * Element color is stored in the color property
 */
class ElementColorInputGroup extends InputGroup<Partial<PercentileProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const elementColorLabel = document.createElement("label");
    elementColorLabel.textContent = t("Element color");

    const elementColorInput = document.createElement("input");
    elementColorInput.type = "color";
    elementColorInput.required = true;

    elementColorInput.value = `${this.currentData.color ||
      this.initialData.color}`;

    elementColorInput.addEventListener("change", e => {
      this.updateData({
        color: (e.target as HTMLInputElement).value
      });
    });

    elementColorLabel.appendChild(elementColorInput);

    return elementColorLabel;
  }
}

/**
 * Class to add item to the percentile item form
 * This item consists of a label and a color type input.
 * Value color is stored in the labelColor property
 */
class ValueColorInputGroup extends InputGroup<Partial<PercentileProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const valueColorLabel = document.createElement("label");
    valueColorLabel.textContent = t("Value color");

    const valueColorInput = document.createElement("input");
    valueColorInput.type = "color";
    valueColorInput.required = true;

    valueColorInput.value = `${this.currentData.labelColor ||
      this.initialData.labelColor}`;

    valueColorInput.addEventListener("change", e => {
      this.updateData({
        labelColor: (e.target as HTMLInputElement).value
      });
    });

    valueColorLabel.appendChild(valueColorInput);

    return valueColorLabel;
  }
}

/**
 * Class to add item to the percentile item form
 * This item consists of a label and a color type input.
 * label is stored in the label property
 */
class LabelPercentileInputGroup extends InputGroup<Partial<PercentileProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const labelPercentileLabel = document.createElement("label");
    labelPercentileLabel.textContent = t("Label");

    const labelPercentileInput = document.createElement("input");
    labelPercentileInput.type = "text";
    labelPercentileInput.required = true;

    labelPercentileInput.value = `${this.currentData.label ||
      this.initialData.label ||
      ""} `;

    labelPercentileInput.addEventListener("change", e => {
      this.updateData({
        label: (e.target as HTMLInputElement).value
      });
    });

    labelPercentileLabel.appendChild(labelPercentileInput);

    return labelPercentileLabel;
  }
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

  /**
   * @override function to add or remove inputsGroups those that are not necessary.
   * Add to:
   * DiameterInputGroup,
   * MinValueInputGroup,
   * MaxValueInputGroup,
   * TypePercentileInputGroup,
   * ValueToShowInputGroup,
   * ElementColorInputGroup,
   * ValueColorInputGroup,
   * LabelPercentileInputGroup
   * LinkConsoleInputGroup
   * are removed:
   * inputgrouplabel
   * size
   */
  public getFormContainer(): FormContainer {
    const formContainer = super.getFormContainer();
    // Delete items groups.
    formContainer.removeInputGroup("size");
    // TODO: Remove inputGroup label this item.
    //formContainer.removeInputGroup("label");

    // Add new items gropus.
    formContainer.addInputGroup(new DiameterInputGroup("diameter", this.props));
    formContainer.addInputGroup(
      new MinValueInputGroup("min-value", this.props)
    );
    formContainer.addInputGroup(
      new MaxValueInputGroup("max-value", this.props)
    );
    formContainer.addInputGroup(
      new TypePercentileInputGroup("type", this.props)
    );
    formContainer.addInputGroup(
      new ValueToShowInputGroup("value-to-show", this.props)
    );
    formContainer.addInputGroup(
      new ElementColorInputGroup("element-color", this.props)
    );
    formContainer.addInputGroup(
      new ValueColorInputGroup("value-color", this.props)
    );
    formContainer.addInputGroup(
      new LabelPercentileInputGroup("label-percentile", this.props)
    );
    formContainer.addInputGroup(
      new LinkConsoleInputGroup("link-console", this.props)
    );
    return formContainer;
  }
}
