import { describe, it, expect, beforeEach } from 'vitest';
import { MemoryTabRepository } from './memory-tab.repository';
import type { RequestTab } from '$lib/domain/request-tab';

describe('MemoryTabRepository', () => {
  let repository: MemoryTabRepository;

  beforeEach(() => {
    repository = new MemoryTabRepository();
  });

  it('saves and loads tabs in memory', async () => {
    const tab1: RequestTab = {
      id: 'tab-1',
      title: 'Users List',
      method: 'GET',
      url: '/api/users',
      headers: {},
      query: {},
      body: '',
      authType: 'none',
      authPayload: {},
      isDirty: false,
    };

    const tab2: RequestTab = {
      id: 'tab-2',
      title: 'Create User',
      method: 'POST',
      url: '/api/users',
      headers: { 'Content-Type': 'application/json' },
      query: {},
      body: '{"name":"Alice"}',
      authType: 'bearer',
      authPayload: { token: 'secret' },
      isDirty: true,
    };

    await repository.save({ tab: tab1 });
    await repository.save({ tab: tab2 });

    const loaded = await repository.all();
    expect(loaded).toHaveLength(2);
    expect(loaded[0].id).toBe('tab-1');
    expect(loaded[1].id).toBe('tab-2');
  });

  it('deletes specific tab by id', async () => {
    const tab: RequestTab = {
      id: 'tab-1',
      title: 'Test',
      method: 'GET',
      url: '/test',
      headers: {},
      query: {},
      body: '',
      authType: 'none',
      authPayload: {},
      isDirty: false,
    };

    await repository.save({ tab });
    await repository.delete({ id: 'tab-1' });

    const loaded = await repository.all();
    expect(loaded).toEqual([]);
  });

  it('clears all tabs from memory', async () => {
    const tab: RequestTab = {
      id: 'tab-1',
      title: 'Test',
      method: 'GET',
      url: '/test',
      headers: {},
      query: {},
      body: '',
      authType: 'none',
      authPayload: {},
      isDirty: false,
    };

    await repository.save({ tab });
    await repository.clear();

    const loaded = await repository.all();
    expect(loaded).toEqual([]);
  });
});
