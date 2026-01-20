import { onMounted, onUnmounted, ref } from 'vue';

export function useNavbar() {
  // -------------------- Reactive State --------------------
  const isScrolled = ref(false);
  const activeSection = ref<string>('home');
  const observers = ref<IntersectionObserver[]>([]);
  const isMenuOpen = ref(false);

  // -------------------- Section IDs --------------------
  const sectionIds = [
    'about',
    'solutions',
    'franchise',
    'services',
    'technology',
    'sustainability',
    'partners',
    'news',
    'careers',
    'testimonials',
    'contact',
  ] as const;

  // -------------------- Helpers --------------------
  const getScrollMargin = (el: HTMLElement): number => {
    const style = window.getComputedStyle(el);
    const scrollMarginTop = parseInt(style.scrollMarginTop || '0', 10);
    return isNaN(scrollMarginTop) ? 0 : scrollMarginTop;
  };

  let currentSection = '';

  // -------------------- Intersection Observer --------------------
  const handleIntersection: IntersectionObserverCallback = (entries) => {
    entries.forEach((entry) => {
      const target = entry.target as HTMLElement;
      const id = target.id;

      if (entry.isIntersecting && entry.intersectionRatio > 0) {
        currentSection = id;
        activeSection.value = id;
      }

      if (
        !entry.isIntersecting &&
        entry.boundingClientRect.top > 0 &&
        currentSection === id
      ) {
        const currentIndex = sectionIds.indexOf(
          id as (typeof sectionIds)[number],
        );
        if (currentIndex > 0) {
          activeSection.value = sectionIds[currentIndex - 1];
        } else {
          activeSection.value = 'home';
        }
      }
    });

    const firstSection = document.getElementById(sectionIds[0]);
    if (firstSection && window.scrollY < firstSection.offsetTop - 100) {
      activeSection.value = 'home';
    }
  };

  // -------------------- Lifecycle Hooks --------------------
  let handleHeaderScroll: () => void;

  onMounted(() => {
    handleHeaderScroll = () => {
      isScrolled.value = window.scrollY > 50;
    };
    window.addEventListener('scroll', handleHeaderScroll);

    // Create observer for each section using its scroll margin
    sectionIds.forEach((id) => {
      const target = document.getElementById(id);
      if (!target) return;

      const marginTop = getScrollMargin(target);
      const rootMargin = `-${marginTop}px 0px -${
        window.innerHeight - marginTop - 10
      }px 0px`;

      const observer = new IntersectionObserver(handleIntersection, {
        root: null,
        rootMargin,
        threshold: [0, 0.25, 0.5, 0.75, 1],
      });

      observer.observe(target);
      observers.value.push(observer);
    });
  });

  onUnmounted(() => {
    window.removeEventListener('scroll', handleHeaderScroll);
    observers.value.forEach((obs) => obs.disconnect());
  });

  // -------------------- Click Handler --------------------
  const handleClick = (id: string) => {
    activeSection.value = id;
    isMenuOpen.value = false;
  };

  return {
    isScrolled,
    activeSection,
    isMenuOpen,
    sectionIds,
    handleClick,
  };
}
