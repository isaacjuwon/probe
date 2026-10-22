<script lang="ts">
  import type { TabsState } from '$lib/features/tabs/tabs.state.svelte';
  import type { Services } from '$lib/services/context';
  import MethodBadge from '$lib/components/MethodBadge.svelte';
  import { Input } from '$lib/components/ui/input';
  import { Button } from '$lib/components/ui/button';
  import { ScrollArea } from '$lib/components/ui/scroll-area';
  import {
    Bookmark,
    Plus,
    Search,
    Trash2,
    Download,
    Upload,
    FolderArchive,
    ExternalLink,
    Check,
  } from '@lucide/svelte';
  import { generateId } from '$lib/utils';
  import type { HttpMethod } from '$lib/domain/endpoint';

  export interface SavedRequestItem {
    readonly id: string;
    readonly title: string;
    readonly collection: string;
    readonly method: HttpMethod;
    readonly url: string;
    readonly headers: Record<string, string>;
    readonly query: Record<string, string>;
    readonly pathParams?: Record<string, string>;
    readonly body?: string;
    readonly authType?: string;
    readonly authPayload?: Record<string, unknown>;
    readonly savedAt: number;
  }

  export interface Props {
    readonly tabsState: TabsState;
    readonly services: Services;
  }

  const { tabsState, services }: Props = $props();

  const STORAGE_KEY = 'probe_saved_collections_v1';

  let savedItems = $state<SavedRequestItem[]>([]);
  let searchQuery = $state('');
  let selectedCollection = $state<string>('all');
  let saveFeedback = $state<string | null>(null);

  // Load from local storage
  if (typeof window !== 'undefined') {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (raw) {
        savedItems = JSON.parse(raw);
      }
    } catch {
      savedItems = [];
    }
  }

  function persist(items: SavedRequestItem[]): void {
    savedItems = items;
    if (typeof window !== 'undefined') {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    }
  }

  const collections = $derived.by(() => {
    const set = new Set<string>();
    for (const item of savedItems) {
      if (item.collection) set.add(item.collection);
    }
    return ['all', ...Array.from(set)];
  });

  const filteredItems = $derived.by(() => {
    const q = searchQuery.toLowerCase().trim();
    return savedItems.filter((item) => {
      const matchesCol = selectedCollection === 'all' || item.collection === selectedCollection;
      const matchesQuery = !q || item.title.toLowerCase().includes(q) || item.url.toLowerCase().includes(q);
      return matchesCol && matchesQuery;
    });
  });

  function saveCurrentTab(): void {
    if (!tabsState.active) return;
    const tab = tabsState.active;
    const collectionName = prompt('Enter collection name (e.g. "Commerce API", "Auth Tests"):', 'General') || 'General';

    const newItem: SavedRequestItem = {
      id: generateId(),
      title: tab.title || tab.url,
      collection: collectionName,
      method: tab.method,
      url: tab.url,
      headers: { ...tab.headers },
      query: { ...tab.query },
      pathParams: tab.pathParams ? { ...tab.pathParams } : undefined,
      body: tab.body,
      authType: tab.authType,
      authPayload: tab.authPayload ? { ...tab.authPayload } : undefined,
      savedAt: Date.now(),
    };

    persist([newItem, ...savedItems]);
    saveFeedback = `Saved "${newItem.title}"!`;
    setTimeout(() => {
      saveFeedback = null;
    }, 2500);

    // Also persist to backend storage service
    void services.storage.save({
      id: newItem.id,
      data: {
        title: newItem.title,
        collection: newItem.collection,
        method: newItem.method,
        url: newItem.url,
        headers: newItem.headers,
        query: newItem.query,
        body: newItem.body,
        auth_type: newItem.authType,
        auth_payload: newItem.authPayload,
      },
    });
  }

  function openSavedItem(item: SavedRequestItem): void {
    tabsState.open({
      tab: {
        id: generateId(),
        title: item.title,
        method: item.method,
        url: item.url,
        headers: { ...item.headers },
        query: { ...item.query },
        pathParams: item.pathParams ? { ...item.pathParams } : undefined,
        body: item.body ?? '',
        authType: item.authType ?? 'none',
        authPayload: item.authPayload ?? {},
        dryRun: false,
        lastResult: null,
      },
    });
  }

  function deleteItem(id: string, e: MouseEvent): void {
    e.stopPropagation();
    persist(savedItems.filter((i) => i.id !== id));
  }

  function exportCollections(): void {
    const dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(savedItems, null, 2));
    const downloadAnchor = document.createElement('a');
    downloadAnchor.setAttribute('href', dataStr);
    downloadAnchor.setAttribute('download', `probe-collections-${new Date().toISOString().slice(0, 10)}.json`);
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
  }

  function importCollections(): void {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = (e) => {
      const file = (e.target as HTMLInputElement).files?.[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (ev) => {
        try {
          const imported = JSON.parse(ev.target?.result as string);
          if (Array.isArray(imported)) {
            persist([...imported, ...savedItems]);
          }
        } catch {
          alert('Invalid JSON file format.');
        }
      };
      reader.readAsText(file);
    };
    input.click();
  }
</script>

<div class="flex flex-col h-full bg-card/30">
  <!-- Header / Action bar -->
  <div class="p-3 border-b border-border space-y-2.5">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
        <Bookmark class="h-3.5 w-3.5 text-primary" />
        Collections ({savedItems.length})
      </span>
      <div class="flex items-center gap-1">
        <Button
          variant="ghost"
          size="icon-sm"
          onclick={exportCollections}
          class="h-6 w-6 text-muted-foreground hover:text-foreground"
          title="Export Collections to JSON"
        >
          <Download class="h-3.5 w-3.5" />
        </Button>
        <Button
          variant="ghost"
          size="icon-sm"
          onclick={importCollections}
          class="h-6 w-6 text-muted-foreground hover:text-foreground"
          title="Import Collections JSON"
        >
          <Upload class="h-3.5 w-3.5" />
        </Button>
      </div>
    </div>

    <!-- Quick action: Save Current Tab -->
    <Button
      variant="outline"
      size="sm"
      onclick={saveCurrentTab}
      disabled={!tabsState.active}
      class="w-full h-7 text-xs font-medium justify-center gap-1.5 bg-background/50 hover:bg-background border-dashed"
    >
      {#if saveFeedback}
        <Check class="h-3.5 w-3.5 text-emerald-500" />
        <span class="text-emerald-500 font-medium">{saveFeedback}</span>
      {:else}
        <Plus class="h-3.5 w-3.5 text-primary" />
        <span>Save Active Request</span>
      {/if}
    </Button>

    <!-- Search filter -->
    <div class="relative">
      <Search class="h-3.5 w-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
      <Input
        type="text"
        placeholder="Filter saved requests..."
        bind:value={searchQuery}
        class="h-7 text-xs pl-8 bg-background/50"
      />
    </div>

    <!-- Collection tags filter -->
    {#if collections.length > 2}
      <div class="flex items-center gap-1 overflow-x-auto pb-1 text-[11px] no-scrollbar">
        {#each collections as col}
          <button
            type="button"
            onclick={() => (selectedCollection = col)}
            class="px-2 py-0.5 rounded-full border text-[10px] font-medium uppercase tracking-wider shrink-0 transition-colors {selectedCollection === col
              ? 'bg-primary text-primary-foreground border-primary'
              : 'border-border text-muted-foreground hover:text-foreground hover:border-foreground/30'}"
          >
            {col}
          </button>
        {/each}
      </div>
    {/if}
  </div>

  <!-- Items list -->
  <ScrollArea class="flex-1">
    {#if filteredItems.length === 0}
      <div class="p-6 text-center text-xs text-muted-foreground space-y-2">
        <FolderArchive class="h-8 w-8 mx-auto opacity-40 text-primary" />
        <p class="font-medium">No saved requests yet</p>
        <p class="text-[11px] text-muted-foreground/80">
          Click <strong>"Save Active Request"</strong> above to organize your requests into collections.
        </p>
      </div>
    {:else}
      <div class="p-1 space-y-1">
        {#each filteredItems as item (item.id)}
          <div
            role="button"
            tabindex="0"
            onclick={() => openSavedItem(item)}
            onkeydown={(e) => e.key === 'Enter' && openSavedItem(item)}
            class="group w-full text-left p-2 rounded-md hover:bg-accent/50 cursor-pointer transition-colors border border-transparent hover:border-border/60"
          >
            <div class="flex items-center justify-between gap-1 mb-1">
              <span class="text-xs font-medium text-foreground truncate max-w-[170px]" title={item.title}>
                {item.title}
              </span>
              <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button
                  type="button"
                  onclick={(e) => deleteItem(item.id, e)}
                  class="p-0.5 rounded text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                  title="Delete from collection"
                >
                  <Trash2 class="h-3 w-3" />
                </button>
              </div>
            </div>

            <div class="flex items-center gap-1.5 text-[11px] text-muted-foreground">
              <MethodBadge method={item.method} class="text-[9px] px-1 py-0 h-4" />
              <span class="truncate font-mono text-[10px] text-muted-foreground/90">{item.url}</span>
            </div>

            {#if item.collection}
              <div class="mt-1 flex items-center gap-1">
                <span class="text-[9px] px-1.5 py-0.2 rounded bg-muted text-muted-foreground font-mono">
                  {item.collection}
                </span>
              </div>
            {/if}
          </div>
        {/each}
      </div>
    {/if}
  </ScrollArea>
</div>
