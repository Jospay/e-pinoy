import axios, { AxiosRequestConfig } from 'axios'; // Import axios
import { ref } from 'vue';

/**
 * A composable to manage the state and logic for a details modal.
 * @param {object} config - Configuration for the modal.
 * @param {string} [config.baseUrl] - A simple base URL for GET requests.
 * @param {Function} [config.fetcher] - A custom async function to fetch data.
 */
export function useDetailsModal<TData = unknown>({
  // Use generic for data type
  baseUrl,
  fetcher,
}: {
  baseUrl?: string;
  fetcher?: (...args: any[]) => Promise<any>;
}) {
  const isOpen = ref(false);
  const isLoading = ref(false);
  const isError = ref(false);
  const data = ref<TData | null>(null); // Type the data ref

  // Define the actual fetch function based on provided config
  const fetchData = async (...args: any[]) => {
    if (fetcher) {
      return fetcher(...args);
    }
    if (baseUrl) {
      let config: AxiosRequestConfig = {};
      const pathSegments = [...args];

      // SAFE CHECK: If the last argument is an object containing 'params' or 'headers',
      // treat it as Axios Config and remove it from the URL path construction.
      if (pathSegments.length > 0) {
        const lastArg = pathSegments[pathSegments.length - 1];
        if (
          typeof lastArg === 'object' &&
          lastArg !== null &&
          (lastArg.params || lastArg.headers)
        ) {
          config = pathSegments.pop();
        }
      }

      // A simple convention for baseUrl: join args with a slash.
      const endpoint = [baseUrl, ...pathSegments].join('/');
      return axios.get(endpoint, config); // Use axios.get
    }
    throw new Error(
      "useDetailsModal requires a 'baseUrl' or a 'fetcher' function.",
    );
  };

  const open = async (...args: any[]) => {
    let onOpenCallback: (() => void) | null = null;
    if (typeof args[args.length - 1] === 'function') {
      onOpenCallback = args.pop() as () => void;
    }

    isLoading.value = true;
    isOpen.value = true;
    data.value = null;
    isError.value = false;

    if (onOpenCallback) {
      onOpenCallback();
    }

    try {
      const response = await fetchData(...args);
      data.value = response.data.data;
    } catch (error) {
      console.error('Error fetching details:', error);
      isError.value = true;
    } finally {
      //await new Promise((resolve) => setTimeout(resolve, 500));
      isLoading.value = false;
    }
  };

  const close = () => {
    isOpen.value = false;
  };

  return {
    isOpen,
    isLoading,
    isError,
    data,
    open,
    close,
  };
}
