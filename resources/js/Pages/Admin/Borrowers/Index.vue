<script setup>
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const page = usePage();

const props = defineProps({
    borrowers: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const type = ref(props.filters?.type ?? "all");
const classFilter = ref(props.filters?.class ?? "");
const status = ref(props.filters?.status ?? "all");
const trashed = ref(props.filters?.trashed ?? "without");

const showModal = ref(false);
const mode = ref("create");
const editing = ref(null);

const form = ref({
    name: "",
    type: "student",
    class: "",
    major: "",
    id_number: "",
    contact: "",
    status: "active",
});

const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);

let t = null;
watch(search, () => {
    clearTimeout(t);
    t = setTimeout(() => applyFilters(), 350);
});

watch([type, status, trashed], () => applyFilters());

function applyFilters() {
    router.get(
        "/admin/borrowers",
        {
            search: search.value,
            type: type.value,
            class: classFilter.value,
            status: status.value,
            trashed: trashed.value,
        },
        { preserveState: true, replace: true, preserveScroll: true }
    );
}

function openCreate() {
    mode.value = "create";
    editing.value = null;
    form.value = {
        name: "",
        type: "student",
        class: "",
        major: "",
        id_number: "",
        contact: "",
        status: "active",
    };
    showModal.value = true;
}

function openEdit(b) {
    mode.value = "edit";
    editing.value = b;
    form.value = {
        name: b.name ?? "",
        type: b.type ?? "student",
        class: b.class ?? "",
        major: b.major ?? "",
        id_number: b.id_number ?? "",
        contact: b.contact ?? "",
        status: b.status ?? "active",
    };
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function submit() {
    if (mode.value === "create") {
        router.post("/admin/borrowers", form.value, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        router.put(`/admin/borrowers/${editing.value.id}`, form.value, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

function softDelete(b) {
    if (!confirm(`Hapus peminjam "${b.name}"? (soft delete)`)) return;
    router.delete(`/admin/borrowers/${b.id}`, { preserveScroll: true });
}

function restore(b) {
    router.patch(`/admin/borrowers/${b.id}/restore`, {}, { preserveScroll: true });
}
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Peminjam (Siswa/Guru)</h5>
                <div class="text-muted small">CRUD peminjam + status blocked + validasi NIS/NIP unik</div>
            </div>

            <button class="btn btn-primary" @click="openCreate">
                <i class="bi bi-plus-lg me-1"></i> Tambah
            </button>
        </div>

        <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ flashSuccess }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- filters -->
        <div class="panel p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-xl-5">
                    <label class="form-label small text-muted">Search</label>
                    <input v-model="search" type="text" class="form-control"
                        placeholder="Cari nama / NIS-NIP / kelas / jurusan..." />
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small text-muted">Tipe</label>
                    <select v-model="type" class="form-select">
                        <option value="all">Semua</option>
                        <option value="student">Siswa</option>
                        <option value="teacher">Guru</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small text-muted">Status</label>
                    <select v-model="status" class="form-select">
                        <option value="all">Semua</option>
                        <option value="active">Active</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small text-muted">Trash</label>
                    <select v-model="trashed" class="form-select">
                        <option value="without">Normal</option>
                        <option value="with">Dengan Trash</option>
                        <option value="only">Trash saja</option>
                    </select>
                </div>

                <div class="col-6 col-md-3 col-xl-1">
                    <label class="form-label small text-muted">Kelas</label>
                    <input v-model="classFilter" type="text" class="form-control" placeholder="XII..."
                        @change="applyFilters" />
                </div>
            </div>
        </div>

        <!-- desktop table -->
        <div class="panel d-none d-md-block">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Tipe</th>
                            <th>Kelas</th>
                            <th>Jurusan</th>
                            <th>NIS/NIP</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="b in borrowers.data" :key="b.id" :class="b.deleted_at ? 'row-trashed' : ''">
                            <td class="fw-semibold">
                                {{ b.name }}
                                <span v-if="b.deleted_at" class="badge text-bg-secondary ms-2">TRASH</span>
                            </td>
                            <td>
                                <span class="badge text-bg-light">{{ b.type }}</span>
                            </td>
                            <td class="text-muted">{{ b.class || '-' }}</td>
                            <td class="text-muted">{{ b.major || '-' }}</td>
                            <td class="text-muted">{{ b.id_number || '-' }}</td>
                            <td>
                                <span class="badge"
                                    :class="b.status === 'active' ? 'text-bg-success' : 'text-bg-danger'">
                                    {{ b.status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" @click="openEdit(b)"
                                        :disabled="!!b.deleted_at">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button v-if="!b.deleted_at" class="btn btn-sm btn-outline-danger"
                                        @click="softDelete(b)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <button v-else class="btn btn-sm btn-outline-success" @click="restore(b)">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="borrowers.data.length === 0">
                            <td colspan="7" class="text-muted p-3">Tidak ada data peminjam.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Page {{ borrowers.current_page }} / {{ borrowers.last_page }} • Total {{ borrowers.total }}
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in borrowers.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
                </div>
            </div>
        </div>

        <!-- mobile cards -->
        <div class="d-md-none">
            <div v-for="b in borrowers.data" :key="b.id" class="mini-card" :class="b.deleted_at ? 'row-trashed' : ''">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-2">
                        <div class="fw-semibold">
                            {{ b.name }}
                            <span v-if="b.deleted_at" class="badge text-bg-secondary ms-2">TRASH</span>
                        </div>
                        <div class="text-muted small mt-1">
                            {{ b.type }} • {{ b.class || '-' }} • {{ b.major || '-' }}
                        </div>
                        <div class="mt-2 d-flex gap-2 align-items-center">
                            <span class="badge" :class="b.status === 'active' ? 'text-bg-success' : 'text-bg-danger'">
                                {{ b.status }}
                            </span>
                            <span class="text-muted small">NIS/NIP: {{ b.id_number || '-' }}</span>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-sm btn-outline-primary" @click="openEdit(b)"
                            :disabled="!!b.deleted_at">Edit</button>
                        <button v-if="!b.deleted_at" class="btn btn-sm btn-outline-danger"
                            @click="softDelete(b)">Hapus</button>
                        <button v-else class="btn btn-sm btn-outline-success" @click="restore(b)">Restore</button>
                    </div>
                </div>
            </div>

            <div v-if="borrowers.data.length === 0" class="text-muted p-2">Tidak ada data peminjam.</div>

            <div class="mt-2 d-flex justify-content-between align-items-center">
                <div class="text-muted small">Total {{ borrowers.total }}</div>
                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in borrowers.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
                </div>
            </div>
        </div>

        <!-- Modal -->
        <transition name="fade">
            <div v-if="showModal" class="modal-backdrop-custom" @click.self="closeModal">
                <div class="modal-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">
                            {{ mode === 'create' ? 'Tambah Peminjam' : 'Edit Peminjam' }}
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" @click="closeModal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Nama</label>
                            <input v-model="form.name" type="text" class="form-control" placeholder="Nama peminjam" />
                            <div v-if="errors.name" class="text-danger small mt-1">{{ errors.name }}</div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Tipe</label>
                            <select v-model="form.type" class="form-select">
                                <option value="student">Siswa</option>
                                <option value="teacher">Guru</option>
                            </select>
                            <div v-if="errors.type" class="text-danger small mt-1">{{ errors.type }}</div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select v-model="form.status" class="form-select">
                                <option value="active">Active</option>
                                <option value="blocked">Blocked</option>
                            </select>
                            <div v-if="errors.status" class="text-danger small mt-1">{{ errors.status }}</div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Kelas</label>
                            <input v-model="form.class" type="text" class="form-control"
                                placeholder="X / XI / XII ..." />
                            <div v-if="errors.class" class="text-danger small mt-1">{{ errors.class }}</div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Jurusan</label>
                            <input v-model="form.major" type="text" class="form-control" placeholder="RPL / TKJ ..." />
                            <div v-if="errors.major" class="text-danger small mt-1">{{ errors.major }}</div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">NIS/NIP</label>
                            <input v-model="form.id_number" type="text" class="form-control"
                                placeholder="(opsional tapi unik)" />
                            <div v-if="errors.id_number" class="text-danger small mt-1">{{ errors.id_number }}</div>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Kontak</label>
                            <input v-model="form.contact" type="text" class="form-control"
                                placeholder="08xxxx / WA (opsional)" />
                            <div v-if="errors.contact" class="text-danger small mt-1">{{ errors.contact }}</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button class="btn btn-outline-secondary" @click="closeModal">Batal</button>
                        <button class="btn btn-primary" @click="submit">
                            {{ mode === 'create' ? 'Simpan' : 'Update' }}
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

/* modal */
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

/* anim */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 160ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
