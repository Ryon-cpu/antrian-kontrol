import { createApp } from 'vue';
import { VueQueryPlugin } from '@tanstack/vue-query';
import DashboardTitle from './components/DashboardTitle.vue';
import StaffDashboard from './components/StaffDashboard.vue';

let titleApp = null;
let staffApp = null;

export function mountDashboardVue() {
    const titleRoot = document.querySelector('[data-vue-dashboard-title]');

    if (titleRoot && !titleApp) {
        titleApp = createApp(DashboardTitle, {
            title: titleRoot.dataset.title ?? '',
        });
        titleApp.mount(titleRoot);
    }

    const staffRoot = document.querySelector('[data-staff-dashboard]');

    if (staffRoot && !staffApp) {
        const config = staffRoot.dataset.dashboardConfig;

        if (!config) {
            throw new Error('Staff dashboard Vue configuration is missing.');
        }

        staffApp = createApp(StaffDashboard, {
            config: JSON.parse(config),
        });
        staffApp.use(VueQueryPlugin, {
            queryClientConfig: {
                defaultOptions: {
                    queries: {
                        refetchOnWindowFocus: false,
                        retry: false,
                    },
                },
            },
        });
        staffApp.mount(staffRoot);
    }
}

export function unmountDashboardVue() {
    if (staffApp) {
        staffApp.unmount();
        staffApp = null;
    }

    if (titleApp) {
        titleApp.unmount();
        titleApp = null;
    }
}
