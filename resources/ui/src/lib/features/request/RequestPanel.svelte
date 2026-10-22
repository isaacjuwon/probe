<script lang="ts">
  import type { RequestTab } from '$lib/domain/request-tab';
  import type { RequestState } from '$lib/features/request/request.state.svelte';
  import type { HttpMethod, AuthType } from '$lib/domain/endpoint';
  import { Input } from '$lib/components/ui/input';
  import { Button } from '$lib/components/ui/button';
  import { Tabs, TabsList, TabsTrigger, TabsContent } from '$lib/components/ui/tabs';
  import KeyValueEditor from '$lib/components/KeyValueEditor.svelte';
  import CodeEditor from '$lib/editors/CodeEditor.svelte';
  import { Send, Square, Bookmark, ShieldAlert, Sparkles, UserCheck, Share2, Check, Cookie } from '@lucide/svelte';
  import { extractPathParams, interpolateUrl, copyToClipboard } from '$lib/utils';
  import { encodeRequestToShareUrl } from '$lib/services/share-url.service';
  import { generateValue, type GeneratorType } from '$lib/services/value-generator';
  import { useServices } from '$lib/services/context';
  import type { AppUser } from '$lib/api/users.client';
  import { onMount } from 'svelte';

  export interface Props {
    tab: RequestTab;
    readonly requestState: RequestState;
    readonly onSend: () => void;
    readonly onSave?: () => void;
  }

  const { tab, requestState, onSend, onSave }: Props = $props();

  const services = useServices();
  const methods: HttpMethod[] = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];
  const authTypes: AuthType[] = ['none', 'session', 'bearer', 'basic', 'api_key', 'sanctum_act_as'];

  let availableUsers = $state<AppUser[]>([]);
  let usersLoading = $state<boolean>(false);
  let copiedShare = $state<boolean>(false);

  const pathParamKeys = $derived(extractPathParams(tab.url));
  const resolvedUrl = $derived(interpolateUrl(tab.url, tab.pathParams));
  const hasValidationErrors = $derived(tab.lastResult?.status === 422);

  onMount(() => {
    if (tab.authType === 'sanctum_act_as') {
      void loadUsers();
    }
  });

  async function loadUsers(): Promise<void> {
    if (availableUsers.length > 0 || usersLoading) return;
    usersLoading = true;
    try {
      availableUsers = await services.users.list();
    } catch {
      // Non-fatal if users table not available
    } finally {
      usersLoading = false;
    }
  }

  function handleMethodChange(e: Event): void {
    tab.method = (e.target as HTMLSelectElement).value as HttpMethod;
    tab.isDirty = true;
  }

  function handleUrlChange(e: Event): void {
    tab.url = (e.target as HTMLInputElement).value;
    tab.isDirty = true;
  }

  function handleAuthTypeChange(e: Event): void {
    tab.authType = (e.target as HTMLSelectElement).value as AuthType;
    tab.isDirty = true;
    if (tab.authType === 'sanctum_act_as') {
      void loadUsers();
    }
  }

  async function handleShare(): Promise<void> {
    const shareUrl = encodeRequestToShareUrl(tab);
    const ok = await copyToClipboard(shareUrl);
    if (ok) {
      copiedShare = true;
      setTimeout(() => {
        copiedShare = false;
      }, 2000);
    }
  }

  function insertIntoBody(type: GeneratorType): void {
    const val = generateValue(type);
    if (!tab.body.trim()) {
      tab.body = JSON.stringify({ [type]: type === 'number' ? Number(val) : type === 'boolean' ? val === 'true' : val }, null, 2);
    } else {
      try {
        const parsed = JSON.parse(tab.body) as Record<string, unknown>;
        if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) {
          parsed[type] = type === 'number' ? Number(val) : type === 'boolean' ? val === 'true' : val;
          tab.body = JSON.stringify(parsed, null, 2);
        } else {
          tab.body += `\n"${val}"`;
        }
      } catch {
        tab.body += `\n"${val}"`;
      }
    }
    tab.isDirty = true;
  }
</script>

