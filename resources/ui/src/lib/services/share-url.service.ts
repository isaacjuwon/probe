import type { RequestTab } from '$lib/domain/request-tab';
import type { AuthType, HttpMethod } from '$lib/domain/endpoint';

export interface SharedRequestPayload {
  m: HttpMethod;
  u: string;
  h?: Record<string, string>;
  q?: Record<string, string>;
  p?: Record<string, string>;
  b?: string;
  a?: AuthType;
  ap?: Record<string, unknown>;
}

export function encodeStringToBase64Url(str: string): string {
  if (typeof btoa === 'function') {
    const bytes = new TextEncoder().encode(str);
    let binary = '';
    for (let i = 0; i < bytes.length; i++) {
      binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary)
      .replace(/\+/g, '-')
      .replace(/\//g, '_')
      .replace(/=+$/, '');
  }
  return '';
}

export function decodeBase64UrlToString(base64url: string): string {
  if (typeof atob === 'function') {
    let base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    while (base64.length % 4 !== 0) {
      base64 += '=';
    }
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return new TextDecoder().decode(bytes);
  }
  return '';
}

export function encodeRequestToShareUrl(tab: RequestTab, currentUrl?: string): string {
  const payload: SharedRequestPayload = {
    m: tab.method,
    u: tab.url,
    h: tab.headers && Object.keys(tab.headers).length > 0 ? tab.headers : undefined,
    q: tab.query && Object.keys(tab.query).length > 0 ? tab.query : undefined,
    p: tab.pathParams && Object.keys(tab.pathParams).length > 0 ? tab.pathParams : undefined,
    b: tab.body.trim() ? tab.body : undefined,
    a: tab.authType !== 'none' ? tab.authType : undefined,
    ap: tab.authPayload && Object.keys(tab.authPayload).length > 0 ? tab.authPayload : undefined,
  };

  const json = JSON.stringify(payload);
  const encoded = encodeStringToBase64Url(json);

  const baseUrl = currentUrl || (typeof window !== 'undefined' ? window.location.href.split('?')[0].split('#')[0] : '');
  return `${baseUrl}?share=${encoded}`;
}

export function decodeSharePayload(encoded: string): Partial<RequestTab> | null {
  try {
    const json = decodeBase64UrlToString(encoded);
    if (!json) return null;
    const payload = JSON.parse(json) as SharedRequestPayload;

    if (!payload.m || !payload.u) return null;

    return {
      title: `${payload.m} ${payload.u}`,
      method: payload.m,
      url: payload.u,
      headers: payload.h || {},
      query: payload.q || {},
      pathParams: payload.p || {},
      body: payload.b || '',
      authType: payload.a || 'none',
      authPayload: payload.ap || {},
    };
  } catch {
    return null;
  }
}

export function extractShareFromSearch(searchOrHash: string): Partial<RequestTab> | null {
  if (!searchOrHash) return null;

  const clean = searchOrHash.startsWith('?') || searchOrHash.startsWith('#')
    ? searchOrHash.slice(1)
    : searchOrHash;

  const params = new URLSearchParams(clean);
  const shareParam = params.get('share');
  if (!shareParam) return null;

  return decodeSharePayload(shareParam);
}
