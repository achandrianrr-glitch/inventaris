<script setup>
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import axios from "axios";

defineOptions({ layout: AdminLayout });

const page = usePage();

const props = defineProps({
    returns: Object,
    filters: Object,
    options: Object,
});

const f = ref({
    search: props.filters?.search ?? "",
    status: props.filters?.status ?? "all",
    date_from: props.filters?.date_from ?? "",
    date_to: props.filters?.date_to ?? "",
});

const showModal = ref(false);

const form = ref({
    borrower_id: "",
    borrowing_id: "",
    return_condition: "normal",
    return_date: "",
    notes: "",
});

const activeBorrowings = ref([]);
const loadingBorrowings = ref(false);

const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);

let t = null;
watch(() => f.value.search, () => {
    clearTimeout(t);
    t = setTimeout(() => applyFilters(), 350);
});
watch(() => [f.value.status, f.value.date_from, f.value.date_to], () => applyFilters());

function applyFilters() {
    router.get("/admin/returns", f.value, { preserveState: true, replace: true, preserveScroll: true });
}

function openCreate() {
    form.value = {
        borrower_id: props.options?.borrowers?.[0]?.id ?? "",
        borrowing_id: "",
        return_condition: "normal",
        return_date: "",
        notes: "",
    };
    activeBorrowings.value = [];
    showModal.value = true;

    if (form.value.borrower_id) loadActiveBorrowings(form.value.borrower_id);
}

function closeModal() {
    showModal.value = false;
}

async function loadActiveBorrowings(borrowerId) {
    loadingBorrowings.value = true;
    activeBorrowings.value = [];
    form.value.borrowing_id = "";

    try {
        const res = await axios.get("/admin/returns/active-borrowings", { params: { borrower_id: borrowerId } });
        activeBorrowings.value = res.data || [];
        if (activeBorrowings.value.length > 0) {
            form.value.borrowing_id = String(activeBorrowings.value[0].id);
        }
    } finally {
        loadingBorrowings.value = false;
    }
}

watch(() => form.value.borrower_id, (v) => {
    if (!showModal.value) return;
    if (!v) return;
    loadActiveBorrowings(v);
});

const selectedBorrowing = computed(() => activeBorrowings.value.find(b => String(b.id) === String(form.value.borrowing_id)));
const selectedItemBrand = computed(() => selectedBorrowing.value?.item?.brand?.name ?? "-");
const selectedItemName = computed(() => selectedBorrowing.value?.item?.name ?? "-");
const selectedItemCode = computed(() => selectedBorrowing.value?.item?.code ?? "-");
const selectedQty = computed(() => selectedBorrowing.value?.qty ?? 0);
const selectedDue = computed(() => selectedBorrowing.value?.return_due ?? "-");

