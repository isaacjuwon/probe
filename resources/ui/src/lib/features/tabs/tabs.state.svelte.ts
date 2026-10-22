import type { RequestTab } from '$lib/domain/request-tab';
import type { TabRepository } from '$lib/persistence/tab.repository';

export class TabsState {
  tabs = $state<RequestTab[]>([]);
  activeId = $state<string | null>(null);
  active = $derived(this.tabs.find((tab) => tab.id === this.activeId) ?? null);

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
    this.tabs.push(options.tab);
    this.activeId = options.tab.id;
    void this.repository.save({ tab: options.tab });
  }

  close(options: { readonly id: string }): void {
    const index = this.tabs.findIndex((t) => t.id === options.id);
    if (index === -1) return;

    this.tabs.splice(index, 1);
    void this.repository.delete({ id: options.id });

    if (this.activeId === options.id) {
      this.activeId = this.tabs[Math.max(0, index - 1)]?.id ?? null;
    }

    if (this.tabs.length === 0) {
      this.createDefaultTab();
    }
  }

  setActive(options: { readonly id: string }): void {
    this.activeId = options.id;
  }

  updateActive(options: { readonly updates: Partial<RequestTab> }): void {
    if (!this.active) return;
    Object.assign(this.active, options.updates);
    this.active.isDirty = true;
    void this.repository.save({ tab: this.active });
  }

  createDefaultTab(): RequestTab {
    const tab: RequestTab = {
      id: crypto.randomUUID(),
      title: 'New Request',
      method: 'GET',
      url: 'http://localhost/api/',
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
