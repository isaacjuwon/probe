import { getContext, setContext } from 'svelte';
import { EndpointClient } from '$lib/api/endpoint.client';
import { UsersClient } from '$lib/api/users.client';
import { ProbeApi } from '$lib/api/probe.client';
import { RelayClient } from '$lib/api/relay.client';
import { StorageClient } from '$lib/api/storage.client';
import { CommandRegistry } from '$lib/commands/command.registry';
import { IndexedDbTabRepository } from '$lib/persistence/indexeddb-tab.repository';
import { LocalStorageHistoryRepository } from '$lib/persistence/local-storage-history.repository';
import type { TabRepository } from '$lib/persistence/tab.repository';
import type { HistoryRepository } from '$lib/persistence/history.repository';
import { EventBus } from '$lib/services/event-bus';

const SERVICES_KEY = Symbol('PROBE_SERVICES');

export interface ProbeServices {
  readonly api: ProbeApi;
  readonly endpoints: EndpointClient;
  readonly users: UsersClient;
  readonly relay: RelayClient;
  readonly storage: StorageClient;
  readonly tabRepository: TabRepository;
  readonly historyRepository: HistoryRepository;
  readonly eventBus: EventBus;
  readonly commands: CommandRegistry;
}

export type Services = ProbeServices;

export function createDefaultServices(): ProbeServices {
  const api = new ProbeApi();
  const endpoints = new EndpointClient(api);
  const users = new UsersClient(api);
  const relay = new RelayClient(api);
  const storage = new StorageClient(api);
  const tabRepository = new IndexedDbTabRepository();
  const historyRepository = new LocalStorageHistoryRepository();
  const eventBus = new EventBus();
  const commands = new CommandRegistry();

  return {
    api,
    endpoints,
    users,
    relay,
    storage,
    tabRepository,
    historyRepository,
    eventBus,
    commands,
  };
}

export function provideServices(customServices?: ProbeServices): ProbeServices {
  const services = customServices ?? createDefaultServices();
  setContext(SERVICES_KEY, services);
  return services;
}

export function useServices(): ProbeServices {
  const services = getContext<ProbeServices>(SERVICES_KEY);
  if (!services) {
    throw new Error('ProbeServices not found in Svelte context. Did you call provideServices() at root?');
  }
  return services;
}
