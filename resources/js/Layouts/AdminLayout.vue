<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import axios from "axios";

import AdminSidebar from "@/Components/Admin/AdminSidebar.vue";
import AdminTopbar from "@/Components/Admin/AdminTopbar.vue";

const page = usePage();

// ✅ pastikan selalu string (anti undefined)
const url = computed(() => String(page.url || "").split("?")[0]);

/**
 * ========== Responsive Sidebar State ==========
 * Mobile: drawerOpen
 * Desktop: sidebarCollapsed
 */
const drawerOpen = ref(false);
const sidebarCollapsed = ref(false);

function toggleDrawer() {
    drawerOpen.value = !drawerOpen.value;
}
function closeDrawer() {
    drawerOpen.value = false;
}
function toggleCollapse() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
}

// ✅ auto close drawer on navigation (mobile)
watch(url, () => {
    drawerOpen.value = false;
});

/**
 * ========== Notifications (Polling fallback) ==========
 * Prioritas: page.props.unreadCount
 * Jika backend belum share unreadCount, polling API.
 */
const unread = ref(0);
let timer = null;

const unreadCount = computed(() => page.props.unreadCount ?? unread.value ?? 0);
const notifications = computed(() => page.props.notifications ?? []);

async function fetchUnread() {
    try {
        const res = await axios.get("/admin/notifications/unread-count");
        unread.value = res.data?.unread_count ?? 0;
    } catch (e) {
        // silent
    }
}

onMounted(() => {
    fetchUnread();
    timer = setInterval(fetchUnread, 45000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

function markAllRead() {
    if (typeof route === "function") {
        router.patch(route("admin.notifications.read_all"), {}, { preserveScroll: true });
        return;
    }
    router.patch("/admin/notifications/read-all", {}, { preserveScroll: true });
}
</script>

<template>
    <div class="admin-shell">
        <!-- ===== MOBILE OVERLAY ===== -->
        <div class="admin-overlay d-lg-none" :class="{ show: drawerOpen }" @click="closeDrawer" />

        <!-- ===== DESKTOP SIDEBAR (fixed) ===== -->
        <aside class="admin-sidebar d-none d-lg-flex" :class="{ collapsed: sidebarCollapsed }">
            <div class="sidebar-head">
                <div class="brand" v-if="!sidebarCollapsed">Inventaris Lab</div>

                <button class="btn btn-outline-secondary btn-sm btn-touch" @click="toggleCollapse"
                    aria-label="Collapse sidebar">
                    <i class="bi" :class="sidebarCollapsed ? 'bi-chevron-right' : 'bi-chevron-left'"></i>
                </button>
            </div>

            <div class="sidebar-body">
                <!-- ✅ selalu kirim current-url yang valid -->
                <AdminSidebar :current-url="url" :collapsed="sidebarCollapsed" />
            </div>
        </aside>

        <!-- ===== MOBILE DRAWER SIDEBAR ===== -->
        <aside class="admin-drawer d-lg-none" :class="{ open: drawerOpen }">
            <div class="drawer-head">
                <div class="fw-semibold">Menu</div>
                <button class="btn btn-outline-secondary btn-sm btn-touch" @click="closeDrawer">X</button>
            </div>

            <div class="drawer-body">
                <AdminSidebar :current-url="url" />
            </div>
        </aside>

        <!-- ===== CONTENT ===== -->
        <div class="admin-content">
            <AdminTopbar :unread-count="unreadCount" :notifications="notifications" @toggle-sidebar="toggleDrawer"
                @mark-all-read="markAllRead" />

            <main class="admin-main">
                <transition name="page" mode="out-in">
                    <div :key="url">
                        <slot />
                    </div>
                </transition>
            </main>

            <footer class="admin-footer">
                <div class="text-muted small">Sistem Inventaris Lab Sekolah • Admin Panel</div>

                <Link class="btn btn-sm btn-outline-secondary btn-touch" method="post" as="button"
                    :href="typeof route === 'function' ? route('logout') : '/logout'">
                    Logout
                </Link>
            </footer>
        </div>
    </div>
</template>

<style scoped>
.admin-shell {
    min-height: 100vh;
    display: flex;
    background: #eef2fb;
}

/* ===== Desktop Sidebar ===== */
.admin-sidebar {
    width: 280px;
    height: 100vh;
    position: sticky;
    top: 0;
    border-right: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    display: flex;
    flex-direction: column;
    transition: width 200ms ease;
}

.admin-sidebar.collapsed {
    width: 92px;
}

.sidebar-head {
    padding: 12px;
    border-bottom: 1px solid rgba(2, 6, 23, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.brand {
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-body {
    padding: 10px;
    overflow: auto;
    flex: 1;
}

/* ===== Mobile Drawer ===== */
.admin-drawer {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: min(86vw, 320px);
    background: rgba(255, 255, 255, 0.96);
    border-right: 1px solid rgba(2, 6, 23, 0.08);
    box-shadow: 18px 0 40px rgba(0, 0, 0, 0.18);
    transform: translateX(-105%);
    transition: transform 220ms ease;
    z-index: 9999;
    display: flex;
    flex-direction: column;
}

.admin-drawer.open {
    transform: translateX(0);
}

.drawer-head {
    padding: 12px;
    border-bottom: 1px solid rgba(2, 6, 23, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.drawer-body {
    padding: 10px;
    overflow: auto;
    flex: 1;
}

/* ===== Mobile Overlay ===== */
.admin-overlay {
    position: fixed;
    inset: 0;
    background: rgba(2, 6, 23, 0.45);
    opacity: 0;
    pointer-events: none;
    transition: opacity 180ms ease;
    z-index: 9998;
}

.admin-overlay.show {
    opacity: 1;
    pointer-events: auto;
}

/* ===== Content ===== */
.admin-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.admin-main {
    padding: 16px;
}

/* ===== Footer ===== */
.admin-footer {
    padding: 14px 16px;
    border-top: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

/* ===== Touch Friendly ===== */
.btn-touch {
    min-width: 44px;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* ===== Page Transition ===== */
.page-enter-active,
.page-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}

.page-enter-from,
.page-leave-to {
    opacity: 0;
    transform: translateY(6px);
}

@media (prefers-reduced-motion: reduce) {

    .admin-sidebar,
    .admin-drawer,
    .admin-overlay,
    .page-enter-active,
    .page-leave-active {
        transition: none !important;
    }
}
</style>
