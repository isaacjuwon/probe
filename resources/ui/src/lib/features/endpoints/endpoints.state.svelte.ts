import type { Endpoint } from '$lib/domain/endpoint';
import type { EndpointClient } from '$lib/api/endpoint.client';
import { extractControllerName, sortEndpointsCanonical } from './endpoint-sorter';

export type GroupByMode = 'resource' | 'controller' | 'version';

export class EndpointsState {
  endpoints = $state.raw<Endpoint[]>([]);
  searchQuery = $state<string>('');
  selectedGroup = $state<string | null>(null);
  apiScope = $state<string>('all');
  authFilter = $state<string>('all');
  versionFilter = $state<string>('all');
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

      const matchesVersion =
        this.versionFilter === 'all' ||
        (this.versionFilter === 'unversioned' && !e.version) ||
        e.version === this.versionFilter;

      return matchesScope && matchesSearch && matchesGroup && matchesAuth && matchesVersion;
    });
  }

  get groups(): string[] {
    const set = new Set<string>();
    for (const ep of this.filtered) {
      let g: string;
      if (this.groupBy === 'controller') {
        g = extractControllerName(ep.action);
      } else if (this.groupBy === 'version') {
        g = ep.version ? ep.version.toUpperCase() : 'Unversioned';
      } else {
        g = ep.group ?? 'General';
      }
      set.add(g);
    }
    return Array.from(set).sort();
  }

  getEndpointsForGroup(group: string): Endpoint[] {
    const matching = this.filtered.filter((e) => {
      let g: string;
      if (this.groupBy === 'controller') {
        g = extractControllerName(e.action);
      } else if (this.groupBy === 'version') {
        g = e.version ? e.version.toUpperCase() : 'Unversioned';
      } else {
        g = e.group ?? 'General';
      }
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
      if (savedGroupBy === 'resource' || savedGroupBy === 'controller' || savedGroupBy === 'version') {
        this.groupBy = savedGroupBy;
      }
      const savedVersion = localStorage.getItem('probe_version_filter');
      if (savedVersion) {
        this.versionFilter = savedVersion;
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

  setVersionFilter(version: string): void {
    this.versionFilter = version;
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('probe_version_filter', version);
    }
  }

  get versions(): string[] {
    const set = new Set<string>();
    for (const ep of this.endpoints) {
      if (ep.version) {
        set.add(ep.version);
      }
    }
    return Array.from(set).sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
  }

  get versionCounts(): Record<string, number> {
    const counts: Record<string, number> = { all: this.endpoints.length, unversioned: 0 };
    for (const v of this.versions) {
      counts[v] = 0;
    }
    for (const ep of this.endpoints) {
      if (ep.version) {
        counts[ep.version] = (counts[ep.version] ?? 0) + 1;
      } else {
        counts.unversioned = (counts.unversioned ?? 0) + 1;
      }
    }
    return counts;
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
