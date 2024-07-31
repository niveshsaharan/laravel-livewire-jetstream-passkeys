import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import AlpineUi from '@alpinejs/ui';


Alpine.plugin(AlpineUi)
