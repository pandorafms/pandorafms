export interface Listener<T> {
  (event: T): void;
}

export interface Disposable {
  dispose: () => void;
}

/** passes through events as they happen. You will not get events from before you start listening */
export default class TypedEvent<T> {
  private listeners: Listener<T>[] = [];
  private listenersOncer: Listener<T>[] = [];

  public on = (listener: Listener<T>): Disposable => {
    this.listeners.push(listener);
    return {
      dispose: () => this.off(listener)
    };
  };

  public once = (listener: Listener<T>): void => {
    this.listenersOncer.push(listener);
  };

  public off = (listener: Listener<T>): void => {
    const callbackIndex = this.listeners.indexOf(listener);
    if (callbackIndex > -1) this.listeners.splice(callbackIndex, 1);
  };

  public emit = (event: T): void => {
    /** Update any general listeners */
    this.listeners.forEach(listener => listener(event));

    /** Clear the `once` queue */
    this.listenersOncer.forEach(listener => listener(event));
    this.listenersOncer = [];
  };

  public pipe = (te: TypedEvent<T>): Disposable => this.on(e => te.emit(e));
}
