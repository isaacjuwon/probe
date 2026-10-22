<script lang="ts">
  import type { Snippet } from 'svelte';
  import { cn } from '$lib/utils';

  export interface Props {
    readonly title: string;
    readonly description?: string;
    readonly class?: string;
    readonly action?: Snippet;
  }

  const { title, description, class: className, action }: Props = $props();
</script>

<div class={cn('flex flex-col items-center justify-center p-8 text-center', className)}>
  <h3 class="text-sm font-semibold text-foreground">{title}</h3>
  {#if description}
    <p class="mt-1 text-xs text-muted-foreground max-w-sm">{description}</p>
  {/if}
  {#if action}
    <div class="mt-4">
      {@render action()}
    </div>
  {/if}
</div>
