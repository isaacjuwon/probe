export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE' | 'OPTIONS' | 'HEAD';

export type AuthType = 'none' | 'bearer' | 'basic' | 'api_key' | 'session' | 'sanctum_act_as';

export type FieldType = 'string' | 'integer' | 'number' | 'boolean' | 'array' | 'object' | 'file';

export interface FieldSchema {
  readonly name: string;
  readonly type: FieldType;
  readonly required: boolean;
  readonly nullable: boolean;
  readonly rules: readonly string[];
  readonly constraints: Readonly<Record<string, unknown>>;
  readonly example: unknown;
  readonly description?: string | null;
}

export interface Endpoint {
  readonly method: HttpMethod;
  readonly uri: string;
  readonly name?: string | null;
  readonly action?: string | null;
  readonly middleware: readonly string[];
  readonly headers: Readonly<Record<string, string>>;
  readonly parameters: Readonly<Record<string, unknown>>;
  readonly fields: Readonly<Record<string, FieldSchema>>;
  readonly group?: string | null;
  readonly description?: string | null;
  readonly auth: AuthType;
  readonly version?: string | null;
}
