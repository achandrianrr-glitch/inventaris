<script setup>
import { computed, reactive } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    tab: { type: String, default: "inventory" },
    options: { type: Object, default: () => ({ categories: [], brands: [], locations: [] }) },
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
    data: { type: Object, default: null }, // paginator from backend
});

const activeTab = computed(() => props.tab || "inventory");

// form filter (sinkron dengan backend filters())
const f = reactive({
    tab: activeTab.value,
    q: props.filters?.q ?? "",

    category_id: props.filters?.category_id ?? "",
    brand_id: props.filters?.brand_id ?? "",
    location_id: props.filters?.location_id ?? "",
    status: props.filters?.status ?? "",

    date_from: props.filters?.date_from ?? "",
    date_to: props.filters?.date_to ?? "",

    type: props.filters?.type ?? "",

    damage_level: props.filters?.damage_level ?? "",
    damage_status: props.filters?.damage_status ?? "",

    borrowing_status: props.filters?.borrowing_status ?? "",
});

function goTab(tab) {
    f.tab = tab;

    router.get("/admin/reports", { ...f, tab }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

function applyFilters() {
    router.get("/admin/reports", { ...f, tab: activeTab.value }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

function resetFilters() {
    Object.keys(f).forEach((k) => (f[k] = ""));
    f.tab = activeTab.value;
    router.get("/admin/reports", { tab: activeTab.value }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

// helper build querystring for export buttons (tetap pakai a href / window open)
function buildQuery(obj) {
    const p = new URLSearchParams();
    Object.entries(obj).forEach(([k, v]) => {
        if (v !== "" && v !== null && v !== undefined) p.set(k, v);
    });
    return p.toString();
}

// export base sesuai tab
const exportBase = computed(() => {
    if (activeTab.value === "inventory") return "/admin/reports/inventory";
    if (activeTab.value === "transactions") return "/admin/reports/transactions";
    if (activeTab.value === "damages") return "/admin/reports/damages";
    return "/admin/reports/borrowings";
});

const excelUrl = computed(() => `${exportBase.value}/excel?${buildQuery({ ...f, tab: activeTab.value })}`);
const pdfUrl = computed(() => `${exportBase.value}/pdf?${buildQuery({ ...f, tab: activeTab.value })}`);
const workbookUrl = computed(() => `/admin/reports/workbook/excel?${buildQuery({ ...f, tab: activeTab.value })}`);

// label summary (biar lebih enak dibaca)
const summaryEntries = computed(() => Object.entries(props.summary || {}));
</script>

<template>
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h5 class="mb-1">Laporan</h5>
                <div class="text-muted small">
                    Laporan ditampilkan langsung di web + opsi export Excel/PDF
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-success" :href="workbookUrl" target="_blank">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Laporan Lengkap (Multi-Sheet)
                </a>

                <a class="btn btn-outline-success" :href="excelUrl" target="_blank">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </a>

                <a class="btn btn-outline-danger" :href="pdfUrl" target="_blank">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                </a>
            </div>
        </div>

        <!-- Tabs -->
        <div class="btn-group mb-3">
            <button class="btn btn-sm" :class="activeTab === 'inventory' ? 'btn-primary' : 'btn-outline-primary'"
                @click="goTab('inventory')">
                Inventaris
            </button>
            <button class="btn btn-sm" :class="activeTab === 'transactions' ? 'btn-primary' : 'btn-outline-primary'"
                @click="goTab('transactions')">
                Transaksi
            </button>
            <button class="btn btn-sm" :class="activeTab === 'damages' ? 'btn-primary' : 'btn-outline-primary'"
                @click="goTab('damages')">
                Kerusakan
            </button>
            <button class="btn btn-sm" :class="activeTab === 'borrowings' ? 'btn-primary' : 'btn-outline-primary'"
                @click="goTab('borrowings')">
                Peminjaman
            </button>
        </div>

        <!-- Summary -->
        <div class="row g-2 mb-3" v-if="summaryEntries.length">
            <div class="col-12 col-md-3" v-for="[k, v] in summaryEntries" :key="k">
                <div class="panel p-3">
                    <div class="text-muted small text-uppercase">{{ k }}</div>
                    <div class="fw-bold">{{ v }}</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="panel p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label small text-muted">Search</label>
                    <input v-model="f.q" class="form-control" placeholder="Kode / Nama / Catatan / dst..." />
                </div>

                <!-- Inventory filters -->
                <template v-if="activeTab === 'inventory'">
                    <div class="col-6 col-lg-2">
                        <label class="form-label small text-muted">Kategori</label>
                        <select v-model="f.category_id" class="form-select">
                            <option value="">Semua</option>
                            <option v-for="c in options.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="form-label small text-muted">Merek</label>
                        <select v-model="f.brand_id" class="form-select">
                            <option value="">Semua</option>
                            <option v-for="b in options.brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="form-label small text-muted">Lokasi</label>
                        <select v-model="f.location_id" class="form-select">
                            <option value="">Semua</option>
                            <option v-for="l in options.locations" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="form-label small text-muted">Status</label>
                        <select v-model="f.status" class="form-select">
                            <option value="">Semua</option>
                            <option value="active">active</option>
                            <option value="service">service</option>
                            <option value="inactive">inactive</option>
                        </select>
                    </div>
                </template>

                <!-- Date range for non-inventory -->
                <template v-else>
                    <div class="col-6 col-lg-2">
                        <label class="form-label small text-muted">Date From</label>
                        <input v-model="f.date_from" type="date" class="form-control" />
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="form-label small text-muted">Date To</label>
                        <input v-model="f.date_to" type="date" class="form-control" />
                    </div>

                    <!-- Transactions type -->
                    <div class="col-6 col-lg-2" v-if="activeTab === 'transactions'">
                        <label class="form-label small text-muted">Tipe</label>
                        <select v-model="f.type" class="form-select">
                            <option value="">Semua</option>
                            <option value="in">in</option>
                            <option value="out">out</option>
                        </select>
                    </div>

                    <!-- Damages -->
                    <div class="col-6 col-lg-2" v-if="activeTab === 'damages'">
                        <label class="form-label small text-muted">Level</label>
                        <select v-model="f.damage_level" class="form-select">
                            <option value="">Semua</option>
                            <option value="minor">minor</option>
                            <option value="moderate">moderate</option>
                            <option value="heavy">heavy</option>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2" v-if="activeTab === 'damages'">
                        <label class="form-label small text-muted">Status</label>
                        <select v-model="f.damage_status" class="form-select">
                            <option value="">Semua</option>
                            <option value="pending">pending</option>
                            <option value="in_progress">in_progress</option>
                            <option value="completed">completed</option>
                        </select>
                    </div>

                    <!-- Borrowings -->
                    <div class="col-6 col-lg-2" v-if="activeTab === 'borrowings'">
                        <label class="form-label small text-muted">Status</label>
                        <select v-model="f.borrowing_status" class="form-select">
                            <option value="">Semua</option>
                            <option value="borrowed">borrowed</option>
                            <option value="late">late</option>
                            <option value="returned">returned</option>
                            <option value="damaged">damaged</option>
                            <option value="lost">lost</option>
                        </select>
                    </div>
                </template>

                <div class="col-12 col-lg-4 d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary" @click="applyFilters">
                        <i class="bi bi-funnel me-1"></i> Terapkan
                    </button>
                    <button class="btn btn-outline-secondary" @click="resetFilters">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="panel p-0 overflow-hidden">
            <div class="table-responsive" v-if="data && data.data">
                <!-- INVENTORY -->
                <table class="table table-sm align-middle mb-0" v-if="activeTab === 'inventory'">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Merek</th>
                            <th>Lokasi</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Tersedia</th>
                            <th class="text-end">Dipinjam</th>
                            <th class="text-end">Rusak</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in data.data" :key="r.id">
                            <td class="fw-semibold">{{ r.code }}</td>
                            <td>{{ r.name }}</td>
                            <td>{{ r.category }}</td>
                            <td>{{ r.brand }}</td>
                            <td>{{ r.location }}</td>
                            <td class="text-end">{{ r.stock_total }}</td>
                            <td class="text-end">{{ r.stock_available }}</td>
                            <td class="text-end">{{ r.stock_borrowed }}</td>
                            <td class="text-end">{{ r.stock_damaged }}</td>
                            <td><span class="badge bg-light text-dark">{{ r.status }}</span></td>
                        </tr>
                    </tbody>
                </table>

                <!-- TRANSACTIONS -->
                <table class="table table-sm align-middle mb-0" v-else-if="activeTab === 'transactions'">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode</th>
                            <th>Tipe</th>
                            <th>Barang</th>
                            <th class="text-end">Qty</th>
                            <th>Dari</th>
                            <th>Ke</th>
                            <th>Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in data.data" :key="r.id">
                            <td>{{ r.transaction_date }}</td>
                            <td class="fw-semibold">{{ r.code }}</td>
                            <td>
                                <span class="badge" :class="r.type === 'in' ? 'bg-success' : 'bg-warning text-dark'">
                                    {{ r.type }}
                                </span>
                            </td>
                            <td>{{ r.item?.code }} — {{ r.item?.name }}</td>
                            <td class="text-end">{{ r.qty }}</td>
                            <td>{{ r.from_location }}</td>
                            <td>{{ r.to_location }}</td>
                            <td class="text-muted">{{ r.admin }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- DAMAGES -->
                <table class="table table-sm align-middle mb-0" v-else-if="activeTab === 'damages'">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode</th>
                            <th>Barang</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Deskripsi</th>
                            <th>Solusi</th>
                            <th>Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in data.data" :key="r.id">
                            <td>{{ r.reported_date }}</td>
                            <td class="fw-semibold">{{ r.code }}</td>
                            <td>{{ r.item?.code }} — {{ r.item?.name }}</td>
                            <td>{{ r.damage_level }}</td>
                            <td><span class="badge bg-light text-dark">{{ r.status }}</span></td>
                            <td class="text-muted">{{ r.description }}</td>
                            <td class="text-muted">{{ r.solution }}</td>
                            <td class="text-muted">{{ r.admin }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- BORROWINGS -->
                <table class="table table-sm align-middle mb-0" v-else>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode</th>
                            <th>Peminjam</th>
                            <th>Barang</th>
                            <th class="text-end">Qty</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in data.data" :key="r.id">
                            <td>{{ r.borrow_date }}</td>
                            <td class="fw-semibold">{{ r.code }}</td>
                            <td>{{ r.borrower?.name }} ({{ r.borrower?.type }})</td>
                            <td>{{ r.item?.code }} — {{ r.item?.name }}</td>
                            <td class="text-end">{{ r.qty }}</td>
                            <td class="text-muted">{{ r.return_due }}</td>
                            <td><span class="badge bg-light text-dark">{{ r.status }}</span></td>
                            <td class="text-muted">{{ r.admin }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty state -->
            <div v-else class="p-3 text-muted">
                Data laporan belum tersedia.
            </div>

            <!-- Pagination -->
            <div class="p-3 d-flex justify-content-between align-items-center" v-if="data && data.total">
                <div class="text-muted small">
                    Menampilkan {{ data.from }} - {{ data.to }} dari {{ data.total }}
                </div>
                <div class="d-flex gap-2">
                    <Link v-if="data.prev_page_url" class="btn btn-sm btn-outline-secondary" :href="data.prev_page_url">
                        Prev
                    </Link>
                    <Link v-if="data.next_page_url" class="btn btn-sm btn-outline-secondary" :href="data.next_page_url">
                        Next
                    </Link>
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
</style>
