import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Expose a null Echo reference so callers can safely feature-detect.
window.Echo = null;
