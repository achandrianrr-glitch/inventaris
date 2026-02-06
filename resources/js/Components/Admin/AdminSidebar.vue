<script setup>
import { ref, watch } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    currentUrl: { type: String, default: "" }, // ✅ FIX: tidak wajib + default aman
    collapsed: { type: Boolean, default: false },
});

/**
 * NOTE:
 * - Disesuaikan dengan routes/web.php kamu
 * - Notifikasi dihapus dari sidebar (sesuai request)
 */
const GROUPS = {
    master: ["/admin/items", "/admin/categories", "/admin/brands", "/admin/locations", "/admin/borrowers"],
    trx: ["/admin/transactions/in", "/admin/transactions/out", "/admin/borrowings", "/admin/returns"],
    monitoring: ["/admin/damages", "/admin/stock-opnames", "/admin/opnames"],
    reports: [
        "/admin/reports/inventory",
        "/admin/reports/transactions",
        "/admin/reports/damages",
        "/admin/reports/borrowings",
        "/admin/history",
    ],
    system: ["/admin/users", "/admin/settings", "/admin/activity-logs"],
};

// ✅ FIX: guard biar tidak error startsWith kalau url kosong/undefined
const isActive = (path) => {
    const u = String(props.currentUrl || "");

    // khusus dashboard biar tidak ikut aktif di semua /admin/*
    if (path === "/admin") return u === "/admin" || u === "/admin/";

    return u === path || u.startsWith(path + "/");
};

const isGroupActive = (key) => (GROUPS[key] || []).some((p) => isActive(p));

const open = ref({
    master: true,
    trx: true,
    monitoring: true,
    reports: false,
    system: false,
});

function toggle(key) {
    open.value[key] = !open.value[key];
}

// ✅ auto-open group sesuai halaman aktif
watch(
    () => props.currentUrl,
    () => {
        Object.keys(GROUPS).forEach((k) => {
            if (isGroupActive(k)) open.value[k] = true;
        });
    },
    { immediate: true }
);

const navItemClass = (path) => ["nav-item-btn", isActive(path) ? "active" : ""];
const groupBtnClass = (key) => ["nav-group-btn", isGroupActive(key) ? "group-active" : ""];

/**
 * Mode collapsed: icon-only
 */
const COLLAPSED_LINKS = [
    { href: "/admin", icon: "bi-speedometer2", label: "Dashboard" },

    { href: "/admin/items", icon: "bi-box-seam", label: "Barang" },
    { href: "/admin/categories", icon: "bi-tags", label: "Kategori" },
    { href: "/admin/brands", icon: "bi-bookmark-star", label: "Merek" },
    { href: "/admin/locations", icon: "bi-geo-alt", label: "Lokasi" },
    { href: "/admin/borrowers", icon: "bi-people", label: "Peminjam" },

    { href: "/admin/transactions/in", icon: "bi-box-arrow-in-down", label: "Barang Masuk" },
    { href: "/admin/transactions/out", icon: "bi-box-arrow-up", label: "Barang Keluar" },
    { href: "/admin/borrowings", icon: "bi-backpack", label: "Peminjaman" },
    { href: "/admin/returns", icon: "bi-arrow-return-left", label: "Pengembalian" },

    { href: "/admin/damages", icon: "bi-exclamation-triangle", label: "Kerusakan" },
    { href: "/admin/opnames", icon: "bi-clipboard-check", label: "Stock Opname" },

    { href: "/admin/reports/inventory", icon: "bi-bar-chart", label: "Laporan" },
    { href: "/admin/history", icon: "bi-clock-history", label: "History" },

    { href: "/admin/users", icon: "bi-person-badge", label: "Pengguna" },
    { href: "/admin/settings", icon: "bi-sliders", label: "Pengaturan" },
    { href: "/admin/activity-logs", icon: "bi-journal-text", label: "Log Aktivitas" },
];
</script>

