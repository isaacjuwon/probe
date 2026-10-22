export interface Command {
  readonly id: string;
  readonly title: string;
  readonly category?: string;
  readonly shortcut?: string;
  readonly run: () => void | Promise<void>;
}
