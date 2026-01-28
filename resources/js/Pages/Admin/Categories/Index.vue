<script setup>
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const page = usePage();

const props = defineProps({
    categories: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "all");
const trashed = ref(props.filters?.trashed ?? "without");

const showModal = ref(false);
const mode = ref("create"); // create | edit
const editing = ref(null);

const form = ref({
    name: "",
    description: "",
    status: "active",
});

const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);

let t = null;
watch(search, () => {
    clearTimeout(t);
    t = setTimeout(() => applyFilters(), 350);
});

watch([status, trashed], () => applyFilters());

function applyFilters() {
    router.get(
        "/admin/categories",
        { search: search.value, status: status.value, trashed: trashed.value },
        { preserveState: true, replace: true, preserveScroll: true }
    );
}

function openCreate() {
    mode.value = "create";
    editing.value = null;
    form.value = { name: "", description: "", status: "active" };
    showModal.value = true;
}

function openEdit(cat) {
    mode.value = "edit";
    editing.value = cat;
    form.value = {
        name: cat.name ?? "",
        description: cat.description ?? "",
        status: cat.status ?? "active",
    };
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

function submit() {
    if (mode.value === "create") {
        router.post("/admin/categories", form.value, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        router.put(`/admin/categories/${editing.value.id}`, form.value, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
}

function softDelete(cat) {
    if (!confirm(`Hapus kategori "${cat.name}"? (soft delete)`)) return;

    router.delete(`/admin/categories/${cat.id}`, { preserveScroll: true });
}

function restore(cat) {
    router.patch(`/admin/categories/${cat.id}/restore`, {}, { preserveScroll: true });
}

const exportUrl = computed(() => {
    const params = new URLSearchParams({
        search: search.value || "",
        status: status.value || "all",
        trashed: trashed.value || "without",
    });
    return `/admin/categories-export?${params.toString()}`;
});
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Kategori</h5>
                <div class="text-muted small">CRUD kategori + search/filter + soft delete + export excel</div>
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-outline-success" :href="exportUrl">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </a>
                <button class="btn btn-primary" @click="openCreate">
                    <i class="bi bi-plus-lg me-1"></i> Tambah
                </button>
            </div>
        </div>

        <!-- flash -->
        <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ flashSuccess }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- filters -->
        <div class="panel p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-xl-5">
                    <label class="form-label small text-muted">Search</label>
                    <input v-model="search" type="text" class="form-control" placeholder="Cari nama/deskripsi..." />
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small text-muted">Status</label>
                    <select v-model="status" class="form-select">
                        <option value="all">Semua</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
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

                <div class="col-12 col-xl-3 d-flex justify-content-xl-end gap-2">
                    <button class="btn btn-outline-secondary" @click="applyFilters">
                        <i class="bi bi-funnel me-1"></i> Terapkan
                    </button>
                    <button class="btn btn-outline-dark"
                        @click="() => { search = ''; status = 'all'; trashed = 'without'; applyFilters(); }">
                        Reset
                    </button>
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
                            <th class="text-muted">Deskripsi</th>
                            <th>Status</th>
                            <th class="text-muted">Updated</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="cat in categories.data" :key="cat.id" :class="cat.deleted_at ? 'row-trashed' : ''">
                            <td class="fw-semibold">
                                {{ cat.name }}
                                <span v-if="cat.deleted_at" class="badge text-bg-secondary ms-2">TRASH</span>
                            </td>
                            <td class="text-muted">{{ cat.description || '-' }}</td>
                            <td>
                                <span class="badge"
                                    :class="cat.status === 'active' ? 'text-bg-success' : 'text-bg-warning'">
                                    {{ cat.status }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ new Date(cat.updated_at).toLocaleString() }}</td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" @click="openEdit(cat)"
                                        :disabled="!!cat.deleted_at">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button v-if="!cat.deleted_at" class="btn btn-sm btn-outline-danger"
                                        @click="softDelete(cat)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <button v-else class="btn btn-sm btn-outline-success" @click="restore(cat)">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="categories.data.length === 0">
                            <td colspan="5" class="text-muted p-3">Tidak ada data kategori.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- pagination -->
            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Page {{ categories.current_page }} / {{ categories.last_page }} • Total {{ categories.total }}
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in categories.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
                </div>
            </div>
        </div>

        <!-- mobile cards -->
        <div class="d-md-none">
            <div v-for="cat in categories.data" :key="cat.id" class="mini-card"
                :class="cat.deleted_at ? 'row-trashed' : ''">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-2">
                        <div class="fw-semibold">
                            {{ cat.name }}
                            <span v-if="cat.deleted_at" class="badge text-bg-secondary ms-2">TRASH</span>
                        </div>
                        <div class="text-muted small mt-1">{{ cat.description || '-' }}</div>
                        <div class="mt-2">
                            <span class="badge"
                                :class="cat.status === 'active' ? 'text-bg-success' : 'text-bg-warning'">
                                {{ cat.status }}
                            </span>
                            <span class="text-muted small ms-2">{{ new Date(cat.updated_at).toLocaleString() }}</span>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-sm btn-outline-primary" @click="openEdit(cat)"
                            :disabled="!!cat.deleted_at">
                            Edit
                        </button>
                        <button v-if="!cat.deleted_at" class="btn btn-sm btn-outline-danger" @click="softDelete(cat)">
                            Hapus
                        </button>
                        <button v-else class="btn btn-sm btn-outline-success" @click="restore(cat)">
                            Restore
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="categories.data.length === 0" class="text-muted p-2">Tidak ada data kategori.</div>

            <div class="mt-2 d-flex justify-content-between align-items-center">
                <div class="text-muted small">Total {{ categories.total }}</div>
                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in categories.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
                </div>
            </div>
        </div>

        <!-- Modal (Vue modal, bootstrap style) -->
        <transition name="fade">
            <div v-if="showModal" class="modal-backdrop-custom" @click.self="closeModal">
                <div class="modal-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold">
                            {{ mode === 'create' ? 'Tambah Kategori' : 'Edit Kategori' }}
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" @click="closeModal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Nama</label>
                        <input v-model="form.name" type="text" class="form-control" placeholder="Contoh: Elektronika" />
                        <div v-if="errors.name" class="text-danger small mt-1">{{ errors.name }}</div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Deskripsi</label>
                        <textarea v-model="form.description" class="form-control" rows="3"
                            placeholder="(opsional)"></textarea>
                        <div v-if="errors.description" class="text-danger small mt-1">{{ errors.description }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select v-model="form.status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div v-if="errors.status" class="text-danger small mt-1">{{ errors.status }}</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
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
    transition: transform 160ms ease, box-shadow 160ms ease;
    box-shadow: 0 10px 22px rgba(2, 6, 23, 0.06);
}

.mini-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 30px rgba(2, 6, 23, 0.10);
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
    width: 520px;
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
