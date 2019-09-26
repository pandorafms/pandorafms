import {
  WithModuleProps,
  LinkedVisualConsoleProps,
  AnyObject
} from "../lib/types";

import {
  modulePropsDecoder,
  linkedVCPropsDecoder,
  notEmptyStringOr,
  t
} from "../lib";
import Item, {
  ItemType,
  ItemProps,
  itemBasePropsDecoder,
  LinkConsoleInputGroup,
  ImageInputGroup,
  AgentModuleInputGroup
} from "../Item";
import { InputGroup, FormContainer } from "../Form";

export type StaticGraphProps = {
  type: ItemType.STATIC_GRAPH;
  imageSrc: string; // URL?
  showLastValueTooltip: "default" | "enabled" | "disabled";
  statusImageSrc: string | null; // URL?
  lastValue: string | null;
} & ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Extract a valid enum value from a raw unknown value.
 * @param showLastValueTooltip Raw value.
 */
const parseShowLastValueTooltip = (
  showLastValueTooltip: unknown
): StaticGraphProps["showLastValueTooltip"] => {
  switch (showLastValueTooltip) {
    case "default":
    case "enabled":
    case "disabled":
      return showLastValueTooltip;
    default:
      return "default";
  }
};

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the static graph props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function staticGraphPropsDecoder(
  data: AnyObject
): StaticGraphProps | never {
  if (typeof data.imageSrc !== "string" || data.imageSrc.length === 0) {
    throw new TypeError("invalid image src.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.STATIC_GRAPH,
    imageSrc: data.imageSrc,
    showLastValueTooltip: parseShowLastValueTooltip(data.showLastValueTooltip),
    statusImageSrc: notEmptyStringOr(data.statusImageSrc, null),
    lastValue: notEmptyStringOr(data.lastValue, null),
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

/**
 * Class to add item to the static Graph item form
 * This item consists of a label and a Show last value select.
 * Show Last Value is stored in the showLastValueTooltip property
 */
class ShowLastValueInputGroup extends InputGroup<Partial<StaticGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const generalDiv = document.createElement("div");
    generalDiv.className = "div-input-group";

    const showLastValueLabel = document.createElement("label");
    showLastValueLabel.textContent = t("Show Last Value");

    generalDiv.appendChild(showLastValueLabel);

    const options: {
      value: StaticGraphProps["showLastValueTooltip"];
      text: string;
    }[] = [
      { value: "default", text: t("Hide last value on boolean modules") },
      { value: "disabled", text: t("Disabled") },
      { value: "enabled", text: t("Enabled") }
    ];

    const showLastValueSelect = document.createElement("select");
    showLastValueSelect.required = true;

    const currentValue =
      this.currentData.showLastValueTooltip ||
      this.initialData.showLastValueTooltip ||
      "default";

    options.forEach(option => {
      const optionElement = document.createElement("option");
      optionElement.value = option.value;
      optionElement.textContent = option.text;
      if (currentValue == optionElement.value) {
        optionElement.selected = true;
      }
      showLastValueSelect.appendChild(optionElement);
    });

    showLastValueSelect.addEventListener("change", event => {
      this.updateData({
        showLastValueTooltip: parseShowLastValueTooltip(
          (event.target as HTMLSelectElement).value
        )
      });
    });

    generalDiv.appendChild(showLastValueSelect);

    return generalDiv;
  }
}

export default class StaticGraph extends Item<StaticGraphProps> {
  protected createDomElement(): HTMLElement {
    const imgSrc = this.props.statusImageSrc || this.props.imageSrc;
    const element = document.createElement("div");
    element.className = "static-graph";
    element.style.backgroundImage = `url(${imgSrc})`;
    element.style.backgroundRepeat = "no-repeat";
    element.style.backgroundSize = "contain";
    element.style.backgroundPosition = "center";

    // Show last value in a tooltip.
    if (
      this.props.lastValue !== null &&
      this.props.showLastValueTooltip !== "disabled"
    ) {
      element.className = "static-graph image forced_title";
      element.setAttribute("data-use_title_for_force_title", "1");
      element.setAttribute("data-title", this.props.lastValue);
    }

    return element;
  }

  /**
   * To update the content element.
   * @override Item.updateDomElement
   */
  protected updateDomElement(element: HTMLElement): void {
    const imgSrc = this.props.statusImageSrc || this.props.imageSrc;
    element.style.backgroundImage = `url(${imgSrc})`;
  }

  /**
   * @override function to add or remove inputsGroups those that are not necessary.
   * Add to:
   * ImageInputGroup
   * ShowLastValueInputGroup
   * LinkConsoleInputGroup
   * AgentModuleInputGroup
   */
  public getFormContainer(): FormContainer {
    const formContainer = super.getFormContainer();
    formContainer.addInputGroup(
      new ImageInputGroup("image-console", {
        ...this.props,
        imageKey: "imageSrc",
        showStatusImg: true
      }),
      3
    );
    formContainer.addInputGroup(
      new AgentModuleInputGroup("agent-autocomplete", this.props),
      4
    );
    formContainer.addInputGroup(
      new ShowLastValueInputGroup("show-last-value", this.props),
      5
    );
    formContainer.addInputGroup(
      new LinkConsoleInputGroup("link-console", this.props),
      13
    );
    return formContainer;
  }
}
