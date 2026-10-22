import type { RelayClient } from '$lib/api/relay.client';
import type { RequestTab } from '$lib/domain/request-tab';
import type { RelayResult } from '$lib/domain/relay';
import { ProbeError } from '$lib/domain/errors';

export type RequestStatus = 'idle' | 'sending' | 'success' | 'error' | 'aborted';

/**
 * State Machine: Controls the lifecycle and transitions of an in-flight relay request.
 */
export class RequestState {
  status = $state<RequestStatus>('idle');
  error = $state<string | null>(null);
  activeController: AbortController | null = null;

  constructor(private readonly client: RelayClient) {}

  async send(options: { readonly tab: RequestTab }): Promise<RelayResult | null> {
    const { tab } = options;

    if (this.status === 'sending') {
      this.abort();
    }

    this.status = 'sending';
    this.error = null;
    this.activeController = new AbortController();

    try {
      let parsedBody: unknown = undefined;
      if (tab.body.trim()) {
        try {
          parsedBody = JSON.parse(tab.body);
        } catch {
          parsedBody = tab.body;
        }
      }

      const result = await this.client.send({
        request: {
          method: tab.method,
          url: tab.url,
          headers: tab.headers,
          body: parsedBody,
          query: tab.query,
          authType: tab.authType,
          authPayload: tab.authPayload,
        },
        signal: this.activeController.signal,
      });

      this.status = 'success';
      tab.lastResult = result;
      return result;
    } catch (err: unknown) {
      if (err instanceof ProbeError && err.kind === 'aborted') {
        this.status = 'aborted';
        this.error = 'Request aborted.';
      } else {
        this.status = 'error';
        this.error = err instanceof Error ? err.message : 'Relay request failed.';
      }
      return null;
    } finally {
      this.activeController = null;
    }
  }

  abort(): void {
    if (this.activeController) {
      this.activeController.abort();
      this.activeController = null;
      this.status = 'aborted';
    }
  }
}
