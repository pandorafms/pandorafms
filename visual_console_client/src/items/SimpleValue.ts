import {
  LinkedVisualConsoleProps,
  AnyObject,
  WithModuleProps
} from "../lib/types";
import {
  linkedVCPropsDecoder,
  parseIntOr,
  modulePropsDecoder,
  replaceMacros
} from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

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
}
