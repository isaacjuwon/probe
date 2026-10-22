import type { RequestTab } from '$lib/domain/request-tab';
import type { RelayResult } from '$lib/domain/relay';

export function generatePestTest(tab: RequestTab, result: RelayResult): string {
  let path = tab.url;
  try {
    const urlObj = new URL(tab.url);
    path = urlObj.pathname + urlObj.search;
  } catch {
    // Keep raw string if URL parse fails
  }

  const method = tab.method.toUpperCase();
  const testName = `can ${method.toLowerCase()} ${path.replace(/^\/api\/?/, '').replace(/[^a-zA-Z0-9/_-]/g, ' ') || 'endpoint'}`.trim();

  let pestMethod = 'getJson';
  if (method === 'POST') pestMethod = 'postJson';
  else if (method === 'PUT') pestMethod = 'putJson';
  else if (method === 'PATCH') pestMethod = 'patchJson';
  else if (method === 'DELETE') pestMethod = 'deleteJson';
  else if (method === 'OPTIONS' || method === 'HEAD') pestMethod = `${method.toLowerCase()}Json`;

  let bodyArg = '';
  if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) && tab.body.trim()) {
    try {
      const parsed = JSON.parse(tab.body);
      bodyArg = `, ${JSON.stringify(parsed, null, 4)}`;
    } catch {
      bodyArg = `, ['data' => '${tab.body.replace(/'/g, "\\'")}']`;
    }
  }

  let statusAssertion = `assertStatus(${result.status})`;
  if (result.status === 200) statusAssertion = 'assertOk()';
  else if (result.status === 201) statusAssertion = 'assertCreated()';
  else if (result.status === 204) statusAssertion = 'assertNoContent()';
  else if (result.status === 401) statusAssertion = 'assertUnauthorized()';
  else if (result.status === 403) statusAssertion = 'assertForbidden()';
  else if (result.status === 404) statusAssertion = 'assertNotFound()';
  else if (result.status === 422) statusAssertion = 'assertUnprocessable()';

  let bodyAssertions = '';
  if (result.body && typeof result.body === 'object') {
    const obj = result.body as Record<string, unknown>;
    if (result.status === 422 && obj.errors && typeof obj.errors === 'object') {
      const errorKeys = Object.keys(obj.errors);
      bodyAssertions = `\n        ->assertJsonValidationErrors(${JSON.stringify(errorKeys)})`;
    } else if (obj.data && typeof obj.data === 'object' && !Array.isArray(obj.data)) {
      const sampleKeys = Object.keys(obj.data).slice(0, 4);
      bodyAssertions = `\n        ->assertJsonStructure([\n            'data' => ${JSON.stringify(sampleKeys)},\n        ])`;
    } else {
      const topKeys = Object.keys(obj).slice(0, 4);
      if (topKeys.length > 0) {
        bodyAssertions = `\n        ->assertJsonStructure(${JSON.stringify(topKeys)})`;
      }
    }
  }

  return `it('${testName}', function () {
    $response = $this->${pestMethod}('${path}'${bodyArg});

    $response->${statusAssertion}${bodyAssertions};
});
`;
}
