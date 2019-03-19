import { Position, Size, UnknownObject } from "./types";
import {
  sizePropsDecoder,
  positionPropsDecoder,
  parseIntOr,
  parseBoolean,
  notEmptyStringOr
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
  isOnTop: boolean;
  parentId: number | null;
  aclGroupId: number | null;
}

// FIXME: Fix type compatibility.
export interface ItemClickEvent<Props extends ItemProps> {
  // data: Props;
  data: UnknownObject;
}

/**
 * Extract a valid enum value from a raw label positi9on value.
 * @param labelPosition Raw value.
 */
const parseLabelPosition = (
  labelPosition: any // eslint-disable-line @typescript-eslint/no-explicit-any
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
  // TODO: Check for valid types.
  if (data.type == null || isNaN(parseInt(data.type))) {
    throw new TypeError("invalid type.");
  }

  return {
    id: parseInt(data.id),
    type: parseInt(data.type),
    label: notEmptyStringOr(data.label, null),
    labelPosition: parseLabelPosition(data.labelPosition),
    isLinkEnabled: parseBoolean(data.isLinkEnabled),
    isOnTop: parseBoolean(data.isOnTop),
    parentId: parseIntOr(data.parentId, null),
    aclGroupId: parseIntOr(data.aclGroupId, null),
    ...sizePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...positionPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

abstract class VisualConsoleItem<Props extends ItemProps> {
  // Properties of the item.
  private itemProps: Props;
  // Reference to the DOM element which will contain the item.
  public readonly elementRef: HTMLElement;
  // Reference to the DOM element which will contain the view of the item which extends this class.
  protected readonly childElementRef: HTMLElement;
  // Event manager for click events.
  private readonly clickEventManager = new TypedEvent<ItemClickEvent<Props>>();
  // List of references to clean the event listeners.
  private readonly disposables: Disposable[] = [];

  /**
   * To create a new element which will be inside the item box.
   * @return Item.
   */
  abstract createDomElement(): HTMLElement;

  public constructor(props: Props) {
    this.itemProps = props;

    /*
     * Get a HTMLElement which represents the container box
     * of the Visual Console item. This element will manage
     * all the common things like click events, show a border
     * when hovered, etc.
     */
    this.elementRef = this.createContainerDomElement();

    /*
     * Get a HTMLElement which represents the custom view
     * of the Visual Console item. This element will be
     * different depending on the item implementation.
     */
    this.childElementRef = this.createDomElement();

    // Insert the elements into the container.
    // Visual Console Item Container > Custom Item View.
    this.elementRef.append(this.childElementRef);
  }

  /**
   * To create a new box for the visual console item.
   * @return Item box.
   */
  private createContainerDomElement(): HTMLElement {
    const box: HTMLDivElement = document.createElement("div");
    box.className = "visual-console-item";
    box.style.width = `${this.props.width}px`;
    box.style.height = `${this.props.height}px`;
    box.style.left = `${this.props.x}px`;
    box.style.top = `${this.props.y}px`;
    box.onclick = () => this.clickEventManager.emit({ data: this.props });
    // TODO: Add label.
    return box;
  }

  /**
   * Public accessor of the `props` property.
   * @return Properties.
   */
  public get props(): Props {
    return this.itemProps;
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
    // Move box.
    if (!prevProps || this.positionChanged(prevProps, this.props)) {
      this.moveElement(this.props.x, this.props.y);
    }
    // Resize box.
    if (!prevProps || this.sizeChanged(prevProps, this.props)) {
      this.resizeElement(this.props.width, this.props.height);
    }

    this.childElementRef.innerHTML = this.createDomElement().innerHTML;
  }

  /**
   * To remove the event listeners and the elements from the DOM.
   */
  public remove(): void {
    // Event listeners.
    this.disposables.forEach(_ => _.dispose());
    // VisualConsoleItem extension DOM element.
    this.childElementRef.remove();
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
   * Resize the DOM container.
   * @param width
   * @param height
   */
  protected resizeElement(width: number, height: number): void {
    this.elementRef.style.width = `${width}px`;
    this.elementRef.style.height = `${height}px`;
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
  public onClick(listener: Listener<ItemClickEvent<Props>>): void {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    this.disposables.push(this.clickEventManager.on(listener));
  }
}

export default VisualConsoleItem;
