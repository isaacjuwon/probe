<script lang="ts">
  import {
    FolderTree,
    Clock,
    Bookmark,
    Activity,
    Users,
    Sliders,
    BookOpen,
    Keyboard,
    ChevronsRight,
    ChevronsLeft,
    Radio,
    ShieldCheck,
    Cookie,
  } from '@lucide/svelte';

  export type ActiveTool =
    | 'endpoints'
    | 'history'
    | 'collections'
    | 'telemetry'
    | 'impersonate'
    | 'cookies'
    | 'environment'
    | 'docs';

  export interface Props {
    activeTool: ActiveTool;
    onselectTool: (tool: ActiveTool) => void;
    isSecondaryOpen: boolean;
    endpointsCount?: number;
    historyCount?: number;
    savedCount?: number;
    queriesCount?: number;
    cookiesCount?: number;
    hasException?: boolean;
    activePersona?: string | null;
    environment?: string;
    onopenshortcuts: () => void;
    ontoggletheme?: () => void;
  }

  const {
    activeTool,
    onselectTool,
    isSecondaryOpen,
    endpointsCount = 0,
    historyCount = 0,
    savedCount = 0,
    queriesCount = 0,
    cookiesCount = 0,
    hasException = false,
    activePersona = null,
    environment = 'local',
    onopenshortcuts,
  }: Props = $props();

  // Collapsible form by default!
  let isExpanded = $state<boolean>(
    typeof window !== 'undefined'
      ? localStorage.getItem('probe_primary_sidebar_expanded') === 'true'
      : false
  );

  function toggleExpanded(): void {
    isExpanded = !isExpanded;
    if (typeof window !== 'undefined') {
      localStorage.setItem('probe_primary_sidebar_expanded', String(isExpanded));
    }
  }

  interface NavItem {
    id: ActiveTool;
    label: string;
    icon: typeof FolderTree;
    badge?: number | string | null;
    badgeClass?: string;
    alert?: boolean;
  }

  const navItems = $derived<NavItem[]>([
    {
      id: 'endpoints',
      label: 'Endpoints',
      icon: FolderTree,
      badge: endpointsCount > 0 ? endpointsCount : null,
      badgeClass: 'bg-muted text-muted-foreground',
    },
    {
      id: 'history',
      label: 'History',
      icon: Clock,
      badge: historyCount > 0 ? historyCount : null,
      badgeClass: 'bg-primary/20 text-primary font-mono',
    },
    {
      id: 'collections',
      label: 'Collections',
      icon: Bookmark,
      badge: savedCount > 0 ? savedCount : null,
      badgeClass: 'bg-muted text-muted-foreground',
    },
    {
      id: 'telemetry',
      label: 'Telemetry',
      icon: Activity,
      badge: queriesCount > 0 ? `${queriesCount}q` : null,
      badgeClass: 'bg-emerald-500/10 text-emerald-500 font-mono',
      alert: hasException,
    },
    {
      id: 'impersonate',
      label: 'Users & Roles',
      icon: activePersona ? ShieldCheck : Users,
      badge: activePersona ? 'Act' : null,
      badgeClass: 'bg-primary text-primary-foreground font-semibold',
    },
    {
      id: 'cookies',
      label: 'Cookies',
      icon: Cookie,
      badge: cookiesCount > 0 ? cookiesCount : null,
      badgeClass: 'bg-amber-500/15 text-amber-500 font-mono',
    },
    {
      id: 'environment',
      label: 'Environment',
      icon: Sliders,
      badge: environment,
      badgeClass: 'bg-muted text-muted-foreground uppercase text-[8px]',
    },
    {
      id: 'docs',
      label: 'Docs & Export',
      icon: BookOpen,
    },
  ]);
</script>

<aside
  class="shrink-0 h-full border-r border-border bg-card/60 flex flex-col justify-between transition-all duration-200 select-none z-20 {isExpanded
    ? 'w-48'
    : 'w-12'}"
