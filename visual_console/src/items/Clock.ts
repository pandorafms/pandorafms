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
  public static readonly TICK_INTERVAL: number = 1000; // In ms.
  private intervalRef: number | null = null;

  public constructor(props: ClockProps) {
    super(props);
    // The item is already loaded and inserted into the DOM.

    // Below you can modify the item, add event handlers, timers, etc.

    /* The use of the arrow function is important here. startTick will
     * use the function passed as an argument to call the global setInterval
     * function. The interval, timeout or event functions, among other, are
     * called into another execution loop and using a different context.
     * The arrow functions, unlike the classic functions, doesn't create
     * their own context (this), so their context at execution time will be
     * use the current context at the declaration time.
     * http://es6-features.org/#Lexicalthis
     */
    this.startTick(() => {
      // Replace the old element with the updated date.
      this.childElementRef.innerHTML = this.createClock().innerHTML;
    });
  }

  /**
   * Wrap a window.clearInterval call.
   */
  private stopTick(): void {
    if (this.intervalRef !== null) {
      window.clearInterval(this.intervalRef);
      this.intervalRef = null;
    }
  }

  /**
   * Wrap a window.setInterval call.
   */
  private startTick(handler: TimerHandler): void {
    this.stopTick();
    this.intervalRef = window.setInterval(handler, Clock.TICK_INTERVAL);
  }

  /**
   * Create a element which contains the DOM representation of the item.
   * @return DOM Element.
   * @override
   */
  public createDomElement(): HTMLElement | never {
    return this.createClock();
  }

  /**
   * To remove the event listeners and the elements from the DOM.
   * @override
   */
  public remove(): void {
    // Clear the interval.
    this.stopTick();
    // Call to the parent clean function.
    super.remove();
  }

  /**
   * @override VisualConsoleItem.resize
   * To resize the item.
   * @param width Width.
   * @param height Height.
   */
  public resize(width: number, height: number): void {
    super.resize(width, height);
    // Re-render the item to force it calculate a new font size.
    this.render();
  }

  /**
   * Create a element which contains a representation of a clock.
   * It choose between the clock types.
   * @return DOM Element.
   * @throws Error.
   */
  private createClock(): HTMLElement | never {
    switch (this.props.clockType) {
      case "analogic":
        throw new Error("not implemented.");
      case "digital":
        return this.createDigitalClock();
      default:
        throw new Error("invalid clock type.");
    }
  }

  /**
   * Create a element which contains a representation of a digital clock.
   * @return DOM Element.
   */
  private createDigitalClock(): HTMLElement {
    const element: HTMLDivElement = document.createElement("div");
    element.className = "digital-clock";

    // The proportion of the clock should be (height * 2 = width) aproximately.
    const width =
      this.props.height * 2 < this.props.width
        ? this.props.height * 2
        : this.props.width;

    // Calculate font size to adapt the font to the item size.
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
  private getDate(): Date {
    const d = new Date();
    const targetTZOffset = this.props.clockTimezoneOffset * 60 * 1000; // In ms.
    const localTZOffset = d.getTimezoneOffset() * 60 * 1000; // In ms.
    const utimestamp = d.getTime() + targetTZOffset + localTZOffset;

    return new Date(utimestamp);
  }

  /**
   * Generate a date representation with the format 'd/m/Y'.
   * e.g.: 24/02/2020.
   * @return Date representation.
   */
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

  /**
   * Generate a time representation with the format 'hh:mm:ss'.
   * e.g.: 01:34:09.
   * @return Time representation.
   */
  public getDigitalTime(initialDate: Date | null = null): string {
    const date = initialDate || this.getDate();
    const hours = padLeft(date.getHours(), 2, 0);
    const minutes = padLeft(date.getMinutes(), 2, 0);
    const seconds = padLeft(date.getSeconds(), 2, 0);

    return `${hours}:${minutes}:${seconds}`;
  }
}