<template>
    <div class="p-3">
        <!-- ===== COLLAPSED MODE ===== -->
        <div v-if="collapsed" class="collapsed-nav">
            <Link v-for="x in COLLAPSED_LINKS" :key="x.href" :href="x.href" :class="navItemClass(x.href)"
                :title="x.label">
                <i class="bi" :class="x.icon"></i>
            </Link>
        </div>

        <!-- ===== NORMAL MODE ===== -->
        <template v-else>
            <!-- brand -->
            <div class="brand-box mb-3">
                <div class="brand-logo">LAB</div>
                <div class="ms-2">
                    <div class="fw-semibold">Inventaris Lab</div>
                    <div class="text-muted small">Admin Only</div>
                </div>
            </div>

            <!-- Dashboard -->
            <div class="nav-section">
                <Link href="/admin" :class="navItemClass('/admin')">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </Link>
            </div>

            <!-- Master Data -->
            <div class="nav-group">
                <button class="nav-group-btn" :class="groupBtnClass('master')" @click="toggle('master')" type="button">
                    <span><i class="bi bi-folder2-open me-2"></i> Master Data</span>
                    <i class="bi" :class="open.master ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </button>

                <transition name="drop">
                    <div v-show="open.master" class="nav-sub">
                        <Link href="/admin/items" :class="navItemClass('/admin/items')">
                            <i class="bi bi-box-seam me-2"></i> Barang
                        </Link>
                        <Link href="/admin/categories" :class="navItemClass('/admin/categories')">
                            <i class="bi bi-tags me-2"></i> Kategori
                        </Link>
                        <Link href="/admin/brands" :class="navItemClass('/admin/brands')">
                            <i class="bi bi-bookmark-star me-2"></i> Merek
                        </Link>
                        <Link href="/admin/locations" :class="navItemClass('/admin/locations')">
                            <i class="bi bi-geo-alt me-2"></i> Lokasi
                        </Link>
                        <Link href="/admin/borrowers" :class="navItemClass('/admin/borrowers')">
                            <i class="bi bi-people me-2"></i> Peminjam
                        </Link>
                    </div>
                </transition>
            </div>

            <!-- Transaksi -->
            <div class="nav-group">
                <button class="nav-group-btn" :class="groupBtnClass('trx')" @click="toggle('trx')" type="button">
                    <span><i class="bi bi-arrow-repeat me-2"></i> Transaksi</span>
                    <i class="bi" :class="open.trx ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </button>

                <transition name="drop">
                    <div v-show="open.trx" class="nav-sub">
                        <Link href="/admin/transactions/in" :class="navItemClass('/admin/transactions/in')">
                            <i class="bi bi-box-arrow-in-down me-2"></i> Barang Masuk
                        </Link>
                        <Link href="/admin/transactions/out" :class="navItemClass('/admin/transactions/out')">
                            <i class="bi bi-box-arrow-up me-2"></i> Barang Keluar
                        </Link>
                        <Link href="/admin/borrowings" :class="navItemClass('/admin/borrowings')">
                            <i class="bi bi-backpack me-2"></i> Peminjaman
                        </Link>
                        <Link href="/admin/returns" :class="navItemClass('/admin/returns')">
                            <i class="bi bi-arrow-return-left me-2"></i> Pengembalian
                        </Link>
                    </div>
                </transition>
            </div>

            <!-- Monitoring -->
            <div class="nav-group">
                <button class="nav-group-btn" :class="groupBtnClass('monitoring')" @click="toggle('monitoring')"
                    type="button">
                    <span><i class="bi bi-activity me-2"></i> Monitoring</span>
                    <i class="bi" :class="open.monitoring ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </button>

                <transition name="drop">
                    <div v-show="open.monitoring" class="nav-sub">
                        <Link href="/admin/damages" :class="navItemClass('/admin/damages')">
                            <i class="bi bi-exclamation-triangle me-2"></i> Kerusakan
                        </Link>

                        <Link href="/admin/opnames" :class="navItemClass('/admin/opnames')">
                            <i class="bi bi-clipboard-check me-2"></i> Stock Opname
                        </Link>

                        <!-- ✅ Notifikasi sidebar dihapus -->
                    </div>
                </transition>
            </div>

            <!-- Laporan -->
            <div class="nav-group">
                <button class="nav-group-btn" :class="groupBtnClass('reports')" @click="toggle('reports')"
                    type="button">
                    <span><i class="bi bi-file-earmark-text me-2"></i> Laporan</span>
                    <i class="bi" :class="open.reports ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </button>

                <transition name="drop">
                    <div v-show="open.reports" class="nav-sub">
                        <Link href="/admin/reports/inventory" :class="navItemClass('/admin/reports/inventory')">
                            <i class="bi bi-bar-chart me-2"></i> Inventaris
                        </Link>

                        <Link href="/admin/reports/transactions" :class="navItemClass('/admin/reports/transactions')">
                            <i class="bi bi-arrow-left-right me-2"></i> Transaksi
                        </Link>

                        <Link href="/admin/reports/damages" :class="navItemClass('/admin/reports/damages')">
                            <i class="bi bi-tools me-2"></i> Kerusakan
                        </Link>

                        <Link href="/admin/history" :class="navItemClass('/admin/history')">
                            <i class="bi bi-clock-history me-2"></i> History
                        </Link>
                    </div>
                </transition>
            </div>

            <!-- Pengguna & Sistem -->
            <div class="nav-group">
                <button class="nav-group-btn" :class="groupBtnClass('system')" @click="toggle('system')" type="button">
                    <span><i class="bi bi-gear me-2"></i> Pengguna & Sistem</span>
                    <i class="bi" :class="open.system ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </button>

                <transition name="drop">
                    <div v-show="open.system" class="nav-sub">
                        <Link href="/admin/users" :class="navItemClass('/admin/users')">
                            <i class="bi bi-person-badge me-2"></i> Data Pengguna
                        </Link>

                        <Link href="/admin/settings" :class="navItemClass('/admin/settings')">
                            <i class="bi bi-sliders me-2"></i> Pengaturan
                        </Link>

                        <Link href="/admin/activity-logs" :class="navItemClass('/admin/activity-logs')">
                            <i class="bi bi-journal-text me-2"></i> Log Aktivitas
                        </Link>
                    </div>
                </transition>
            </div>
        </template>
    </div>
