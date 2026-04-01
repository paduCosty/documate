import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import { route } from 'ziggy-js';
import { Ziggy } from './ziggy';

window.route = route;
window.Ziggy = Ziggy;
