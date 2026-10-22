import { describe, expect, it } from 'vitest';
import {
  getEndpointRestWeight,
  sortEndpointsCanonical,
  extractControllerName,
} from './endpoint-sorter';
import type { Endpoint } from '$lib/domain/endpoint';

describe('endpoint-sorter', () => {
  const createMock = (method: Endpoint['method'], uri: string, action?: string): Endpoint => ({
    method,
    uri,
    action,
    middleware: [],
    headers: {},
    parameters: {},
    fields: {},
    auth: 'none',
  });

  describe('getEndpointRestWeight', () => {
    it('ranks index GET before store POST, and show GET before delete DELETE', () => {
      const getIndex = createMock('GET', 'api/users');
      const postStore = createMock('POST', 'api/users');
      const getShow = createMock('GET', 'api/users/{user}');
      const putUpdate = createMock('PUT', 'api/users/{user}');
      const patchUpdate = createMock('PATCH', 'api/users/{user}');
      const deleteDestroy = createMock('DELETE', 'api/users/{user}');

      expect(getEndpointRestWeight(getIndex)).toBeLessThan(getEndpointRestWeight(postStore));
      expect(getEndpointRestWeight(postStore)).toBeLessThan(getEndpointRestWeight(getShow));
      expect(getEndpointRestWeight(getShow)).toBeLessThan(getEndpointRestWeight(putUpdate));
      expect(getEndpointRestWeight(putUpdate)).toBeLessThan(getEndpointRestWeight(patchUpdate));
      expect(getEndpointRestWeight(patchUpdate)).toBeLessThan(getEndpointRestWeight(deleteDestroy));
    });
  });

  describe('sortEndpointsCanonical', () => {
    it('sorts random endpoints in canonical CRUD lifecycle order', () => {
      const endpoints: Endpoint[] = [
        createMock('DELETE', 'api/users/{id}'),
        createMock('POST', 'api/users'),
        createMock('PUT', 'api/users/{id}'),
        createMock('GET', 'api/users/{id}'),
        createMock('GET', 'api/users'),
        createMock('POST', 'api/users/{id}/verify'),
      ];

      const sorted = sortEndpointsCanonical(endpoints);
      expect(sorted.map((e) => `${e.method} ${e.uri}`)).toEqual([
        'GET api/users',
        'POST api/users',
        'GET api/users/{id}',
        'PUT api/users/{id}',
        'DELETE api/users/{id}',
        'POST api/users/{id}/verify',
      ]);
    });
  });

  describe('extractControllerName', () => {
    it('extracts controller class basename from standard Laravel action string', () => {
      expect(extractControllerName('App\\Http\\Controllers\\Api\\UserController@index')).toBe('UserController');
      expect(extractControllerName('App\\Http\\Controllers\\OrderController@store')).toBe('OrderController');
      expect(extractControllerName('App\\Http\\Controllers\\InvokableController')).toBe('InvokableController');
    });

    it('handles closures and empty actions gracefully', () => {
      expect(extractControllerName('Closure')).toBe('Closure Routes');
      expect(extractControllerName(null)).toBe('Closure Routes');
      expect(extractControllerName(undefined)).toBe('Closure Routes');
    });
  });
});
