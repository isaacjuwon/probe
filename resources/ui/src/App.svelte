<script lang="ts">
  import { onMount } from 'svelte';
  import { ModeWatcher, toggleMode } from 'mode-watcher';
  import { provideServices } from '$lib/services/context';
  import { TabsState } from '$lib/features/tabs/tabs.state.svelte';
  import { EndpointsState } from '$lib/features/endpoints/endpoints.state.svelte';
  import { RequestState } from '$lib/features/request/request.state.svelte';
  import { HistoryState } from '$lib/features/history/history.state.svelte';
  import TabBar from '$lib/features/tabs/TabBar.svelte';
  import EndpointTree from '$lib/features/endpoints/EndpointTree.svelte';
  import HistoryList from '$lib/features/history/HistoryList.svelte';
  import RequestPanel from '$lib/features/request/RequestPanel.svelte';
  import ResponseViewer from '$lib/features/response/ResponseViewer.svelte';
  import CommandPalette from '$lib/features/palette/CommandPalette.svelte';
  import { Button } from '$lib/components/ui/button';
  import { Badge } from '$lib/components/ui/badge';
  import { Sun, Moon, Search, PanelLeft, Radio, Plus, FolderTree, Clock } from '@lucide/svelte';
  import { generateId, interpolateUrl } from '$lib/utils';
  import { extractShareFromSearch } from '$lib/services/share-url.service';

  const services = provideServices();
  const tabsState = new TabsState(services.tabRepository);
  const endpointsState = new EndpointsState(services.endpoints);
  const requestState = new RequestState(services.relay);
  const historyState = new HistoryState(services.historyRepository);

  let commandPaletteOpen = $state(false);
  let sidebarCollapsed = $state(false);
  let sidebarView = $state<'routes' | 'history'>('routes');

  let sidebarWidth = $state<number>(
    typeof window !== 'undefined'
      ? parseInt(localStorage.getItem('probe_sidebar_width') ?? '280', 10)
      : 280
  );
  let requestRatio = $state<number>(
    typeof window !== 'undefined'
      ? parseFloat(localStorage.getItem('probe_request_ratio') ?? '50')
      : 50
  );

  let isResizingSidebar = $state(false);
  let isResizingPanes = $state(false);

  const config = services.api.getConfig();

  async function triggerSend(): Promise<void> {
    if (tabsState.active) {
      const tab = tabsState.active;
      const result = await requestState.send({ tab });
      if (result) {
        void historyState.record({
          id: generateId(),
          timestamp: Date.now(),
          method: tab.method,
          url: interpolateUrl(tab.url, tab.pathParams),
          status: result.status,
          durationMs: result.durationMs,
          headers: { ...tab.headers },
          query: { ...tab.query },
          pathParams: tab.pathParams ? { ...tab.pathParams } : undefined,
          body: tab.body,
          dryRun: tab.dryRun,
        });
      }
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

  function startResizeSidebar(e: PointerEvent): void {
    e.preventDefault();
    isResizingSidebar = true;
    const startX = e.clientX;
    const startW = sidebarWidth;

    function onMove(ev: PointerEvent): void {
      const newW = Math.min(500, Math.max(200, startW + (ev.clientX - startX)));
      sidebarWidth = newW;
      localStorage.setItem('probe_sidebar_width', String(newW));
    }

    function onUp(): void {
      isResizingSidebar = false;
      window.removeEventListener('pointermove', onMove);
      window.removeEventListener('pointerup', onUp);
    }

    window.addEventListener('pointermove', onMove);
    window.addEventListener('pointerup', onUp);
  }

  function startResizePanes(e: PointerEvent): void {
    e.preventDefault();
    isResizingPanes = true;
    const container = (e.currentTarget as HTMLElement).parentElement;
    if (!container) return;
    const rect = container.getBoundingClientRect();

    function onMove(ev: PointerEvent): void {
      const pct = ((ev.clientX - rect.left) / rect.width) * 100;
      const clamped = Math.min(80, Math.max(20, pct));
      requestRatio = clamped;
      localStorage.setItem('probe_request_ratio', String(clamped));
    }

    function onUp(): void {
      isResizingPanes = false;
      window.removeEventListener('pointermove', onMove);
      window.removeEventListener('pointerup', onUp);
    }

    window.addEventListener('pointermove', onMove);
    window.addEventListener('pointerup', onUp);
  }

  onMount(() => {
    void tabsState.load().then(() => {
      if (typeof window !== 'undefined') {
        const shared = extractShareFromSearch(window.location.search || window.location.hash);
        if (shared) {
          tabsState.open({
            tab: {
              id: generateId(),
              title: shared.title ?? 'Shared Request',
              method: shared.method ?? 'GET',
              url: shared.url ?? '',
              headers: shared.headers ?? {},
              query: shared.query ?? {},
              pathParams: shared.pathParams ?? {},
              body: shared.body ?? '',
              authType: shared.authType ?? 'none',
              authPayload: shared.authPayload ?? {},
              isDirty: false,
            },
          });
          const cleanUrl = window.location.href.split('?')[0].split('#')[0];
          window.history.replaceState({}, document.title, cleanUrl);
        }
      }
    });
    void endpointsState.load();
    void historyState.load();

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
        id: 'toggle-sidebar',
        title: 'Toggle Sidebar',
        shortcut: '⌘B',
        run: () => {
          sidebarCollapsed = !sidebarCollapsed;
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
      const isCmd = e.metaKey || e.ctrlKey;
      if (!isCmd) return;

      if (e.key === 'Enter') {
        e.preventDefault();
        void triggerSend();
      } else if (e.key.toLowerCase() === 'b') {
        e.preventDefault();
        sidebarCollapsed = !sidebarCollapsed;
      } else if (e.key.toLowerCase() === 't') {
        e.preventDefault();
        tabsState.createDefaultTab();
      } else if (e.key.toLowerCase() === 'w') {
        e.preventDefault();
        if (tabsState.activeId) {
          tabsState.close({ id: tabsState.activeId });
        }
      } else if (e.key >= '1' && e.key <= '9') {
        const idx = parseInt(e.key, 10) - 1;
        if (tabsState.tabs[idx]) {
          e.preventDefault();
          tabsState.setActive({ id: tabsState.tabs[idx].id });
        }
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
        title="Toggle sidebar (⌘B)"
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
    <!-- Sidebar: Routes & History -->
    {#if !sidebarCollapsed}
      <aside style="width: {sidebarWidth}px;" class="shrink-0 h-full flex flex-col border-r border-border bg-card/20 select-none">
        <!-- Sidebar Navigation switcher (Routes vs History) -->
        <div class="p-2 border-b border-border flex items-center gap-1 bg-card/40">
          <button
            type="button"
            onclick={() => (sidebarView = 'routes')}
            class="flex-1 py-1 px-2 rounded text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors {sidebarView === 'routes'
              ? 'bg-primary text-primary-foreground shadow-sm'
              : 'text-muted-foreground hover:text-foreground'}"
          >
            <FolderTree class="h-3.5 w-3.5" />
            <span>Routes</span>
          </button>
          <button
            type="button"
            onclick={() => (sidebarView = 'history')}
            class="flex-1 py-1 px-2 rounded text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors {sidebarView === 'history'
              ? 'bg-primary text-primary-foreground shadow-sm'
              : 'text-muted-foreground hover:text-foreground'}"
          >
            <Clock class="h-3.5 w-3.5" />
            <span>History ({historyState.items.length})</span>
          </button>
        </div>

        <div class="flex-1 min-h-0 overflow-hidden">
          {#if sidebarView === 'routes'}
            <EndpointTree {endpointsState} {tabsState} />
          {:else}
            <HistoryList {historyState} {tabsState} />
          {/if}
        </div>
      </aside>

      <!-- Sidebar resize handle -->
      <div
        role="separator"
        aria-orientation="vertical"
        onpointerdown={startResizeSidebar}
        ondblclick={() => {
          sidebarWidth = 280;
          localStorage.setItem('probe_sidebar_width', '280');
        }}
        class="w-1.5 hover:w-2 -ml-1 hover:bg-primary/50 cursor-col-resize z-20 transition-all select-none {isResizingSidebar ? 'bg-primary w-2' : 'bg-transparent'}"
        title="Drag to resize sidebar (double-click to reset)"
      ></div>
    {/if}

    <!-- Center Workspace -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-background min-w-0">
      <TabBar {tabsState} />

      {#if tabsState.active}
        <div class="flex-1 flex min-h-0 overflow-hidden relative">
          <!-- Request Panel -->
          <div style="width: {requestRatio}%;" class="h-full overflow-hidden shrink-0">
            <RequestPanel
              tab={tabsState.active}
              {requestState}
              onSend={triggerSend}
              onSave={saveCurrentRequest}
            />
          </div>

          <!-- Splitter handle between Request & Response -->
          <div
            role="separator"
            aria-orientation="vertical"
            onpointerdown={startResizePanes}
            ondblclick={() => {
              requestRatio = 50;
              localStorage.setItem('probe_request_ratio', '50');
            }}
            class="w-1.5 hover:w-2.5 -ml-1 -mr-1 hover:bg-primary/50 cursor-col-resize z-20 transition-all select-none bg-border/60 {isResizingPanes ? 'bg-primary w-2.5' : ''}"
            title="Drag to adjust Request / Response ratio (double-click to reset to 50%)"
          ></div>

          <!-- Response Viewer -->
          <div style="width: {100 - requestRatio}%;" class="h-full overflow-hidden shrink-0">
            <ResponseViewer tab={tabsState.active} {requestState} />
          </div>
        </div>
      {:else}
        <div class="flex-1 flex flex-col items-center justify-center p-8 text-muted-foreground text-sm gap-3">
          <p>No request open. Click a route on the left or open a tab.</p>
          <Button
            variant="outline"
            size="sm"
            onclick={() => tabsState.createDefaultTab()}
            class="text-xs gap-1.5"
          >
            <Plus class="h-3.5 w-3.5" />
            <span>Open New Tab (⌘T)</span>
          </Button>
        </div>
      {/if}
    </main>
  </div>
</div>

<CommandPalette bind:open={commandPaletteOpen} commandRegistry={services.commands} />
