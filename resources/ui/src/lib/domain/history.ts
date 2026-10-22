import type { HttpMethod } from '$lib/domain/endpoint';

export interface HistoryItem {
  readonly id: string;
  readonly timestamp: number;
  readonly method: HttpMethod;
  readonly url: string;
  readonly status: number;
  readonly durationMs: number;
  readonly headers: Record<string, string>;
  readonly query: Record<string, string>;
  readonly body: string;
  readonly pathParams?: Record<string, string>;
  readonly dryRun?: boolean;
}
