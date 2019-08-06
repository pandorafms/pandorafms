import {
  LinkedVisualConsoleProps,
  AnyObject,
  WithModuleProps
} from "../lib/types";
import {
  linkedVCPropsDecoder,
  modulePropsDecoder,
  decodeBase64,
  stringIsEmpty,
  t
} from "../lib";
import Item, {
  ItemType,
  ItemProps,
  itemBasePropsDecoder,
  LinkConsoleInputGroup
} from "../Item";
import { FormContainer, InputGroup } from "../Form";

export type DonutGraphProps = {
  type: ItemType.DONUT_GRAPH;
  html: string;
  legendBackgroundColor: string;
} & ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the donut graph props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function donutGraphPropsDecoder(
  data: AnyObject
): DonutGraphProps | never {
  if (stringIsEmpty(data.html) && stringIsEmpty(data.encodedHtml)) {
    throw new TypeError("missing html content.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.DONUT_GRAPH,
    html: !stringIsEmpty(data.html)
      ? data.html
      : decodeBase64(data.encodedHtml),
    legendBackgroundColor: stringIsEmpty(data.legendBackgroundColor)
      ? "#000000"
      : data.legendBackgroundColor,
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

/**
 * Class to add item to the DonutsGraph item form
 * This item consists of a label and a color type input.
 * Element color is stored in the legendBackgroundColor property
 */
class LegendBackgroundColorInputGroup extends InputGroup<
  Partial<DonutGraphProps>
> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const legendBackgroundLabel = document.createElement("label");
    legendBackgroundLabel.textContent = t("Resume data color");

    const legendBackgroundInput = document.createElement("input");
    legendBackgroundInput.type = "color";
    legendBackgroundInput.required = true;

    legendBackgroundInput.value = `${this.currentData.legendBackgroundColor ||
      this.initialData.legendBackgroundColor ||
      "#000000"}`;

    legendBackgroundInput.addEventListener("change", e => {
      this.updateData({
        legendBackgroundColor: (e.target as HTMLInputElement).value
      });
    });

    legendBackgroundLabel.appendChild(legendBackgroundInput);

    return legendBackgroundLabel;
  }
}

export default class DonutGraph extends Item<DonutGraphProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "donut-graph";
    element.innerHTML = this.props.html;

    // Hack to execute the JS after the HTML is added to the DOM.
    const scripts = element.getElementsByTagName("script");
    for (let i = 0; i < scripts.length; i++) {
      setTimeout(() => {
        if (scripts[i].src.length === 0) eval(scripts[i].innerHTML.trim());
      }, 0);
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
   * LinkConsoleInputGroup
   */
  public getFormContainer(): FormContainer {
    const formContainer = super.getFormContainer();
    formContainer.addInputGroup(
      new LinkConsoleInputGroup("link-console", this.props)
    );
    formContainer.addInputGroup(
      new LegendBackgroundColorInputGroup("legend-background-color", this.props)
    );
    return formContainer;
  }
}
