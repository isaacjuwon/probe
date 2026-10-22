<script lang="ts">
  import type { Endpoint } from '$lib/domain/endpoint';
  import type { EndpointsState } from '$lib/features/endpoints/endpoints.state.svelte';
  import type { TabsState } from '$lib/features/tabs/tabs.state.svelte';
  import MethodBadge from '$lib/components/MethodBadge.svelte';
  import { Input } from '$lib/components/ui/input';
  import { Button } from '$lib/components/ui/button';
  import { ScrollArea } from '$lib/components/ui/scroll-area';
  import { Search, RotateCw, Folder } from '@lucide/svelte';
  import { generateId, extractPathParams } from '$lib/utils';

  export interface Props {
    readonly endpointsState: EndpointsState;
    readonly tabsState: TabsState;
  }

  const { endpointsState, tabsState }: Props = $props();

  function openEndpointInTab(endpoint: Endpoint): void {
    const bodyObj: Record<string, unknown> = {};
    if (endpoint.fields && typeof endpoint.fields === 'object') {
      for (const [name, field] of Object.entries(endpoint.fields)) {
        if (field && typeof field === 'object' && 'example' in field) {
          bodyObj[name] = field.example;
        }
      }
    }

    const plainEndpoint = $state.snapshot(endpoint);

    const pathParams: Record<string, string> = {};
    for (const param of extractPathParams(endpoint.uri)) {
      pathParams[param] = '';
    }

    tabsState.open({
      tab: {
        id: generateId(),
        title: endpoint.name || endpoint.uri,
        method: endpoint.method,
        url: (typeof window !== 'undefined' ? window.location.origin : '') + '/' + endpoint.uri.replace(/^\//, ''),
        headers: { ...endpoint.headers },
        query: {},
        pathParams,
        body: Object.keys(bodyObj).length > 0 ? JSON.stringify(bodyObj, null, 2) : '',
        authType: endpoint.auth,
        authPayload: {},
        lastResult: null,
        endpoint: plainEndpoint,
      },
    });
  }
</script>

<div class="flex flex-col h-full border-r border-border bg-card/30">
  <div class="p-3 border-b border-border space-y-2">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
        Endpoints ({endpointsState.endpoints.length})
      </span>
      <Button
        variant="ghost"
        size="icon-sm"
        onclick={() => endpointsState.load()}
        class="h-6 w-6 text-muted-foreground hover:text-foreground"
        title="Refresh endpoints"
      >
        <RotateCw class="h-3 w-3 {endpointsState.loading ? 'animate-spin' : ''}" />
      </Button>
    </div>

    <div class="relative">
      <Search class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
      <Input
        type="search"
        placeholder="Filter endpoints..."
        value={endpointsState.searchQuery}
        oninput={(e: Event) => endpointsState.setSearchQuery((e.target as HTMLInputElement).value)}
        class="pl-8 h-8 text-xs bg-background/50"
      />
    </div>
  </div>

  <ScrollArea class="flex-1 p-2">
    {#if endpointsState.loading && endpointsState.endpoints.length === 0}
      <div class="p-4 text-center text-xs text-muted-foreground">Loading endpoints...</div>
    {:else if endpointsState.error}
      <div class="p-4 text-center text-xs text-destructive">{endpointsState.error}</div>
    {:else if endpointsState.filtered.length === 0}
      <div class="p-4 text-center text-xs text-muted-foreground">No endpoints found</div>
    {:else}
      <div class="space-y-4">
        {#each endpointsState.groups as group}
          {@const groupEndpoints = endpointsState.filtered.filter((e) => (e.group ?? 'General') === group)}
          {#if groupEndpoints.length > 0}
            <div class="space-y-1">
              <div class="flex items-center gap-1.5 px-2 py-1 text-[11px] font-semibold text-muted-foreground">
                <Folder class="h-3 w-3" />
                <span>{group}</span>
              </div>
              <div class="space-y-0.5">
                {#each groupEndpoints as endpoint}
                  <button
                    type="button"
                    onclick={() => openEndpointInTab(endpoint)}
                    class="w-full flex items-center gap-2 px-2 py-1.5 rounded text-left hover:bg-accent/60 transition-colors group text-xs"
                  >
                    <MethodBadge method={endpoint.method} />
                    <span class="truncate font-mono text-[11px] text-foreground/90 group-hover:text-foreground">
                      {endpoint.uri}
                    </span>
                  </button>
                {/each}
              </div>
            </div>
          {/if}
        {/each}
      </div>
    {/if}
  </ScrollArea>
</div>
