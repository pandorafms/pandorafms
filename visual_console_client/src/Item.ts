import {
  Position,
  Size,
  AnyObject,
  WithModuleProps,
  ItemMeta
} from "./lib/types";
import {
  sizePropsDecoder,
  positionPropsDecoder,
  parseIntOr,
  parseBoolean,
  notEmptyStringOr,
  replaceMacros,
  humanDate,
  humanTime,
  addMovementListener,
  debounce,
  addResizementListener
} from "./lib";
import TypedEvent, { Listener, Disposable } from "./lib/TypedEvent";

// Enum: https://www.typescriptlang.org/docs/handbook/enums.html.
export const enum ItemType {
  STATIC_GRAPH = 0,
  MODULE_GRAPH = 1,
  SIMPLE_VALUE = 2,
  PERCENTILE_BAR = 3,
  LABEL = 4,
  ICON = 5,
  SIMPLE_VALUE_MAX = 6,
  SIMPLE_VALUE_MIN = 7,
  SIMPLE_VALUE_AVG = 8,
  PERCENTILE_BUBBLE = 9,
  SERVICE = 10,
  GROUP_ITEM = 11,
  BOX_ITEM = 12,
  LINE_ITEM = 13,
  AUTO_SLA_GRAPH = 14,
  CIRCULAR_PROGRESS_BAR = 15,
  CIRCULAR_INTERIOR_PROGRESS_BAR = 16,
  DONUT_GRAPH = 17,
  BARS_GRAPH = 18,
  CLOCK = 19,
  COLOR_CLOUD = 20
}

// Base item properties. This interface should be extended by the item implementations.
export interface ItemProps extends Position, Size {
  readonly id: number;
  readonly type: ItemType;
  label: string | null;
  labelPosition: "up" | "right" | "down" | "left";
  isLinkEnabled: boolean;
  link: string | null;
  isOnTop: boolean;
  parentId: number | null;
  aclGroupId: number | null;
}

// FIXME: Fix type compatibility.
export interface ItemClickEvent<Props extends ItemProps> {
  // data: Props;
  data: AnyObject;
  nativeEvent: Event;
}

// FIXME: Fix type compatibility.
export interface ItemRemoveEvent<Props extends ItemProps> {
  // data: Props;
  data: AnyObject;
}

export interface ItemMovedEvent {
  item: VisualConsoleItem<ItemProps>;
  prevPosition: Position;
  newPosition: Position;
}

export interface ItemResizedEvent {
  item: VisualConsoleItem<ItemProps>;
  prevSize: Size;
  newSize: Size;
}

/**
 * Extract a valid enum value from a raw label positi9on value.
 * @param labelPosition Raw value.
 */
