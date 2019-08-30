import TypedEvent, { Disposable, Listener } from "./TypedEvent";

interface Cancellable {
  cancel(): void;
}

type AsyncTaskStatus = "waiting" | "started" | "cancelled" | "finished";
type AsyncTaskInitiator = (done: () => void) => Cancellable;

/**
 * Defines an async task which can be started and cancelled.
 * It's possible to observe the status changes of the task.
 */
class AsyncTask {
  private readonly taskInitiator: AsyncTaskInitiator;
  private cancellable: Cancellable = { cancel: () => {} };
  private _status: AsyncTaskStatus = "waiting";

  // Event manager for status change events.
  private readonly statusChangeEventManager = new TypedEvent<AsyncTaskStatus>();
  // List of references to clean the event listeners.
  private readonly disposables: Disposable[] = [];

  public constructor(taskInitiator: AsyncTaskInitiator) {
    this.taskInitiator = taskInitiator;
  }

  /**
   * Public setter of the `status` property.
   * @param status.
   */
  public set status(status: AsyncTaskStatus) {
    this._status = status;
    this.statusChangeEventManager.emit(status);
  }

  /**
   * Public accessor of the `status` property.
   * @return status.
   */
  public get status() {
    return this._status;
  }

  /**
   * Start the async task.
   */
  public init(): void {
    this.cancellable = this.taskInitiator(() => {
      this.status = "finished";
    });
    this.status = "started";
  }

  /**
   * Cancel the async task.
   */
  public cancel(): void {
    this.cancellable.cancel();
    this.status = "cancelled";
  }

  /**
   * Add an event handler to the status change.
   * @param listener Function which is going to be executed when the status changes.
   */
  public onStatusChange(listener: Listener<AsyncTaskStatus>): Disposable {
    /*
     * The '.on' function returns a function which will clean the event
     * listener when executed. We store all the 'dispose' functions to
     * call them when the item should be cleared.
     */
    const disposable = this.statusChangeEventManager.on(listener);
    this.disposables.push(disposable);

    return disposable;
  }
}

/**
 * Wrap an async task into another which will execute that task indefinitely
 * every time the tash finnish and the chosen period ends.
 * Will last until cancellation.
 *
 * @param task Async task to execute.
 * @param period Time in milliseconds to wait until the next async esecution.
 *
 * @return A new async task.
 */
function asyncPeriodic(task: AsyncTask, period: number): AsyncTask {
  return new AsyncTask(() => {
    let ref: number | null = null;

    task.onStatusChange(status => {
      if (status === "finished") {
        ref = window.setTimeout(() => {
          task.init();
        }, period);
      }
    });

    task.init();

    return {
      cancel: () => {
        if (ref) clearTimeout(ref);
        task.cancel();
      }
    };
  });
}

/**
 * Manages a list of async tasks.
 */
export default class AsyncTaskManager {
  private tasks: { [identifier: string]: AsyncTask } = {};

  /**
   * Adds an async task to the manager.
   *
   * @param identifier Unique identifier.
   * @param taskInitiator Function to initialize the async task.
   * Should return a structure to cancel the task.
   * @param period Optional period to repeat the task indefinitely.
   */
  public add(
    identifier: string,
    taskInitiator: AsyncTaskInitiator,
    period: number = 0
  ): AsyncTask {
    if (this.tasks[identifier] && this.tasks[identifier].status === "started") {
      this.tasks[identifier].cancel();
    }

    const asyncTask =
      period > 0
        ? asyncPeriodic(new AsyncTask(taskInitiator), period)
        : new AsyncTask(taskInitiator);

    this.tasks[identifier] = asyncTask;

    return this.tasks[identifier];
  }

  /**
   * Starts an async task.
   *
   * @param identifier Unique identifier.
   */
  public init(identifier: string) {
    if (
      this.tasks[identifier] &&
      (this.tasks[identifier].status === "waiting" ||
        this.tasks[identifier].status === "cancelled" ||
        this.tasks[identifier].status === "finished")
    ) {
      this.tasks[identifier].init();
    }
  }

  /**
   * Cancel a running async task.
   *
   * @param identifier Unique identifier.
   */
  public cancel(identifier: string) {
    if (this.tasks[identifier] && this.tasks[identifier].status === "started") {
      this.tasks[identifier].cancel();
    }
  }
}
