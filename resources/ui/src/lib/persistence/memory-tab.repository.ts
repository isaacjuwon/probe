import type { RequestTab } from '$lib/domain/request-tab';
import type { DeleteTabOptions, SaveTabOptions, TabRepository } from '$lib/persistence/tab.repository';

export class MemoryTabRepository implements TabRepository {
  private tabs: Map<string, RequestTab> = new Map();

  async all(): Promise<RequestTab[]> {
    return Array.from(this.tabs.values());
  }

  async save(options: SaveTabOptions): Promise<void> {
    let toStore: RequestTab;
    try {
      toStore = JSON.parse(JSON.stringify(options.tab)) as RequestTab;
    } catch {
      try {
        toStore = structuredClone(options.tab);
      } catch {
        toStore = options.tab;
      }
    }
    this.tabs.set(options.tab.id, toStore);
  }

  async delete(options: DeleteTabOptions): Promise<void> {
    this.tabs.delete(options.id);
  }

  async clear(): Promise<void> {
    this.tabs.clear();
  }
}
