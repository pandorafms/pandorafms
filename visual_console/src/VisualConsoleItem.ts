import { Position, Size, UnknownObject } from "./types";
import {
  sizePropsDecoder,
  positionPropsDecoder,
  parseIntOr,
  parseBoolean
} from "./lib";
import TypedEvent, { Listener, Disposable } from "./TypedEvent";

// Base item properties. This interface should be extended by the item implementations.
export interface VisualConsoleItemProps extends Position, Size {
  readonly id: number;
  readonly type: number;
  label: string | null;
  isLinkEnabled: boolean;
  isOnTop: boolean;
  parentId: number | null;
  aclGroupId: number | null;
}

export type ItemClickEvent<ItemProps extends VisualConsoleItemProps> = {
  data: ItemProps;
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
export function itemPropsDecoder(
  data: UnknownObject
): VisualConsoleItemProps | never {
  if (data.id == null || isNaN(parseInt(data.id))) {
    throw new TypeError("invalid id.");
  }
  // TODO: Check valid types.
  if (data.type == null || isNaN(parseInt(data.type))) {
    throw new TypeError("invalid type.");
  }

  return {
    id: parseInt(data.id),
    type: parseInt(data.type),
    label:
      typeof data.label === "string" && data.label.length > 0
        ? data.label
        : null,
    isLinkEnabled: parseBoolean(data.isLinkEnabled),
    isOnTop: parseBoolean(data.isOnTop),
    parentId: parseIntOr(data.parentId, null),
    aclGroupId: parseIntOr(data.aclGroupId, null),
    ...sizePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...positionPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

abstract class VisualConsoleItem<ItemProps extends VisualConsoleItemProps> {
  // Properties of the item.
  private itemProps: ItemProps;
  // Reference of the DOM element which contain all the items.
  private readonly containerRef: HTMLElement;
  // Reference of the DOM element which contain the item box.
  private readonly itemBoxRef: HTMLElement;
  // Reference of the DOM element which contain the view of the item which extends this class.
  protected readonly elementRef: HTMLElement;
  // Event manager for click events.
  private readonly clickEventManager = new TypedEvent<
    ItemClickEvent<ItemProps>
  >();
  // List of references to clean the event listeners.
  private readonly disposables: Disposable[] = [];

  /**
   * To create a new element which will be inside the item box.
   * @return Item.
   */
  abstract createDomElement(): HTMLElement;

  constructor(container: HTMLElement, props: ItemProps) {
    this.containerRef = container;
    this.itemProps = props;

    /*
     * Get a HTMLElement which represents the container box
     * of the Visual Console item. This element will manage
     * all the common things like click events, show a border
     * when hovered, etc.
     */
    this.itemBoxRef = this.createItemBoxDomElement();

    /*
     * Get a HTMLElement which represents the custom view
     * of the Visual Console item. This element will be
     * different depending on the item implementation.
     */
    this.elementRef = this.createDomElement();

    // Insert the elements into their parents.
    // Visual Console Container > Generic Item Box > Custom Item View.
    this.itemBoxRef.append(this.elementRef);
    this.containerRef.append(this.itemBoxRef);
  }

  /**
   * To create a new box for the visual console item.
   * @return Item box.
   */
  private createItemBoxDomElement(): HTMLElement {
    const box: HTMLDivElement = document.createElement("div");
    box.className = "visual-console-item";
    box.style.width = `${this.props.width}px`;
    box.style.height = `${this.props.height}px`;
    box.onclick = () => this.clickEventManager.emit({ data: this.props });
    // TODO: Add label.
    return box;
  }

  /**
   * Public accessor of the `props` property.
   * @return Properties.
   */
  get props(): ItemProps {
    return this.itemProps;
  }

  /**
   * Public setter of the `props` property.
   * If the new props are different enough than the
   * stored props, a render would be fired.
   * @param newProps
   */
  set props(newProps: ItemProps) {
    const prevProps = this.props;
    // Update the internal props.
    this.itemProps = newProps;

    // From this point, things which rely on this.props can access to the changes.

    // Check if we should re-render.
    if (this.shouldBeUpdated(newProps)) this.render(prevProps);
  }

  /**
   * To compare the previous and the new props and returns a boolean value
   * in case the difference is meaningfull enough to perform DOM changes.
   *
   * Here, the only comparision is done by reference.
   *
   * Override this function to perform a different comparision depending on the item needs.
   *
   * @param newProps
   * @return Whether the difference is meaningful enough to perform DOM changes or not.
   */
  protected shouldBeUpdated(newProps: ItemProps): boolean {
    return this.props !== newProps;
  }

  /**
   * To recreate or update the HTMLElement which represents the item into the DOM.
   * @param prevProps If exists it will be used to only perform
   * perform DOM updates instead of a full replace.
   */
  render(prevProps: ItemProps | null): void {
    // Move box.
    if (!prevProps || prevProps.x !== this.props.x) {
      this.itemBoxRef.style.left = `${this.props.x}px`;
    }
    if (!prevProps || prevProps.y !== this.props.y) {
      this.itemBoxRef.style.top = `${this.props.y}px`;
    }
    // Resize box.
    if (!prevProps || prevProps.width !== this.props.width) {
      this.itemBoxRef.style.width = `${this.props.width}px`;
    }
    if (!prevProps || prevProps.height !== this.props.height) {
      this.itemBoxRef.style.height = `${this.props.height}px`;
    }

    this.elementRef.replaceWith(this.createDomElement());
  }

  /**
   * To remove the event listeners and the elements from the DOM.
   */
  remove(): void {
    // Event listeners.
    this.disposables.forEach(_ => _.dispose());
    // VisualConsoleItem extension DOM element.
    this.elementRef.remove();
    // VisualConsoleItem DOM element.
    this.itemBoxRef.remove();
  }

  /**
   * To move the item.
   * @param x Horizontal axis position.
   * @param y Vertical axis position.
   */
  move(x: number, y: number): void {
    // Compare position.
    if (x === this.props.x && y === this.props.y) return;
    // Update position. Change itemProps instead of props to avoid re-render.
    this.itemProps.x = x;
    this.itemProps.y = y;
    // Move element.
    this.itemBoxRef.style.left = `${x}px`;
    this.itemBoxRef.style.top = `${y}px`;
  }

  /**
   * To resize the item.
   * @param width Width.
   * @param height Height.
   */
  resize(width: number, height: number): void {
    // Compare size.
    if (width === this.props.width && height === this.props.height) return;
    // Update size. Change itemProps instead of props to avoid re-render.
    this.itemProps.width = width;
    this.itemProps.height = height;
    // Resize element.
    this.itemBoxRef.style.width = `${width}px`;
    this.itemBoxRef.style.height = `${height}px`;
  }

  /**
   * To add an event handler to the click of the linked visual console elements.
   * @param listener Function which is going to be executed when a linked console is clicked.
   */
  onClick(listener: Listener<ItemClickEvent<ItemProps>>): void {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    this.disposables.push(this.clickEventManager.on(listener));
  }
}

export default VisualConsoleItem;
