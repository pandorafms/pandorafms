import {
  WithModuleProps,
  LinkedVisualConsoleProps,
  AnyObject,
  WithAgentProps
} from "../lib/types";
import { modulePropsDecoder, linkedVCPropsDecoder, t } from "../lib";
import Item, { itemBasePropsDecoder, ItemType, ItemProps } from "../Item";
import { FormContainer, InputGroup } from "../Form";
import fontAwesomeIcon from "../lib/FontAwesomeIcon";
import { faTrashAlt, faPlusCircle } from "@fortawesome/free-solid-svg-icons";

export type ColorCloudProps = {
  type: ItemType.COLOR_CLOUD;
  color: string;
  defaultColor: string;
  colorRanges: {
    color: string;
    fromValue: number;
    toValue: number;
  }[];
  // TODO: Add the rest of the color cloud values?
} & ItemProps &
  WithAgentProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the static graph props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function colorCloudPropsDecoder(
  data: AnyObject
): ColorCloudProps | never {
  // TODO: Validate the color.
  if (typeof data.color !== "string" || data.color.length === 0) {
    throw new TypeError("invalid color.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.COLOR_CLOUD,
    color: data.color,
    defaultColor: data.defaultColor,
    colorRanges: data.colorRanges,
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

/**
 * Class to add item to the Color cloud item form
 * This item consists of a label and a color type input color.
 * Element default color is stored in the color property
 */
class ColorInputGroup extends InputGroup<Partial<ColorCloudProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const generalDiv = document.createElement("div");
    generalDiv.className = "div-input-group";

    const colorLabel = document.createElement("label");
    colorLabel.textContent = t("Default color");

    generalDiv.appendChild(colorLabel);

    const ColorInput = document.createElement("input");
    ColorInput.type = "color";
    ColorInput.required = true;

    ColorInput.value = `${this.currentData.defaultColor ||
      this.initialData.defaultColor ||
      "#000000"}`;

    ColorInput.addEventListener("change", e => {
      this.updateData({
        defaultColor: (e.target as HTMLInputElement).value
      });
    });

    generalDiv.appendChild(ColorInput);

    return generalDiv;
  }
}

type ColorRanges = ColorCloudProps["colorRanges"];
type ColorRange = ColorRanges[0];

class RangesInputGroup extends InputGroup<Partial<ColorCloudProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const generalDiv = document.createElement("div");
    generalDiv.className = "div-input-group div-ranges-input-group";

    const rangesLabel = this.createLabel("Ranges");

    generalDiv.appendChild(rangesLabel);

    const rangesControlsContainer = document.createElement("div");
    const createdRangesContainer = document.createElement("div");

    generalDiv.appendChild(createdRangesContainer);
    generalDiv.appendChild(rangesControlsContainer);

    const colorRanges =
      this.currentData.colorRanges || this.initialData.colorRanges || [];

    let buildRanges: (ranges: ColorRanges) => void;

    const handleRangeUpdatePartial = (index: number) => (
      range: ColorRange
    ): void => {
      const colorRanges =
        this.currentData.colorRanges || this.initialData.colorRanges || [];
      this.updateData({
        colorRanges: [
          ...colorRanges.slice(0, index),
          range,
          ...colorRanges.slice(index + 1)
        ]
      });
    };

    const handleDelete = (index: number) => () => {
      const colorRanges =
        this.currentData.colorRanges || this.initialData.colorRanges || [];
      const newRanges = [
        ...colorRanges.slice(0, index),
        ...colorRanges.slice(index + 1)
      ];

      this.updateData({ colorRanges: newRanges });
      buildRanges(newRanges);
    };

    const handleCreate = (range: ColorRange): void => {
      const colorRanges =
        this.currentData.colorRanges || this.initialData.colorRanges || [];
      const newRanges = [...colorRanges, range];
      this.updateData({ colorRanges: newRanges });
      buildRanges(newRanges);
    };

    buildRanges = ranges => {
      createdRangesContainer.innerHTML = "";
      ranges.forEach((colorRange, index) =>
        createdRangesContainer.appendChild(
          this.rangeContainer(
            colorRange,
            handleRangeUpdatePartial(index),
            handleDelete(index)
          )
        )
      );
    };

    buildRanges(colorRanges);

    rangesControlsContainer.appendChild(
      this.initialRangeContainer(handleCreate)
    );

    return generalDiv;
  }

  private initialRangeContainer(onCreate: (range: ColorRange) => void) {
    // TODO: Document
    const initialState = { color: "#ffffff" };

    let state: Partial<ColorRange> = { ...initialState };

    const handleFromValue = (value: ColorRange["fromValue"]): void => {
      state.fromValue = value;
    };
    const handleToValue = (value: ColorRange["toValue"]): void => {
      state.toValue = value;
    };
    const handleColor = (value: ColorRange["color"]): void => {
      state.color = value;
    };

    // User defined type guard.
    // Docs: https://www.typescriptlang.org/docs/handbook/advanced-types.html#user-defined-type-guards
    const isValid = (range: Partial<ColorRange>): range is ColorRange =>
      typeof range.color !== "undefined" &&
      typeof range.toValue !== "undefined" &&
      typeof range.fromValue !== "undefined";

    const rangesContainer = document.createElement("div");

    // Div From value.
    const rangesContainerFromValue = document.createElement("div");
    const rangesLabelFromValue = this.createLabel("From Value");
    const rangesInputFromValue = this.createInputNumber(null, handleFromValue);
    rangesContainerFromValue.appendChild(rangesLabelFromValue);
    rangesContainerFromValue.appendChild(rangesInputFromValue);
    rangesContainer.appendChild(rangesContainerFromValue);

    // Div To Value.
    const rangesDivContainerToValue = document.createElement("div");
    const rangesLabelToValue = this.createLabel("To Value");
    const rangesInputToValue = this.createInputNumber(null, handleToValue);
    rangesContainerFromValue.appendChild(rangesLabelToValue);
    rangesContainerFromValue.appendChild(rangesInputToValue);
    rangesContainer.appendChild(rangesDivContainerToValue);

    // Div Color.
    const rangesDivContainerColor = document.createElement("div");
    const rangesLabelColor = this.createLabel("Color");
    const rangesInputColor = this.createInputColor(
      initialState.color,
      handleColor
    );
    rangesContainerFromValue.appendChild(rangesLabelColor);
    rangesContainerFromValue.appendChild(rangesInputColor);
    rangesContainer.appendChild(rangesDivContainerColor);

    // Button delete.
    const createBtn = document.createElement("a");
    createBtn.appendChild(
      fontAwesomeIcon(faPlusCircle, t("Create color range"), {
        size: "small",
        color: "#565656"
      })
    );

    const handleCreate = () => {
      if (isValid(state)) onCreate(state);
      state = initialState;
      rangesInputFromValue.value = `${state.fromValue || ""}`;
      rangesInputToValue.value = `${state.toValue || ""}`;
      rangesInputColor.value = `${state.color}`;
    };

    createBtn.addEventListener("click", handleCreate);

    rangesContainer.appendChild(createBtn);

    return rangesContainer;
  }

  private rangeContainer(
    colorRange: ColorRange,
    onUpdate: (range: ColorRange) => void,
    onDelete: () => void
  ): HTMLDivElement {
    // TODO: Document
    const state = { ...colorRange };

    const handleFromValue = (value: ColorRange["fromValue"]): void => {
      state.fromValue = value;
      onUpdate({ ...state });
    };
    const handleToValue = (value: ColorRange["toValue"]): void => {
      state.toValue = value;
      onUpdate({ ...state });
    };
    const handleColor = (value: ColorRange["color"]): void => {
      state.color = value;
      onUpdate({ ...state });
    };

    const rangesContainer = document.createElement("div");

    // Div From value.
    const rangesContainerFromValue = document.createElement("div");
    const rangesLabelFromValue = this.createLabel("From Value");
    const rangesInputFromValue = this.createInputNumber(
      colorRange.fromValue,
      handleFromValue
    );
    rangesContainerFromValue.appendChild(rangesLabelFromValue);
    rangesContainerFromValue.appendChild(rangesInputFromValue);
    rangesContainer.appendChild(rangesContainerFromValue);

    // Div To Value.
    const rangesDivContainerToValue = document.createElement("div");
    const rangesLabelToValue = this.createLabel("To Value");
    const rangesInputToValue = this.createInputNumber(
      colorRange.toValue,
      handleToValue
    );
    rangesContainerFromValue.appendChild(rangesLabelToValue);
    rangesContainerFromValue.appendChild(rangesInputToValue);
    rangesContainer.appendChild(rangesDivContainerToValue);

    // Div Color.
    const rangesDivContainerColor = document.createElement("div");
    const rangesLabelColor = this.createLabel("Color");
    const rangesInputColor = this.createInputColor(
      colorRange.color,
      handleColor
    );
    rangesContainerFromValue.appendChild(rangesLabelColor);
    rangesContainerFromValue.appendChild(rangesInputColor);
    rangesContainer.appendChild(rangesDivContainerColor);

    // Button delete.
    const deleteBtn = document.createElement("a");
    deleteBtn.appendChild(
      fontAwesomeIcon(faTrashAlt, t("Delete color range"), {
        size: "small",
        color: "#565656"
      })
    );
    deleteBtn.addEventListener("click", onDelete);

    rangesContainer.appendChild(deleteBtn);

    return rangesContainer;
  }

  private createLabel(text: string): HTMLLabelElement {
    const label = document.createElement("label");
    label.textContent = t(text);
    return label;
  }

  private createInputNumber(
    value: number | null,
    onUpdate: (value: number) => void
  ): HTMLInputElement {
    const input = document.createElement("input");
    input.type = "number";
    if (value !== null) input.value = `${value}`;
    input.addEventListener("change", e => {
      const value = parseInt((e.target as HTMLInputElement).value);
      if (!isNaN(value)) onUpdate(value);
    });

    return input;
  }

  private createInputColor(
    value: string | null,
    onUpdate: (value: string) => void
  ): HTMLInputElement {
    const input = document.createElement("input");
    input.type = "color";
    if (value !== null) input.value = value;
    input.addEventListener("change", e =>
      onUpdate((e.target as HTMLInputElement).value)
    );

    return input;
  }
}

