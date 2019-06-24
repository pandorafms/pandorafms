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
    agentId: parseIntOr(data.agent, null),
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
  // Object destructuring: http://es6-features.org/#ObjectMatchingShorthandNotation
  const {
    metaconsoleId,
    linkedLayoutId: id,
    linkedLayoutAgentId: agentId
  } = data;

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

  const linkedLayoutBaseProps = {
    linkedLayoutId: parseIntOr(id, null),
    linkedLayoutAgentId: parseIntOr(agentId, null),
    ...linkedLayoutStatusProps // Object spread: http://es6-features.org/#SpreadOperator
  };

  return metaconsoleId != null
    ? {
        metaconsoleId,
        ...linkedLayoutBaseProps // Object spread: http://es6-features.org/#SpreadOperator
      }
    : linkedLayoutBaseProps;
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
    isUpdating: false
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
function getOffset(el: HTMLElement | null) {
  let x = 0;
  let y = 0;
  while (el && !Number.isNaN(el.offsetLeft) && !Number.isNaN(el.offsetTop)) {
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
 *
 * @return A function which will clean the event handlers when executed.
 */
export function addMovementListener(
  element: HTMLElement,
  onMoved: (x: Position["x"], y: Position["y"]) => void
): Function {
  const container = element.parentElement as HTMLElement;
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
  const debouncedMovement = debounce(32, (x: Position["x"], y: Position["y"]) =>
    onMoved(x, y)
  );
  // Will run onMoved one time max every 16ms.
  const throttledMovement = throttle(16, (x: Position["x"], y: Position["y"]) =>
    onMoved(x, y)
  );

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
    e.stopPropagation();

    // Disable the drag temporarily.
    element.draggable = false;

    // Store the difference between the cursor and
    // the initial coordinates of the element.
    lastX = element.offsetLeft;
    lastY = element.offsetTop;
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
  const debouncedResizement = debounce(
    32,
    (width: Size["width"], height: Size["height"]) => onResized(width, height)
  );
  // Will run onResized one time max every 16ms.
  const throttledResizement = throttle(
    16,
    (width: Size["width"], height: Size["height"]) => onResized(width, height)
  );

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
