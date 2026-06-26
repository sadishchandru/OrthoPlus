import { useColorMode } from '@vueuse/core';

// Toggles the `.dark` class on <html>; persists to localStorage. shadcn tokens react.
export function useTheme() {
  const mode = useColorMode({
    selector: 'html',
    attribute: 'class',
    storageKey: 'ortho_theme',
    modes: { light: '', dark: 'dark' },
  });
  const toggle = () => { mode.value = mode.value === 'dark' ? 'light' : 'dark'; };
  return { mode, toggle };
}
