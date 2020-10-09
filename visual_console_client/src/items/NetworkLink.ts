import { AnyObject, Position, Size, ItemMeta } from "../lib/types";
import {
  parseIntOr,
  notEmptyStringOr,
  debounce,
  addMovementListener
} from "../lib";
import Item, { ItemType, ItemProps, itemBasePropsDecoder } from "../Item";
import TypedEvent, { Listener, Disposable } from "../lib/TypedEvent";

interface NetworkLinkProps extends ItemProps {
  // Overrided properties.
  readonly type: ItemType.NETWORK_LINK;
  label: null;
  isLinkEnabled: false;
  parentId: null;
  aclGroupId: null;
  // Custom properties.
  startPosition: Position;
  endPosition: Position;
  lineWidth: number;
  color: string | null;
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
export function networkLinkPropsDecoder(
  data: AnyObject
): NetworkLinkProps | never {
  const props: NetworkLinkProps = {
    ...itemBasePropsDecoder({ ...data, width: 1, height: 1 }), // Object spread. It will merge the properties of the two objects.
    type: ItemType.NETWORK_LINK,
    label: null,
    isLinkEnabled: false,
    parentId: null,
    aclGroupId: null,
    // Initialize Position & Size.
    x: 0,
    y: 0,
    width: 0,
    height: 0,
    // Custom properties.
    startPosition: {
      x: parseIntOr(data.startX, 0),
      y: parseIntOr(data.startY, 0)
    },
    endPosition: {
      x: parseIntOr(data.endX, 0),
      y: parseIntOr(data.endY, 0)
    },
    lineWidth: parseIntOr(data.lineWidth || data.borderWidth, 1),
    color: notEmptyStringOr(data.borderColor || data.color, null)
  };

  /*
   * We need to enhance the props with the extracted size and position
   * of the box cause there are missing at the props update. A better
   * solution would be overriding the props setter to do it there, but
   * the language doesn't allow it while targetting ES5.
   * TODO: We need to figure out a more consistent solution.
   */

  return {
    ...props,
    // Enhance the props extracting the box size and position.
    // eslint-disable-next-line @typescript-eslint/no-use-before-define
    ...NetworkLink.extractBoxSizeAndPosition(
      props.startPosition,
      props.endPosition
    )
  };
}

const svgNS = "http://www.w3.org/2000/svg";

export interface NetworkLinkMovedEvent {
  item: NetworkLink;
  startPosition: NetworkLinkProps["startPosition"];
  endPosition: NetworkLinkProps["endPosition"];
}

export default class NetworkLink extends Item<NetworkLinkProps> {
  private circleRadius = 8;
  // To control if the line movement is enabled.
  private moveMode: boolean = false;
  // To control if the line is moving.
  private isMoving: boolean = false;

  // Event manager for moved events.
  private readonly lineMovedEventManager = new TypedEvent<
    NetworkLinkMovedEvent
  >();
  // List of references to clean the event listeners.
  private readonly lineMovedEventDisposables: Disposable[] = [];

  // This function will only run the 2nd arg function after the time
  // of the first arg have passed after its last execution.
  private debouncedStartPositionMovementSave = debounce(
    500, // ms.
    (x: Position["x"], y: Position["y"]) => {
      this.isMoving = false;
      const startPosition = { x, y };
      // Emit the movement event.
      this.lineMovedEventManager.emit({
        item: this,
        startPosition,
        endPosition: this.props.endPosition
      });
    }
  );
  // This property will store the function
  // to clean the movement listener.
  private removeStartPositionMovement: Function | null = null;

  /**
   * Start the movement funtionality for the start position.
   * @param element Element to move inside its container.
   */
  private initStartPositionMovementListener(
    element: HTMLElement,
    container: HTMLElement
  ): void {
    this.removeStartPositionMovement = addMovementListener(
      element,
      (x: Position["x"], y: Position["y"]) => {
        // Calculate the center of the circle.
        x += this.circleRadius;
        y += this.circleRadius;

        const startPosition = { x, y };

        this.isMoving = true;
        this.props = {
          ...this.props,
          startPosition
        };

        // Run the end function.
        this.debouncedStartPositionMovementSave(x, y);
      },
      container
    );
  }
  /**
   * Stop the movement fun
   */
  private stopStartPositionMovementListener(): void {
    if (this.removeStartPositionMovement) {
      this.removeStartPositionMovement();
      this.removeStartPositionMovement = null;
    }
  }

  // This function will only run the 2nd arg function after the time
  // of the first arg have passed after its last execution.
  private debouncedEndPositionMovementSave = debounce(
    500, // ms.
    (x: Position["x"], y: Position["y"]) => {
      this.isMoving = false;
      const endPosition = { x, y };
      // Emit the movement event.
      this.lineMovedEventManager.emit({
        item: this,
        endPosition,
        startPosition: this.props.startPosition
      });
    }
  );
  // This property will store the function
  // to clean the movement listener.
  private removeEndPositionMovement: Function | null = null;

