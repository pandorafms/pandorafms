import { AnyObject, Size } from "./lib/types";
import {
  parseBoolean,
  sizePropsDecoder,
  parseIntOr,
  notEmptyStringOr,
  itemMetaDecoder
} from "./lib";
import Item, {
  ItemType,
  ItemProps,
  ItemClickEvent,
  ItemRemoveEvent,
  ItemMovedEvent,
  ItemResizedEvent
} from "./Item";
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
import TypedEvent, { Disposable, Listener } from "./lib/TypedEvent";
import DonutGraph, { donutGraphPropsDecoder } from "./items/DonutGraph";
import BarsGraph, { barsGraphPropsDecoder } from "./items/BarsGraph";
import ModuleGraph, { moduleGraphPropsDecoder } from "./items/ModuleGraph";
import Service, { servicePropsDecoder } from "./items/Service";

// TODO: Document.
// eslint-disable-next-line @typescript-eslint/explicit-function-return-type
function itemInstanceFrom(data: AnyObject) {
  const type = parseIntOr(data.type, null);
  if (type == null) throw new TypeError("missing item type.");

  const meta = itemMetaDecoder(data);

  switch (type as ItemType) {
    case ItemType.STATIC_GRAPH:
      return new StaticGraph(staticGraphPropsDecoder(data), meta);
    case ItemType.MODULE_GRAPH:
      return new ModuleGraph(moduleGraphPropsDecoder(data), meta);
    case ItemType.SIMPLE_VALUE:
    case ItemType.SIMPLE_VALUE_MAX:
    case ItemType.SIMPLE_VALUE_MIN:
    case ItemType.SIMPLE_VALUE_AVG:
      return new SimpleValue(simpleValuePropsDecoder(data), meta);
    case ItemType.PERCENTILE_BAR:
    case ItemType.PERCENTILE_BUBBLE:
    case ItemType.CIRCULAR_PROGRESS_BAR:
    case ItemType.CIRCULAR_INTERIOR_PROGRESS_BAR:
      return new Percentile(percentilePropsDecoder(data), meta);
    case ItemType.LABEL:
      return new Label(labelPropsDecoder(data), meta);
    case ItemType.ICON:
      return new Icon(iconPropsDecoder(data), meta);
    case ItemType.SERVICE:
      return new Service(servicePropsDecoder(data), meta);
    case ItemType.GROUP_ITEM:
      return new Group(groupPropsDecoder(data), meta);
    case ItemType.BOX_ITEM:
      return new Box(boxPropsDecoder(data), meta);
    case ItemType.LINE_ITEM:
      return new Line(linePropsDecoder(data), meta);
    case ItemType.AUTO_SLA_GRAPH:
      return new EventsHistory(eventsHistoryPropsDecoder(data), meta);
    case ItemType.DONUT_GRAPH:
      return new DonutGraph(donutGraphPropsDecoder(data), meta);
    case ItemType.BARS_GRAPH:
      return new BarsGraph(barsGraphPropsDecoder(data), meta);
    case ItemType.CLOCK:
      return new Clock(clockPropsDecoder(data), meta);
    case ItemType.COLOR_CLOUD:
      return new ColorCloud(colorCloudPropsDecoder(data), meta);
    default:
      throw new TypeError("item not found");
  }
}

// TODO: Document.
// eslint-disable-next-line @typescript-eslint/explicit-function-return-type
function decodeProps(data: AnyObject) {
  const type = parseIntOr(data.type, null);
  if (type == null) throw new TypeError("missing item type.");

  switch (type as ItemType) {
    case ItemType.STATIC_GRAPH:
      return staticGraphPropsDecoder(data);
    case ItemType.MODULE_GRAPH:
      return moduleGraphPropsDecoder(data);
    case ItemType.SIMPLE_VALUE:
    case ItemType.SIMPLE_VALUE_MAX:
    case ItemType.SIMPLE_VALUE_MIN:
    case ItemType.SIMPLE_VALUE_AVG:
      return simpleValuePropsDecoder(data);
    case ItemType.PERCENTILE_BAR:
    case ItemType.PERCENTILE_BUBBLE:
    case ItemType.CIRCULAR_PROGRESS_BAR:
    case ItemType.CIRCULAR_INTERIOR_PROGRESS_BAR:
      return percentilePropsDecoder(data);
    case ItemType.LABEL:
      return labelPropsDecoder(data);
    case ItemType.ICON:
      return iconPropsDecoder(data);
    case ItemType.SERVICE:
      return servicePropsDecoder(data);
    case ItemType.GROUP_ITEM:
      return groupPropsDecoder(data);
    case ItemType.BOX_ITEM:
      return boxPropsDecoder(data);
    case ItemType.LINE_ITEM:
      return linePropsDecoder(data);
    case ItemType.AUTO_SLA_GRAPH:
      return eventsHistoryPropsDecoder(data);
    case ItemType.DONUT_GRAPH:
      return donutGraphPropsDecoder(data);
    case ItemType.BARS_GRAPH:
      return barsGraphPropsDecoder(data);
    case ItemType.CLOCK:
      return clockPropsDecoder(data);
    case ItemType.COLOR_CLOUD:
      return colorCloudPropsDecoder(data);
    default:
      throw new TypeError("decoder not found");
  }
}

