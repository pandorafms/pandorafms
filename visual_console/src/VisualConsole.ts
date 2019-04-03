import { UnknownObject, Size } from "./types";
import {
  parseBoolean,
  sizePropsDecoder,
  parseIntOr,
  notEmptyStringOr
} from "./lib";
import Item, { ItemType, ItemProps } from "./Item";
import StaticGraph, { staticGraphPropsDecoder } from "./items/StaticGraph";
import Icon, { iconPropsDecoder } from "./items/Icon";
import ColorCloud, { colorCloudPropsDecoder } from "./items/ColorCloud";
import Group, { groupPropsDecoder } from "./items/Group";
import Clock, { clockPropsDecoder } from "./items/Clock";
import Box, { boxPropsDecoder } from "./items/Box";
import Line, { linePropsDecoder } from "./items/Line";
import Label, { labelPropsDecoder } from "./items/Label";
import SimpleValue, { simpleValuePropsDecoder } from "./items/SimpleValue";
import EventsHistory, {
  eventsHistoryPropsDecoder
} from "./items/EventsHistory";
import Percentile, { percentilePropsDecoder } from "./items/Percentile";

// Base properties.
export interface VisualConsoleProps extends Size {
  readonly id: number;
  name: string;
  groupId: number;
  backgroundURL: string | null; // URL?
  backgroundColor: string | null;
  isFavorite: boolean;
}

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the Visual Console props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function visualConsolePropsDecoder(
  data: UnknownObject
): VisualConsoleProps | never {
  // Object destructuring: http://es6-features.org/#ObjectMatchingShorthandNotation
  const {
    id,
    name,
    groupId,
    backgroundURL,
    backgroundColor,
    isFavorite
  } = data;

  if (id == null || isNaN(parseInt(id))) {
    throw new TypeError("invalid Id.");
  }
  if (typeof name !== "string" || name.length === 0) {
    throw new TypeError("invalid name.");
  }
  if (groupId == null || isNaN(parseInt(groupId))) {
    throw new TypeError("invalid group Id.");
  }

  return {
    id: parseInt(id),
    name,
    groupId: parseInt(groupId),
    backgroundURL: notEmptyStringOr(backgroundURL, null),
    backgroundColor: notEmptyStringOr(backgroundColor, null),
    isFavorite: parseBoolean(isFavorite),
    ...sizePropsDecoder(data)
  };
}

// TODO: Document.
// eslint-disable-next-line @typescript-eslint/explicit-function-return-type
function itemInstanceFrom(data: UnknownObject) {
  const type = parseIntOr(data.type, null);
  if (type == null) throw new TypeError("missing item type.");

  switch (type as ItemType) {
    case ItemType.STATIC_GRAPH:
      return new StaticGraph(staticGraphPropsDecoder(data));
    case ItemType.MODULE_GRAPH:
      throw new TypeError("item not found");
    case ItemType.SIMPLE_VALUE:
    case ItemType.SIMPLE_VALUE_MAX:
    case ItemType.SIMPLE_VALUE_MIN:
    case ItemType.SIMPLE_VALUE_AVG:
      return new SimpleValue(simpleValuePropsDecoder(data));
    case ItemType.PERCENTILE_BAR:
    case ItemType.PERCENTILE_BUBBLE:
    case ItemType.CIRCULAR_PROGRESS_BAR:
    case ItemType.CIRCULAR_INTERIOR_PROGRESS_BAR:
      return new Percentile(percentilePropsDecoder(data));
    case ItemType.LABEL:
      return new Label(labelPropsDecoder(data));
    case ItemType.ICON:
      return new Icon(iconPropsDecoder(data));
    case ItemType.SERVICE:
      throw new TypeError("item not found");
    case ItemType.GROUP_ITEM:
      return new Group(groupPropsDecoder(data));
    case ItemType.BOX_ITEM:
      return new Box(boxPropsDecoder(data));
    case ItemType.LINE_ITEM:
      return new Line(linePropsDecoder(data));
    case ItemType.AUTO_SLA_GRAPH:
      return new EventsHistory(eventsHistoryPropsDecoder(data));
    case ItemType.DONUT_GRAPH:
    case ItemType.BARS_GRAPH:
      throw new TypeError("item not found");
    case ItemType.CLOCK:
      return new Clock(clockPropsDecoder(data));
    case ItemType.COLOR_CLOUD:
      return new ColorCloud(colorCloudPropsDecoder(data));
    default:
      throw new TypeError("item not found");
  }
}

