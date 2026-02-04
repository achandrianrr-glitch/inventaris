<script setup>
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const page = usePage();
const props = defineProps({
    users: Object,
    filters: Object,
});

const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);

const f = ref({
    search: props.filters?.search ?? "",
    status: props.filters?.status ?? "all",
});

let t = null;
watch(() => f.value.search, () => {
    clearTimeout(t);
    t = setTimeout(() => applyFilters(), 350);
});
watch(() => f.value.status, () => applyFilters());

function applyFilters() {
    router.get("/admin/users", f.value, { preserveState: true, replace: true, preserveScroll: true });
}

// ===== modal state
const showCreate = ref(false);
const showEdit = ref(false);
const showReset = ref(false);

const createForm = ref({ name: "", email: "", password: "", status: "active" });
const editForm = ref({ id: null, name: "", email: "", status: "active" });
const resetForm = ref({ id: null, new_password: "" });

function openCreate() {
    createForm.value = { name: "", email: "", password: "", status: "active" };
    showCreate.value = true;
}

function openEdit(u) {
    editForm.value = { id: u.id, name: u.name, email: u.email, status: u.status };
    showEdit.value = true;
}

function openReset(u) {
    resetForm.value = { id: u.id, new_password: "" };
    showReset.value = true;
}

function submitCreate() {
    router.post("/admin/users", createForm.value, { preserveScroll: true, onSuccess: () => (showCreate.value = false) });
}

function submitEdit() {
    router.patch(`/admin/users/${editForm.value.id}`, {
        name: editForm.value.name,
        email: editForm.value.email,
        status: editForm.value.status,
    }, { preserveScroll: true, onSuccess: () => (showEdit.value = false) });
}

function submitReset() {
    router.patch(`/admin/users/${resetForm.value.id}/reset-password`, {
        new_password: resetForm.value.new_password,
    }, { preserveScroll: true, onSuccess: () => (showReset.value = false) });
}

function toggleStatus(u) {
    router.patch(`/admin/users/${u.id}/toggle-status`, {}, { preserveScroll: true });
}

function softDelete(u) {
    if (!confirm(`Hapus admin ${u.name}? (soft delete)`)) return;
    router.delete(`/admin/users/${u.id}`, { preserveScroll: true });
}

function restore(u) {
    router.patch(`/admin/users/${u.id}/restore`, {}, { preserveScroll: true });
}

