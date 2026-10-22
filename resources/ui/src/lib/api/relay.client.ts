import type { RelayRequest, RelayResult } from '$lib/domain/relay';
import type { ProbeApi } from '$lib/api/probe.client';

export interface SendRelayOptions {
  readonly request: RelayRequest;
  readonly signal?: AbortSignal;
}

export class RelayClient {
  constructor(private readonly api: ProbeApi) {}

  async send(options: SendRelayOptions): Promise<RelayResult> {
    const { request, signal } = options;

    const response = await this.api.request<{ data: RelayResult }>({
      path: 'relay',
      method: 'POST',
      body: {
        method: request.method,
        url: request.url,
        headers: request.headers,
        body: request.body,
        query: request.query,
        auth_type: request.authType,
        auth_payload: request.authPayload,
        timeout_seconds: request.timeoutSeconds,
      },
      signal,
    });

    return response.data;
  }
}
