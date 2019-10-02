import TypedEvent, { Listener, Disposable } from "./lib/TypedEvent";
import { AnyObject, UnknownObject } from "./lib/types";
import { t } from "./lib";

interface InputGroupDataRequestedEvent {
  identifier: string;
  params: UnknownObject;
  done: (error: Error | null, data?: unknown) => void;
}

// TODO: Document
export abstract class InputGroup<Data extends {} = {}> {
  private _name: string = "";
  private _element?: HTMLElement;
  public readonly initialData: Data;
  protected currentData: Partial<Data> = {};
  // Event manager for data requests.
  private readonly dataRequestedEventManager = new TypedEvent<
    InputGroupDataRequestedEvent
  >();

  public constructor(name: string, initialData: Data) {
    this.name = name;
    this.initialData = initialData;
  }

  public set name(name: string) {
    if (name.length === 0) throw new RangeError("empty name");
    this._name = name;
  }

  public get name(): string {
    return this._name;
  }

  public get data(): Partial<Data> {
    return { ...this.currentData };
  }

  public get element(): HTMLElement {
    if (this._element == null) {
      const element = document.createElement("div");
      element.className = `input-group input-group-${this.name}`;

      const content = this.createContent();

      if (content instanceof Array) {
        content.forEach(element.appendChild);
      } else {
        element.appendChild(content);
      }

      this._element = element;
    }

    return this._element;
  }

  public reset(): void {
    this.currentData = {};
  }

  protected updateData(data: Partial<Data>): void {
    this.currentData = {
      ...this.currentData,
      ...data
    };
    // TODO: Update item.
  }

  protected requestData(
    identifier: string,
    params: UnknownObject,
    done: (error: Error | null, data?: unknown) => void
  ): void {
    this.dataRequestedEventManager.emit({ identifier, params, done });
  }

  public onDataRequested(
    listener: Listener<InputGroupDataRequestedEvent>
  ): Disposable {
    return this.dataRequestedEventManager.on(listener);
  }

  protected abstract createContent(): HTMLElement | HTMLElement[];

  // public abstract get isValid(): boolean;
}

export interface SubmitFormEvent {
  nativeEvent: Event;
  data: AnyObject;
}

// TODO: Document
export class FormContainer {
  public readonly title: string;
  private inputGroupsByName: { [name: string]: InputGroup } = {};
  private enabledInputGroupNames: string[] = [];
  // Event manager for submit events.
  private readonly submitEventManager = new TypedEvent<SubmitFormEvent>();
  // Event manager for item data requests.
  private readonly itemDataRequestedEventManager = new TypedEvent<
    InputGroupDataRequestedEvent
  >();
  private handleItemDataRequested = this.itemDataRequestedEventManager.emit;

  public constructor(
    title: string,
    inputGroups: InputGroup[] = [],
    enabledInputGroups: string[] = []
  ) {
    this.title = title;

    if (inputGroups.length > 0) {
      this.inputGroupsByName = inputGroups.reduce((prevVal, inputGroup) => {
        // Add event handlers.
        inputGroup.onDataRequested(this.handleItemDataRequested);
        prevVal[inputGroup.name] = inputGroup;
        return prevVal;
      }, this.inputGroupsByName);
    }

    if (enabledInputGroups.length > 0) {
      this.enabledInputGroupNames = [
        ...this.enabledInputGroupNames,
        ...enabledInputGroups.filter(
          name => this.inputGroupsByName[name] != null
        )
      ];
    }
  }

  public getInputGroup(inputGroupName: string): InputGroup | null {
    return this.inputGroupsByName[inputGroupName] || null;
  }

  public addInputGroup(
    inputGroup: InputGroup,
    index: number | null = null
  ): FormContainer {
    // Add event handlers.
    inputGroup.onDataRequested(this.handleItemDataRequested);
    this.inputGroupsByName[inputGroup.name] = inputGroup;

    // Remove the current stored name if exist.
    this.enabledInputGroupNames = this.enabledInputGroupNames.filter(
      name => name !== inputGroup.name
    );

    if (index !== null) {
      if (index <= 0) {
        this.enabledInputGroupNames = [
          inputGroup.name,
          ...this.enabledInputGroupNames
        ];
      } else if (index >= this.enabledInputGroupNames.length) {
        this.enabledInputGroupNames = [
          ...this.enabledInputGroupNames,
          inputGroup.name
        ];
      } else {
        this.enabledInputGroupNames = [
          // part of the array before the specified index
          ...this.enabledInputGroupNames.slice(0, index),
          // inserted item
          inputGroup.name,
          // part of the array after the specified index
          ...this.enabledInputGroupNames.slice(index)
        ];
      }
    } else {
      this.enabledInputGroupNames = [
        ...this.enabledInputGroupNames,
        inputGroup.name
      ];
    }

    return this;
  }

  public removeInputGroup(inputGroupName: string): FormContainer {
    delete this.inputGroupsByName[inputGroupName];
    // Remove the current stored name.
    this.enabledInputGroupNames = this.enabledInputGroupNames.filter(
      name => name !== inputGroupName
    );

    return this;
  }

  public getFormElement(
    type: "creation" | "update" = "update"
  ): HTMLFormElement {
    const form = document.createElement("form");
    form.id = "visual-console-item-edition";
    form.className = "visual-console-item-edition";
    form.addEventListener("submit", e => {
      e.preventDefault();
      this.submitEventManager.emit({
        nativeEvent: e,
        data: this.enabledInputGroupNames.reduce((data, name) => {
          if (this.inputGroupsByName[name]) {
            data = {
              ...data,
              ...this.inputGroupsByName[name].data
            };
          }
          return data;
        }, {})
      });
    });

    const formContent = document.createElement("div");
    formContent.className = "input-groups";

    this.enabledInputGroupNames.forEach(name => {
      if (this.inputGroupsByName[name]) {
        formContent.appendChild(this.inputGroupsByName[name].element);
      }
    });

    form.appendChild(formContent);

    return form;
  }

  public reset(): void {
    this.enabledInputGroupNames.forEach(name => {
      if (this.inputGroupsByName[name]) {
        this.inputGroupsByName[name].reset();
      }
    });
  }

  // public get isValid(): boolean {
  //   for (let i = 0; i < this.enabledInputGroupNames.length; i++) {
  //     const inputGroup = this.inputGroupsByName[this.enabledInputGroupNames[i]];
  //     if (inputGroup && !inputGroup.isValid) return false;
  //   }

  //   return true;
  // }

  public onSubmit(listener: Listener<SubmitFormEvent>): Disposable {
    return this.submitEventManager.on(listener);
  }

  public onInputGroupDataRequested(
    listener: Listener<InputGroupDataRequestedEvent>
  ): Disposable {
    return this.itemDataRequestedEventManager.on(listener);
  }
}
