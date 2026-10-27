{{-- resources/views/components/notification-bell.blade.php --}}
<div x-data="{
        open: false,
        unread: 0,
        items: [],
        timer: null,
        dot: { info: 'bg-blue-500', success: 'bg-green-500', warning: 'bg-amber-500', danger: 'bg-red-600' },
        async load() {
            try {
                const r = await fetch('{{ route('notifications.feed') }}', { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                this.unread = d.unread;
                this.items = d.items;
            } catch (e) {}
        },
        async post(url) {
            await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
            });
        },
        async openItem(n) {
            if (!n.read) { await this.post('{{ url('/notifications') }}/' + n.id + '/read'); }
            if (n.url) { window.location = n.url; } else { this.load(); }
        },
        async markAll() {
            await this.post('{{ route('notifications.read-all') }}');
            this.load();
        },
        init() { this.load(); this.timer = setInterval(() => this.load(), 30000); }
    }"
    @click.outside="open = false"
    class="relative">

    <button type="button" @click="open = !open; if (open) load()"
            class="relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:bg-gray-50 focus:outline-none"
            aria-label="Notifications">
        <i class="fa-solid fa-bell text-lg"></i>
        <span x-show="unread > 0" x-cloak
              x-text="unread > 99 ? '99+' : unread"
              class="absolute -right-1 -top-1 min-w-[20px] rounded-full bg-red-700 px-1.5 text-center text-xs font-bold leading-5 text-white"></span>
    </button>

    <div x-show="open" x-cloak x-transition
         class="absolute right-0 z-50 mt-2 w-80 max-w-[90vw] overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg sm:w-96">

        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <span class="font-semibold text-gray-900">Notifications</span>
            <button type="button" @click="markAll()" x-show="unread > 0"
                    class="text-xs font-medium text-red-700 hover:underline">Mark all as read</button>
        </div>

        <div class="max-h-96 divide-y divide-gray-100 overflow-y-auto">
            <template x-for="n in items" :key="n.id">
                <button type="button" @click="openItem(n)"
                        class="flex w-full items-start gap-3 px-4 py-3 text-left hover:bg-gray-50"
                        :class="n.read ? '' : 'bg-red-50/40'">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" :class="dot[n.type] || dot.info"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm text-gray-900" :class="n.read ? 'font-medium' : 'font-semibold'" x-text="n.title"></span>
                        <span class="mt-0.5 block text-xs text-gray-600" x-text="n.message"></span>
                        <span class="mt-1 block text-[11px] text-gray-400" x-text="n.time"></span>
                    </span>
                </button>
            </template>

            <p x-show="items.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">No notifications yet.</p>
        </div>

        <a href="{{ route('notifications.index') }}"
           class="block border-t border-gray-100 px-4 py-2.5 text-center text-sm font-medium text-red-700 hover:bg-gray-50">
            View all
        </a>
    </div>
</div>