const svgNS = "http://www.w3.org/2000/svg";

export default class ColorCloud extends Item<ColorCloudProps> {
  protected createDomElement(): HTMLElement {
    const container: HTMLDivElement = document.createElement("div");
    container.className = "color-cloud";

    // Add the SVG.
    container.append(this.createSvgElement());

    return container;
  }

  public resizeElement(width: number): void {
    super.resizeElement(width, width);
  }

  public createSvgElement(): SVGSVGElement {
    const gradientId = `grad_${this.props.id}`;
    // SVG container.
    const svg = document.createElementNS(svgNS, "svg");
    // Auto resize SVG using the view box magic: https://css-tricks.com/scale-svg/
    svg.setAttribute("viewBox", "0 0 100 100");

    // Defs.
    const defs = document.createElementNS(svgNS, "defs");
    // Radial gradient.
    const radialGradient = document.createElementNS(svgNS, "radialGradient");
    radialGradient.setAttribute("id", gradientId);
    radialGradient.setAttribute("cx", "50%");
    radialGradient.setAttribute("cy", "50%");
    radialGradient.setAttribute("r", "50%");
    radialGradient.setAttribute("fx", "50%");
    radialGradient.setAttribute("fy", "50%");
    // Stops.
    const stop0 = document.createElementNS(svgNS, "stop");
    stop0.setAttribute("offset", "0%");
    stop0.setAttribute(
      "style",
      `stop-color:${this.props.color};stop-opacity:0.9`
    );
    const stop100 = document.createElementNS(svgNS, "stop");
    stop100.setAttribute("offset", "100%");
    stop100.setAttribute(
      "style",
      `stop-color:${this.props.color};stop-opacity:0`
    );
    // Circle.
    const circle = document.createElementNS(svgNS, "circle");
    circle.setAttribute("fill", `url(#${gradientId})`);
    circle.setAttribute("cx", "50%");
    circle.setAttribute("cy", "50%");
    circle.setAttribute("r", "50%");

    // Append elements.
    radialGradient.append(stop0, stop100);
    defs.append(radialGradient);
    svg.append(defs, circle);

    if (
      this.props.agentDisabled === true ||
      this.props.moduleDisabled === true
    ) {
      svg.setAttribute("opacity", "0.2");
    }

    return svg;
  }

  /**
   * @override function to add or remove inputsGroups those that are not necessary.
   * Add to:
   * ColorInputGroup
   * RangesInputGroup
   */
  public getFormContainer(): FormContainer {
    return ColorCloud.getFormContainer(this.props);
  }

  public static getFormContainer(
    props: Partial<ColorCloudProps>
  ): FormContainer {
    const formContainer = super.getFormContainer(props);
    formContainer.removeInputGroup("label");

    formContainer.addInputGroup(new ColorInputGroup("color-cloud", props), 3);
    formContainer.addInputGroup(new RangesInputGroup("ranges-cloud", props), 4);

    return formContainer;
  }
}
