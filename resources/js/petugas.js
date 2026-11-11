const app = document.getElementById('staff-app');

if (app) {
    const tokenKey = 'antrian_staff_token';
    const authMode = app.dataset.authMode;
    const usesWebSession = authMode === 'session';
    const loginPanel = document.getElementById('login-panel');
    const dashboardPanel = document.getElementById('dashboard-panel');
    const loginForm = document.getElementById('login-form');
    const loginButton = document.getElementById('login-button');
    const logoutButton = document.getElementById('logout-button');
    const feedback = document.getElementById('staff-feedback');
    const practiceButton = document.getElementById('practice-button');
    const callButton = document.getElementById('call-button');
    const queueList = document.getElementById('queue-list');
    const emptyQueue = document.getElementById('empty-queue');
    const statusFilter = document.getElementById('status-filter');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    let token = usesWebSession ? null : window.sessionStorage.getItem(tokenKey);
    let entries = [];
    let busy = false;

    const statusLabels = {
        waiting: 'Menunggu',
        called: 'Sedang dipanggil',
        done: 'Selesai',
        cancelled: 'Dibatalkan',
    };

    const showFeedback = (message, isError = false) => {
        feedback.textContent = message;
        feedback.className = 'rounded-md border px-4 py-3 text-base';
        feedback.classList.add(...(isError
            ? ['border-red-300', 'bg-red-50', 'text-red-800']
            : ['border-blue-200', 'bg-blue-50', 'text-blue-900']));
    };

    const hideFeedback = () => feedback.classList.add('hidden');

    const setBusy = (value) => {
        busy = value;
        callButton.disabled = value || (!entries.some((entry) => entry.status === 'waiting')
            && !entries.some((entry) => entry.status === 'called'));
        practiceButton.disabled = value;
        if (logoutButton) {
            logoutButton.disabled = value;
        }
    };

    const api = async (path, options = {}) => {
        const response = await fetch(`/api/${path}`, {
            ...options,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...options.headers,
            },
        });
        const data = response.status === 204 ? {} : await response.json();

        if (response.status === 401) {
            if (!usesWebSession) {
                clearSession();
            }
            throw new Error(data.message ?? 'Sesi telah berakhir. Silakan masuk kembali.');
        }

        if (!response.ok) {
            throw new Error(data.message ?? Object.values(data.errors ?? {}).flat()[0] ?? 'Permintaan belum berhasil.');
        }

        return data;
    };

    const clearSession = () => {
        token = null;
        window.sessionStorage.removeItem(tokenKey);
        loginPanel?.classList.remove('hidden');
        dashboardPanel.classList.add('hidden');
        dashboardPanel.classList.remove('flex');
        logoutButton?.classList.add('hidden');
    };

    const setPractice = (practice) => {
        const open = practice.is_open;
        document.getElementById('practice-status').textContent = practice.message;
        document.getElementById('practice-status').className = `text-base font-semibold ${open ? 'text-green-700' : 'text-gray-700'}`;
        practiceButton.textContent = open ? 'Tutup praktek' : 'Buka praktek';
        practiceButton.dataset.open = String(open);
        practiceButton.disabled = busy || practice.state === 'libur';
    };

    const statusBadgeClass = (status) => ({
        waiting: 'bg-yellow-100 text-yellow-900',
        called: 'bg-blue-100 text-blue-900',
        done: 'bg-green-100 text-green-900',
        cancelled: 'bg-gray-200 text-gray-800',
    }[status] ?? 'bg-gray-100 text-gray-800');

    const createActionButton = (label, action, style = 'secondary') => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.dataset.action = action;
        button.className = style === 'primary'
            ? 'min-h-12 rounded-md bg-blue-700 px-4 py-2 text-base font-bold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-400'
            : style === 'danger'
                ? 'min-h-12 rounded-md border border-red-300 bg-white px-4 py-2 text-base font-semibold text-red-800 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50'
                : 'min-h-12 rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
        return button;
    };

    const renderEntry = (entry) => {
        const card = document.createElement('article');
        card.className = 'flex flex-col gap-4 rounded-lg border border-gray-200 p-4 sm:p-5';

        const heading = document.createElement('div');
        heading.className = 'flex flex-wrap items-center justify-between gap-3';
        const title = document.createElement('h3');
        title.className = 'text-2xl font-bold text-gray-900';
        title.textContent = `Antrian ${entry.number_label}`;
        const badge = document.createElement('span');
        badge.className = `rounded-full px-3 py-1 text-sm font-bold ${statusBadgeClass(entry.status)}`;
        badge.textContent = statusLabels[entry.status] ?? entry.status;
        heading.append(title, badge);
        card.append(heading);

        const patient = document.createElement('div');
        patient.className = 'grid grid-cols-1 gap-2 text-base leading-relaxed text-gray-700 sm:grid-cols-2';
        const fields = [
            ['Nama', entry.patient.name],
            ['Usia', `${entry.patient.age} tahun`],
            ['Alamat', entry.patient.address],
            ['Keterangan sakit', entry.patient.complaint],
            ['Jumlah panggilan', `${entry.call_count} kali`],
        ];
        for (const [label, value] of fields) {
            const line = document.createElement('p');
            const fieldLabel = document.createElement('span');
            fieldLabel.className = 'font-semibold text-gray-900';
            fieldLabel.textContent = `${label}: `;
            line.append(fieldLabel, document.createTextNode(String(value ?? '-')));
            patient.append(line);
        }
        card.append(patient);

        const actions = document.createElement('div');
        actions.className = 'flex flex-wrap gap-3';
        if (entry.status === 'called') {
            actions.append(
                createActionButton('Selesai', 'done', 'primary'),
                createActionButton('Lewati', 'skip'),
            );
        }
        if (['waiting', 'called'].includes(entry.status)) {
            if (entry.status === 'waiting') {
                actions.append(createActionButton('Geser ke bawah', 'move'));
            }
            actions.append(createActionButton('Batalkan antrian', 'cancel', 'danger'));
        }
        if (actions.childElementCount > 0) {
            card.append(actions);
        }

        return card;
    };

    const renderQueue = (data) => {
        entries = data.data;
        document.getElementById('now-serving').textContent = data.now_serving ?? '--';
        document.getElementById('waiting-count').textContent = String(entries.filter((entry) => entry.status === 'waiting').length);
        document.getElementById('registered-count').textContent = String(entries.length);
        setPractice(data.practice);

        const selectedStatus = statusFilter.value;
        const visibleEntries = selectedStatus === 'all'
            ? entries
            : entries.filter((entry) => entry.status === selectedStatus);

        queueList.replaceChildren(...visibleEntries.map(renderEntry));
        emptyQueue.classList.toggle('hidden', visibleEntries.length > 0);
        setBusy(false);
    };

    const refreshQueue = async () => {
        if ((!usesWebSession && !token) || busy) {
            return;
        }

        try {
            const data = await api('petugas/queue');
            renderQueue(data);
            hideFeedback();
        } catch (error) {
            if (usesWebSession || token) {
                showFeedback(error.message, true);
            }
        }
    };

    const showDashboard = async () => {
        try {
            const { user } = await api('auth/me');
            if (!['petugas', 'admin'].includes(user.role)) {
                if (!usesWebSession) {
                    clearSession();
                }
                showFeedback('Akun ini tidak memiliki akses dashboard petugas.', true);
                return;
            }
            document.getElementById('staff-name').textContent = `${user.name} (${user.role})`;
            loginPanel?.classList.add('hidden');
            dashboardPanel.classList.remove('hidden');
            dashboardPanel.classList.add('flex');
            logoutButton?.classList.remove('hidden');
            await refreshQueue();
        } catch (error) {
            if (usesWebSession || token) {
                showFeedback(error.message, true);
            }
        }
    };

    loginForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!loginForm.reportValidity()) {
            return;
        }

        loginButton.disabled = true;
        loginButton.textContent = 'Memeriksa akun...';
        hideFeedback();
        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: JSON.stringify(Object.fromEntries(new FormData(loginForm).entries())),
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message ?? Object.values(data.errors ?? {}).flat()[0] ?? 'Tidak dapat masuk.');
            }
            if (!['petugas', 'admin'].includes(data.user.role)) {
                throw new Error('Akun ini tidak memiliki akses dashboard petugas.');
            }

            token = data.token;
            window.sessionStorage.setItem(tokenKey, token);
            loginForm.reset();
            await showDashboard();
        } catch (error) {
            showFeedback(error.message, true);
        } finally {
            loginButton.disabled = false;
            loginButton.textContent = 'Masuk';
        }
    });

    logoutButton?.addEventListener('click', async () => {
        try {
            if (token) {
                await api('auth/logout', { method: 'POST' });
            }
        } catch (error) {
            showFeedback(error.message, true);
        } finally {
            clearSession();
            showFeedback('Anda sudah keluar.');
        }
    });

    practiceButton.addEventListener('click', async () => {
        const action = practiceButton.dataset.open === 'true' ? 'close' : 'open';
        setBusy(true);
        try {
            const data = await api(`petugas/practice/${action}`, { method: 'POST' });
            setPractice(data.practice);
            showFeedback(data.practice.message);
        } catch (error) {
            showFeedback(error.message, true);
        } finally {
            setBusy(false);
            await refreshQueue();
        }
    });

    callButton.addEventListener('click', async () => {
        setBusy(true);
        try {
            await api('petugas/queue/call', { method: 'POST', body: JSON.stringify({}) });
            showFeedback('Panggilan berhasil dikirim.');
            await refreshQueue();
        } catch (error) {
            showFeedback(error.message, true);
        } finally {
            setBusy(false);
            await refreshQueue();
        }
    });

    queueList.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button || busy) {
            return;
        }

        const card = button.closest('article');
        const number = card.querySelector('h3').textContent.replace('Antrian ', '');
        const entry = entries.find((item) => item.number_label === number);
        if (!entry) {
            showFeedback('Data antrian tidak ditemukan. Segarkan daftar lalu coba lagi.', true);
            return;
        }

        const action = button.dataset.action;
        if (action === 'cancel' && !window.confirm(`Batalkan antrian ${number}?`)) {
            return;
        }

        setBusy(true);
        try {
            const options = action === 'move'
                ? { method: 'POST', body: JSON.stringify({}) }
                : { method: 'POST' };
            await api(`petugas/queue/${entry.id}/${action}`, options);
            showFeedback(`Aksi "${button.textContent}" berhasil untuk antrian ${number}.`);
            await refreshQueue();
        } catch (error) {
            showFeedback(error.message, true);
        } finally {
            setBusy(false);
            await refreshQueue();
        }
    });

    statusFilter.addEventListener('change', () => {
        if (usesWebSession || token) {
            refreshQueue();
        }
    });

    if (usesWebSession || token) {
        showDashboard();
    }
    window.setInterval(refreshQueue, 5000);
}
