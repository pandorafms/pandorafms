import {
  AnyObject,
  Position,
  Size,
  WithAgentProps,
  WithModuleProps,
  LinkedVisualConsoleProps,
  LinkedVisualConsolePropsStatus,
  UnknownObject,
  ItemMeta
} from "./types";

import helpTipIcon from "./help-tip.png";
import fontAwesomeIcon from "./FontAwesomeIcon";
import { faPencilAlt, faListAlt } from "@fortawesome/free-solid-svg-icons";
import "./autocomplete.css";

/**
 * Return a number or a default value from a raw value.
 * @param value Raw value from which we will try to extract a valid number.
 * @param defaultValue Default value to use if we cannot extract a valid number.
 * @return A valid number or the default value.
 */
export function parseIntOr<T>(value: unknown, defaultValue: T): number | T {
  if (typeof value === "number") return value;
  if (typeof value === "string" && value.length > 0 && !isNaN(parseInt(value)))
    return parseInt(value);
  else return defaultValue;
}

/**
 * Return a number or a default value from a raw value.
 * @param value Raw value from which we will try to extract a valid number.
 * @param defaultValue Default value to use if we cannot extract a valid number.
 * @return A valid number or the default value.
 */
export function parseFloatOr<T>(value: unknown, defaultValue: T): number | T {
  if (typeof value === "number") return value;
  if (
    typeof value === "string" &&
    value.length > 0 &&
    !isNaN(parseFloat(value))
  )
    return parseFloat(value);
  else return defaultValue;
}

/**
 * Check if a string exists and it's not empty.
 * @param value Value to check.
 * @return The check result.
 */
export function stringIsEmpty(value?: string | null): boolean {
  return value == null || value.length === 0;
}

/**
 * Return a not empty string or a default value from a raw value.
 * @param value Raw value from which we will try to extract a non empty string.
 * @param defaultValue Default value to use if we cannot extract a non empty string.
 * @return A non empty string or the default value.
 */
export function notEmptyStringOr<T>(
  value: unknown,
  defaultValue: T
): string | T {
  return typeof value === "string" && value.length > 0 ? value : defaultValue;
}

/**
 * Return a boolean from a raw value.
 * @param value Raw value from which we will try to extract the boolean.
 * @return A valid boolean value. false by default.
 */
export function parseBoolean(value: unknown): boolean {
  if (typeof value === "boolean") return value;
  else if (typeof value === "number") return value > 0;
  else if (typeof value === "string") return value === "1" || value === "true";
  else return false;
}

/**
 * Return a valid date or a default value from a raw value.
 * @param value Raw value from which we will try to extract a valid date.
 * @param defaultValue Default value to use if we cannot extract a valid date.
 * @return A valid date or the default value.
 */
export function parseDateOr<T>(value: unknown, defaultValue: T): Date | T {
  if (value instanceof Date) return value;
  else if (typeof value === "number") return new Date(value * 1000);
  else if (
    typeof value === "string" &&
    !Number.isNaN(new Date(value).getTime())
  )
    return new Date(value);
  else return defaultValue;
}

/**
 * Pad the current string with another string (multiple times, if needed)
 * until the resulting string reaches the given length.
 * The padding is applied from the start (left) of the current string.
 * @param value Text that needs to be padded.
 * @param length Length of the returned text.
 * @param pad Text to add.
 * @return Padded text.
 */
export function leftPad(
  value: string | number,
  length: number,
  pad: string | number = " "
): string {
  if (typeof value === "number") value = `${value}`;
  if (typeof pad === "number") pad = `${pad}`;

  const diffLength = length - value.length;
  if (diffLength === 0) return value;
  if (diffLength < 0) return value.substr(Math.abs(diffLength));

  if (diffLength === pad.length) return `${pad}${value}`;
  if (diffLength < pad.length) return `${pad.substring(0, diffLength)}${value}`;

  const repeatTimes = Math.floor(diffLength / pad.length);
  const restLength = diffLength - pad.length * repeatTimes;

  let newPad = "";
  for (let i = 0; i < repeatTimes; i++) newPad += pad;

  if (restLength === 0) return `${newPad}${value}`;
  return `${newPad}${pad.substring(0, restLength)}${value}`;
}

