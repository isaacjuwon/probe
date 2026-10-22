import type { AuthType, Endpoint, HttpMethod } from '$lib/domain/endpoint';
import type { RelayResult } from '$lib/domain/relay';

export interface RequestTab {
  readonly id: string;
  readonly title: string;
  method: HttpMethod;
  url: string;
  headers: Record<string, string>;
  query: Record<string, string>;
  body: string;
  authType: AuthType;
  authPayload: Record<string, unknown>;
  lastResult?: RelayResult | null;
  isDirty?: boolean;
  endpoint?: Endpoint | null;
}
