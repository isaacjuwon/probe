<script lang="ts">
  import type { HttpMethod } from '$lib/domain/endpoint';
  import { Badge } from '$lib/components/ui/badge';
  import { cn } from '$lib/utils';

  export interface Props {
    readonly method: HttpMethod | string;
    readonly class?: string;
  }

  const { method, class: className }: Props = $props();

  const colorClasses = $derived(() => {
    switch (method.toUpperCase()) {
      case 'GET':
        return 'bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-emerald-600/30 dark:border-emerald-500/30';
      case 'POST':
        return 'bg-amber-500/10 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-600/30 dark:border-amber-500/30';
      case 'PUT':
      case 'PATCH':
        return 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-700 dark:text-sky-400 border-sky-600/30 dark:border-sky-500/30';
      case 'DELETE':
        return 'bg-rose-500/10 dark:bg-rose-500/15 text-rose-700 dark:text-rose-400 border-rose-600/30 dark:border-rose-500/30';
      default:
        return 'bg-muted text-muted-foreground border-border';
    }
  });
</script>

<Badge
  variant="outline"
  class={cn('font-mono font-bold tracking-wider text-[10px] px-1.5 py-0.5 border', colorClasses(), className)}
>
  {method.toUpperCase()}
</Badge>