export default class VisualConsole {
  // Reference to the DOM element which will contain the items.
  private readonly containerRef: HTMLElement;
  // Properties.
  private _props: VisualConsoleProps;
  // Visual Console Item instances.
  private elements: Item<ItemProps>[] = [];

  public constructor(
    container: HTMLElement,
    props: UnknownObject,
    items: UnknownObject[]
  ) {
    this.containerRef = container;
    this._props = visualConsolePropsDecoder(props);

    // Force the first render.
    this.render();

    // TODO: Document.
    items.forEach(item => {
      try {
        const itemInstance = itemInstanceFrom(item);
        this.elements.push(itemInstance);
        itemInstance.onClick(e =>
          console.log(`Clicked element #${e.data.id}`, e)
        );
        this.containerRef.append(itemInstance.elementRef);
      } catch (error) {
        console.log("Error creating a new element:", error.message);
      }
    });

    // Sort by isOnTop, id ASC
    this.elements.sort(function(a, b) {
      if (a.props.isOnTop && !b.props.isOnTop) return 1;
      else if (!a.props.isOnTop && b.props.isOnTop) return -1;
      else if (a.props.id < b.props.id) return 1;
      else return -1;
    });
  }

  /**
   * Public accessor of the `props` property.
   * @return Properties.
   */
  public get props(): VisualConsoleProps {
    return this._props;
  }

  /**
   * Public setter of the `props` property.
   * If the new props are different enough than the
   * stored props, a render would be fired.
   * @param newProps
   */
  public set props(newProps: VisualConsoleProps) {
    const prevProps = this.props;
    // Update the internal props.
    this._props = newProps;

    // From this point, things which rely on this.props can access to the changes.

    // Re-render.
    this.render(prevProps);
  }

  /**
   * Recreate or update the HTMLElement which represents the Visual Console into the DOM.
   * @param prevProps If exists it will be used to only DOM updates instead of a full replace.
   */
  public render(prevProps: VisualConsoleProps | null = null): void {
    if (prevProps) {
      if (prevProps.backgroundURL !== this.props.backgroundURL) {
        this.containerRef.style.backgroundImage = this.props.backgroundURL;
      }
      if (prevProps.backgroundColor !== this.props.backgroundColor) {
        this.containerRef.style.backgroundColor = this.props.backgroundColor;
      }
      if (this.sizeChanged(prevProps, this.props)) {
        this.resizeElement(this.props.width, this.props.height);
      }
    } else {
      this.containerRef.style.backgroundImage = this.props.backgroundURL;
      this.containerRef.style.backgroundColor = this.props.backgroundColor;
      this.resizeElement(this.props.width, this.props.height);
    }
  }

  /**
   * Compare the previous and the new size and return
   * a boolean value in case the size changed.
   * @param prevSize
   * @param newSize
   * @return Whether the size changed or not.
   */
  public sizeChanged(prevSize: Size, newSize: Size): boolean {
    return (
      prevSize.width !== newSize.width || prevSize.height !== newSize.height
    );
  }

  /**
   * Resize the DOM container.
   * @param width
   * @param height
   */
  public resizeElement(width: number, height: number): void {
    this.containerRef.style.width = `${width}px`;
    this.containerRef.style.height = `${height}px`;
  }

  /**
   * Update the size into the properties and resize the DOM container.
   * @param width
   * @param height
   */
  public resize(width: number, height: number): void {
    this.props = {
      ...this.props, // Object spread: http://es6-features.org/#SpreadOperator
      width,
      height
    };
  }

  /**
   * To remove the event listeners and the elements from the DOM.
   */
  public remove(): void {
    this.elements.forEach(e => e.remove()); // Arrow function.
    this.elements = [];
    // Clean container.
    this.containerRef.innerHTML = "";
  }
}
