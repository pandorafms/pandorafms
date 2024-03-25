import {
  Position,
  Size,
  AnyObject,
  WithModuleProps,
  ItemMeta,
  LinkedVisualConsoleProps,
  WithAgentProps
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
  addResizementListener,
  t,
  parseFloatOr
} from "./lib";
import TypedEvent, { Listener, Disposable } from "./lib/TypedEvent";
import { FormContainer, InputGroup } from "./Form";

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
  COLOR_CLOUD = 20,
  NETWORK_LINK = 21,
  ODOMETER = 22,
  BASIC_CHART = 23
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
  cacheExpiration: number | null;
  colorStatus: string;
  cellId: string | null;
  alertOutline: boolean;
  ratio: number | null;
}

export interface ItemClickEvent {
  item: VisualConsoleItem<ItemProps>;
  nativeEvent: Event;
}

// FIXME: Fix type compatibility.
export interface ItemRemoveEvent {
  // data: Props;
  item: VisualConsoleItem<ItemProps>;
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

export interface ItemSelectionChangedEvent {
  selected: boolean;
}

/**
 * Extract a valid enum value from a raw label position value.
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
    cacheExpiration: parseIntOr(data.cacheExpiration, null),
    colorStatus: notEmptyStringOr(data.colorStatus, "#CCC"),
    cellId: notEmptyStringOr(data.cellId, ""),
    alertOutline: parseBoolean(data.alertOutline),
    ratio: parseFloatOr(data.ratio, null),
    ...sizePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    ...positionPropsDecoder(data) // Object spread. It will merge the properties of the two objects.
  };
}

//TODO: Document
export function titleItem(id: number): string {
  let title = "";
  switch (id) {
    case ItemType.STATIC_GRAPH:
      title = t("Static image");
      break;
    case ItemType.MODULE_GRAPH:
      title = t("Module graph");
      break;
    case ItemType.SIMPLE_VALUE:
      title = t("Simple value");
      break;
    case ItemType.PERCENTILE_BAR:
      title = t("Percentile item");
      break;
    case ItemType.LABEL:
      title = t("Label");
      break;
    case ItemType.ICON:
      title = t("Icon");
      break;
    case ItemType.SIMPLE_VALUE_MAX:
      title = t("Simple value");
      break;
    case ItemType.SIMPLE_VALUE_MIN:
      title = t("Simple value");
      break;
    case ItemType.SIMPLE_VALUE_AVG:
      title = t("Simple value");
      break;
    case ItemType.PERCENTILE_BUBBLE:
      title = t("Percentile item");
      break;
    case ItemType.SERVICE:
      title = t("Service");
      break;
    case ItemType.GROUP_ITEM:
      title = t("Group");
      break;
    case ItemType.BOX_ITEM:
      title = t("Box");
      break;
    case ItemType.LINE_ITEM:
      title = t("Line");
      break;
    case ItemType.AUTO_SLA_GRAPH:
      title = t("Event history graph");
      break;
    case ItemType.CIRCULAR_PROGRESS_BAR:
      title = t("Percentile item");
      break;
    case ItemType.CIRCULAR_INTERIOR_PROGRESS_BAR:
      title = t("Percentile item");
      break;
    case ItemType.DONUT_GRAPH:
      title = t("Serialized pie graph");
      break;
    case ItemType.BARS_GRAPH:
      title = t("Bars graph");
      break;
    case ItemType.CLOCK:
      title = t("Clock");
      break;
    case ItemType.COLOR_CLOUD:
      title = t("Color cloud");
      break;
    case ItemType.NETWORK_LINK:
      title = t("Network link");
      break;
    case ItemType.ODOMETER:
      title = t("Odometer");
      break;
    case ItemType.BASIC_CHART:
      title = t("Basic chart");
      break;
    default:
      title = t("Item");
      break;
  }

  return title;
}

/**
 * Base class of the visual console items. Should be extended to use its capabilities.
 */
abstract class VisualConsoleItem<Props extends ItemProps> {
  // Properties of the item.
  public itemProps: Props;
  // Metadata of the item.
  private _metadata: ItemMeta;
  // Reference to the DOM element which will contain the item.
  public elementRef: HTMLElement = document.createElement("div");
  public labelElementRef: HTMLElement = document.createElement("div");
  // Reference to the DOM element which will contain the view of the item which extends this class.
  protected childElementRef: HTMLElement = document.createElement("div");
  // Event manager for click events.
  private readonly clickEventManager = new TypedEvent<ItemClickEvent>();
  // Event manager for double click events.
  private readonly dblClickEventManager = new TypedEvent<ItemClickEvent>();
  // Event manager for moved events.
  private readonly movedEventManager = new TypedEvent<ItemMovedEvent>();
  // Event manager for stopped movement events.
  private readonly movementFinishedEventManager = new TypedEvent<
    ItemMovedEvent
  >();
  // Event manager for resized events.
  private readonly resizedEventManager = new TypedEvent<ItemResizedEvent>();
  // Event manager for resize finished events.
  private readonly resizeFinishedEventManager = new TypedEvent<
    ItemResizedEvent
  >();
  // Event manager for remove events.
  private readonly removeEventManager = new TypedEvent<ItemRemoveEvent>();
  // Event manager for selection change events.
  private readonly selectionChangedEventManager = new TypedEvent<
    ItemSelectionChangedEvent
  >();
  // List of references to clean the event listeners.
  private readonly disposables: Disposable[] = [];

