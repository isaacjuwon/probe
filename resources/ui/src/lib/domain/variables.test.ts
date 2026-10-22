import { describe, it, expect } from 'vitest';
import { interpolate, detectVariables } from './variables';

describe('variables', () => {
  it('detects single and multiple template variables', () => {
    const template = 'https://api.example.com/users/{{ userId }}/posts/{{postId}}?filter={{active}}';
    const detected = detectVariables({ template });

    expect(detected).toEqual(['userId', 'postId', 'active']);
  });

  it('deduplicates detected variable names', () => {
    const template = '{{ token }} and {{ token }} again';
    const detected = detectVariables({ template });

    expect(detected).toEqual(['token']);
  });

  it('interpolates known variables and leaves unknown ones intact', () => {
    const template = 'Hello, {{ name }}! Your order {{ order_id }} is {{ status }}.';
    const variables = {
      name: 'Alice',
      order_id: '12345',
    };

    const result = interpolate({ template, variables });

    expect(result).toBe('Hello, Alice! Your order 12345 is {{ status }}.');
  });

  it('handles spaces and trim correctly inside double curly braces', () => {
    const template = 'Bearer {{   bearer_token   }}';
    const variables = { bearer_token: 'secret-xyz' };

    expect(interpolate({ template, variables })).toBe('Bearer secret-xyz');
  });
});
