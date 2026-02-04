<script setup>
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const page = usePage();
const props = defineProps({
    notifications: Object,
    filters: Object,
    unreadCount: Number,
});

const flashSuccess = computed(() => page.props.flash?.success);
const f = ref({
    status: props.filters?.status ?? "all",
    type: props.filters?.type ?? "all",
    search: props.filters?.search ?? "",
});

let t = null;
watch(() => f.value.search, () => {
    clearTimeout(t);
    t = setTimeout(() => applyFilters(), 350);
});
watch(() => [f.value.status, f.value.type], () => applyFilters());

function applyFilters() {
    router.get("/admin/notifications", f.value, { preserveState: true, replace: true, preserveScroll: true });
}

function markRead(id) {
    router.patch(`/admin/notifications/${id}/read`, {}, { preserveScroll: true });
}

function markAllRead() {
    router.patch(`/admin/notifications/read-all`, {}, { preserveScroll: true });
}

function badgeClass(type) {
    if (type === "stock_low") return "text-bg-danger";
    if (type === "overdue") return "text-bg-warning";
    if (type === "damage") return "text-bg-primary";
    return "text-bg-secondary";
}
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Notifikasi</h5>
                <div class="text-muted small">Unread: <b>{{ unreadCount }}</b></div>
            </div>

            <button class="btn btn-outline-primary" @click="markAllRead" :disabled="unreadCount === 0">
                <i class="bi bi-check2-all me-1"></i> Tandai Semua Dibaca
            </button>
        </div>

        <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ flashSuccess }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <div class="panel p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-5">
                    <label class="form-label small text-muted">Search</label>
                    <input v-model="f.search" class="form-control" placeholder="Cari judul / pesan..." />
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label small text-muted">Status</label>
                    <select v-model="f.status" class="form-select">
                        <option value="all">Semua</option>
                        <option value="unread">Belum dibaca</option>
                        <option value="read">Sudah dibaca</option>
                    </select>
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label small text-muted">Type</label>
                    <select v-model="f.type" class="form-select">
                        <option value="all">Semua</option>
                        <option value="stock_low">stock_low</option>
                        <option value="overdue">overdue</option>
                        <option value="damage">damage</option>
                        <option value="opname">opname</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="panel p-3">
            <div v-if="notifications.data.length === 0" class="text-muted">Tidak ada notifikasi.</div>

            <div v-for="n in notifications.data" :key="n.id" class="notif-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" :class="badgeClass(n.type)">{{ n.type }}</span>
                            <span class="fw-semibold">{{ n.title }}</span>
                            <span v-if="!n.is_read" class="badge text-bg-success">unread</span>
                        </div>
                        <div class="text-muted small mt-1">{{ n.message }}</div>
                        <div class="text-muted small mt-2">{{ n.created_at }}</div>
                    </div>

                    <button v-if="!n.is_read" class="btn btn-sm btn-outline-primary" @click="markRead(n.id)">
                        Tandai dibaca
                    </button>
                </div>
            </div>

            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Page {{ notifications.current_page }} / {{ notifications.last_page }} • Total {{ notifications.total
                    }}
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in notifications.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.panel {
    border-radius: 18px;
    border: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    box-shadow: 0 12px 28px rgba(2, 6, 23, 0.08);
}

.notif-card {
    border: 1px solid rgba(2, 6, 23, 0.08);
    border-radius: 16px;
    padding: 12px;
    margin: 10px 0;
    background: rgba(255, 255, 255, 0.9);
}
</style>