/* Decoders */

/**
 * Build a valid typed object from a raw object.
 * @param data Raw object.
 * @return An object representing the position.
 */
export function positionPropsDecoder(data: AnyObject): Position {
  return {
    x: parseIntOr(data.x, 0),
    y: parseIntOr(data.y, 0)
  };
}

/**
 * Build a valid typed object from a raw object.
 * @param data Raw object.
 * @return An object representing the size.
 * @throws Will throw a TypeError if the width and height are not valid numbers.
 */
export function sizePropsDecoder(data: AnyObject): Size | never {
  if (
    data.width == null ||
    isNaN(parseInt(data.width)) ||
    data.height == null ||
    isNaN(parseInt(data.height))
  ) {
    throw new TypeError("invalid size.");
  }

  return {
    width: parseInt(data.width),
    height: parseInt(data.height)
  };
}

/**
 * Build a valid typed object from a raw object.
 * @param data Raw object.
 * @return An object representing the agent properties.
 */
export function agentPropsDecoder(data: AnyObject): WithAgentProps {
  const agentProps: WithAgentProps = {
    agentId: parseIntOr(data.agentId, null),
    agentName: notEmptyStringOr(data.agentName, null),
    agentAlias: notEmptyStringOr(data.agentAlias, null),
    agentDescription: notEmptyStringOr(data.agentDescription, null),
    agentAddress: notEmptyStringOr(data.agentAddress, null)
  };

  return data.metaconsoleId != null
    ? {
        metaconsoleId: data.metaconsoleId,
        ...agentProps // Object spread: http://es6-features.org/#SpreadOperator
      }
    : agentProps;
}

/**
 * Build a valid typed object from a raw object.
 * @param data Raw object.
 * @return An object representing the module and agent properties.
 */
export function modulePropsDecoder(data: AnyObject): WithModuleProps {
  return {
    moduleId: parseIntOr(data.moduleId, null),
    moduleName: notEmptyStringOr(data.moduleName, null),
    moduleDescription: notEmptyStringOr(data.moduleDescription, null),
    ...agentPropsDecoder(data) // Object spread: http://es6-features.org/#SpreadOperator
  };
}

/**
 * Build a valid typed object from a raw object.
 * @param data Raw object.
 * @return An object representing the linked visual console properties.
 * @throws Will throw a TypeError if the status calculation properties are invalid.
 */
export function linkedVCPropsDecoder(
  data: AnyObject
): LinkedVisualConsoleProps | never {
  let linkedLayoutStatusProps: LinkedVisualConsolePropsStatus = {
    linkedLayoutStatusType: "default"
  };
  switch (data.linkedLayoutStatusType) {
    case "weight": {
      const weight = parseIntOr(data.linkedLayoutStatusTypeWeight, null);
      if (weight == null)
        throw new TypeError("invalid status calculation properties.");

      if (data.linkedLayoutStatusTypeWeight)
        linkedLayoutStatusProps = {
          linkedLayoutStatusType: "weight",
          linkedLayoutStatusTypeWeight: weight
        };
      break;
    }
    case "service": {
      const warningThreshold = parseIntOr(
        data.linkedLayoutStatusTypeWarningThreshold,
        null
      );
      const criticalThreshold = parseIntOr(
        data.linkedLayoutStatusTypeCriticalThreshold,
        null
      );
      if (warningThreshold == null || criticalThreshold == null) {
        throw new TypeError("invalid status calculation properties.");
      }

      linkedLayoutStatusProps = {
        linkedLayoutStatusType: "service",
        linkedLayoutStatusTypeWarningThreshold: warningThreshold,
        linkedLayoutStatusTypeCriticalThreshold: criticalThreshold
      };
      break;
    }
  }

  return {
    linkedLayoutId: parseIntOr(data.linkedLayoutId, null),
    linkedLayoutNodeId: parseIntOr(data.linkedLayoutNodeId, null),
    ...linkedLayoutStatusProps // Object spread: http://es6-features.org/#SpreadOperator
  };
}

