import type { Command } from '$lib/commands/command';

export class CommandRegistry {
  private commands = new Map<string, Command>();

  register(options: { readonly command: Command }): void {
    this.commands.set(options.command.id, options.command);
  }

  all(): Command[] {
    return Array.from(this.commands.values());
  }

  find(options: { readonly id: string }): Command | undefined {
    return this.commands.get(options.id);
  }
}