function submit() {
    router.post("/admin/returns", form.value, { preserveScroll: true, onSuccess: () => closeModal() });
}
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Pengembalian</h5>
                <div class="text-muted small">Pilih peminjam → load pinjaman aktif → pilih kondisi normal/rusak/hilang
                </div>
            </div>

            <button class="btn btn-primary" @click="openCreate">
                <i class="bi bi-plus-lg me-1"></i> Proses Pengembalian
            </button>
        </div>

        <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ flashSuccess }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- filters -->
        <div class="panel p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-5">
                    <label class="form-label small text-muted">Search</label>
                    <input v-model="f.search" type="text" class="form-control"
                        placeholder="Cari kode pinjam / peminjam / barang..." />
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">Status</label>
                    <select v-model="f.status" class="form-select">
                        <select v-model="f.status" class="form-select">
                            <option value="all">Semua</option>
                            <option value="returned">returned</option>
                            <option value="damaged">damaged</option>
                            <option value="lost">lost</option>
                        </select>

                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">Dari</label>
                    <input v-model="f.date_from" type="date" class="form-control" />
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">Sampai</label>
                    <input v-model="f.date_to" type="date" class="form-control" />
                </div>

                <div class="col-6 col-lg-1">
                    <button class="btn btn-outline-secondary w-100" @click="applyFilters">Terapkan</button>
                </div>
            </div>
        </div>

        <!-- desktop table -->
        <div class="panel d-none d-md-block">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode Pinjam</th>
                            <th>Peminjam</th>
                            <th>Barang</th>
                            <th class="text-end">Qty</th>
                            <th>Due</th>
                            <th>Return</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in returns.data" :key="r.id">
                            <td class="text-muted">{{ r.code }}</td>
                            <td class="fw-semibold">{{ r.borrower?.name ?? '-' }}</td>
                            <td class="fw-semibold">
                                {{ r.item?.code ?? '-' }} — {{ r.item?.name ?? '-' }}
                                <div class="text-muted small">Merek: {{ r.item?.brand?.name ?? '-' }}</div>
                            </td>
                            <td class="text-end fw-semibold">{{ r.qty }}</td>
                            <td class="text-muted">{{ r.return_due }}</td>
                            <td class="text-muted">{{ r.return_date }}</td>
                            <td class="text-end">
                                <span class="badge"
                                    :class="r.status === 'returned' ? 'text-bg-success' : (r.status === 'damaged' ? 'text-bg-warning' : 'text-bg-danger')">
                                    {{ r.status }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="returns.data.length === 0">
                            <td colspan="7" class="text-muted p-3">Belum ada data pengembalian.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Page {{ returns.current_page }} / {{ returns.last_page }} • Total {{ returns.total }}
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in returns.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
                </div>
            </div>
        </div>

        <!-- modal -->
        <transition name="fade">
            <div v-if="showModal" class="modal-backdrop-custom" @click.self="closeModal">
                <div class="modal-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Proses Pengembalian</div>
                        <button class="btn btn-sm btn-outline-secondary" @click="closeModal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Peminjam</label>
                            <select v-model="form.borrower_id" class="form-select">
                                <option v-for="br in options.borrowers" :key="br.id" :value="br.id">
                                    {{ br.name }} ({{ br.type }} • {{ br.class || '-' }} • {{ br.major || '-' }})
                                </option>
                            </select>
                            <div v-if="errors.borrower_id" class="text-danger small mt-1">{{ errors.borrower_id }}</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Peminjaman Aktif</label>
                            <div v-if="loadingBorrowings" class="text-muted small">Loading...</div>

                            <select v-else v-model="form.borrowing_id" class="form-select"
                                :disabled="activeBorrowings.length === 0">
                                <option v-for="b in activeBorrowings" :key="b.id" :value="b.id">
                                    {{ b.code }} — {{ b.item?.code }} {{ b.item?.name }} (Qty: {{ b.qty }})
                                </option>
                            </select>

                            <div v-if="activeBorrowings.length === 0 && !loadingBorrowings"
                                class="text-muted small mt-1">
                                Tidak ada pinjaman aktif untuk peminjam ini.
                            </div>

                            <div v-if="errors.borrowing_id" class="text-danger small mt-1">{{ errors.borrowing_id }}
                            </div>

                            <div v-if="selectedBorrowing" class="mt-2 small text-muted">
                                Barang: <span class="fw-semibold">{{ selectedItemCode }} — {{ selectedItemName }}</span>
                                •
                                Merek: <span class="fw-semibold">{{ selectedItemBrand }}</span> •
                                Qty: <span class="fw-semibold">{{ selectedQty }}</span> •
                                Due: <span class="fw-semibold">{{ selectedDue }}</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Kondisi</label>
                            <select v-model="form.return_condition" class="form-select">
                                <option value="normal">Normal</option>
                                <option value="damaged">Rusak</option>
                                <option value="lost">Hilang</option>
                            </select>
                            <div v-if="errors.return_condition" class="text-danger small mt-1">{{
                                errors.return_condition }}</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Tanggal Return (opsional)</label>
                            <input v-model="form.return_date" type="datetime-local" class="form-control" />
                            <div v-if="errors.return_date" class="text-danger small mt-1">{{ errors.return_date }}</div>
                            <div class="text-muted small mt-1">Kosongkan untuk pakai waktu sekarang.</div>
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
                        <button class="btn btn-primary" @click="submit" :disabled="!form.borrowing_id">
                            Proses
                        </button>
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
    width: 820px;
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
