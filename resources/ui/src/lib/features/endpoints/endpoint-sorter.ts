import type { Endpoint } from '$lib/domain/endpoint';

/**
 * Calculates a canonical REST weight for an endpoint to arrange routes in standard CRUD lifecycle:
 * 1. GET Collection (Index)
 * 2. POST Collection (Store)
 * 3. GET Item (Show)
 * 4. PUT Item (Replace)
 * 5. PATCH Item (Update)
 * 6. DELETE Item (Destroy)
 * 7. Sub-actions / RPCs
 */
export function getEndpointRestWeight(endpoint: Endpoint): number {
  const method = endpoint.method.toUpperCase();
  const uri = endpoint.uri.replace(/\/+$/, '');
  const hasParam = /\{[a-zA-Z0-9_]+\}/.test(uri);
  const endsWithParam = /\{[a-zA-Z0-9_]+\}$/.test(uri);

  // 1. GET Collection (Index)
  if (method === 'GET' && !hasParam) return 10;

  // 2. POST Collection (Store / Create)
  if (method === 'POST' && !hasParam) return 20;

  // 3. GET Single Item (Show)
  if (method === 'GET' && endsWithParam) return 30;

  // 4. PUT Single Item (Replace)
  if (method === 'PUT' && endsWithParam) return 40;

  // 5. PATCH Single Item (Partial Update)
  if (method === 'PATCH' && endsWithParam) return 50;

  // 6. DELETE Single Item (Destroy)
  if (method === 'DELETE' && endsWithParam) return 60;

  // 7. General DELETE
  if (method === 'DELETE') return 65;

  // 8. General PUT/PATCH
  if (method === 'PUT' || method === 'PATCH') return 68;

  // 9. Sub-resource actions (e.g. POST /users/{id}/verify)
  if (method === 'POST') return 70;
  if (method === 'GET') return 75;

  return 80;
}

export function sortEndpointsCanonical(endpoints: readonly Endpoint[]): Endpoint[] {
  return [...endpoints].sort((a, b) => {
    const weightA = getEndpointRestWeight(a);
    const weightB = getEndpointRestWeight(b);
    if (weightA !== weightB) {
      return weightA - weightB;
    }
    return a.uri.localeCompare(b.uri);
  });
}

export function extractControllerName(action?: string | null): string {
  if (!action || action === 'Closure' || action.toLowerCase().includes('closure')) {
    return 'Closure Routes';
  }
  const classWithMethod = action.split('@')[0];
  const parts = classWithMethod.split('\\');
  const className = parts[parts.length - 1];
  return className && className.trim() !== '' ? className : 'General';
}