  // This function will only run the 2nd arg function after the time
  // of the first arg have passed after its last execution.
  public debouncedMovementSave = debounce(
    500, // ms.
    (x: Position["x"], y: Position["y"]) => {
      // Update the metadata information.
      // Don't use the .meta property cause we don't need DOM updates.
      this._metadata.isBeingMoved = false;

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
      this.movementFinishedEventManager.emit({
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
    // Avoid line movement as 'block' force using circles.
    if (
      this.props.type == ItemType.LINE_ITEM ||
      this.props.type == ItemType.NETWORK_LINK
    ) {
      return;
    }

    this.removeMovement = addMovementListener(
      element,
      (x: Position["x"], y: Position["y"]) => {
        const prevPosition = {
          x: this.props.x,
          y: this.props.y
        };
        const newPosition = { x, y };

        this.meta = {
          ...this.meta,
          isSelected: true
        };

        if (!this.positionChanged(prevPosition, newPosition)) return;

        // Update the metadata information.
        // Don't use the .meta property cause we don't need DOM updates.
        this._metadata.isBeingMoved = true;
        // Move the DOM element.
        this.moveElement(x, y);
        // Emit the movement event.
        this.movedEventManager.emit({
          item: this,
          prevPosition: prevPosition,
          newPosition: newPosition
        });
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
  public debouncedResizementSave = debounce(
    500, // ms.
    (width: Size["width"], height: Size["height"]) => {
      // Update the metadata information.
      // Don't use the .meta property cause we don't need DOM updates.
      this._metadata.isBeingResized = false;

      const prevSize = {
        width: this.props.width,
        height: this.props.height
      };
      const newSize = { width, height };

      if (!this.sizeChanged(prevSize, newSize)) return;

      // Save the new position to the props.
      this.resize(width, height);

      // Emit the resize finished event.
      this.resizeFinishedEventManager.emit({
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
    if (
      this.props.type == ItemType.LINE_ITEM ||
      this.props.type == ItemType.NETWORK_LINK
    ) {
      return;
    }
    this.removeResizement = addResizementListener(
      element,
      (width: Size["width"], height: Size["height"]) => {
        // Update the metadata information.
        // Don't use the .meta property cause we don't need DOM updates.
        this._metadata.isBeingResized = true;

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

        const prevSize = {
          width: this.props.width,
          height: this.props.height
        };
        const newSize = { width, height };

        if (!this.sizeChanged(prevSize, newSize)) return;

        // Move the DOM element.
        this.resizeElement(width, height);
        // Emit the resizement event.
        this.resizedEventManager.emit({
          item: this,
          prevSize,
          newSize
        });
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

  public constructor(
    props: Props,
    metadata: ItemMeta,
    deferInit: boolean = false
  ) {
    this.itemProps = props;
    this._metadata = metadata;

    if (!deferInit) this.init();
  }

  /**
   * To create and append the DOM elements.
   */
  protected init(): void {
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
    this.elementRef.appendChild(this.childElementRef);
    this.elementRef.appendChild(this.labelElementRef);

    // Resize element.
    this.resizeElement(this.itemProps.width, this.itemProps.height);

    // Set label position.
    this.changeLabelPosition(this.itemProps.labelPosition);
  }

  /**
   * To create a new box for the visual console item.
   * @return Item box.
   */
  private createContainerDomElement(): HTMLElement {
    let box;
    if (this.props.isLinkEnabled) {
      box = document.createElement("a") as HTMLAnchorElement;

      if (this.props.link) {
        box.href = this.props.link;
      } else {
        box.className = "textDecorationNone";
      }
    } else {
      box = document.createElement("div") as HTMLDivElement;
      box.className = "textDecorationNone";
    }

    box.classList.add("visual-console-item");
    if (this.props.isOnTop) {
      box.classList.add("is-on-top");
    }

    box.style.left = `${this.props.x}px`;
    box.style.top = `${this.props.y}px`;

    if (this.props.alertOutline) {
      box.classList.add("is-alert-triggered");
    }

    // Init the click listeners.
    box.addEventListener("dblclick", e => {
      if (!this.meta.isBeingMoved && !this.meta.isBeingResized) {
        this.unSelectItem();
        this.selectItem();

        this.dblClickEventManager.emit({
          item: this,
          nativeEvent: e
        });
      }
    });
    box.addEventListener("click", e => {
      if (this.meta.editMode) {
        e.preventDefault();
        e.stopPropagation();
      } else {
        // Add loading click item.
        if (this.itemProps.isLinkEnabled && this.itemProps.link != null) {
          const divParent = document.createElement("div");
          divParent.className = "div-visual-console-spinner";
          const divSpinner = document.createElement("div");
          divSpinner.className = "visual-console-spinner";
          divParent.appendChild(divSpinner);
          let path = e.composedPath();
          let containerId = "visual-console-container";
          for (let index = 0; index < path.length; index++) {
            const element = path[index] as HTMLInputElement;
            if (
              element.id != undefined &&
              element.id != null &&
              element.id != ""
            ) {
              if (element.id.includes(containerId) === true) {
                containerId = element.id;
                break;
              }
            }
          }

          const containerVC = document.getElementById(containerId);
          if (containerVC != null) {
            containerVC.classList.add("is-updating");
            containerVC.appendChild(divParent);
          }
        }
      }

      if (!this.meta.isBeingMoved && !this.meta.isBeingResized) {
        this.clickEventManager.emit({
          item: this,
          nativeEvent: e
        });
      }
    });

    // Metadata state.
    if (this.meta.maintenanceMode) {
      box.classList.add("is-maintenance");
    }
    if (this.meta.editMode) {
      box.classList.add("is-editing");
    }
    if (this.meta.isFetching) {
      box.classList.add("is-fetching");
    }
    if (this.meta.isUpdating) {
      box.classList.add("is-updating");
    }
    if (this.meta.isSelected) {
      box.classList.add("is-selected");
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
      row.appendChild(cell);
      table.appendChild(emptyRow1);
      table.appendChild(row);
      table.appendChild(emptyRow2);
      table.style.textAlign = "center";
      element.appendChild(table);
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
    this.setProps(newProps);
  }

  /**
   * Clasic and protected version of the setter of the `props` property.
   * Useful to override it from children classes.
   * @param newProps
   */
  protected setProps(newProps: Props) {
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
   * Classic version of the setter of the `meta` property.
   * Useful to override it from children classes.
   * @param newProps
   */
  public setMeta(newMetadata: Partial<ItemMeta>): void {
    const prevMetadata = this._metadata;
    // Update the internal meta.
    this._metadata = {
      ...prevMetadata,
      ...newMetadata
    };

    if (
      typeof newMetadata.isSelected !== "undefined" &&
      prevMetadata.isSelected !== newMetadata.isSelected
    ) {
      this.selectionChangedEventManager.emit({
        selected: newMetadata.isSelected
      });
    }

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
   * Compare object.
   * @param previousObject Object 1.
   * @param currentObject Object 2.
   * @return Returns true if these are equal.
   */
  public compareObjects(
    previousObject: Record<string, any>,
    currentObject: Record<string, any>
  ): boolean {
    for (let key in previousObject) {
      if (key === "ratio") {
        continue;
      }

      if (currentObject.hasOwnProperty(key)) {
        if (previousObject[key] !== currentObject[key]) {
          return true;
        }
      } else {
        return true;
      }
    }

    return false;
  }

  /**
   * To recreate or update the HTMLElement which represents the item into the DOM.
   * @param prevProps If exists it will be used to only perform DOM updates instead of a full replace.
   */
  public render(
    prevProps: Props | null = null,
    prevMeta: ItemMeta | null = null
  ): void {
    if (prevProps) {
      if (this.props.ratio !== 1 && this.props.type != ItemType.LINE_ITEM) {
        this.elementRef.style.transform = `scale(${
          this.props.ratio ? this.props.ratio : 1
        })`;
        this.elementRef.style.transformOrigin = "left top";
        this.elementRef.style.minWidth = "max-content";
        this.elementRef.style.minHeight = "max-content";
      }

      if (this.compareObjects(prevProps, this.props) === true) {
        this.updateDomElement(this.childElementRef);
      }
    }

    // Move box.
    if (!prevProps || this.positionChanged(prevProps, this.props)) {
      this.moveElement(this.props.x, this.props.y);
      if (
        prevProps &&
        prevProps.type != ItemType.LINE_ITEM &&
        prevProps.type != ItemType.NETWORK_LINK
      ) {
        this.updateDomElement(this.childElementRef);
      }
    }

    // Resize box.
    if (!prevProps || this.sizeChanged(prevProps, this.props)) {
      this.resizeElement(this.props.width, this.props.height);
      if (
        prevProps &&
        prevProps.type != ItemType.LINE_ITEM &&
        prevProps.type != ItemType.NETWORK_LINK
      ) {
        this.updateDomElement(this.childElementRef);
      }
    }
    // Change label.
    const oldLabelHtml = this.labelElementRef.innerHTML;
    const newLabelHtml = this.createLabelDomElement().innerHTML;
    if (oldLabelHtml !== newLabelHtml) {
      this.labelElementRef.innerHTML = newLabelHtml;
      this.changeLabelPosition(this.itemProps.labelPosition);
    } else {
      // Change label position.
      if (!prevProps || prevProps.labelPosition !== this.props.labelPosition) {
        this.changeLabelPosition(this.props.labelPosition);
      }
    }

    //Change z-index class is-on-top
    if (!prevProps || prevProps.isOnTop !== this.props.isOnTop) {
      if (this.props.isOnTop) {
        this.elementRef.classList.add("is-on-top");
      } else {
        this.elementRef.classList.remove("is-on-top");
      }
    }

    // Change link.
    if (prevProps && prevProps.isLinkEnabled !== this.props.isLinkEnabled) {
      const container = this.createContainerDomElement();
      // Copy the attributes.
      const attrs = this.elementRef.attributes;
      for (let i = 0; i < attrs.length; i++) {
        if (attrs[i].nodeName !== "id") {
          let cloneIsNeeded = this.elementRef.getAttributeNode(
            attrs[i].nodeName
          );
          if (cloneIsNeeded !== null) {
            let cloneAttr = cloneIsNeeded.cloneNode(true) as Attr;
            container.setAttributeNode(cloneAttr);
          }
        }
      }
      // Replace the reference.
      if (this.elementRef.parentNode !== null) {
        this.elementRef.parentNode.replaceChild(container, this.elementRef);
      }

      // Changed the reference to the main element. It's ugly, but needed.
      this.elementRef = container;

      // Insert the elements into the container.
      this.elementRef.appendChild(this.childElementRef);
      this.elementRef.appendChild(this.labelElementRef);
    }

    if (
      prevProps &&
      this.props.isLinkEnabled &&
      prevProps.link !== this.props.link
    ) {
      if (this.props.link !== null) {
        this.elementRef.setAttribute("href", this.props.link);
      }
    }

    // Change metadata related things.
    if (
      !prevMeta ||
      prevMeta.editMode !== this.meta.editMode ||
      prevMeta.maintenanceMode !== this.meta.maintenanceMode
    ) {
      if (this.meta.editMode && this.meta.maintenanceMode === false) {
        this.elementRef.classList.add("is-editing");
        this.elementRef.classList.remove("is-alert-triggered");
      } else {
        this.elementRef.classList.remove("is-editing");

        if (this.props.alertOutline) {
          this.elementRef.classList.add("is-alert-triggered");
        }
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

        const divParent = document.createElement("div");
        divParent.className = "div-visual-console-spinner";
        const divSpinner = document.createElement("div");
        divSpinner.className = "visual-console-spinner";
        divParent.appendChild(divSpinner);
        this.elementRef.appendChild(divParent);
      } else {
        this.elementRef.classList.remove("is-updating");

        const div = this.elementRef.querySelector(
          ".div-visual-console-spinner"
        );
        if (div !== null) {
          const parent = div.parentElement;
          if (parent !== null) {
            parent.removeChild(div);
          }
        }
      }

      this.updateDomElement(this.childElementRef);
    }
    if (!prevMeta || prevMeta.isSelected !== this.meta.isSelected) {
      if (this.meta.isSelected) {
        this.elementRef.classList.add("is-selected");
        this.elementRef.setAttribute("id", "item-selected-move");
      } else {
        this.elementRef.classList.remove("is-selected");
        this.elementRef.removeAttribute("id");
      }
    }
  }

  /**
   * To remove the event listeners and the elements from the DOM.
   */
  public remove(): void {
    // Call the remove event.
    this.removeEventManager.emit({ item: this });
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
            table.style.height = "";
          }
          break;
        case "left":
        case "right":
          if (this.props.height > 0) {
            table.style.width = "";
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
  public moveElement(x: number, y: number): void {
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
  public resizeElement(width: number, height: number): void {
    // The most valuable size is the content size.
    if (
      this.props.type != ItemType.LINE_ITEM &&
      this.props.type != ItemType.NETWORK_LINK
    ) {
      this.childElementRef.style.width = width > 0 ? `${width}px` : "";
      this.childElementRef.style.height = height > 0 ? `${height}px` : "";
    }

    if (this.props.label && this.props.label.length > 0) {
      // Ugly table to show the label as its legacy counterpart.
      const tables = this.labelElementRef.getElementsByTagName("table");
      const table = tables.length > 0 ? tables.item(0) : null;

      if (table) {
        switch (this.props.labelPosition) {
          case "up":
          case "down":
            table.style.width = width > 0 ? `${width}px` : "";
            break;
          case "left":
          case "right":
            table.style.height = height > 0 ? `${height}px` : "";
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
  public onClick(listener: Listener<ItemClickEvent>): Disposable {
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
   * To add an event handler to the double click of the linked visual console elements.
   * @param listener Function which is going to be executed when a linked console is double clicked.
   */
  public onDblClick(listener: Listener<ItemClickEvent>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.dblClickEventManager.on(listener);
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
   * To add an event handler to the movement stopped of visual console elements.
   * @param listener Function which is going to be executed when a linked console's movement is finished.
   */
  public onMovementFinished(listener: Listener<ItemMovedEvent>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.movementFinishedEventManager.on(listener);
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
   * To add an event handler to the resizement finish of visual console elements.
   * @param listener Function which is going to be executed when a linked console is finished resizing.
   */
  public onResizeFinished(listener: Listener<ItemResizedEvent>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.resizeFinishedEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * To add an event handler to the removal of the item.
   * @param listener Function which is going to be executed when a item is removed.
   */
  public onRemove(listener: Listener<ItemRemoveEvent>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.removeEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * To add an event handler to item selection.
   * @param listener Function which is going to be executed when a item is removed.
   */
  public onSelectionChanged(
    listener: Listener<ItemSelectionChangedEvent>
  ): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.selectionChangedEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }

  /**
   * Select an item.
   * @param itemId Item Id.
   * @param unique To remove the selection of other items or not.
   */
  public selectItem(): void {
    this.meta = {
      ...this.meta,
      isSelected: true
    };

    this.initMovementListener(this.elementRef);
    if (
      this.props.type !== ItemType.LINE_ITEM &&
      this.props.type !== ItemType.NETWORK_LINK
    ) {
      this.initResizementListener(this.elementRef);
    }
  }

  /**
   * Unselect an item.
   * @param itemId Item Id.
   */
  public unSelectItem(): void {
    this.meta = {
      ...this.meta,
      isSelected: false
    };

    this.stopMovementListener();
    if (this.props.type !== ItemType.LINE_ITEM) {
      this.stopResizementListener();
    }
  }

  // TODO: Document
  public getFormContainer(): FormContainer {
    return VisualConsoleItem.getFormContainer(this.props);
  }

  // TODO: Document
  public static getFormContainer(props: Partial<ItemProps>): FormContainer {
    const title: string = props.type ? titleItem(props.type) : t("Item");
    return new FormContainer(title, [], []);
  }
}

export default VisualConsoleItem;