const parseLabelPosition = (
  labelPosition: unknown
): ItemProps["labelPosition"] => {
  switch (labelPosition) {
    case "up":
    case "right":
    case "down":
    case "left":
      return labelPosition;
    default:
      return "down";
  }
};

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the item props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function itemBasePropsDecoder(data: AnyObject): ItemProps | never {
  if (data.id == null || isNaN(parseInt(data.id))) {
    throw new TypeError("invalid id.");
  }
  if (data.type == null || isNaN(parseInt(data.type))) {
    throw new TypeError("invalid type.");
  }

  return {
    id: parseInt(data.id),
    type: parseInt(data.type),
    label: notEmptyStringOr(data.label, null),
    labelPosition: parseLabelPosition(data.labelPosition),
    isLinkEnabled: parseBoolean(data.isLinkEnabled),
    link: notEmptyStringOr(data.link, null),
    isOnTop: parseBoolean(data.isOnTop),
    parentId: parseIntOr(data.parentId, null),
    aclGroupId: parseIntOr(data.aclGroupId, null),
    ...sizePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...positionPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

/**
 * Base class of the visual console items. Should be extended to use its capabilities.
 */
abstract class VisualConsoleItem<Props extends ItemProps> {
  // Properties of the item.
  private itemProps: Props;
  // Metadata of the item.
  private _metadata: ItemMeta;
  // Reference to the DOM element which will contain the item.
  public elementRef: HTMLElement;
  public readonly labelElementRef: HTMLElement;
  // Reference to the DOM element which will contain the view of the item which extends this class.
  protected readonly childElementRef: HTMLElement;
  // Event manager for click events.
  private readonly clickEventManager = new TypedEvent<ItemClickEvent<Props>>();
  // Event manager for moved events.
  private readonly movedEventManager = new TypedEvent<ItemMovedEvent>();
  // Event manager for resized events.
  private readonly resizedEventManager = new TypedEvent<ItemResizedEvent>();
  // Event manager for remove events.
  private readonly removeEventManager = new TypedEvent<
    ItemRemoveEvent<Props>
  >();
  // List of references to clean the event listeners.
  private readonly disposables: Disposable[] = [];

  // This function will only run the 2nd arg function after the time
  // of the first arg have passed after its last execution.
  private debouncedMovementSave = debounce(
    500, // ms.
    (x: Position["x"], y: Position["y"]) => {
      const prevPosition = {
        x: this.props.x,
        y: this.props.y
      };
      const newPosition = {
        x: x,
        y: y
      };

      if (!this.positionChanged(prevPosition, newPosition)) return;

      // Save the new position to the props.
      this.move(x, y);
      // Emit the movement event.
      this.movedEventManager.emit({
        item: this,
        prevPosition: prevPosition,
        newPosition: newPosition
      });
    }
  );
  // This property will store the function
  // to clean the movement listener.
  private removeMovement: Function | null = null;

  /**
   * Start the movement funtionality.
   * @param element Element to move inside its container.
   */
  private initMovementListener(element: HTMLElement): void {
    this.removeMovement = addMovementListener(
      element,
      (x: Position["x"], y: Position["y"]) => {
        // Move the DOM element.
        this.moveElement(x, y);
        // Run the save function.
        this.debouncedMovementSave(x, y);
      }
    );
  }
  /**
   * Stop the movement fun
   */
  private stopMovementListener(): void {
    if (this.removeMovement) {
      this.removeMovement();
      this.removeMovement = null;
    }
  }

  // This function will only run the 2nd arg function after the time
  // of the first arg have passed after its last execution.
  private debouncedResizementSave = debounce(
    500, // ms.
    (width: Size["width"], height: Size["height"]) => {
      const prevSize = {
        width: this.props.width,
        height: this.props.height
      };
      const newSize = {
        width: width,
        height: height
      };

      if (!this.sizeChanged(prevSize, newSize)) return;

      // Save the new position to the props.
      this.resize(width, height);
      // Emit the resizement event.
      this.resizedEventManager.emit({
        item: this,
        prevSize: prevSize,
        newSize: newSize
      });
    }
  );
  // This property will store the function
  // to clean the resizement listener.
  private removeResizement: Function | null = null;

  /**
   * Start the resizement funtionality.
   * @param element Element to move inside its container.
   */
  protected initResizementListener(element: HTMLElement): void {
    this.removeResizement = addResizementListener(
      element,
      (width: Size["width"], height: Size["height"]) => {
        // The label it's outside the item's size, so we need
        // to get rid of its size to get the real size of the
        // item's content.
        if (this.props.label && this.props.label.length > 0) {
          const {
            width: labelWidth,
            height: labelHeight
          } = this.labelElementRef.getBoundingClientRect();

          switch (this.props.labelPosition) {
            case "up":
            case "down":
              height -= labelHeight;
              break;
            case "left":
            case "right":
              width -= labelWidth;
              break;
          }
        }

        // Move the DOM element.
        this.resizeElement(width, height);
        // Run the save function.
        this.debouncedResizementSave(width, height);
      }
    );
  }
  /**
   * Stop the resizement functionality.
   */
  private stopResizementListener(): void {
    if (this.removeResizement) {
      this.removeResizement();
      this.removeResizement = null;
    }
  }

  /**
   * To create a new element which will be inside the item box.
   * @return Item.
   */
  protected abstract createDomElement(): HTMLElement;

  public constructor(props: Props, metadata: ItemMeta) {
    this.itemProps = props;
    this._metadata = metadata;

    /*
     * Get a HTMLElement which represents the container box
     * of the Visual Console item. This element will manage
     * all the common things like click events, show a border
     * when hovered, etc.
     */
    this.elementRef = this.createContainerDomElement();
    this.labelElementRef = this.createLabelDomElement();

    /*
     * Get a HTMLElement which represents the custom view
     * of the Visual Console item. This element will be
     * different depending on the item implementation.
     */
    this.childElementRef = this.createDomElement();

    // Insert the elements into the container.
    this.elementRef.append(this.childElementRef, this.labelElementRef);

    // Resize element.
    this.resizeElement(props.width, props.height);
    // Set label position.
    this.changeLabelPosition(props.labelPosition);
  }

  /**
   * To create a new box for the visual console item.
   * @return Item box.
   */
  private createContainerDomElement(): HTMLElement {
    let box;
    if (this.props.isLinkEnabled) {
      box = document.createElement("a") as HTMLAnchorElement;
      if (this.props.link) box.href = this.props.link;
    } else {
      box = document.createElement("div") as HTMLDivElement;
    }

    box.className = "visual-console-item";
    box.style.zIndex = this.props.isOnTop ? "2" : "1";
    box.style.left = `${this.props.x}px`;
    box.style.top = `${this.props.y}px`;
    // Init the click listener.
    box.addEventListener("click", e => {
      if (this.meta.editMode) {
        e.preventDefault();
        e.stopPropagation();
      } else {
        this.clickEventManager.emit({ data: this.props, nativeEvent: e });
      }
    });

    // Metadata state.
    if (this.meta.editMode) {
      box.classList.add("is-editing");
      // Init the movement listener.
      this.initMovementListener(box);
      // Init the resizement listener.
      this.initResizementListener(box);
    }
    if (this.meta.isFetching) {
      box.classList.add("is-fetching");
    }
    if (this.meta.isUpdating) {
      box.classList.add("is-updating");
    }

    return box;
  }

  /**
   * To create a new label for the visual console item.
   * @return Item label.
   */
  protected createLabelDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "visual-console-item-label";
    // Add the label if it exists.
    const label = this.getLabelWithMacrosReplaced();
    if (label.length > 0) {
      // Ugly table we need to use to replicate the legacy style.
      const table = document.createElement("table");
      const row = document.createElement("tr");
      const emptyRow1 = document.createElement("tr");
      const emptyRow2 = document.createElement("tr");
      const cell = document.createElement("td");

      cell.innerHTML = label;
      row.append(cell);
      table.append(emptyRow1, row, emptyRow2);
      table.style.textAlign = "center";

      // Change the table size depending on its position.
      switch (this.props.labelPosition) {
        case "up":
        case "down":
          if (this.props.width > 0) {
            table.style.width = `${this.props.width}px`;
            table.style.height = null;
          }
          break;
        case "left":
        case "right":
          if (this.props.height > 0) {
            table.style.width = null;
            table.style.height = `${this.props.height}px`;
          }
          break;
      }

      // element.innerHTML = this.props.label;
      element.append(table);
    }

    return element;
  }

  /**
   * Return the label stored into the props with some macros replaced.
   */
  protected getLabelWithMacrosReplaced(): string {
    // We assert that the props may have some needed properties.
    const props = this.props as Partial<WithModuleProps>;

    return replaceMacros(
      [
        {
          macro: "_date_",
          value: humanDate(new Date())
        },
        {
          macro: "_time_",
          value: humanTime(new Date())
        },
        {
          macro: "_agent_",
          value: props.agentAlias != null ? props.agentAlias : ""
        },
        {
          macro: "_agentdescription_",
          value: props.agentDescription != null ? props.agentDescription : ""
        },
        {
          macro: "_address_",
          value: props.agentAddress != null ? props.agentAddress : ""
        },
        {
          macro: "_module_",
          value: props.moduleName != null ? props.moduleName : ""
        },
        {
          macro: "_moduledescription_",
          value: props.moduleDescription != null ? props.moduleDescription : ""
        }
      ],
      this.props.label || ""
    );
  }

  /**
   * To update the content element.
   * @return Item.
   */
  protected updateDomElement(element: HTMLElement): void {
    element.innerHTML = this.createDomElement().innerHTML;
  }

  /**
   * Public accessor of the `props` property.
   * @return Properties.
   */
  public get props(): Props {
    return { ...this.itemProps }; // Return a copy.
  }

  /**
   * Public setter of the `props` property.
   * If the new props are different enough than the
   * stored props, a render would be fired.
   * @param newProps
   */
  public set props(newProps: Props) {
    const prevProps = this.props;
    // Update the internal props.
    this.itemProps = newProps;

    // From this point, things which rely on this.props can access to the changes.

    // Check if we should re-render.
    if (this.shouldBeUpdated(prevProps, newProps))
      this.render(prevProps, this._metadata);
  }

  /**
   * Public accessor of the `meta` property.
   * @return Properties.
   */
  public get meta(): ItemMeta {
    return { ...this._metadata }; // Return a copy.
  }

  /**
   * Public setter of the `meta` property.
   * If the new meta are different enough than the
   * stored meta, a render would be fired.
   * @param newProps
   */
  public set meta(newMetadata: ItemMeta) {
    this.setMeta(newMetadata);
  }

  /**
   * Clasic and protected version of the setter of the `meta` property.
   * Useful to override it from children classes.
   * @param newProps
   */
  protected setMeta(newMetadata: ItemMeta) {
    const prevMetadata = this._metadata;
    // Update the internal meta.
    this._metadata = newMetadata;

    // From this point, things which rely on this.props can access to the changes.

    // Check if we should re-render.
    // if (this.shouldBeUpdated(prevMetadata, newMetadata))
    this.render(this.itemProps, prevMetadata);
  }

  /**
   * To compare the previous and the new props and returns a boolean value
   * in case the difference is meaningfull enough to perform DOM changes.
   *
   * Here, the only comparision is done by reference.
   *
   * Override this function to perform a different comparision depending on the item needs.
   *
   * @param prevProps
   * @param newProps
   * @return Whether the difference is meaningful enough to perform DOM changes or not.
   */
  protected shouldBeUpdated(prevProps: Props, newProps: Props): boolean {
    return prevProps !== newProps;
  }

  /**
   * To recreate or update the HTMLElement which represents the item into the DOM.
   * @param prevProps If exists it will be used to only perform DOM updates instead of a full replace.
   */
  public render(
    prevProps: Props | null = null,
    prevMeta: ItemMeta | null = null
  ): void {
    this.updateDomElement(this.childElementRef);

    // Move box.
    if (!prevProps || this.positionChanged(prevProps, this.props)) {
      this.moveElement(this.props.x, this.props.y);
    }
    // Resize box.
    if (!prevProps || this.sizeChanged(prevProps, this.props)) {
      this.resizeElement(this.props.width, this.props.height);
    }
    // Change label.
    const oldLabelHtml = this.labelElementRef.innerHTML;
    const newLabelHtml = this.createLabelDomElement().innerHTML;
    if (oldLabelHtml !== newLabelHtml) {
      this.labelElementRef.innerHTML = newLabelHtml;
    }
    // Change label position.
    if (!prevProps || prevProps.labelPosition !== this.props.labelPosition) {
      this.changeLabelPosition(this.props.labelPosition);
    }
    // Change link.
    if (
      prevProps &&
      (prevProps.isLinkEnabled !== this.props.isLinkEnabled ||
        (this.props.isLinkEnabled && prevProps.link !== this.props.link))
    ) {
      const container = this.createContainerDomElement();
      // Add the children of the old element.
      container.innerHTML = this.elementRef.innerHTML;
      // Copy the attributes.
      const attrs = this.elementRef.attributes;
      for (let i = 0; i < attrs.length; i++) {
        if (attrs[i].nodeName !== "id") {
          container.setAttributeNode(attrs[i]);
        }
      }
      // Replace the reference.
      if (this.elementRef.parentNode !== null) {
        this.elementRef.parentNode.replaceChild(container, this.elementRef);
      }

      // Changed the reference to the main element. It's ugly, but needed.
      this.elementRef = container;
    }

    // Change metadata related things.
    if (!prevMeta || prevMeta.editMode !== this.meta.editMode) {
      if (this.meta.editMode) {
        this.elementRef.classList.add("is-editing");
        this.initMovementListener(this.elementRef);
        this.initResizementListener(this.elementRef);
      } else {
        this.elementRef.classList.remove("is-editing");
        this.stopMovementListener();
        this.stopResizementListener();
      }
    }
    if (!prevMeta || prevMeta.isFetching !== this.meta.isFetching) {
      if (this.meta.isFetching) {
        this.elementRef.classList.add("is-fetching");
      } else {
        this.elementRef.classList.remove("is-fetching");
      }
    }
    if (!prevMeta || prevMeta.isUpdating !== this.meta.isUpdating) {
      if (this.meta.isUpdating) {
        this.elementRef.classList.add("is-updating");
      } else {
        this.elementRef.classList.remove("is-updating");
      }
    }
  }

  /**
   * To remove the event listeners and the elements from the DOM.
   */
  public remove(): void {
    // Call the remove event.
    this.removeEventManager.emit({ data: this.props });
    // Event listeners.
    this.disposables.forEach(disposable => {
      try {
        disposable.dispose();
      } catch (ignored) {} // eslint-disable-line no-empty
    });
    // VisualConsoleItem DOM element.
    this.elementRef.remove();
  }

  /**
   * Compare the previous and the new position and return
   * a boolean value in case the position changed.
   * @param prevPosition
   * @param newPosition
   * @return Whether the position changed or not.
   */
  protected positionChanged(
    prevPosition: Position,
    newPosition: Position
  ): boolean {
    return prevPosition.x !== newPosition.x || prevPosition.y !== newPosition.y;
  }

  /**
   * Move the label around the item content.
   * @param position Label position.
   */
  protected changeLabelPosition(position: Props["labelPosition"]): void {
    switch (position) {
      case "up":
        this.elementRef.style.flexDirection = "column-reverse";
        break;
      case "left":
        this.elementRef.style.flexDirection = "row-reverse";
        break;
      case "right":
        this.elementRef.style.flexDirection = "row";
        break;
      case "down":
      default:
        this.elementRef.style.flexDirection = "column";
        break;
    }

    // Ugly table to show the label as its legacy counterpart.
    const tables = this.labelElementRef.getElementsByTagName("table");
    const table = tables.length > 0 ? tables.item(0) : null;
    // Change the table size depending on its position.
    if (table) {
      switch (this.props.labelPosition) {
        case "up":
        case "down":
          if (this.props.width > 0) {
            table.style.width = `${this.props.width}px`;
            table.style.height = null;
          }
          break;
        case "left":
        case "right":
          if (this.props.height > 0) {
            table.style.width = null;
            table.style.height = `${this.props.height}px`;
          }
          break;
      }
    }
  }

  /**
   * Move the DOM container.
   * @param x Horizontal axis position.
   * @param y Vertical axis position.
   */
  protected moveElement(x: number, y: number): void {
    this.elementRef.style.left = `${x}px`;
    this.elementRef.style.top = `${y}px`;
  }

  /**
   * Update the position into the properties and move the DOM container.
   * @param x Horizontal axis position.
   * @param y Vertical axis position.
   */
  public move(x: number, y: number): void {
    this.moveElement(x, y);
    this.itemProps = {
      ...this.props, // Object spread: http://es6-features.org/#SpreadOperator
      x,
      y
    };
  }

  /**
   * Compare the previous and the new size and return
   * a boolean value in case the size changed.
   * @param prevSize
   * @param newSize
   * @return Whether the size changed or not.
   */
  protected sizeChanged(prevSize: Size, newSize: Size): boolean {
    return (
      prevSize.width !== newSize.width || prevSize.height !== newSize.height
    );
  }

  /**
   * Resize the DOM content container.
   * @param width
   * @param height
   */
  protected resizeElement(width: number, height: number): void {
    // The most valuable size is the content size.
    this.childElementRef.style.width = width > 0 ? `${width}px` : null;
    this.childElementRef.style.height = height > 0 ? `${height}px` : null;

    if (this.props.label && this.props.label.length > 0) {
      // Ugly table to show the label as its legacy counterpart.
      const tables = this.labelElementRef.getElementsByTagName("table");
      const table = tables.length > 0 ? tables.item(0) : null;

      if (table) {
        switch (this.props.labelPosition) {
          case "up":
          case "down":
            table.style.width = width > 0 ? `${width}px` : null;
            break;
          case "left":
          case "right":
            table.style.height = height > 0 ? `${height}px` : null;
            break;
        }
      }
    }
  }

  /**
   * Update the size into the properties and resize the DOM container.
   * @param width
   * @param height
   */
  public resize(width: number, height: number): void {
    this.resizeElement(width, height);
    this.itemProps = {
      ...this.props, // Object spread: http://es6-features.org/#SpreadOperator
      width,
      height
    };
  }

  /**
   * To add an event handler to the click of the linked visual console elements.
   * @param listener Function which is going to be executed when a linked console is clicked.
   */
  public onClick(listener: Listener<ItemClickEvent<Props>>): Disposable {
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
   * To add an event handler to the movement of visual console elements.
   * @param listener Function which is going to be executed when a linked console is moved.
   */
  public onMoved(listener: Listener<ItemMovedEvent>): Disposable {
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
   * To add an event handler to the resizement of visual console elements.
   * @param listener Function which is going to be executed when a linked console is moved.
   */
  public onResized(listener: Listener<ItemResizedEvent>): Disposable {
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
   * To add an event handler to the removal of the item.
   * @param listener Function which is going to be executed when a item is removed.
   */
  public onRemove(listener: Listener<ItemRemoveEvent<Props>>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.removeEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }
}

export default VisualConsoleItem;