/**
 * Build a valid typed object from a raw object.
 * @param data Raw object.
 * @return An object representing the item's meta properties.
 */
export function itemMetaDecoder(data: UnknownObject): ItemMeta | never {
  const receivedAt = parseDateOr(data.receivedAt, null);
  if (receivedAt === null) throw new TypeError("invalid meta structure");

  let error = null;
  if (data.error instanceof Error) error = data.error;
  else if (typeof data.error === "string") error = new Error(data.error);

  return {
    receivedAt,
    error,
    editMode: parseBoolean(data.editMode),
    isFromCache: parseBoolean(data.isFromCache),
    isFetching: false,
    isUpdating: false,
    isBeingMoved: false,
    isBeingResized: false,
    isSelected: false,
    lineMode: false
  };
}

/**
 * To get a CSS rule with the most used prefixes.
 * @param ruleName Name of the CSS rule.
 * @param ruleValue Value of the CSS rule.
 * @return An array of rules with the prefixes applied.
 */
export function prefixedCssRules(
  ruleName: string,
  ruleValue: string
): string[] {
  const rule = `${ruleName}: ${ruleValue};`;
  return [
    `-webkit-${rule}`,
    `-moz-${rule}`,
    `-ms-${rule}`,
    `-o-${rule}`,
    `${rule}`
  ];
}

/**
 * Decode a base64 string.
 * @param input Data encoded using base64.
 * @return Decoded data.
 */
export function decodeBase64(input: string): string {
  return decodeURIComponent(escape(window.atob(input)));
}

/**
 * Generate a date representation with the format 'd/m/Y'.
 * @param initialDate Date to be used instead of a generated one.
 * @param locale Locale to use if localization is required and available.
 * @example 24/02/2020.
 * @return Date representation.
 */
export function humanDate(date: Date, locale: string | null = null): string {
  if (locale && Intl && Intl.DateTimeFormat) {
    // Format using the user locale.
    const options: Intl.DateTimeFormatOptions = {
      day: "2-digit",
      month: "2-digit",
      year: "numeric"
    };
    return Intl.DateTimeFormat(locale, options).format(date);
  } else {
    // Use getDate, getDay returns the week day.
    const day = leftPad(date.getDate(), 2, 0);
    // The getMonth function returns the month starting by 0.
    const month = leftPad(date.getMonth() + 1, 2, 0);
    const year = leftPad(date.getFullYear(), 4, 0);

    // Format: 'd/m/Y'.
    return `${day}/${month}/${year}`;
  }
}

/**
 * Generate a time representation with the format 'hh:mm:ss'.
 * @param initialDate Date to be used instead of a generated one.
 * @example 01:34:09.
 * @return Time representation.
 */
export function humanTime(date: Date): string {
  const hours = leftPad(date.getHours(), 2, 0);
  const minutes = leftPad(date.getMinutes(), 2, 0);
  const seconds = leftPad(date.getSeconds(), 2, 0);

  return `${hours}:${minutes}:${seconds}`;
}

interface Macro {
  macro: string | RegExp;
  value: string;
}
/**
 * Replace the macros of a text.
 * @param macros List of macros and their replacements.
 * @param text Text in which we need to replace the macros.
 */
export function replaceMacros(macros: Macro[], text: string): string {
  return macros.reduce(
    (acc, { macro, value }) => acc.replace(macro, value),
    text
  );
}

/**
 * Create a function which will limit the rate of execution of
 * the selected function to one time for the selected interval.
 * @param delay Interval.
 * @param fn Function to be executed at a limited rate.
 */
export function throttle<T, R>(delay: number, fn: (...args: T[]) => R) {
  let last = 0;
  return (...args: T[]) => {
    const now = Date.now();
    if (now - last < delay) return;
    last = now;
    return fn(...args);
  };
}

/**
 * Create a function which will call the selected function only
 * after the interval time has passed after its last execution.
 * @param delay Interval.
 * @param fn Function to be executed after the last call.
 */
