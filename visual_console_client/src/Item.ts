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
  helpTip,
  periodSelector,
  autocompleteInput
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
  cacheExpiration: number | null;
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
 * This item consists of a label and a Parent select.
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
 * This item consists of a label and a Acl Group type select.
 * Acl is stored in the aclGroupId property
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

// TODO: Document
class CacheExpirationInputGroup extends InputGroup<Partial<ItemProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const periodLabel = document.createElement("label");
    periodLabel.textContent = t("Cache expiration");

    const periodControl = periodSelector(
      this.currentData.cacheExpiration || this.initialData.cacheExpiration || 0,
      { text: t("No cache"), value: 0 },
      [
        { text: t("10 seconds"), value: 10 },
        { text: t("30 seconds"), value: 30 },
        { text: t("60 seconds"), value: 60 },
        { text: t("5 minutes"), value: 300 },
        { text: t("15 minutes"), value: 900 },
        { text: t("30 minutes"), value: 1800 },
        { text: t("1 hour"), value: 3600 }
      ],
      value => this.updateData({ cacheExpiration: value })
    );

    periodLabel.appendChild(periodControl);

    return periodLabel;
  }
}

/**
 * Class to add item to the general items form
 * This item consists of a label and a Link console type select.
 * Parent is stored in the parentId property
 */
export class LinkConsoleInputGroup extends InputGroup<
  Partial<ItemProps & LinkedVisualConsoleProps>
