<script lang="ts">
  import { onMount } from 'svelte';
  import { ModeWatcher, toggleMode } from 'mode-watcher';
  import { provideServices } from '$lib/services/context';
  import { TabsState } from '$lib/features/tabs/tabs.state.svelte';
  import { EndpointsState } from '$lib/features/endpoints/endpoints.state.svelte';
  import { RequestState } from '$lib/features/request/request.state.svelte';
  import TabBar from '$lib/features/tabs/TabBar.svelte';
  import EndpointTree from '$lib/features/endpoints/EndpointTree.svelte';
  import RequestPanel from '$lib/features/request/RequestPanel.svelte';
  import ResponseViewer from '$lib/features/response/ResponseViewer.svelte';
  import CommandPalette from '$lib/features/palette/CommandPalette.svelte';
  import { Button } from '$lib/components/ui/button';
  import { Badge } from '$lib/components/ui/badge';
  import { Sun, Moon, Search, PanelLeft, Radio } from '@lucide/svelte';

  const services = provideServices();
  const tabsState = new TabsState(services.tabRepository);
  const endpointsState = new EndpointsState(services.endpoints);
  const requestState = new RequestState(services.relay);

  let commandPaletteOpen = $state(false);
  let sidebarCollapsed = $state(false);

  const config = services.api.getConfig();

  function triggerSend(): void {
    if (tabsState.active) {
      void requestState.send({ tab: tabsState.active });
    }
  }

  function saveCurrentRequest(): void {
    if (!tabsState.active) return;
    const tab = tabsState.active;
    void services.storage.save({
      id: tab.id,
      data: {
        title: tab.title,
        method: tab.method,
        url: tab.url,
        headers: tab.headers,
        query: tab.query,
        body: tab.body,
        auth_type: tab.authType,
        auth_payload: tab.authPayload,
      },
    });
    tab.isDirty = false;
  }

  onMount(() => {
    void tabsState.load();
    void endpointsState.load();

    services.commands.register({
      command: {
        id: 'send-request',
        title: 'Send Request',
        shortcut: '⌘Enter',
        run: triggerSend,
      },
    });

    services.commands.register({
      command: {
        id: 'new-tab',
        title: 'New Request Tab',
        shortcut: '⌘T',
        run: () => {
          tabsState.createDefaultTab();
        },
      },
    });

    services.commands.register({
      command: {
        id: 'close-tab',
        title: 'Close Tab',
        shortcut: '⌘W',
        run: () => {
          if (tabsState.activeId) {
            tabsState.close({ id: tabsState.activeId });
          }
        },
      },
    });

    services.commands.register({
      command: {
        id: 'refresh-endpoints',
        title: 'Refresh Endpoints',
        run: () => endpointsState.load(),
      },
    });

    services.commands.register({
      command: {
        id: 'toggle-theme',
        title: 'Toggle Dark / Light Theme',
        run: toggleMode,
      },
    });

    function handleGlobalShortcuts(e: KeyboardEvent): void {
      if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
        e.preventDefault();
        triggerSend();
      }
    }

    window.addEventListener('keydown', handleGlobalShortcuts);
    return () => window.removeEventListener('keydown', handleGlobalShortcuts);
  });
</script>

<ModeWatcher defaultMode="dark" />

<div class="flex flex-col h-screen w-screen overflow-hidden bg-background text-foreground font-sans">
  <!-- Top App Navigation Bar -->
  <header class="h-12 border-b border-border bg-card/80 px-4 flex items-center justify-between shrink-0 select-none">
    <div class="flex items-center gap-3">
      <Button
        variant="ghost"
        size="icon-sm"
        onclick={() => (sidebarCollapsed = !sidebarCollapsed)}
        class="h-8 w-8 text-muted-foreground hover:text-foreground"
        title="Toggle sidebar"
      >
        <PanelLeft class="h-4 w-4" />
      </Button>

      <div class="flex items-center gap-2">
        <Radio class="h-4 w-4 text-primary animate-pulse" />
        <span class="font-black tracking-widest text-sm text-foreground">PROBE</span>
      </div>

      <Badge variant="outline" class="text-[10px] uppercase font-mono px-1.5 py-0 border-primary/40 text-primary">
        {config.environment}
      </Badge>
    </div>

    <!-- Quick action search / command trigger -->
    <div class="flex items-center gap-2">
      <Button
        variant="outline"
        size="sm"
        onclick={() => (commandPaletteOpen = true)}
        class="h-8 text-xs font-normal text-muted-foreground gap-2 w-56 justify-between bg-background/50 hover:bg-background"
      >
        <span class="flex items-center gap-1.5">
          <Search class="h-3.5 w-3.5" />
          <span>Quick search...</span>
        </span>
        <kbd class="pointer-events-none inline-flex h-4 select-none items-center gap-0.5 rounded border border-border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground">
          ⌘K
        </kbd>
      </Button>

      <Button
        variant="ghost"
        size="icon-sm"
        onclick={toggleMode}
        class="h-8 w-8 text-muted-foreground hover:text-foreground"
        title="Toggle Theme"
      >
        <Sun class="h-4 w-4 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
        <Moon class="absolute h-4 w-4 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
        <span class="sr-only">Toggle theme</span>
      </Button>
    </div>
  </header>

  <!-- Main Split Layout -->
  <div class="flex-1 flex overflow-hidden">
    <!-- Sidebar: Endpoints -->
    {#if !sidebarCollapsed}
      <aside class="w-72 shrink-0 h-full">
        <EndpointTree {endpointsState} {tabsState} />
      </aside>
    {/if}

    <!-- Center Workspace -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-background">
      <TabBar {tabsState} />

      {#if tabsState.active}
        <div class="flex-1 flex min-h-0 overflow-hidden divide-x divide-border">
          <div class="w-1/2 h-full overflow-hidden">
            <RequestPanel
              tab={tabsState.active}
              {requestState}
              onSend={triggerSend}
              onSave={saveCurrentRequest}
            />
          </div>

          <div class="w-1/2 h-full overflow-hidden">
            <ResponseViewer tab={tabsState.active} {requestState} />
          </div>
        </div>
      {:else}
        <div class="flex-1 flex items-center justify-center p-8 text-muted-foreground text-sm">
          No request open. Click a route on the left or press '+' to open a tab.
        </div>
      {/if}
    </main>
  </div>
</div>

<CommandPalette bind:open={commandPaletteOpen} commandRegistry={services.commands} />