  /**
   * End the movement funtionality for the end position.
   * @param element Element to move inside its container.
   */
  private initEndPositionMovementListener(
    element: HTMLElement,
    container: HTMLElement
  ): void {
    this.removeEndPositionMovement = addMovementListener(
      element,
      (x: Position["x"], y: Position["y"]) => {
        // Calculate the center of the circle.
        x += this.circleRadius;
        y += this.circleRadius;

        this.isMoving = true;
        this.props = {
          ...this.props,
          endPosition: { x, y }
        };

        // Run the end function.
        this.debouncedEndPositionMovementSave(x, y);
      },
      container
    );
  }
  /**
   * Stop the movement function.
   */
  private stopEndPositionMovementListener(): void {
    if (this.removeEndPositionMovement) {
      this.removeEndPositionMovement();
      this.removeEndPositionMovement = null;
    }
  }

  /**
   * @override
   */
  public constructor(props: NetworkLinkProps, meta: ItemMeta) {
    /*
     * We need to override the constructor cause we need to obtain the
     * box size and position from the start and finish points of the line.
     */
    super(
      {
        ...props,
        ...NetworkLink.extractBoxSizeAndPosition(
          props.startPosition,
          props.endPosition
        )
      },
      {
        ...meta
      },
      true
    );

    this.moveMode = meta.editMode;
    this.init();
  }

  /**
   * Classic and protected version of the setter of the `props` property.
   * Useful to override it from children classes.
   * @param newProps
   * @override Item.setProps
   */
  public setProps(newProps: NetworkLinkProps) {
    super.setProps({
      ...newProps,
      ...NetworkLink.extractBoxSizeAndPosition(
        newProps.startPosition,
        newProps.endPosition
      )
    });
  }

  /**
   * Classic and protected version of the setter of the `meta` property.
   * Useful to override it from children classes.
   * @param newMetadata
   * @override Item.setMeta
   */
  public setMeta(newMetadata: ItemMeta) {
    this.moveMode = newMetadata.editMode;
    super.setMeta({
      ...newMetadata,
      lineMode: true
    });
  }

  /**
   * @override
   * To create the item's DOM representation.
   * @return Item.
   */
  protected createDomElement(): HTMLElement {
    const element: HTMLDivElement = document.createElement("div");
    element.className = "line";

    const {
      x, // Box x
      y, // Box y
      width, // Box width
      height, // Box height
      lineWidth, // NetworkLink thickness
      startPosition, // NetworkLink start position
      endPosition, // NetworkLink end position
      color // NetworkLink color
    } = this.props;

    const x1 = startPosition.x - x + lineWidth / 2;
    const y1 = startPosition.y - y + lineWidth / 2;
    const x2 = endPosition.x - x + lineWidth / 2;
    const y2 = endPosition.y - y + lineWidth / 2;

    // SVG container.
    const svg = document.createElementNS(svgNS, "svg");
    // Set SVG size.
    svg.setAttribute("width", `${width + lineWidth}`);
    svg.setAttribute("height", `${height + lineWidth}`);
    const line = document.createElementNS(svgNS, "line");
    line.setAttribute("x1", `${x1}`);
    line.setAttribute("y1", `${y1}`);
    line.setAttribute("x2", `${x2}`);
    line.setAttribute("y2", `${y2}`);
    line.setAttribute("stroke", color || "black");
    line.setAttribute("stroke-width", `${lineWidth}`);

    svg.append(line);
    element.append(svg);

    return element;
  }

