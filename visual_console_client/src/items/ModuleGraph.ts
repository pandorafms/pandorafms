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
  t,
  parseIntOr,
  periodSelector
} from "../lib";
import Item, {
  ItemType,
  ItemProps,
  itemBasePropsDecoder,
  LinkConsoleInputGroup,
  AgentModuleInputGroup
} from "../Item";
import { FormContainer, InputGroup } from "../Form";
import fontAwesomeIcon from "../lib/FontAwesomeIcon";
import {
  faCircleNotch,
  faExclamationCircle
} from "@fortawesome/free-solid-svg-icons";

export type ModuleGraphProps = {
  type: ItemType.MODULE_GRAPH;
  html: string;
  backgroundType: "white" | "black" | "transparent";
  graphType: "line" | "area";
  period: number | null;
  customGraphId: number | null;
} & ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Extract a valid enum value from a raw unknown value.
 * @param backgroundType Raw value.
 */
const parseBackgroundType = (
  backgroundType: unknown
): ModuleGraphProps["backgroundType"] => {
  switch (backgroundType) {
    case "white":
    case "black":
    case "transparent":
      return backgroundType;
    default:
      return "transparent";
  }
};

/**
 * Extract a valid enum value from a raw unknown value.
 * @param graphType Raw value.
 */
const parseGraphType = (graphType: unknown): ModuleGraphProps["graphType"] => {
  switch (graphType) {
    case "line":
    case "area":
      return graphType;
    default:
      return "line";
  }
};

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the module graph props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function moduleGraphPropsDecoder(
  data: AnyObject
): ModuleGraphProps | never {
  if (stringIsEmpty(data.html) && stringIsEmpty(data.encodedHtml)) {
    throw new TypeError("missing html content.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.MODULE_GRAPH,
    html: !stringIsEmpty(data.html)
      ? data.html
      : decodeBase64(data.encodedHtml),
    backgroundType: parseBackgroundType(data.backgroundType),
    period: parseIntOr(data.period, null),
    graphType: parseGraphType(data.graphType),
    customGraphId: parseIntOr(data.customGraphId, null),
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

/**
 * Class to add item to the Module graph item form
 * This item consists of a label and select background.
 * Show background is stored in the backgroundType property.
 */
class BackgroundTypeInputGroup extends InputGroup<Partial<ModuleGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const generalDiv = document.createElement("div");
    generalDiv.className = "div-input-group";

    const backgroundTypeLabel = document.createElement("label");
    backgroundTypeLabel.textContent = t("Background color");

    generalDiv.appendChild(backgroundTypeLabel);

    const options: {
      value: ModuleGraphProps["backgroundType"];
      text: string;
    }[] = [
      { value: "white", text: t("White") },
      { value: "black", text: t("Black") },
      { value: "transparent", text: t("Transparent") }
    ];

    const backgroundTypeSelect = document.createElement("select");
    backgroundTypeSelect.required = true;

    options.forEach(option => {
      const optionElement = document.createElement("option");
      optionElement.value = option.value;
      optionElement.textContent = option.text;
      backgroundTypeSelect.appendChild(optionElement);
    });

    backgroundTypeSelect.value =
      this.currentData.backgroundType ||
      this.initialData.backgroundType ||
      "default";

    backgroundTypeSelect.addEventListener("change", event => {
      this.updateData({
        backgroundType: parseBackgroundType(
          (event.target as HTMLSelectElement).value
        )
      });
    });

    generalDiv.appendChild(backgroundTypeSelect);

    return generalDiv;
  }
}

/**
 * Class to add item to the Module graph item form
 * This item consists of a radio buttons.
 */
class ChooseTypeInputGroup extends InputGroup<Partial<ModuleGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const divContainer = document.createElement("div");
    divContainer.className = "div-input-group";
    const radioButtonModuleLabel = document.createElement("label");
    radioButtonModuleLabel.textContent = t("Module Graph");

    divContainer.appendChild(radioButtonModuleLabel);

    const radioButtonModule = document.createElement("input");
    radioButtonModule.type = "radio";
    radioButtonModule.name = "type-graph";
    radioButtonModule.value = "module";
    radioButtonModule.required = true;
    radioButtonModule.checked = this.initialData.customGraphId ? false : true;

    divContainer.appendChild(radioButtonModule);

    radioButtonModule.addEventListener("change", e => {
      this.updateRadioButton(
        "input-group-custom-graph-list",
        "input-group-agent-autocomplete"
      );
      //Remove Id graph custom.
      this.updateData({ customGraphId: 0 });
    });

    const radioButtonCustomLabel = document.createElement("label");
    radioButtonCustomLabel.textContent = t("Custom Graph");

    divContainer.appendChild(radioButtonCustomLabel);

    const radioButtonCustom = document.createElement("input");
    radioButtonCustom.type = "radio";
    radioButtonCustom.name = "type-graph";
    radioButtonCustom.value = "custom";
    radioButtonCustom.required = true;
    radioButtonCustom.checked = this.initialData.customGraphId ? true : false;

    divContainer.appendChild(radioButtonCustom);

    radioButtonCustom.addEventListener("change", event => {
      this.updateRadioButton(
        "input-group-agent-autocomplete",
        "input-group-custom-graph-list"
      );
    });

    return divContainer;
  }

  private updateRadioButton = (id: string, id2: string): void => {
    const itemAgentAutocomplete = document.getElementsByClassName(id);
    for (let i = 0; i < itemAgentAutocomplete.length; i++) {
      itemAgentAutocomplete[i].classList.add("hide-elements");
      itemAgentAutocomplete[i].classList.remove("show-elements");
    }
    const itemCustomGraphList = document.getElementsByClassName(id2);
    for (let i = 0; i < itemCustomGraphList.length; i++) {
      itemCustomGraphList[i].classList.add("show-elements");
      itemCustomGraphList[i].classList.remove("hide-elements");
    }
  };
}

