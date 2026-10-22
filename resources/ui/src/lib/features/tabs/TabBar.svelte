<script lang="ts">
  import type { TabsState } from '$lib/features/tabs/tabs.state.svelte';
  import MethodBadge from '$lib/components/MethodBadge.svelte';
  import { Button } from '$lib/components/ui/button';
  import { Plus, X } from '@lucide/svelte';
  import { cn } from '$lib/utils';

  export interface Props {
    readonly tabsState: TabsState;
  }

  const { tabsState }: Props = $props();
</script>

<div class="flex items-center border-b border-border bg-card/60 px-2 h-10 gap-1 overflow-x-auto no-scrollbar">
  <div class="flex items-center gap-1">
    {#each tabsState.tabs as tab (tab.id)}
      {@const isActive = tab.id === tabsState.activeId}
      <div
        role="button"
        tabindex="0"
        onclick={() => tabsState.setActive({ id: tab.id })}
        onkeydown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            tabsState.setActive({ id: tab.id });
          }
        }}
        class={cn(
          'group flex items-center gap-2 px-3 py-1.5 rounded-t-md text-xs font-medium cursor-pointer border-t-2 border-transparent select-none transition-colors max-w-[200px]',
          isActive
            ? 'bg-background text-foreground border-primary shadow-sm'
            : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
        )}
      >
        <MethodBadge method={tab.method} />
        <span class="truncate">{tab.title}</span>
        {#if tab.isDirty}
          <span class="h-1.5 w-1.5 rounded-full bg-primary shrink-0"></span>
        {/if}
        <button
          type="button"
          onclick={(e) => {
            e.stopPropagation();
            tabsState.close({ id: tab.id });
          }}
          class="opacity-0 group-hover:opacity-100 hover:text-destructive transition-opacity ml-1 rounded p-0.5"
          aria-label="Close tab"
        >
          <X class="h-3 w-3" />
        </button>
      </div>
    {/each}
  </div>

  <Button
    variant="ghost"
    size="icon-sm"
    onclick={() => tabsState.createDefaultTab()}
    class="h-7 w-7 text-muted-foreground hover:text-foreground shrink-0 ml-1"
    aria-label="New tab"
  >
    <Plus class="h-4 w-4" />
  </Button>
</div>
