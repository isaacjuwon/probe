import type { ProbeApi } from '$lib/api/probe.client';

export interface AppUser {
  readonly id: string;
  readonly name: string;
  readonly email?: string | null;
}

export class UsersClient {
  constructor(private readonly api: ProbeApi) {}

  async list(options?: { readonly signal?: AbortSignal }): Promise<AppUser[]> {
    const response = await this.api.request<{ data: AppUser[] }>({
      path: 'users',
      method: 'GET',
      signal: options?.signal,
    });

    return response.data;
  }
}
