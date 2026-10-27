{{-- resources/views/components/notification-bell.blade.php
     Styled to match the HR Reports design (.icon-button, .notification-popup, .notification-item) --}}
<style>
    .hn-wrap { position: relative; }

    /* Bell button (same as .icon-button) */
    .hn-bell {
        position: relative; width: 48px; height: 48px;
        border: 1px solid #e5e7eb; border-radius: 16px;
        background: #ffffff; color: #111827;
        display: grid; place-items: center; cursor: pointer;
        transition: transform .18s ease, background-color .18s ease, border-color .18s ease;
    }
    .hn-bell:hover { transform: translateY(-1px); border-color: #d1d5db; }
    .hn-bell i { font-size: 1.05rem; }
    .hn-count {
        position: absolute; top: 8px; right: 8px;
        min-width: 18px; height: 18px; padding: 0 6px;
        border-radius: 999px; background: #dc2626; color: #ffffff;
        font-size: .72rem; font-weight: 700; line-height: 18px; text-align: center;
    }

    /* Popup (same as .notification-popup) */
    .hn-popup {
        position: absolute; top: calc(100% + 14px); right: 0;
        width: min(380px, 90vw); max-height: 520px;
        display: flex; flex-direction: column; gap: 14px; padding: 14px;
        border-radius: 22px; background: #ffffff;
        border: 1px solid rgba(31, 41, 55, .08);
        box-shadow: 0 14px 28px rgba(15, 23, 42, .08);
        z-index: 50;
    }
    .hn-header { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .hn-header h3 { margin: 0; font-size: 1rem; font-weight: 700; color: #111827; }
    .hn-icon-btn {
        width: 38px; height: 38px;
        border: 1px solid rgba(31, 41, 55, .08); border-radius: 12px;
        background: #ffffff; color: #6b7280;
        display: grid; place-items: center; cursor: pointer;
        transition: background-color .18s ease, border-color .18s ease, color .18s ease;
    }
    .hn-icon-btn:hover { background: #f4f4f6; border-color: rgba(31, 41, 55, .14); color: #111827; }

    /* List + items (same as .notification-list / .notification-item) */
    .hn-list { display: flex; flex-direction: column; gap: 10px; overflow-y: auto; max-height: 340px; padding-right: 2px; }
    .hn-item {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 14px; min-height: 52px; border-radius: 16px;
        background: #f8f8fb; border: 1px solid rgba(31, 41, 55, .08);
        transition: background-color .18s ease, border-color .18s ease;
        cursor: pointer; text-align: left; width: 100%;
    }
    .hn-item:hover { background: #f4f4f6; }
    .hn-item.unread { background: #fff6f6; border-color: rgba(220, 38, 38, .15); }
    .hn-item .hn-icon { font-size: 1rem; color: #9ca3af; margin-top: 2px; }
    .hn-item.type-success .hn-icon { color: #16a34a; }
    .hn-item.type-warning .hn-icon { color: #d97706; }
    .hn-item.type-danger  .hn-icon { color: #dc2626; }
    .hn-item.type-info    .hn-icon { color: #6b7280; }
    .hn-content { min-width: 0; flex: 1; display: grid; gap: 4px; }
    .hn-content h4 {
        margin: 0; font-size: .95rem; line-height: 1.25; color: #111827;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .hn-content p {
        margin: 0; color: #6b7280; font-size: .88rem; line-height: 1.2;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .hn-time { margin: 0; color: #9ca3af; font-size: .75rem; white-space: nowrap; }
    .hn-empty {
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        min-height: 160px; text-align: center; color: #9ca3af;
    }
    .hn-empty i { font-size: 1.8rem; color: #d1d5db; margin-bottom: 10px; }

    /* Footer (same as .notification-popup-footer / .see-all-notifications) */
    .hn-footer { display: flex; justify-content: flex-start; padding-top: 12px; border-top: 1px solid #e5e7eb; }
    .hn-see-all {
        display: inline-flex; align-items: center; gap: 6px; padding: 8px 10px;
        color: #111827; font-weight: 700; white-space: nowrap; text-decoration: none;
    }
    .hn-see-all:hover { color: #b22222; }
    .hn-see-all i { color: #b22222; }
</style>

<div class="hn-wrap"
     x-data="{
        open: false,
        unread: 0,
        items: [],
        timer: null,
        icons: { info: 'fa-circle-info', success: 'fa-circle-check', warning: 'fa-triangle-exclamation', danger: 'fa-circle-xmark' },
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
     @keydown.escape.window="open = false">

    <button type="button" class="hn-bell" aria-label="Notifications"
            :aria-expanded="open.toString()"
            @click="open = !open; if (open) load()">
        <i class="fa-regular fa-bell"></i>
        <span class="hn-count" x-show="unread > 0" x-cloak x-text="unread > 99 ? '99+' : unread"></span>
    </button>

    <div class="hn-popup" x-show="open" x-cloak x-transition.opacity.duration.150ms>
        <div class="hn-header">
            <h3>Notifications</h3>
            <button type="button" class="hn-icon-btn" title="Mark all as read" aria-label="Mark all as read"
                    x-show="unread > 0" @click="markAll()">
                <i class="fa-solid fa-check-double"></i>
            </button>
        </div>

        <div class="hn-list">
            <template x-for="n in items" :key="n.id">
                <div class="hn-item" role="button" tabindex="0"
                     :class="[n.read ? '' : 'unread', 'type-' + n.type]"
                     :title="n.message"
                     @click="openItem(n)" @keydown.enter="openItem(n)">
                    <i class="fa-solid hn-icon" :class="icons[n.type] || icons.info"></i>
                    <div class="hn-content">
                        <h4 x-text="n.title"></h4>
                        <p x-text="n.message"></p>
                    </div>
                    <span class="hn-time" x-text="n.time"></span>
                </div>
            </template>

            <div class="hn-empty" x-show="items.length === 0">
                <i class="fa-regular fa-bell-slash"></i>
                <span>No notifications yet</span>
            </div>
        </div>

        <div class="hn-footer">
            <a href="{{ route('notifications.index') }}" class="hn-see-all">
                See all notifications <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>