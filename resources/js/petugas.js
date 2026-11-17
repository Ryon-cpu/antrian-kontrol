export function createQueueDashboardState(config = {}) {
    return {
        authMode: config.authMode || 'session',
        staffName: config.staffName || '',
        userRole: '',
        token: null,
        tokenKey: 'antrian_staff_token',
        isLoggedIn: config.authMode === 'session',
        loginLoading: false,
        loginForm: {
            username: '',
            password: '',
        },

        // Data state pre-hydrated from config if provided
        practice: config.initialPractice || {
            state: 'tutup',
            is_open: false,
            message: 'Memuat status...',
        },
        nowServing: config.initialNowServing || '--',
        entries: config.initialEntries || [],
        busy: false,
        soundEnabled: true,

        // Move modal state
        moveModalOpen: false,
        moveTargetEntry: null,
        moveSteps: 2,

        // Cancel modal state
        cancelModalOpen: false,
        cancelTargetEntry: null,

        // Doctor & Clinic Info modal state
        doctorModalOpen: false,
        savingDoctorInfo: false,
        doctorForm: {
            place_name: config.initialDoctorData?.place_name || '',
            place_address: config.initialDoctorData?.place_address || '',
            place_description: config.initialDoctorData?.place_description || '',
            practice_hours_note: config.initialDoctorData?.practice_hours_note || '',
            doctor_name: config.initialDoctorData?.doctor_name || '',
            doctor_title: config.initialDoctorData?.doctor_title || '',
            doctor_bio: config.initialDoctorData?.doctor_bio || '',
        },
        achievementsList: config.initialAchievements || [],

        // Feedback state
        feedback: {
            show: false,
            message: '',
            isError: false,
        },

        // Audio Bell Synthesizer (Chime Bell)
        playBell() {
            if (!this.soundEnabled) return;
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const now = ctx.currentTime;

                // Nada 1: D5
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, now);
                gain1.gain.setValueAtTime(0.3, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.6);

                // Nada 2: A4
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(440, now + 0.22);
                gain2.gain.setValueAtTime(0.35, now + 0.22);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.85);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.22);
                osc2.stop(now + 0.85);
            } catch (e) {
                // Ignore audio context error if browser blocks autoplay
            }
        },

        get staffDisplay() {
            if (!this.staffName) return '';
            return this.userRole ? `${this.staffName} (${this.userRole})` : this.staffName;
        },

        get waitingCount() {
            return this.entries.filter((e) => e.status === 'waiting').length;
        },

        get calledCount() {
            return this.entries.filter((e) => e.status === 'called').length;
        },

        get activeEntry() {
            return this.entries.find((e) => e.status === 'called') || null;
        },

        get canCallNext() {
            if (this.busy) return false;
            return this.entries.some((e) => e.status === 'waiting' || e.status === 'called');
        },

        showFeedback(message, isError = false) {
            this.feedback.message = message;
            this.feedback.isError = isError;
            this.feedback.show = true;
        },

        hideFeedback() {
            this.feedback.show = false;
        },

        async api(path, options = {}) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(`/api/${path}`, {
                ...options,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    ...(this.token ? { Authorization: `Bearer ${this.token}` } : {}),
                    ...options.headers,
                },
            });

            const data = response.status === 204 ? {} : await response.json();

            if (response.status === 401) {
                if (this.authMode === 'token') {
                    this.clearSession();
                }
                throw new Error(data.message ?? 'Sesi telah berakhir. Silakan masuk kembali.');
            }

            if (!response.ok) {
                throw new Error(data.message ?? Object.values(data.errors ?? {}).flat()[0] ?? 'Permintaan belum berhasil.');
            }

            return data;
        },

        clearSession() {
            this.token = null;
            this.isLoggedIn = false;
            this.userRole = '';
            window.sessionStorage.removeItem(this.tokenKey);
        },

        async loadUserProfile() {
            try {
                const { user } = await this.api('auth/me');
                if (!['petugas', 'admin'].includes(user.role)) {
                    if (this.authMode === 'token') this.clearSession();
                    this.showFeedback('Akun ini tidak memiliki akses dashboard petugas.', true);
                    return false;
                }
                this.staffName = user.name;
                this.userRole = user.role;
                return true;
            } catch (error) {
                if (this.authMode === 'token') {
                    this.clearSession();
                }
                return false;
            }
        },

        async handleLogin() {
            this.loginLoading = true;
            this.hideFeedback();

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    },
                    body: JSON.stringify(this.loginForm),
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message ?? Object.values(data.errors ?? {}).flat()[0] ?? 'Tidak dapat masuk.');
                }

                if (!['petugas', 'admin'].includes(data.user.role)) {
                    throw new Error('Akun ini tidak memiliki hak akses petugas atau admin.');
                }

                this.token = data.token;
                window.sessionStorage.setItem(this.tokenKey, data.token);
                this.staffName = data.user.name;
                this.userRole = data.user.role;
                this.isLoggedIn = true;

                // Reset form login
                this.loginForm.username = '';
                this.loginForm.password = '';

                this.showFeedback(`Selamat datang, ${data.user.name}.`);
            } catch (error) {
                this.showFeedback(error.message, true);
            } finally {
                this.loginLoading = false;
            }
        },

        async handleLogout() {
            if (this.authMode === 'token') {
                try {
                    await this.api('auth/logout', { method: 'POST' });
                } catch (e) {
                    // Ignore logout error
                }
                this.clearSession();
                this.showFeedback('Anda telah keluar.');
            }
        },

        async togglePractice() {
            if (this.busy || this.practice.state === 'libur') return;
            this.busy = true;
            this.hideFeedback();

            try {
                const action = this.practice.is_open ? 'close' : 'open';
                await this.api(`petugas/practice/${action}`, { method: 'POST' });
                await this.refreshQueue();
                this.showFeedback(
                    this.practice.is_open
                        ? 'Praktek dibuka. Pendaftaran antrian pasien aktif.'
                        : 'Praktek ditutup. Pendaftaran dinonaktifkan.'
                );
            } catch (error) {
                this.showFeedback(error.message, true);
            } finally {
                this.busy = false;
            }
        },

        async callNext() {
            if (!this.canCallNext) return;
            this.busy = true;
            this.hideFeedback();

            try {
                const res = await this.api('petugas/queue/call', { method: 'POST' });
                this.playBell();
                await this.refreshQueue();
                const num = res.data?.number_label ?? '';
                const count = res.data?.call_count ?? 1;
                this.showFeedback(`Memanggil Antrian ${num} (Panggilan ke-${count}).`);
            } catch (error) {
                this.showFeedback(error.message, true);
            } finally {
                this.busy = false;
            }
        },

        async callSpecific(entry) {
            if (this.busy) return;
            this.busy = true;
            this.hideFeedback();

            try {
                const res = await this.api('petugas/queue/call', {
                    method: 'POST',
                    body: JSON.stringify({ entry_id: entry.id }),
                });
                this.playBell();
                await this.refreshQueue();
                const count = res.data?.call_count ?? 1;
                this.showFeedback(`Memanggil Antrian ${entry.number_label} (Panggilan ke-${count}).`);
            } catch (error) {
                this.showFeedback(error.message, true);
            } finally {
                this.busy = false;
            }
        },

        async doneEntry(entry) {
            if (this.busy) return;
            this.busy = true;
            this.hideFeedback();

            try {
                await this.api(`petugas/queue/${entry.id}/done`, { method: 'POST' });
                await this.refreshQueue();
                this.showFeedback(`Antrian ${entry.number_label} (${entry.patient?.name}) selesai diperiksa.`);
            } catch (error) {
                this.showFeedback(error.message, true);
            } finally {
                this.busy = false;
            }
        },

        async skipEntry(entry) {
            if (this.busy) return;
            this.busy = true;
            this.hideFeedback();

            try {
                await this.api(`petugas/queue/${entry.id}/skip`, { method: 'POST' });
                await this.refreshQueue();
                this.showFeedback(`Antrian ${entry.number_label} dilewati dan digeser ke bawah.`);
            } catch (error) {
                this.showFeedback(error.message, true);
            } finally {
                this.busy = false;
            }
        },

        openCancelModal(entry) {
            this.cancelTargetEntry = entry;
            this.cancelModalOpen = true;
        },

        closeCancelModal() {
            this.cancelModalOpen = false;
            this.cancelTargetEntry = null;
        },

        cancelEntry(entry) {
            this.openCancelModal(entry);
        },

        async deleteEntry(entry) {
            if (this.busy) return;
            this.busy = true;
            this.hideFeedback();
            try {
                await this.api(`petugas/queue/${entry.id}`, { method: 'DELETE' });
                this.entries = this.entries.filter(e => e.id !== entry.id);
                this.showFeedback(`Data antrian ${entry.number_label} (${entry.patient?.name}) berhasil dihapus.`);
            } catch (error) {
                this.showFeedback(error.message, true);
            } finally {
                this.busy = false;
            }
        },

        async confirmCancel() {
            if (!this.cancelTargetEntry || this.busy) return;
            const entry = this.cancelTargetEntry;
            this.busy = true;
            this.hideFeedback();

            try {
                await this.api(`petugas/queue/${entry.id}/cancel`, { method: 'POST' });
                await this.refreshQueue();
                this.showFeedback(`Antrian ${entry.number_label} (${entry.patient?.name}) berhasil dibatalkan.`);
                this.closeCancelModal();
            } catch (error) {
                this.showFeedback(error.message, true);
            } finally {
                this.busy = false;
            }
        },

        openMoveModal(entry) {
            this.moveTargetEntry = entry;
            this.moveSteps = 2;
            this.moveModalOpen = true;
        },

        closeMoveModal() {
            this.moveModalOpen = false;
            this.moveTargetEntry = null;
        },

        async confirmMove() {
            if (!this.moveTargetEntry) return;
            this.busy = true;
            this.hideFeedback();

            try {
                await this.api(`petugas/queue/${this.moveTargetEntry.id}/move`, {
                    method: 'POST',
                    body: JSON.stringify({ steps: parseInt(this.moveSteps, 10) }),
                });
                await this.refreshQueue();
                this.showFeedback(
                    `Antrian ${this.moveTargetEntry.number_label} digeser ${this.moveSteps} posisi ke bawah.`
                );
                this.closeMoveModal();
            } catch (error) {
                this.showFeedback(error.message, true);
            } finally {
                this.busy = false;
            }
        },

        async init() {
            if (this.authMode === 'token') {
                this.token = window.sessionStorage.getItem(this.tokenKey);
                if (this.token) {
                    this.isLoggedIn = true;
                    await this.loadUserProfile();
                }
            } else {
                this.isLoggedIn = true;
                await this.loadUserProfile();
            }
        },
    };
}
