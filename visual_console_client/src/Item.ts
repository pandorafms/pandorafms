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
  addResizementListener,
  t,
  helpTip
} from "./lib";
import TypedEvent, { Listener, Disposable } from "./lib/TypedEvent";
import { FormContainer, InputGroup } from "./Form";

import {
  faCircleNotch,
  faExclamationCircle
} from "@fortawesome/free-solid-svg-icons";
import fontAwesomeIcon from "./lib/FontAwesomeIcon";

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

// TODO: Document
class LinkInputGroup extends InputGroup<Partial<ItemProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const linkLabel = document.createElement("label");
    linkLabel.textContent = t("Link enabled");

    const linkInputChkbx = document.createElement("input");
    linkInputChkbx.id = "checkbox-switch";
    linkInputChkbx.className = "checkbox-switch";
    linkInputChkbx.type = "checkbox";
    linkInputChkbx.name = "checkbox-enable-link";
    linkInputChkbx.value = "1";
    linkInputChkbx.checked =
      this.currentData.isLinkEnabled || this.initialData.isLinkEnabled || false;
    linkInputChkbx.addEventListener("change", e =>
      this.updateData({
        isLinkEnabled: (e.target as HTMLInputElement).checked
      })
    );

    const linkInputLabel = document.createElement("label");
    linkInputLabel.className = "label-switch";
    linkInputLabel.htmlFor = "checkbox-switch";

    linkLabel.appendChild(linkInputChkbx);
    linkLabel.appendChild(linkInputLabel);

    return linkLabel;
  }
}

// TODO: Document
class OnTopInputGroup extends InputGroup<Partial<ItemProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const onTopLabel = document.createElement("label");
    onTopLabel.textContent = t("Show on top");

    const onTopInputChkbx = document.createElement("input");
    onTopInputChkbx.id = "checkbox-switch";
    onTopInputChkbx.className = "checkbox-switch";
    onTopInputChkbx.type = "checkbox";
    onTopInputChkbx.name = "checkbox-show-on-top";
    onTopInputChkbx.value = "1";
    onTopInputChkbx.checked =
      this.currentData.isOnTop || this.initialData.isOnTop || false;
    onTopInputChkbx.addEventListener("change", e =>
      this.updateData({
        isOnTop: (e.target as HTMLInputElement).checked
      })
    );

    const onTopInputLabel = document.createElement("label");
    onTopInputLabel.className = "label-switch";
    onTopInputLabel.htmlFor = "checkbox-switch";

    onTopLabel.appendChild(
      helpTip(
        t(
          "It allows the element to be superimposed to the rest of items of the visual console"
        )
      )
    );
    onTopLabel.appendChild(onTopInputChkbx);
    onTopLabel.appendChild(onTopInputLabel);

    return onTopLabel;
  }
}

// TODO: Document
class PositionInputGroup extends InputGroup<Partial<ItemProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const positionLabel = document.createElement("label");
    positionLabel.textContent = t("Position");

    const positionInputX = document.createElement("input");
    positionInputX.type = "number";
    positionInputX.min = "0";
    positionInputX.required = true;
    positionInputX.value = `${this.currentData.x || this.initialData.x || 0}`;
    positionInputX.addEventListener("change", e =>
      this.updateData({
        x: parseIntOr((e.target as HTMLInputElement).value, 0)
      })
    );

    const positionInputY = document.createElement("input");
    positionInputY.type = "number";
    positionInputY.min = "0";
    positionInputY.required = true;
    positionInputY.value = `${this.currentData.y || this.initialData.y || 0}`;
    positionInputY.addEventListener("change", e =>
      this.updateData({
        y: parseIntOr((e.target as HTMLInputElement).value, 0)
      })
    );

    positionLabel.appendChild(positionInputX);
    positionLabel.appendChild(positionInputY);

    return positionLabel;
  }
}

// TODO: Document
class SizeInputGroup extends InputGroup<Partial<ItemProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const sizeLabel = document.createElement("label");
    sizeLabel.textContent = t("Size");

    const sizeInputWidth = document.createElement("input");
    sizeInputWidth.type = "number";
    sizeInputWidth.min = "0";
    sizeInputWidth.required = true;
    sizeInputWidth.value = `${this.currentData.width ||
      this.initialData.width ||
      0}`;
    sizeInputWidth.addEventListener("change", e =>
      this.updateData({
        width: parseIntOr((e.target as HTMLInputElement).value, 0)
      })
    );

    const sizeInputHeight = document.createElement("input");
    sizeInputHeight.type = "number";
    sizeInputHeight.min = "0";
    sizeInputHeight.required = true;
    sizeInputHeight.value = `${this.currentData.height ||
      this.initialData.height ||
      0}`;
    sizeInputHeight.addEventListener("change", e =>
      this.updateData({
        height: parseIntOr((e.target as HTMLInputElement).value, 0)
      })
    );

    sizeLabel.appendChild(
      helpTip(
        t(
          "In order to use the original image file size, set width and height to 0."
        )
      )
    );
    sizeLabel.appendChild(sizeInputWidth);
    sizeLabel.appendChild(sizeInputHeight);

    return sizeLabel;
  }
}

/**
 * Class to add item to the general items form
 * This item consists of a label and a color type select.
 * Parent is stored in the parentId property
 */
