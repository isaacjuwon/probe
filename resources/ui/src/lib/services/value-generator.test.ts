import { describe, expect, it } from 'vitest';
import { generateValue, GENERATOR_OPTIONS } from './value-generator';

describe('value-generator', () => {
  it('generates valid UUIDs', () => {
    const val = generateValue('uuid');
    expect(val).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i);
  });

  it('generates valid timestamps and dates', () => {
    const ts = generateValue('timestamp');
    expect(ts).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/);

    const date = generateValue('date');
    expect(date).toMatch(/^\d{4}-\d{2}-\d{2}$/);
  });

  it('generates emails and names', () => {
    const email = generateValue('email');
    expect(email).toMatch(/^user\.[a-z0-9]+@example\.com$/);

    const name = generateValue('name');
    expect(name.length).toBeGreaterThan(3);
  });

  it('generates numbers and booleans', () => {
    const num = generateValue('number');
    expect(Number(num)).toBeGreaterThan(0);

    const bool = generateValue('boolean');
    expect(['true', 'false']).toContain(bool);
  });

  it('exports valid generator options', () => {
    expect(GENERATOR_OPTIONS.length).toBe(8);
  });
});
