import { beforeEach, describe, expect, it, vi } from 'vitest';
import { EndpointsState } from './endpoints.state.svelte';
import type { EndpointClient } from '$lib/api/endpoint.client';
import type { Endpoint } from '$lib/domain/endpoint';

const mockEndpoints: Endpoint[] = [
  {
    method: 'GET',
    uri: 'api/users',
    name: 'List Users',
    action: 'App\\Http\\Controllers\\UserController@index',
    group: 'Users',
    version: 'v1',
    middleware: ['api'],
    headers: {},
    parameters: {},
    fields: {},
    auth: 'none',
  },
  {
    method: 'POST',
    uri: 'api/users',
    name: 'Create User',
    action: 'App\\Http\\Controllers\\UserController@store',
    group: 'Users',
    version: 'v1',
    middleware: ['api'],
    headers: {},
    parameters: {},
    fields: {},
    auth: 'sanctum_act_as',
  },
  {
    method: 'GET',
    uri: 'api/orders',
    name: 'List Orders',
    action: 'App\\Http\\Controllers\\OrderController@index',
    group: 'Orders',
    version: 'v2',
    middleware: ['api'],
    headers: {},
    parameters: {},
    fields: {},
    auth: 'api_key',
  },
];

const createMockClient = (endpoints: Endpoint[] = mockEndpoints): EndpointClient => {
  return {
    list: async () => endpoints,
  } as unknown as EndpointClient;
};

const storageMap = new Map<string, string>();
const mockLocalStorage = {
  getItem: (key: string) => storageMap.get(key) ?? null,
  setItem: (key: string, val: string) => {
    storageMap.set(key, String(val));
  },
  removeItem: (key: string) => {
    storageMap.delete(key);
  },
  clear: () => {
    storageMap.clear();
  },
};

describe('EndpointsState', () => {
  beforeEach(() => {
    vi.stubGlobal('localStorage', mockLocalStorage);
    mockLocalStorage.clear();
  });

  it('collapses all groups by default on initialization and after loading', async () => {
    const client = createMockClient();
    const state = new EndpointsState(client);

    await state.load();

    expect(state.groups).toEqual(['Orders', 'Users']);
    expect(state.expandedGroups).toEqual([]);
    expect(state.isAllCollapsed).toBe(true);

    expect(state.isGroupCollapsed('Users')).toBe(true);
    expect(state.isGroupCollapsed('Orders')).toBe(true);
    expect(state.isGroupExpanded('Users')).toBe(false);
    expect(state.isGroupExpanded('Orders')).toBe(false);
  });

  it('toggles a single group between expanded and collapsed', async () => {
    const client = createMockClient();
    const state = new EndpointsState(client);
    await state.load();

    state.toggleGroup('Users');
    expect(state.expandedGroups).toEqual(['Users']);
    expect(state.isGroupCollapsed('Users')).toBe(false);
    expect(state.isGroupExpanded('Users')).toBe(true);
    expect(state.isGroupCollapsed('Orders')).toBe(true);
    expect(state.isAllCollapsed).toBe(false);

    state.toggleGroup('Users');
    expect(state.expandedGroups).toEqual([]);
    expect(state.isGroupCollapsed('Users')).toBe(true);
    expect(state.isAllCollapsed).toBe(true);
  });

  it('supports expandAll and collapseAll', async () => {
    const client = createMockClient();
    const state = new EndpointsState(client);
    await state.load();

    state.expandAll();
    expect(state.expandedGroups).toEqual(['Orders', 'Users']);
    expect(state.isGroupCollapsed('Users')).toBe(false);
    expect(state.isGroupCollapsed('Orders')).toBe(false);
    expect(state.isAllCollapsed).toBe(false);

    state.collapseAll();
    expect(state.expandedGroups).toEqual([]);
    expect(state.isGroupCollapsed('Users')).toBe(true);
    expect(state.isGroupCollapsed('Orders')).toBe(true);
    expect(state.isAllCollapsed).toBe(true);
  });

  it('temporarily expands all groups when searching and restores state when search is cleared', async () => {
    const client = createMockClient();
    const state = new EndpointsState(client);
    await state.load();

    expect(state.isGroupCollapsed('Users')).toBe(true);

    state.setSearchQuery('users');
    expect(state.isGroupCollapsed('Users')).toBe(false);
    expect(state.isGroupExpanded('Users')).toBe(true);

    state.setSearchQuery('');
    expect(state.isGroupCollapsed('Users')).toBe(true);
  });

  it('persists expanded groups to localStorage', async () => {
    const client = createMockClient();
    const state = new EndpointsState(client);
    await state.load();

    state.toggleGroup('Orders');
    expect(mockLocalStorage.getItem('probe_expanded_groups')).toBe(JSON.stringify(['Orders']));

    const secondState = new EndpointsState(client);
    await secondState.load();
    expect(secondState.isGroupCollapsed('Orders')).toBe(false);
    expect(secondState.isGroupCollapsed('Users')).toBe(true);
  });

  it('resets expanded groups to collapsed by default when switching groupBy mode', async () => {
    const client = createMockClient();
    const state = new EndpointsState(client);
    await state.load();

    state.expandAll();
    expect(state.isAllCollapsed).toBe(false);

    state.setGroupBy('controller');
    expect(state.groupBy).toBe('controller');
    expect(state.expandedGroups).toEqual([]);
    expect(state.isAllCollapsed).toBe(true);
  });
});
