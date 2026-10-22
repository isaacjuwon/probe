import { generateId } from '$lib/utils';

export type GeneratorType = 'uuid' | 'timestamp' | 'date' | 'email' | 'name' | 'number' | 'boolean' | 'lorem';

const SAMPLE_NAMES = [
  'Alex Taylor',
  'Jordan Lee',
  'Morgan Smith',
  'Taylor Brooks',
  'Casey Rivera',
  'Riley Johnson',
  'Sam Martinez',
  'Jamie Clark',
];

const SAMPLE_WORDS = [
  'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
  'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
];

export function generateValue(type: GeneratorType): string {
  switch (type) {
    case 'uuid':
      return generateId();
    case 'timestamp':
      return new Date().toISOString();
    case 'date':
      return new Date().toISOString().split('T')[0];
    case 'email': {
      const rand = Math.random().toString(36).substring(2, 7);
      return `user.${rand}@example.com`;
    }
    case 'name': {
      const index = Math.floor(Math.random() * SAMPLE_NAMES.length);
      return SAMPLE_NAMES[index];
    }
    case 'number':
      return String(Math.floor(Math.random() * 1000) + 1);
    case 'boolean':
      return Math.random() > 0.5 ? 'true' : 'false';
    case 'lorem': {
      const count = 4;
      const words = Array.from({ length: count }, () => {
        const idx = Math.floor(Math.random() * SAMPLE_WORDS.length);
        return SAMPLE_WORDS[idx];
      });
      return words.join(' ');
    }
  }
}

export interface GeneratorOption {
  readonly type: GeneratorType;
  readonly label: string;
  readonly description: string;
}

export const GENERATOR_OPTIONS: readonly GeneratorOption[] = [
  { type: 'uuid', label: 'UUID', description: 'v4 Unique Identifier' },
  { type: 'timestamp', label: 'ISO Timestamp', description: 'Current ISO 8601 DateTime' },
  { type: 'date', label: 'Date', description: 'Current YYYY-MM-DD' },
  { type: 'email', label: 'Email', description: 'Random test email address' },
  { type: 'name', label: 'Full Name', description: 'Random realistic name' },
  { type: 'number', label: 'Number', description: 'Random positive integer' },
  { type: 'boolean', label: 'Boolean', description: 'true or false' },
  { type: 'lorem', label: 'Text', description: 'Short random lorem text' },
];
