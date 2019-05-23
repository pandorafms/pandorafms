import { Position, Size, UnknownObject, WithModuleProps } from "./types";
import {
  sizePropsDecoder,
  positionPropsDecoder,
  parseIntOr,
  parseBoolean,
  notEmptyStringOr,
  replaceMacros,
  humanDate,
  humanTime
} from "./lib";
import TypedEvent, { Listener, Disposable } from "./TypedEvent";

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
  data: UnknownObject;
  nativeEvent: Event;
}

// FIXME: Fix type compatibility.
export interface ItemRemoveEvent<Props extends ItemProps> {
  // data: Props;
  data: UnknownObject;
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
export function itemBasePropsDecoder(data: UnknownObject): ItemProps | never {
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
  // Reference to the DOM element which will contain the item.
  public elementRef: HTMLElement;
  public readonly labelElementRef: HTMLElement;
  // Reference to the DOM element which will contain the view of the item which extends this class.
  protected readonly childElementRef: HTMLElement;
  // Event manager for click events.
  private readonly clickEventManager = new TypedEvent<ItemClickEvent<Props>>();
  // Event manager for remove events.
  private readonly removeEventManager = new TypedEvent<
    ItemRemoveEvent<Props>
  >();
  // List of references to clean the event listeners.
  private readonly disposables: Disposable[] = [];

  /**
   * To create a new element which will be inside the item box.
   * @return Item.
   */
  protected abstract createDomElement(): HTMLElement;

  public constructor(props: Props) {
    this.itemProps = props;

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
      box = document.createElement("a");
      box as HTMLAnchorElement;
      if (this.props.link) box.href = this.props.link;
    } else {
      box = document.createElement("div");
      box as HTMLDivElement;
    }

    box.className = "visual-console-item";
    box.style.zIndex = this.props.isOnTop ? "2" : "1";
    box.style.left = `${this.props.x}px`;
    box.style.top = `${this.props.y}px`;
    box.onclick = e =>
      this.clickEventManager.emit({ data: this.props, nativeEvent: e });

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
    if (this.shouldBeUpdated(prevProps, newProps)) this.render(prevProps);
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
  public render(prevProps: Props | null = null): void {
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
