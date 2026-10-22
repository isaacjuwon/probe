import { ProbeError } from '$lib/domain/errors';

export interface BootstrapConfig {
  readonly basePath: string;
  readonly csrfToken: string;
  readonly environment: string;
  readonly appName: string;
  readonly features: {
    readonly scribe: boolean;
    readonly inProcessTransport: boolean;
  };
}

declare global {
  interface Window {
    __PROBE__?: BootstrapConfig;
  }
}

export interface RequestOptions {
  readonly path: string;
  readonly method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  readonly headers?: Record<string, string>;
  readonly body?: unknown;
  readonly signal?: AbortSignal;
}

/**
 * Facade: Single HTTP client for Probe backend communication with CSRF and error mapping.
 */
export class ProbeApi {
  private readonly config: BootstrapConfig;

  constructor(customConfig?: BootstrapConfig) {
    this.config = customConfig ?? window.__PROBE__ ?? {
      basePath: 'probe',
      csrfToken: '',
      environment: 'local',
      appName: 'Laravel',
      features: {
        scribe: false,
        inProcessTransport: false,
      },
    };
  }

  getConfig(): BootstrapConfig {
    return this.config;
  }

  buildUrl(path: string): string {
    const cleanBase = this.config.basePath.replace(/^\/+|\/+$/g, '');
    const cleanPath = path.replace(/^\/+/g, '');
    return `/${cleanBase}/${cleanPath}`;
  }

  async request<T>(options: RequestOptions): Promise<T> {
    const { path, method = 'GET', headers = {}, body, signal } = options;
    const url = this.buildUrl(path);

    const mergedHeaders: Record<string, string> = {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...headers,
    };

    if (this.config.csrfToken) {
      mergedHeaders['X-CSRF-TOKEN'] = this.config.csrfToken;
    }

    let payload: BodyInit | undefined;
    if (body !== undefined && body !== null) {
      mergedHeaders['Content-Type'] = 'application/json';
      payload = JSON.stringify(body);
    }

    try {
      const response = await fetch(url, {
        method,
        headers: mergedHeaders,
        body: payload,
        signal,
      });

      if (!response.ok) {
        let details: unknown;
        try {
          details = await response.json();
        } catch {
          details = await response.text();
        }

        const kind = response.status === 403
          ? 'forbidden'
          : response.status === 422
          ? 'validation'
          : response.status >= 500
          ? 'server'
          : 'network';

        throw new ProbeError({
          kind,
          status: response.status,
          message: `Request to ${path} failed with status ${response.status}`,
          details,
        });
      }

      return (await response.json()) as T;
    } catch (err: unknown) {
      if (err instanceof ProbeError) {
        throw err;
      }

      if (err instanceof DOMException && err.name === 'AbortError') {
        throw new ProbeError({
          kind: 'aborted',
          message: 'Request was cancelled',
        });
      }

      throw new ProbeError({
        kind: 'network',
        message: err instanceof Error ? err.message : 'Network request failed',
        details: err,
      });
    }
  }
}
