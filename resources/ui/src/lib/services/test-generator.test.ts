import { describe, expect, it } from 'vitest';
import { generatePestTest } from './test-generator';
import type { RequestTab } from '$lib/domain/request-tab';
import type { RelayResult } from '$lib/domain/relay';

describe('generatePestTest', () => {
  it('generates a GET test with assertOk', () => {
    const tab: RequestTab = {
      id: 'tab-1',
      title: 'Get Users',
      method: 'GET',
      url: 'http://localhost/api/users',
      headers: {},
      query: {},
      body: '',
      authType: 'none',
      authPayload: {},
    };

    const result: RelayResult = {
      status: 200,
      headers: {},
      body: { data: [{ id: 1 }] },
      durationMs: 12.5,
      queries: [],
      dumps: [],
      logs: [],
      exceptions: [],
    };

    const test = generatePestTest(tab, result);
    expect(test).toContain("it('can get users', function () {");
    expect(test).toContain("$response = $this->getJson('/api/users');");
    expect(test).toContain('$response->assertOk()');
  });

  it('generates a POST test with JSON payload and validation errors on 422', () => {
    const tab: RequestTab = {
      id: 'tab-2',
      title: 'Create User',
      method: 'POST',
      url: 'http://localhost/api/users',
      headers: {},
      query: {},
      body: JSON.stringify({ email: 'bad-email' }),
      authType: 'none',
      authPayload: {},
    };

    const result: RelayResult = {
      status: 422,
      headers: {},
      body: { message: 'Invalid data', errors: { email: ['Invalid email format'] } },
      durationMs: 8.2,
      queries: [],
      dumps: [],
      logs: [],
      exceptions: [],
    };

    const test = generatePestTest(tab, result);
    expect(test).toContain("$response = $this->postJson('/api/users', {\n    \"email\": \"bad-email\"\n});");
    expect(test).toContain('$response->assertUnprocessable()');
    expect(test).toContain('->assertJsonValidationErrors(["email"])');
  });
});
