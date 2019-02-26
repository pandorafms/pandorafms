import { LinkedVisualConsoleProps, UnknownObject } from "../types";

import {
  linkedVCPropsDecoder,
  parseIntOr,
  padLeft,
  parseBoolean
} from "../lib";

import VisualConsoleItem, {
  VisualConsoleItemProps,
  itemBasePropsDecoder,
  VisualConsoleItemType
} from "../VisualConsoleItem";

export type ClockProps = {
  type: VisualConsoleItemType.CLOCK;
  clockType: "analogic" | "digital";
  clockFormat: "datetime" | "time";
  clockTimezone: string;
  clockTimezoneOffset: number; // Offset of the timezone to UTC in seconds.
  showClockTimezone: boolean;
} & VisualConsoleItemProps &
  LinkedVisualConsoleProps;

/**
 * Extract a valid enum value from a raw unknown value.
 * @param clockType Raw value.
 */
const parseClockType = (
  clockType: any // eslint-disable-line @typescript-eslint/no-explicit-any
): ClockProps["clockType"] => {
  switch (clockType) {
    case "analogic":
    case "digital":
      return clockType;
    default:
      return "analogic";
  }
};

/**
 * Extract a valid enum value from a raw unknown value.
 * @param clockFormat Raw value.
 */
const parseClockFormat = (
  clockFormat: any // eslint-disable-line @typescript-eslint/no-explicit-any
): ClockProps["clockFormat"] => {
  switch (clockFormat) {
    case "datetime":
    case "date":
    case "time":
      return clockFormat;
    default:
      return "datetime";
  }
};

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the clock props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function clockPropsDecoder(data: UnknownObject): ClockProps | never {
  if (
    typeof data.clockTimezone !== "string" ||
    data.clockTimezone.length === 0
  ) {
    throw new TypeError("invalid timezone.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: VisualConsoleItemType.CLOCK,
    clockType: parseClockType(data.clockType),
    clockFormat: parseClockFormat(data.clockFormat),
    clockTimezone: data.clockTimezone,
    clockTimezoneOffset: parseIntOr(data.clockTimezoneOffset, 0),
    showClockTimezone: parseBoolean(data.showClockTimezone),
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class Clock extends VisualConsoleItem<ClockProps> {
  public createDomElement(): HTMLElement {
    switch (this.props.clockType) {
      case "analogic":
        throw new Error("not implemented.");
      case "digital":
        return this.createDigitalClock();
    }
  }

  public createDigitalClock(): HTMLElement {
    const element: HTMLDivElement = document.createElement("div");
    element.className = "digital-clock";

    // The proportion of the clock should be (height * 2 = width) aproximately.
    const width =
      this.props.height * 2 < this.props.width
        ? this.props.height * 2
        : this.props.width;
    this.props.clockTimezone = "Madrid";
    const baseTimeFontSize = 20; // Per 100px of width.
    const dateFontSizeMultiplier = 0.5;
    const tzFontSizeMultiplier = 6 / this.props.clockTimezone.length;
    const timeFontSize = (baseTimeFontSize * width) / 100;
    const dateFontSize =
      (baseTimeFontSize * dateFontSizeMultiplier * width) / 100;
    const tzFontSize = Math.min(
      (baseTimeFontSize * tzFontSizeMultiplier * width) / 100,
      (width / 100) * 10
    );

    // Date.
    if (this.props.clockFormat === "datetime") {
      const dateElem: HTMLSpanElement = document.createElement("span");
      dateElem.className = "date";
      dateElem.textContent = this.getDigitalDate();
      dateElem.style.fontSize = `${dateFontSize}px`;
      element.append(dateElem);
    }

    // Time.
    const timeElem: HTMLSpanElement = document.createElement("span");
    timeElem.className = "time";
    timeElem.textContent = this.getDigitalTime();
    timeElem.style.fontSize = `${timeFontSize}px`;
    element.append(timeElem);

    // Timezone name.
    if (this.props.showClockTimezone) {
      const tzElem: HTMLSpanElement = document.createElement("span");
      tzElem.className = "timezone";
      tzElem.textContent = this.props.clockTimezone;
      tzElem.style.fontSize = `${tzFontSize}px`;
      element.append(tzElem);
    }

    return element;
  }

  /**
   * Generate the current date using the timezone offset stored into the properties.
   * @return The current date.
   */
  public getDate(): Date {
    const d = new Date();
    const targetTZOffset = this.props.clockTimezoneOffset * 60 * 1000; // In ms.
    const localTZOffset = d.getTimezoneOffset() * 60 * 1000; // In ms.
    const utimestamp = d.getTime() + targetTZOffset + localTZOffset;

    return new Date(utimestamp);
  }

  public getDigitalDate(initialDate: Date | null = null): string {
    const date = initialDate || this.getDate();
    // Use getDate, getDay returns the week day.
    const day = padLeft(date.getDate(), 2, 0);
    // The getMonth function returns the month starting by 0.
    const month = padLeft(date.getMonth() + 1, 2, 0);
    const year = padLeft(date.getFullYear(), 4, 0);

    // Format: 'd/m/Y'.
    return `${day}/${month}/${year}`;
  }

  public getDigitalTime(initialDate: Date | null = null): string {
    const date = initialDate || this.getDate();
    const hours = padLeft(date.getHours(), 2, 0);
    const minutes = padLeft(date.getMinutes(), 2, 0);
    const seconds = padLeft(date.getSeconds(), 2, 0);

    return `${hours}:${minutes}:${seconds}`;
  }
}
