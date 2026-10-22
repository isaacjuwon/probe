<script lang="ts">
  import { Button } from '$lib/components/ui/button';
  import { Keyboard, X } from '@lucide/svelte';

  export interface Props {
    open: boolean;
    onclose: () => void;
  }

  const { open, onclose }: Props = $props();

  const shortcuts = [
    { key: '⌘ Enter', desc: 'Send current request via Probe Relay' },
    { key: '⌘ K', desc: 'Open Quick Search / Command Palette' },
    { key: '⌘ B', desc: 'Toggle Secondary Explorer Panel' },
    { key: '⌘ T', desc: 'Open a new Request Tab' },
    { key: '⌘ W', desc: 'Close active Request Tab' },
    { key: '⌘ 1-9', desc: 'Quick jump between open Request Tabs' },
  ];
</script>

{#if open}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4 animate-in fade-in duration-150"
    role="dialog"
    aria-modal="true"
    tabindex="-1"
    onclick={(e) => {
      if (e.target === e.currentTarget) onclose();
    }}
    onkeydown={(e) => e.key === 'Escape' && onclose()}
  >
    <!-- Modal card -->
    <div
      class="bg-card border border-border rounded-xl shadow-2xl max-w-md w-full p-5 space-y-4 text-foreground relative animate-in zoom-in-95 duration-150"
    >
      <div class="flex items-center justify-between pb-2 border-b border-border">
        <div class="flex items-center gap-2 font-semibold text-sm">
          <Keyboard class="h-4 w-4 text-primary" />
          <span>Keyboard Shortcuts</span>
        </div>
        <Button
          variant="ghost"
          size="icon-sm"
          onclick={onclose}
          class="h-6 w-6 text-muted-foreground hover:text-foreground"
        >
          <X class="h-4 w-4" />
        </Button>
      </div>

      <div class="space-y-2 text-xs">
        {#each shortcuts as sc}
          <div class="flex items-center justify-between py-1.5 px-2 rounded-md hover:bg-muted/40 transition-colors">
            <span class="text-muted-foreground">{sc.desc}</span>
            <kbd class="font-mono text-[11px] font-semibold bg-muted px-2 py-0.5 rounded border border-border text-foreground shadow-2xs">
              {sc.key}
            </kbd>
          </div>
        {/each}
      </div>

      <div class="pt-2 border-t border-border flex justify-end">
        <Button variant="outline" size="sm" onclick={onclose} class="h-7 text-xs">
          Close (Esc)
        </Button>
      </div>
    </div>
  </div>
{/if}
