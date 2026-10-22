import { describe, it, expect } from 'vitest';
import { ProbeError } from './errors';

describe('errors', () => {
  it('constructs ProbeError with status and details', () => {
    const error = new ProbeError({
      kind: 'server',
      message: 'Relay failed',
      status: 502,
      details: { detail: 'Gateway Timeout' },
    });

    expect(error.message).toBe('Relay failed');
    expect(error.kind).toBe('server');
    expect(error.status).toBe(502);
    expect(error.details).toEqual({ detail: 'Gateway Timeout' });
    expect(error.name).toBe('ProbeError');
  });

  it('correctly discriminates ProbeError instances', () => {
    const apiError = new ProbeError({
      kind: 'validation',
      message: 'Validation failed',
      status: 422,
    });
    const standardError = new Error('Standard failure');

    expect(apiError instanceof ProbeError).toBe(true);
    expect(standardError instanceof ProbeError).toBe(false);
  });
});
