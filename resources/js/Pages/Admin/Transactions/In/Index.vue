<script setup>
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const page = usePage();

const props = defineProps({
    transactions: Object,
    filters: Object,
    options: Object,
});

const f = ref({
    search: props.filters?.search ?? "",
    item_id: props.filters?.item_id ?? "",
    date_from: props.filters?.date_from ?? "",
    date_to: props.filters?.date_to ?? "",
});

const showModal = ref(false);
const form = ref({
    item_id: "",
    qty: 1,
    from_location: "",
    transaction_date: new Date().toISOString().slice(0, 10),
    notes: "",
});

const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);

let t = null;
watch(() => f.value.search, () => {
    clearTimeout(t);
    t = setTimeout(() => applyFilters(), 350);
});
watch(() => [f.value.item_id, f.value.date_from, f.value.date_to], () => applyFilters());

function applyFilters() {
    router.get("/admin/transactions/in", f.value, { preserveState: true, replace: true, preserveScroll: true });
}

function resetFilters() {
    f.value = { search: "", item_id: "", date_from: "", date_to: "" };
    applyFilters();
}

function openCreate() {
    form.value = {
        item_id: props.options?.items?.[0]?.id ?? "",
        qty: 1,
        from_location: "",
        transaction_date: new Date().toISOString().slice(0, 10),
        notes: "",
    };
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function submit() {
    router.post("/admin/transactions/in", form.value, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Barang Masuk</h5>
                <div class="text-muted small">Transaksi masuk menambah stok total & tersedia</div>
            </div>

            <button class="btn btn-primary" @click="openCreate">
                <i class="bi bi-plus-lg me-1"></i> Tambah Barang Masuk
            </button>
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
                        placeholder="Cari kode transaksi / kode barang / nama barang..." />
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <label class="form-label small text-muted">Barang</label>
                    <select v-model="f.item_id" class="form-select">
                        <option value="">Semua</option>
                        <option v-for="it in options.items" :key="it.id" :value="it.id">
                            {{ it.code }} — {{ it.name }}
                        </option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted">Dari Tgl</label>
                    <input v-model="f.date_from" type="date" class="form-control" />
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted">Sampai</label>
                    <input v-model="f.date_to" type="date" class="form-control" />
                </div>

                <div class="col-12 col-lg-1 d-flex gap-2 justify-content-lg-end">
                    <button class="btn btn-outline-secondary w-100" @click="resetFilters">Reset</button>
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
                            <th>Barang</th>
                            <th class="text-end">Qty</th>
                            <th>Dari</th>
                            <th>Tanggal</th>
                            <th>Admin</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="tr in transactions.data" :key="tr.id">
                            <td class="text-muted">{{ tr.code }}</td>
                            <td class="fw-semibold">
                                {{ tr.item?.code ?? '-' }} — {{ tr.item?.name ?? '-' }}
                            </td>
                            <td class="text-end fw-semibold">+{{ tr.qty }}</td>
                            <td class="text-muted">{{ tr.from_location || '-' }}</td>
                            <td class="text-muted">{{ tr.transaction_date }}</td>
                            <td class="text-muted">{{ tr.admin?.name ?? '-' }}</td>
                            <td class="text-end">
                                <span class="badge"
                                    :class="tr.status === 'completed' ? 'text-bg-success' : 'text-bg-warning'">
                                    {{ tr.status }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="transactions.data.length === 0">
                            <td colspan="7" class="text-muted p-3">Belum ada transaksi barang masuk.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Page {{ transactions.current_page }} / {{ transactions.last_page }} • Total {{ transactions.total }}
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in transactions.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
                </div>
            </div>
        </div>

        <!-- mobile cards -->
        <div class="d-md-none">
            <div v-for="tr in transactions.data" :key="tr.id" class="mini-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-2">
                        <div class="text-muted small">{{ tr.code }}</div>
                        <div class="fw-semibold">{{ tr.item?.code ?? '-' }} — {{ tr.item?.name ?? '-' }}</div>
                        <div class="text-muted small mt-1">
                            Qty: <span class="fw-semibold">+{{ tr.qty }}</span> • Tgl: {{ tr.transaction_date }}
                        </div>
                        <div class="text-muted small mt-1">Dari: {{ tr.from_location || '-' }}</div>
                        <div class="text-muted small mt-1">Admin: {{ tr.admin?.name ?? '-' }}</div>
                    </div>
                    <span class="badge" :class="tr.status === 'completed' ? 'text-bg-success' : 'text-bg-warning'">
                        {{ tr.status }}
                    </span>
                </div>
            </div>

            <div v-if="transactions.data.length === 0" class="text-muted p-2">Belum ada transaksi barang masuk.</div>
        </div>

        <!-- Modal -->
        <transition name="fade">
            <div v-if="showModal" class="modal-backdrop-custom" @click.self="closeModal">
                <div class="modal-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Tambah Barang Masuk</div>
                        <button class="btn btn-sm btn-outline-secondary" @click="closeModal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Barang</label>
                            <select v-model="form.item_id" class="form-select">
                                <option v-for="it in options.items" :key="it.id" :value="it.id">
                                    {{ it.code }} — {{ it.name }}
                                </option>
                            </select>
                            <div v-if="errors.item_id" class="text-danger small mt-1">{{ errors.item_id }}</div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Qty</label>
                            <input v-model="form.qty" type="number" min="1" class="form-control" />
                            <div v-if="errors.qty" class="text-danger small mt-1">{{ errors.qty }}</div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Tanggal</label>
                            <input v-model="form.transaction_date" type="date" class="form-control" />
                            <div v-if="errors.transaction_date" class="text-danger small mt-1">{{
                                errors.transaction_date }}</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Dari (Supplier/Lokasi)</label>
                            <input v-model="form.from_location" type="text" class="form-control"
                                placeholder="Contoh: Donasi / Supplier A / Gudang" />
                            <div v-if="errors.from_location" class="text-danger small mt-1">{{ errors.from_location }}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea v-model="form.notes" class="form-control" rows="3"
                                placeholder="(opsional)"></textarea>
                            <div v-if="errors.notes" class="text-danger small mt-1">{{ errors.notes }}</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button class="btn btn-outline-secondary" @click="closeModal">Batal</button>
                        <button class="btn btn-primary" @click="submit">Simpan</button>
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
    width: 720px;
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