  protected updateDomElement(element: HTMLElement): void {
    const {
      x, // Box x
      y, // Box y
      width, // Box width
      height, // Box height
      lineWidth, // NetworkLink thickness
      startPosition, // NetworkLink start position
      endPosition, // NetworkLink end position
      color // NetworkLink color
    } = this.props;

    const x1 = startPosition.x - x + lineWidth / 2;
    const y1 = startPosition.y - y + lineWidth / 2;
    const x2 = endPosition.x - x + lineWidth / 2;
    const y2 = endPosition.y - y + lineWidth / 2;

    const svgs = element.getElementsByTagName("svg");

    if (svgs.length > 0) {
      const svg = svgs.item(0);

      if (svg != null) {
        // Set SVG size.
        svg.setAttribute("width", `${width + lineWidth}`);
        svg.setAttribute("height", `${height + lineWidth}`);

        const lines = svg.getElementsByTagNameNS(svgNS, "line");

        if (lines.length > 0) {
          const line = lines.item(0);

          if (line != null) {
            line.setAttribute("x1", `${x1}`);
            line.setAttribute("y1", `${y1}`);
            line.setAttribute("x2", `${x2}`);
            line.setAttribute("y2", `${y2}`);
            line.setAttribute("stroke", color || "black");
            line.setAttribute("stroke-width", `${lineWidth}`);
          }
        }
      }
    }

    if (this.moveMode) {
      const startIsLeft = startPosition.x - endPosition.x <= 0;
      const startIsTop = startPosition.y - endPosition.y <= 0;

      let startCircle: HTMLElement = document.createElement("div");
      let endCircle: HTMLElement = document.createElement("div");

      if (this.isMoving) {
        const circlesStart = element.getElementsByClassName(
          "visual-console-item-line-circle-start"
        );
        if (circlesStart.length > 0) {
          const circle = circlesStart.item(0) as HTMLElement;
          if (circle) startCircle = circle;
        }
        const circlesEnd = element.getElementsByClassName(
          "visual-console-item-line-circle-end"
        );
        if (circlesEnd.length > 0) {
          const circle = circlesEnd.item(0) as HTMLElement;
          if (circle) endCircle = circle;
        }
      }

      startCircle.classList.add(
        "visual-console-item-line-circle",
        "visual-console-item-line-circle-start"
      );
      startCircle.style.width = `${this.circleRadius * 2}px`;
      startCircle.style.height = `${this.circleRadius * 2}px`;
      startCircle.style.borderRadius = "50%";
      startCircle.style.backgroundColor = `${color}`;
      startCircle.style.position = "absolute";
      startCircle.style.left = startIsLeft
        ? `-${this.circleRadius}px`
        : `${width + lineWidth - this.circleRadius}px`;
      startCircle.style.top = startIsTop
        ? `-${this.circleRadius}px`
        : `${height + lineWidth - this.circleRadius}px`;

      endCircle.classList.add(
        "visual-console-item-line-circle",
        "visual-console-item-line-circle-end"
      );
      endCircle.style.width = `${this.circleRadius * 2}px`;
      endCircle.style.height = `${this.circleRadius * 2}px`;
      endCircle.style.borderRadius = "50%";
      endCircle.style.backgroundColor = `${color}`;
      endCircle.style.position = "absolute";
      endCircle.style.left = startIsLeft
        ? `${width + lineWidth - 8}px`
        : `-${this.circleRadius}px`;
      endCircle.style.top = startIsTop
        ? `${height + lineWidth - this.circleRadius}px`
        : `-${this.circleRadius}px`;

      if (element.parentElement !== null) {
        const circles = element.parentElement.getElementsByClassName(
          "visual-console-item-line-circle"
        );
        while (circles.length > 0) {
          const circle = circles.item(0);
          if (circle) circle.remove();
        }

        element.parentElement.appendChild(startCircle);
        element.parentElement.appendChild(endCircle);
      }

      // Init the movement listeners.
      this.initStartPositionMovementListener(startCircle, this.elementRef
        .parentElement as HTMLElement);
      this.initEndPositionMovementListener(endCircle, this.elementRef
        .parentElement as HTMLElement);
    } else if (!this.moveMode) {
      this.stopStartPositionMovementListener();
      // Remove circles.
      if (element.parentElement !== null) {
        const circles = element.parentElement.getElementsByClassName(
          "visual-console-item-line-circle"
        );

        while (circles.length > 0) {
          const circle = circles.item(0);
          if (circle) circle.remove();
        }
      }
    } else {
      this.stopStartPositionMovementListener();
    }
  }

  /**
   * Extract the size and position of the box from
   * the start and the finish of the line.
   * @param props Item properties.
   */
  public static extractBoxSizeAndPosition(
    startPosition: Position,
    endPosition: Position
  ): Size & Position {
    return {
      width: Math.abs(startPosition.x - endPosition.x),
      height: Math.abs(startPosition.y - endPosition.y),
      x: Math.min(startPosition.x, endPosition.x),
      y: Math.min(startPosition.y, endPosition.y)
    };
  }

  /**
   * Update the position into the properties and move the DOM container.
   * @param x Horizontal axis position.
   * @param y Vertical axis position.
   * @override item function
   */
  public move(x: number, y: number): void {
    super.moveElement(x, y);
    const startIsLeft =
      this.props.startPosition.x - this.props.endPosition.x <= 0;
    const startIsTop =
      this.props.startPosition.y - this.props.endPosition.y <= 0;

    const start = {
      x: startIsLeft ? x : this.props.width + x,
      y: startIsTop ? y : this.props.height + y
    };

    const end = {
      x: startIsLeft ? this.props.width + x : x,
      y: startIsTop ? this.props.height + y : y
    };

    this.props = {
      ...this.props,
      startPosition: start,
      endPosition: end
    };
  }

  /**
   * To remove the event listeners and the elements from the DOM.
   * @override Item.remove
   */
  public remove(): void {
    // Clear the item's event listeners.
    this.stopStartPositionMovementListener();
    // Call the parent's .remove()
    super.remove();
  }

  /**
   * To add an event handler to the movement of visual console elements.
   * @param listener Function which is going to be executed when a linked console is moved.
   *
   * @override Item.onMoved
   */
  public onNetworkLinkMovementFinished(
    listener: Listener<NetworkLinkMovedEvent>
  ): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.lineMovedEventManager.on(listener);
    this.lineMovedEventDisposables.push(disposable);

    return disposable;
  }
}
