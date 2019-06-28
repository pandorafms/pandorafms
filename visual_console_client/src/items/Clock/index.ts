import "./styles.css";

import {
  LinkedVisualConsoleProps,
  AnyObject,
  Size,
  ItemMeta
} from "../../lib/types";
import {
  linkedVCPropsDecoder,
  parseIntOr,
  parseBoolean,
  prefixedCssRules,
  notEmptyStringOr,
  humanDate,
  humanTime
} from "../../lib";
import Item, { ItemProps, itemBasePropsDecoder, ItemType } from "../../Item";

export type ClockProps = {
  type: ItemType.CLOCK;
  clockType: "analogic" | "digital";
  clockFormat: "datetime" | "time";
  clockTimezone: string;
  clockTimezoneOffset: number; // Offset of the timezone to UTC in seconds.
  showClockTimezone: boolean;
  color?: string | null;
} & ItemProps &
  LinkedVisualConsoleProps;

/**
 * Extract a valid enum value from a raw unknown value.
 * @param clockType Raw value.
 */
const parseClockType = (clockType: unknown): ClockProps["clockType"] => {
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
const parseClockFormat = (clockFormat: unknown): ClockProps["clockFormat"] => {
  switch (clockFormat) {
    case "datetime":
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
export function clockPropsDecoder(data: AnyObject): ClockProps | never {
  if (
    typeof data.clockTimezone !== "string" ||
    data.clockTimezone.length === 0
  ) {
    throw new TypeError("invalid timezone.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.CLOCK,
    clockType: parseClockType(data.clockType),
    clockFormat: parseClockFormat(data.clockFormat),
    clockTimezone: data.clockTimezone,
    clockTimezoneOffset: parseIntOr(data.clockTimezoneOffset, 0),
    showClockTimezone: parseBoolean(data.showClockTimezone),
    color: notEmptyStringOr(data.color, null),
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class Clock extends Item<ClockProps> {
  public static readonly TICK_INTERVAL = 1000; // In ms.
  private intervalRef: number | null = null;

  public constructor(props: ClockProps, meta: ItemMeta) {
    // Call the superclass constructor.
    super(props, meta);

    /* The item is already loaded and inserted into the DOM.
     * The class properties are now initialized.
     * Now you can modify the item, add event handlers, timers, etc.
     */

    /* The use of the arrow function is important here. startTick will
     * use the function passed as an argument to call the global setInterval
     * function. The interval, timeout or event functions, among other, are
     * called into another execution loop and using a different context.
     * The arrow functions, unlike the classic functions, doesn't create
     * their own context (this), so their context at execution time will be
     * use the current context at the declaration time.
     * http://es6-features.org/#Lexicalthis
     */
    this.startTick(
      () => {
        // Replace the old element with the updated date.
        this.childElementRef.innerHTML = this.createClock().innerHTML;
      },
      /* The analogic clock doesn't need to tick,
       * but it will be refreshed every 20 seconds
       * to avoid a desync caused by page freezes.
       */
      this.props.clockType === "analogic" ? 20000 : Clock.TICK_INTERVAL
    );
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
   * @param handler Function to be called every time the interval
   * timer is reached.
   * @param interval Number in milliseconds for the interval timer.
   */
  private startTick(
    handler: TimerHandler,
    interval: number = Clock.TICK_INTERVAL
  ): void {
    this.stopTick();
    this.intervalRef = window.setInterval(handler, interval);
  }

  /**
   * Create a element which contains the DOM representation of the item.
   * @return DOM Element.
   * @override
   */
  protected createDomElement(): HTMLElement | never {
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
   * @override Item.resizeElement
   * Resize the DOM content container.
   * @param width
   * @param height
   */
  protected resizeElement(width: number, height: number): void {
    const { width: newWidth, height: newHeight } = this.getElementSize(
      width,
      height
    ); // Destructuring assigment: http://es6-features.org/#ObjectMatchingShorthandNotation
    super.resizeElement(newWidth, newHeight);
    // Re-render the item to force it calculate a new font size.
    if (this.props.clockType === "digital") {
      // Replace the old element with the updated date.
      this.childElementRef.innerHTML = this.createClock().innerHTML;
    }
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
        return this.createAnalogicClock();
      case "digital":
        return this.createDigitalClock();
      default:
        throw new Error("invalid clock type.");
    }
  }

  /**
   * Create a element which contains a representation of an analogic clock.
   * @return DOM Element.
   */
  private createAnalogicClock(): HTMLElement {
    const svgNS = "http://www.w3.org/2000/svg";
    const colors = {
      watchFace: "#FFFFF0",
      watchFaceBorder: "#242124",
      mark: "#242124",
      handDark: "#242124",
      handLight: "#525252",
      secondHand: "#DC143C"
    };

    const { width, height } = this.getElementSize(); // Destructuring assigment: http://es6-features.org/#ObjectMatchingShorthandNotation

    // Calculate font size to adapt the font to the item size.
    const baseTimeFontSize = 20; // Per 100px of width.
    const dateFontSizeMultiplier = 0.5;
    const dateFontSize =
      (baseTimeFontSize * dateFontSizeMultiplier * width) / 100;

    const div = document.createElement("div");
    div.className = "analogic-clock";
    div.style.width = `${width}px`;
    div.style.height = `${height}px`;

    // SVG container.
    const svg = document.createElementNS(svgNS, "svg");
    // Auto resize SVG using the view box magic: https://css-tricks.com/scale-svg/
    svg.setAttribute("viewBox", "0 0 100 100");

    // Clock face.
    const clockFace = document.createElementNS(svgNS, "g");
    clockFace.setAttribute("class", "clockface");
    const clockFaceBackground = document.createElementNS(svgNS, "circle");
    clockFaceBackground.setAttribute("cx", "50");
    clockFaceBackground.setAttribute("cy", "50");
    clockFaceBackground.setAttribute("r", "48");
    clockFaceBackground.setAttribute("fill", colors.watchFace);
    clockFaceBackground.setAttribute("stroke", colors.watchFaceBorder);
    clockFaceBackground.setAttribute("stroke-width", "2");
    clockFaceBackground.setAttribute("stroke-linecap", "round");
    // Insert the clockface background into the clockface group.
    clockFace.append(clockFaceBackground);

    // Timezone complication.
    const city = this.getHumanTimezone();
    if (city.length > 0) {
      const timezoneComplication = document.createElementNS(svgNS, "text");
      timezoneComplication.setAttribute("text-anchor", "middle");
      timezoneComplication.setAttribute("font-size", "8");
      timezoneComplication.setAttribute(
        "transform",
        "translate(30 50) rotate(90)" // Rotate to counter the clock rotation.
      );
      timezoneComplication.setAttribute("fill", colors.mark);
      timezoneComplication.textContent = city;
      clockFace.append(timezoneComplication);
    }

    // Marks group.
    const marksGroup = document.createElementNS(svgNS, "g");
    marksGroup.setAttribute("class", "marks");
    // Build the 12 hours mark.
    const mainMarkGroup = document.createElementNS(svgNS, "g");
    mainMarkGroup.setAttribute("class", "mark");
    mainMarkGroup.setAttribute("transform", "translate(50 50)");
    const mark1a = document.createElementNS(svgNS, "line");
    mark1a.setAttribute("x1", "36");
    mark1a.setAttribute("y1", "0");
    mark1a.setAttribute("x2", "46");
    mark1a.setAttribute("y2", "0");
    mark1a.setAttribute("stroke", colors.mark);
    mark1a.setAttribute("stroke-width", "5");
    const mark1b = document.createElementNS(svgNS, "line");
    mark1b.setAttribute("x1", "36");
    mark1b.setAttribute("y1", "0");
    mark1b.setAttribute("x2", "46");
    mark1b.setAttribute("y2", "0");
    mark1b.setAttribute("stroke", colors.watchFace);
    mark1b.setAttribute("stroke-width", "1");
    // Insert the 12 mark lines into their group.
    mainMarkGroup.append(mark1a, mark1b);
    // Insert the main mark into the marks group.
    marksGroup.append(mainMarkGroup);
    // Build the rest of the marks.
    for (let i = 1; i < 60; i++) {
      const mark = document.createElementNS(svgNS, "line");
      mark.setAttribute("y1", "0");
      mark.setAttribute("y2", "0");
      mark.setAttribute("stroke", colors.mark);
      mark.setAttribute("transform", `translate(50 50) rotate(${i * 6})`);

      if (i % 5 === 0) {
        mark.setAttribute("x1", "38");
        mark.setAttribute("x2", "46");
        mark.setAttribute("stroke-width", i % 15 === 0 ? "2" : "1");
      } else {
        mark.setAttribute("x1", "42");
        mark.setAttribute("x2", "46");
        mark.setAttribute("stroke-width", "0.5");
      }

      // Insert the mark into the marks group.
      marksGroup.append(mark);
    }

    /* Clock hands */

    // Hour hand.
    const hourHand = document.createElementNS(svgNS, "g");
    hourHand.setAttribute("class", "hour-hand");
    hourHand.setAttribute("transform", "translate(50 50)");
    // This will go back and will act like a border.
    const hourHandA = document.createElementNS(svgNS, "line");
    hourHandA.setAttribute("class", "hour-hand-a");
    hourHandA.setAttribute("x1", "0");
    hourHandA.setAttribute("y1", "0");
    hourHandA.setAttribute("x2", "30");
    hourHandA.setAttribute("y2", "0");
    hourHandA.setAttribute("stroke", colors.handLight);
    hourHandA.setAttribute("stroke-width", "4");
    hourHandA.setAttribute("stroke-linecap", "round");
    // This will go in front of the previous line.
    const hourHandB = document.createElementNS(svgNS, "line");
    hourHandB.setAttribute("class", "hour-hand-b");
    hourHandB.setAttribute("x1", "0");
    hourHandB.setAttribute("y1", "0");
    hourHandB.setAttribute("x2", "29.9");
    hourHandB.setAttribute("y2", "0");
    hourHandB.setAttribute("stroke", colors.handDark);
    hourHandB.setAttribute("stroke-width", "3.1");
    hourHandB.setAttribute("stroke-linecap", "round");
    // Append the elements to finish the hour hand.
    hourHand.append(hourHandA, hourHandB);

    // Minute hand.
    const minuteHand = document.createElementNS(svgNS, "g");
    minuteHand.setAttribute("class", "minute-hand");
    minuteHand.setAttribute("transform", "translate(50 50)");
    // This will go back and will act like a border.
    const minuteHandA = document.createElementNS(svgNS, "line");
    minuteHandA.setAttribute("class", "minute-hand-a");
    minuteHandA.setAttribute("x1", "0");
    minuteHandA.setAttribute("y1", "0");
    minuteHandA.setAttribute("x2", "40");
    minuteHandA.setAttribute("y2", "0");
    minuteHandA.setAttribute("stroke", colors.handLight);
    minuteHandA.setAttribute("stroke-width", "2");
    minuteHandA.setAttribute("stroke-linecap", "round");
    // This will go in front of the previous line.
    const minuteHandB = document.createElementNS(svgNS, "line");
    minuteHandB.setAttribute("class", "minute-hand-b");
    minuteHandB.setAttribute("x1", "0");
    minuteHandB.setAttribute("y1", "0");
    minuteHandB.setAttribute("x2", "39.9");
    minuteHandB.setAttribute("y2", "0");
    minuteHandB.setAttribute("stroke", colors.handDark);
    minuteHandB.setAttribute("stroke-width", "1.5");
    minuteHandB.setAttribute("stroke-linecap", "round");
    const minuteHandPin = document.createElementNS(svgNS, "circle");
    minuteHandPin.setAttribute("r", "3");
    minuteHandPin.setAttribute("fill", colors.handDark);
    // Append the elements to finish the minute hand.
    minuteHand.append(minuteHandA, minuteHandB, minuteHandPin);

    // Second hand.
    const secondHand = document.createElementNS(svgNS, "g");
    secondHand.setAttribute("class", "second-hand");
    secondHand.setAttribute("transform", "translate(50 50)");
    const secondHandBar = document.createElementNS(svgNS, "line");
    secondHandBar.setAttribute("x1", "0");
    secondHandBar.setAttribute("y1", "0");
    secondHandBar.setAttribute("x2", "46");
    secondHandBar.setAttribute("y2", "0");
    secondHandBar.setAttribute("stroke", colors.secondHand);
    secondHandBar.setAttribute("stroke-width", "1");
    secondHandBar.setAttribute("stroke-linecap", "round");
    const secondHandPin = document.createElementNS(svgNS, "circle");
    secondHandPin.setAttribute("r", "2");
    secondHandPin.setAttribute("fill", colors.secondHand);
    // Append the elements to finish the second hand.
    secondHand.append(secondHandBar, secondHandPin);

    // Pin.
    const pin = document.createElementNS(svgNS, "circle");
    pin.setAttribute("cx", "50");
    pin.setAttribute("cy", "50");
    pin.setAttribute("r", "0.3");
    pin.setAttribute("fill", colors.handDark);

    // Get the hand angles.
    const date = this.getOriginDate();
    const seconds = date.getSeconds();
    const minutes = date.getMinutes();
    const hours = date.getHours();
    const secAngle = (360 / 60) * seconds;
    const minuteAngle = (360 / 60) * minutes + (360 / 60) * (seconds / 60);
    const hourAngle = (360 / 12) * hours + (360 / 12) * (minutes / 60);
    // Set the clock time by moving the hands.
    hourHand.setAttribute("transform", `translate(50 50) rotate(${hourAngle})`);
    minuteHand.setAttribute(
      "transform",
      `translate(50 50) rotate(${minuteAngle})`
    );
    secondHand.setAttribute(
      "transform",
      `translate(50 50) rotate(${secAngle})`
    );

    // Build the clock
    svg.append(clockFace, marksGroup, hourHand, minuteHand, secondHand, pin);
    // Rotate the clock to its normal position.
    svg.setAttribute("transform", "rotate(-90)");

    /* Add the animation declaration to the container.
     * Since the animation keyframes need to know the
     * start angle, this angle is dynamic (current time),
     * and we can't edit keyframes through javascript
     * safely and with backwards compatibility, we need
     * to inject it.
     */
    div.innerHTML = `
      <style>
        @keyframes rotate-hour {
          from {
            ${prefixedCssRules(
              "transform",
              `translate(50px, 50px) rotate(${hourAngle}deg)`
            ).join("\n")}
          }
          to {
            ${prefixedCssRules(
              "transform",
              `translate(50px, 50px) rotate(${hourAngle + 360}deg)`
            ).join("\n")}
          }
        }
        @keyframes rotate-minute {
          from {
            ${prefixedCssRules(
              "transform",
              `translate(50px, 50px) rotate(${minuteAngle}deg)`
            ).join("\n")}
          }
          to {
            ${prefixedCssRules(
              "transform",
              `translate(50px, 50px) rotate(${minuteAngle + 360}deg)`
            ).join("\n")}
          }
        }
        @keyframes rotate-second {
          from {
            ${prefixedCssRules(
              "transform",
              `translate(50px, 50px) rotate(${secAngle}deg)`
            ).join("\n")}
          }
          to {
            ${prefixedCssRules(
              "transform",
              `translate(50px, 50px) rotate(${secAngle + 360}deg)`
            ).join("\n")}
          }
        }
      </style>
    `;
    // Add the clock to the container
    div.append(svg);

    // Date.
    if (this.props.clockFormat === "datetime") {
      const dateElem: HTMLSpanElement = document.createElement("span");
      dateElem.className = "date";
      dateElem.textContent = humanDate(date, "default");
      dateElem.style.fontSize = `${dateFontSize}px`;
      if (this.props.color) dateElem.style.color = this.props.color;
      div.append(dateElem);
    }

    return div;
  }

  /**
   * Create a element which contains a representation of a digital clock.
   * @return DOM Element.
   */
  private createDigitalClock(): HTMLElement {
    const element: HTMLDivElement = document.createElement("div");
    element.className = "digital-clock";

    const { width } = this.getElementSize(); // Destructuring assigment: http://es6-features.org/#ObjectMatchingShorthandNotation

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

    // Date calculated using the original timezone.
    const date = this.getOriginDate();

    // Date.
    if (this.props.clockFormat === "datetime") {
      const dateElem: HTMLSpanElement = document.createElement("span");
      dateElem.className = "date";
      dateElem.textContent = humanDate(date, "default");
      dateElem.style.fontSize = `${dateFontSize}px`;
      if (this.props.color) dateElem.style.color = this.props.color;
      element.append(dateElem);
    }

    // Time.
    const timeElem: HTMLSpanElement = document.createElement("span");
    timeElem.className = "time";
    timeElem.textContent = humanTime(date);
    timeElem.style.fontSize = `${timeFontSize}px`;
    if (this.props.color) timeElem.style.color = this.props.color;
    element.append(timeElem);

    // City name.
    const city = this.getHumanTimezone();
    if (city.length > 0) {
      const tzElem: HTMLSpanElement = document.createElement("span");
      tzElem.className = "timezone";
      tzElem.textContent = city;
      tzElem.style.fontSize = `${tzFontSize}px`;
      if (this.props.color) tzElem.style.color = this.props.color;
      element.append(tzElem);
    }

    return element;
  }

  /**
   * Generate the current date using the timezone offset stored into the properties.
   * @return The current date.
   */
  private getOriginDate(initialDate: Date | null = null): Date {
    const d = initialDate ? initialDate : new Date();
    const targetTZOffset = this.props.clockTimezoneOffset * 1000; // In ms.
    const localTZOffset = d.getTimezoneOffset() * 60 * 1000; // In ms.
    const utimestamp = d.getTime() + targetTZOffset + localTZOffset;

    return new Date(utimestamp);
  }

  /**
   * Extract a human readable city name from the timezone text.
   * @param timezone Timezone text.
   */
  public getHumanTimezone(timezone: string = this.props.clockTimezone): string {
    const [, city = ""] = timezone.split("/");
    return city.replace("_", " ");
  }

  /**
   * Generate a element size using the current size and the default values.
   * @return The size.
   */
  private getElementSize(
    width: number = this.props.width,
    height: number = this.props.height
  ): Size {
    switch (this.props.clockType) {
      case "analogic": {
        let diameter = 100; // Default value.

        if (width > 0 && height > 0) {
          diameter = Math.min(width, height);
        } else if (width > 0) {
          diameter = width;
        } else if (height > 0) {
          diameter = height;
        }

        return {
          width: diameter,
          height: diameter
        };
      }
      case "digital": {
        if (width > 0 && height > 0) {
          // The proportion of the clock should be (width = height / 2) aproximately.
          height = width / 2 < height ? width / 2 : height;
        } else if (width > 0) {
          height = width / 2;
        } else if (height > 0) {
          // The proportion of the clock should be (height * 2 = width) aproximately.
          width = height * 2;
        } else {
          width = 100; // Default value.
          height = 50; // Default value.
        }

        return {
          width,
          height
        };
      }
      default:
        throw new Error("invalid clock type.");
    }
  }
}
