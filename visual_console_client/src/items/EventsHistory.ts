import { AnyObject, WithModuleProps } from "../lib/types";
import {
  modulePropsDecoder,
  parseIntOr,
  decodeBase64,
  stringIsEmpty
} from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type EventsHistoryProps = {
  type: ItemType.AUTO_SLA_GRAPH;
  maxTime: number | null;
  html: string;
} & ItemProps &
  WithModuleProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the events history props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function eventsHistoryPropsDecoder(
  data: AnyObject
): EventsHistoryProps | never {
  if (stringIsEmpty(data.html) && stringIsEmpty(data.encodedHtml)) {
    throw new TypeError("missing html content.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.AUTO_SLA_GRAPH,
    maxTime: parseIntOr(data.maxTime, null),
    html: !stringIsEmpty(data.html)
      ? data.html
      : decodeBase64(data.encodedHtml),
    ...modulePropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class EventsHistory extends Item<EventsHistoryProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "events-history";
    element.innerHTML = this.props.html;

    // Hack to execute the JS after the HTML is added to the DOM.
    const scripts = element.getElementsByTagName("script");
    for (let i = 0; i < scripts.length; i++) {
      if (scripts[i].src.length === 0) {
        setTimeout(() => {
          try {
            eval(scripts[i].innerHTML.trim());
          } catch (ignored) {} // eslint-disable-line no-empty
        }, 0);
      }
    }

    return element;
  }

  protected updateDomElement(element: HTMLElement): void {
    element.innerHTML = this.props.html;

    // Hack to execute the JS after the HTML is added to the DOM.
    const aux = document.createElement("div");
    aux.innerHTML = this.props.html;
    const scripts = aux.getElementsByTagName("script");
    for (let i = 0; i < scripts.length; i++) {
      if (scripts[i].src.length === 0) {
        eval(scripts[i].innerHTML.trim());
      }
    }
  }
}
