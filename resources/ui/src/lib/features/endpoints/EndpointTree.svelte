<script lang="ts">
  import type { Endpoint } from '$lib/domain/endpoint';
  import type { EndpointsState } from '$lib/features/endpoints/endpoints.state.svelte';
  import type { TabsState } from '$lib/features/tabs/tabs.state.svelte';
  import MethodBadge from '$lib/components/MethodBadge.svelte';
  import AuthBadge from '$lib/components/AuthBadge.svelte';
  import { Input } from '$lib/components/ui/input';
  import { Button } from '$lib/components/ui/button';
  import { ScrollArea } from '$lib/components/ui/scroll-area';
  import * as Select from '$lib/components/ui/select';
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
    GitBranch,
    Lock,
    Key,
    User,
    Globe,
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

    const initialAuthPayload: Record<string, unknown> = {};
    if (endpoint.auth === 'api_key') {
      initialAuthPayload.name = 'X-API-Key';
      initialAuthPayload.value = '';
    } else if (endpoint.auth === 'basic') {
      initialAuthPayload.username = 'admin';
      initialAuthPayload.password = '';
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
        authPayload: initialAuthPayload,
        lastResult: null,
        endpoint: plainEndpoint,
      },
    });
  }
</script>

<div class="flex flex-col h-full min-h-0 border-r border-border bg-card/30">
  <div class="p-3 border-b border-border space-y-2 shrink-0">
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

    <div class="flex items-center gap-1.5">
      <Select.Root
        type="single"
        value={endpointsState.apiScope}
        onValueChange={(val: string) => {
          if (val) endpointsState.setApiScope(val);
        }}
      >
        <Select.Trigger size="sm" class="flex-1 h-7 text-[11px] font-medium bg-background/60 border-border truncate">
          <span class="truncate">
            {endpointsState.availableScopes.find((s) => s.id === endpointsState.apiScope)?.label ?? 'All Endpoints'}
          </span>
        </Select.Trigger>
        <Select.Content class="z-50 bg-popover text-popover-foreground border border-border shadow-md">
          {#each endpointsState.availableScopes as scope (scope.id)}
            <Select.Item value={scope.id} label={scope.label}>
              <span class="text-xs">{scope.label}</span>
            </Select.Item>
          {/each}
        </Select.Content>
      </Select.Root>

      {#if endpointsState.versions.length > 0}
        <Select.Root
          type="single"
          value={endpointsState.versionFilter}
          onValueChange={(val: string) => {
            if (val) endpointsState.setVersionFilter(val);
          }}
        >
          <Select.Trigger size="sm" class="w-24 h-7 text-[11px] font-semibold text-primary font-mono bg-background/60 border-border shrink-0">
            <span class="truncate">
              {#if endpointsState.versionFilter === 'all'}
                All ({endpointsState.versionCounts.all})
              {:else if endpointsState.versionFilter === 'unversioned'}
                None ({endpointsState.versionCounts.unversioned})
              {:else}
                {endpointsState.versionFilter} ({endpointsState.versionCounts[endpointsState.versionFilter] ?? 0})
              {/if}
            </span>
          </Select.Trigger>
          <Select.Content class="z-50 bg-popover text-popover-foreground border border-border shadow-md min-w-[140px]">
            <Select.Item value="all" label="All Versions">
              <span class="text-xs">All ({endpointsState.versionCounts.all})</span>
            </Select.Item>
            {#each endpointsState.versions as v (v)}
              <Select.Item value={v} label={v}>
                <span class="font-mono text-xs">{v}</span>
                <span class="text-xs text-muted-foreground ml-auto">({endpointsState.versionCounts[v] ?? 0})</span>
              </Select.Item>
            {/each}
            {#if (endpointsState.versionCounts.unversioned ?? 0) > 0}
              <Select.Item value="unversioned" label="Unversioned">
                <span class="text-xs">None ({endpointsState.versionCounts.unversioned})</span>
              </Select.Item>
            {/if}
          </Select.Content>
        </Select.Root>
      {/if}
    </div>

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
      {#if endpointsState.versions.length > 0}
        <button
          type="button"
          onclick={() => endpointsState.setGroupBy('version')}
          class="flex-1 py-1 px-2 text-[10px] font-semibold rounded transition-colors flex items-center justify-center gap-1 {endpointsState.groupBy === 'version'
            ? 'bg-background text-foreground shadow-xs'
            : 'text-muted-foreground hover:text-foreground'}"
        >
          <GitBranch class="h-3 w-3" />
          <span>Version</span>
        </button>
      {/if}
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
          if (endpointsState.isAllCollapsed) {
            endpointsState.expandAll();
          } else {
            endpointsState.collapseAll();
          }
        }}
        title={endpointsState.isAllCollapsed ? 'Expand all folders' : 'Collapse all folders'}
        class="h-8 w-8 text-muted-foreground hover:text-foreground shrink-0"
      >
        <ChevronsUpDown class="h-3.5 w-3.5" />
      </Button>
    </div>

    <!-- Auth Type Filter Pills -->
    <div class="flex items-center gap-1 overflow-x-auto pb-0.5 text-[10px] no-scrollbar">
      <button
        type="button"
        onclick={() => endpointsState.setAuthFilter('all')}
        class="px-2 py-0.5 rounded-full border text-[10px] font-semibold shrink-0 transition-colors {endpointsState.authFilter === 'all'
          ? 'bg-primary text-primary-foreground border-primary'
          : 'border-border text-muted-foreground hover:text-foreground'}"
      >
        All ({endpointsState.authCounts.all})
      </button>
      {#if endpointsState.authCounts.sanctum > 0}
        <button
          type="button"
          onclick={() => endpointsState.setAuthFilter('sanctum')}
          title="Sanctum Auth ({endpointsState.authCounts.sanctum})"
          class="px-1.5 py-0.5 rounded-full border text-[10px] font-semibold shrink-0 transition-colors flex items-center gap-1 {endpointsState.authFilter === 'sanctum'
            ? 'bg-amber-500 text-white border-amber-500'
            : 'border-border text-amber-500/80 hover:text-amber-500'}"
        >
          <Lock class="h-3 w-3" />
          <span>{endpointsState.authCounts.sanctum}</span>
        </button>
      {/if}
      {#if endpointsState.authCounts.api_key > 0}
        <button
          type="button"
          onclick={() => endpointsState.setAuthFilter('api_key')}
          title="API Key ({endpointsState.authCounts.api_key})"
          class="px-1.5 py-0.5 rounded-full border text-[10px] font-semibold shrink-0 transition-colors flex items-center gap-1 {endpointsState.authFilter === 'api_key'
            ? 'bg-purple-500 text-white border-purple-500'
            : 'border-border text-purple-400 hover:text-purple-300'}"
        >
          <Key class="h-3 w-3" />
          <span>{endpointsState.authCounts.api_key}</span>
        </button>
      {/if}
      {#if endpointsState.authCounts.basic > 0}
        <button
          type="button"
          onclick={() => endpointsState.setAuthFilter('basic')}
          title="Basic Auth ({endpointsState.authCounts.basic})"
          class="px-1.5 py-0.5 rounded-full border text-[10px] font-semibold shrink-0 transition-colors flex items-center gap-1 {endpointsState.authFilter === 'basic'
            ? 'bg-indigo-500 text-white border-indigo-500'
            : 'border-border text-indigo-400 hover:text-indigo-300'}"
        >
          <User class="h-3 w-3" />
          <span>{endpointsState.authCounts.basic}</span>
        </button>
      {/if}
      {#if endpointsState.authCounts.public > 0}
        <button
          type="button"
          onclick={() => endpointsState.setAuthFilter('public')}
          title="Public Endpoints ({endpointsState.authCounts.public})"
          class="px-1.5 py-0.5 rounded-full border text-[10px] font-semibold shrink-0 transition-colors flex items-center gap-1 {endpointsState.authFilter === 'public'
            ? 'bg-muted-foreground text-background border-muted-foreground'
            : 'border-border text-muted-foreground hover:text-foreground'}"
        >
          <Globe class="h-3 w-3" />
          <span>{endpointsState.authCounts.public}</span>
        </button>
      {/if}
    </div>
  </div>

  <ScrollArea class="flex-1 min-h-0">
    {#if endpointsState.loading && endpointsState.endpoints.length === 0}
      <div class="p-4 text-center text-xs text-muted-foreground">Loading endpoints...</div>
    {:else if endpointsState.error}
      <div class="p-4 text-center text-xs text-destructive">{endpointsState.error}</div>
    {:else if endpointsState.filtered.length === 0}
      <div class="p-4 text-center text-xs text-muted-foreground">No endpoints found</div>
    {:else}
      <div class="p-2 space-y-1.5">
        {#each endpointsState.groups as group (group)}
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
                  {#each groupEndpoints as endpoint (`${endpoint.method}:${endpoint.uri}`)}
                    <button
                      type="button"
                      onclick={() => openEndpointInTab(endpoint)}
                      class="w-full flex items-center justify-between gap-1.5 px-2 py-1.5 rounded text-left hover:bg-accent/60 transition-colors group text-xs"
                      title={endpoint.name ? `${endpoint.name} (${endpoint.action ?? ''})` : endpoint.action ?? ''}
                    >
                      <div class="flex items-center gap-2 min-w-0 flex-1">
                        <MethodBadge method={endpoint.method} />
                        <span class="truncate font-mono text-[11px] text-foreground/90 group-hover:text-foreground">
                          {endpoint.uri}
                        </span>
                      </div>
                      <div class="flex items-center gap-1 shrink-0">
                        {#if endpoint.version && endpointsState.groupBy !== 'version'}
                          <span
                            class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold uppercase tracking-wider bg-primary/10 text-primary border border-primary/20 shrink-0"
                            title="API Version: {endpoint.version}"
                          >
                            {endpoint.version}
                          </span>
                        {/if}
                        <AuthBadge auth={endpoint.auth} />
                      </div>
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
