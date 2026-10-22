import type { AuthType, HttpMethod } from '$lib/domain/endpoint';

export interface RelayRequest {
  readonly method: HttpMethod;
  readonly url: string;
  readonly headers?: Readonly<Record<string, string>>;
  readonly body?: unknown;
  readonly query?: Readonly<Record<string, unknown>>;
  readonly authType?: AuthType;
  readonly authPayload?: Readonly<Record<string, unknown>>;
  readonly timeoutSeconds?: number;
  readonly dryRun?: boolean;
}

export interface CapturedQuery {
  readonly sql: string;
  readonly bindings?: readonly unknown[];
  readonly time?: number;
  readonly connection?: string;
}

export interface CapturedException {
  readonly class: string;
  readonly message: string;
  readonly code?: number | string;
  readonly file?: string;
  readonly line?: number;
}

export interface RelayResult {
  readonly status: number;
  readonly headers: Readonly<Record<string, string | string[]>>;
  readonly body: unknown;
  readonly durationMs: number;
  readonly queries: readonly CapturedQuery[];
  readonly dumps: readonly string[];
  readonly logs: readonly unknown[];
  readonly exceptions: readonly CapturedException[];
  readonly dryRun?: boolean;
}
