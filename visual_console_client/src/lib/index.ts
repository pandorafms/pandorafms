import {
  UnknownObject,
  Position,
  Size,
  WithAgentProps,
  WithModuleProps,
  LinkedVisualConsoleProps,
  LinkedVisualConsolePropsStatus
} from "../types";

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
export function positionPropsDecoder(data: UnknownObject): Position {
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
export function sizePropsDecoder(data: UnknownObject): Size | never {
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
export function agentPropsDecoder(data: UnknownObject): WithAgentProps {
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
export function modulePropsDecoder(data: UnknownObject): WithModuleProps {
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
  data: UnknownObject
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
