<script lang="ts">
  import type { AuthType } from '$lib/domain/endpoint';
  import {
    Lock,
    Key,
    ShieldCheck,
    User,
    Shield,
    Globe,
  } from '@lucide/svelte';

  export interface Props {
    readonly auth?: AuthType;
    readonly class?: string;
  }

  const { auth = 'none', class: className = '' }: Props = $props();

  interface BadgeConfig {
    icon: typeof Lock;
    classes: string;
    tooltip: string;
  }

  const config = $derived.by<BadgeConfig>(() => {
    switch (auth) {
      case 'sanctum_act_as':
        return {
          icon: Lock,
          classes: 'bg-amber-500/10 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-600/30 dark:border-amber-500/30',
          tooltip: 'Requires Sanctum Authentication (Act as User)',
        };
      case 'api_key':
        return {
          icon: Key,
          classes: 'bg-purple-500/10 dark:bg-purple-500/15 text-purple-700 dark:text-purple-400 border-purple-600/30 dark:border-purple-500/30',
          tooltip: 'Requires API Key (Header: X-API-Key)',
        };
      case 'bearer':
        return {
          icon: ShieldCheck,
          classes: 'bg-blue-500/10 dark:bg-blue-500/15 text-blue-700 dark:text-blue-400 border-blue-600/30 dark:border-blue-500/30',
          tooltip: 'Requires Bearer Token',
        };
      case 'basic':
        return {
          icon: User,
          classes: 'bg-indigo-500/10 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-400 border-indigo-600/30 dark:border-indigo-500/30',
          tooltip: 'Requires HTTP Basic Authentication',
        };
      case 'session':
        return {
          icon: Shield,
          classes: 'bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-emerald-600/30 dark:border-emerald-500/30',
          tooltip: 'Requires Web Session Cookie',
        };
      case 'none':
      default:
        return {
          icon: Globe,
          classes: 'bg-muted/50 text-muted-foreground border-border/40',
          tooltip: 'Public Endpoint (No Authentication Required)',
        };
    }
  });

  const IconComponent = $derived(config.icon);
</script>

{#if auth && auth !== 'none'}
  <span
    class="inline-flex items-center justify-center rounded p-1 border select-none shrink-0 transition-colors {config.classes} {className}"
    title={config.tooltip}
    aria-label={config.tooltip}
  >
    <IconComponent class="h-2.5 w-2.5 shrink-0" />
  </span>
{/if}
