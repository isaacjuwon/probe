import type { HistoryItem } from '$lib/domain/history';
import type { HistoryRepository } from '$lib/persistence/history.repository';

export class HistoryState {
  items = $state<HistoryItem[]>([]);
  searchQuery = $state<string>('');

  get filtered(): HistoryItem[] {
    if (!this.searchQuery.trim()) {
      return this.items;
    }
    const q = this.searchQuery.toLowerCase();
    return this.items.filter(
      (item) => item.url.toLowerCase().includes(q) || item.method.toLowerCase().includes(q)
    );
  }

  constructor(private readonly repository: HistoryRepository) {}

  async load(): Promise<void> {
    this.items = await this.repository.all();
  }

  async record(item: HistoryItem): Promise<void> {
    this.items = [item, ...this.items.filter((i) => i.id !== item.id)].slice(0, 100);
    await this.repository.add(item);
  }

  async clear(): Promise<void> {
    this.items = [];
    await this.repository.clear();
  }
}
