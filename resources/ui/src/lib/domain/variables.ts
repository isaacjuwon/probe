export interface InterpolateOptions {
  readonly template: string;
  readonly variables: Readonly<Record<string, string>>;
}

/**
 * Interpreter: Replaces {{key}} variables in the template string with their values.
 */
export function interpolate(options: InterpolateOptions): string {
  const { template, variables } = options;

  return template.replace(/\{\{\s*([a-zA-Z0-9_\-]+)\s*\}\}/g, (match, key) => {
    return Object.prototype.hasOwnProperty.call(variables, key) ? variables[key] : match;
  });
}

/**
 * Extracts unique variable names referenced in the template.
 */
export function detectVariables(options: { readonly template: string }): string[] {
  const matches = options.template.matchAll(/\{\{\s*([a-zA-Z0-9_\-]+)\s*\}\}/g);
  const result = new Set<string>();
  for (const m of matches) {
    if (m[1]) {
      result.add(m[1]);
    }
  }
  return Array.from(result);
}
