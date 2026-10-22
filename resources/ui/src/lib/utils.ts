import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs));
}

export type WithoutChild<T> = T extends { child?: unknown } ? Omit<T, 'child'> : T;

export type WithoutChildren<T> = T extends { children?: unknown } ? Omit<T, 'children'> : T;

export type WithoutChildrenOrChild<T> = T extends { child?: unknown; children?: unknown }
  ? Omit<T, 'child' | 'children'>
  : T extends { child?: unknown }
    ? Omit<T, 'child'>
    : T extends { children?: unknown }
      ? Omit<T, 'children'>
      : T;

export type WithElementRef<T, E extends HTMLElement = HTMLElement> = T & {
  ref?: E | null;
};

export function generateId(): string {
  if (typeof crypto !== 'undefined') {
    if (typeof crypto.randomUUID === 'function') {
      try {
        return crypto.randomUUID();
      } catch {
        // Fallback if randomUUID fails in insecure context
      }
    }
    if (typeof crypto.getRandomValues === 'function') {
      try {
        const bytes = new Uint8Array(16);
        crypto.getRandomValues(bytes);
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;
        const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
        return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
      } catch {
        // Fallback to Math.random
      }
    }
  }

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}


