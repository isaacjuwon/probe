import { describe, it, expect, vi } from 'vitest';
import { CommandRegistry } from './command.registry';

describe('CommandRegistry', () => {
  it('registers and retrieves commands by id', async () => {
    const registry = new CommandRegistry();
    const action = vi.fn();

    registry.register({
      command: {
        id: 'test-action',
        title: 'Run Test',
        run: action,
      },
    });

    const cmd = registry.find({ id: 'test-action' });
    expect(cmd).toBeDefined();
    expect(cmd?.title).toBe('Run Test');

    await cmd?.run();
    expect(action).toHaveBeenCalledTimes(1);
  });

  it('lists all registered commands', () => {
    const registry = new CommandRegistry();
    registry.register({
      command: {
        id: 'send-req',
        title: 'Send Request',
        category: 'Relay',
        run: () => {},
      },
    });
    registry.register({
      command: {
        id: 'new-tab',
        title: 'New Tab',
        category: 'Tabs',
        run: () => {},
      },
    });

    const commands = registry.all();
    expect(commands).toHaveLength(2);
    expect(commands.map((c) => c.id)).toEqual(['send-req', 'new-tab']);
  });
});
