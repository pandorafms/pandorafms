import { AnyObject, WithModuleProps } from "../lib/types";
import {
  modulePropsDecoder,
  parseIntOr,
  decodeBase64,
  stringIsEmpty,
  t
} from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";
import { InputGroup, FormContainer } from "../Form";

export type EventsHistoryProps = {
  type: ItemType.AUTO_SLA_GRAPH;
  maxTime: number | null;
  html: string;
} & ItemProps &
  WithModuleProps;

/**
 * Class to add item to the Event History item form
 * This item consists of a label and select time.
 * Show time is stored in the maxTime property.
 */
class MaxTimeInputGroup extends InputGroup<Partial<EventsHistoryProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const maxTimeLabel = document.createElement("label");
    maxTimeLabel.textContent = t("Max. Time");

    const options: {
      value: string;
      text: string;
    }[] = [
      { value: "86400", text: "24h" },
      { value: "43200", text: "12h" },
      { value: "28800", text: "8h" },
      { value: "7200", text: "2h" },
      { value: "3600", text: "1h" }
    ];

    const maxTimeSelect = document.createElement("select");
    maxTimeSelect.required = true;

    options.forEach(option => {
      const optionElement = document.createElement("option");
      optionElement.value = option.value;
      optionElement.textContent = option.text;
      maxTimeSelect.appendChild(optionElement);
    });

    maxTimeSelect.value = `${this.currentData.maxTime ||
      this.initialData.maxTime ||
      "86400"}`;

    maxTimeSelect.addEventListener("change", event => {
      this.updateData({
        maxTime: parseIntOr((event.target as HTMLSelectElement).value, 0)
      });
    });

    maxTimeLabel.appendChild(maxTimeSelect);

    return maxTimeLabel;
  }
}

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

  /**
   * @override function to add or remove inputsGroups those that are not necessary.
   * Add to:
   * MaxTimeInputGroup
   */
  public getFormContainer(): FormContainer {
    const formContainer = super.getFormContainer();
    formContainer.addInputGroup(new MaxTimeInputGroup("max-time", this.props));

    return formContainer;
  }
}