export function debounce<T>(delay: number, fn: (...args: T[]) => void) {
  let timerRef: number | null = null;
  return (...args: T[]) => {
    if (timerRef !== null) window.clearTimeout(timerRef);
    timerRef = window.setTimeout(() => {
      fn(...args);
      timerRef = null;
    }, delay);
  };
}

/**
 * Retrieve the offset of an element relative to the page.
 * @param el Node used to calculate the offset.
 */
function getOffset(el: HTMLElement | null, parent?: HTMLElement) {
  let x = 0;
  let y = 0;
  while (
    el &&
    !Number.isNaN(el.offsetLeft) &&
    !Number.isNaN(el.offsetTop) &&
    el !== parent
  ) {
    x += el.offsetLeft - el.scrollLeft;
    y += el.offsetTop - el.scrollTop;
    el = el.offsetParent as HTMLElement | null;
  }
  return { top: y, left: x };
}

/**
 * Add the grab & move functionality to a certain element inside it's container.
 *
 * @param element Element to move.
 * @param onMoved Function to execute when the element moves.
 * @param altContainer Alternative element to contain the moved element.
 *
 * @return A function which will clean the event handlers when executed.
 */
export function addMovementListener(
  element: HTMLElement,
  onMoved: (x: Position["x"], y: Position["y"]) => void,
  altContainer?: HTMLElement
): Function {
  const container = altContainer || (element.parentElement as HTMLElement);

  // Store the initial draggable state.
  const isDraggable = element.draggable;
  // Init the coordinates.
  let lastX: Position["x"] = 0;
  let lastY: Position["y"] = 0;
  let lastMouseX: Position["x"] = 0;
  let lastMouseY: Position["y"] = 0;
  let mouseElementOffsetX: Position["x"] = 0;
  let mouseElementOffsetY: Position["y"] = 0;
  // Bounds.
  let containerBounds = container.getBoundingClientRect();
  let containerOffset = getOffset(container);
  let containerTop = containerOffset.top;
  let containerBottom = containerTop + containerBounds.height;
  let containerLeft = containerOffset.left;
  let containerRight = containerLeft + containerBounds.width;
  let elementBounds = element.getBoundingClientRect();
  let borderWidth = window.getComputedStyle(element).borderWidth || "0";
  let borderFix = Number.parseInt(borderWidth) * 2;

  // Will run onMoved 32ms after its last execution.
  const debouncedMovement = debounce(32, onMoved);
  // Will run onMoved one time max every 16ms.
  const throttledMovement = throttle(16, onMoved);

  const handleMove = (e: MouseEvent) => {
    // Calculate the new element coordinates.
    let x = 0;
    let y = 0;

    const mouseX = e.pageX;
    const mouseY = e.pageY;
    const mouseDeltaX = mouseX - lastMouseX;
    const mouseDeltaY = mouseY - lastMouseY;

    const minX = 0;
    const maxX = containerBounds.width - elementBounds.width + borderFix;
    const minY = 0;
    const maxY = containerBounds.height - elementBounds.height + borderFix;

    const outOfBoundsLeft =
      mouseX < containerLeft ||
      (lastX === 0 &&
        mouseDeltaX > 0 &&
        mouseX < containerLeft + mouseElementOffsetX);
    const outOfBoundsRight =
      mouseX > containerRight ||
      mouseDeltaX + lastX + elementBounds.width - borderFix >
        containerBounds.width ||
      (lastX === maxX &&
        mouseDeltaX < 0 &&
        mouseX > containerLeft + maxX + mouseElementOffsetX);
    const outOfBoundsTop =
      mouseY < containerTop ||
      (lastY === 0 &&
        mouseDeltaY > 0 &&
        mouseY < containerTop + mouseElementOffsetY);
    const outOfBoundsBottom =
      mouseY > containerBottom ||
      mouseDeltaY + lastY + elementBounds.height - borderFix >
        containerBounds.height ||
      (lastY === maxY &&
        mouseDeltaY < 0 &&
        mouseY > containerTop + maxY + mouseElementOffsetY);

    if (outOfBoundsLeft) x = minX;
    else if (outOfBoundsRight) x = maxX;
    else x = mouseDeltaX + lastX;

    if (outOfBoundsTop) y = minY;
    else if (outOfBoundsBottom) y = maxY;
    else y = mouseDeltaY + lastY;

    if (x < 0) x = minX;
    if (y < 0) y = minY;

    // Store the last mouse coordinates.
    lastMouseX = mouseX;
    lastMouseY = mouseY;

    if (x === lastX && y === lastY) return;

    // Run the movement events.
    throttledMovement(x, y);
    debouncedMovement(x, y);

    // Store the coordinates of the element.
    lastX = x;
    lastY = y;
  };
  const handleEnd = () => {
    // Reset the positions.
    lastX = 0;
    lastY = 0;
    lastMouseX = 0;
    lastMouseY = 0;
    // Remove the move event.
    document.removeEventListener("mousemove", handleMove);
    // Clean itself.
    document.removeEventListener("mouseup", handleEnd);
    // Reset the draggable property to its initial state.
    element.draggable = isDraggable;
    // Reset the body selection property to a default state.
    document.body.style.userSelect = "auto";
  };
  const handleStart = (e: MouseEvent) => {
    // Avoid starting the movement on right click.
    if (e.button === 2) return;

    e.stopPropagation();

    // Disable the drag temporarily.
    element.draggable = false;

    // Store the difference between the cursor and
    // the initial coordinates of the element.
    const elementOffset = getOffset(element, container);
    lastX = elementOffset.left;
    lastY = elementOffset.top;

    // Store the mouse position.
    lastMouseX = e.pageX;
    lastMouseY = e.pageY;
    // Store the relative position between the mouse and the element.
    mouseElementOffsetX = e.offsetX;
    mouseElementOffsetY = e.offsetY;

    // Initialize the bounds.
    containerBounds = container.getBoundingClientRect();
    containerOffset = getOffset(container);
    containerTop = containerOffset.top;
    containerBottom = containerTop + containerBounds.height;
    containerLeft = containerOffset.left;
    containerRight = containerLeft + containerBounds.width;
    elementBounds = element.getBoundingClientRect();
    borderWidth = window.getComputedStyle(element).borderWidth || "0";
    borderFix = Number.parseInt(borderWidth) * 2;

    // Listen to the mouse movement.
    document.addEventListener("mousemove", handleMove);
    // Listen to the moment when the mouse click is not pressed anymore.
    document.addEventListener("mouseup", handleEnd);
    // Limit the mouse selection of the body.
    document.body.style.userSelect = "none";
  };

  // Event to listen the init of the movement.
  element.addEventListener("mousedown", handleStart);

  // Returns a function to clean the event listeners.
  return () => {
    element.removeEventListener("mousedown", handleStart);
    handleEnd();
  };
}

