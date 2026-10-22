<script lang="ts">
  import type { RequestState } from '$lib/features/request/request.state.svelte';
  import type { TabsState } from '$lib/features/tabs/tabs.state.svelte';
  import type { CapturedCookie } from '$lib/domain/relay';
  import { ScrollArea } from '$lib/components/ui/scroll-area';
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import {
    Cookie,
    Lock,
    Search,
    Copy,
    Check,
    Download,
    Sparkles,
    Shield,
  } from '@lucide/svelte';
  import { generateId } from '$lib/utils';

  export interface Props {
    readonly requestState: RequestState;
    readonly tabsState: TabsState;
  }

  const { requestState, tabsState }: Props = $props();

  let searchQuery = $state('');
  let copiedName = $state<string | null>(null);

  const cookies = $derived.by<CapturedCookie[]>(() => {
    const activeResult = tabsState.active?.lastResult ?? requestState.activeResult;
    return activeResult?.cookies ?? [];
  });

  const filteredCookies = $derived.by(() => {
    const q = searchQuery.toLowerCase().trim();
    if (!q) return cookies;
    return cookies.filter((c) => c.name.toLowerCase().includes(q));
  });

  async function copyCookieVal(name: string, val: unknown): Promise<void> {
    const text = typeof val === 'object' && val !== null ? JSON.stringify(val, null, 2) : String(val ?? '');
    try {
      if (navigator.clipboard) {
        await navigator.clipboard.writeText(text);
      } else {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        textarea.remove();
      }
      copiedName = name;
      setTimeout(() => (copiedName = null), 2000);
    } catch {
      // ignore
    }
  }

  function downloadCookieJson(cookie: CapturedCookie): void {
    const data = {
      name: cookie.name,
      value: cookie.decrypted_value ?? cookie.raw_value,
      isEncrypted: cookie.is_encrypted,
      attributes: {
        domain: cookie.domain,
        path: cookie.path,
        expires: cookie.expires,
        httpOnly: cookie.http_only,
        secure: cookie.secure,
        sameSite: cookie.same_site,
      },
      exportedAt: new Date().toISOString(),
    };

    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `cookie-${cookie.name}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  function openCookieTestEndpoint(): void {
    tabsState.open({
      tab: {
        id: generateId(),
        title: 'Set Encrypted Cookies',
        method: 'GET',
        url: (typeof window !== 'undefined' ? window.location.origin : '') + '/api/v1/cookies/set',
        headers: {},
        query: {},
        body: '',
        authType: 'none',
        authPayload: {},
        dryRun: false,
        lastResult: null,
      },
    });
  }
</script>

<div class="flex flex-col h-full bg-card/30">
  <!-- Header -->
  <div class="p-3 border-b border-border space-y-2.5">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
        <Cookie class="h-3.5 w-3.5 text-amber-500" />
        Cookies Inspector
      </span>
      <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-semibold flex items-center gap-1">
        <Shield class="h-3 w-3" />
        APP_KEY Decryptor
      </span>
    </div>

    <!-- Filter input -->
    <div class="relative">
      <Search class="h-3.5 w-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground" />
      <Input
        type="text"
        placeholder="Filter cookies..."
        bind:value={searchQuery}
        class="h-7 text-xs pl-8 bg-background/50 font-mono text-[11px]"
      />
    </div>
  </div>

  <!-- Content -->
  <ScrollArea class="flex-1">
    {#if cookies.length === 0}
      <div class="p-6 text-center text-xs text-muted-foreground space-y-3">
        <Cookie class="h-8 w-8 mx-auto opacity-40 text-amber-500" />
        <p class="font-medium text-foreground">No cookies in active response</p>
        <p class="text-[11px] text-muted-foreground/80 leading-relaxed">
          The current response did not return any cookies. Click below to test Probe's AES Cookie Decryption with the demo endpoint!
        </p>
        <Button
          variant="outline"
          size="sm"
          onclick={openCookieTestEndpoint}
          class="text-xs gap-1.5 font-medium border-dashed"
        >
          <Sparkles class="h-3.5 w-3.5 text-primary" />
          <span>Probe GET /api/v1/cookies/set</span>
        </Button>
      </div>
    {:else}
      <div class="p-2 space-y-2">
        <div class="text-[11px] text-muted-foreground px-1 flex items-center justify-between">
          <span>Active response cookies ({filteredCookies.length}):</span>
        </div>

        {#each filteredCookies as cookie (cookie.name)}
          <div class="p-2.5 rounded-md border border-border/70 bg-background/70 space-y-2 text-xs hover:border-foreground/30 transition-colors">
            <div class="flex items-center justify-between gap-1">
              <div class="flex items-center gap-1.5 min-w-0">
                <span class="font-mono font-semibold text-primary truncate max-w-[130px]">{cookie.name}</span>
                {#if cookie.is_encrypted}
                  <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex items-center gap-0.5 shrink-0">
                    <Lock class="h-2.5 w-2.5" />
                    Decrypted
                  </span>
                {:else}
                  <span class="px-1 py-0.2 rounded text-[9px] bg-muted text-muted-foreground shrink-0">
                    Plain
                  </span>
                {/if}
              </div>

              <div class="flex items-center gap-1 shrink-0">
                <Button
                  variant="ghost"
                  size="icon-sm"
                  onclick={() => void copyCookieVal(cookie.name, cookie.decrypted_value ?? cookie.raw_value)}
                  class="h-6 w-6 text-muted-foreground hover:text-foreground"
                  title="Copy decrypted value"
                >
                  {#if copiedName === cookie.name}
                    <Check class="h-3 w-3 text-emerald-400" />
                  {:else}
                    <Copy class="h-3 w-3" />
                  {/if}
                </Button>

                <Button
                  variant="ghost"
                  size="icon-sm"
                  onclick={() => downloadCookieJson(cookie)}
                  class="h-6 w-6 text-muted-foreground hover:text-foreground"
                  title="Download cookie JSON"
                >
                  <Download class="h-3 w-3" />
                </Button>
              </div>
            </div>

            <!-- Value preview -->
            <div class="p-1.5 rounded bg-muted/50 border border-border/50 font-mono text-[11px] max-h-36 overflow-y-auto select-all">
              {#if typeof cookie.decrypted_value === 'object' && cookie.decrypted_value !== null}
                <pre class="text-foreground leading-tight">{JSON.stringify(cookie.decrypted_value, null, 2)}</pre>
              {:else}
                <span class="text-foreground break-all">{cookie.decrypted_value ?? '(empty)'}</span>
              {/if}
            </div>

            <!-- Attributes -->
            <div class="flex items-center gap-2 text-[10px] text-muted-foreground flex-wrap pt-1 border-t border-border/30">
              <span>Path: <strong class="text-foreground font-mono">{cookie.path ?? '/'}</strong></span>
              {#if cookie.http_only}
                <span class="text-emerald-400 font-medium">HttpOnly</span>
              {/if}
              {#if cookie.secure}
                <span class="text-cyan-400 font-medium">Secure</span>
              {/if}
              {#if cookie.same_site}
                <span>SameSite: <strong class="text-foreground font-mono">{cookie.same_site}</strong></span>
              {/if}
            </div>
          </div>
        {/each}
      </div>
    {/if}
  </ScrollArea>
</div>
