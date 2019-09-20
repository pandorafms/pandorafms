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
    const backgroundTypeLabel = document.createElement("label");
    backgroundTypeLabel.textContent = t("Background color");

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

    backgroundTypeLabel.appendChild(backgroundTypeSelect);

    return backgroundTypeLabel;
  }
}

/**
 * Class to add item to the Module graph item form
 * This item consists of a radio buttons.
 */
class ChooseTypeInputGroup extends InputGroup<Partial<ModuleGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const divContainer = document.createElement("div");
    const radioButtonModuleLabel = document.createElement("label");
    radioButtonModuleLabel.textContent = t("Module Graph");

    divContainer.appendChild(radioButtonModuleLabel);

    const radioButtonModule = document.createElement("input");
    radioButtonModule.type = "radio";
    radioButtonModule.name = "type-graph";
    radioButtonModule.value = "module";
    radioButtonModule.required = true;

    divContainer.appendChild(radioButtonModule);

    radioButtonModule.addEventListener("change", event => {
      const show = document.getElementsByClassName(
        "input-group-agent-autocomplete"
      );
      for (let i = 0; i < show.length; i++) {
        show[i].classList.add("show-elements");
        show[i].classList.remove("hide-elements");
      }
    });

    const radioButtonCustomLabel = document.createElement("label");
    radioButtonCustomLabel.textContent = t("Custom Graph");

    divContainer.appendChild(radioButtonCustomLabel);

    const radioButtonCustom = document.createElement("input");
    radioButtonCustom.type = "radio";
    radioButtonCustom.name = "type-graph";
    radioButtonCustom.value = "module";
    radioButtonCustom.required = true;

    divContainer.appendChild(radioButtonCustom);

    radioButtonCustom.addEventListener("change", event => {
      const show = document.getElementsByClassName(
        "input-group-agent-autocomplete"
      );
      for (let i = 0; i < show.length; i++) {
        show[i].classList.add("hide-elements");
        show[i].classList.remove("show-elements");
      }
    });

    /*
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
    */

    return divContainer;
  }
}

/**
 * Class to add item to the general items form
 * This item consists of a label and a Acl Group type select.
 * Acl is stored in the aclGroupId property
 */
class CustomGraphInputGroup extends InputGroup<Partial<ItemProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const customGraphLabel = document.createElement("label");
    customGraphLabel.textContent = t("Custom graph");

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
        customGraphSelect.required = true;

        data.forEach(option => {
          const optionElement = document.createElement("option");
          optionElement.value = option.value;
          optionElement.textContent = option.text;
          customGraphSelect.appendChild(optionElement);
        });

        /*
        customGraphSelect.addEventListener("change", event => {
          this.updateData({
            aclGroupId: parseIntOr((event.target as HTMLSelectElement).value, 0)
          });
        });

        customGraphSelect.value = `${this.currentData.aclGroupId ||
          this.initialData.aclGroupId ||
          0}`;
          */

        customGraphLabel.appendChild(customGraphSelect);
      }
    });

    return customGraphLabel;
  }
}

/*
 * Class to add item to the Module graph item form
 * This item consists of a label and select type graph.
 * Show type is stored in the  property.
 */
class GraphTypeInputGroup extends InputGroup<Partial<ModuleGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const graphTypeLabel = document.createElement("label");
    graphTypeLabel.textContent = t("Graph Type");

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

    graphTypeLabel.appendChild(graphTypeSelect);

    return graphTypeLabel;
  }
}

// TODO: Document
class PeriodInputGroup extends InputGroup<Partial<ModuleGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const periodLabel = document.createElement("label");
    periodLabel.textContent = t("Period");

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

    periodLabel.appendChild(periodControl);

    return periodLabel;
  }
}

export default class ModuleGraph extends Item<ModuleGraphProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "module-graph";
    element.innerHTML = this.props.html;

    // Remove the overview graph.
    const legendP = element.getElementsByTagName("p");
    for (let i = 0; i < legendP.length; i++) {
      legendP[i].style.margin = "0px";
    }

    // Remove the overview graph.
    const overviewGraphs = element.getElementsByClassName("overview_graph");
    for (let i = 0; i < overviewGraphs.length; i++) {
      overviewGraphs[i].remove();
    }

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

    // Remove the overview graph.
    const legendP = element.getElementsByTagName("p");
    for (let i = 0; i < legendP.length; i++) {
      legendP[i].style.margin = "0px";
    }

    // Remove the overview graph.
    const overviewGraphs = element.getElementsByClassName("overview_graph");
    for (let i = 0; i < overviewGraphs.length; i++) {
      overviewGraphs[i].remove();
    }

    // Hack to execute the JS after the HTML is added to the DOM.
    const scripts = element.getElementsByTagName("script");
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
   * PeriodInputGroup
   * GraphTypeInputGroup
   * BackgroundTypeInputGroup
   */
  public getFormContainer(): FormContainer {
    const formContainer = super.getFormContainer();
    formContainer.addInputGroup(
      new BackgroundTypeInputGroup("background-type", this.props)
    );
    formContainer.addInputGroup(
      new GraphTypeInputGroup("graph-type", this.props)
    );
    formContainer.addInputGroup(
      new PeriodInputGroup("period-graph", this.props)
    );
    formContainer.addInputGroup(
      new LinkConsoleInputGroup("link-console", this.props)
    );
    formContainer.addInputGroup(
      new ChooseTypeInputGroup("show-type-graph", this.props)
    );
    formContainer.addInputGroup(
      new AgentModuleInputGroup("agent-autocomplete", this.props)
    );
    formContainer.addInputGroup(
      new CustomGraphInputGroup("custom-graph-list", this.props)
    );
    return formContainer;
  }
}
