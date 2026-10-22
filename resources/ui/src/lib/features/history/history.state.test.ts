import { describe, expect, it } from 'vitest';
import { HistoryState } from './history.state.svelte';
import type { HistoryRepository } from '$lib/persistence/history.repository';
import type { HistoryItem } from '$lib/domain/history';

class MemoryHistoryRepository implements HistoryRepository {
  private items: HistoryItem[] = [];

  async all(): Promise<HistoryItem[]> {
    return [...this.items];
  }

  async add(item: HistoryItem): Promise<void> {
    this.items = [item, ...this.items.filter((i) => i.id !== item.id)];
  }

  async clear(): Promise<void> {
    this.items = [];
  }
}

describe('HistoryState', () => {
  it('records and filters history items', async () => {
    const repo = new MemoryHistoryRepository();
    const state = new HistoryState(repo);

    await state.load();
    expect(state.items.length).toBe(0);

    await state.record({
      id: 'h-1',
      timestamp: Date.now(),
      method: 'GET',
      url: 'http://localhost/api/users',
      status: 200,
      durationMs: 15,
      headers: {},
      query: {},
      body: '',
    });

    await state.record({
      id: 'h-2',
      timestamp: Date.now(),
      method: 'POST',
      url: 'http://localhost/api/posts',
      status: 201,
      durationMs: 25,
      headers: {},
      query: {},
      body: '{}',
    });

    expect(state.items.length).toBe(2);
    expect(state.filtered.length).toBe(2);

    state.searchQuery = 'users';
    expect(state.filtered.length).toBe(1);
    expect(state.filtered[0].url).toContain('users');

    await state.clear();
    expect(state.items.length).toBe(0);
  });
});
