import type { ProbeApi } from '$lib/api/probe.client';

export interface SaveRequestOptions {
  readonly id: string;
  readonly data: Record<string, unknown>;
  readonly signal?: AbortSignal;
}

export class StorageClient {
  constructor(private readonly api: ProbeApi) {}

  async save(options: SaveRequestOptions): Promise<boolean> {
    const { id, data, signal } = options;

    const response = await this.api.request<{ success: boolean }>({
      path: 'requests',
      method: 'POST',
      body: {
        id,
        data,
      },
      signal,
    });

    return response.success;
  }
}
