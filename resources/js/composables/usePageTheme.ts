import { router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

/**
 * Force a specific theme for a page, bypassing user preferences
 * Automatically restores user's saved theme when leaving the page
 */
export function usePageTheme(forcedTheme: 'light' | 'dark') {
  let isPageThemeActive = false;
  let removeBeforeListener: (() => void) | null = null;
  let currentComponent: string | null = null;

  const applyForcedTheme = () => {
    isPageThemeActive = true;
    document.documentElement.classList.toggle('dark', forcedTheme === 'dark');
  };

  const restoreUserTheme = () => {
    if (!isPageThemeActive) return;

    isPageThemeActive = false;
    const savedAppearance = (localStorage.getItem('appearance') ||
      'system') as Appearance;

    if (savedAppearance === 'system') {
      const prefersDark = window.matchMedia(
        '(prefers-color-scheme: dark)',
      ).matches;
      document.documentElement.classList.toggle('dark', prefersDark);
    } else {
      document.documentElement.classList.toggle(
        'dark',
        savedAppearance === 'dark',
      );
    }
  };

  onMounted(() => {
    applyForcedTheme();

    // Store the current page component name
    const page = usePage();
    currentComponent = page.component;

    // Listen for navigation BEFORE it happens
    // Only restore theme if we're actually changing pages
    removeBeforeListener = router.on('before', (event) => {
      // Check if the destination page is different from current page
      if (
        event.detail.visit.url.href !== window.location.href &&
        currentComponent !== page.component
      ) {
        restoreUserTheme();
      }
    });
  });

  onBeforeUnmount(() => {
    // Remove the event listener if it exists
    if (removeBeforeListener) {
      removeBeforeListener();
    }
    restoreUserTheme();
  });
}
