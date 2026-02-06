<script setup>
import { computed, ref, watch } from "vue";
import { router, Link, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const page = usePage();

const props = defineProps({
    history: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({ types: [], periods: [] }) },
    meta: { type: Object, default: () => ({ from: "", to: "" }) },
});

const f = ref({
    period: props.filters?.period ?? "30d",
    type: props.filters?.type ?? "all",
    search: props.filters?.search ?? "",
    per_page: props.filters?.per_page ?? 15,

    // backward compatible (jika UI lama masih pakai)
    date_from: props.filters?.date_from ?? "",
    date_to: props.filters?.date_to ?? "",
});

const errors = computed(() => page.props.errors || {});

// debounce search
let t = null;
watch(
    () => f.value.search,
    () => {
        clearTimeout(t);
        t = setTimeout(() => applyFilters(), 350);
    }
);

// auto apply saat filter lain berubah
watch(
    () => [f.value.period, f.value.type, f.value.per_page, f.value.date_from, f.value.date_to],
    () => applyFilters()
);

function applyFilters() {
    router.get("/admin/history", f.value, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

function resetFilters() {
    f.value = {
        period: "30d",
        type: "all",
        search: "",
        per_page: 15,
        date_from: "",
        date_to: "",
    };
    applyFilters();
}

function formatDT(iso) {
    if (!iso) return "-";
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return "-";
    return d.toLocaleString("id-ID");
}

const badgeClass = (type) => {
    if (type === "in") return "text-bg-success";
    if (type === "out") return "text-bg-danger";
    if (type === "borrow") return "text-bg-warning";
    if (type === "return") return "text-bg-primary";
    if (type === "damage") return "text-bg-secondary";
    if (type === "opname") return "text-bg-dark";
    return "text-bg-light";
};

function goPage(link) {
    if (!link?.url) return;
    router.get(link.url, {}, { preserveState: true, preserveScroll: true });
}

const typeOptions = computed(() => {
    // prioritas dari backend options.types, kalau kosong fallback hardcode
    const arr = props.options?.types ?? [];
    if (arr.length) return arr;

    return [
        { value: "all", label: "Semua" },
        { value: "in", label: "Barang Masuk" },
        { value: "out", label: "Barang Keluar" },
        { value: "borrow", label: "Peminjaman" },
        { value: "return", label: "Pengembalian" },
        { value: "damage", label: "Kerusakan" },
        { value: "opname", label: "Stock Opname" },
    ];
});

const periodOptions = computed(() => {
    const arr = props.options?.periods ?? [];
    if (arr.length) return arr;

    return [
        { value: "7d", label: "7 Hari" },
        { value: "30d", label: "30 Hari" },
        { value: "90d", label: "90 Hari" },
    ];
});
</script>

<template>
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">History Barang</h5>
                <div class="text-muted small">
                    Aktivitas gabungan (Transaksi / Peminjaman / Pengembalian / Kerusakan / Opname)
                    <span v-if="meta?.from && meta?.to"> • Range: {{ meta.from }} — {{ meta.to }}</span>
                </div>
            </div>

            <div class="d-flex gap-2">
                <Link class="btn btn-outline-secondary" href="/admin">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </Link>

                <button class="btn btn-outline-dark" @click="resetFilters">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="panel p-3 mb-3">
            <div class="row g-2 align-items-end">
                <!-- Period -->
                <div class="col-12 col-lg-2">
                    <label class="form-label small text-muted">Periode</label>
                    <select v-model="f.period" class="form-select">
                        <option v-for="p in periodOptions" :key="p.value" :value="p.value">
                            {{ p.label }}
                        </option>
                    </select>
                </div>

                <!-- Type -->
                <div class="col-12 col-lg-2">
                    <label class="form-label small text-muted">Jenis</label>
                    <select v-model="f.type" class="form-select">
                        <option v-for="t in typeOptions" :key="t.value" :value="t.value">
                            {{ t.label }}
                        </option>
                    </select>
                </div>

                <!-- Search -->
                <div class="col-12 col-lg-4">
                    <label class="form-label small text-muted">Search</label>
                    <input v-model="f.search" type="text" class="form-control"
                        placeholder="Cari kode history / kode barang / nama barang..." />
                </div>

                <!-- Optional: Date range override -->
                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">Dari (opsional)</label>
                    <input v-model="f.date_from" type="date" class="form-control" />
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">Sampai (opsional)</label>
                    <input v-model="f.date_to" type="date" class="form-control" />
                </div>

                <div class="col-12 col-lg-2 mt-2">
                    <label class="form-label small text-muted">Per Halaman</label>
                    <select v-model="f.per_page" class="form-select">
                        <option :value="10">10</option>
                        <option :value="15">15</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                </div>

                <div v-if="errors?.date_from || errors?.date_to" class="col-12">
                    <div class="text-danger small">
                        {{ errors.date_from || errors.date_to }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop table -->
        <div class="panel d-none d-md-block">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 175px;">Waktu</th>
                            <th style="width: 160px;">Jenis</th>
                            <th style="width: 170px;">Kode</th>
                            <th>Barang</th>
                            <th style="width: 90px;" class="text-end">Qty</th>
                            <th>Detail</th>
                            <th style="width: 140px;">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="h in history.data" :key="h.key">
                            <td class="text-muted small">{{ formatDT(h.at) }}</td>

                            <td>
                                <span class="badge" :class="badgeClass(h.type)">
                                    {{ h.label }}
                                </span>
                            </td>

                            <td class="fw-semibold">{{ h.code }}</td>

                            <td>
                                <div class="fw-semibold">{{ h.item?.name ?? "-" }}</div>
                                <div class="text-muted small">{{ h.item?.code ?? "-" }}</div>
                            </td>

                            <td class="text-end">
                                <span v-if="h.qty !== null && h.qty !== undefined" class="badge text-bg-light">
                                    {{ h.qty }}
                                </span>
                                <span v-else class="text-muted">-</span>
                            </td>

                            <td class="text-muted small">{{ h.detail ?? "-" }}</td>

                            <td>
                                <span class="badge text-bg-light">{{ h.status ?? "-" }}</span>
                            </td>
                        </tr>

                        <tr v-if="history.data?.length === 0">
                            <td colspan="7" class="text-muted p-3">
                                Tidak ada data history pada periode ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Page {{ history.current_page }} / {{ history.last_page }} • Total {{ history.total }}
                </div>

                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in history.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="goPage(l)" />
                </div>
            </div>
        </div>

        <!-- Mobile cards -->
        <div class="d-md-none">
            <div v-for="h in history.data" :key="h.key" class="mini-card">
                <div class="d-flex justify-content-between gap-2">
                    <div class="min-w-0">
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <span class="badge" :class="badgeClass(h.type)">{{ h.label }}</span>
                            <span class="text-muted small">{{ formatDT(h.at) }}</span>
                        </div>

                        <div class="fw-semibold mt-2">{{ h.item?.name ?? "-" }}</div>
                        <div class="text-muted small">{{ h.item?.code ?? "-" }}</div>

                        <div class="text-muted small mt-2">
                            <span class="fw-semibold">Kode:</span> {{ h.code }}
                            <span v-if="h.qty !== null && h.qty !== undefined" class="ms-2 badge text-bg-light">
                                Qty {{ h.qty }}
                            </span>
                        </div>

                        <div class="text-muted small mt-2">{{ h.detail ?? "-" }}</div>

                        <div class="mt-2">
                            <span class="badge text-bg-light">{{ h.status ?? "-" }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="history.data?.length === 0" class="text-muted p-2">
                Tidak ada data history pada periode ini.
            </div>

            <div class="mt-2 d-flex flex-wrap gap-1">
                <button v-for="(l, idx) in history.links" :key="idx" class="btn btn-sm"
                    :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                    @click="goPage(l)" />
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
    overflow: hidden;
}

.mini-card {
    border: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.85);
    border-radius: 16px;
    padding: 12px;
    margin: 10px 0;
    box-shadow: 0 10px 22px rgba(2, 6, 23, 0.06);
}

.min-w-0 {
    min-width: 0;
}
</style>
