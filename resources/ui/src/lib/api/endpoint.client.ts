import type { Endpoint } from '$lib/domain/endpoint';
import type { ProbeApi } from '$lib/api/probe.client';

export class EndpointClient {
  constructor(private readonly api: ProbeApi) {}

  async list(options?: { readonly signal?: AbortSignal }): Promise<Endpoint[]> {
    const response = await this.api.request<{ data: Endpoint[] }>({
      path: 'endpoints',
      method: 'GET',
      signal: options?.signal,
    });

    return response.data;
  }
}
