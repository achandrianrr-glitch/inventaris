<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";

const emit = defineEmits(["toggle-sidebar", "mark-all-read"]);

const props = defineProps({
    unreadCount: { type: Number, default: 0 },
    notifications: { type: Array, default: () => [] },
});

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name ?? "Admin");

const notifOpen = ref(false);

function toggleNotif() {
    notifOpen.value = !notifOpen.value;
}

function closeNotif() {
    notifOpen.value = false;
}

function onDocClick(e) {
    const el = e.target;
    if (!el.closest?.(".notif-wrap")) closeNotif();
}

onMounted(() => document.addEventListener("click", onDocClick));
onBeforeUnmount(() => document.removeEventListener("click", onDocClick));

function markRead(id) {
    router.patch(`/admin/notifications/${id}/read`, {}, { preserveScroll: true });
}
</script>

<template>
    <header class="topbar">
        <div class="left">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" @click="$emit('toggle-sidebar')">
                <i class="bi bi-list"></i>
            </button>

            <div class="ms-2">
                <div class="fw-semibold">Dashboard</div>
                <div class="text-muted small">Halo, {{ userName }}</div>
            </div>
        </div>

        <div class="right">
            <!-- Notif -->
            <div class="notif-wrap">
                <button class="icon-btn" @click.stop="toggleNotif" aria-label="Notifikasi">
                    <i class="bi bi-bell"></i>
                    <span v-if="unreadCount > 0" class="badge-pill">{{ unreadCount }}</span>
                </button>

                <transition name="pop">
                    <div v-if="notifOpen" class="notif-pop">
                        <div class="notif-head">
                            <div class="fw-semibold">Notifikasi</div>
                            <button class="btn btn-sm btn-outline-primary" @click="$emit('mark-all-read')">
                                Tandai semua
                            </button>
                        </div>

                        <div v-if="notifications.length === 0" class="p-3 text-muted">
                            Belum ada notifikasi.
                        </div>

                        <div v-else class="notif-list">
                            <div v-for="n in notifications" :key="n.id" class="notif-item"
                                :class="{ unread: !n.is_read }">
                                <div class="notif-title">
                                    <span class="dot" />
                                    <div class="fw-semibold text-truncate">{{ n.title }}</div>
                                </div>

                                <div class="text-muted small notif-msg">
                                    {{ n.message }}
                                </div>

                                <div class="notif-actions">
                                    <span class="text-muted small">{{ new Date(n.created_at).toLocaleString() }}</span>
                                    <button v-if="!n.is_read" class="btn btn-sm btn-outline-secondary"
                                        @click="markRead(n.id)">
                                        Read
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="notif-foot">
                            <Link class="btn btn-sm btn-primary w-100" href="/admin/notifications" @click="closeNotif">
                                Lihat Semua
                            </Link>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>

<style scoped>
.topbar {
    position: sticky;
    top: 0;
    z-index: 30;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(2, 6, 23, 0.08);
}

.left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.icon-btn {
    position: relative;
    width: 42px;
    height: 42px;
    border-radius: 14px;
    border: 1px solid rgba(2, 6, 23, 0.10);
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 160ms ease, box-shadow 160ms ease;
}

.icon-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(2, 6, 23, 0.10);
}

.badge-pill {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 20px;
    height: 20px;
    border-radius: 999px;
    padding: 0 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #dc3545;
    color: white;
    font-size: 12px;
    font-weight: 700;
    border: 2px solid white;
}

/* popover */
.notif-wrap {
    position: relative;
}

.notif-pop {
    position: absolute;
    right: 0;
    top: 52px;
    width: 360px;
    max-width: calc(100vw - 28px);
    border-radius: 18px;
    border: 1px solid rgba(2, 6, 23, 0.10);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px);
    box-shadow: 0 18px 45px rgba(2, 6, 23, 0.18);
    overflow: hidden;
}

.notif-head {
    padding: 12px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(2, 6, 23, 0.08);
}

.notif-list {
    max-height: 340px;
    overflow: auto;
}

.notif-item {
    padding: 12px 12px;
    border-bottom: 1px solid rgba(2, 6, 23, 0.06);
    transition: background 160ms ease;
}

.notif-item:hover {
    background: rgba(15, 23, 42, 0.04);
}

.notif-item.unread .dot {
    background: #1e4db7;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: rgba(100, 116, 139, 0.35);
    margin-right: 8px;
    flex: 0 0 auto;
}

.notif-title {
    display: flex;
    align-items: center;
}

.notif-msg {
    margin-top: 4px;
    line-height: 1.2rem;
}

.notif-actions {
    margin-top: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.notif-foot {
    padding: 12px;
}

/* animation */
.pop-enter-active,
.pop-leave-active {
    transition: opacity 160ms ease, transform 160ms ease;
}

.pop-enter-from,
.pop-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
