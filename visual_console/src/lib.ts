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
export function parseIntOr<T>(value: any, defaultValue: T): number | T {
  if (typeof value === "number") return value;
  if (typeof value === "string" && value.length > 0 && isNaN(parseInt(value)))
    return parseInt(value);
  else return defaultValue;
}

/**
 * Return a boolean from a raw value.
 * @param value Raw value from which we will try to extract the boolean.
 * @return A valid boolean value. false by default.
 */
export function parseBoolean(value: any): boolean {
  if (typeof value === "boolean") return value;
  else if (typeof value === "number") return value > 0;
  else if (typeof value === "string") return value === "1" || value === "true";
  else return false;
}

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
  // Object destructuring: http://exploringjs.com/es6/ch_destructuring.html
  const { metaconsoleId, agentId: id, agentName: name } = data;

  const agentProps: WithAgentProps = {
    agentId: parseIntOr(id, null),
    agentName: typeof name === "string" && name.length > 0 ? name : null
  };

  return metaconsoleId != null
    ? {
        metaconsoleId,
        ...agentProps // Object spread: http://exploringjs.com/es6/ch_parameter-handling.html#sec_spread-operator
      }
    : agentProps;
}

/**
 * Build a valid typed object from a raw object.
 * @param data Raw object.
 * @return An object representing the module and agent properties.
 */
export function modulePropsDecoder(data: UnknownObject): WithModuleProps {
  // Object destructuring: http://exploringjs.com/es6/ch_destructuring.html
  const { moduleId: id, moduleName: name } = data;

  return {
    moduleId: parseIntOr(id, null),
    moduleName: typeof name === "string" && name.length > 0 ? name : null,
    ...agentPropsDecoder(data)
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
  // Object destructuring: http://exploringjs.com/es6/ch_destructuring.html
  const {
    metaconsoleId,
    linkedLayoutId: id,
    linkedLayoutAgentId: agentId
  } = data;

  let linkedLayoutStatusProps: LinkedVisualConsolePropsStatus = {
    linkedLayoutStatusType: "default"
  };
  switch (data.linkedLayoutStatusType) {
    case "weight":
      const weight = parseIntOr(data.linkedLayoutStatusTypeWeight, null);
      if (weight == null)
        throw new TypeError("invalid status calculation properties.");

      if (data.linkedLayoutStatusTypeWeight)
        linkedLayoutStatusProps = {
          linkedLayoutStatusType: "weight",
          linkedLayoutStatusTypeWeight: weight
        };
      break;
    case "service":
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

  const linkedLayoutBaseProps = {
    linkedLayoutId: parseIntOr(id, null),
    linkedLayoutAgentId: parseIntOr(agentId, null),
    ...linkedLayoutStatusProps // Object spread: http://exploringjs.com/es6/ch_parameter-handling.html#sec_spread-operator
  };

  return metaconsoleId != null
    ? {
        metaconsoleId,
        ...linkedLayoutBaseProps // Object spread: http://exploringjs.com/es6/ch_parameter-handling.html#sec_spread-operator
      }
    : linkedLayoutBaseProps;
}
