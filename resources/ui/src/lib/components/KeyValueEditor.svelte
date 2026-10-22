<script lang="ts">
  import { Button } from '$lib/components/ui/button';
  import { Input } from '$lib/components/ui/input';
  import { Trash2, Plus } from '@lucide/svelte';
  import { generateId } from '$lib/utils';
  import { generateValue, type GeneratorType } from '$lib/services/value-generator';

  export interface Props {
    entries: Record<string, string>;
    readonly onChange?: (options: { readonly entries: Record<string, string> }) => void;
  }

  let { entries = $bindable(), onChange }: Props = $props();

  interface Row {
    id: string;
    key: string;
    value: string;
    enabled: boolean;
  }

  let rows = $state<Row[]>([]);
  let lastSyncedEntries: Record<string, string> | null = null;

  $effect(() => {
    if (entries !== lastSyncedEntries) {
      lastSyncedEntries = entries;
      rows = Object.entries(entries ?? {}).map(([k, v]) => ({
        id: generateId(),
        key: k,
        value: v,
        enabled: true,
      }));
    }
  });

  function sync(): void {
    const updated: Record<string, string> = {};
    for (const row of rows) {
      if (row.enabled && row.key.trim() !== '') {
        updated[row.key.trim()] = row.value;
      }
    }
    entries = updated;
    lastSyncedEntries = updated;
    onChange?.({ entries: updated });
  }

  function addRow(): void {
    rows = [
      ...rows,
      {
        id: generateId(),
        key: '',
        value: '',
        enabled: true,
      },
    ];
    sync();
  }

  function removeRow(id: string): void {
    rows = rows.filter((r) => r.id !== id);
    sync();
  }

  function onKeyChange(row: Row, val: string): void {
    row.key = val;
    sync();
  }

  function onValueChange(row: Row, val: string): void {
    row.value = val;
    sync();
  }

  function onToggleRow(row: Row): void {
    row.enabled = !row.enabled;
    sync();
  }
</script>

<div class="space-y-2">
  <div class="space-y-1.5">
    {#each rows as row (row.id)}
      <div class="flex items-center gap-2">
        <input
          type="checkbox"
          checked={row.enabled}
          onchange={() => onToggleRow(row)}
          class="rounded border-border accent-primary h-4 w-4 cursor-pointer"
        />
        <Input
          placeholder="Key"
          value={row.key}
          oninput={(e: Event) => onKeyChange(row, (e.target as HTMLInputElement).value)}
          class="h-8 text-xs font-mono"
        />
        <Input
          placeholder="Value"
          value={row.value}
          oninput={(e: Event) => onValueChange(row, (e.target as HTMLInputElement).value)}
          class="h-8 text-xs font-mono"
        />
        <select
          title="Generate sample value"
          class="h-8 text-[11px] bg-card/80 border border-border rounded px-1.5 text-muted-foreground hover:text-foreground cursor-pointer shrink-0"
          onchange={(e) => {
            const sel = e.target as HTMLSelectElement;
            const type = sel.value as GeneratorType;
            if (type) {
              onValueChange(row, generateValue(type));
              sel.value = '';
            }
          }}
        >
          <option value="" disabled selected>✨ Gen</option>
          <option value="uuid">UUID</option>
          <option value="timestamp">Timestamp</option>
          <option value="date">Date</option>
          <option value="email">Email</option>
          <option value="name">Name</option>
          <option value="number">Number</option>
          <option value="boolean">Boolean</option>
        </select>
        <Button
          variant="ghost"
          size="icon-sm"
          onclick={() => removeRow(row.id)}
          class="text-muted-foreground hover:text-destructive h-8 w-8 shrink-0"
        >
          <Trash2 class="h-3.5 w-3.5" />
        </Button>
      </div>
    {/each}
  </div>

  <Button variant="outline" size="sm" onclick={addRow} class="text-xs h-7 gap-1.5">
    <Plus class="h-3.5 w-3.5" />
    Add Row
  </Button>
</div>
