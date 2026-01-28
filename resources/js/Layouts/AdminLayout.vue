<script setup>
import { computed, ref, watch } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import AdminSidebar from "@/Components/Admin/AdminSidebar.vue";
import AdminTopbar from "@/Components/Admin/AdminTopbar.vue";

const page = usePage();
const url = computed(() => page.url);

const sidebarOpen = ref(false);

watch(url, () => {
    // auto close drawer on navigation (mobile)
    sidebarOpen.value = false;
});

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}

function closeSidebar() {
    sidebarOpen.value = false;
}

const unreadCount = computed(() => page.props.unreadCount ?? 0);
const notifications = computed(() => page.props.notifications ?? []);

function markAllRead() {
    router.patch("/admin/notifications/read-all", {}, { preserveScroll: true });
}
</script>

<template>
    <div class="admin-shell">
        <!-- overlay (mobile) -->
        <div class="admin-overlay" :class="{ show: sidebarOpen }" @click="closeSidebar" />

        <!-- sidebar -->
        <aside class="admin-sidebar" :class="{ open: sidebarOpen }">
            <AdminSidebar :current-url="url" />
        </aside>

        <!-- content -->
        <div class="admin-content">
            <AdminTopbar :unread-count="unreadCount" :notifications="notifications" @toggle-sidebar="toggleSidebar"
                @mark-all-read="markAllRead" />

            <main class="admin-main">
                <transition name="page" mode="out-in">
                    <div :key="url">
                        <slot />
                    </div>
                </transition>
            </main>

            <footer class="admin-footer">
                <div class="text-muted small">
                    Sistem Inventaris Lab Sekolah • Admin Panel
                </div>
                <Link class="btn btn-sm btn-outline-secondary" method="post" as="button" href="/logout">
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

/* sidebar */
.admin-sidebar {
    width: 280px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-right: 1px solid rgba(2, 6, 23, 0.08);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow: auto;
    transition: transform 220ms ease, box-shadow 220ms ease;
}

/* content */
.admin-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.admin-main {
    padding: 16px;
}

/* footer */
.admin-footer {
    padding: 14px 16px;
    border-top: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* mobile drawer */
.admin-overlay {
    position: fixed;
    inset: 0;
    background: rgba(2, 6, 23, 0.45);
    opacity: 0;
    pointer-events: none;
    transition: opacity 180ms ease;
    z-index: 40;
}

.admin-overlay.show {
    opacity: 1;
    pointer-events: auto;
}

@media (max-width: 991.98px) {
    .admin-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        z-index: 50;
        transform: translateX(-102%);
        box-shadow: none;
    }

    .admin-sidebar.open {
        transform: translateX(0);
        box-shadow: 0 18px 45px rgba(2, 6, 23, 0.20);
    }
}

/* page transition */
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
    .admin-overlay,
    .page-enter-active,
    .page-leave-active {
        transition: none !important;
    }
}
</style>
