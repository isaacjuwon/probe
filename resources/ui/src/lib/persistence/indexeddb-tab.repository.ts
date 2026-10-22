import type { RequestTab } from '$lib/domain/request-tab';
import type { DeleteTabOptions, SaveTabOptions, TabRepository } from '$lib/persistence/tab.repository';
import { MemoryTabRepository } from '$lib/persistence/memory-tab.repository';

const DB_NAME = 'probe_db';
const DB_VERSION = 1;
const STORE_NAME = 'tabs';

export class IndexedDbTabRepository implements TabRepository {
  private fallback = new MemoryTabRepository();

  private async openDb(): Promise<IDBDatabase | null> {
    if (typeof window === 'undefined' || !window.indexedDB) {
      return null;
    }

    return new Promise((resolve) => {
      const request = window.indexedDB.open(DB_NAME, DB_VERSION);

      request.onupgradeneeded = () => {
        const db = request.result;
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          db.createObjectStore(STORE_NAME, { keyPath: 'id' });
        }
      };

      request.onsuccess = () => resolve(request.result);
      request.onerror = () => resolve(null);
    });
  }

  async all(): Promise<RequestTab[]> {
    const db = await this.openDb();
    if (!db) {
      return this.fallback.all();
    }

    return new Promise((resolve) => {
      const transaction = db.transaction(STORE_NAME, 'readonly');
      const store = transaction.objectStore(STORE_NAME);
      const request = store.getAll();

      request.onsuccess = () => resolve((request.result as RequestTab[]) ?? []);
      request.onerror = () => resolve([]);
    });
  }

  async save(options: SaveTabOptions): Promise<void> {
    const db = await this.openDb();
    if (!db) {
      return this.fallback.save(options);
    }

    return new Promise((resolve, reject) => {
      const transaction = db.transaction(STORE_NAME, 'readwrite');
      const store = transaction.objectStore(STORE_NAME);
      const request = store.put(structuredClone(options.tab));

      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  async delete(options: DeleteTabOptions): Promise<void> {
    const db = await this.openDb();
    if (!db) {
      return this.fallback.delete(options);
    }

    return new Promise((resolve, reject) => {
      const transaction = db.transaction(STORE_NAME, 'readwrite');
      const store = transaction.objectStore(STORE_NAME);
      const request = store.delete(options.id);

      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  async clear(): Promise<void> {
    const db = await this.openDb();
    if (!db) {
      return this.fallback.clear();
    }

    return new Promise((resolve, reject) => {
      const transaction = db.transaction(STORE_NAME, 'readwrite');
      const store = transaction.objectStore(STORE_NAME);
      const request = store.clear();

      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }
}
