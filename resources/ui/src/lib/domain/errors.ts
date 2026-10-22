export type ProbeErrorKind = 'network' | 'validation' | 'forbidden' | 'server' | 'aborted';

export interface ProbeErrorOptions {
  readonly kind: ProbeErrorKind;
  readonly message: string;
  readonly status?: number;
  readonly details?: unknown;
}

export class ProbeError extends Error {
  readonly kind: ProbeErrorKind;
  readonly status?: number;
  readonly details?: unknown;

  constructor(options: ProbeErrorOptions) {
    super(options.message);
    this.name = 'ProbeError';
    this.kind = options.kind;
    this.status = options.status;
    this.details = options.details;
  }
}