/**
 * Class to add item to the general items form
 * This item consists of a label and a Acl Group type select.
 * Acl is stored in the aclGroupId property
 */
class CustomGraphInputGroup extends InputGroup<Partial<ModuleGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const containerGeneralDiv = document.createElement("div");
    containerGeneralDiv.className = "div-input-group-autocomplete-agent";

    const generalDiv = document.createElement("div");
    generalDiv.className = "div-input-group";

    const customGraphLabel = document.createElement("label");
    customGraphLabel.textContent = t("Custom graph");

    generalDiv.appendChild(customGraphLabel);

    const spinner = fontAwesomeIcon(faCircleNotch, t("Spinner"), {
      size: "small",
      spin: true
    });
    customGraphLabel.appendChild(spinner);

    this.requestData("custom-graph-list", {}, (error, data) => {
      // Remove Spinner.
      spinner.remove();

      if (error) {
        customGraphLabel.appendChild(
          fontAwesomeIcon(faExclamationCircle, t("Error"), {
            size: "small",
            color: "#e63c52"
          })
        );
      }

      if (data instanceof Array) {
        const customGraphSelect = document.createElement("select");

        data.forEach(option => {
          const optionElement = document.createElement("option");
          optionElement.value = option.value;
          optionElement.textContent = option.text;
          customGraphSelect.appendChild(optionElement);
        });

        customGraphSelect.value = `${this.currentData.customGraphId ||
          this.initialData.customGraphId ||
          0}`;

        customGraphSelect.addEventListener("change", event => {
          if (typeof (event.target as HTMLSelectElement).value === "string") {
            const id = (event.target as HTMLSelectElement).value.split("|")[0];
            const metaconsoleId = (event.target as HTMLSelectElement).value.split(
              "|"
            )[1];
            this.updateData({
              customGraphId: parseIntOr(id, 0),
              metaconsoleId: parseIntOr(metaconsoleId, 0)
            });
          } else {
            this.updateData({
              customGraphId: parseIntOr(
                (event.target as HTMLSelectElement).value,
                0
              )
            });
          }
        });

        generalDiv.appendChild(customGraphSelect);
      }
    });

    containerGeneralDiv.appendChild(generalDiv);

    return containerGeneralDiv;
  }
}

/*
 * Class to add item to the Module graph item form
 * This item consists of a label and select type graph.
 * Show type is stored in the  property.
 */
