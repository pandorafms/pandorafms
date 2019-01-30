// interface VisualConsoleElement<VCItemProps> extends EventEmitter {
//   private itemProps: VCItemProps extends VCGenericItemProps:
//   private containerRef: HTMLElement;
//   private itemBoxRef: HTMLElement;
//   protected elementRef: HTMLElement;

//   new (container: HTMLElement, props: VCItemProps): VisualConsoleElement;

//   get props (): VCItemProps;
//   set props (newProps: VCItemProps): void;

//   protected shouldBeUpdated (newProps: VCItemProps): boolean;
//   abstract createDomElement (): HTMLElement;
//   render (lastProps: VCItemProps): void;
//   remove (): void;
//   move (x: number, y: number): void;
//   resize (width: number, height: number): void;
// }

class EventEmitter {}
type VisualConsoleItemProps = {};

abstract class VisualConsoleItem extends EventEmitter {
  private itemProps: VisualConsoleItemProps;
  private containerRef: HTMLElement;
  private itemBoxRef: HTMLElement;
  protected elementRef: HTMLElement;

  constructor(container: HTMLElement, props: VisualConsoleItemProps) {
    super();
    this.containerRef = container;
    this.itemProps = props;
  }

  get props(): VisualConsoleItemProps {
    return this.itemProps;
  }
}

export default VisualConsoleItem;
