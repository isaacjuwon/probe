import { describe, it, expect, beforeEach } from 'vitest';
import { TabsState } from './tabs.state.svelte';
import { MemoryTabRepository } from '$lib/persistence/memory-tab.repository';

describe('TabsState', () => {
  let repository: MemoryTabRepository;
  let state: TabsState;

  beforeEach(() => {
    repository = new MemoryTabRepository();
    state = new TabsState(repository);
  });

  it('creates default tab and sets it active on load if empty', async () => {
    await state.load();
    expect(state.tabs).toHaveLength(1);
    expect(state.activeId).toBe(state.tabs[0].id);
    expect(state.active).not.toBeNull();
    expect(state.active?.title).toBe('New Request');
  });

  it('opens a new tab and updates active tab', () => {
    const tab = state.createDefaultTab();
    expect(state.tabs).toHaveLength(1);
    expect(state.active?.id).toBe(tab.id);

    state.open({
      tab: {
        id: 'tab-2',
        title: 'Users',
        method: 'GET',
        url: 'http://localhost/api/users',
        headers: {},
        query: {},
        body: '',
        authType: 'none',
        authPayload: {},
      },
    });

    expect(state.tabs).toHaveLength(2);
    expect(state.activeId).toBe('tab-2');
    expect(state.active?.title).toBe('Users');
  });

  it('switches active tab using setActive', () => {
    state.open({
      tab: {
        id: 'tab-1',
        title: 'First',
        method: 'GET',
        url: '/first',
        headers: {},
        query: {},
        body: '',
        authType: 'none',
        authPayload: {},
      },
    });
    state.open({
      tab: {
        id: 'tab-2',
        title: 'Second',
        method: 'POST',
        url: '/second',
        headers: {},
        query: {},
        body: '',
        authType: 'none',
        authPayload: {},
      },
    });

    expect(state.active?.id).toBe('tab-2');
    state.setActive({ id: 'tab-1' });
    expect(state.active?.id).toBe('tab-1');
  });

  it('closes a tab and selects adjacent tab', () => {
    state.open({
      tab: {
        id: 't1',
        title: 'T1',
        method: 'GET',
        url: '/1',
        headers: {},
        query: {},
        body: '',
        authType: 'none',
        authPayload: {},
      },
    });
    state.open({
      tab: {
        id: 't2',
        title: 'T2',
        method: 'GET',
        url: '/2',
        headers: {},
        query: {},
        body: '',
        authType: 'none',
        authPayload: {},
      },
    });

    expect(state.active?.id).toBe('t2');
    state.close({ id: 't2' });
    expect(state.tabs).toHaveLength(1);
    expect(state.active?.id).toBe('t1');
  });
});
