import { AnyObject, WithModuleProps } from "../lib/types";
import { modulePropsDecoder, decodeBase64, stringIsEmpty, t } from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";
import { InputGroup, FormContainer } from "../Form";

export type BarsGraphProps = {
  type: ItemType.BARS_GRAPH;
  html: string;
  backgroundColor: "white" | "black" | "transparent";
  typeGraph: "horizontal" | "vertical";
  gridColor: string;
} & ItemProps &
  WithModuleProps;

/**
 * Extract a valid enum value from a raw unknown value.
 * @param BarsGraphProps Raw value.
 */
const parseBarsGraphProps = (
  backgroundColor: unknown
): BarsGraphProps["backgroundColor"] => {
  switch (backgroundColor) {
    case "white":
    case "black":
    case "transparent":
      return backgroundColor;
    default:
      return "transparent";
  }
};

/**
 * Extract a valid enum value from a raw unknown value.
 * @param typeGraph Raw value.
 */
const parseTypeGraph = (typeGraph: unknown): BarsGraphProps["typeGraph"] => {
  switch (typeGraph) {
    case "horizontal":
    case "vertical":
      return typeGraph;
    default:
      return "vertical";
  }
};

/**
 * Class to add item to the Bars graph item form
 * This item consists of a label and select background.
 * Show background is stored in the backgroundType property.
 */
class BackgroundColorInputGroup extends InputGroup<Partial<BarsGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const backgroundTypeLabel = document.createElement("label");
    backgroundTypeLabel.textContent = t("Background color");

    const options: {
      value: BarsGraphProps["backgroundColor"];
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
      this.currentData.backgroundColor ||
      this.initialData.backgroundColor ||
      "default";

    backgroundTypeSelect.addEventListener("change", event => {
      this.updateData({
        backgroundColor: parseBarsGraphProps(
          (event.target as HTMLSelectElement).value
        )
      });
    });

    backgroundTypeLabel.appendChild(backgroundTypeSelect);

    return backgroundTypeLabel;
  }
}

/**
 * Class to add item to the Bars graph item form
 * This item consists of a label and select type graph.
 * Show type is stored in the typeGraph property.
 */
class TypeGraphInputGroup extends InputGroup<Partial<BarsGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const graphTypeLabel = document.createElement("label");
    graphTypeLabel.textContent = t("Graph Type");

    const options: {
      value: BarsGraphProps["typeGraph"];
      text: string;
    }[] = [
      { value: "horizontal", text: t("Horizontal") },
      { value: "vertical", text: t("Vertical") }
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
      this.currentData.typeGraph || this.initialData.typeGraph || "vertical";

    graphTypeSelect.addEventListener("change", event => {
      this.updateData({
        typeGraph: parseTypeGraph((event.target as HTMLSelectElement).value)
      });
    });

    graphTypeLabel.appendChild(graphTypeSelect);

    return graphTypeLabel;
  }
}

/**
 * Class to add item to the BarsGraph item form
 * This item consists of a label and a color type input.
 * Element grid color is stored in the gridColor property
 */
class GridColorInputGroup extends InputGroup<Partial<BarsGraphProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const gridLabel = document.createElement("label");
    gridLabel.textContent = t("Grid color");

    const gridInput = document.createElement("input");
    gridInput.type = "color";
    gridInput.required = true;

    gridInput.value = `${this.currentData.gridColor ||
      this.initialData.gridColor ||
      "#000000"}`;

    gridInput.addEventListener("change", e => {
      this.updateData({
        gridColor: (e.target as HTMLInputElement).value
      });
    });

    gridLabel.appendChild(gridInput);

    return gridLabel;
  }
}

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the bars graph props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function barsGraphPropsDecoder(data: AnyObject): BarsGraphProps | never {
  if (stringIsEmpty(data.html) && stringIsEmpty(data.encodedHtml)) {
    throw new TypeError("missing html content.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.BARS_GRAPH,
    html: !stringIsEmpty(data.html)
      ? data.html
      : decodeBase64(data.encodedHtml),
    backgroundColor: parseBarsGraphProps(data.backgroundColor),
    typeGraph: parseTypeGraph(data.typeGraph),
    gridColor: stringIsEmpty(data.gridColor) ? "#000000" : data.gridColor,
    ...modulePropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class BarsGraph extends Item<BarsGraphProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "bars-graph";
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
   * BackgroundColorInputGroup
   * GridColorInputGroup
   * TypeGraphInputGroup
   */
  public getFormContainer(): FormContainer {
    const formContainer = super.getFormContainer();
    formContainer.addInputGroup(
      new BackgroundColorInputGroup("backgroundColor-type", this.props)
    );
    formContainer.addInputGroup(
      new TypeGraphInputGroup("type-graph", this.props)
    );
    formContainer.addInputGroup(
      new GridColorInputGroup("grid-color", this.props)
    );

    return formContainer;
  }
}
