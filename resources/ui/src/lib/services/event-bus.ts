export type EventCallback<T = unknown> = (data: T) => void;

/**
 * Observer: Tiny event bus for cross-feature notifications and signals.
 */
export class EventBus {
  private listeners: Map<string, Set<EventCallback<unknown>>> = new Map();

  on<T>(options: { readonly event: string; readonly callback: EventCallback<T> }): () => void {
    const { event, callback } = options;
    if (!this.listeners.has(event)) {
      this.listeners.set(event, new Set());
    }

    const set = this.listeners.get(event)!;
    set.add(callback as EventCallback<unknown>);

    return () => {
      set.delete(callback as EventCallback<unknown>);
    };
  }

  emit<T>(options: { readonly event: string; readonly data?: T }): void {
    const { event, data } = options;
    const set = this.listeners.get(event);
    if (set) {
      for (const listener of set) {
        try {
          listener(data);
        } catch (err) {
          console.error(`Error in event listener for [${event}]`, err);
        }
      }
    }
  }
}
