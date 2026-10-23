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
    readonly compact?: boolean;
    readonly class?: string;
  }

  const { auth = 'none', compact = false, class: className = '' }: Props = $props();

  interface BadgeConfig {
    label: string;
    icon: typeof Lock;
    classes: string;
    tooltip: string;
  }

  const config = $derived.by<BadgeConfig>(() => {
    switch (auth) {
      case 'sanctum_act_as':
        return {
          label: 'Sanctum',
          icon: Lock,
          classes: 'bg-amber-500/15 text-amber-500 border-amber-500/30',
          tooltip: 'Requires Sanctum Authentication (Act as User)',
        };
      case 'api_key':
        return {
          label: 'API Key',
          icon: Key,
          classes: 'bg-purple-500/15 text-purple-400 border-purple-500/30',
          tooltip: 'Requires API Key (Header: X-API-Key)',
        };
      case 'bearer':
        return {
          label: 'Bearer',
          icon: ShieldCheck,
          classes: 'bg-blue-500/15 text-blue-400 border-blue-500/30',
          tooltip: 'Requires Bearer Token',
        };
      case 'basic':
        return {
          label: 'Basic',
          icon: User,
          classes: 'bg-indigo-500/15 text-indigo-400 border-indigo-500/30',
          tooltip: 'Requires HTTP Basic Authentication',
        };
      case 'session':
        return {
          label: 'Session',
          icon: Shield,
          classes: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
          tooltip: 'Requires Web Session Cookie',
        };
      case 'none':
      default:
        return {
          label: 'Public',
          icon: Globe,
          classes: 'bg-muted/50 text-muted-foreground border-border/40',
          tooltip: 'Public Endpoint (No Authentication Required)',
        };
    }
  });

  const IconComponent = $derived(config.icon);
</script>

<span
  class="inline-flex items-center gap-1 rounded text-[10px] font-medium border select-none shrink-0 transition-colors {config.classes} {compact
    ? 'p-0.5'
    : 'px-1.5 py-0.2'} {className}"
  title={config.tooltip}
>
  <IconComponent class="h-2.5 w-2.5 shrink-0" />
  {#if !compact}
    <span class="leading-none">{config.label}</span>
  {/if}
</span>
