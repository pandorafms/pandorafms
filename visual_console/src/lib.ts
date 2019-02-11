import { Position, Size, UnknownObject } from "./types";

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
