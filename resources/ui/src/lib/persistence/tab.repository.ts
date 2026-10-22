import type { RequestTab } from '$lib/domain/request-tab';

export interface SaveTabOptions {
  readonly tab: RequestTab;
}

export interface DeleteTabOptions {
  readonly id: string;
}

export interface TabRepository {
  all(): Promise<RequestTab[]>;
  save(options: SaveTabOptions): Promise<void>;
  delete(options: DeleteTabOptions): Promise<void>;
  clear(): Promise<void>;
}
