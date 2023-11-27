import { AnyObject, WithModuleProps } from "../lib/types";

import { modulePropsDecoder, parseIntOr, stringIsEmpty, t } from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";

export type OdometerProps = {
  type: ItemType.ODOMETER;
  value: number;
  status: string;
  title: string | null;
  titleModule: string;
  titleColor: string;
  odometerType: string;
  thresholds: string | any;
  minMaxValue: string;
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
export function odometerPropsDecoder(data: AnyObject): OdometerProps | never {
  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.ODOMETER,
    value: parseIntOr(data.value, 0),
    status: stringIsEmpty(data.status) ? "#B2B2B2" : data.status,
    titleColor: stringIsEmpty(data.titleColor) ? "#3f3f3f" : data.titleColor,
    title: stringIsEmpty(data.title) ? "" : data.title,
    titleModule: stringIsEmpty(data.titleModule) ? "" : data.titleModule,
    thresholds: stringIsEmpty(data.thresholds) ? "" : data.thresholds,
    minMaxValue: stringIsEmpty(data.minMaxValue) ? "" : data.minMaxValue,
    odometerType: stringIsEmpty(data.odometerType)
      ? "percent"
      : data.odometerType,
    ...modulePropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

export default class Odometer extends Item<OdometerProps> {
  protected createDomElement(): HTMLElement {
    let lineWarning = "";
    let lineWarning2 = "";
    let lineCritical = "";
    let lineCritical2 = "";

    if (this.props.thresholds !== "") {
      const thresholds = JSON.parse(this.props.thresholds);

      if (thresholds !== null) {
        if (thresholds.min_warning != 0 || thresholds.max_warning != 0) {
          lineWarning = this.getCoords(
            thresholds.min_warning,
            this.props.width / 2
          );
          if (thresholds.max_warning == 0) {
            lineWarning2 = this.getCoords(100, this.props.width / 2);
          } else {
            lineWarning2 = this.getCoords(
              thresholds.max_warning,
              this.props.width / 2
            );
          }
        }

        if (thresholds.min_critical != 0 || thresholds.max_critical != 0) {
          lineCritical = this.getCoords(
            thresholds.min_critical,
            this.props.width / 2
          );
          if (thresholds.max_critical == 0) {
            lineCritical2 = this.getCoords(100, this.props.width / 2);
          } else {
            lineCritical2 = this.getCoords(
              thresholds.max_critical,
              this.props.width / 2
            );
          }
        }
      }
    }

    let percent = "";
    let number;
    // Float
    if (
      Number(this.props.value) === this.props.value &&
      this.props.value % 1 !== 0
    ) {
      number = this.props.value.toFixed(1);
    } else {
      if (this.props.minMaxValue === "") {
        percent = " %";
      } else {
        percent = this.getSubfix(this.props.value);
      }
      number = new Intl.NumberFormat("es", {
        maximumSignificantDigits: 4,
        maximumFractionDigits: 3
      }).format(this.props.value);
    }

    var numb = number.match(/\d*\.\d/);
    if (numb !== null) {
      number = numb[0];
    }

    const rotate = this.getRotate(this.props.value);

    let backgroundColor = document.getElementById(
      "visual-console-container"
    ) as HTMLElement;

    if (backgroundColor === null) {
      backgroundColor = document.getElementById(
        `visual-console-container-${this.props.cellId}`
      ) as HTMLElement;
    }

    if (backgroundColor.style.backgroundColor == "") {
      backgroundColor.style.backgroundColor = "#fff";
    }

    const anchoB = this.props.width * 0.7;

    const element = document.createElement("div");
    element.className = "odometer";

    if (
      this.props.agentDisabled === true ||
      this.props.moduleDisabled === true
    ) {
      element.style.opacity = "0.2";
    }

    // Odometer container.
    const odometerContainer = document.createElement("div");
    odometerContainer.className = "odometer-container";

    // Central semicircle.
    const odometerA = document.createElement("div");
    odometerA.className = "odometer-a";
    odometerA.style.backgroundColor = `${backgroundColor.style.backgroundColor}`;

    // Semicircle rotating with the value.
    const odometerB = document.createElement("div");
    odometerB.className = "odometer-b";
    odometerB.id = `odometerB-${this.props.id}`;
    odometerB.style.backgroundColor = `${this.props.status}`;

    // Dark semicircle.
    const odometerC = document.createElement("div");
    odometerC.className = "odometer-c";

    // Green outer semicircle.
    const gaugeE = document.createElement("div");
    gaugeE.className = "odometer-d";

    const SVG_NS = "http://www.w3.org/2000/svg";
    // Portion of threshold warning
    if (lineWarning != "") {
      const svgWarning = document.createElementNS(SVG_NS, "svg");
      svgWarning.setAttributeNS(null, "width", "100%");
      svgWarning.setAttributeNS(null, "height", "100%");
      svgWarning.setAttributeNS(null, "style", "position:absolute;z-index:1");
      const pathWarning = document.createElementNS(SVG_NS, "path");
      pathWarning.setAttributeNS(null, "id", `svgWarning-${this.props.id}`);
      pathWarning.setAttributeNS(
        null,
        "d",
        `M${this.props.width / 2},${this.props.width / 2}L${lineWarning}A${this
          .props.width / 2},${this.props.width / 2},0,0,1,${lineWarning2}Z`
      );
      pathWarning.setAttributeNS(null, "class", "svg_warning");
      svgWarning.appendChild(pathWarning);
      odometerContainer.appendChild(svgWarning);
    }

    // Portion of threshold critical
    if (lineCritical != "") {
      const svgCritical = document.createElementNS(SVG_NS, "svg");
      svgCritical.setAttributeNS(null, "width", "100%");
      svgCritical.setAttributeNS(null, "height", "100%");
      svgCritical.setAttributeNS(null, "style", "position:absolute;z-index:2");
      const pathCritical = document.createElementNS(SVG_NS, "path");
      pathCritical.setAttributeNS(null, "id", `svgCritical-${this.props.id}`);
      pathCritical.setAttributeNS(
        null,
        "d",
        `M${this.props.width / 2},${this.props.width / 2}L${lineCritical}A${this
          .props.width / 2},${this.props.width / 2},0,0,1,${lineCritical2}Z`
      );
      pathCritical.setAttributeNS(null, "fill", "#E63C52");
      svgCritical.appendChild(pathCritical);
      odometerContainer.appendChild(svgCritical);
    }

    // Text.
    const h1 = document.createElement("h1");
    h1.innerText = number + percent;
    h1.style.fontSize = `${anchoB * 0.17}px`;
    h1.style.color = `${this.props.status}`;
    h1.style.lineHeight = "0";

    const h2 = document.createElement("h2");
    if (this.props.title == "") {
      h2.textContent = this.truncateTitle(this.props.moduleName);
    } else {
      h2.textContent = this.truncateTitle(this.props.title);
    }
    h2.title = this.props.titleModule;
    h2.setAttribute("title", this.props.titleModule);

    h2.style.fontSize = `${anchoB * 0.06}px`;
    h2.style.color = `${this.props.titleColor}`;
    h2.style.lineHeight = "0";

    let script = document.createElement("script");
    script.type = "text/javascript";
    script.onload = () => {
      odometerB.style.transform = `rotate(${rotate}turn)`;
    };
    var urlPandora = window.location.pathname.split("/")[1];
    script.src = `${document.dir}/${urlPandora}/include/javascript/pandora_alerts.js`;
    odometerA.appendChild(h1);
    odometerA.appendChild(h2);
    odometerContainer.appendChild(odometerB);
    odometerContainer.appendChild(odometerC);
    odometerContainer.appendChild(gaugeE);
    odometerContainer.appendChild(odometerA);
    odometerContainer.appendChild(script);
    element.appendChild(odometerContainer);

    return element;
  }

  protected updateDomElement(element: HTMLElement): void {
    element.innerHTML = this.createDomElement().innerHTML;

    let rotate = this.getRotate(this.props.value);

    const svgWarning = document.getElementById(`svgWarning-${this.props.id}`);
    if (svgWarning != null) {
      svgWarning.style.display = "none";
    }

    const svgCritical = document.getElementById(`svgCritical-${this.props.id}`);
    if (svgCritical != null) {
      svgCritical.style.display = "none";
    }

    setTimeout(() => {
      if (svgWarning != null) {
        svgWarning.style.display = "block";
      }

      if (svgCritical != null) {
        svgCritical.style.display = "block";
      }

      var odometerB = document.getElementById(`odometerB-${this.props.id}`);
      if (odometerB) {
        odometerB.style.transform = `rotate(${rotate}turn)`;
      }
    }, 500);
  }

  public resizeElement(width: number): void {
    super.resizeElement(width, width / 2);
  }

  /**
   * To update the content element.
   * @override resize
   */
  public resize(width: number): void {
    this.resizeElement(this.props.width);
  }

  private getRotate(value: number): number {
    let rotate = 0;
    if (this.props.minMaxValue === "") {
      rotate = value / 2 / 100;
    } else {
      const minMax = JSON.parse(this.props.minMaxValue);
      if (minMax["min"] === value) {
        rotate = 0;
      } else if (minMax["max"] === value) {
        rotate = 0.5;
      } else {
        const limit = minMax["max"] - minMax["min"];
        const valueMax = minMax["max"] - value;
        rotate = (100 - (valueMax * 100) / limit) / 100 / 2;
      }
    }

    return rotate;
  }

  private getSubfix(value: number): string {
    let subfix = "";
    const length = (value + "").length;
    if (length > 3 && length <= 6) {
      subfix = " K";
    } else if (length > 6 && length <= 9) {
      subfix = " M";
    } else if (length > 9 && length <= 12) {
      subfix = " G";
    } else if (length > 12 && length <= 15) {
      subfix = " T";
    }

    return subfix;
  }

  private getCoords(percent: number, radio: number): string {
    if (this.props.minMaxValue !== "") {
      const minMax = JSON.parse(this.props.minMaxValue);
      if (minMax["min"] === percent) {
        percent = 0;
      } else if (minMax["max"] === percent || percent === 100) {
        percent = 100;
      } else {
        const limit = minMax["max"] - minMax["min"];
        let valueMax = minMax["max"] - percent;
        percent = 100 - (valueMax * 100) / limit;
      }
    }

    percent = 180 - percent * 1.8;
    const x = radio + Math.cos((percent * Math.PI) / 180) * radio;
    const y = radio - Math.sin((percent * Math.PI) / 180) * radio;
    return `${x},${y}`;
  }

  private truncateTitle(title: any): string {
    if (title != null && title.length > 22) {
      const halfLength = title.length / 2;
      const diff = halfLength - 9;
      const stringBefore = title.substr(0, halfLength - diff);
      const stringAfter = title.substr(halfLength + diff);

      return `${stringBefore}...${stringAfter}`;
    } else {
      return title;
    }
  }
}
