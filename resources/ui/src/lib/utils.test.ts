import { describe, expect, it, vi } from 'vitest';
import { generateId } from './utils';

describe('generateId', () => {
  it('generates a valid UUID string format', () => {
    const id = generateId();
    expect(id).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i);
  });

  it('generates unique IDs across calls', () => {
    const ids = new Set(Array.from({ length: 100 }, () => generateId()));
    expect(ids.size).toBe(100);
  });

  it('falls back when crypto.randomUUID is not a function (insecure context)', () => {
    const originalDescriptor = Object.getOwnPropertyDescriptor(globalThis, 'crypto');
    try {
      Object.defineProperty(globalThis, 'crypto', {
        value: {
          getRandomValues: originalDescriptor?.value?.getRandomValues?.bind(originalDescriptor.value),
        },
        configurable: true,
        writable: true,
      });

      const id = generateId();
      expect(id).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i);
    } finally {
      if (originalDescriptor) {
        Object.defineProperty(globalThis, 'crypto', originalDescriptor);
      }
    }
  });

  it('falls back to Math.random when crypto is completely absent', () => {
    const originalDescriptor = Object.getOwnPropertyDescriptor(globalThis, 'crypto');
    try {
      Object.defineProperty(globalThis, 'crypto', {
        value: undefined,
        configurable: true,
        writable: true,
      });

      const id = generateId();
      expect(id).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i);
    } finally {
      if (originalDescriptor) {
        Object.defineProperty(globalThis, 'crypto', originalDescriptor);
      }
    }
  });
});
