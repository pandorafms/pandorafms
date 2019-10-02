import { AnyObject } from "../lib/types";
import {
  stringIsEmpty,
  notEmptyStringOr,
  decodeBase64,
  parseIntOr,
  t
} from "../lib";
import Item, {
  ItemType,
  ItemProps,
  itemBasePropsDecoder,
  ImageInputGroup
} from "../Item";
import { FormContainer, InputGroup } from "../Form";
import fontAwesomeIcon from "../lib/FontAwesomeIcon";
import {
  faCircleNotch,
  faExclamationCircle
} from "@fortawesome/free-solid-svg-icons";

export type ServiceProps = {
  type: ItemType.SERVICE;
  serviceId: number;
  imageSrc: string | null;
  statusImageSrc: string | null;
  encodedTitle: string | null;
} & ItemProps;

/**
 * Build a valid typed object from a raw object.
 * This will allow us to ensure the type safety.
 *
 * @param data Raw object.
 * @return An object representing the service props.
 * @throws Will throw a TypeError if some property
 * is missing from the raw object or have an invalid type.
 */
export function servicePropsDecoder(data: AnyObject): ServiceProps | never {
  if (data.imageSrc !== null) {
    if (
      typeof data.statusImageSrc !== "string" ||
      data.imageSrc.statusImageSrc === 0
    ) {
      throw new TypeError("invalid status image src.");
    }
  } else {
    if (stringIsEmpty(data.encodedTitle)) {
      throw new TypeError("missing encode tittle content.");
    }
  }

  if (parseIntOr(data.serviceId, null) === null) {
    throw new TypeError("invalid service id.");
  }

  return {
    ...itemBasePropsDecoder(data), // Object spread. It will merge the properties of the two objects.
    type: ItemType.SERVICE,
    serviceId: data.serviceId,
    imageSrc: notEmptyStringOr(data.imageSrc, null),
    statusImageSrc: notEmptyStringOr(data.statusImageSrc, null),
    encodedTitle: notEmptyStringOr(data.encodedTitle, null)
  };
}

/**
 * Class to add item to the general items form
 * This item consists of a label and a Service List type select.
 */
class ServiceListInputGroup extends InputGroup<Partial<ServiceProps>> {
  protected createContent(): HTMLElement | HTMLElement[] {
    const serviceListLabel = document.createElement("label");
    serviceListLabel.textContent = t("Service");

    const spinner = fontAwesomeIcon(faCircleNotch, t("Spinner"), {
      size: "small",
      spin: true
    });
    serviceListLabel.appendChild(spinner);

    this.requestData("service-list", {}, (error, data) => {
      // Remove Spinner.
      spinner.remove();

      if (error) {
        serviceListLabel.appendChild(
          fontAwesomeIcon(faExclamationCircle, t("Error"), {
            size: "small",
            color: "#e63c52"
          })
        );
      }

      if (data instanceof Array) {
        const serviceListSelect = document.createElement("select");
        serviceListSelect.required = true;

        data.forEach(option => {
          const optionElement = document.createElement("option");
          optionElement.value = option.id;
          optionElement.textContent = option.name;
          serviceListSelect.appendChild(optionElement);
        });

        serviceListSelect.value = `${this.currentData.serviceId ||
          this.initialData.serviceId ||
          0}`;

        serviceListSelect.addEventListener("change", event => {
          if (typeof (event.target as HTMLSelectElement).value === "string") {
            const id = (event.target as HTMLSelectElement).value.split("|")[0];
            this.updateData({
              serviceId: parseIntOr(id, 0)
            });
          } else {
            this.updateData({
              serviceId: parseIntOr(
                (event.target as HTMLSelectElement).value,
                0
              )
            });
          }
        });

        serviceListLabel.appendChild(serviceListSelect);
      }
    });

    return serviceListLabel;
  }
}

export default class Service extends Item<ServiceProps> {
  public createDomElement(): HTMLElement {
    const element = document.createElement("div");
    element.className = "service";

    if (this.props.statusImageSrc !== null) {
      element.style.background = `url(${this.props.statusImageSrc}) no-repeat`;
      element.style.backgroundSize = "contain";
      element.style.backgroundPosition = "center";
    } else if (this.props.encodedTitle !== null) {
      element.innerHTML = decodeBase64(this.props.encodedTitle);
    }

    return element;
  }

  /**
   * @override function to add or remove inputsGroups those that are not necessary.
   * Add to:
   * ImageInputGroup
   * ServiceListInputGroup
   */
  public getFormContainer(): FormContainer {
    return Service.getFormContainer(this.props);
  }

  public static getFormContainer(props: Partial<ServiceProps>): FormContainer {
    const formContainer = super.getFormContainer(props);
    formContainer.addInputGroup(
      new ImageInputGroup("image-console", {
        ...props,
        imageKey: "imageSrc",
        showStatusImg: false
      })
    );
    formContainer.addInputGroup(
      new ServiceListInputGroup("service-list", props)
    );

    return formContainer;
  }
}