</template>

<style scoped>
.brand-box {
    display: flex;
    align-items: center;
    padding: 12px;
    border: 1px solid rgba(2, 6, 23, 0.08);
    border-radius: 16px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.85), rgba(255, 255, 255, 0.6));
    box-shadow: 0 12px 28px rgba(2, 6, 23, 0.08);
}

.brand-logo {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    letter-spacing: 0.5px;
    background: radial-gradient(circle at 30% 30%, #1e4db7, #0f2a6b);
    color: white;
}

.nav-group {
    margin-top: 10px;
}

.nav-group-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 0;
    background: transparent;
    padding: 10px 10px;
    border-radius: 12px;
    font-weight: 600;
    color: #0f172a;
    transition: background 160ms ease, transform 160ms ease;
}

.nav-group-btn:hover {
    background: rgba(30, 77, 183, 0.08);
    transform: translateY(-1px);
}

.nav-group-btn.group-active {
    background: rgba(30, 77, 183, 0.10);
    border: 1px solid rgba(30, 77, 183, 0.18);
}

.nav-sub {
    padding-left: 6px;
}

.nav-item-btn {
    width: 100%;
    display: flex;
    align-items: center;
    padding: 10px 10px;
    margin: 6px 0;
    border-radius: 12px;
    text-decoration: none;
    color: #0f172a;
    border: 1px solid transparent;
    transition: background 160ms ease, transform 160ms ease, border-color 160ms ease;
}

.nav-item-btn:hover {
    background: rgba(15, 23, 42, 0.06);
    transform: translateY(-1px);
}

.nav-item-btn.active {
    background: rgba(30, 77, 183, 0.12);
    border-color: rgba(30, 77, 183, 0.25);
}

.drop-enter-active,
.drop-leave-active {
    transition: opacity 160ms ease, transform 160ms ease;
}

.drop-enter-from,
.drop-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

/* ===== collapsed ===== */
.collapsed-nav {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.collapsed-nav .nav-item-btn {
    justify-content: center;
    padding: 12px 0;
}

.collapsed-nav .nav-item-btn i {
    font-size: 20px;
}
</style>
