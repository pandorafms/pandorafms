import {
  LinkedVisualConsoleProps,
  UnknownObject,
  WithModuleProps
} from "../types";
import { linkedVCPropsDecoder, modulePropsDecoder, parseIntOr } from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type EventsHistoryProps = {
  type: ItemType.AUTO_SLA_GRAPH;
  maxTime: number | null;
  data: any[]; // eslint-disable-line @typescript-eslint/no-explicit-any
} & ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the simple value props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function eventsHistoryPropsDecoder(
  data: UnknownObject
): EventsHistoryProps | never {
  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.AUTO_SLA_GRAPH,
    maxTime: parseIntOr(data.maxTime, null),
    data: data.data instanceof Array ? data.data : [],
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class EventsHistory extends Item<EventsHistoryProps> {
  public createDomElement(): HTMLElement {
    throw new Error("not implemented");
  }
}
