<div class="relative"
    x-data="notificationCenter({
        feedUrl: @js(route('notifications.feed')),
        listUrl: @js(route('notifications.list')),
        readUrl: @js(route('notifications.read-selected')),
        archiveUrl: @js(route('notifications.archive-selected')),
        restoreUrl: @js(route('notifications.restore-selected')),
        singleReadUrl: @js(url('/notifications')),
    })"
    @click.outside="if (!modalOpen) open = false"
    @keydown.escape.window="modalOpen ? closeModal() : open = false">
    <button type="button" @click="open = !open; if (open) loadPreview()"
        :aria-expanded="open.toString()" aria-label="Notifications"
        class="relative grid h-11 w-11 place-items-center rounded-2xl border border-gray-200 bg-white text-gray-700 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-[#b22222] focus:outline-none focus-visible:ring-2 focus-visible:ring-red-700">
        <i class="fa-regular fa-bell text-lg" aria-hidden="true"></i>
        <span x-show="unread > 0" x-cloak x-text="unread > 99 ? '99+' : unread"
            class="absolute -right-1 -top-1 min-w-[19px] rounded-full bg-red-700 px-1 text-center text-[11px] font-bold leading-[19px] text-white"></span>
    </button>

    <div x-show="open" x-cloak x-transition.opacity
        class="absolute right-0 z-50 mt-3 flex max-h-[min(520px,80vh)] w-[min(460px,calc(100vw-24px))] flex-col overflow-hidden rounded-[22px] border border-gray-100 bg-white p-5 shadow-[0_20px_55px_rgba(15,23,42,0.18)]">
        <header class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-extrabold text-gray-900">Notifications</h2>
            <div class="flex items-center gap-2">
                <button type="button" @click="loadPreview()" aria-label="Refresh notifications" title="Refresh"
                    class="grid h-10 w-10 place-items-center rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50">
                    <i class="fa-solid fa-arrows-rotate" :class="loading ? 'animate-spin' : ''" aria-hidden="true"></i>
                </button>
                <button type="button" @click="open = false" aria-label="Close notifications"
                    class="grid h-10 w-10 place-items-center rounded-xl text-red-600 hover:bg-red-50">
                    <i class="fa-solid fa-xmark text-xl" aria-hidden="true"></i>
                </button>
            </div>
        </header>

        <p x-show="error" x-text="error" role="alert"
            class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"></p>

        <div class="mt-4 min-h-[150px] flex-1 space-y-2 overflow-y-auto">
            <template x-for="item in preview" :key="item.id">
                <button type="button" @click="openItem(item)"
                    class="flex w-full items-start gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-gray-50"
                    :class="item.read ? '' : 'bg-red-50/60'">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full"
                        :class="item.read ? 'bg-gray-300' : 'bg-red-600'"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-bold text-gray-900" x-text="item.title"></span>
                        <span class="mt-1 block line-clamp-2 text-sm text-gray-600" x-text="item.message"></span>
                        <span class="mt-1 block text-xs text-gray-400" x-text="item.time"></span>
                    </span>
                </button>
            </template>
            <div x-show="!loading && preview.length === 0" class="grid min-h-[150px] place-items-center px-4 text-center text-gray-500">
                <p>No notifications yet. Updates from across the system will appear here.</p>
            </div>
            <p x-show="loading && preview.length === 0" class="py-8 text-center text-sm text-gray-500">Loading notifications...</p>
        </div>

        <footer class="mt-4 border-t border-gray-200 pt-3">
            <button type="button" @click="openAll()"
                class="inline-flex items-center gap-2 rounded-lg px-3 py-2 font-bold text-gray-900 hover:bg-gray-50">
                <i class="fa-solid fa-list-ul text-red-700" aria-hidden="true"></i> See all
            </button>
        </footer>
    </div>

    <template x-teleport="body">
        <div x-show="modalOpen" x-cloak x-transition.opacity
            @click.self="closeModal()"
            class="fixed inset-0 z-[120] grid place-items-center bg-[rgba(17,24,39,0.62)] p-3 sm:p-6">
            <section role="dialog" aria-modal="true" aria-labelledby="allNotificationsTitle"
                class="flex max-h-[88vh] w-full max-w-[1120px] flex-col overflow-hidden rounded-[26px] bg-white shadow-[0_24px_70px_rgba(15,23,42,0.3)]">
                <header class="flex items-center justify-between border-b border-gray-200 px-5 py-5 sm:px-8">
                    <h2 id="allNotificationsTitle" class="text-xl font-extrabold text-gray-900">All Notifications</h2>
                    <button type="button" @click="closeModal()" aria-label="Close all notifications"
                        class="grid h-10 w-10 place-items-center rounded-lg text-red-600 hover:bg-red-50">
                        <i class="fa-solid fa-xmark text-xl" aria-hidden="true"></i>
                    </button>
                </header>

                <p x-show="error" x-text="error" role="alert"
                    class="mx-5 mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 sm:mx-8"></p>

                <div class="overflow-auto px-4 py-4 sm:px-8">
                    <div class="grid min-w-[680px] grid-cols-[48px_minmax(120px,0.8fr)_minmax(220px,2fr)_minmax(135px,1fr)_90px] items-center gap-3 rounded-xl bg-[#fff5f5] px-4 py-4 text-xs font-extrabold uppercase tracking-wide text-[#a32020]">
                        <input type="checkbox" aria-label="Select all notifications" :checked="allSelected"
                            @change="toggleAll($event.target.checked)"
                            class="h-5 w-5 rounded border-gray-300 text-red-700 focus:ring-red-600">
                        <span>From</span><span>Subject</span><span>Sent</span><span>Read?</span>
                    </div>

                    <div class="min-w-[680px] divide-y divide-gray-100">
                        <template x-for="item in allItems" :key="item.id">
                            <div class="grid grid-cols-[48px_minmax(120px,0.8fr)_minmax(220px,2fr)_minmax(135px,1fr)_90px] items-center gap-3 px-4 py-4 text-sm hover:bg-gray-50">
                                <input type="checkbox" :value="item.id" :checked="selected.includes(item.id)"
                                    @change="toggleItem(item.id, $event.target.checked)"
                                    :aria-label="'Select notification: ' + item.title"
                                    class="h-5 w-5 rounded border-gray-300 text-red-700 focus:ring-red-600">
                                <span class="truncate text-gray-700" x-text="item.from"></span>
                                <button type="button" @click="openItem(item)" class="min-w-0 text-left">
                                    <span class="block truncate font-bold text-gray-900" x-text="item.title"></span>
                                    <span class="mt-1 block truncate text-gray-500" x-text="item.message"></span>
                                </button>
                                <span class="text-gray-500" x-text="item.sent"></span>
                                <span class="font-semibold" :class="item.read ? 'text-green-700' : 'text-red-700'"
                                    x-text="item.read ? 'Yes' : 'No'"></span>
                            </div>
                        </template>
                    </div>

                    <p x-show="!listing && allItems.length === 0" class="py-10 text-center text-gray-500">
                        No notifications yet. Updates from across the system will appear here.
                    </p>
                    <p x-show="listing && allItems.length === 0" class="py-10 text-center text-gray-500">Loading notifications...</p>
                    <button x-show="hasMore" type="button" @click="loadMore()"
                        class="mx-auto mt-3 block rounded-lg px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-50">
                        Load more
                    </button>
                </div>

                <footer class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <button type="button" @click="toggleArchived()"
                        class="inline-flex items-center gap-2 self-start rounded-lg px-3 py-2 font-bold text-[#a32020] hover:bg-red-50">
                        <i class="fa-solid" :class="archivedView ? 'fa-inbox' : 'fa-box-archive'" aria-hidden="true"></i>
                        <span x-text="archivedView ? 'Back to notifications' : 'See archived'"></span>
                    </button>
                    <div class="flex flex-wrap justify-end gap-2">
                        <button x-show="!archivedView" type="button" @click="applySelection('read')"
                            :disabled="selected.length === 0 || busy"
                            class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-gray-200 px-4 font-bold text-[#a32020] hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <i class="fa-solid fa-check" aria-hidden="true"></i> Mark as read
                        </button>
                        <button x-show="!archivedView" type="button" @click="applySelection('archive')"
                            :disabled="selected.length === 0 || busy"
                            class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-gray-200 px-4 font-bold text-[#a32020] hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <i class="fa-solid fa-box-archive" aria-hidden="true"></i> Archive
                        </button>
                        <button x-show="archivedView" type="button" @click="applySelection('restore')"
                            :disabled="selected.length === 0 || busy"
                            class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-gray-200 px-4 font-bold text-[#a32020] hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40">
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Restore
                        </button>
                    </div>
                </footer>
            </section>
        </div>
    </template>
</div>

<script>
    function notificationCenter(cfg) {
        return {
            open: false,
            modalOpen: false,
            archivedView: false,
            unread: 0,
            preview: [],
            allItems: [],
            selected: [],
            hasMore: false,
            page: 1,
            loading: false,
            listing: false,
            busy: false,
            error: '',
            timer: null,
            visibilityHandler: null,

            async request(url, options = {}) {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        Accept: 'application/json',
                        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                        ...(options.method ? { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } : {}),
                        ...options.headers,
                    },
                });
                if (!response.ok) {
                    const body = await response.json().catch(() => ({}));
                    throw new Error(body.message || 'Unable to update notifications. Please try again.');
                }
                return response.json();
            },

            async loadPreview() {
                this.loading = true;
                this.error = '';
                try {
                    const data = await this.request(cfg.feedUrl);
                    this.unread = data.unread;
                    this.preview = data.items;
                } catch (error) {
                    this.error = error.message;
                } finally {
                    this.loading = false;
                }
            },

            async loadList(reset = false) {
                if (reset) {
                    this.page = 1;
                    this.allItems = [];
                    this.selected = [];
                }
                this.listing = true;
                this.error = '';
                try {
                    const query = new URLSearchParams({ archived: this.archivedView ? '1' : '0', page: String(this.page) });
                    const data = await this.request(`${cfg.listUrl}?${query}`);
                    this.allItems = reset ? data.items : [...this.allItems, ...data.items];
                    this.hasMore = data.has_more;
                } catch (error) {
                    this.error = error.message;
                } finally {
                    this.listing = false;
                }
            },

            get allSelected() {
                return this.allItems.length > 0 && this.allItems.every(item => this.selected.includes(item.id));
            },

            toggleAll(checked) {
                this.selected = checked ? this.allItems.map(item => item.id) : [];
            },

            toggleItem(id, checked) {
                this.selected = checked
                    ? [...new Set([...this.selected, id])]
                    : this.selected.filter(selectedId => selectedId !== id);
            },

            async openAll() {
                this.open = false;
                this.modalOpen = true;
                this.archivedView = false;
                await this.loadList(true);
            },

            async toggleArchived() {
                this.archivedView = !this.archivedView;
                await this.loadList(true);
            },

            async loadMore() {
                if (!this.hasMore || this.listing) return;
                this.page += 1;
                await this.loadList(false);
            },

            async applySelection(action) {
                if (!this.selected.length || this.busy) return;
                this.busy = true;
                this.error = '';
                const urls = { read: cfg.readUrl, archive: cfg.archiveUrl, restore: cfg.restoreUrl };
                try {
                    await this.request(urls[action], {
                        method: 'POST',
                        body: JSON.stringify({ ids: this.selected }),
                    });
                    await Promise.all([this.loadList(true), this.loadPreview()]);
                } catch (error) {
                    this.error = error.message;
                } finally {
                    this.busy = false;
                }
            },

            async openItem(item) {
                try {
                    if (!item.read) {
                        await this.request(`${cfg.singleReadUrl}/${encodeURIComponent(item.id)}/read`, { method: 'POST' });
                    }
                    if (item.url) {
                        window.location.assign(item.url);
                        return;
                    }
                    await Promise.all([this.loadPreview(), this.loadList(true)]);
                } catch (error) {
                    this.error = error.message;
                }
            },

            closeModal() {
                this.modalOpen = false;
                this.selected = [];
                this.loadPreview();
            },

            init() {
                this.loadPreview();
                this.visibilityHandler = () => {
                    if (document.visibilityState === 'visible') this.loadPreview();
                };
                document.addEventListener('visibilitychange', this.visibilityHandler);
                this.timer = window.setInterval(() => {
                    if (document.visibilityState === 'visible') this.loadPreview();
                }, 5000);
            },

            destroy() {
                window.clearInterval(this.timer);
                document.removeEventListener('visibilitychange', this.visibilityHandler);
            },
        };
    }
</script>
