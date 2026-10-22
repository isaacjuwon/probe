<script lang="ts">
  import type { RequestTab } from '$lib/domain/request-tab';
  import type { RequestState } from '$lib/features/request/request.state.svelte';
  import StatusPill from '$lib/components/StatusPill.svelte';
  import EmptyState from '$lib/components/EmptyState.svelte';
  import CodeEditor from '$lib/editors/CodeEditor.svelte';
  import { Tabs, TabsList, TabsTrigger, TabsContent } from '$lib/components/ui/tabs';
  import { Button } from '$lib/components/ui/button';
  import { Skeleton } from '$lib/components/ui/skeleton';
  import {
    Copy,
    Check,
    Database,
    Bug,
    AlertTriangle,
    Sparkles,
    ShieldAlert,
    Radio,
    Cookie,
    ChevronLeft,
    ChevronRight,
    Lock,
    Download,
  } from '@lucide/svelte';
  import { generatePestTest } from '$lib/services/test-generator';
  import { copyToClipboard } from '$lib/utils';

  export interface Props {
    readonly tab: RequestTab;
    readonly requestState: RequestState;
  }

  const { tab, requestState }: Props = $props();

  let copiedResponse = $state(false);
  let downloadedResponse = $state(false);
  let copiedPest = $state(false);
  let currentDumpIndex = $state(0);
  let copiedCookieKey = $state<string | null>(null);
  let copiedDump = $state(false);

  $effect(() => {
    if (tab.lastResult) {
      currentDumpIndex = 0;
    }
  });

  function downloadResponse(): void {
    if (!tab.lastResult) return;
    const body = formattedBody();
    if (!body) return;

    const rawHeaders = tab.lastResult.headers;
    const contentType = (rawHeaders['content-type'] ?? rawHeaders['Content-Type'] ?? 'application/json') as string;
    let ext = 'json';
    if (typeof contentType === 'string') {
      if (contentType.includes('html')) ext = 'html';
      else if (contentType.includes('xml')) ext = 'xml';
      else if (contentType.includes('text')) ext = 'txt';
      else if (contentType.includes('csv')) ext = 'csv';
    }

    const safeTitle = (tab.title || 'response').replace(/[^a-zA-Z0-9_-]/g, '_').toLowerCase();
    const filename = `${safeTitle}-${Date.now()}.${ext}`;

    const blob = new Blob([body], { type: typeof contentType === 'string' ? contentType : 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    downloadedResponse = true;
    setTimeout(() => {
      downloadedResponse = false;
    }, 2000);
  }

  function downloadCookie(cookie: { name: string; decrypted_value?: unknown; raw_value?: string | null }): void {
    const data = {
      name: cookie.name,
      value: cookie.decrypted_value ?? cookie.raw_value,
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

  async function copyCookie(key: string, val: unknown): Promise<void> {
    const text = typeof val === 'object' ? JSON.stringify(val, null, 2) : String(val);
    const ok = await copyToClipboard(text);
    if (ok) {
      copiedCookieKey = key;
      setTimeout(() => {
        copiedCookieKey = null;
      }, 2000);
    }
  }

  async function copyActiveDump(dumpHtml: string): Promise<void> {
    const ok = await copyToClipboard(dumpHtml);
    if (ok) {
      copiedDump = true;
      setTimeout(() => {
        copiedDump = false;
      }, 2000);
    }
  }

  const formattedBody = $derived(() => {
    if (!tab.lastResult || tab.lastResult.body === null || tab.lastResult.body === undefined) {
      return '';
    }
    if (typeof tab.lastResult.body === 'object') {
      return JSON.stringify(tab.lastResult.body, null, 2);
    }
    return String(tab.lastResult.body);
  });

  interface SseEvent {
    id?: string;
    event?: string;
    data: string;
  }

  const sseEvents = $derived((): SseEvent[] => {
    if (!tab.lastResult) return [];
    const text = typeof tab.lastResult.body === 'string' ? tab.lastResult.body : '';
    if (!text.includes('data:')) return [];

    const events: SseEvent[] = [];
    const blocks = text.split(/\r?\n\r?\n/);
    for (const block of blocks) {
      if (!block.trim()) continue;
      const current: SseEvent = { data: '' };
      for (const line of block.split(/\r?\n/)) {
        if (line.startsWith('data:')) {
          current.data += (current.data ? '\n' : '') + line.slice(5).trim();
        } else if (line.startsWith('event:')) {
          current.event = line.slice(6).trim();
        } else if (line.startsWith('id:')) {
          current.id = line.slice(3).trim();
        }
      }
      if (current.data || current.event) {
        events.push(current);
      }
    }
    return events;
  });

  const validationErrors = $derived(() => {
    if (!tab.lastResult || tab.lastResult.status !== 422 || !tab.lastResult.body || typeof tab.lastResult.body !== 'object') {
      return [];
    }
    const obj = tab.lastResult.body as { errors?: Record<string, string[]> };
    if (!obj.errors || typeof obj.errors !== 'object') return [];
    return Object.entries(obj.errors).map(([field, messages]) => ({
      field,
      messages: Array.isArray(messages) ? messages : [String(messages)],
    }));
  });

  async function copyResponse(): Promise<void> {
    const text = formattedBody();
    if (!text) return;
    const ok = await copyToClipboard(text);
    if (ok) {
      copiedResponse = true;
      setTimeout(() => {
        copiedResponse = false;
      }, 2000);
    }
  }

  async function copyPestTest(): Promise<void> {
    if (!tab.lastResult) return;
    const testCode = generatePestTest(tab, tab.lastResult);
    const ok = await copyToClipboard(testCode);
    if (ok) {
      copiedPest = true;
      setTimeout(() => {
        copiedPest = false;
      }, 2000);
    }
  }
</script>

<div class="flex flex-col h-full bg-background overflow-hidden">
  {#if requestState.status === 'sending'}
    <div class="p-6 space-y-4">
      <div class="flex items-center gap-3">
        <Skeleton class="h-6 w-20" />
        <Skeleton class="h-6 w-16" />
      </div>
      <Skeleton class="h-8 w-full max-w-sm" />
      <Skeleton class="h-48 w-full" />
    </div>
  {:else if !tab.lastResult}
    {#if requestState.error}
      <div class="p-6">
        <div
          class="p-4 rounded-md border border-destructive/30 bg-destructive/10 text-destructive text-sm flex items-start gap-3"
        >
          <AlertTriangle class="h-5 w-5 shrink-0 mt-0.5" />
          <div>
            <h4 class="font-semibold">Relay Error</h4>
            <p class="mt-1 text-xs">{requestState.error}</p>
          </div>
        </div>
      </div>
    {:else}
      <EmptyState
        title="Ready to Send"
        description="Click 'Send' or press Cmd/Ctrl+Enter to relay this request through your Laravel application stack."
      />
    {/if}
  {:else}
    {@const result = tab.lastResult}
    <!-- Status header -->
    <div class="p-3 border-b border-border flex items-center justify-between bg-card/20">
      <div class="flex items-center gap-2.5">
        <StatusPill status={result.status} durationMs={result.durationMs} />
        {#if result.dryRun}
          <span
            class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30"
            title="Request executed in an isolated database transaction and was rolled back"
          >
            DRY RUN (ROLLED BACK)
          </span>
        {/if}
      </div>

      <div class="flex items-center gap-1.5">
        <Button
          variant="ghost"
          size="sm"
          onclick={copyPestTest}
          class="h-7 text-xs gap-1.5 text-muted-foreground hover:text-foreground hover:bg-card"
          title="Generate and copy an automated Pest PHP feature test for this request"
        >
          {#if copiedPest}
            <Check class="h-3.5 w-3.5 text-emerald-400" />
            <span class="text-emerald-400 font-medium">Copied Test!</span>
          {:else}
            <Sparkles class="h-3.5 w-3.5 text-primary" />
            <span>Copy as Pest Test</span>
          {/if}
        </Button>

        <Button
          variant="ghost"
          size="sm"
          onclick={copyResponse}
          class="h-7 text-xs gap-1.5 text-muted-foreground hover:text-foreground hover:bg-card"
          title="Copy response payload"
        >
          {#if copiedResponse}
            <Check class="h-3.5 w-3.5 text-emerald-400" />
            <span>Copied</span>
          {:else}
            <Copy class="h-3.5 w-3.5" />
            <span>Copy</span>
          {/if}
        </Button>

        <Button
          variant="ghost"
          size="sm"
          onclick={downloadResponse}
          class="h-7 text-xs gap-1.5 text-muted-foreground hover:text-foreground hover:bg-card"
          title="Download response payload as a file"
        >
          {#if downloadedResponse}
            <Check class="h-3.5 w-3.5 text-emerald-400" />
            <span class="text-emerald-400 font-medium">Downloaded</span>
          {:else}
            <Download class="h-3.5 w-3.5" />
            <span>Download</span>
          {/if}
        </Button>
      </div>
    </div>

    <!-- Response details -->
    <div class="flex-1 flex flex-col min-h-0">
      <Tabs value={validationErrors().length > 0 ? 'validation' : 'body'} class="flex-1 flex flex-col min-h-0">
        <div class="px-3 border-b border-border bg-card/30">
          <TabsList class="h-9 bg-transparent p-0 gap-4">
            {#if validationErrors().length > 0}
              <TabsTrigger
                value="validation"
                class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-destructive rounded-none h-9 px-1 text-xs gap-1.5 text-destructive font-semibold"
              >
                <ShieldAlert class="h-3.5 w-3.5" />
                Validation ({validationErrors().length})
              </TabsTrigger>
            {/if}
            <TabsTrigger
              value="body"
              class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs"
            >
              Response Body
            </TabsTrigger>
            <TabsTrigger
              value="headers"
              class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs"
            >
              Headers ({Object.keys(result.headers).length})
            </TabsTrigger>
            <TabsTrigger
              value="queries"
              class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs gap-1.5"
            >
              <Database class="h-3.5 w-3.5 text-primary" />
              Queries ({result.queries.length})
            </TabsTrigger>
            <TabsTrigger
              value="cookies"
              class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs gap-1.5"
            >
              <Cookie class="h-3.5 w-3.5 text-amber-400" />
              Cookies ({(result.cookies ?? []).length})
            </TabsTrigger>
            {#if sseEvents().length > 0}
              <TabsTrigger
                value="events"
                class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs gap-1.5"
              >
                <Radio class="h-3 w-3 text-cyan-400" />
                Stream Events ({sseEvents().length})
              </TabsTrigger>
            {/if}
            {#if result.dumps.length > 0}
              <TabsTrigger
                value="dumps"
                class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs gap-1.5"
              >
                <Bug class="h-3 w-3" />
                Dumps ({result.dumps.length})
              </TabsTrigger>
            {/if}
          </TabsList>
        </div>

        <div class="flex-1 overflow-y-auto p-4 min-h-0">
          {#if validationErrors().length > 0}
            <TabsContent value="validation" class="m-0 space-y-3">
              <div class="p-3 rounded-md bg-destructive/10 border border-destructive/25 text-xs text-destructive">
                <div class="font-semibold mb-1">Laravel FormRequest Validation Failed (422 Unprocessable Entity)</div>
                <div>The following fields failed backend validation rules:</div>
              </div>

              <div class="space-y-2">
                {#each validationErrors() as item}
                  <div class="p-3 rounded-md border border-border bg-card/40 space-y-1">
                    <div class="font-mono text-xs font-semibold text-primary">{item.field}</div>
                    <ul class="list-disc list-inside text-xs text-muted-foreground space-y-0.5">
                      {#each item.messages as message}
                        <li>{message}</li>
                      {/each}
                    </ul>
                  </div>
                {/each}
              </div>
            </TabsContent>
          {/if}

          <TabsContent value="body" class="m-0 h-full">
            <CodeEditor value={formattedBody()} readonly={true} language="json" />
          </TabsContent>

          <TabsContent value="headers" class="m-0 space-y-1">
            <div class="rounded-md border border-border overflow-hidden">
              <table class="w-full text-xs font-mono">
                <tbody>
                  {#each Object.entries(result.headers) as [key, val]}
                    <tr class="border-b border-border/50 last:border-0 hover:bg-card/40">
                      <td class="px-3 py-1.5 text-muted-foreground font-semibold select-all w-1/3">{key}</td>
                      <td class="px-3 py-1.5 text-foreground select-all break-all">
                        {Array.isArray(val) ? val.join(', ') : val}
                      </td>
                    </tr>
                  {/each}
                </tbody>
              </table>
            </div>
          </TabsContent>

          <TabsContent value="cookies" class="m-0 space-y-3">
            {#if (result.cookies ?? []).length === 0}
              <div class="p-8 text-center text-xs text-muted-foreground space-y-2">
                <Cookie class="h-8 w-8 mx-auto opacity-40 text-amber-500" />
                <p class="font-medium text-foreground">No cookies returned in this response</p>
                <p class="text-[11px] text-muted-foreground/80 max-w-sm mx-auto">
                  The server did not send any <code>Set-Cookie</code> headers. Test with <code>GET /api/v1/cookies/set</code> to inspect auto-decrypted Laravel session and application cookies!
                </p>
              </div>
            {:else}
              <div class="text-xs text-muted-foreground flex items-center justify-between">
                <span>Decrypted and inspected response cookies ({(result.cookies ?? []).length}):</span>
                <span class="text-[11px] text-emerald-400 font-mono font-medium">APP_KEY Decryption Active</span>
              </div>
              <div class="space-y-2.5">
                {#each (result.cookies ?? []) as cookie}
                  <div class="p-3 rounded-md border border-border bg-card/40 space-y-2">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-2">
                        <span class="font-mono text-xs font-semibold text-primary">{cookie.name}</span>
                        {#if cookie.is_encrypted}
                          <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex items-center gap-1">
                            <Lock class="h-2.5 w-2.5" />
                            Decrypted
                          </span>
                        {:else}
                          <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-muted text-muted-foreground">
                            Plain
                          </span>
                        {/if}
                      </div>

                      <div class="flex items-center gap-1">
                        <Button
                          variant="ghost"
                          size="icon-sm"
                          onclick={() => copyCookie(cookie.name, cookie.decrypted_value ?? cookie.raw_value)}
                          class="h-7 px-2 text-xs gap-1 text-muted-foreground hover:text-foreground"
                          title="Copy decrypted value"
                        >
                          {#if copiedCookieKey === cookie.name}
                            <Check class="h-3 w-3 text-emerald-400" />
                            <span class="text-[10px]">Copied</span>
                          {:else}
                            <Copy class="h-3 w-3" />
                            <span class="text-[10px]">Copy</span>
                          {/if}
                        </Button>

                        <Button
                          variant="ghost"
                          size="icon-sm"
                          onclick={() => downloadCookie(cookie)}
                          class="h-7 px-2 text-xs gap-1 text-muted-foreground hover:text-foreground"
                          title="Download cookie payload as JSON"
                        >
                          <Download class="h-3 w-3" />
                          <span class="text-[10px]">Download</span>
                        </Button>
                      </div>
                    </div>

                    <div class="p-2 rounded bg-background/80 border border-border/60 text-xs font-mono select-all overflow-x-auto">
                      {#if typeof cookie.decrypted_value === 'object' && cookie.decrypted_value !== null}
                        <pre class="text-foreground">{JSON.stringify(cookie.decrypted_value, null, 2)}</pre>
                      {:else}
                        <span class="text-foreground break-all">{cookie.decrypted_value ?? '(empty)'}</span>
                      {/if}
                    </div>

                    <div class="flex items-center gap-3 text-[11px] text-muted-foreground flex-wrap pt-1 border-t border-border/40">
                      <span>Path: <strong class="text-foreground font-mono">{cookie.path ?? '/'}</strong></span>
                      {#if cookie.domain}
                        <span>Domain: <strong class="text-foreground font-mono">{cookie.domain}</strong></span>
                      {/if}
                      {#if cookie.same_site}
                        <span>SameSite: <strong class="text-foreground font-mono">{cookie.same_site}</strong></span>
                      {/if}
                      {#if cookie.http_only}
                        <span class="text-emerald-400 font-medium">HttpOnly</span>
                      {/if}
                      {#if cookie.secure}
                        <span class="text-cyan-400 font-medium">Secure</span>
                      {/if}
                    </div>
                  </div>
                {/each}
              </div>
            {/if}
          </TabsContent>

          {#if sseEvents().length > 0}
            <TabsContent value="events" class="m-0 space-y-2">
              {#each sseEvents() as sse, idx}
                <div class="p-3 rounded-md border border-border bg-card/40 text-xs font-mono space-y-1">
                  <div class="flex items-center justify-between text-muted-foreground text-[11px]">
                    <span class="font-semibold text-cyan-400">Event #{idx + 1} {sse.event ? `(${sse.event})` : ''}</span>
                    {#if sse.id}
                      <span class="text-[10px]">id: {sse.id}</span>
                    {/if}
                  </div>
                  <pre class="text-foreground select-all break-all whitespace-pre-wrap">{sse.data}</pre>
                </div>
              {/each}
            </TabsContent>
          {/if}

          <TabsContent value="queries" class="m-0 space-y-2">
            {#if result.queries.length === 0}
              <div class="p-8 text-center text-xs text-muted-foreground space-y-2">
                <Database class="h-8 w-8 mx-auto opacity-40 text-primary" />
                <p class="font-medium text-foreground">No database queries executed</p>
                <p class="text-[11px] text-muted-foreground/80 max-w-sm mx-auto">
                  This endpoint did not execute any SQL queries. Endpoints that query the database (e.g. <code>GET /api/v1/products</code> or <code>GET /admin/metrics</code>) will stream their query profiler here!
                </p>
              </div>
            {:else}
              {#each result.queries as query}
                <div class="p-2.5 rounded-md border border-border bg-card/40 text-xs font-mono space-y-1">
                  <div class="flex items-center justify-between text-muted-foreground text-[11px]">
                    <span class="font-semibold text-primary">{query.connection ?? 'default'}</span>
                    {#if query.time}
                      <span class="font-semibold text-emerald-400">{query.time} ms</span>
                    {/if}
                  </div>
                  <div class="text-foreground select-all break-all">{query.sql}</div>
                </div>
              {/each}
            {/if}
          </TabsContent>

          {#if result.dumps.length > 0}
            <TabsContent value="dumps" class="m-0 space-y-3">
              <div class="flex items-center justify-between p-2 rounded-md bg-card/60 border border-border">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-medium text-muted-foreground">
                    Dump {currentDumpIndex + 1} of {result.dumps.length}
                  </span>
                  {#if result.dumps.length > 1}
                    <div class="flex items-center gap-1">
                      <Button
                        variant="outline"
                        size="icon-sm"
                        disabled={currentDumpIndex === 0}
                        onclick={() => {
                          currentDumpIndex = Math.max(0, currentDumpIndex - 1);
                        }}
                        class="h-7 w-7"
                        title="Previous dump"
                      >
                        <ChevronLeft class="h-3.5 w-3.5" />
                      </Button>
                      <Button
                        variant="outline"
                        size="icon-sm"
                        disabled={currentDumpIndex >= result.dumps.length - 1}
                        onclick={() => {
                          currentDumpIndex = Math.min(result.dumps.length - 1, currentDumpIndex + 1);
                        }}
                        class="h-7 w-7"
                        title="Next dump"
                      >
                        <ChevronRight class="h-3.5 w-3.5" />
                      </Button>
                    </div>
                  {/if}
                </div>
                <Button
                  variant="ghost"
                  size="sm"
                  onclick={() => copyActiveDump(result.dumps[currentDumpIndex])}
                  class="h-7 px-2 text-xs gap-1.5 text-muted-foreground hover:text-foreground"
                >
                  {#if copiedDump}
                    <Check class="h-3 w-3 text-emerald-400" />
                    <span>Copied</span>
                  {:else}
                    <Copy class="h-3 w-3" />
                    <span>Copy Dump</span>
                  {/if}
                </Button>
              </div>

              <div class="p-3 rounded-md border border-border bg-card/60 overflow-x-auto text-xs font-mono">
                {@html result.dumps[currentDumpIndex]}
              </div>
            </TabsContent>
          {/if}
        </div>
      </Tabs>
    </div>
  {/if}
</div>
