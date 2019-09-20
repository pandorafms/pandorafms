import {
  LinkedVisualConsoleProps,
  AnyObject,
  WithModuleProps
} from "../lib/types";
import {
  linkedVCPropsDecoder,
  parseIntOr,
  modulePropsDecoder,
  replaceMacros,
  t,
  periodSelector
} from "../lib";
import Item, {
  ItemType,
  ItemProps,
  itemBasePropsDecoder,
  LinkConsoleInputGroup,
  AgentModuleInputGroup
} from "../Item";
import { FormContainer, InputGroup } from "../Form";

export type SimpleValueProps = {
  type: ItemType.SIMPLE_VALUE;
  valueType: "string" | "image";
  value: string;
} & (
  | {
      processValue: "none";
    }
  | {
      processValue: "avg" | "max" | "min";
      period: number;
    }) &
  ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Extract a valid enum value from a raw value type.
 * @param valueType Raw value.
 */
const parseValueType = (valueType: unknown): SimpleValueProps["valueType"] => {
  switch (valueType) {
    case "string":
    case "image":
      return valueType;
    default:
      return "string";
  }
};

/**
 * Extract a valid enum value from a raw process value.
 * @param processValue Raw value.
 */
const parseProcessValue = (
  processValue: unknown
): SimpleValueProps["processValue"] => {
  switch (processValue) {
    case "none":
    case "avg":
    case "max":
    case "min":
      return processValue;
    default:
      return "none";
  }
};

/**
 * Class to add item to the Simple value item form
 * This item consists of a label and select Process.
 * Show process is stored in the processValue property.
 */
class ProcessInputGroup extends InputGroup<Partial<SimpleValueProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const processLabel = document.createElement("label");

    const container = document.createElement("div");
    processLabel.textContent = t("Process");

    const options: {
      value: SimpleValueProps["processValue"];
      text: string;
    }[] = [
      { value: "none", text: t("None") },
      { value: "avg", text: t("Avg Value") },
      { value: "max", text: t("Max Value") },
      { value: "min", text: t("Min Value") }
    ];

    const processSelect = document.createElement("select");
    processSelect.required = true;

    options.forEach(option => {
      const optionElement = document.createElement("option");
      optionElement.value = option.value;
      optionElement.textContent = option.text;
      processSelect.appendChild(optionElement);
    });

    const valueProcess =
      this.currentData.processValue || this.initialData.processValue || "none";

    processSelect.value = valueProcess;

    switch (valueProcess) {
      case "avg":
      case "max":
      case "min":
        container.appendChild(this.getPeriodSelector());
        break;
      case "none":
      default:
        break;
    }

    processSelect.addEventListener("change", event => {
      const value = (event.target as HTMLSelectElement).value;
      container.childNodes.forEach(n => n.remove());

      switch (value) {
        case "avg":
        case "max":
        case "min":
          container.appendChild(this.getPeriodSelector());
          this.updateData({
            processValue: value
          });
          break;
        case "none":
        default:
          this.updateData({
            processValue: "none"
          });
          break;
      }
    });

    processLabel.appendChild(processSelect);
    processLabel.appendChild(container);

    return processLabel;
  }

  private getPeriodSelector = (): HTMLElement => {
    const periodLabel = document.createElement("label");
    periodLabel.textContent = t("Period");

    let period = 300;
    {
      this.currentData as SimpleValueProps;
      this.initialData as SimpleValueProps;

      if (
        (this.currentData.processValue === "avg" ||
          this.currentData.processValue === "max" ||
          this.currentData.processValue === "min") &&
        this.currentData.period != null
      ) {
        period = this.currentData.period;
      } else if (
        (this.initialData.processValue === "avg" ||
          this.initialData.processValue === "max" ||
          this.initialData.processValue === "min") &&
        this.initialData.period != null
      ) {
        period = this.initialData.period;
      }
    }

    const periodControl = periodSelector(
      period,
      { text: t("None"), value: 0 },
      [
        { text: t("5 minutes"), value: 300 },
        { text: t("30 minutes"), value: 1800 },
        { text: t("1 hours"), value: 3600 },
        { text: t("6 hours"), value: 21600 },
        { text: t("12 hours"), value: 43200 },
        { text: t("1 day"), value: 86400 },
        { text: t("1 week"), value: 604800 },
        { text: t("15 days"), value: 1296000 },
        { text: t("1 month"), value: 2592000 },
        { text: t("3 months"), value: 7776000 },
        { text: t("6 months"), value: 15552000 },
        { text: t("1 year"), value: 31104000 },
        { text: t("2 years"), value: 62208000 },
        { text: t("3 years"), value: 93312000 }
      ],
      value =>
        this.updateData({
          period: value
        })
    );

    periodLabel.appendChild(periodControl);

    return periodLabel;
  };
}

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the simple value props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function simpleValuePropsDecoder(
  data: AnyObject
): SimpleValueProps | never {
  if (typeof data.value !== "string" || data.value.length === 0) {
    throw new TypeError("invalid value");
  }

  const processValue = parseProcessValue(data.processValue);

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.SIMPLE_VALUE,
    valueType: parseValueType(data.valueType),
    value: data.value,
    ...(processValue === "none"
      ? { processValue }
      : { processValue, period: parseIntOr(data.period, 0) }), // Object spread. It will merge the properties of the two objects.
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class SimpleValue extends Item<SimpleValueProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "simple-value";

    if (this.props.valueType === "image") {
      const img = document.createElement("img");
      img.src = this.props.value;
      element.append(img);
    } else {
      // Add the value to the label and show it.
      let text = this.props.value;
      let label = this.getLabelWithMacrosReplaced();
      if (label.length > 0) {
        text = replaceMacros([{ macro: /\(?_VALUE_\)?/i, value: text }], label);
      }

      element.innerHTML = text;
    }

    return element;
  }

  /**
   * @override Item.createLabelDomElement
   * Create a new label for the visual console item.
   * @return Item label.
   */
  protected createLabelDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "visual-console-item-label";
    // Always return an empty label.
    return element;
  }

  /**
   * @override function to add or remove inputsGroups those that are not necessary.
   * Add to:
   * LinkConsoleInputGroup
   * ProcessInputGroup
   * AgentModuleInputGroup
   */
  public getFormContainer(): FormContainer {
    const formContainer = super.getFormContainer();
    formContainer.addInputGroup(
      new LinkConsoleInputGroup("link-console", this.props)
    );
    formContainer.addInputGroup(
      new ProcessInputGroup("process-operation", this.props)
    );
    formContainer.addInputGroup(
      new AgentModuleInputGroup("agent-autocomplete", this.props)
    );
    return formContainer;
  }
}