// Base properties.
export interface VisualConsoleProps extends Size {
  readonly id: number;
  name: string;
  groupId: number;
  backgroundURL: string | null; // URL?
  backgroundColor: string | null;
  isFavorite: boolean;
  relationLineWidth: number;
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
  data: AnyObject
): VisualConsoleProps | never {
  // Object destructuring: http://es6-features.org/#ObjectMatchingShorthandNotation
  const {
    id,
    name,
    groupId,
    backgroundURL,
    backgroundColor,
    isFavorite,
    relationLineWidth
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
    relationLineWidth: parseIntOr(relationLineWidth, 0),
    ...sizePropsDecoder(data)
  };
}

export default class VisualConsole {
  // Reference to the DOM element which will contain the items.
  private readonly containerRef: HTMLElement;
  // Properties.
  private _props: VisualConsoleProps;
  // Visual Console Item instances by their Id.
  private elementsById: {
    [key: number]: Item<ItemProps>;
  } = {};
  // Visual Console Item Ids.
  private elementIds: ItemProps["id"][] = [];
  // Dictionary which store the created lines.
  private relations: {
    [key: string]: Line;
  } = {};
  // Event manager for click events.
  private readonly clickEventManager = new TypedEvent<
    ItemClickEvent<ItemProps>
  >();
  // Event manager for move events.
  private readonly movedEventManager = new TypedEvent<ItemMovedEvent>();
  // Event manager for resize events.
  private readonly resizedEventManager = new TypedEvent<ItemResizedEvent>();
  // List of references to clean the event listeners.
  private readonly disposables: Disposable[] = [];

  /**
   * React to a click on an element.
   * @param e Event object.
   */
  private handleElementClick: (e: ItemClickEvent<ItemProps>) => void = e => {
    this.clickEventManager.emit(e);
    // console.log(`Clicked element #${e.data.id}`, e);
  };

  /**
   * React to a movement on an element.
   * @param e Event object.
   */
  private handleElementMovement: (e: ItemMovedEvent) => void = e => {
    this.movedEventManager.emit(e);
    // console.log(`Moved element #${e.item.props.id}`, e);
  };

  /**
   * React to a resizement on an element.
   * @param e Event object.
   */
  private handleElementResizement: (e: ItemResizedEvent) => void = e => {
    this.resizedEventManager.emit(e);
    // console.log(`Resized element #${e.item.props.id}`, e);
  };

  /**
   * Clear some element references.
   * @param e Event object.
   */
  private handleElementRemove: (e: ItemRemoveEvent<ItemProps>) => void = e => {
    // Remove the element from the list and its relations.
    this.elementIds = this.elementIds.filter(id => id !== e.data.id);
    delete this.elementsById[e.data.id];
    this.clearRelations(e.data.id);
  };

