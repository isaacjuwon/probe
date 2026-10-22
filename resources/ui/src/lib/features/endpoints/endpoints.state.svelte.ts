import type { Endpoint } from '$lib/domain/endpoint';
import type { EndpointClient } from '$lib/api/endpoint.client';

export class EndpointsState {
  endpoints = $state<Endpoint[]>([]);
  searchQuery = $state<string>('');
  selectedGroup = $state<string | null>(null);
  loading = $state<boolean>(false);
  error = $state<string | null>(null);

  get filtered(): Endpoint[] {
    return this.endpoints.filter((e) => {
      const matchesSearch =
        this.searchQuery.trim() === '' ||
        e.uri.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
        (e.name && e.name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
        e.method.toLowerCase().includes(this.searchQuery.toLowerCase());

      const matchesGroup =
        this.selectedGroup === null || (e.group ?? 'General') === this.selectedGroup;

      return matchesSearch && matchesGroup;
    });
  }

  get groups(): string[] {
    return Array.from(new Set(this.endpoints.map((e) => e.group ?? 'General'))).sort();
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

  setSelectedGroup(group: string | null): void {
    this.selectedGroup = group;
  }
}
