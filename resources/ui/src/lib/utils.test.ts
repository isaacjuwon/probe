import { describe, expect, it, vi } from 'vitest';
import { generateId, extractPathParams, interpolateUrl, copyToClipboard } from './utils';

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

  describe('extractPathParams', () => {
    it('extracts param names from route URL', () => {
      expect(extractPathParams('http://localhost/api/users/{id}')).toEqual(['id']);
      expect(extractPathParams('http://localhost/api/teams/{team}/members/{user}')).toEqual(['team', 'user']);
      expect(extractPathParams('http://localhost/api/posts')).toEqual([]);
    });
  });

  describe('interpolateUrl', () => {
    it('replaces path parameters with supplied values', () => {
      expect(interpolateUrl('http://localhost/api/users/{id}', { id: '42' })).toBe('http://localhost/api/users/42');
      expect(interpolateUrl('http://localhost/api/teams/{team}/members/{user}', { team: 'eng', user: 'alice' }))
        .toBe('http://localhost/api/teams/eng/members/alice');
    });

    it('leaves unfilled path parameters intact if missing or empty', () => {
      expect(interpolateUrl('http://localhost/api/users/{id}', { id: '' })).toBe('http://localhost/api/users/{id}');
      expect(interpolateUrl('http://localhost/api/users/{id}')).toBe('http://localhost/api/users/{id}');
    });
  });

  describe('copyToClipboard', () => {
    it('uses navigator.clipboard.writeText when available', async () => {
      const writeText = vi.fn().mockResolvedValue(undefined);
      const originalClipboard = navigator.clipboard;
      Object.defineProperty(navigator, 'clipboard', {
        value: { writeText },
        configurable: true,
        writable: true,
      });

      try {
        const result = await copyToClipboard('hello world');
        expect(result).toBe(true);
        expect(writeText).toHaveBeenCalledWith('hello world');
      } finally {
        Object.defineProperty(navigator, 'clipboard', {
          value: originalClipboard,
          configurable: true,
          writable: true,
        });
      }
    });

    it('falls back to document.execCommand when navigator.clipboard is undefined (insecure context)', async () => {
      const originalClipboard = navigator.clipboard;
      const mockTextarea = {
        value: '',
        style: { position: '', left: '', top: '', opacity: '' },
        focus: vi.fn(),
        select: vi.fn(),
      };
      const execCommand = vi.fn().mockReturnValue(true);
      const appendChild = vi.fn();
      const removeChild = vi.fn();

      const originalDocument = (globalThis as unknown as { document?: unknown }).document;
      (globalThis as unknown as { document: unknown }).document = {
        createElement: vi.fn().mockReturnValue(mockTextarea),
        body: {
          appendChild,
          removeChild,
        },
        execCommand,
      };

      Object.defineProperty(navigator, 'clipboard', {
        value: undefined,
        configurable: true,
        writable: true,
      });

      try {
        const result = await copyToClipboard('fallback text');
        expect(result).toBe(true);
        expect(mockTextarea.value).toBe('fallback text');
        expect(mockTextarea.focus).toHaveBeenCalled();
        expect(mockTextarea.select).toHaveBeenCalled();
        expect(execCommand).toHaveBeenCalledWith('copy');
        expect(appendChild).toHaveBeenCalledWith(mockTextarea);
        expect(removeChild).toHaveBeenCalledWith(mockTextarea);
      } finally {
        Object.defineProperty(navigator, 'clipboard', {
          value: originalClipboard,
          configurable: true,
          writable: true,
        });
        (globalThis as unknown as { document: unknown }).document = originalDocument;
      }
    });

    it('falls back to document.execCommand when writeText rejects', async () => {
      const writeText = vi.fn().mockRejectedValue(new Error('Permission denied'));
      const originalClipboard = navigator.clipboard;
      const mockTextarea = {
        value: '',
        style: { position: '', left: '', top: '', opacity: '' },
        focus: vi.fn(),
        select: vi.fn(),
      };
      const execCommand = vi.fn().mockReturnValue(true);

      const originalDocument = (globalThis as unknown as { document?: unknown }).document;
      (globalThis as unknown as { document: unknown }).document = {
        createElement: vi.fn().mockReturnValue(mockTextarea),
        body: {
          appendChild: vi.fn(),
          removeChild: vi.fn(),
        },
        execCommand,
      };

      Object.defineProperty(navigator, 'clipboard', {
        value: { writeText },
        configurable: true,
        writable: true,
      });

      try {
        const result = await copyToClipboard('fallback after error');
        expect(result).toBe(true);
        expect(writeText).toHaveBeenCalledWith('fallback after error');
        expect(execCommand).toHaveBeenCalledWith('copy');
      } finally {
        Object.defineProperty(navigator, 'clipboard', {
          value: originalClipboard,
          configurable: true,
          writable: true,
        });
        (globalThis as unknown as { document: unknown }).document = originalDocument;
      }
    });

    it('returns false if both clipboard and execCommand fail', async () => {
      const originalClipboard = navigator.clipboard;
      const mockTextarea = {
        value: '',
        style: { position: '', left: '', top: '', opacity: '' },
        focus: vi.fn(),
        select: vi.fn(),
      };
      const execCommand = vi.fn().mockReturnValue(false);

      const originalDocument = (globalThis as unknown as { document?: unknown }).document;
      (globalThis as unknown as { document: unknown }).document = {
        createElement: vi.fn().mockReturnValue(mockTextarea),
        body: {
          appendChild: vi.fn(),
          removeChild: vi.fn(),
        },
        execCommand,
      };

      Object.defineProperty(navigator, 'clipboard', {
        value: undefined,
        configurable: true,
        writable: true,
      });

      try {
        const result = await copyToClipboard('fail text');
        expect(result).toBe(false);
        expect(execCommand).toHaveBeenCalledWith('copy');
      } finally {
        Object.defineProperty(navigator, 'clipboard', {
          value: originalClipboard,
          configurable: true,
          writable: true,
        });
        (globalThis as unknown as { document: unknown }).document = originalDocument;
      }
    });

    it('returns false when document is undefined and clipboard is unavailable', async () => {
      const originalClipboard = navigator.clipboard;
      const originalDocument = (globalThis as unknown as { document?: unknown }).document;

      Object.defineProperty(navigator, 'clipboard', {
        value: undefined,
        configurable: true,
        writable: true,
      });
      (globalThis as unknown as { document: unknown }).document = undefined;

      try {
        const result = await copyToClipboard('no doc or clipboard');
        expect(result).toBe(false);
      } finally {
        Object.defineProperty(navigator, 'clipboard', {
          value: originalClipboard,
          configurable: true,
          writable: true,
        });
        (globalThis as unknown as { document: unknown }).document = originalDocument;
      }
    });
  });
});
