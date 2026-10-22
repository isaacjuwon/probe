<script lang="ts">
  import type { RequestState } from '$lib/features/request/request.state.svelte';
  import type { HistoryState } from '$lib/features/history/history.state.svelte';
  import type { TabsState } from '$lib/features/tabs/tabs.state.svelte';
  import { ScrollArea } from '$lib/components/ui/scroll-area';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import {
    Activity,
    Database,
    Zap,
    AlertCircle,
    Copy,
    Check,
    Search,
    Flame,
    Clock,
  } from '@lucide/svelte';

  export interface Props {
    readonly requestState: RequestState;
    readonly historyState: HistoryState;
    readonly tabsState?: TabsState;
  }

  const { requestState, historyState, tabsState }: Props = $props();

  let searchQuery = $state('');
  let slowOnly = $state(false);
  let copiedSql = $state<string | null>(null);

  interface FlatQuery {
    readonly sql: string;
    readonly time: number;
    readonly connection: string;
    readonly timestamp: number;
  }

  // Collect all queries from latest result and recent history
  const allQueries = $derived.by<FlatQuery[]>(() => {
    const list: FlatQuery[] = [];

    // Latest active result queries
    const activeResult = tabsState?.active?.lastResult ?? requestState.activeResult;
    if (activeResult && activeResult.queries) {
      for (const q of activeResult.queries) {
        list.push({
          sql: q.sql,
          time: q.time,
          connection: q.connection,
          timestamp: Date.now(),
        });
      }
    }

    return list;
  });

  const totalQueriesCount = $derived(allQueries.length);
  const slowQueriesCount = $derived(allQueries.filter((q) => q.time >= 50).length);
  const totalExecutionTime = $derived(
    allQueries.reduce((acc, q) => acc + q.time, 0).toFixed(2)
  );

  const filteredQueries = $derived.by(() => {
    const q = searchQuery.toLowerCase().trim();
    return allQueries.filter((item) => {
      if (slowOnly && item.time < 50) return false;
      if (!q) return true;
      return item.sql.toLowerCase().includes(q) || item.connection.toLowerCase().includes(q);
    });
  });

  async function copyToClipboard(text: string): Promise<void> {
    try {
      if (navigator.clipboard) {
        await navigator.clipboard.writeText(text);
      } else {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        textarea.remove();
      }
      copiedSql = text;
      setTimeout(() => {
        copiedSql = null;
      }, 2000);
    } catch {
      // ignore
    }
  }
</script>

<div class="flex flex-col h-full bg-card/30">
  <!-- Telemetry Overview Header -->
  <div class="p-3 border-b border-border space-y-2.5">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
        <Activity class="h-3.5 w-3.5 text-primary" />
        Telemetry Profiler
      </span>
      <span class="text-[10px] font-mono text-muted-foreground">
        Live SQL Feed
      </span>
    </div>

    <!-- Quick stats grid -->
    <div class="grid grid-cols-3 gap-1.5 text-center">
      <div class="p-2 rounded-md bg-background/60 border border-border/50">
        <div class="text-[10px] text-muted-foreground uppercase tracking-wider font-semibold">Queries</div>
        <div class="text-sm font-bold font-mono text-foreground">{totalQueriesCount}</div>
      </div>
      <div class="p-2 rounded-md bg-background/60 border border-border/50">
        <div class="text-[10px] text-muted-foreground uppercase tracking-wider font-semibold">Total Time</div>
        <div class="text-sm font-bold font-mono text-foreground">{totalExecutionTime}ms</div>
      </div>
      <div class="p-2 rounded-md bg-background/60 border border-border/50">
        <div class="text-[10px] text-muted-foreground uppercase tracking-wider font-semibold">Slow (>50ms)</div>
        <div class="text-sm font-bold font-mono {slowQueriesCount > 0 ? 'text-amber-500' : 'text-emerald-500'}">
          {slowQueriesCount}
        </div>
      </div>
    </div>

    <!-- Filter row -->
    <div class="flex items-center gap-1.5">
      <div class="relative flex-1">
        <Search class="h-3.5 w-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
        <Input
          type="text"
          placeholder="Filter queries..."
          bind:value={searchQuery}
          class="h-7 text-xs pl-8 bg-background/50"
        />
      </div>
      <Button
        variant={slowOnly ? 'default' : 'outline'}
        size="sm"
        onclick={() => (slowOnly = !slowOnly)}
        class="h-7 px-2 text-[10px] font-medium gap-1 shrink-0"
      >
        <Flame class="h-3 w-3 {slowOnly ? 'text-primary-foreground' : 'text-amber-500'}" />
        <span>Slow</span>
      </Button>
    </div>
  </div>

  <!-- Queries list -->
  <ScrollArea class="flex-1">
    {#if allQueries.length === 0}
      <div class="p-6 text-center text-xs text-muted-foreground space-y-2">
        <Database class="h-8 w-8 mx-auto opacity-40 text-primary" />
        <p class="font-medium">No queries captured yet</p>
        <p class="text-[11px] text-muted-foreground/80">
          Send a request using Probe Relay to automatically capture database queries, timings, and execution profiles.
        </p>
      </div>
    {:else if filteredQueries.length === 0}
      <div class="p-6 text-center text-xs text-muted-foreground">
        No queries matching your filter.
      </div>
    {:else}
      <div class="p-2 space-y-2">
        {#each filteredQueries as query, idx}
          <div class="p-2 rounded-md bg-background/80 border border-border/70 text-xs space-y-1.5 group hover:border-primary/50 transition-colors">
            <div class="flex items-center justify-between gap-1 text-[11px]">
              <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-muted text-muted-foreground font-semibold">
                {query.connection || 'default'}
              </span>

              <div class="flex items-center gap-1.5">
                <span class="font-mono text-[10px] font-semibold px-1.5 py-0.5 rounded {query.time >= 50
                  ? 'bg-amber-500/10 text-amber-500 border border-amber-500/30'
                  : 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/30'}">
                  {query.time}ms
                </span>

                <button
                  type="button"
                  onclick={() => void copyToClipboard(query.sql)}
                  class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted opacity-0 group-hover:opacity-100 transition-opacity"
                  title="Copy SQL"
                >
                  {#if copiedSql === query.sql}
                    <Check class="h-3 w-3 text-emerald-500" />
                  {:else}
                    <Copy class="h-3 w-3" />
                  {/if}
                </button>
              </div>
            </div>

            <!-- SQL text -->
            <pre class="font-mono text-[11px] text-foreground/90 whitespace-pre-wrap break-all leading-tight bg-muted/40 p-1.5 rounded select-all">{query.sql}</pre>
          </div>
        {/each}
      </div>
    {/if}
  </ScrollArea>
</div>
