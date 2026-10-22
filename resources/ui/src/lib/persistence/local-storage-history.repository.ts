import type { HistoryItem } from '$lib/domain/history';
import type { HistoryRepository } from '$lib/persistence/history.repository';

const STORAGE_KEY = 'probe_request_history';
const MAX_HISTORY = 100;

export class LocalStorageHistoryRepository implements HistoryRepository {
  async all(): Promise<HistoryItem[]> {
    if (typeof window === 'undefined' || !window.localStorage) {
      return [];
    }
    try {
      const data = localStorage.getItem(STORAGE_KEY);
      return data ? (JSON.parse(data) as HistoryItem[]) : [];
    } catch {
      return [];
    }
  }

  async add(item: HistoryItem): Promise<void> {
    if (typeof window === 'undefined' || !window.localStorage) {
      return;
    }
    try {
      const existing = await this.all();
      const updated = [item, ...existing.filter((h) => h.id !== item.id)].slice(0, MAX_HISTORY);
      localStorage.setItem(STORAGE_KEY, JSON.stringify(updated));
    } catch {
      // Storage quota exceeded or disabled
    }
  }

  async clear(): Promise<void> {
    if (typeof window === 'undefined' || !window.localStorage) {
      return;
    }
    try {
      localStorage.removeItem(STORAGE_KEY);
    } catch {
      // Ignore
    }
  }
}