> {
  protected createContent(): HTMLElement | HTMLElement[] {
    // Create div container.
    const container = document.createElement("div");
    const lvcTypeContainer = document.createElement("div");

    // Create Principal element label - select.
    const linkConsoleLabel = document.createElement("label");
    linkConsoleLabel.textContent = t("Linked visual console	");

    // Create element Spinner.
    const spinner = fontAwesomeIcon(faCircleNotch, t("Spinner"), {
      size: "small",
      spin: true
    });
    linkConsoleLabel.appendChild(spinner);

    // Init request
    this.requestData("link-console", {}, (error, data) => {
      // Remove Spinner.
      spinner.remove();

      // Check errors.
      if (error) {
        // Add img error.
        linkConsoleLabel.appendChild(
          fontAwesomeIcon(faExclamationCircle, t("Error"), {
            size: "small",
            color: "#e63c52"
          })
        );
      }

      // Create principal element select
      const linkConsoleSelect = document.createElement("select");
      linkConsoleSelect.required = true;

      // Default option principal select.
      const defaultOptionElement = document.createElement("option");
      defaultOptionElement.value = "0";
      defaultOptionElement.textContent = t("none");
      linkConsoleSelect.appendChild(defaultOptionElement);

      // Check data is array
      if (data instanceof Array) {
        // Create other options for principal select.
        data.forEach(option => {
          let id = option.id;
          // Check if metaconsole save id|nodeID.
          if (option.nodeId) {
            id = `${option.id}|${option.nodeId}`;
          }

          // Create option
          const optionElement = document.createElement("option");
          optionElement.value = id;
          optionElement.textContent = option.name;
          linkConsoleSelect.appendChild(optionElement);
        });

        // Set values.
        // Principal values .
        // Convert current data to string if meta id|idNode or only id if node.
        let currentValue: string | undefined;
        if (typeof this.currentData.linkedLayoutId !== "undefined") {
          currentValue =
            typeof this.currentData.linkedLayoutNodeId !== "undefined" &&
            this.currentData.linkedLayoutNodeId !== 0
              ? `${this.currentData.linkedLayoutId}|${
                  this.currentData.linkedLayoutNodeId
                }`
              : `${this.currentData.linkedLayoutId}`;
        }

        // Convert Initial data to string if meta id|idNode or only id if node.
        let initialValue: string | undefined;
        if (typeof this.initialData.linkedLayoutId !== "undefined") {
          initialValue =
            typeof this.initialData.linkedLayoutNodeId !== "undefined" &&
            this.initialData.linkedLayoutNodeId !== 0
              ? `${this.initialData.linkedLayoutId}|${
                  this.initialData.linkedLayoutNodeId
                }`
              : `${this.initialData.linkedLayoutId}`;
        }

        linkConsoleSelect.value = `${currentValue || initialValue || 0}`;

        // Listener event change select principal.
        linkConsoleSelect.addEventListener("change", event => {
          // Convert value to insert data.
          const linkedLayoutExtract = (event.target as HTMLSelectElement).value.split(
            "|"
          );

          let linkedLayoutNodeId = 0;
          let linkedLayoutId = 0;
          if (linkedLayoutExtract instanceof Array) {
            linkedLayoutId = parseIntOr(linkedLayoutExtract[0], 0);
            linkedLayoutNodeId = parseIntOr(linkedLayoutExtract[1], 0);
          }

          // Update data element.
          this.updateData({
            linkedLayoutId: linkedLayoutId,
            linkedLayoutNodeId: linkedLayoutNodeId,
            linkedLayoutStatusType: "default"
          });

          // Add containerType to container.
          lvcTypeContainer.childNodes.forEach(n => n.remove());
          lvcTypeContainer.appendChild(
            this.getLinkedVisualConsoleTypeSelector(linkedLayoutId)
          );
        });

        // Add principal select to label.
        linkConsoleLabel.appendChild(linkConsoleSelect);

        // Add weight warning field.
        container.appendChild(linkConsoleLabel);

        // Add containerType to container.
        lvcTypeContainer.appendChild(
          this.getLinkedVisualConsoleTypeSelector(
            parseIntOr(this.initialData.linkedLayoutId, 0)
          )
        );
        container.appendChild(lvcTypeContainer);
      } else {
        // Add principal select to label.
        linkConsoleLabel.appendChild(linkConsoleSelect);
        container.appendChild(linkConsoleLabel);
      }
    });

    return container;
  }

  private getLinkedVisualConsoleTypeSelector = (
    linkedLayoutId: number
  ): HTMLElement => {
    // Create div container Type.
    const containerType = document.createElement("div");

    const lvcTypeContainerChild = document.createElement("div");

    // Check id visual console for show label type.
    if (linkedLayoutId === 0) return containerType;

    // Select type link console appears when selecting a visual console
    // from the main select.
    // Label type link.
    const typeLinkConsoleLabel = document.createElement("label");
    typeLinkConsoleLabel.textContent = t(
      "Type of the status calculation of the linked visual console"
    );

    // Select type link.
    const typeLinkConsoleSelect = document.createElement("select");
    typeLinkConsoleSelect.required = false;

    // Array types for Linked. default | weight | service.
    const arrayTypeLinked = [
      { value: "default", text: t("By default") },
      { value: "weight", text: t("By status weight") },
      { value: "service", text: t("By critical elements") }
    ];

    // Create options select type link.
    arrayTypeLinked.forEach(option => {
      const typeOptionElement = document.createElement("option");
      typeOptionElement.value = option.value;
      typeOptionElement.textContent = option.text;
      typeLinkConsoleSelect.appendChild(typeOptionElement);
    });

    // Set values undef is default.
    let value: LinkedVisualConsoleProps["linkedLayoutStatusType"];
    value =
      typeof this.currentData.linkedLayoutStatusType === "undefined"
        ? typeof this.initialData.linkedLayoutStatusType === "undefined"
          ? "default"
          : this.initialData.linkedLayoutStatusType
        : this.currentData.linkedLayoutStatusType;

    typeLinkConsoleSelect.value = value;

    // Add select type link.
    typeLinkConsoleLabel.appendChild(typeLinkConsoleSelect);

    // Add type link.
    containerType.appendChild(typeLinkConsoleLabel);

    switch (value) {
      case "weight":
        // Add Chil container with weight.
        lvcTypeContainerChild.appendChild(
          this.getLinkedVisualConsoleTypeWeihtInput()
        );
        break;
      case "service":
        // Add Chil container with weight.
        lvcTypeContainerChild.appendChild(
          this.getLinkedVisualConsoleTypeServiceInput()
        );
        break;
      default:
        break;
    }

    // Add types.
    containerType.appendChild(lvcTypeContainerChild);

    // Listener event change select type link.
    typeLinkConsoleSelect.addEventListener("change", event => {
      // Convert value to insert data.
      let value = (event.target as HTMLSelectElement).value;
      let linkedLayoutStatusType: LinkedVisualConsoleProps["linkedLayoutStatusType"] =
        value !== "weight" && value !== "service" ? "default" : value;

      lvcTypeContainerChild.childNodes.forEach(n => n.remove());

      switch (linkedLayoutStatusType) {
        case "weight":
          // Update data element.
          this.updateData({
            linkedLayoutStatusType,
            linkedLayoutStatusTypeWeight: 0
          });

          // Add Chil container with weight.
          lvcTypeContainerChild.appendChild(
            this.getLinkedVisualConsoleTypeWeihtInput()
          );
          break;
        case "service":
          // Update data element.
          this.updateData({
            linkedLayoutStatusType,
            linkedLayoutStatusTypeWarningThreshold: 0,
            linkedLayoutStatusTypeCriticalThreshold: 0
          });

          // Add Chil container with weight.
          lvcTypeContainerChild.appendChild(
            this.getLinkedVisualConsoleTypeServiceInput()
          );
          break;
        default:
          // Update data element.
          this.updateData({
            linkedLayoutStatusType
          });
          break;
      }
    });

    return containerType;
  };

  private getLinkedVisualConsoleTypeWeihtInput = (): HTMLElement => {
    // Crete div container child type.
    const containerChildType = document.createElement("div");

    // Input selected type = weight.
    // from the select type.
    // Label.
    const weightLabel = document.createElement("label");
    weightLabel.textContent = t("Linked visual console weight");

    // Input.
    const weightInput = document.createElement("input");
    weightInput.type = "number";
    weightInput.min = "0";
    weightInput.required = true;

    let currentValueWeight: number | undefined;
    if (this.currentData.linkedLayoutStatusType === "weight") {
      currentValueWeight = this.currentData.linkedLayoutStatusTypeWeight;
    }

    let initialValueWeight: number | undefined;
    if (this.initialData.linkedLayoutStatusType === "weight") {
      initialValueWeight = this.initialData.linkedLayoutStatusTypeWeight;
    }

    weightInput.value = `${currentValueWeight || initialValueWeight || 0}`;

    weightInput.addEventListener("change", e =>
      this.updateData({
        linkedLayoutStatusTypeWeight: parseIntOr(
          (e.target as HTMLInputElement).value,
          0
        )
      })
    );

    // Add input weight.
    weightLabel.appendChild(weightInput);

    // Add label weight.
    containerChildType.appendChild(weightLabel);

    return containerChildType;
  };

  private getLinkedVisualConsoleTypeServiceInput = (): HTMLElement => {
    // Crete div container child type.
    const containerChildType = document.createElement("div");

    // Input selected type = services.
    // from the select type.
    // Label.
    const criticalWeightLabel = document.createElement("label");
    criticalWeightLabel.textContent = t("Critical weight");

    //Input.
    const criticalWeightInput = document.createElement("input");
    criticalWeightInput.type = "number";
    criticalWeightInput.min = "0";
    criticalWeightInput.required = true;

    let currentValueCritical: number | undefined;
    if (this.currentData.linkedLayoutStatusType === "service") {
      currentValueCritical = this.currentData
        .linkedLayoutStatusTypeCriticalThreshold;
    }

    let initialValueCritical: number | undefined;
    if (this.initialData.linkedLayoutStatusType === "service") {
      initialValueCritical = this.initialData
        .linkedLayoutStatusTypeCriticalThreshold;
    }

    criticalWeightInput.value = `${currentValueCritical ||
      initialValueCritical ||
      0}`;

    criticalWeightInput.addEventListener("change", e =>
      this.updateData({
        linkedLayoutStatusTypeCriticalThreshold: parseIntOr(
          (e.target as HTMLInputElement).value,
          0
        )
      })
    );

    // Input selected type = services.
    // from the select type.
    // Label.
    const warningWeightLabel = document.createElement("label");
    warningWeightLabel.textContent = t("Warning weight");

    //Input.
    const warningWeightInput = document.createElement("input");
    warningWeightInput.type = "number";
    warningWeightInput.min = "0";
    warningWeightInput.required = true;

    let currentValueWarning: number | undefined;
    if (this.currentData.linkedLayoutStatusType === "service") {
      currentValueWarning = this.currentData
        .linkedLayoutStatusTypeWarningThreshold;
    }

    let initialValueWarning: number | undefined;
    if (this.initialData.linkedLayoutStatusType === "service") {
      initialValueWarning = this.initialData
        .linkedLayoutStatusTypeWarningThreshold;
    }

    warningWeightInput.value = `${currentValueWarning ||
      initialValueWarning ||
      0}`;

    warningWeightInput.addEventListener("change", e =>
      this.updateData({
        linkedLayoutStatusTypeWarningThreshold: parseIntOr(
          (e.target as HTMLInputElement).value,
          0
        )
      })
    );

    // Add input weight warning.
    warningWeightLabel.appendChild(warningWeightInput);

    // Add label warning field.
    containerChildType.appendChild(warningWeightLabel);

    // Add input crital weight.
    criticalWeightLabel.appendChild(criticalWeightInput);

    // Add label weight critical.
    containerChildType.appendChild(criticalWeightLabel);

    return containerChildType;
  };
}

