<script lang="ts">
  import { onMount } from 'svelte';
  import type { CommandRegistry } from '$lib/commands/command.registry';
  import {
    CommandDialog,
    CommandInput,
    CommandList,
    CommandEmpty,
    CommandGroup,
    CommandItem,
    CommandShortcut,
  } from '$lib/components/ui/command';

  export interface Props {
    readonly commandRegistry: CommandRegistry;
    open?: boolean;
    readonly onOpenChange?: (open: boolean) => void;
  }

  let { commandRegistry, open = $bindable(false), onOpenChange }: Props = $props();

  onMount(() => {
    function handleKeyDown(e: KeyboardEvent): void {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        open = !open;
        onOpenChange?.(open);
      }
    }

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  });

  function executeCommand(run: () => void | Promise<void>): void {
    open = false;
    onOpenChange?.(false);
    void run();
  }
</script>

<CommandDialog bind:open>
  <CommandInput placeholder="Type a command or search..." />
  <CommandList>
    <CommandEmpty>No results found.</CommandEmpty>
    <CommandGroup heading="Actions">
      {#each commandRegistry.all() as command (command.id)}
        <CommandItem onSelect={() => executeCommand(command.run)} class="cursor-pointer">
          <span>{command.title}</span>
          {#if command.shortcut}
            <CommandShortcut>{command.shortcut}</CommandShortcut>
          {/if}
        </CommandItem>
      {/each}
    </CommandGroup>
  </CommandList>
</CommandDialog>