/**
 * Add the grab & resize functionality to a certain element.
 *
 * @param element Element to move.
 * @param onResized Function to execute when the element is resized.
 *
 * @return A function which will clean the event handlers when executed.
 */
export function addResizementListener(
  element: HTMLElement,
  onResized: (x: Position["x"], y: Position["y"]) => void
): Function {
  const minWidth = 15;
  const minHeight = 15;

  const resizeDraggable = document.createElement("div");
  resizeDraggable.className = "resize-draggable";
  element.appendChild(resizeDraggable);

  // Container of the resizable element.
  const container = element.parentElement as HTMLElement;
  // Store the initial draggable state.
  const isDraggable = element.draggable;
  // Init the coordinates.
  let lastWidth: Size["width"] = 0;
  let lastHeight: Size["height"] = 0;
  let lastMouseX: Position["x"] = 0;
  let lastMouseY: Position["y"] = 0;
  let mouseElementOffsetX: Position["x"] = 0;
  let mouseElementOffsetY: Position["y"] = 0;
  // Init the bounds.
  let containerBounds = container.getBoundingClientRect();
  let containerOffset = getOffset(container);
  let containerTop = containerOffset.top;
  let containerBottom = containerTop + containerBounds.height;
  let containerLeft = containerOffset.left;
  let containerRight = containerLeft + containerBounds.width;
  let elementOffset = getOffset(element);
  let elementTop = elementOffset.top;
  let elementLeft = elementOffset.left;
  let borderWidth = window.getComputedStyle(element).borderWidth || "0";
  let borderFix = Number.parseInt(borderWidth);

  // Will run onResized 32ms after its last execution.
  const debouncedResizement = debounce(32, onResized);
  // Will run onResized one time max every 16ms.
  const throttledResizement = throttle(16, onResized);

  const handleResize = (e: MouseEvent) => {
    // Calculate the new element coordinates.
    let width = lastWidth + (e.pageX - lastMouseX);
    let height = lastHeight + (e.pageY - lastMouseY);

    if (width === lastWidth && height === lastHeight) return;

    if (
      width < lastWidth &&
      e.pageX > elementLeft + (lastWidth - mouseElementOffsetX)
    )
      return;

    if (width < minWidth) {
      // Minimum value.
      width = minWidth;
    } else if (width + elementLeft - borderFix / 2 >= containerRight) {
      // Limit the size to the container.
      width = containerRight - elementLeft;
    }
    if (height < minHeight) {
      // Minimum value.
      height = minHeight;
    } else if (height + elementTop - borderFix / 2 >= containerBottom) {
      // Limit the size to the container.
      height = containerBottom - elementTop;
    }

    // Run the movement events.
    throttledResizement(width, height);
    debouncedResizement(width, height);

    // Store the coordinates of the element.
    lastWidth = width;
    lastHeight = height;
    // Store the last mouse coordinates.
    lastMouseX = e.pageX;
    lastMouseY = e.pageY;
  };
  const handleEnd = () => {
    // Reset the positions.
    lastWidth = 0;
    lastHeight = 0;
    lastMouseX = 0;
    lastMouseY = 0;
    mouseElementOffsetX = 0;
    mouseElementOffsetY = 0;
    // Remove the move event.
    document.removeEventListener("mousemove", handleResize);
    // Clean itself.
    document.removeEventListener("mouseup", handleEnd);
    // Reset the draggable property to its initial state.
    element.draggable = isDraggable;
    // Reset the body selection property to a default state.
    document.body.style.userSelect = "auto";
  };
  const handleStart = (e: MouseEvent) => {
    e.stopPropagation();

    // Disable the drag temporarily.
    element.draggable = false;

    // Store the difference between the cursor and
    // the initial coordinates of the element.
    const { width, height } = element.getBoundingClientRect();
    lastWidth = width;
    lastHeight = height;
    // Store the mouse position.
    lastMouseX = e.pageX;
    lastMouseY = e.pageY;
    // Store the relative position between the mouse and the element.
    mouseElementOffsetX = e.offsetX;
    mouseElementOffsetY = e.offsetY;

    // Initialize the bounds.
    containerBounds = container.getBoundingClientRect();
    containerOffset = getOffset(container);
    containerTop = containerOffset.top;
    containerBottom = containerTop + containerBounds.height;
    containerLeft = containerOffset.left;
    containerRight = containerLeft + containerBounds.width;
    elementOffset = getOffset(element);
    elementTop = elementOffset.top;
    elementLeft = elementOffset.left;

    // Listen to the mouse movement.
    document.addEventListener("mousemove", handleResize);
    // Listen to the moment when the mouse click is not pressed anymore.
    document.addEventListener("mouseup", handleEnd);
    // Limit the mouse selection of the body.
    document.body.style.userSelect = "none";
  };

  // Event to listen the init of the movement.
  resizeDraggable.addEventListener("mousedown", handleStart);

  // Returns a function to clean the event listeners.
  return () => {
    resizeDraggable.remove();
    handleEnd();
  };
}

