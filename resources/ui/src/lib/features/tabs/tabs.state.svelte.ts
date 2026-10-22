import type { RequestTab } from '$lib/domain/request-tab';
import type { TabRepository } from '$lib/persistence/tab.repository';
import { generateId } from '$lib/utils';

export class TabsState {
  tabs = $state<RequestTab[]>([]);
  activeId = $state<string | null>(null);

  get active(): RequestTab | null {
    return this.tabs.find((tab) => tab.id === this.activeId) ?? null;
  }

  constructor(private readonly repository: TabRepository) {}

  async load(): Promise<void> {
    this.tabs = await this.repository.all();
    if (this.tabs.length === 0) {
      this.createDefaultTab();
    } else {
      this.activeId = this.tabs[0]?.id ?? null;
    }
  }

  open(options: { readonly tab: RequestTab }): void {
    const existing = this.tabs.find((t) => t.id === options.tab.id);
    if (existing) {
      this.activeId = existing.id;
      return;
    }
    this.tabs = [...this.tabs, options.tab];
    this.activeId = options.tab.id;
    void this.repository.save({ tab: options.tab });
  }

  close(options: { readonly id: string }): void {
    const index = this.tabs.findIndex((t) => t.id === options.id);
    if (index === -1) return;

    const remaining = this.tabs.filter((t) => t.id !== options.id);
    this.tabs = remaining;
    void this.repository.delete({ id: options.id });

    if (this.activeId === options.id) {
      this.activeId = remaining[Math.max(0, index - 1)]?.id ?? null;
    }

    if (remaining.length === 0) {
      this.createDefaultTab();
    }
  }

  setActive(options: { readonly id: string }): void {
    this.activeId = options.id;
  }

  updateActive(options: { readonly updates: Partial<RequestTab> }): void {
    const current = this.active;
    if (!current) return;
    Object.assign(current, options.updates);
    current.isDirty = true;
    this.tabs = [...this.tabs];
    void this.repository.save({ tab: current });
  }

  createDefaultTab(): RequestTab {
    const origin = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
    const tab: RequestTab = {
      id: generateId(),
      title: 'New Request',
      method: 'GET',
      url: `${origin}/api/`,
      headers: { Accept: 'application/json' },
      query: {},
      body: '',
      authType: 'none',
      authPayload: {},
      lastResult: null,
    };
    this.open({ tab });
    return tab;
  }
}