  public constructor(
    container: HTMLElement,
    props: AnyObject,
    items: AnyObject[]
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
      else if (a.id > b.id) return 1;
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
        itemInstance.onClick(this.handleElementClick);
        itemInstance.onMoved(this.handleElementMovement);
        itemInstance.onResized(this.handleElementResizement);
        itemInstance.onRemove(this.handleElementRemove);
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
   * Public setter of the `elements` property.
   * @param items.
   */
  public updateElements(items: AnyObject[]): void {
    // Ensure the type cause Typescript doesn't know the filter removes null items.
    const itemIds = items
      .map(item => item.id || null)
      .filter(id => id != null) as number[];
    // Get the elements we should delete.
    const deletedIds = this.elementIds.filter(id => itemIds.indexOf(id) < 0);
    // Delete the elements.
    deletedIds.forEach(id => {
      if (this.elementsById[id] != null) {
        this.elementsById[id].remove();
        delete this.elementsById[id];
      }
    });
    // Replace the element ids.
    this.elementIds = itemIds;

    // Initialize the items.
    items.forEach(item => {
      if (item.id) {
        if (this.elementsById[item.id] == null) {
          // New item.
          try {
            const itemInstance = itemInstanceFrom(item);
            // Add the item to the list.
            this.elementsById[itemInstance.props.id] = itemInstance;
            // Item event handlers.
            itemInstance.onClick(this.handleElementClick);
            itemInstance.onRemove(this.handleElementRemove);
            // Add the item to the DOM.
            this.containerRef.append(itemInstance.elementRef);
          } catch (error) {
            console.log("Error creating a new element:", error.message);
          }
        } else {
          // Update item.
          try {
            this.elementsById[item.id].props = decodeProps(item);
          } catch (error) {
            console.log("Error updating an element:", error.message);
          }
        }
      }
    });

    // Re-build relations.
    this.buildRelations();
  }

  /**
   * Public accessor of the `props` property.
   * @return Properties.
   */
  public get props(): VisualConsoleProps {
    return { ...this._props }; // Return a copy.
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
        this.containerRef.style.backgroundImage =
          this.props.backgroundURL !== null
            ? `url(${this.props.backgroundURL})`
            : null;
      }
      if (prevProps.backgroundColor !== this.props.backgroundColor) {
        this.containerRef.style.backgroundColor = this.props.backgroundColor;
      }
      if (this.sizeChanged(prevProps, this.props)) {
        this.resizeElement(this.props.width, this.props.height);
      }
    } else {
      this.containerRef.style.backgroundImage =
        this.props.backgroundURL !== null
          ? `url(${this.props.backgroundURL})`
          : null;

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
    // Clear relations.
    this.clearRelations();
    // Clean container.
    this.containerRef.innerHTML = "";
  }

  /**
   * Create line elements which connect the elements with their parents.
   */
  private buildRelations(): void {
    // Clear relations.
    this.clearRelations();
    // Add relations.
    this.elements.forEach(item => {
      if (item.props.parentId !== null) {
        const parent = this.elementsById[item.props.parentId];
        const child = this.elementsById[item.props.id];
        if (parent && child) this.addRelationLine(parent, child);
      }
    });
  }

  /**
   * @param itemId Optional identifier of a parent or child item.
   * Remove the line elements which connect the elements with their parents.
   */
  private clearRelations(itemId?: number): void {
    if (itemId != null) {
      for (let key in this.relations) {
        const ids = key.split("|");
        const parentId = Number.parseInt(ids[0]);
        const childId = Number.parseInt(ids[1]);

        if (itemId === parentId || itemId === childId) {
          this.relations[key].remove();
          delete this.relations[key];
        }
      }
    } else {
      for (let key in this.relations) {
        this.relations[key].remove();
        delete this.relations[key];
      }
    }
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
      this.relations[identifier].remove();
    }

    // Get the items center.
    const startX = parent.props.x + parent.elementRef.clientWidth / 2;
    const startY =
      parent.props.y +
      (parent.elementRef.clientHeight - parent.labelElementRef.clientHeight) /
        2;
    const endX = child.props.x + child.elementRef.clientWidth / 2;
    const endY =
      child.props.y +
      (child.elementRef.clientHeight - child.labelElementRef.clientHeight) / 2;

    const line = new Line(
      linePropsDecoder({
        id: 0,
        type: ItemType.LINE_ITEM,
        startX,
        startY,
        endX,
        endY,
        width: 0,
        height: 0,
        lineWidth: this.props.relationLineWidth,
        color: "#CCCCCC"
      }),
      itemMetaDecoder({
        receivedAt: new Date()
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
  public onItemClick(
    listener: Listener<ItemClickEvent<ItemProps>>
  ): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.clickEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * Add an event handler to the movement of the visual console elements.
   * @param listener Function which is going to be executed when a linked console is moved.
   */
  public onItemMoved(listener: Listener<ItemMovedEvent>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.movedEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * Add an event handler to the resizement of the visual console elements.
   * @param listener Function which is going to be executed when a linked console is moved.
   */
  public onItemResized(listener: Listener<ItemResizedEvent>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.resizedEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * Enable the edition mode.
   */
  public enableEditMode(): void {
    this.elements.forEach(item => {
      item.meta = { ...item.meta, editMode: true };
    });
    this.containerRef.classList.add("is-editing");
  }

  /**
   * Disable the edition mode.
   */
  public disableEditMode(): void {
    this.elements.forEach(item => {
      item.meta = { ...item.meta, editMode: false };
    });
    this.containerRef.classList.remove("is-editing");
  }
}