function statusBadge(s) {
    return s === "active" ? "text-bg-success" : "text-bg-secondary";
}
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Data Pengguna (Admin)</h5>
                <div class="text-muted small">Kelola akun admin, reset password, status active/inactive, soft delete
                </div>
            </div>
            <button class="btn btn-primary" @click="openCreate">
                <i class="bi bi-plus-lg me-1"></i> Tambah Admin
            </button>
        </div>

        <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ flashSuccess }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div class="panel p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label small text-muted">Search</label>
                    <input v-model="f.search" class="form-control" placeholder="Cari nama / email..." />
                </div>
                <div class="col-6 col-lg-3">
                    <label class="form-label small text-muted">Status</label>
                    <select v-model="f.status" class="form-select">
                        <option value="all">Semua</option>
                        <option value="active">active</option>
                        <option value="inactive">inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="table-responsive d-none d-md-block">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users.data" :key="u.id" :class="u.deleted_at ? 'table-warning' : ''">
                            <td class="fw-semibold">
                                {{ u.name }}
                                <span v-if="u.deleted_at" class="badge text-bg-warning ms-2">deleted</span>
                            </td>
                            <td class="text-muted">{{ u.email }}</td>
                            <td><span class="badge" :class="statusBadge(u.status)">{{ u.status }}</span></td>
                            <td class="text-muted">{{ u.last_login ?? "-" }}</td>
                            <td class="text-muted">{{ u.created_at }}</td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                    <button class="btn btn-sm btn-outline-secondary" @click="openEdit(u)"
                                        :disabled="!!u.deleted_at">Edit</button>
                                    <button class="btn btn-sm btn-outline-primary" @click="openReset(u)"
                                        :disabled="!!u.deleted_at">Reset Password</button>
                                    <button class="btn btn-sm btn-outline-warning" @click="toggleStatus(u)"
                                        :disabled="!!u.deleted_at">Toggle</button>
                                    <button v-if="!u.deleted_at" class="btn btn-sm btn-outline-danger"
                                        @click="softDelete(u)">Delete</button>
                                    <button v-else class="btn btn-sm btn-success" @click="restore(u)">Restore</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" class="text-muted p-3">Belum ada admin.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="d-md-none p-3">
                <div v-for="u in users.data" :key="u.id" class="mini-card"
                    :class="u.deleted_at ? 'border-warning' : ''">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">{{ u.name }}</div>
                        <span class="badge" :class="statusBadge(u.status)">{{ u.status }}</span>
                    </div>
                    <div class="text-muted small mt-1">{{ u.email }}</div>
                    <div class="text-muted small mt-1">Last login: {{ u.last_login ?? "-" }}</div>
                    <div class="text-muted small mt-1">Created: {{ u.created_at }}</div>

                    <div class="d-flex gap-2 flex-wrap mt-2">
                        <button class="btn btn-sm btn-outline-secondary" @click="openEdit(u)"
                            :disabled="!!u.deleted_at">Edit</button>
                        <button class="btn btn-sm btn-outline-primary" @click="openReset(u)"
                            :disabled="!!u.deleted_at">Reset</button>
                        <button class="btn btn-sm btn-outline-warning" @click="toggleStatus(u)"
                            :disabled="!!u.deleted_at">Toggle</button>
                        <button v-if="!u.deleted_at" class="btn btn-sm btn-outline-danger"
                            @click="softDelete(u)">Delete</button>
                        <button v-else class="btn btn-sm btn-success" @click="restore(u)">Restore</button>
                    </div>
                </div>
            </div>

            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">Page {{ users.current_page }} / {{ users.last_page }} • Total {{
                    users.total }}</div>
                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in users.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
                </div>
            </div>
        </div>

        <!-- CREATE MODAL -->
        <div v-if="showCreate" class="modal-backdrop-custom">
            <div class="modal-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Tambah Admin</div>
                    <button class="btn btn-sm btn-outline-secondary" @click="showCreate = false">X</button>
                </div>

                <div class="mb-2">
                    <label class="form-label">Nama</label>
                    <input v-model="createForm.name" class="form-control" />
                    <div v-if="errors.name" class="text-danger small mt-1">{{ errors.name }}</div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Email (@gmail.com)</label>
                    <input v-model="createForm.email" class="form-control" />
                    <div v-if="errors.email" class="text-danger small mt-1">{{ errors.email }}</div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Password</label>
                    <input v-model="createForm.password" type="password" class="form-control" />
                    <div v-if="errors.password" class="text-danger small mt-1">{{ errors.password }}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select v-model="createForm.status" class="form-select">
                        <option value="active">active</option>
                        <option value="inactive">inactive</option>
                    </select>
                    <div v-if="errors.status" class="text-danger small mt-1">{{ errors.status }}</div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" @click="showCreate = false">Batal</button>
                    <button class="btn btn-primary" @click="submitCreate">Simpan</button>
                </div>
            </div>
        </div>

        <!-- EDIT MODAL -->
        <div v-if="showEdit" class="modal-backdrop-custom">
            <div class="modal-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Edit Admin</div>
                    <button class="btn btn-sm btn-outline-secondary" @click="showEdit = false">X</button>
                </div>

                <div class="mb-2">
                    <label class="form-label">Nama</label>
                    <input v-model="editForm.name" class="form-control" />
                </div>

                <div class="mb-2">
                    <label class="form-label">Email (@gmail.com)</label>
                    <input v-model="editForm.email" class="form-control" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select v-model="editForm.status" class="form-select">
                        <option value="active">active</option>
                        <option value="inactive">inactive</option>
                    </select>
                </div>

                <div v-if="errors.update" class="alert alert-danger">{{ errors.update }}</div>

                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" @click="showEdit = false">Batal</button>
                    <button class="btn btn-primary" @click="submitEdit">Update</button>
                </div>
            </div>
        </div>

        <!-- RESET MODAL -->
        <div v-if="showReset" class="modal-backdrop-custom">
            <div class="modal-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Reset Password</div>
                    <button class="btn btn-sm btn-outline-secondary" @click="showReset = false">X</button>
                </div>

                <div class="mb-2">
                    <label class="form-label">Password Baru</label>
                    <input v-model="resetForm.new_password" type="password" class="form-control" />
                    <div v-if="errors.new_password" class="text-danger small mt-1">{{ errors.new_password }}</div>
                </div>

                <div v-if="errors.reset" class="alert alert-danger">{{ errors.reset }}</div>

                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" @click="showReset = false">Batal</button>
                    <button class="btn btn-primary" @click="submitReset">Reset</button>
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
    overflow: hidden;
}

.mini-card {
    border: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.9);
    border-radius: 16px;
    padding: 12px;
    margin: 10px 0;
    box-shadow: 0 10px 22px rgba(2, 6, 23, 0.06);
}

.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 14px;
    z-index: 9999;
}

.modal-card {
    width: min(520px, 100%);
    border-radius: 18px;
    background: white;
    border: 1px solid rgba(2, 6, 23, 0.12);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.25);
    padding: 14px;
}
</style>
