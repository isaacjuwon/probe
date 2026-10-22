import type { Endpoint } from '$lib/domain/endpoint';
import type { EndpointClient } from '$lib/api/endpoint.client';

export class EndpointsState {
  endpoints = $state.raw<Endpoint[]>([]);
  searchQuery = $state<string>('');
  selectedGroup = $state<string | null>(null);
  apiScope = $state<string>('all');
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

      return matchesScope && matchesSearch && matchesGroup;
    });
  }

  get groups(): string[] {
    return Array.from(new Set(this.endpoints.map((e) => e.group ?? 'General'))).sort();
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

  constructor(private readonly client: EndpointClient) {}

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

  setSelectedGroup(group: string | null): void {
    this.selectedGroup = group;
  }
}
