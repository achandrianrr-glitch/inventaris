<script setup>
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const page = usePage();

const props = defineProps({
    items: Object,
    filters: Object,
    options: Object,
});

const f = ref({
    search: props.filters?.search ?? "",
    category_id: props.filters?.category_id ?? "",
    brand_id: props.filters?.brand_id ?? "",
    location_id: props.filters?.location_id ?? "",
    status: props.filters?.status ?? "all",
    condition: props.filters?.condition ?? "all",
    trashed: props.filters?.trashed ?? "without",
});

const showModal = ref(false);
const mode = ref("create");
const editing = ref(null);

const form = ref({
    name: "",
    category_id: "",
    brand_id: "",
    location_id: "",
    specification: "",
    purchase_year: "",
    purchase_price: "",
    stock_total: 0,
    condition: "good",
    status: "active",
});

const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);

let t = null;
watch(() => f.value.search, () => {
    clearTimeout(t);
    t = setTimeout(() => applyFilters(), 350);
});
watch(
    () => [f.value.category_id, f.value.brand_id, f.value.location_id, f.value.status, f.value.condition, f.value.trashed],
    () => applyFilters()
);

function applyFilters() {
    router.get("/admin/items", f.value, { preserveState: true, replace: true, preserveScroll: true });
}

function resetFilters() {
    f.value = { search: "", category_id: "", brand_id: "", location_id: "", status: "all", condition: "all", trashed: "without" };
    applyFilters();
}

function openCreate() {
    mode.value = "create";
    editing.value = null;
    form.value = {
        name: "",
        category_id: props.options?.categories?.[0]?.id ?? "",
        brand_id: props.options?.brands?.[0]?.id ?? "",
        location_id: props.options?.locations?.[0]?.id ?? "",
        specification: "",
        purchase_year: "",
        purchase_price: "",
        stock_total: 0,
        condition: "good",
        status: "active",
    };
    showModal.value = true;
}

