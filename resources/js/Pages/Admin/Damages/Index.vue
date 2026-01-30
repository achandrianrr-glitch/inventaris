<script setup>
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const page = usePage();

const props = defineProps({
    damages: Object,
    filters: Object,
    options: Object,
});

const f = ref({
    search: props.filters?.search ?? "",
    status: props.filters?.status ?? "all",
    level: props.filters?.level ?? "all",
    location_id: props.filters?.location_id ?? "",
});

const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);

let t = null;
watch(() => f.value.search, () => {
    clearTimeout(t);
    t = setTimeout(() => applyFilters(), 350);
});
watch(() => [f.value.status, f.value.level, f.value.location_id], () => applyFilters());

function applyFilters() {
    router.get("/admin/damages", f.value, { preserveState: true, replace: true, preserveScroll: true });
}

function resetFilters() {
    f.value = { search: "", status: "all", level: "all", location_id: "" };
    applyFilters();
}

// ===== Create modal
const showCreate = ref(false);
const createForm = ref({
    item_id: "",
    borrowing_id: "",
    damage_level: "moderate",
    reported_date: new Date().toISOString().slice(0, 10),
    description: "",
});

function openCreate() {
    createForm.value = {
        item_id: props.options?.items?.[0]?.id ?? "",
        borrowing_id: "",
        damage_level: "moderate",
        reported_date: new Date().toISOString().slice(0, 10),
        description: "",
    };
    showCreate.value = true;
}
function closeCreate() { showCreate.value = false; }
function submitCreate() {
    router.post("/admin/damages", createForm.value, { preserveScroll: true, onSuccess: () => closeCreate() });
}

// ===== Process modal (update)
const showProcess = ref(false);
const processForm = ref({
    id: null,
    status: "pending",
    solution: "",
    completion_date: "",
});

function openProcess(row) {
    processForm.value = {
        id: row.id,
        status: row.status,
        solution: row.solution ?? "",
        completion_date: row.completion_date ?? "",
    };
    showProcess.value = true;
}
function closeProcess() { showProcess.value = false; }

watch(() => processForm.value.status, (v) => {
    if (v !== "completed") {
        processForm.value.completion_date = "";
    } else if (!processForm.value.completion_date) {
        processForm.value.completion_date = new Date().toISOString().slice(0, 10);
    }
});

function submitProcess() {
    router.patch(`/admin/damages/${processForm.value.id}`, {
        status: processForm.value.status,
        solution: processForm.value.solution,
        completion_date: processForm.value.completion_date,
    }, { preserveScroll: true, onSuccess: () => closeProcess() });
}

// badge helper
function badgeClass(status) {
    if (status === "pending") return "text-bg-warning";
    if (status === "in_progress") return "text-bg-primary";
    return "text-bg-success";
}

