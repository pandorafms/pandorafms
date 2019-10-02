import { AnyObject } from "../lib/types";
import { parseIntOr, notEmptyStringOr, t } from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";
import { InputGroup, FormContainer } from "../Form";

interface BoxProps extends ItemProps {
  // Overrided properties.
  readonly type: ItemType.BOX_ITEM;
  label: null;
  isLinkEnabled: false;
  parentId: null;
  aclGroupId: null;
  // Custom properties.
  borderWidth: number;
  borderColor: string | null;
  fillColor: string | null;
}

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the item props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function boxPropsDecoder(data: AnyObject): BoxProps | never {
  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.BOX_ITEM,
    label: null,
    isLinkEnabled: false,
    parentId: null,
    aclGroupId: null,
    // Custom properties.
    borderWidth: parseIntOr(data.borderWidth, 0),
    borderColor: notEmptyStringOr(data.borderColor, null),
    fillColor: notEmptyStringOr(data.fillColor, null)
  };
}

/**
 * Class to add item to the Box item form
 * This item consists of a label and a color type input color.
 * Element border color is stored in the borderColor property
 */
class BorderColorInputGroup extends InputGroup<Partial<BoxProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const borderColorLabel = document.createElement("label");
    borderColorLabel.textContent = t("Border color");

    const borderColorInput = document.createElement("input");
    borderColorInput.type = "color";
    borderColorInput.required = true;

    borderColorInput.value = `${this.currentData.borderColor ||
      this.initialData.borderColor ||
      "#000000"}`;

    borderColorInput.addEventListener("change", e => {
      this.updateData({
        borderColor: (e.target as HTMLInputElement).value
      });
    });

    borderColorLabel.appendChild(borderColorInput);

    return borderColorLabel;
  }
}

/**
 * Class to add item to the Box item form
 * This item consists of a label and a color type input number.
 * Element border width is stored in the borderWidth property
 */
class BorderWidthInputGroup extends InputGroup<Partial<BoxProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const borderWidthLabel = document.createElement("label");
    borderWidthLabel.textContent = t("Border Width");

    const borderWidthInput = document.createElement("input");
    borderWidthInput.type = "number";
    borderWidthInput.min = "0";
    borderWidthInput.required = true;
    borderWidthInput.value = `${this.currentData.borderWidth ||
      this.initialData.borderWidth ||
      0}`;
    borderWidthInput.addEventListener("change", e =>
      this.updateData({
        borderWidth: parseIntOr((e.target as HTMLInputElement).value, 0)
      })
    );

    borderWidthLabel.appendChild(borderWidthInput);

    return borderWidthLabel;
  }
}

/**
 * Class to add item to the Box item form
 * This item consists of a label and a color type input color.
 * Element fill color is stored in the fillcolor property
 */
class FillColorInputGroup extends InputGroup<Partial<BoxProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const fillColorLabel = document.createElement("label");
    fillColorLabel.textContent = t("Fill color");

    const fillColorInput = document.createElement("input");
    fillColorInput.type = "color";
    fillColorInput.required = true;

    fillColorInput.value = `${this.currentData.fillColor ||
      this.initialData.fillColor ||
      "#000000"}`;

    fillColorInput.addEventListener("change", e => {
      this.updateData({
        fillColor: (e.target as HTMLInputElement).value
      });
    });

    fillColorLabel.appendChild(fillColorInput);

    return fillColorLabel;
  }
}

export default class Box extends Item<BoxProps> {
  protected createDomElement(): HTMLElement {
    const box: HTMLDivElement = document.createElement("div");
    box.className = "box";
    // To prevent this item to expand beyond its parent.
    box.style.boxSizing = "border-box";

    if (this.props.fillColor) {
      box.style.backgroundColor = this.props.fillColor;
    }

    // Border.
    if (this.props.borderWidth > 0) {
      box.style.borderStyle = "solid";
      // Control the max width to prevent this item to expand beyond its parent.
      const maxBorderWidth = Math.min(this.props.width, this.props.height) / 2;
      const borderWidth = Math.min(this.props.borderWidth, maxBorderWidth);
      box.style.borderWidth = `${borderWidth}px`;

      if (this.props.borderColor) {
        box.style.borderColor = this.props.borderColor;
      }
    }

    return box;
  }

  /**
   * To update the content element.
   * @override Item.updateDomElement
   */
  protected updateDomElement(element: HTMLElement): void {
    if (this.props.fillColor) {
      element.style.backgroundColor = this.props.fillColor;
    }

    // Border.
    if (this.props.borderWidth > 0) {
      element.style.borderStyle = "solid";
      // Control the max width to prevent this item to expand beyond its parent.
      const maxBorderWidth = Math.min(this.props.width, this.props.height) / 2;
      const borderWidth = Math.min(this.props.borderWidth, maxBorderWidth);
      element.style.borderWidth = `${borderWidth}px`;

      if (this.props.borderColor) {
        element.style.borderColor = this.props.borderColor;
      }
    }
  }

  /**
   * @override function to add or remove inputsGroups those that are not necessary.
   * Add to:
   * LinkConsoleInputGroup
   */
  public getFormContainer(): FormContainer {
    return Box.getFormContainer(this.props);
  }

  public static getFormContainer(props: Partial<BoxProps>): FormContainer {
    const formContainer = super.getFormContainer(props);
    formContainer.addInputGroup(
      new BorderColorInputGroup("border-color", props)
    );
    formContainer.addInputGroup(
      new BorderWidthInputGroup("border-width", props)
    );
    formContainer.addInputGroup(new FillColorInputGroup("fill-width", props));

    return formContainer;
  }
}