// TODO: Document and code
export function t(text: string): string {
  return text;
}

export function helpTip(text: string): HTMLElement {
  const container = document.createElement("a");
  container.className = "tip";
  const icon = document.createElement("img");
  icon.src = helpTipIcon;
  icon.className = "forced_title";
  icon.setAttribute("alt", text);
  icon.setAttribute("data-title", text);
  icon.setAttribute("data-use_title_for_force_title", "1");

  container.appendChild(icon);

  return container;
}

interface PeriodSelectorOption {
  value: number;
  text: string;
}
export function periodSelector(
  selectedValue: PeriodSelectorOption["value"] | null,
  emptyOption: PeriodSelectorOption | null,
  options: PeriodSelectorOption[],
  onChange: (value: PeriodSelectorOption["value"]) => void
): HTMLElement {
  if (selectedValue === null) selectedValue = 0;
  const initialValue = emptyOption ? emptyOption.value : 0;
  let currentValue: number =
    selectedValue != null ? selectedValue : initialValue;
  // Main container.
  const container = document.createElement("div");
  // Container for the period selector.
  const periodsContainer = document.createElement("div");
  const selectPeriods = document.createElement("select");
  const useManualPeriodsBtn = document.createElement("a");
  // Container for the custom period input.
  const manualPeriodsContainer = document.createElement("div");
  const inputTimeValue = document.createElement("input");
  const unitsSelect = document.createElement("select");
  const usePeriodsBtn = document.createElement("a");
  // Units to multiply the custom period input.
  const unitOptions: { value: string; text: string }[] = [
    { value: "1", text: t("Seconds").toLowerCase() },
    { value: "60", text: t("Minutes").toLowerCase() },
    { value: "3600", text: t("Hours").toLowerCase() },
    { value: "86400", text: t("Days").toLowerCase() },
    { value: "604800", text: t("Weeks").toLowerCase() },
    { value: `${86400 * 30}`, text: t("Months").toLowerCase() },
    { value: `${86400 * 30 * 12}`, text: t("Years").toLowerCase() }
  ];

  // Will be executed every time the value changes.
  const handleChange = (value: number) => {
    currentValue = value;
    onChange(currentValue);
  };
  // Will return the first period option smaller than the value.
  const findPeriodsOption = (value: number) =>
    options
      .sort((a, b) => (a.value < b.value ? 1 : -1))
      .find(optionVal => value >= optionVal.value);
  // Will return the first multiple of the value using the custom input multipliers.
  const findManualPeriodsOptionValue = (value: number) =>
    unitOptions
      .map(unitOption => Number.parseInt(unitOption.value))
      .sort((a, b) => (a < b ? 1 : -1))
      .find(optionVal => value % optionVal === 0);
  // Will find and set a valid option for the period selector.
  const setPeriodsValue = (value: number) => {
    let option = findPeriodsOption(value);
    selectPeriods.value = `${option ? option.value : initialValue}`;
  };
  // Will transform the value to show the perfect fit for the custom input period.
  const setManualPeriodsValue = (value: number) => {
    const optionVal = findManualPeriodsOptionValue(value);
    if (optionVal) {
      inputTimeValue.value = `${value / optionVal}`;
      unitsSelect.value = `${optionVal}`;
    } else {
      inputTimeValue.value = `${value}`;
      unitsSelect.value = "1";
    }
  };

  // Will modify the value to show the perfect fit for this element and show its container.
  const showPeriods = () => {
    let option = findPeriodsOption(currentValue);
    const newValue = option ? option.value : initialValue;
    selectPeriods.value = `${newValue}`;

    if (newValue !== currentValue) handleChange(newValue);

    container.replaceChild(periodsContainer, manualPeriodsContainer);
  };
  // Will modify the value to show the perfect fit for this element and show its container.
  const showManualPeriods = () => {
    const optionVal = findManualPeriodsOptionValue(currentValue);

    if (optionVal) {
      inputTimeValue.value = `${currentValue / optionVal}`;
      unitsSelect.value = `${optionVal}`;
    } else {
      inputTimeValue.value = `${currentValue}`;
      unitsSelect.value = "1";
    }

    container.replaceChild(manualPeriodsContainer, periodsContainer);
  };

  // Append the elements

  periodsContainer.appendChild(selectPeriods);
  periodsContainer.appendChild(useManualPeriodsBtn);

  manualPeriodsContainer.appendChild(inputTimeValue);
  manualPeriodsContainer.appendChild(unitsSelect);
  manualPeriodsContainer.appendChild(usePeriodsBtn);

  if (
    options.find(option => option.value === selectedValue) ||
    (emptyOption && emptyOption.value === selectedValue)
  ) {
    // Start with the custom periods select.
    container.appendChild(periodsContainer);
  } else {
    // Start with the manual time input
    container.appendChild(manualPeriodsContainer);
  }

  // Set and fill the elements.

  // Periods selector.

  selectPeriods.addEventListener("change", (e: Event) =>
    handleChange(
      parseIntOr((e.target as HTMLSelectElement).value, initialValue)
    )
  );
  if (emptyOption) {
    const optionElem = document.createElement("option");
    optionElem.value = `${emptyOption.value}`;
    optionElem.text = emptyOption.text;
    selectPeriods.appendChild(optionElem);
  }
  options.forEach(option => {
    const optionElem = document.createElement("option");
    optionElem.value = `${option.value}`;
    optionElem.text = option.text;
    selectPeriods.appendChild(optionElem);
  });

  setPeriodsValue(selectedValue);

  useManualPeriodsBtn.appendChild(
    fontAwesomeIcon(faPencilAlt, t("Show manual period input"), {
      size: "small"
    })
  );
  useManualPeriodsBtn.addEventListener("click", e => {
    e.preventDefault();
    showManualPeriods();
  });

  // Manual periods input.

  inputTimeValue.type = "number";
  inputTimeValue.min = "0";
  inputTimeValue.required = true;
  inputTimeValue.addEventListener("change", (e: Event) =>
    handleChange(
      parseIntOr((e.target as HTMLSelectElement).value, 0) *
        parseIntOr(unitsSelect.value, 1)
    )
  );
  // Select for time units.
  unitsSelect.addEventListener("change", (e: Event) =>
    handleChange(
      parseIntOr(inputTimeValue.value, 0) *
        parseIntOr((e.target as HTMLSelectElement).value, 1)
    )
  );
  unitOptions.forEach(option => {
    const optionElem = document.createElement("option");
    optionElem.value = `${option.value}`;
    optionElem.text = option.text;
    unitsSelect.appendChild(optionElem);
  });

  setManualPeriodsValue(selectedValue);

  usePeriodsBtn.appendChild(
    fontAwesomeIcon(faListAlt, t("Show periods selector"), { size: "small" })
  );
  usePeriodsBtn.addEventListener("click", e => {
    e.preventDefault();
    showPeriods();
  });

  return container;
}

