import { WithModuleProps, LinkedVisualConsoleProps } from "../types";

import VisualConsoleItem, {
  VisualConsoleItemProps
} from "../VisualConsoleItem";

export type StaticGraphProps = {
  imageSrc: string; // URL?
  showLastValueTooltip: "default" | "enabled" | "disabled";
} & VisualConsoleItemProps &
  (WithModuleProps | LinkedVisualConsoleProps);

export default class StaticGraph extends VisualConsoleItem<StaticGraphProps> {
  createDomElement(): HTMLElement {
    const img: HTMLImageElement = document.createElement("img");
    img.className = "static-graph";
    img.src = this.props.imageSrc;

    return img;
  }
}