>
  <!-- Top: Logo / App Header -->
  <div>
    <div class="h-12 border-b border-border/80 flex items-center px-2.5 overflow-hidden">
      {#if isExpanded}
        <div class="flex items-center gap-2">
          <Radio class="h-4 w-4 text-primary animate-pulse shrink-0" />
          <span class="font-black tracking-widest text-xs text-foreground truncate">PROBE</span>
          <span class="text-[9px] uppercase px-1 py-0.2 rounded bg-primary/10 text-primary border border-primary/30 font-mono">
            {environment}
          </span>
        </div>
      {:else}
        <button
          type="button"
          onclick={toggleExpanded}
          class="w-full flex items-center justify-center p-1 rounded-md text-primary hover:bg-primary/10 transition-colors"
          title="Expand Sidebar"
        >
          <Radio class="h-5 w-5 animate-pulse" />
        </button>
      {/if}
    </div>

    <!-- Nav Items list -->
    <nav class="p-1 space-y-1 mt-1">
      {#each navItems as item (item.id)}
        {@const isActive = activeTool === item.id && isSecondaryOpen}
        {@const IconComponent = item.icon}
        <button
          type="button"
          onclick={() => onselectTool(item.id)}
          title="{item.label}{item.badge ? ` (${item.badge})` : ''}"
          class="w-full relative flex items-center rounded-md text-xs font-medium transition-all group {isExpanded
            ? 'px-2.5 py-2 justify-between'
            : 'h-9 justify-center'} {isActive
            ? 'bg-primary/15 text-primary font-semibold shadow-2xs'
            : 'text-muted-foreground hover:text-foreground hover:bg-accent/50'}"
        >
          <!-- Active Indicator Pill on far left -->
          {#if isActive}
            <span class="absolute left-0 top-1.5 bottom-1.5 w-0.75 bg-primary rounded-r"></span>
          {/if}

          <!-- Icon + Optional Alert Dot -->
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="relative shrink-0">
              <IconComponent class="h-4 w-4 {isActive ? 'text-primary' : 'text-muted-foreground group-hover:text-foreground'}" />
              {#if item.alert}
                <span class="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-destructive ring-2 ring-background"></span>
              {/if}
            </div>

            {#if isExpanded}
              <span class="truncate">{item.label}</span>
            {/if}
          </div>

          <!-- Badges -->
          {#if item.badge !== undefined && item.badge !== null}
            {#if isExpanded}
              <span class="text-[9px] px-1.5 py-0.2 rounded {item.badgeClass} shrink-0">
                {item.badge}
              </span>
            {:else}
              <!-- Mini indicator dot in compact rail mode -->
              <span class="absolute bottom-1 right-1.5 h-1.5 w-1.5 rounded-full bg-primary/70"></span>
            {/if}
          {/if}
        </button>
      {/each}
    </nav>
  </div>

  <!-- Bottom Utility & Collapse Controls -->
  <div class="p-1 border-t border-border/80 space-y-1 bg-card/40">
    <!-- Shortcuts modal trigger -->
    <button
      type="button"
      onclick={onopenshortcuts}
      title="Keyboard Shortcuts (⌘/Ctrl)"
      class="w-full flex items-center rounded-md text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-accent/50 transition-colors {isExpanded
        ? 'px-2.5 py-1.5 gap-2.5'
        : 'h-8 justify-center'}"
    >
      <Keyboard class="h-4 w-4 shrink-0" />
      {#if isExpanded}
        <span class="truncate">Shortcuts</span>
      {/if}
    </button>

    <!-- GitHub Repository Link -->
    <a
      href="https://github.com/isaacjuwon/probe"
      target="_blank"
      rel="noopener noreferrer"
      title="GitHub Repository (isaacjuwon/probe)"
      class="w-full flex items-center rounded-md text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-accent/50 transition-colors {isExpanded
        ? 'px-2.5 py-1.5 gap-2.5'
        : 'h-8 justify-center'}"
    >
      <svg class="h-4 w-4 shrink-0 fill-current" viewBox="0 0 24 24" aria-hidden="true">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
      </svg>
      {#if isExpanded}
        <span class="truncate">GitHub</span>
      {/if}
    </a>

    <!-- Expand / Collapse Sidebar Toggle Button -->
    <button
      type="button"
      onclick={toggleExpanded}
      title={isExpanded ? 'Collapse to compact rail' : 'Expand sidebar'}
      class="w-full flex items-center rounded-md text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-accent/50 transition-colors {isExpanded
        ? 'px-2.5 py-1.5 justify-between'
        : 'h-8 justify-center'}"
    >
      <div class="flex items-center gap-2.5">
        {#if isExpanded}
          <ChevronsLeft class="h-4 w-4 shrink-0" />
          <span class="text-[11px] uppercase tracking-wider text-muted-foreground/80">Collapse Rail</span>
        {:else}
          <ChevronsRight class="h-4 w-4 shrink-0" />
        {/if}
      </div>
    </button>
  </div>
</aside>
