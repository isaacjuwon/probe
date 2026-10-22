<script lang="ts">
  import { Badge } from '$lib/components/ui/badge';
  import { cn } from '$lib/utils';

  export interface Props {
    readonly status: number;
    readonly durationMs?: number;
    readonly class?: string;
  }

  const { status, durationMs, class: className }: Props = $props();

  const statusColor = $derived(() => {
    if (status >= 200 && status < 300) {
      return 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30';
    }
    if (status >= 300 && status < 400) {
      return 'bg-sky-500/15 text-sky-400 border-sky-500/30';
    }
    if (status >= 400 && status < 500) {
      return 'bg-amber-500/15 text-amber-400 border-amber-500/30';
    }
    return 'bg-rose-500/15 text-rose-400 border-rose-500/30';
  });
</script>

<div class={cn('inline-flex items-center gap-2', className)}>
  <Badge variant="outline" class={cn('font-mono font-bold text-xs px-2 py-0.5 border', statusColor())}>
    {status}
  </Badge>

  {#if durationMs !== undefined}
    <span class="text-xs font-mono text-muted-foreground">
      {durationMs.toFixed(0)} ms
    </span>
  {/if}
</div>
