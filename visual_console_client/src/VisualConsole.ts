import { UnknownObject, Size } from "./types";
import {
  parseBoolean,
  sizePropsDecoder,
  parseIntOr,
  notEmptyStringOr
} from "./lib";
import Item, { ItemType, ItemProps, ItemClickEvent } from "./Item";
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
import TypedEvent, { Disposable, Listener } from "./TypedEvent";
import DonutGraph, { donutGraphPropsDecoder } from "./items/DonutGraph";
import BarsGraph, { barsGraphPropsDecoder } from "./items/BarsGraph";
import ModuleGraph, { moduleGraphPropsDecoder } from "./items/ModuleGraph";

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
      return new ModuleGraph(moduleGraphPropsDecoder(data));
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
      return new DonutGraph(donutGraphPropsDecoder(data));
    case ItemType.BARS_GRAPH:
      return new BarsGraph(barsGraphPropsDecoder(data));
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
  // Visual Console Item instances by their Id.
  private elementsById: {
    [key: number]: Item<ItemProps> | null;
  } = {};
  // Visual Console Item Ids.
  private elementIds: ItemProps["id"][] = [];
  // Dictionary which store the created lines.
  private relations: {
    [key: string]: Line | null;
  } = {};
  // Event manager for click events.
  private readonly clickEventManager = new TypedEvent<
    ItemClickEvent<ItemProps>
  >();
  // List of references to clean the event listeners.
  private readonly disposables: Disposable[] = [];

  public constructor(
    container: HTMLElement,
    props: UnknownObject,
    items: UnknownObject[]
  ) {
    this.containerRef = container;
    this._props = visualConsolePropsDecoder(props);

    // Force the first render.
    this.render();

    // Sort by isOnTop, id ASC
    items = items.sort(function(a, b) {
      if (
        a.isOnTop == null ||
        b.isOnTop == null ||
        a.id == null ||
        b.id == null
      ) {
        return 0;
      }

      if (a.isOnTop && !b.isOnTop) return 1;
      else if (!a.isOnTop && b.isOnTop) return -1;
      else if (a.id < b.id) return 1;
      else return -1;
    });

    // Initialize the items.
    items.forEach(item => {
      try {
        const itemInstance = itemInstanceFrom(item);
        // Add the item to the list.
        this.elementsById[itemInstance.props.id] = itemInstance;
        this.elementIds.push(itemInstance.props.id);
        // Item event handlers.
        itemInstance.onClick(e => {
          this.clickEventManager.emit(e);
          // console.log(`Clicked element #${e.data.id}`, e);
        });
        itemInstance.onRemove(e => {
          // TODO: Remove the element from the list and its relations.
        });
        // Add the item to the DOM.
        this.containerRef.append(itemInstance.elementRef);
      } catch (error) {
        console.log("Error creating a new element:", error.message);
      }
    });

    // Create lines.
    this.buildRelations();
  }

  /**
   * Public accessor of the `elements` property.
   * @return Properties.
   */
  public get elements(): Item<ItemProps>[] {
    // Ensure the type cause Typescript doesn't know the filter removes null items.
    return this.elementIds
      .map(id => this.elementsById[id])
      .filter(_ => _ != null) as Item<ItemProps>[];
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
    this.disposables.forEach(d => d.dispose()); // Arrow function.
    this.elements.forEach(e => e.remove()); // Arrow function.
    this.elementsById = {};
    this.elementIds = [];
    // Clean container.
    this.containerRef.innerHTML = "";
  }

  /**
   * Create line elements which connect the elements with their parents.
   */
  private buildRelations(): void {
    this.elements.forEach(item => {
      if (item.props.parentId !== null) {
        const parent = this.elementsById[item.props.parentId];
        const child = this.elementsById[item.props.id];
        if (parent && child) this.addRelationLine(parent, child);
      }
    });
  }

  /**
   * Retrieve the line element which represent the relation between items.
   * @param parentId Identifier of the parent item.
   * @param childId Itentifier of the child item.
   * @return The line element or nothing.
   */
  private getRelationLine(parentId: number, childId: number): Line | null {
    const identifier = `${parentId}|${childId}`;
    return this.relations[identifier] || null;
  }

  /**
   * Add a new line item to represent a relation between the items.
   * @param parent Parent item.
   * @param child Child item.
   * @return Whether the line was added or not.
   */
  private addRelationLine(
    parent: Item<ItemProps>,
    child: Item<ItemProps>
  ): Line {
    const identifier = `${parent.props.id}|${child.props.id}`;
    if (this.relations[identifier] != null) {
      (this.relations[identifier] as Line).remove();
    }

    // Get the items center.
    const startX = parent.props.x + parent.elementRef.clientWidth / 2;
    const startY = parent.props.y + parent.elementRef.clientHeight / 2;
    const endX = child.props.x + child.elementRef.clientWidth / 2;
    const endY = child.props.y + child.elementRef.clientHeight / 2;

    const line = new Line(
      linePropsDecoder({
        id: 0,
        type: ItemType.LINE_ITEM,
        startX,
        startY,
        endX,
        endY,
        width: 0,
        height: 0
      })
    );
    // Save a reference to the line item.
    this.relations[identifier] = line;

    // Add the line to the DOM.
    line.elementRef.style.zIndex = "0";
    this.containerRef.append(line.elementRef);

    return line;
  }

  /**
   * Add an event handler to the click of the linked visual console elements.
   * @param listener Function which is going to be executed when a linked console is clicked.
   */
  public onClick(listener: Listener<ItemClickEvent<ItemProps>>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.clickEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }
}