class ParentInputGroup extends InputGroup<Partial<ItemProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const parentLabel = document.createElement("label");
    parentLabel.textContent = t("Parent");

    const parentSelect = document.createElement("select");
    parentSelect.required = true;

    this.requestData("parent", { id: this.initialData.id }, (error, data) => {
      const optionElement = document.createElement("option");
      optionElement.value = "0";
      optionElement.textContent = t("None");
      parentSelect.appendChild(optionElement);

      if (data instanceof Array) {
        data.forEach(option => {
          const optionElement = document.createElement("option");
          optionElement.value = option.value;
          optionElement.textContent = option.text;
          parentSelect.appendChild(optionElement);
        });
      }

      parentSelect.addEventListener("change", event => {
        this.updateData({
          parentId: parseIntOr((event.target as HTMLSelectElement).value, 0)
        });
      });

      parentSelect.value = `${this.currentData.parentId ||
        this.initialData.parentId ||
        0}`;
    });

    parentLabel.appendChild(parentSelect);

    return parentLabel;
  }
}

/**
 * Class to add item to the general items form
 * This item consists of a label and a color type select.
 * Parent is stored in the parentId property
 */
class AclGroupInputGroup extends InputGroup<Partial<ItemProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const aclGroupLabel = document.createElement("label");
    aclGroupLabel.textContent = t("Restrict access to group");

    const spinner = fontAwesomeIcon(faCircleNotch, t("Spinner"), {
      size: "small",
      spin: true
    });
    aclGroupLabel.appendChild(spinner);

    this.requestData("acl-group", {}, (error, data) => {
      // Remove Spinner.
      spinner.remove();

      if (error) {
        aclGroupLabel.appendChild(
          fontAwesomeIcon(faExclamationCircle, t("Error"), {
            size: "small",
            color: "#e63c52"
          })
        );
      }

      if (data instanceof Array) {
        const aclGroupSelect = document.createElement("select");
        aclGroupSelect.required = true;

        data.forEach(option => {
          const optionElement = document.createElement("option");
          optionElement.value = option.value;
          // Dangerous because injection sql.
          // Use textContent for innerHTML.
          // Here it is used to show the depth of the groups.
          optionElement.innerHTML = option.text;
          aclGroupSelect.appendChild(optionElement);
        });

        aclGroupSelect.addEventListener("change", event => {
          this.updateData({
            aclGroupId: parseIntOr((event.target as HTMLSelectElement).value, 0)
          });
        });

        aclGroupSelect.value = `${this.currentData.aclGroupId ||
          this.initialData.aclGroupId ||
          0}`;

        aclGroupLabel.appendChild(aclGroupSelect);
      }
    });

    return aclGroupLabel;
  }
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
  // Event manager for resized events.
  private readonly resizedEventManager = new TypedEvent<ItemResizedEvent>();
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
  private debouncedMovementSave = debounce(
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
        // Update the metadata information.
        // Don't use the .meta property cause we don't need DOM updates.
        this._metadata.isBeingMoved = true;
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
      // Update the metadata information.
      // Don't use the .meta property cause we don't need DOM updates.
      this._metadata.isBeingResized = false;

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
      if (this.props.link) box.href = this.props.link;
    } else {
      box = document.createElement("div") as HTMLDivElement;
    }

    box.className = "visual-console-item";
    box.style.zIndex = this.props.isOnTop ? "2" : "1";
    box.style.left = `${this.props.x}px`;
    box.style.top = `${this.props.y}px`;

    // Init the click listeners.
    box.addEventListener("dblclick", e => {
      if (!this.meta.isBeingMoved && !this.meta.isBeingResized) {
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
      }

      if (!this.meta.isBeingMoved && !this.meta.isBeingResized) {
        this.clickEventManager.emit({
          item: this,
          nativeEvent: e
        });
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
    }
    if (!prevMeta || prevMeta.isSelected !== this.meta.isSelected) {
      if (this.meta.isSelected) {
        this.elementRef.classList.add("is-selected");
      } else {
        this.elementRef.classList.remove("is-selected");
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
  public onSeletionChanged(
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

  // TODO: Document
  public getFormContainer(): FormContainer {
    return new FormContainer(
      t("Item"),
      [
        new PositionInputGroup("position", this.props),
        new SizeInputGroup("size", this.props),
        new LinkInputGroup("link", this.props),
        new OnTopInputGroup("show-on-top", this.props),
        new ParentInputGroup("parent", this.props),
        new AclGroupInputGroup("acl-group", this.props)
      ],
      ["position", "size", "link", "show-on-top", "parent", "acl-group"]
    );

    //return VisualConsoleItem.getFormContainer(this.props);
  }

  // TODO: Document
  public static getFormContainer(props: Partial<ItemProps>): FormContainer {
    return new FormContainer(
      t("Item"),
      [
        new PositionInputGroup("position", props),
        new SizeInputGroup("size", props),
        new LinkInputGroup("link", props),
        new OnTopInputGroup("show-on-top", props),
        new ParentInputGroup("parent", props),
        new AclGroupInputGroup("acl-group", props)
      ],
      ["position", "size", "link", "show-on-top", "parent", "acl-group"]
    );
  }
}

export default VisualConsoleItem;