class GraphTypeInputGroup extends InputGroup<Partial<ModuleGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const generalDiv = document.createElement("div");
    generalDiv.className = "div-input-group";

    const graphTypeLabel = document.createElement("label");
    graphTypeLabel.textContent = t("Graph Type");

    generalDiv.appendChild(graphTypeLabel);

    const options: {
      value: ModuleGraphProps["graphType"];
      text: string;
    }[] = [
      { value: "line", text: t("Line") },
      { value: "area", text: t("Area") }
    ];

    const graphTypeSelect = document.createElement("select");
    graphTypeSelect.required = true;

    options.forEach(option => {
      const optionElement = document.createElement("option");
      optionElement.value = option.value;
      optionElement.textContent = option.text;
      graphTypeSelect.appendChild(optionElement);
    });

    graphTypeSelect.value =
      this.currentData.graphType || this.initialData.graphType || "line";

    graphTypeSelect.addEventListener("change", event => {
      this.updateData({
        graphType: parseGraphType((event.target as HTMLSelectElement).value)
      });
    });

    generalDiv.appendChild(graphTypeSelect);

    return generalDiv;
  }
}

// TODO: Document
class PeriodInputGroup extends InputGroup<Partial<ModuleGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const generalDiv = document.createElement("div");
    generalDiv.className = "div-input-group";

    const periodLabel = document.createElement("label");
    periodLabel.textContent = t("Period");

    generalDiv.appendChild(periodLabel);

    const periodControl = periodSelector(
      this.currentData.period || this.initialData.period || 300,
      null,
      [
        { text: t("5 minutes"), value: 300 },
        { text: t("30 minutes"), value: 1800 },
        { text: t("6 hours"), value: 21600 },
        { text: t("1 day"), value: 86400 },
        { text: t("1 week"), value: 604800 },
        { text: t("15 days"), value: 1296000 },
        { text: t("1 month"), value: 2592000 },
        { text: t("3 months"), value: 7776000 },
        { text: t("6 months"), value: 15552000 },
        { text: t("1 year"), value: 31104000 },
        { text: t("2 years"), value: 62208000 },
        { text: t("3 years"), value: 93312000 }
      ],
      value => this.updateData({ period: value })
    );

    generalDiv.appendChild(periodControl);

    return generalDiv;
  }
}

export default class ModuleGraph extends Item<ModuleGraphProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "module-graph";
    element.style.backgroundImage = `url(${this.props.html})`;
    element.style.backgroundRepeat = "no-repeat";
    element.style.backgroundSize = `${this.props.width}px ${
      this.props.height
    }px`;

    return element;
  }

  protected updateDomElement(element: HTMLElement): void {
    element.style.backgroundImage = `url(${this.props.html})`;
    element.style.backgroundRepeat = "no-repeat";
    element.style.backgroundSize = `${this.props.width}px ${
      this.props.height
    }px`;
  }

  /**
   * @override function to add or remove inputsGroups those that are not necessary.
   * Add to:
   * LinkConsoleInputGroup
   * PeriodInputGroup
   * GraphTypeInputGroup
   * BackgroundTypeInputGroup
   * ChooseTypeInputGroup
   * AgentModuleInputGroup
   * CustomGraphInputGroup
   */
  public getFormContainer(): FormContainer {
    return ModuleGraph.getFormContainer(this.props);
  }

  public static getFormContainer(
    props: Partial<ModuleGraphProps>
  ): FormContainer {
    const formContainer = super.getFormContainer(props);
    formContainer.addInputGroup(
      new BackgroundTypeInputGroup("background-type", props),
      3
    );
    formContainer.addInputGroup(
      new ChooseTypeInputGroup("show-type-graph", props),
      4
    );

    const displayAgent = props.customGraphId
      ? "hide-elements"
      : "show-elements";
    const displayCustom = props.customGraphId
      ? "show-elements"
      : "hide-elements ";

    formContainer.addInputGroup(
      new AgentModuleInputGroup(`agent-autocomplete ${displayAgent}`, props),
      5
    );
    formContainer.addInputGroup(
      new CustomGraphInputGroup(`custom-graph-list ${displayCustom}`, props),
      6
    );
    formContainer.addInputGroup(
      new GraphTypeInputGroup("graph-type", props),
      7
    );
    formContainer.addInputGroup(new PeriodInputGroup("period-graph", props), 8);
    formContainer.addInputGroup(
      new LinkConsoleInputGroup("link-console", props),
      16
    );

    return formContainer;
  }
}
