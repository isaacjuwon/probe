<script lang="ts">
  import type { ProbeConfig } from '$lib/api/probe.client';
  import { ScrollArea } from '$lib/components/ui/scroll-area';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import {
    Sliders,
    Globe,
    Server,
    Shield,
    Plus,
    Trash2,
    Check,
    Cpu,
  } from '@lucide/svelte';

  export interface Props {
    readonly config: ProbeConfig;
  }

  const { config }: Props = $props();

  interface HeaderPair {
    key: string;
    value: string;
    enabled: boolean;
  }

  const STORAGE_KEY = 'probe_global_headers_v1';

  let globalHeaders = $state<HeaderPair[]>([]);
  let newKey = $state('');
  let newValue = $state('');
  let savedFeedback = $state(false);

  if (typeof window !== 'undefined') {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (raw) {
        globalHeaders = JSON.parse(raw);
      }
    } catch {
      globalHeaders = [];
    }
  }

  function persistHeaders(): void {
    if (typeof window !== 'undefined') {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(globalHeaders));
    }
    savedFeedback = true;
    setTimeout(() => {
      savedFeedback = false;
    }, 2000);
  }

  function addHeader(): void {
    if (!newKey.trim()) return;
    globalHeaders = [
      ...globalHeaders,
      { key: newKey.trim(), value: newValue.trim(), enabled: true },
    ];
    newKey = '';
    newValue = '';
    persistHeaders();
  }

  function removeHeader(index: number): void {
    globalHeaders = globalHeaders.filter((_, i) => i !== index);
    persistHeaders();
  }

  function toggleHeader(index: number): void {
    globalHeaders[index].enabled = !globalHeaders[index].enabled;
    persistHeaders();
  }
</script>

<div class="flex flex-col h-full bg-card/30">
  <!-- Environment Info Header -->
  <div class="p-3 border-b border-border space-y-2.5">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
        <Sliders class="h-3.5 w-3.5 text-primary" />
        Environment & Globals
      </span>
      <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-primary/10 text-primary border border-primary/30 uppercase font-semibold">
        {config.environment}
      </span>
    </div>

    <!-- System info cards -->
    <div class="space-y-1.5 text-xs">
      <div class="p-2 rounded bg-background/60 border border-border/50 flex items-center justify-between">
        <span class="text-muted-foreground flex items-center gap-1.5">
          <Globe class="h-3.5 w-3.5 opacity-70" />
          <span>Base Origin</span>
        </span>
        <span class="font-mono text-[11px] text-foreground font-medium truncate max-w-[140px]">
          {typeof window !== 'undefined' ? window.location.origin : 'http://localhost'}
        </span>
      </div>

      <div class="p-2 rounded bg-background/60 border border-border/50 flex items-center justify-between">
        <span class="text-muted-foreground flex items-center gap-1.5">
          <Server class="h-3.5 w-3.5 opacity-70" />
          <span>Relay Driver</span>
        </span>
        <span class="font-mono text-[11px] text-primary font-semibold">
          in_process (Local Kernel)
        </span>
      </div>

      <div class="p-2 rounded bg-background/60 border border-border/50 flex items-center justify-between">
        <span class="text-muted-foreground flex items-center gap-1.5">
          <Shield class="h-3.5 w-3.5 opacity-70" />
          <span>Dry Run Support</span>
        </span>
        <span class="text-emerald-500 font-semibold text-[11px] flex items-center gap-1">
          <Check class="h-3 w-3" />
          <span>Active (DB Rollback)</span>
        </span>
      </div>
    </div>
  </div>

  <!-- Global Headers Section -->
  <ScrollArea class="flex-1 p-3 space-y-3">
    <div class="space-y-1.5">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          Global Request Headers
        </span>
        {#if savedFeedback}
          <span class="text-[10px] text-emerald-500 flex items-center gap-1">
            <Check class="h-3 w-3" /> Saved
          </span>
        {/if}
      </div>
      <p class="text-[11px] text-muted-foreground">
        These headers will be applied automatically across all relay requests.
      </p>
    </div>

    <!-- Add header inputs -->
    <div class="flex items-center gap-1.5 pt-1">
      <Input
        type="text"
        placeholder="Header Name"
        bind:value={newKey}
        class="h-7 text-xs bg-background/60 flex-1 font-mono text-[11px]"
      />
      <Input
        type="text"
        placeholder="Value"
        bind:value={newValue}
        class="h-7 text-xs bg-background/60 flex-1 font-mono text-[11px]"
      />
      <Button
        variant="outline"
        size="icon-sm"
        onclick={addHeader}
        class="h-7 w-7 shrink-0 text-primary hover:text-primary-foreground hover:bg-primary"
        title="Add Header"
      >
        <Plus class="h-3.5 w-3.5" />
      </Button>
    </div>

    <!-- Headers list -->
    <div class="space-y-1.5 pt-2">
      {#if globalHeaders.length === 0}
        <div class="p-4 text-center text-xs text-muted-foreground border border-dashed rounded-md">
          No global headers configured yet.
        </div>
      {:else}
        {#each globalHeaders as header, idx}
          <div class="flex items-center justify-between gap-1.5 p-1.5 rounded bg-background/80 border border-border text-xs group">
            <input
              type="checkbox"
              checked={header.enabled}
              onchange={() => toggleHeader(idx)}
              class="rounded border-border text-primary focus:ring-primary h-3.5 w-3.5"
            />
            <span class="font-mono text-[11px] font-medium text-foreground truncate flex-1 {header.enabled ? '' : 'line-through opacity-50'}">
              {header.key}:
            </span>
            <span class="font-mono text-[11px] text-muted-foreground truncate max-w-[90px] {header.enabled ? '' : 'line-through opacity-50'}">
              {header.value}
            </span>
            <button
              type="button"
              onclick={() => removeHeader(idx)}
              class="p-1 rounded text-muted-foreground hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <Trash2 class="h-3 w-3" />
            </button>
          </div>
        {/each}
      {/if}
    </div>
  </ScrollArea>
</div>