function openEdit(it) {
    mode.value = "edit";
    editing.value = it;
    form.value = {
        name: it.name ?? "",
        category_id: it.category_id ?? "",
        brand_id: it.brand_id ?? "",
        location_id: it.location_id ?? "",
        specification: it.specification ?? "",
        purchase_year: it.purchase_year ?? "",
        purchase_price: it.purchase_price ?? "",
        stock_total: it.stock_total ?? 0,
        condition: it.condition ?? "good",
        status: it.status ?? "active",
    };
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function submit() {
    if (mode.value === "create") {
        router.post("/admin/items", form.value, { preserveScroll: true, onSuccess: () => closeModal() });
    } else {
        router.put(`/admin/items/${editing.value.id}`, form.value, { preserveScroll: true, onSuccess: () => closeModal() });
    }
}

function softDelete(it) {
    if (!confirm(`Hapus barang "${it.name}"? (soft delete)`)) return;
    router.delete(`/admin/items/${it.id}`, { preserveScroll: true });
}

function restore(it) {
    router.patch(`/admin/items/${it.id}/restore`, {}, { preserveScroll: true });
}

function goDetail(it) {
    router.get(`/admin/items/${it.id}`);
}

const exportExcelUrl = computed(() => `/admin/items-export-excel?${new URLSearchParams(f.value).toString()}`);
const exportPdfUrl = computed(() => `/admin/items-export-pdf?${new URLSearchParams(f.value).toString()}`);
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Barang</h5>
                <div class="text-muted small">CRUD barang + kode otomatis + filter lengkap + export excel/pdf</div>
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-outline-success" :href="exportExcelUrl">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </a>
                <a class="btn btn-outline-danger" :href="exportPdfUrl">
                    <i class="bi bi-filetype-pdf me-1"></i> PDF
                </a>
                <button class="btn btn-primary" @click="openCreate">
                    <i class="bi bi-plus-lg me-1"></i> Tambah
                </button>
            </div>
        </div>

        <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ flashSuccess }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- filters -->
        <div class="panel p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label small text-muted">Search</label>
                    <input v-model="f.search" type="text" class="form-control"
                        placeholder="Cari kode/nama/spesifikasi..." />
                </div>

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
                        <option value="all">Semua</option>
                        <option value="active">active</option>
                        <option value="service">service</option>
                        <option value="inactive">inactive</option>
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">Kondisi</label>
                    <select v-model="f.condition" class="form-select">
                        <option value="all">Semua</option>
                        <option value="good">good</option>
                        <option value="minor">minor</option>
                        <option value="heavy">heavy</option>
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">Trash</label>
                    <select v-model="f.trashed" class="form-select">
                        <option value="without">Normal</option>
                        <option value="with">Dengan Trash</option>
                        <option value="only">Trash saja</option>
                    </select>
                </div>

                <div class="col-12 col-lg-2 d-flex gap-2 justify-content-lg-end">
                    <button class="btn btn-outline-secondary w-100" @click="applyFilters">
                        <i class="bi bi-funnel me-1"></i> Terapkan
                    </button>
                    <button class="btn btn-outline-dark w-100" @click="resetFilters">Reset</button>
                </div>
            </div>
        </div>

        <!-- desktop table -->
        <div class="panel d-none d-md-block">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Merek</th>
                            <th>Lokasi</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="it in items.data" :key="it.id" :class="it.deleted_at ? 'row-trashed' : ''">
                            <td class="text-muted">{{ it.code }}</td>
                            <td class="fw-semibold">
                                {{ it.name }}
                                <span v-if="it.deleted_at" class="badge text-bg-secondary ms-2">TRASH</span>
                            </td>
                            <td class="text-muted">{{ it.category?.name ?? '-' }}</td>
                            <td class="text-muted">{{ it.brand?.name ?? '-' }}</td>
                            <td class="text-muted">{{ it.location?.name ?? '-' }}</td>
                            <td class="text-muted small">
                                T: {{ it.stock_total }} • A: {{ it.stock_available }} • B: {{ it.stock_borrowed }} • D:
                                {{ it.stock_damaged }}
                            </td>
                            <td>
                                <span class="badge text-bg-light me-1">{{ it.condition }}</span>
                                <span class="badge"
                                    :class="it.status === 'active' ? 'text-bg-success' : (it.status === 'service' ? 'text-bg-warning' : 'text-bg-secondary')">
                                    {{ it.status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-dark" @click="goDetail(it)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" @click="openEdit(it)"
                                        :disabled="!!it.deleted_at">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button v-if="!it.deleted_at" class="btn btn-sm btn-outline-danger"
                                        @click="softDelete(it)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <button v-else class="btn btn-sm btn-outline-success" @click="restore(it)">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="items.data.length === 0">
                            <td colspan="8" class="text-muted p-3">Tidak ada data barang.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Page {{ items.current_page }} / {{ items.last_page }} • Total {{ items.total }}
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in items.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
                </div>
            </div>
        </div>

        <!-- mobile cards -->
        <div class="d-md-none">
            <div v-for="it in items.data" :key="it.id" class="mini-card" :class="it.deleted_at ? 'row-trashed' : ''">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-2">
                        <div class="text-muted small">{{ it.code }}</div>
                        <div class="fw-semibold">
                            {{ it.name }}
                            <span v-if="it.deleted_at" class="badge text-bg-secondary ms-2">TRASH</span>
                        </div>
                        <div class="text-muted small mt-1">
                            {{ it.category?.name ?? '-' }} • {{ it.brand?.name ?? '-' }} • {{ it.location?.name ?? '-'
                            }}
                        </div>
                        <div class="text-muted small mt-2">
                            T: {{ it.stock_total }} • A: {{ it.stock_available }} • B: {{ it.stock_borrowed }} • D: {{
                            it.stock_damaged }}
                        </div>
                        <div class="mt-2 d-flex gap-2 align-items-center">
                            <span class="badge text-bg-light">{{ it.condition }}</span>
                            <span class="badge"
                                :class="it.status === 'active' ? 'text-bg-success' : (it.status === 'service' ? 'text-bg-warning' : 'text-bg-secondary')">
                                {{ it.status }}
                            </span>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-sm btn-outline-dark" @click="goDetail(it)">Detail</button>
                        <button class="btn btn-sm btn-outline-primary" @click="openEdit(it)"
                            :disabled="!!it.deleted_at">Edit</button>
                        <button v-if="!it.deleted_at" class="btn btn-sm btn-outline-danger"
                            @click="softDelete(it)">Hapus</button>
                        <button v-else class="btn btn-sm btn-outline-success" @click="restore(it)">Restore</button>
                    </div>
                </div>
            </div>

            <div v-if="items.data.length === 0" class="text-muted p-2">Tidak ada data barang.</div>
        </div>

        <!-- Modal -->
        <transition name="fade">
            <div v-if="showModal" class="modal-backdrop-custom" @click.self="closeModal">
                <div class="modal-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">{{ mode === 'create' ? 'Tambah Barang' : 'Edit Barang' }}</div>
                        <button class="btn btn-sm btn-outline-secondary" @click="closeModal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Nama</label>
                            <input v-model="form.name" type="text" class="form-control" />
                            <div v-if="errors.name" class="text-danger small mt-1">{{ errors.name }}</div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Kategori</label>
                            <select v-model="form.category_id" class="form-select">
                                <option v-for="c in options.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <div v-if="errors.category_id" class="text-danger small mt-1">{{ errors.category_id }}</div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Merek</label>
                            <select v-model="form.brand_id" class="form-select">
                                <option v-for="b in options.brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <div v-if="errors.brand_id" class="text-danger small mt-1">{{ errors.brand_id }}</div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Lokasi</label>
                            <select v-model="form.location_id" class="form-select">
                                <option v-for="l in options.locations" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                            <div v-if="errors.location_id" class="text-danger small mt-1">{{ errors.location_id }}</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Spesifikasi</label>
                            <textarea v-model="form.specification" class="form-control" rows="3"></textarea>
                            <div v-if="errors.specification" class="text-danger small mt-1">{{ errors.specification }}
                            </div>
                        </div>

                        <div class="col-6 col-md-4">
                            <label class="form-label">Tahun</label>
                            <input v-model="form.purchase_year" type="number" class="form-control" placeholder="2024" />
                            <div v-if="errors.purchase_year" class="text-danger small mt-1">{{ errors.purchase_year }}
                            </div>
                        </div>

                        <div class="col-6 col-md-4">
                            <label class="form-label">Harga</label>
                            <input v-model="form.purchase_price" type="number" class="form-control" placeholder="0" />
                            <div v-if="errors.purchase_price" class="text-danger small mt-1">{{ errors.purchase_price }}
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Stok Total</label>
                            <input v-model="form.stock_total" type="number" class="form-control" min="0" />
                            <div v-if="errors.stock_total" class="text-danger small mt-1">{{ errors.stock_total }}</div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Kondisi</label>
                            <select v-model="form.condition" class="form-select">
                                <option value="good">good</option>
                                <option value="minor">minor</option>
                                <option value="heavy">heavy</option>
                            </select>
                            <div v-if="errors.condition" class="text-danger small mt-1">{{ errors.condition }}</div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select v-model="form.status" class="form-select">
                                <option value="active">active</option>
                                <option value="service">service</option>
                                <option value="inactive">inactive</option>
                            </select>
                            <div v-if="errors.status" class="text-danger small mt-1">{{ errors.status }}</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button class="btn btn-outline-secondary" @click="closeModal">Batal</button>
                        <button class="btn btn-primary" @click="submit">{{ mode === 'create' ? 'Simpan' : 'Update'
                            }}</button>
                    </div>
                </div>
            </div>
        </transition>
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

.row-trashed {
    opacity: .72;
}

.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(2, 6, 23, 0.55);
    z-index: 80;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
}

.modal-card {
    width: 920px;
    max-width: 100%;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(14px);
    box-shadow: 0 18px 45px rgba(2, 6, 23, 0.25);
    padding: 14px;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 160ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
