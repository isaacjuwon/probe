<script lang="ts">
  import type { RequestTab } from '$lib/domain/request-tab';
  import type { RequestState } from '$lib/features/request/request.state.svelte';
  import type { HttpMethod, AuthType } from '$lib/domain/endpoint';
  import { Input } from '$lib/components/ui/input';
  import { Button } from '$lib/components/ui/button';
  import { Tabs, TabsList, TabsTrigger, TabsContent } from '$lib/components/ui/tabs';
  import KeyValueEditor from '$lib/components/KeyValueEditor.svelte';
  import CodeEditor from '$lib/editors/CodeEditor.svelte';
  import { Send, Square, Bookmark } from '@lucide/svelte';

  export interface Props {
    tab: RequestTab;
    readonly requestState: RequestState;
    readonly onSend: () => void;
    readonly onSave?: () => void;
  }

  const { tab, requestState, onSend, onSave }: Props = $props();

  const methods: HttpMethod[] = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];
  const authTypes: AuthType[] = ['none', 'bearer', 'basic', 'api_key'];

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
  }
</script>

<div class="flex flex-col h-full bg-background border-r border-border">
  <!-- URL and Action Bar -->
  <div class="p-3 border-b border-border flex items-center gap-2">
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
        placeholder="https://api.example.com/endpoint"
        class="h-9 font-mono text-xs w-full bg-card/60"
      />
    </div>

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
  </div>

  <!-- Request Details Tabs -->
  <div class="flex-1 flex flex-col min-h-0">
    <Tabs value="params" class="flex-1 flex flex-col min-h-0">
      <div class="px-3 border-b border-border bg-card/30">
        <TabsList class="h-9 bg-transparent p-0 gap-4">
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
            class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs"
          >
            Auth {tab.authType !== 'none' ? `(${tab.authType})` : ''}
          </TabsTrigger>
          <TabsTrigger
            value="body"
            class="data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:border-b-2 data-[state=active]:border-primary rounded-none h-9 px-1 text-xs"
          >
            Body
          </TabsTrigger>
        </TabsList>
      </div>

      <div class="flex-1 overflow-y-auto p-4 min-h-0">
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
              <label for="auth-type-select" class="block text-xs font-medium text-muted-foreground mb-1">Authentication Type</label>
              <select
                id="auth-type-select"
                value={tab.authType}
                onchange={handleAuthTypeChange}
                class="w-full h-8 px-2 rounded-md border border-border bg-card text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer"
              >
                {#each authTypes as type}
                  <option value={type}>{type.toUpperCase()}</option>
                {/each}
              </select>
            </div>

            {#if tab.authType === 'bearer'}
              <div class="space-y-1">
                <label for="bearer-token-input" class="block text-xs font-medium text-muted-foreground">Bearer Token</label>
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
                  <label for="basic-username-input" class="block text-xs font-medium text-muted-foreground">Username</label>
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
                  <label for="basic-password-input" class="block text-xs font-medium text-muted-foreground">Password</label>
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
                  <label for="apikey-name-input" class="block text-xs font-medium text-muted-foreground">Key Name</label>
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
                  <label for="apikey-value-input" class="block text-xs font-medium text-muted-foreground">Value</label>
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
            {:else}
              <p class="text-xs text-muted-foreground">No authentication will be attached to this request.</p>
            {/if}
          </div>
        </TabsContent>

        <TabsContent value="body" class="m-0 h-full">
          <CodeEditor
            value={tab.body}
            language="json"
            onChange={({ value }) => {
              tab.body = value;
              tab.isDirty = true;
            }}
          />
        </TabsContent>
      </div>
    </Tabs>
  </div>
</div>
