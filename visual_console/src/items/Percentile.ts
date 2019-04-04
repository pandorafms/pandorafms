import {
  LinkedVisualConsoleProps,
  UnknownObject,
  WithModuleProps
} from "../types";
import {
  linkedVCPropsDecoder,
  modulePropsDecoder,
  decodeBase64,
  stringIsEmpty,
  notEmptyStringOr
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
  value: string | null;
  color: string | null;
  labelColor: string | null;
  html: string;
} & ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Extract a valid enum value from a raw type value.
 * @param type Raw value.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function extractPercentileType(type: any): PercentileProps["percentileType"] {
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
// eslint-disable-next-line @typescript-eslint/no-explicit-any
function extractValueType(valueType: any): PercentileProps["valueType"] {
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
  data: UnknownObject
): PercentileProps | never {
  if (stringIsEmpty(data.html) || stringIsEmpty(data.encodedHtml)) {
    throw new TypeError("missing html content.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.PERCENTILE_BAR,
    percentileType: extractPercentileType(data.type),
    valueType: extractValueType(data.valueType),
    value: notEmptyStringOr(data.value, null),
    color: notEmptyStringOr(data.color, null),
    labelColor: notEmptyStringOr(data.labelColor, null),
    html: !stringIsEmpty(data.html)
      ? data.html
      : decodeBase64(data.encodedHtml),
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class Percentile extends Item<PercentileProps> {
  public createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.innerHTML = this.props.html;

    return element;
  }
}
