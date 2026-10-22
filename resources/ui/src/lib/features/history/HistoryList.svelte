<script lang="ts">
  import type { HistoryState } from '$lib/features/history/history.state.svelte';
  import type { TabsState } from '$lib/features/tabs/tabs.state.svelte';
  import type { HistoryItem } from '$lib/domain/history';
  import MethodBadge from '$lib/components/MethodBadge.svelte';
  import { Input } from '$lib/components/ui/input';
  import { Button } from '$lib/components/ui/button';
  import { ScrollArea } from '$lib/components/ui/scroll-area';
  import { Trash2, Clock, Search } from '@lucide/svelte';
  import { generateId } from '$lib/utils';

  export interface Props {
    readonly historyState: HistoryState;
    readonly tabsState: TabsState;
  }

  const { historyState, tabsState }: Props = $props();

  function restoreItem(item: HistoryItem): void {
    tabsState.open({
      tab: {
        id: generateId(),
        title: item.url.replace(/^https?:\/\/[^/]+/, '') || item.url,
        method: item.method,
        url: item.url,
        headers: { ...item.headers },
        query: { ...item.query },
        pathParams: item.pathParams ? { ...item.pathParams } : undefined,
        body: item.body,
        authType: 'none',
        authPayload: {},
        dryRun: item.dryRun,
        lastResult: null,
      },
    });
  }

  function formatTime(timestamp: number): string {
    const diff = Math.floor((Date.now() - timestamp) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    return `${Math.floor(diff / 3600)}h ago`;
  }
</script>

<div class="flex flex-col h-full border-r border-border bg-card/30">
  <div class="p-3 border-b border-border space-y-2">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
        <Clock class="h-3.5 w-3.5" />
        History ({historyState.items.length})
      </span>
      {#if historyState.items.length > 0}
        <Button
          variant="ghost"
          size="icon-sm"
          onclick={() => void historyState.clear()}
          class="h-6 w-6 text-muted-foreground hover:text-destructive"
          title="Clear History"
        >
          <Trash2 class="h-3.5 w-3.5" />
        </Button>
      {/if}
    </div>

    <div class="relative">
      <Search class="h-3.5 w-3.5 absolute left-2.5 top-2.5 text-muted-foreground" />
      <Input
        placeholder="Filter history..."
        value={historyState.searchQuery}
        oninput={(e) => (historyState.searchQuery = (e.target as HTMLInputElement).value)}
        class="h-8 pl-8 text-xs bg-background/50"
      />
    </div>
  </div>

  <ScrollArea class="flex-1 p-2">
    {#if historyState.filtered.length === 0}
      <div class="p-4 text-center text-xs text-muted-foreground">
        {#if historyState.items.length === 0}
          No requests sent yet. Relayed requests will appear here.
        {:else}
          No history items match your search.
        {/if}
      </div>
    {:else}
      <div class="space-y-1">
        {#each historyState.filtered as item (item.id)}
          <div
            role="button"
            tabindex="0"
            onclick={() => restoreItem(item)}
            onkeydown={(e) => e.key === 'Enter' && restoreItem(item)}
            class="p-2 rounded-md hover:bg-accent/40 cursor-pointer text-xs space-y-1 transition-colors border border-transparent hover:border-border/60"
          >
            <div class="flex items-center justify-between gap-1.5">
              <div class="flex items-center gap-1.5">
                <MethodBadge method={item.method} />
                <span
                  class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold {item.status < 300
                    ? 'text-emerald-400 bg-emerald-500/10'
                    : item.status < 400
                      ? 'text-blue-400 bg-blue-500/10'
                      : 'text-amber-400 bg-amber-500/10'}"
                >
                  {item.status}
                </span>
              </div>
              <span class="text-[10px] text-muted-foreground">{formatTime(item.timestamp)}</span>
            </div>
            <div class="font-mono text-xs text-foreground/80 truncate">{item.url}</div>
          </div>
        {/each}
      </div>
    {/if}
  </ScrollArea>
</div>