interface ImageInputGroupProps {
  imageSrc: string | null;
  image: string | null;
}

/**
 * Class to add item to the static Graph item form
 * This item consists of a label and a Image select.
 * Show Last Value is stored in the showLastValueTooltip property
 */
export class ImageInputGroup extends InputGroup<
  Partial<ImageInputGroupProps> & {
    imageKey: keyof ImageInputGroupProps;
    showStatusImg?: boolean;
  }
> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const imageKey = this.initialData.imageKey;
    const imageLabel = document.createElement("label");
    imageLabel.textContent = t("Image");

    const divImage = document.createElement("div");

    // Create element Spinner.
    const spinner = fontAwesomeIcon(faCircleNotch, t("Spinner"), {
      size: "small",
      spin: true
    });
    imageLabel.appendChild(spinner);

    // Init request
    this.requestData("image-console", {}, (error, data) => {
      // Remove Spinner.
      spinner.remove();

      // Check errors.
      if (error) {
        // Add img error.
        imageLabel.appendChild(
          fontAwesomeIcon(faExclamationCircle, t("Error"), {
            size: "small",
            color: "#e63c52"
          })
        );
      }

      if (data instanceof Array) {
        const labelSelect = document.createElement("select");
        labelSelect.required = true;

        data.forEach(option => {
          const optionElement = document.createElement("option");
          optionElement.value = option.name;
          optionElement.textContent = option.name;
          labelSelect.appendChild(optionElement);
        });

        labelSelect.addEventListener("change", event => {
          const imageSrc = (event.target as HTMLSelectElement).value;
          this.updateData({ [imageKey]: imageSrc });

          if (imageSrc != null) {
            const imageItem = data.find(item => item.name === imageSrc);
            this.getImage(imageItem, divImage);
          }
        });

        const valueImage = `${this.currentData[imageKey] ||
          this.initialData[imageKey] ||
          null}`;

        labelSelect.value = valueImage;

        imageLabel.appendChild(labelSelect);

        if (valueImage != null) {
          const imageItem = data.find(item => item.name === valueImage);
          imageLabel.appendChild(this.getImage(imageItem, divImage));
        }
      }
    });

    return imageLabel;
  }

  private getImage(
    imageItem: HTMLImageElement,
    divImage: HTMLElement
  ): HTMLElement {
    if (imageItem) {
      const deleteImg = divImage.querySelectorAll(".img-vc-elements");
      deleteImg.forEach(value => {
        divImage.removeChild(value);
      });

      if (this.initialData.showStatusImg) {
        const imageTypes = ["", "_bad", "_ok", "_warning"];
        imageTypes.forEach(value => {
          const imagePaint = document.createElement("img");
          imagePaint.alt = t("Image VC");
          imagePaint.style.width = "40px";
          imagePaint.style.height = "40px";
          imagePaint.className = "img-vc-elements";
          imagePaint.src = `${imageItem.src}${value}.png`;
          divImage.appendChild(imagePaint);
        });
      } else {
        const imagePaint = document.createElement("img");
        imagePaint.alt = t("Image VC");
        imagePaint.style.width = "40px";
        imagePaint.style.height = "40px";
        imagePaint.className = "img-vc-elements";
        imagePaint.src = `${imageItem.src}.png`;
        divImage.appendChild(imagePaint);
      }
    }

    return divImage;
  }
}

