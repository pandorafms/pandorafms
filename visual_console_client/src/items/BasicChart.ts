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
  parseIntOr
} from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type BasicChartProps = {
  type: ItemType.BASIC_CHART;
  html: string;
  period: number | null;
  value: number | null;
  status: string;
  moduleNameColor: string;
} & ItemProps &
  WithModuleProps &
  LinkedVisualConsoleProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the basic chart props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function basicChartPropsDecoder(
  data: AnyObject
): BasicChartProps | never {
  if (stringIsEmpty(data.html) && stringIsEmpty(data.encodedHtml)) {
    throw new TypeError("missing html content.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.BASIC_CHART,
    html: !stringIsEmpty(data.html)
      ? data.html
      : decodeBase64(data.encodedHtml),
    period: parseIntOr(data.period, null),
    value: parseFloat(data.value),
    status: stringIsEmpty(data.status) ? "#B2B2B2" : data.status,
    moduleNameColor: stringIsEmpty(data.moduleNameColor)
      ? "#3f3f3f"
      : data.moduleNameColor,
    ...modulePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...linkedVCPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class BasicChart extends Item<BasicChartProps> {
  protected createDomElement(): HTMLElement {
    const element = document.createElement("div");
    const header = document.createElement("div");

    header.style.height = "40%";
    header.style.width = "100%";
    header.style.display = "flex";

    const moduleName = document.createElement("h2");
    moduleName.textContent = this.props.moduleName;
    moduleName.style.margin = "0";
    moduleName.style.padding = "0";
    moduleName.style.height = "100%";
    moduleName.style.width = "80%";
    moduleName.style.display = "flex";
    moduleName.style.alignItems = "center";
    moduleName.style.fontSize = `2.5vmin`;
    moduleName.style.marginLeft = "3%";
    moduleName.style.color = `${this.props.moduleNameColor}`;
    header.appendChild(moduleName);

    const moduleValue = document.createElement("h2");
    moduleValue.textContent = `${this.props.value}`;
    moduleValue.style.margin = "0";
    moduleValue.style.padding = "0";
    moduleValue.style.height = "100%";
    moduleValue.style.width = "20%";
    moduleValue.style.display = "flex";
    moduleValue.style.alignItems = "center";
    moduleValue.style.justifyContent = "center";
    moduleValue.style.fontSize = `2.5vmin`;
    moduleValue.style.color = this.props.status;
    moduleValue.style.textDecoration = "none !important";
    header.appendChild(moduleValue);

    element.innerHTML = this.props.html;
    element.className = "basic-chart";
    if (
      this.props.agentDisabled === true ||
      this.props.moduleDisabled === true
    ) {
      element.style.opacity = "0.2";
    }

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

    element.innerHTML = this.props.html;
    element.insertBefore(header, element.firstChild);

    return element;
  }

  protected updateDomElement(element: HTMLElement): void {
    const header = document.createElement("div");
    header.style.height = "40%";
    header.style.width = "100%";
    header.style.display = "flex";

    const moduleName = document.createElement("h2");
    moduleName.textContent = this.props.moduleName;
    moduleName.style.margin = "0";
    moduleName.style.padding = "0";
    moduleName.style.height = "100%";
    moduleName.style.width = "80%";
    moduleName.style.display = "flex";
    moduleName.style.alignItems = "center";
    moduleName.style.fontSize = `2.5vmin`;
    moduleName.style.marginLeft = "3%";
    moduleName.style.color = `${this.props.moduleNameColor}`;
    header.appendChild(moduleName);

    const moduleValue = document.createElement("h2");
    moduleValue.textContent = `${this.props.value}`;
    moduleValue.style.margin = "0";
    moduleValue.style.padding = "0";
    moduleValue.style.height = "100%";
    moduleValue.style.width = "20%";
    moduleValue.style.display = "flex";
    moduleValue.style.alignItems = "center";
    moduleValue.style.justifyContent = "center";
    moduleValue.style.fontSize = `2.5vmin`;
    moduleValue.style.color = this.props.status;
    header.appendChild(moduleValue);

    element.innerHTML = this.props.html;
    element.insertBefore(header, element.firstChild);

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
}
