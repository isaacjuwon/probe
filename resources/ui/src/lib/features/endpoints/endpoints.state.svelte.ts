import type { Endpoint } from '$lib/domain/endpoint';
import type { EndpointClient } from '$lib/api/endpoint.client';
import { extractControllerName, sortEndpointsCanonical } from './endpoint-sorter';

export type GroupByMode = 'resource' | 'controller';

export class EndpointsState {
  endpoints = $state.raw<Endpoint[]>([]);
  searchQuery = $state<string>('');
  selectedGroup = $state<string | null>(null);
  apiScope = $state<string>('all');
  authFilter = $state<string>('all');
  groupBy = $state<GroupByMode>('resource');
  collapsedGroups = $state<string[]>([]);
  loading = $state<boolean>(false);
  error = $state<string | null>(null);

  get filtered(): Endpoint[] {
    return this.endpoints.filter((e) => {
      const uri = e.uri.toLowerCase();
      const matchesScope =
        this.apiScope === 'all' ||
        (this.apiScope === 'api' && (uri.startsWith('api') || uri.startsWith('/api'))) ||
        (this.apiScope === 'web' && !uri.startsWith('api') && !uri.startsWith('/api')) ||
        uri.startsWith(this.apiScope.toLowerCase());

      const matchesSearch =
        this.searchQuery.trim() === '' ||
        e.uri.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
        (e.name && e.name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
        e.method.toLowerCase().includes(this.searchQuery.toLowerCase());

      const matchesGroup =
        this.selectedGroup === null || (e.group ?? 'General') === this.selectedGroup;

      const matchesAuth =
        this.authFilter === 'all' ||
        (this.authFilter === 'public' && (e.auth === 'none' || !e.auth)) ||
        (this.authFilter === 'sanctum' && e.auth === 'sanctum_act_as') ||
        (this.authFilter === 'api_key' && e.auth === 'api_key') ||
        (this.authFilter === 'basic' && e.auth === 'basic') ||
        e.auth === this.authFilter;

      return matchesScope && matchesSearch && matchesGroup && matchesAuth;
    });
  }

  get groups(): string[] {
    const set = new Set<string>();
    for (const ep of this.filtered) {
      const g = this.groupBy === 'controller'
        ? extractControllerName(ep.action)
        : (ep.group ?? 'General');
      set.add(g);
    }
    return Array.from(set).sort();
  }

  getEndpointsForGroup(group: string): Endpoint[] {
    const matching = this.filtered.filter((e) => {
      const g = this.groupBy === 'controller'
        ? extractControllerName(e.action)
        : (e.group ?? 'General');
      return g === group;
    });
    return sortEndpointsCanonical(matching);
  }

  get availableScopes(): { id: string; label: string }[] {
    const scopes: { id: string; label: string }[] = [
      { id: 'all', label: 'All Endpoints' },
      { id: 'api', label: 'API Routes (/api)' },
      { id: 'web', label: 'Web Routes' },
    ];
    const custom = new Set<string>();
    for (const ep of this.endpoints) {
      const clean = ep.uri.replace(/^\//, '');
      const parts = clean.split('/');
      if (parts.length > 1 && parts[0] === 'api') {
        custom.add(`api/${parts[1]}`);
      } else if (parts.length > 1 && !['api', 'probe'].includes(parts[0])) {
        custom.add(parts[0]);
      }
    }
    for (const prefix of Array.from(custom).sort()) {
      scopes.push({ id: prefix, label: `${prefix}/*` });
    }
    return scopes;
  }

  constructor(private readonly client: EndpointClient) {
    if (typeof localStorage !== 'undefined') {
      const savedGroupBy = localStorage.getItem('probe_group_by');
      if (savedGroupBy === 'resource' || savedGroupBy === 'controller') {
        this.groupBy = savedGroupBy;
      }
      try {
        const savedCollapsed = localStorage.getItem('probe_collapsed_groups');
        if (savedCollapsed) {
          const parsed = JSON.parse(savedCollapsed);
          if (Array.isArray(parsed)) {
            this.collapsedGroups = parsed;
          }
        }
      } catch {
        // Ignore parsing errors
      }
    }
  }

  async load(): Promise<void> {
    this.loading = true;
    this.error = null;
    try {
      this.endpoints = await this.client.list();
    } catch (err: unknown) {
      this.error = err instanceof Error ? err.message : 'Failed to load endpoints';
    } finally {
      this.loading = false;
    }
  }

  setSearchQuery(query: string): void {
    this.searchQuery = query;
  }

  setApiScope(scope: string): void {
    this.apiScope = scope;
  }

  setAuthFilter(filter: string): void {
    this.authFilter = filter;
  }

  get authCounts(): { all: number; sanctum: number; api_key: number; basic: number; public: number } {
    let sanctum = 0;
    let apiKey = 0;
    let basic = 0;
    let pub = 0;
    for (const ep of this.endpoints) {
      if (ep.auth === 'sanctum_act_as') sanctum++;
      else if (ep.auth === 'api_key') apiKey++;
      else if (ep.auth === 'basic') basic++;
      else if (ep.auth === 'none' || !ep.auth) pub++;
    }
    return {
      all: this.endpoints.length,
      sanctum,
      api_key: apiKey,
      basic,
      public: pub,
    };
  }

  setSelectedGroup(group: string | null): void {
    this.selectedGroup = group;
  }

  setGroupBy(mode: GroupByMode): void {
    this.groupBy = mode;
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('probe_group_by', mode);
    }
  }

  isGroupCollapsed(group: string): boolean {
    if (this.searchQuery.trim() !== '') {
      return false;
    }
    return this.collapsedGroups.includes(group);
  }

  toggleGroup(group: string): void {
    if (this.collapsedGroups.includes(group)) {
      this.collapsedGroups = this.collapsedGroups.filter((g) => g !== group);
    } else {
      this.collapsedGroups = [...this.collapsedGroups, group];
    }
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('probe_collapsed_groups', JSON.stringify(this.collapsedGroups));
    }
  }

  expandAll(): void {
    this.collapsedGroups = [];
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('probe_collapsed_groups', JSON.stringify([]));
    }
  }

  collapseAll(): void {
    this.collapsedGroups = [...this.groups];
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('probe_collapsed_groups', JSON.stringify(this.collapsedGroups));
    }
  }
}