interface AgentAutocompleteData {
  metaconsoleId?: number | null;
  agentId: number;
  agentName: string | null;
  agentAlias: string | null;
  agentDescription: string | null;
  agentAddress: string | null;
}

/**
 * Class to add item to the general items form
 * This item consists of a label and agent select.
 * Agent and module is stored in the  property
 */
export class AgentModuleInputGroup extends InputGroup<Partial<WithAgentProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const agentLabel = document.createElement("label");
    agentLabel.textContent = t("Agent");

    const agentInput = document.createElement("input");
    agentInput.type = "text";
    agentInput.required = true;
    agentInput.className = "autocomplete-agent";

    //const imgeAgent = "";
    //agentInput.style.backgroundImage = `url(${imgeAgent})`;

    //agentInput.value = `${this.currentData.width ||
    //  this.initialData.width ||
    //  0}`;

    const handleDataRequested = (
      value: string,
      done: (data: AgentAutocompleteData[]) => void
    ) => {
      this.requestData("autocomplete-agent", { value }, (error, data) => {
        if (error) {
          done([]);
          return;
        }

        if (data instanceof Array) {
          done(
            data.reduce((prev: AgentAutocompleteData[], current: AnyObject) => {
              if (typeof current === "object" && current !== null) {
                const agentId = parseIntOr(current.id, null);
                if (agentId !== null) {
                  prev.push({
                    agentId,
                    agentName: notEmptyStringOr(current.name, null),
                    agentAlias: notEmptyStringOr(current.alias, null),
                    agentAddress: notEmptyStringOr(current.ip, null),
                    agentDescription: notEmptyStringOr(
                      current.agentDescription,
                      null
                    ),
                    metaconsoleId: parseIntOr(current.metaconsoleId, null)
                  });
                }
              }
              return prev;
            }, [])
          );
        }
      });
    };

    const renderListItem = (item: AgentAutocompleteData) => {
      const listItem = document.createElement("div");
      listItem.textContent = item.agentAddress
        ? `${item.agentAlias} - ${item.agentAddress}`
        : item.agentAlias;
      return listItem;
    };

    const handleSelected = (item: AgentAutocompleteData): string => {
      this.updateData({ agentId: item.agentId });

      const selectedItem = item.agentAddress
        ? `${item.agentAlias} - ${item.agentAddress}`
        : item.agentAlias;

      return `${selectedItem || ""}`;
    };

    agentLabel.appendChild(
      autocompleteInput("", handleDataRequested, renderListItem, handleSelected)
    );

    return agentLabel;
  }

  private agentModuleInput(item: AgentAutocompleteData): HTMLElement {
    const agentModuleLabel = document.createElement("label");
    agentModuleLabel.textContent = t("Module");

    // Create element Spinner.
    const spinner = fontAwesomeIcon(faCircleNotch, t("Spinner"), {
      size: "small",
      spin: true
    });
    agentModuleLabel.appendChild(spinner);

    // Init request
    this.requestData("autocomplete-module", {}, (error, data) => {
      // Remove Spinner.
      spinner.remove();

      // Check errors.
      if (error) {
        // Add img error.
        agentModuleLabel.appendChild(
          fontAwesomeIcon(faExclamationCircle, t("Error"), {
            size: "small",
            color: "#e63c52"
          })
        );
      }

      if (data instanceof Array) {
        const agentModuleSelect = document.createElement("select");
        agentModuleSelect.required = true;

        data.forEach(option => {
          const optionElement = document.createElement("option");
          optionElement.value = option.name;
          optionElement.textContent = option.name;
          agentModuleSelect.appendChild(optionElement);
        });

        /*
        labelSelect.addEventListener("change", event => {
          const imageSrc = (event.target as HTMLSelectElement).value;
          this.updateData({ [imageKey]: imageSrc });

          if (imageSrc != null) {
            const imageItem = data.find(item => item.name === imageSrc);
            this.getImage(imageItem, divImage);
          }
        });

        const valueImage = `${this.currentData[imageKey] ||
          this.initialData[imageKey] ||
          null}`;

          labelSelect.value = valueImage;
        */

        agentModuleLabel.appendChild(agentModuleSelect);
      }
    });

    return agentModuleLabel;
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
    cacheExpiration: parseIntOr(data.cacheExpiration, null),
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
    this.removeMovement = addMovementListener(
      element,
      (x: Position["x"], y: Position["y"]) => {
        const prevPosition = {
          x: this.props.x,
          y: this.props.y
        };
        const newPosition = { x, y };

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
      if (this.props.link) box.href = this.props.link;
    } else {
      box = document.createElement("div") as HTMLDivElement;
    }

    box.className = "visual-console-item";
    if (this.props.isOnTop) {
      box.classList.add("is-on-top");
    }
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
    //Change z-index class is-on-top
    if (!prevProps || prevProps.isOnTop !== this.props.isOnTop) {
      if (this.props.isOnTop) {
        this.elementRef.classList.add("is-on-top");
      } else {
        this.elementRef.classList.remove("is-on-top");
      }
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
        new AclGroupInputGroup("acl-group", this.props),
        new CacheExpirationInputGroup("cache-expiration", this.props)
      ],
      [
        "position",
        "size",
        "link",
        "show-on-top",
        "parent",
        "acl-group",
        "cache-expiration"
      ]
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
        new AclGroupInputGroup("acl-group", props),
        new CacheExpirationInputGroup("cache-expiration", props)
      ],
      [
        "position",
        "size",
        "link",
        "show-on-top",
        "parent",
        "acl-group",
        "cache-expiration"
      ]
    );
  }
}

export default VisualConsoleItem;
