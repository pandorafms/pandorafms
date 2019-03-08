import {
  UnknownObject,
  Position,
  Size,
  WithAgentProps,
  WithModuleProps,
  LinkedVisualConsoleProps,
  LinkedVisualConsolePropsStatus
} from "./types";

/**
 * Return a number or a default value from a raw value.
 * @param value Raw value from which we will try to extract a valid number.
 * @param defaultValue Default value to use if we cannot extract a valid number.
 * @return A valid number or the default value.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function parseIntOr<T>(value: any, defaultValue: T): number | T {
  if (typeof value === "number") return value;
  if (typeof value === "string" && value.length > 0 && !isNaN(parseInt(value)))
    return parseInt(value);
  else return defaultValue;
}

/**
 * Return a not empty string or a default value from a raw value.
 * @param value Raw value from which we will try to extract a non empty string.
 * @param defaultValue Default value to use if we cannot extract a non empty string.
 * @return A non empty string or the default value.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function notEmptyStringOr<T>(value: any, defaultValue: T): string | T {
  return typeof value === "string" && value.length > 0 ? value : defaultValue;
}

/**
 * Return a boolean from a raw value.
 * @param value Raw value from which we will try to extract the boolean.
 * @return A valid boolean value. false by default.
 */
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function parseBoolean(value: any): boolean {
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
export function padLeft(
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
  // Object destructuring: http://es6-features.org/#ObjectMatchingShorthandNotation
  const { metaconsoleId, agentId: id, agentName: name } = data;

  const agentProps: WithAgentProps = {
    agentId: parseIntOr(id, null),
    agentName: typeof name === "string" && name.length > 0 ? name : null
  };

  return metaconsoleId != null
    ? {
        metaconsoleId,
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
  // Object destructuring: http://es6-features.org/#ObjectMatchingShorthandNotation
  const { moduleId: id, moduleName: name } = data;

  return {
    moduleId: parseIntOr(id, null),
    moduleName: typeof name === "string" && name.length > 0 ? name : null,
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
