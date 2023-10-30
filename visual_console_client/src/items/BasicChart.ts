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
    header.className = "basic-chart-header";

    const moduleName = document.createElement("h2");
    moduleName.className = "basic-chart-header-name";
    moduleName.textContent = this.props.moduleName;
    moduleName.style.color = `${this.props.moduleNameColor}`;
    header.appendChild(moduleName);

    let value = "";
    if (this.props.value !== null) {
      value = this.numberFormat(this.props.value, false, "", 2, 1000);
    }

    const moduleValue = document.createElement("h2");
    moduleValue.className = "basic-chart-header-value";
    moduleValue.textContent = `${value}`;
    moduleValue.style.color = this.props.status;
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
    header.className = "basic-chart-header";

    const moduleName = document.createElement("h2");
    moduleName.className = "basic-chart-header-name";
    moduleName.textContent = this.props.moduleName;
    moduleName.style.color = `${this.props.moduleNameColor}`;
    header.appendChild(moduleName);

    let value = "";
    if (this.props.value !== null) {
      value = this.numberFormat(this.props.value, false, "", 2, 1000);
    }

    const moduleValue = document.createElement("h2");
    moduleValue.className = "basic-chart-header-value";
    moduleValue.textContent = `${value}`;
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

  protected numberFormat(
    number: number,
    forceInteger: boolean,
    unit: string,
    shortData: number,
    divisor: number
  ) {
    divisor = typeof divisor !== "undefined" ? divisor : 1000;
    var decimals = 2;

    // Set maximum decimal precision to 99 in case shortData is not set.
    if (!shortData) {
      shortData = 99;
    }

    if (forceInteger) {
      if (Math.round(number) != number) {
        return "";
      }
    } else {
      shortData++;
      const auxDecimals = this.pad("1", shortData, 0);
      number =
        Math.round(number * Number.parseInt(auxDecimals)) /
        Number.parseInt(auxDecimals);
    }

    var shorts = ["", "K", "M", "G", "T", "P", "E", "Z", "Y"];
    var pos = 0;

    while (Math.abs(number) >= divisor) {
      // As long as the number can be divided by 1000 or 1024.
      pos++;
      number = number / divisor;
    }

    if (divisor) {
      number = Math.round(number * decimals) / decimals;
    } else {
      number = Math.round(number * decimals);
    }

    if (isNaN(number)) {
      number = 0;
    }

    return number + " " + shorts[pos] + unit;
  }

  protected pad(input: string, length: number, padding: number): string {
    var str = input + "";
    return length <= str.length
      ? str
      : this.pad(str + padding, length, padding);
  }
}