/**
 * Cuts the text if their length is greater than the selected max length
 * and applies the selected ellipse to the result text.
 * @param str Text to cut
 * @param max Maximum length after cutting the text
 * @param ellipse String to be added to the cutted text
 * @returns Full text or text cutted with the ellipse
 */
export function ellipsize(
  str: string,
  max: number = 140,
  ellipse: string = "…"
): string {
  return str.trim().length > max ? str.substr(0, max).trim() + ellipse : str;
}

// TODO: Document
export function autocompleteInput<T>(
  initialValue: string | null,
  onDataRequested: (value: string, done: (data: T[]) => void) => void,
  renderListElement: (data: T) => HTMLElement,
  onSelected: (data: T) => string
): HTMLElement {
  const container = document.createElement("div");
  container.classList.add("autocomplete");

  const input = document.createElement("input");
  input.type = "text";
  input.required = true;
  if (initialValue !== null) input.value = initialValue;

  const list = document.createElement("div");
  list.classList.add("autocomplete-items");

  const cleanList = () => {
    list.innerHTML = "";
  };

  input.addEventListener("keyup", e => {
    const value = (e.target as HTMLInputElement).value;
    if (value) {
      onDataRequested(value, data => {
        cleanList();
        if (data instanceof Array) {
          data.forEach(item => {
            const listElement = renderListElement(item);
            listElement.addEventListener("click", () => {
              input.value = onSelected(item);
              cleanList();
            });
            list.appendChild(listElement);
          });
        }
      });
    } else {
      cleanList();
    }
  });

  container.appendChild(input);
  container.appendChild(list);

  return container;
}
