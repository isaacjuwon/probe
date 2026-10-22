import { describe, expect, it } from 'vitest';
import {
  encodeRequestToShareUrl,
  decodeSharePayload,
  extractShareFromSearch,
  encodeStringToBase64Url,
  decodeBase64UrlToString,
} from './share-url.service';
import type { RequestTab } from '$lib/domain/request-tab';

describe('share-url.service', () => {
  it('encodes and decodes utf8 strings to base64url correctly', () => {
    const original = 'Hello World 🚀 {"user": "Alice"}';
    const encoded = encodeStringToBase64Url(original);
    expect(encoded).not.toContain('+');
    expect(encoded).not.toContain('/');
    expect(encoded).not.toContain('=');

    const decoded = decodeBase64UrlToString(encoded);
    expect(decoded).toBe(original);
  });

  it('encodes a RequestTab to a share URL and decodes it back', () => {
    const mockTab: RequestTab = {
      id: 'tab-1',
      title: 'GET /api/users',
      method: 'GET',
      url: 'http://localhost/api/users',
      headers: { 'X-Custom-Header': 'val123' },
      query: { page: '2' },
      pathParams: { org: 'dev' },
      body: '{"foo":"bar"}',
      authType: 'bearer',
      authPayload: { token: 'secret-token' },
      isDirty: false,
    };

    const shareUrl = encodeRequestToShareUrl(mockTab, 'http://probe.test/probe');
    expect(shareUrl).toContain('http://probe.test/probe?share=');

    const parsed = extractShareFromSearch(shareUrl.split('?')[1]);
    expect(parsed).not.toBeNull();
    expect(parsed?.method).toBe('GET');
    expect(parsed?.url).toBe('http://localhost/api/users');
    expect(parsed?.headers).toEqual({ 'X-Custom-Header': 'val123' });
    expect(parsed?.query).toEqual({ page: '2' });
    expect(parsed?.pathParams).toEqual({ org: 'dev' });
    expect(parsed?.body).toBe('{"foo":"bar"}');
    expect(parsed?.authType).toBe('bearer');
    expect(parsed?.authPayload).toEqual({ token: 'secret-token' });
  });

  it('returns null gracefully on malformed share payloads', () => {
    expect(decodeSharePayload('invalid-base64-payload!!!')).toBeNull();
    expect(extractShareFromSearch('?other_param=123')).toBeNull();
    expect(extractShareFromSearch('')).toBeNull();
  });
});
