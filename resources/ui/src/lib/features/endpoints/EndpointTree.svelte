<script lang="ts">
  import type { Endpoint } from '$lib/domain/endpoint';
  import type { EndpointsState } from '$lib/features/endpoints/endpoints.state.svelte';
  import type { TabsState } from '$lib/features/tabs/tabs.state.svelte';
  import MethodBadge from '$lib/components/MethodBadge.svelte';
  import { Input } from '$lib/components/ui/input';
  import { Button } from '$lib/components/ui/button';
  import { ScrollArea } from '$lib/components/ui/scroll-area';
  import {
    Search,
    RotateCw,
    Folder,
    FolderOpen,
    ChevronDown,
    ChevronRight,
    ChevronsUpDown,
    Layers,
    Cpu,
  } from '@lucide/svelte';
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

    <select
      aria-label="Filter API scope"
      value={endpointsState.apiScope}
      onchange={(e: Event) => endpointsState.setApiScope((e.target as HTMLSelectElement).value)}
      class="w-full h-7 px-2 rounded border border-border bg-background/60 text-[11px] font-medium text-foreground focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer"
    >
      {#each endpointsState.availableScopes as scope}
        <option value={scope.id}>{scope.label}</option>
      {/each}
    </select>

    <div class="flex items-center gap-1 p-0.5 rounded-md bg-muted/60 border border-border/60">
      <button
        type="button"
        onclick={() => endpointsState.setGroupBy('resource')}
        class="flex-1 py-1 px-2 text-[10px] font-semibold rounded transition-colors flex items-center justify-center gap-1 {endpointsState.groupBy === 'resource'
          ? 'bg-background text-foreground shadow-xs'
          : 'text-muted-foreground hover:text-foreground'}"
      >
        <Layers class="h-3 w-3" />
        <span>Resource</span>
      </button>
      <button
        type="button"
        onclick={() => endpointsState.setGroupBy('controller')}
        class="flex-1 py-1 px-2 text-[10px] font-semibold rounded transition-colors flex items-center justify-center gap-1 {endpointsState.groupBy === 'controller'
          ? 'bg-background text-foreground shadow-xs'
          : 'text-muted-foreground hover:text-foreground'}"
      >
        <Cpu class="h-3 w-3" />
        <span>Controller</span>
      </button>
    </div>

    <div class="flex items-center gap-1.5">
      <div class="relative flex-1">
        <Search class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
        <Input
          type="search"
          placeholder="Filter endpoints..."
          value={endpointsState.searchQuery}
          oninput={(e: Event) => endpointsState.setSearchQuery((e.target as HTMLInputElement).value)}
          class="pl-8 h-8 text-xs bg-background/50"
        />
      </div>
      <Button
        variant="ghost"
        size="icon-sm"
        onclick={() => {
          if (endpointsState.collapsedGroups.length > 0) {
            endpointsState.expandAll();
          } else {
            endpointsState.collapseAll();
          }
        }}
        title={endpointsState.collapsedGroups.length > 0 ? 'Expand all folders' : 'Collapse all folders'}
        class="h-8 w-8 text-muted-foreground hover:text-foreground shrink-0"
      >
        <ChevronsUpDown class="h-3.5 w-3.5" />
      </Button>
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
      <div class="space-y-1.5">
        {#each endpointsState.groups as group}
          {@const groupEndpoints = endpointsState.getEndpointsForGroup(group)}
          {#if groupEndpoints.length > 0}
            {@const isCollapsed = endpointsState.isGroupCollapsed(group)}
            <div class="space-y-0.5">
              <button
                type="button"
                onclick={() => endpointsState.toggleGroup(group)}
                class="w-full flex items-center justify-between px-2 py-1.5 rounded-md hover:bg-muted/40 transition-colors text-left group"
              >
                <div class="flex items-center gap-1.5 min-w-0">
                  {#if isCollapsed}
                    <ChevronRight class="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                    <Folder class="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                  {:else}
                    <ChevronDown class="h-3.5 w-3.5 text-primary shrink-0" />
                    <FolderOpen class="h-3.5 w-3.5 text-primary shrink-0" />
                  {/if}
                  <span class="text-xs font-semibold text-foreground/90 truncate group-hover:text-foreground">
                    {group}
                  </span>
                </div>
                <span
                  class="text-[10px] font-mono font-medium px-1.5 py-0.5 rounded bg-muted/80 text-muted-foreground shrink-0"
                >
                  {groupEndpoints.length}
                </span>
              </button>

              {#if !isCollapsed}
                <div class="pl-3.5 space-y-0.5 border-l border-border/40 ml-3.5 py-0.5">
                  {#each groupEndpoints as endpoint}
                    <button
                      type="button"
                      onclick={() => openEndpointInTab(endpoint)}
                      class="w-full flex items-center gap-2 px-2 py-1.5 rounded text-left hover:bg-accent/60 transition-colors group text-xs"
                      title={endpoint.name ? `${endpoint.name} (${endpoint.action ?? ''})` : endpoint.action ?? ''}
                    >
                      <MethodBadge method={endpoint.method} />
                      <span class="truncate font-mono text-[11px] text-foreground/90 group-hover:text-foreground">
                        {endpoint.uri}
                      </span>
                    </button>
                  {/each}
                </div>
              {/if}
            </div>
          {/if}
        {/each}
      </div>
    {/if}
  </ScrollArea>
</div>