<div class="flex flex-col h-full bg-background border-r border-border">
  <!-- URL and Action Bar -->
  <div class="p-3 border-b border-border flex flex-col gap-2">
    <div class="flex items-center gap-2">
      <select
        value={tab.method}
        onchange={handleMethodChange}
        class="h-9 px-2.5 rounded-md border border-border bg-card text-xs font-mono font-bold text-foreground focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer shrink-0"
      >
        {#each methods as m}
          <option value={m}>{m}</option>
        {/each}
      </select>

      <div class="relative flex-1">
        <Input
          value={tab.url}
          oninput={handleUrlChange}
          onclick={(e: MouseEvent) => {
            const input = e.currentTarget as HTMLInputElement;
            const pos = input.selectionStart ?? 0;
            const val = input.value;
            const before = val.slice(0, pos).lastIndexOf('{');
            const after = val.indexOf('}', pos);
            if (before !== -1 && after !== -1 && before < after && !val.slice(before, after).includes(' ')) {
              input.setSelectionRange(before, after + 1);
            }
          }}
          placeholder="https://api.example.com/endpoint"
          class="h-9 font-mono text-xs w-full bg-card/60"
        />
      </div>

      <!-- Dry Run toggle -->
      <button
        type="button"
        onclick={() => {
          tab.dryRun = !tab.dryRun;
          tab.isDirty = true;
        }}
        class="h-9 px-2.5 rounded-md border text-xs font-medium flex items-center gap-1.5 transition-colors shrink-0 {tab.dryRun
          ? 'bg-amber-500/15 border-amber-500/40 text-amber-500 font-semibold'
          : 'bg-card border-border text-muted-foreground hover:text-foreground'}"
        title="Dry Run mode: Dispatches inside an isolated database transaction and rolls back all changes"
      >
        <span
          class="w-2 h-2 rounded-full {tab.dryRun ? 'bg-amber-500 animate-pulse' : 'bg-muted-foreground/30'}"
        ></span>
        Dry Run
      </button>

      {#if requestState.status === 'sending'}
        <Button
          variant="destructive"
          size="sm"
          onclick={() => requestState.abort()}
          class="h-9 gap-1.5 px-3 shrink-0"
        >
          <Square class="h-3.5 w-3.5 fill-current" />
          Cancel
        </Button>
      {:else}
        <Button
          variant="default"
          size="sm"
          onclick={onSend}
          class="h-9 gap-1.5 px-4 font-semibold shrink-0 bg-primary hover:bg-primary/90 text-primary-foreground"
        >
          <Send class="h-3.5 w-3.5" />
          Send
        </Button>
      {/if}

      {#if onSave}
        <Button
          variant="outline"
          size="icon-sm"
          onclick={onSave}
          class="h-9 w-9 text-muted-foreground hover:text-foreground shrink-0"
          title="Save request"
        >
          <Bookmark class="h-4 w-4" />
        </Button>
      {/if}

      <Button
        variant="outline"
        size="icon-sm"
        onclick={handleShare}
        class="h-9 w-9 text-muted-foreground hover:text-foreground shrink-0"
        title="Copy shareable request link"
      >
        {#if copiedShare}
          <Check class="h-4 w-4 text-emerald-400" />
        {:else}
          <Share2 class="h-4 w-4" />
        {/if}
      </Button>
    </div>

    <!-- Resolved URL preview if parameters are present -->
    {#if pathParamKeys.length > 0 && resolvedUrl !== tab.url}
      <div class="flex items-center gap-1.5 text-[11px] font-mono text-muted-foreground px-1">
        <span class="text-primary font-semibold">Resolved:</span>
        <span class="truncate text-foreground/80">{resolvedUrl}</span>
      </div>
    {/if}
  </div>

  <!-- Request Details Tabs -->
  <div class="flex-1 flex flex-col min-h-0">
    <Tabs value={pathParamKeys.length > 0 ? 'path' : 'params'} class="flex-1 flex flex-col min-h-0">
      <div class="px-3 border-b border-border bg-card/30">
        <TabsList class="h-9 bg-transparent p-0 gap-4">
          {#if pathParamKeys.length > 0}
            <TabsTrigger
              value="path"
              class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs text-primary font-semibold"
            >
              Path ({pathParamKeys.length})
            </TabsTrigger>
          {/if}
          <TabsTrigger
            value="params"
            class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs"
          >
            Params {Object.keys(tab.query).length > 0 ? `(${Object.keys(tab.query).length})` : ''}
          </TabsTrigger>
          <TabsTrigger
            value="headers"
            class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs"
          >
            Headers {Object.keys(tab.headers).length > 0 ? `(${Object.keys(tab.headers).length})` : ''}
          </TabsTrigger>
          <TabsTrigger
            value="auth"
            class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs gap-1.5"
          >
            Auth {tab.authType !== 'none' ? `(${tab.authType})` : ''}
          </TabsTrigger>
          <TabsTrigger
            value="body"
            class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs gap-1.5"
          >
            Body
            {#if hasValidationErrors}
              <ShieldAlert class="h-3 w-3 text-destructive animate-pulse" />
            {/if}
          </TabsTrigger>
        </TabsList>
      </div>

      <div class="flex-1 overflow-y-auto p-4 min-h-0">
        {#if pathParamKeys.length > 0}
          <TabsContent value="path" class="m-0 space-y-3">
            <div class="text-xs text-muted-foreground mb-2">
              Route parameters found in URL. Enter values below to interpolate into request:
            </div>
            <div class="space-y-2 max-w-lg">
              {#each pathParamKeys as key}
                <div class="flex items-center gap-2">
                  <div class="w-32 font-mono text-xs font-semibold text-primary shrink-0 truncate">
                    {`{${key}}`}
                  </div>
                  <Input
                    placeholder={`Value for ${key}`}
                    value={tab.pathParams?.[key] ?? ''}
                    oninput={(e) => {
                      if (!tab.pathParams) tab.pathParams = {};
                      tab.pathParams[key] = (e.target as HTMLInputElement).value;
                      tab.isDirty = true;
                    }}
                    class="h-8 font-mono text-xs"
                  />
                  <select
                    title="Generate sample value"
                    class="h-8 text-[11px] bg-card/80 border border-border rounded px-1.5 text-muted-foreground hover:text-foreground cursor-pointer shrink-0"
                    onchange={(e) => {
                      const sel = e.target as HTMLSelectElement;
                      const type = sel.value as GeneratorType;
                      if (type) {
                        if (!tab.pathParams) tab.pathParams = {};
                        tab.pathParams[key] = generateValue(type);
                        tab.isDirty = true;
                        sel.value = '';
                      }
                    }}
                  >
                    <option value="" disabled selected>✨ Gen</option>
                    <option value="uuid">UUID</option>
                    <option value="timestamp">Timestamp</option>
                    <option value="number">Number</option>
                    <option value="name">Name</option>
                  </select>
                </div>
              {/each}
            </div>
          </TabsContent>
        {/if}

        <TabsContent value="params" class="m-0 h-full">
          <KeyValueEditor
            entries={tab.query}
            onChange={({ entries }) => {
              tab.query = entries;
              tab.isDirty = true;
            }}
          />
        </TabsContent>

        <TabsContent value="headers" class="m-0 h-full">
          <KeyValueEditor
            entries={tab.headers}
            onChange={({ entries }) => {
              tab.headers = entries;
              tab.isDirty = true;
            }}
          />
        </TabsContent>

        <TabsContent value="auth" class="m-0 space-y-4">
          <div class="max-w-md space-y-3">
            <div>
              <label for="auth-type-select" class="block text-xs font-medium text-muted-foreground mb-1"
                >Authentication Type</label
              >
              <select
                id="auth-type-select"
                value={tab.authType}
                onchange={handleAuthTypeChange}
                class="w-full h-8 px-2 rounded-md border border-border bg-card text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer"
              >
                {#each authTypes as type}
                  <option value={type}>{type.toUpperCase().replace(/_/g, ' ')}</option>
                {/each}
              </select>
            </div>

            {#if tab.authType === 'sanctum_act_as'}
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <label for="act-as-user-select" class="block text-xs font-medium text-muted-foreground"
                    >Select User to Impersonate</label
                  >
                  <button
                    type="button"
                    onclick={() => void loadUsers()}
                    class="text-[11px] text-primary hover:underline"
                  >
                    Refresh Users
                  </button>
                </div>
                {#if availableUsers.length > 0}
                  <select
                    id="act-as-user-select"
                    value={(tab.authPayload.user_id as string) ?? ''}
                    onchange={(e) => {
                      tab.authPayload.user_id = (e.target as HTMLSelectElement).value;
                      tab.isDirty = true;
                    }}
                    class="w-full h-8 px-2 rounded-md border border-border bg-card text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer"
                  >
                    <option value="">-- Choose Application User --</option>
                    {#each availableUsers as user}
                      <option value={user.id}>
                        {user.name} ({user.email ?? `ID: ${user.id}`})
                      </option>
                    {/each}
                  </select>
                {:else}
                  <Input
                    id="act-as-user-select"
                    placeholder="User ID (e.g. 1)"
                    value={(tab.authPayload.user_id as string) ?? ''}
                    oninput={(e) => {
                      tab.authPayload.user_id = (e.target as HTMLInputElement).value;
                      tab.isDirty = true;
                    }}
                    class="h-8 font-mono text-xs"
                  />
                {/if}
                <p class="text-[11px] text-muted-foreground">
                  Generates an in-memory Sanctum token for this user without requiring manual login.
                </p>
              </div>
            {:else if tab.authType === 'bearer'}
              <div class="space-y-1">
                <label for="bearer-token-input" class="block text-xs font-medium text-muted-foreground"
                  >Bearer Token</label
                >
                <Input
                  id="bearer-token-input"
                  type="password"
                  placeholder="Token"
                  value={(tab.authPayload.token as string) ?? ''}
                  oninput={(e) => {
                    tab.authPayload.token = (e.target as HTMLInputElement).value;
                    tab.isDirty = true;
                  }}
                  class="h-8 font-mono text-xs"
                />
              </div>
            {:else if tab.authType === 'basic'}
              <div class="space-y-2">
                <div class="space-y-1">
                  <label for="basic-username-input" class="block text-xs font-medium text-muted-foreground"
                    >Username</label
                  >
                  <Input
                    id="basic-username-input"
                    placeholder="Username"
                    value={(tab.authPayload.username as string) ?? ''}
                    oninput={(e) => {
                      tab.authPayload.username = (e.target as HTMLInputElement).value;
                      tab.isDirty = true;
                    }}
                    class="h-8 font-mono text-xs"
                  />
                </div>
                <div class="space-y-1">
                  <label for="basic-password-input" class="block text-xs font-medium text-muted-foreground"
                    >Password</label
                  >
                  <Input
                    id="basic-password-input"
                    type="password"
                    placeholder="Password"
                    value={(tab.authPayload.password as string) ?? ''}
                    oninput={(e) => {
                      tab.authPayload.password = (e.target as HTMLInputElement).value;
                      tab.isDirty = true;
                    }}
                    class="h-8 font-mono text-xs"
                  />
                </div>
              </div>
            {:else if tab.authType === 'api_key'}
              <div class="space-y-2">
                <div class="space-y-1">
                  <label for="apikey-name-input" class="block text-xs font-medium text-muted-foreground"
                    >Key Name</label
                  >
                  <Input
                    id="apikey-name-input"
                    placeholder="X-API-Key"
                    value={(tab.authPayload.name as string) ?? 'X-API-Key'}
                    oninput={(e) => {
                      tab.authPayload.name = (e.target as HTMLInputElement).value;
                      tab.isDirty = true;
                    }}
                    class="h-8 font-mono text-xs"
                  />
                </div>
                <div class="space-y-1">
                  <label for="apikey-value-input" class="block text-xs font-medium text-muted-foreground"
                    >Value</label
                  >
                  <Input
                    id="apikey-value-input"
                    placeholder="Value"
                    value={(tab.authPayload.value as string) ?? ''}
                    oninput={(e) => {
                      tab.authPayload.value = (e.target as HTMLInputElement).value;
                      tab.isDirty = true;
                    }}
                    class="h-8 font-mono text-xs"
                  />
                </div>
              </div>
            {:else if tab.authType === 'session'}
              <div class="p-3 rounded-md bg-card/60 border border-border text-xs space-y-2">
                <div class="flex items-center gap-2 text-primary font-semibold">
                  <Cookie class="h-4 w-4" />
                  <span>Active Browser Session Pass-Through</span>
                </div>
                <p class="text-muted-foreground">
                  Probe automatically forwards your current authenticated web session, session cookies, and CSRF tokens directly into the sub-request without requiring manual token setup.
                </p>
              </div>
            {:else}
              <p class="text-xs text-muted-foreground">No authentication will be attached to this request.</p>
            {/if}
          </div>
        </TabsContent>

        <TabsContent value="body" class="m-0 h-full flex flex-col min-h-0">
          <div class="flex items-center gap-1.5 pb-2 text-xs text-muted-foreground flex-wrap shrink-0">
            <span class="text-[11px] font-semibold text-foreground/80 flex items-center gap-1">
              <Sparkles class="h-3 w-3 text-primary" />
              Quick Values:
            </span>
            <button
              type="button"
              onclick={() => insertIntoBody('uuid')}
              class="px-2 py-0.5 rounded bg-card/80 hover:bg-card text-[11px] border border-border/80 transition-colors"
            >
              + UUID
            </button>
            <button
              type="button"
              onclick={() => insertIntoBody('timestamp')}
              class="px-2 py-0.5 rounded bg-card/80 hover:bg-card text-[11px] border border-border/80 transition-colors"
            >
              + Timestamp
            </button>
            <button
              type="button"
              onclick={() => insertIntoBody('email')}
              class="px-2 py-0.5 rounded bg-card/80 hover:bg-card text-[11px] border border-border/80 transition-colors"
            >
              + Email
            </button>
            <button
              type="button"
              onclick={() => insertIntoBody('name')}
              class="px-2 py-0.5 rounded bg-card/80 hover:bg-card text-[11px] border border-border/80 transition-colors"
            >
              + Name
            </button>
            <button
              type="button"
              onclick={() => insertIntoBody('number')}
              class="px-2 py-0.5 rounded bg-card/80 hover:bg-card text-[11px] border border-border/80 transition-colors"
            >
              + Number
            </button>
          </div>
          <div class="flex-1 min-h-0">
            <CodeEditor
              value={tab.body}
              language="json"
              onChange={({ value }) => {
                tab.body = value;
                tab.isDirty = true;
              }}
            />
          </div>
        </TabsContent>
      </div>
    </Tabs>
  </div>
</div>
