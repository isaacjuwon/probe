import type { RequestTab } from '$lib/domain/request-tab';
import type { DeleteTabOptions, SaveTabOptions, TabRepository } from '$lib/persistence/tab.repository';

export class MemoryTabRepository implements TabRepository {
  private tabs: Map<string, RequestTab> = new Map();

  async all(): Promise<RequestTab[]> {
    return Array.from(this.tabs.values());
  }

  async save(options: SaveTabOptions): Promise<void> {
    this.tabs.set(options.tab.id, structuredClone(options.tab));
  }

  async delete(options: DeleteTabOptions): Promise<void> {
    this.tabs.delete(options.id);
  }

  async clear(): Promise<void> {
    this.tabs.clear();
  }
}
