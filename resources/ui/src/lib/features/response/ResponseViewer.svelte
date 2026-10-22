<script lang="ts">
  import type { RequestTab } from '$lib/domain/request-tab';
  import type { RequestState } from '$lib/features/request/request.state.svelte';
  import StatusPill from '$lib/components/StatusPill.svelte';
  import EmptyState from '$lib/components/EmptyState.svelte';
  import CodeEditor from '$lib/editors/CodeEditor.svelte';
  import { Tabs, TabsList, TabsTrigger, TabsContent } from '$lib/components/ui/tabs';
  import { Button } from '$lib/components/ui/button';
  import { Skeleton } from '$lib/components/ui/skeleton';
  import { Copy, Check, Database, Bug, AlertTriangle } from '@lucide/svelte';

  export interface Props {
    readonly tab: RequestTab;
    readonly requestState: RequestState;
  }

  const { tab, requestState }: Props = $props();

  let copied = $state(false);

  const formattedBody = $derived(() => {
    if (!tab.lastResult || tab.lastResult.body === null || tab.lastResult.body === undefined) {
      return '';
    }
    if (typeof tab.lastResult.body === 'object') {
      return JSON.stringify(tab.lastResult.body, null, 2);
    }
    return String(tab.lastResult.body);
  });

  async function copyResponse(): Promise<void> {
    const text = formattedBody();
    if (!text) return;
    await navigator.clipboard.writeText(text);
    copied = true;
    setTimeout(() => {
      copied = false;
    }, 2000);
  }
</script>

<div class="flex flex-col h-full bg-background overflow-hidden">
  {#if requestState.status === 'sending'}
    <div class="p-6 space-y-4">
      <div class="flex items-center gap-3">
        <Skeleton class="h-6 w-20" />
        <Skeleton class="h-6 w-16" />
      </div>
      <Skeleton class="h-8 w-full max-w-sm" />
      <Skeleton class="h-48 w-full" />
    </div>
  {:else if !tab.lastResult}
    {#if requestState.error}
      <div class="p-6">
        <div class="p-4 rounded-md border border-destructive/30 bg-destructive/10 text-destructive text-sm flex items-start gap-3">
          <AlertTriangle class="h-5 w-5 shrink-0 mt-0.5" />
          <div>
            <h4 class="font-semibold">Relay Error</h4>
            <p class="mt-1 text-xs">{requestState.error}</p>
          </div>
        </div>
      </div>
    {:else}
      <EmptyState
        title="Ready to Send"
        description="Click 'Send' or press Cmd/Ctrl+Enter to relay this request through your Laravel application stack."
      />
    {/if}
  {:else}
    {@const result = tab.lastResult}
    <!-- Status header -->
    <div class="p-3 border-b border-border flex items-center justify-between bg-card/20">
      <div class="flex items-center gap-3">
        <StatusPill status={result.status} durationMs={result.durationMs} />
      </div>

      <Button
        variant="ghost"
        size="sm"
        onclick={copyResponse}
        class="h-7 text-xs gap-1.5 text-muted-foreground hover:text-foreground"
      >
        {#if copied}
          <Check class="h-3.5 w-3.5 text-emerald-400" />
          <span>Copied</span>
        {:else}
          <Copy class="h-3.5 w-3.5" />
          <span>Copy</span>
        {/if}
      </Button>
    </div>

    <!-- Response details -->
    <div class="flex-1 flex flex-col min-h-0">
      <Tabs value="body" class="flex-1 flex flex-col min-h-0">
        <div class="px-3 border-b border-border bg-card/30">
          <TabsList class="h-9 bg-transparent p-0 gap-4">
            <TabsTrigger
              value="body"
              class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs"
            >
              Response Body
            </TabsTrigger>
            <TabsTrigger
              value="headers"
              class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs"
            >
              Headers ({Object.keys(result.headers).length})
            </TabsTrigger>
            {#if result.queries.length > 0}
              <TabsTrigger
                value="queries"
                class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs gap-1.5"
              >
                <Database class="h-3 w-3" />
                Queries ({result.queries.length})
              </TabsTrigger>
            {/if}
            {#if result.dumps.length > 0}
              <TabsTrigger
                value="dumps"
                class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs gap-1.5"
              >
                <Bug class="h-3 w-3" />
                Dumps ({result.dumps.length})
              </TabsTrigger>
            {/if}
          </TabsList>
        </div>

        <div class="flex-1 overflow-y-auto p-4 min-h-0">
          <TabsContent value="body" class="m-0 h-full">
            <CodeEditor
              value={formattedBody()}
              readonly={true}
              language="json"
            />
          </TabsContent>

          <TabsContent value="headers" class="m-0 space-y-1">
            <div class="rounded-md border border-border overflow-hidden">
              <table class="w-full text-xs font-mono">
                <tbody>
                  {#each Object.entries(result.headers) as [key, val]}
                    <tr class="border-b border-border/50 last:border-0 hover:bg-card/40">
                      <td class="px-3 py-1.5 text-muted-foreground font-semibold select-all w-1/3">{key}</td>
                      <td class="px-3 py-1.5 text-foreground select-all break-all">
                        {Array.isArray(val) ? val.join(', ') : val}
                      </td>
                    </tr>
                  {/each}
                </tbody>
              </table>
            </div>
          </TabsContent>

          {#if result.queries.length > 0}
            <TabsContent value="queries" class="m-0 space-y-2">
              {#each result.queries as query}
                <div class="p-2.5 rounded-md border border-border bg-card/40 text-xs font-mono space-y-1">
                  <div class="flex items-center justify-between text-muted-foreground text-[11px]">
                    <span>{query.connection ?? 'default'}</span>
                    {#if query.time}
                      <span>{query.time} ms</span>
                    {/if}
                  </div>
                  <div class="text-foreground select-all break-all">{query.sql}</div>
                </div>
              {/each}
            </TabsContent>
          {/if}

          {#if result.dumps.length > 0}
            <TabsContent value="dumps" class="m-0 space-y-2">
              {#each result.dumps as dump}
                <div class="p-3 rounded-md border border-border bg-card/60 overflow-x-auto text-xs font-mono">
                  {@html dump}
                </div>
              {/each}
            </TabsContent>
          {/if}
        </div>
      </Tabs>
    </div>
  {/if}
</div>