function levelBadge(level) {
    if (level === "minor") return "text-bg-success";
    if (level === "moderate") return "text-bg-warning";
    return "text-bg-danger";
}
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Kerusakan</h5>
                <div class="text-muted small">Ticketing kerusakan: Pending → In Progress → Completed, filter by
                    level/status/lokasi</div>
            </div>

            <button class="btn btn-primary" @click="openCreate">
                <i class="bi bi-plus-lg me-1"></i> Lapor Kerusakan
            </button>
        </div>

        <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ flashSuccess }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- Filters -->
        <div class="panel p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-5">
                    <label class="form-label small text-muted">Search</label>
                    <input v-model="f.search" type="text" class="form-control"
                        placeholder="Cari kode DMG / barang / kode pinjam..." />
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">Status</label>
                    <select v-model="f.status" class="form-select">
                        <option value="all">Semua</option>
                        <option value="pending">pending</option>
                        <option value="in_progress">in_progress</option>
                        <option value="completed">completed</option>
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">Level</label>
                    <select v-model="f.level" class="form-select">
                        <option value="all">Semua</option>
                        <option value="minor">minor</option>
                        <option value="moderate">moderate</option>
                        <option value="heavy">heavy</option>
                    </select>
                </div>

                <div class="col-6 col-lg-2">
                    <label class="form-label small text-muted">Lokasi</label>
                    <select v-model="f.location_id" class="form-select">
                        <option value="">Semua</option>
                        <option v-for="l in options.locations" :key="l.id" :value="l.id">{{ l.name }}</option>
                    </select>
                </div>

                <div class="col-6 col-lg-1">
                    <button class="btn btn-outline-secondary w-100" @click="resetFilters">Reset</button>
                </div>
            </div>
        </div>

        <!-- Desktop table -->
        <div class="panel d-none d-md-block">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Barang</th>
                            <th>Lokasi</th>
                            <th>Level</th>
                            <th>Reported</th>
                            <th class="text-end">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in damages.data" :key="d.id">
                            <td class="text-muted">{{ d.code }}</td>
                            <td class="fw-semibold">
                                {{ d.item?.code ?? '-' }} — {{ d.item?.name ?? '-' }}
                                <div class="text-muted small" v-if="d.borrowing">
                                    Link: {{ d.borrowing?.code }} • Peminjam: {{ d.borrowing?.borrower?.name ?? '-' }}
                                </div>
                            </td>
                            <td class="text-muted">{{ d.item?.location?.name ?? '-' }}</td>
                            <td>
                                <span class="badge" :class="levelBadge(d.damage_level)">{{ d.damage_level }}</span>
                            </td>
                            <td class="text-muted">{{ d.reported_date }}</td>
                            <td class="text-end">
                                <span class="badge" :class="badgeClass(d.status)">{{ d.status }}</span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" @click="openProcess(d)">
                                    Proses
                                </button>
                            </td>
                        </tr>

                        <tr v-if="damages.data.length === 0">
                            <td colspan="7" class="text-muted p-3">Belum ada laporan kerusakan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Page {{ damages.current_page }} / {{ damages.last_page }} • Total {{ damages.total }}
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in damages.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
                </div>
            </div>
        </div>

        <!-- Mobile cards -->
        <div class="d-md-none">
            <div v-for="d in damages.data" :key="d.id" class="mini-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-2">
                        <div class="text-muted small">{{ d.code }}</div>
                        <div class="fw-semibold">{{ d.item?.code ?? '-' }} — {{ d.item?.name ?? '-' }}</div>
                        <div class="text-muted small mt-1">Lokasi: {{ d.item?.location?.name ?? '-' }}</div>
                        <div class="text-muted small mt-1">
                            Level: <span class="badge" :class="levelBadge(d.damage_level)">{{ d.damage_level }}</span>
                            • Status: <span class="badge ms-1" :class="badgeClass(d.status)">{{ d.status }}</span>
                        </div>
                        <div class="text-muted small mt-1">Reported: {{ d.reported_date }}</div>
                        <div class="text-muted small mt-1" v-if="d.borrowing">Link: {{ d.borrowing?.code }}</div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" @click="openProcess(d)">
                        Proses
                    </button>
                </div>
            </div>

            <div v-if="damages.data.length === 0" class="text-muted p-2">Belum ada laporan kerusakan.</div>
        </div>

        <!-- Modal Create -->
        <transition name="fade">
            <div v-if="showCreate" class="modal-backdrop-custom" @click.self="closeCreate">
                <div class="modal-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Lapor Kerusakan</div>
                        <button class="btn btn-sm btn-outline-secondary" @click="closeCreate"><i
                                class="bi bi-x-lg"></i></button>
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Barang</label>
                            <select v-model="createForm.item_id" class="form-select">
                                <option v-for="it in options.items" :key="it.id" :value="it.id">
                                    {{ it.code }} — {{ it.name }} (Lok: {{ it.location?.name ?? '-' }})
                                </option>
                            </select>
                            <div v-if="errors.item_id" class="text-danger small mt-1">{{ errors.item_id }}</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Link Peminjaman (opsional)</label>
                            <select v-model="createForm.borrowing_id" class="form-select">
                                <option value="">(tanpa link)</option>
                                <option v-for="b in options.borrowings" :key="b.id" :value="b.id">
                                    {{ b.code }} — qty: {{ b.qty }} — {{ b.borrower?.name ?? '-' }}
                                </option>
                            </select>
                            <div v-if="errors.borrowing_id" class="text-danger small mt-1">{{ errors.borrowing_id }}
                            </div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Level</label>
                            <select v-model="createForm.damage_level" class="form-select">
                                <option value="minor">minor</option>
                                <option value="moderate">moderate</option>
                                <option value="heavy">heavy</option>
                            </select>
                            <div v-if="errors.damage_level" class="text-danger small mt-1">{{ errors.damage_level }}
                            </div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Tanggal Lapor</label>
                            <input v-model="createForm.reported_date" type="date" class="form-control" />
                            <div v-if="errors.reported_date" class="text-danger small mt-1">{{ errors.reported_date }}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea v-model="createForm.description" class="form-control" rows="4"></textarea>
                            <div v-if="errors.description" class="text-danger small mt-1">{{ errors.description }}</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button class="btn btn-outline-secondary" @click="closeCreate">Batal</button>
                        <button class="btn btn-primary" @click="submitCreate">Simpan</button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Modal Process -->
        <transition name="fade">
            <div v-if="showProcess" class="modal-backdrop-custom" @click.self="closeProcess">
                <div class="modal-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">Proses Kerusakan</div>
                        <button class="btn btn-sm btn-outline-secondary" @click="closeProcess"><i
                                class="bi bi-x-lg"></i></button>
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select v-model="processForm.status" class="form-select">
                                <option value="pending">pending</option>
                                <option value="in_progress">in_progress</option>
                                <option value="completed">completed</option>
                            </select>
                            <div v-if="errors.status" class="text-danger small mt-1">{{ errors.status }}</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Solusi</label>
                            <textarea v-model="processForm.solution" class="form-control" rows="4"
                                placeholder="Isi solusi / tindakan perbaikan..."></textarea>
                            <div v-if="errors.solution" class="text-danger small mt-1">{{ errors.solution }}</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Tanggal Selesai</label>
                            <input v-model="processForm.completion_date" type="date" class="form-control"
                                :disabled="processForm.status !== 'completed'" />
                            <div v-if="errors.completion_date" class="text-danger small mt-1">{{ errors.completion_date
                                }}</div>
                            <div class="text-muted small mt-1">Wajib jika status completed.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button class="btn btn-outline-secondary" @click="closeProcess">Batal</button>
                        <button class="btn btn-primary" @click="submitProcess">Simpan</button>
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
    width: 900px;
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
