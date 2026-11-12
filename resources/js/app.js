

import Alpine from 'alpinejs';
import { queueDashboard } from './petugas';

window.Alpine = Alpine;
Alpine.data('queueDashboard', queueDashboard);

Alpine.start();
