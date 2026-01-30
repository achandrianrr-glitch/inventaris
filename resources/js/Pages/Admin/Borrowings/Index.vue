<script setup>
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const page = usePage();

const props = defineProps({
    borrowings: Object,
    filters: Object,
    options: Object,
    kpi: Object,
});

const f = ref({
    search: props.filters?.search ?? "",
    status: props.filters?.status ?? "all",
    type: props.filters?.type ?? "all",
});

const showModal = ref(false);

const form = ref({
    borrower_id: "",
    item_id: "",
    qty: 1,
    borrow_type: "lesson", // lesson | daily
    borrow_date: new Date().toISOString().slice(0, 10),
    borrow_time: "",
    return_due_date: "",
    lesson_hour: "",
    subject: "",
    teacher: "",
    notes: "",
});

const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);

let t = null;
watch(
    () => f.value.search,
    () => {
        clearTimeout(t);
        t = setTimeout(() => applyFilters(), 350);
    }
);
watch(() => [f.value.status, f.value.type], () => applyFilters());

function applyFilters() {
    router.get("/admin/borrowings", f.value, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

function openCreate() {
    form.value = {
        borrower_id: props.options?.borrowers?.[0]?.id ?? "",
        item_id: props.options?.items?.[0]?.id ?? "",
        qty: 1,
        borrow_type: "lesson",
        borrow_date: new Date().toISOString().slice(0, 10),
        borrow_time: "",
        return_due_date: "",
        lesson_hour: "",
        subject: "",
        teacher: "",
        notes: "",
    };
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function submit(closeAfter = true) {
    router.post("/admin/borrowings", form.value, {
        preserveScroll: true,
        onSuccess: () => {
            if (closeAfter) {
                closeModal();
            } else {
                // Simpan & Buat Lagi: reset item/qty/notes (borrower tetap biar cepat input)
                const borrowerKeep = form.value.borrower_id;
                form.value = {
                    ...form.value,
                    borrower_id: borrowerKeep,
                    item_id: props.options?.items?.[0]?.id ?? "",
                    qty: 1,
                    notes: "",
                    borrow_time: "",
                    return_due_date: "",
                    lesson_hour: "",
                    subject: "",
                    teacher: "",
                };
            }
        },
    });
}

// brand auto muncul dari item terpilih
const selectedItem = computed(() =>
    (props.options?.items ?? []).find((i) => String(i.id) === String(form.value.item_id))
);
const brandName = computed(() => selectedItem.value?.brand?.name ?? "-");
const stockAvailable = computed(() => selectedItem.value?.stock_available ?? 0);

// UX: kalau ganti tipe, kosongkan field yang tidak relevan
watch(
    () => form.value.borrow_type,
    (v) => {
        if (v === "lesson") {
            form.value.return_due_date = "";
        } else {
            form.value.lesson_hour = "";
            form.value.subject = "";
            form.value.teacher = "";
        }
    }
);
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Peminjaman</h5>
                <div class="text-muted small">
                    Validasi 1 peminjam = 1 pinjaman aktif • indikator terlambat • merek auto muncul
                </div>
            </div>

            <button class="btn btn-primary" @click="openCreate">
                <i class="bi bi-plus-lg me-1"></i> Buat Peminjaman
            </button>
        </div>

        <!-- KPI kecil -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="panel p-3">
                    <div class="text-muted small">Pinjaman Aktif</div>
                    <div class="fw-semibold fs-4">{{ kpi.active }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="panel p-3">
                    <div class="text-muted small">Terlambat (overdue)</div>
                    <div class="fw-semibold fs-4">{{ kpi.overdue }}</div>
                </div>
            </div>
        </div>

        <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ flashSuccess }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- filters -->
        <div class="panel p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label small text-muted">Search</label>
                    <input v-model="f.search" type="text" class="form-control"
                        placeholder="Cari kode pinjam / peminjam / barang..." />
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label small text-muted">Status</label>
                    <select v-model="f.status" class="form-select">
                        <option value="all">Semua</option>
                        <option value="borrowed">borrowed</option>
                        <option value="late">late</option>
                        <option value="returned">returned</option>
                        <option value="damaged">damaged</option>
                        <option value="lost">lost</option>
                    </select>
                </div>

                <div class="col-6 col-lg-3">
                    <label class="form-label small text-muted">Tipe</label>
                    <select v-model="f.type" class="form-select">
                        <option value="all">Semua</option>
                        <option value="lesson">lesson</option>
                        <option value="daily">daily</option>
                    </select>
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
                            <th>Peminjam</th>
                            <th>Barang</th>
                            <th class="text-end">Qty</th>
                            <th>Tipe</th>
                            <th>Due</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="b in borrowings.data" :key="b.id">
                            <td class="text-muted">{{ b.code }}</td>

                            <td class="fw-semibold">
                                {{ b.borrower?.name ?? "-" }}
                                <div class="text-muted small">
                                    {{ b.borrower?.type ?? "-" }} • {{ b.borrower?.class || "-" }} • {{
                                    b.borrower?.major || "-" }}
                                </div>
                            </td>

                            <td class="fw-semibold">
                                {{ b.item?.code ?? "-" }} — {{ b.item?.name ?? "-" }}
                                <div class="text-muted small">Merek: {{ b.item?.brand?.name ?? "-" }}</div>
                            </td>

                            <td class="text-end fw-semibold">{{ b.qty }}</td>

                            <td class="text-muted">{{ b.borrow_type }}</td>

                            <td class="text-muted">
                                {{ b.return_due }}
                                <div v-if="b.is_overdue" class="text-danger small">Overdue</div>
                            </td>

                            <td class="text-end">
                                <span class="badge" :class="b.display_status === 'late'
                                    ? 'text-bg-danger'
                                    : (b.display_status === 'borrowed' ? 'text-bg-warning' : 'text-bg-success')">
                                    {{ b.display_status }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="borrowings.data.length === 0">
                            <td colspan="7" class="text-muted p-3">Belum ada data peminjaman.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Page {{ borrowings.current_page }} / {{ borrowings.last_page }} • Total {{ borrowings.total }}
                </div>

                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in borrowings.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
                </div>
            </div>
        </div>

        <!-- mobile cards -->
        <div class="d-md-none">
            <div v-for="b in borrowings.data" :key="b.id" class="mini-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-2">
                        <div class="text-muted small">{{ b.code }}</div>
                        <div class="fw-semibold">{{ b.borrower?.name ?? "-" }}</div>

                        <div class="text-muted small mt-1">
                            {{ b.item?.code ?? "-" }} — {{ b.item?.name ?? "-" }} • Merek: {{ b.item?.brand?.name ?? "-"
                            }}
                        </div>

                        <div class="text-muted small mt-1">
                            Qty: <span class="fw-semibold">{{ b.qty }}</span> • Tipe: {{ b.borrow_type }}
                        </div>

                        <div class="text-muted small mt-1">Due: {{ b.return_due }}</div>
                        <div v-if="b.is_overdue" class="text-danger small mt-1">Overdue</div>
                    </div>

                    <span class="badge" :class="b.display_status === 'late'
                        ? 'text-bg-danger'
                        : (b.display_status === 'borrowed' ? 'text-bg-warning' : 'text-bg-success')">
                        {{ b.display_status }}
                    </span>
                </div>
            </div>

            <div v-if="borrowings.data.length === 0" class="text-muted p-2">Belum ada data peminjaman.</div>
        </div>

        <!-- Modal Create -->
        <transition name="fade">
            <div v-if="showModal" class="modal-backdrop-custom" @click.self="closeModal">
                <div class="modal-card">
                    <!-- HEADER -->
                    <div class="modal-head">
                        <div class="fw-semibold">Buat Peminjaman</div>
                        <button class="btn btn-sm btn-outline-secondary" @click="closeModal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- BODY (SCROLL) -->
                    <div class="modal-body-scroll">
                        <div class="row g-2">
                            <div class="col-12 col-lg-6">
                                <label class="form-label small text-muted">Peminjam</label>
                                <select v-model="form.borrower_id" class="form-select form-select-sm">
                                    <option v-for="br in options.borrowers" :key="br.id" :value="br.id">
                                        {{ br.name }} ({{ br.type }} • {{ br.class || "-" }} • {{ br.major || "-" }})
                                    </option>
                                </select>
                                <div v-if="errors.borrower_id" class="text-danger small mt-1">{{ errors.borrower_id }}
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <label class="form-label small text-muted">Barang</label>
                                <select v-model="form.item_id" class="form-select form-select-sm">
                                    <option v-for="it in options.items" :key="it.id" :value="it.id">
                                        {{ it.code }} — {{ it.name }} (A: {{ it.stock_available }})
                                    </option>
                                </select>

                                <div class="d-flex gap-3 small text-muted mt-1">
                                    <div>Merek: <span class="fw-semibold text-dark">{{ brandName }}</span></div>
                                    <div>Stok: <span class="fw-semibold text-dark">{{ stockAvailable }}</span></div>
                                </div>

                                <div v-if="errors.item_id" class="text-danger small mt-1">{{ errors.item_id }}</div>
                            </div>

                            <div class="col-6 col-lg-3">
                                <label class="form-label small text-muted">Qty</label>
                                <input v-model="form.qty" type="number" min="1" class="form-control form-control-sm" />
                                <div v-if="errors.qty" class="text-danger small mt-1">{{ errors.qty }}</div>
                            </div>

                            <div class="col-6 col-lg-3">
                                <label class="form-label small text-muted">Jenis Waktu</label>
                                <select v-model="form.borrow_type" class="form-select form-select-sm">
                                    <option value="lesson">Jam Pelajaran</option>
                                    <option value="daily">Harian</option>
                                </select>
                                <div v-if="errors.borrow_type" class="text-danger small mt-1">{{ errors.borrow_type }}
                                </div>
                            </div>

                            <div class="col-6 col-lg-3">
                                <label class="form-label small text-muted">Tanggal Pinjam</label>
                                <input v-model="form.borrow_date" type="date" class="form-control form-control-sm" />
                                <div v-if="errors.borrow_date" class="text-danger small mt-1">{{ errors.borrow_date }}
                                </div>
                            </div>

                            <div class="col-6 col-lg-3">
                                <label class="form-label small text-muted">Jam (opsional)</label>
                                <input v-model="form.borrow_time" type="time" class="form-control form-control-sm" />
                                <div v-if="errors.borrow_time" class="text-danger small mt-1">{{ errors.borrow_time }}
                                </div>
                            </div>

                            <!-- Lesson fields -->
                            <template v-if="form.borrow_type === 'lesson'">
                                <div class="col-4 col-lg-2">
                                    <label class="form-label small text-muted">Jam ke-</label>
                                    <input v-model="form.lesson_hour" type="number" min="1" max="12"
                                        class="form-control form-control-sm" />
                                    <div v-if="errors.lesson_hour" class="text-danger small mt-1">{{ errors.lesson_hour
                                        }}</div>
                                </div>

                                <div class="col-8 col-lg-4">
                                    <label class="form-label small text-muted">Mapel</label>
                                    <input v-model="form.subject" type="text" class="form-control form-control-sm" />
                                    <div v-if="errors.subject" class="text-danger small mt-1">{{ errors.subject }}</div>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label class="form-label small text-muted">Guru</label>
                                    <input v-model="form.teacher" type="text" class="form-control form-control-sm" />
                                    <div v-if="errors.teacher" class="text-danger small mt-1">{{ errors.teacher }}</div>
                                </div>

                                <div class="col-12">
                                    <div class="hint-line small text-muted">Due otomatis: hari yang sama (23:59)</div>
                                </div>
                            </template>

                            <!-- Daily fields -->
                            <template v-else>
                                <div class="col-12">
                                    <label class="form-label small text-muted">Tanggal Kembali (Due)</label>
                                    <input v-model="form.return_due_date" type="date"
                                        class="form-control form-control-sm" />
                                    <div v-if="errors.return_due_date" class="text-danger small mt-1">{{
                                        errors.return_due_date }}</div>
                                    <div class="hint-line small text-muted mt-1">Due otomatis: tanggal kembali (23:59)
                                    </div>
                                </div>
                            </template>

                            <div class="col-12">
                                <label class="form-label small text-muted">Catatan</label>
                                <textarea v-model="form.notes" class="form-control form-control-sm" rows="3"
                                    placeholder="(opsional)"></textarea>
                                <div v-if="errors.notes" class="text-danger small mt-1">{{ errors.notes }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="modal-foot">
                        <button class="btn btn-outline-secondary" @click="closeModal">Batal</button>
                        <button class="btn btn-outline-primary" @click="submit(false)">Simpan & Buat Lagi</button>
                        <button class="btn btn-primary" @click="submit(true)">Simpan</button>
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

/* MODAL */
.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(2, 6, 23, 0.55);
    z-index: 80;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.modal-card {
    width: min(760px, 96vw);
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(14px);
    box-shadow: 0 18px 45px rgba(2, 6, 23, 0.25);
    overflow: hidden;
}

.modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-bottom: 1px solid rgba(2, 6, 23, 0.08);
}

.modal-body-scroll {
    padding: 12px 14px;
    overflow: auto;
}

.modal-foot {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 14px;
    border-top: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.95);
}

.hint-line {
    padding-top: 4px;
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
