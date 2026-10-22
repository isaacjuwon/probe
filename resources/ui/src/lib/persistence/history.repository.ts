import type { HistoryItem } from '$lib/domain/history';

export interface HistoryRepository {
  all(): Promise<HistoryItem[]>;
  add(item: HistoryItem): Promise<void>;
  clear(): Promise<void>;
}
