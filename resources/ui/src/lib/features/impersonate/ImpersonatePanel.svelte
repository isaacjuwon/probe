<script lang="ts">
  import type { TabsState } from '$lib/features/tabs/tabs.state.svelte';
  import type { Services } from '$lib/services/context';
  import type { AppUser } from '$lib/api/users.client';
  import { ScrollArea } from '$lib/components/ui/scroll-area';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import {
    Users,
    ShieldCheck,
    Search,
    UserCheck,
    RotateCcw,
    Sparkles,
    UserX,
  } from '@lucide/svelte';

  export interface Props {
    readonly tabsState: TabsState;
    readonly services: Services;
  }

  const { tabsState, services }: Props = $props();

  let users = $state<AppUser[]>([]);
  let isLoading = $state(false);
  let searchQuery = $state('');

  // Fallback demo users if endpoint list is empty
  const defaultFallbackUsers: AppUser[] = [
    { id: '1', name: 'Alice Admin', email: 'admin@probe.test' },
    { id: '2', name: 'Bob Manager', email: 'manager@probe.test' },
    { id: '3', name: 'Charlie Customer', email: 'customer@probe.test' },
    { id: '4', name: 'Diana Developer', email: 'dev@probe.test' },
  ];

  async function loadUsers(): Promise<void> {
    isLoading = true;
    try {
      const result = await services.users.list();
      if (Array.isArray(result) && result.length > 0) {
        users = result;
      } else {
        users = defaultFallbackUsers;
      }
    } catch {
      users = defaultFallbackUsers;
    } finally {
      isLoading = false;
    }
  }

  $effect(() => {
    void loadUsers();
  });

  const activeUserId = $derived.by(() => {
    const tab = tabsState.active;
    if (!tab) return null;
    if (tab.authType === 'sanctum_act_as' && tab.authPayload && 'user_id' in tab.authPayload) {
      return String(tab.authPayload.user_id);
    }
    return null;
  });

  const activeImpersonatedUser = $derived.by(() => {
    if (!activeUserId) return null;
    return users.find((u) => String(u.id) === activeUserId) ?? null;
  });

  const filteredUsers = $derived.by(() => {
    const q = searchQuery.toLowerCase().trim();
    if (!q) return users;
    return users.filter(
      (u) =>
        u.name.toLowerCase().includes(q) ||
        (u.email && u.email.toLowerCase().includes(q))
    );
  });

  function impersonateUser(user: AppUser): void {
    if (!tabsState.active) return;
    tabsState.active.authType = 'sanctum_act_as';
    tabsState.active.authPayload = {
      user_id: user.id,
    };
    tabsState.active.isDirty = true;
  }

  function resetToGuest(): void {
    if (!tabsState.active) return;
    tabsState.active.authType = 'none';
    tabsState.active.authPayload = {};
    tabsState.active.isDirty = true;
  }

  function getRoleBadge(user: AppUser): { label: string; class: string } {
    const email = user.email?.toLowerCase() ?? '';
    const name = user.name.toLowerCase();
    if (email.includes('admin') || name.includes('admin')) {
      return { label: 'Admin', class: 'bg-red-500/10 text-red-500 border-red-500/30' };
    }
    if (email.includes('manager') || name.includes('manager')) {
      return { label: 'Manager', class: 'bg-purple-500/10 text-purple-500 border-purple-500/30' };
    }
    if (email.includes('dev') || name.includes('dev')) {
      return { label: 'Developer', class: 'bg-blue-500/10 text-blue-500 border-blue-500/30' };
    }
    return { label: 'Customer', class: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30' };
  }
</script>

<div class="flex flex-col h-full bg-card/30">
  <!-- Impersonation Header -->
  <div class="p-3 border-b border-border space-y-2.5">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
        <Users class="h-3.5 w-3.5 text-primary" />
        Sanctum Impersonator
      </span>
      <Button
        variant="ghost"
        size="icon-sm"
        onclick={loadUsers}
        class="h-6 w-6 text-muted-foreground hover:text-foreground"
        title="Refresh users"
      >
        <RotateCcw class="h-3.5 w-3.5 {isLoading ? 'animate-spin' : ''}" />
      </Button>
    </div>

    <!-- Active Persona Status Card -->
    <div class="p-2.5 rounded-md border {activeImpersonatedUser
      ? 'bg-primary/10 border-primary/40 text-foreground'
      : 'bg-background/60 border-border/60 text-muted-foreground'} space-y-1">
      <div class="flex items-center justify-between">
        <span class="text-[10px] uppercase tracking-wider font-semibold">Active Persona</span>
        {#if activeImpersonatedUser}
          <Button
            variant="ghost"
            size="sm"
            onclick={resetToGuest}
            class="h-5 px-1.5 text-[10px] text-muted-foreground hover:text-destructive gap-1"
          >
            <UserX class="h-3 w-3" />
            <span>Reset</span>
          </Button>
        {/if}
      </div>

      {#if activeImpersonatedUser}
        <div class="flex items-center gap-2 pt-0.5">
          <div class="h-7 w-7 rounded-full bg-primary text-primary-foreground font-bold text-xs flex items-center justify-center shrink-0">
            {activeImpersonatedUser.name.slice(0, 2).toUpperCase()}
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-xs font-semibold text-foreground truncate">
              {activeImpersonatedUser.name}
            </div>
            <div class="text-[10px] text-muted-foreground truncate">
              {activeImpersonatedUser.email ?? 'No email'}
            </div>
          </div>
        </div>
      {:else}
        <div class="text-xs text-muted-foreground/80 flex items-center gap-1.5 pt-0.5">
          <ShieldCheck class="h-3.5 w-3.5 opacity-60" />
          <span>Guest (No Impersonation Active)</span>
        </div>
      {/if}
    </div>

    <!-- Filter input -->
    <div class="relative">
      <Search class="h-3.5 w-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
      <Input
        type="text"
        placeholder="Search users by name, role..."
        bind:value={searchQuery}
        class="h-7 text-xs pl-8 bg-background/50"
      />
    </div>
  </div>

  <!-- Users List -->
  <ScrollArea class="flex-1">
    <div class="p-2 space-y-1.5">
      {#each filteredUsers as user (user.id)}
        {@const role = getRoleBadge(user)}
        {@const isCurrent = activeUserId === String(user.id)}
        <div
          class="p-2 rounded-md border transition-all text-xs flex items-center justify-between gap-2 {isCurrent
            ? 'bg-primary/10 border-primary shadow-sm'
            : 'bg-background/70 border-border/60 hover:border-foreground/30'}"
        >
          <div class="flex items-center gap-2 min-w-0">
            <div class="h-7 w-7 rounded-full bg-muted text-muted-foreground font-semibold text-xs flex items-center justify-center shrink-0">
              {user.name.slice(0, 2).toUpperCase()}
            </div>
            <div class="min-w-0">
              <div class="font-medium text-foreground truncate flex items-center gap-1.5">
                <span>{user.name}</span>
                <span class="text-[9px] font-mono font-medium px-1 py-0.2 rounded border {role.class}">
                  {role.label}
                </span>
              </div>
              <div class="text-[10px] text-muted-foreground truncate">
                {user.email ?? `ID: ${user.id}`}
              </div>
            </div>
          </div>

          <Button
            variant={isCurrent ? 'default' : 'outline'}
            size="sm"
            onclick={() => impersonateUser(user)}
            class="h-6 px-2 text-[10px] font-medium shrink-0 gap-1"
          >
            {#if isCurrent}
              <UserCheck class="h-3 w-3" />
              <span>Active</span>
            {:else}
              <Sparkles class="h-3 w-3 text-primary" />
              <span>Act as</span>
            {/if}
          </Button>
        </div>
      {/each}
    </div>
  </ScrollArea>
</div>
