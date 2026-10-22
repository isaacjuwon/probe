import { getContext, setContext } from 'svelte';
import { EndpointClient } from '$lib/api/endpoint.client';
import { ProbeApi } from '$lib/api/probe.client';
import { RelayClient } from '$lib/api/relay.client';
import { StorageClient } from '$lib/api/storage.client';
import { CommandRegistry } from '$lib/commands/command.registry';
import { IndexedDbTabRepository } from '$lib/persistence/indexeddb-tab.repository';
import type { TabRepository } from '$lib/persistence/tab.repository';
import { EventBus } from '$lib/services/event-bus';

const SERVICES_KEY = Symbol('PROBE_SERVICES');

export interface ProbeServices {
  readonly api: ProbeApi;
  readonly endpoints: EndpointClient;
  readonly relay: RelayClient;
  readonly storage: StorageClient;
  readonly tabRepository: TabRepository;
  readonly eventBus: EventBus;
  readonly commands: CommandRegistry;
}

export function createDefaultServices(): ProbeServices {
  const api = new ProbeApi();
  const endpoints = new EndpointClient(api);
  const relay = new RelayClient(api);
  const storage = new StorageClient(api);
  const tabRepository = new IndexedDbTabRepository();
  const eventBus = new EventBus();
  const commands = new CommandRegistry();

  return {
    api,
    endpoints,
    relay,
    storage,
    tabRepository,
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
