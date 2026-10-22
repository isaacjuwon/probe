<script lang="ts">
  import { onMount, onDestroy } from 'svelte';
  import { EditorView, basicSetup } from 'codemirror';
  import { EditorState } from '@codemirror/state';
  import { json } from '@codemirror/lang-json';
  import { cn } from '$lib/utils';

  export interface Props {
    value: string;
    readonly readonly?: boolean;
    readonly language?: 'json' | 'text';
    readonly class?: string;
    readonly onChange?: (options: { readonly value: string }) => void;
  }

  const {
    value,
    readonly = false,
    language = 'json',
    class: className,
    onChange,
  }: Props = $props();

  let container: HTMLDivElement | null = $state(null);
  let view: EditorView | null = null;

  const ferriteEditorTheme = EditorView.theme({
    '&': {
      backgroundColor: 'transparent',
      color: 'var(--foreground)',
      height: '100%',
      fontSize: '12px',
      fontFamily: 'monospace',
    },
    '.cm-content': {
      caretColor: 'var(--primary)',
      padding: '8px 0',
    },
    '.cm-gutters': {
      backgroundColor: 'transparent',
      color: 'var(--muted-foreground)',
      borderRight: '1px solid var(--border)',
    },
    '&.cm-focused .cm-cursor': {
      borderLeftColor: 'var(--primary)',
    },
  });

  onMount(() => {
    if (!container) return;

    const extensions = [
      basicSetup,
      ferriteEditorTheme,
      EditorView.updateListener.of((update) => {
        if (update.docChanged && onChange) {
          onChange({ value: update.state.doc.toString() });
        }
      }),
    ];

    if (language === 'json') {
      extensions.push(json());
    }

    if (readonly) {
      extensions.push(EditorState.readOnly.of(true));
    }

    const state = EditorState.create({
      doc: value,
      extensions,
    });

    view = new EditorView({
      state,
      parent: container,
    });
  });

  $effect(() => {
    if (view && value !== view.state.doc.toString()) {
      view.dispatch({
        changes: { from: 0, to: view.state.doc.length, insert: value },
      });
    }
  });

  onDestroy(() => {
    view?.destroy();
  });
</script>

<div
  bind:this={container}
  class={cn('h-full w-full overflow-hidden rounded-md border border-border bg-card/40', className)}
></div>
