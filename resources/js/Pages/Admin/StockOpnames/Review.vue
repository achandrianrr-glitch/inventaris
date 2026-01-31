<script setup>
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const page = usePage();
const props = defineProps({
    reviewItems: Object,
    filters: Object,
    options: Object,
});

const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);

const f = ref({
    location_id: props.filters?.location_id ?? "",
    date_from: props.filters?.date_from ?? "",
    date_to: props.filters?.date_to ?? "",
});

watch(() => [f.value.location_id, f.value.date_from, f.value.date_to], () => {
    router.get("/admin/stock-opnames/review", f.value, { preserveState: true, replace: true, preserveScroll: true });
});

function approveRow(id) {
    router.patch(`/admin/stock-opnames/${id}/approve`, {}, { preserveScroll: true });
}
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Review Selisih Opname</h5>
                <div class="text-muted small">Hanya data discrepancy yang butuh approval (validation=review)</div>
            </div>

            <a class="btn btn-outline-secondary" href="/admin/stock-opnames">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ flashSuccess }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <div v-if="errors.approve" class="alert alert-danger">
            {{ errors.approve }}
        </div>

        <div class="panel p-3 mb-3">
            <div class="row g-2">
                <div class="col-12 col-lg-4">
                    <label class="form-label small text-muted">Lokasi</label>
                    <select v-model="f.location_id" class="form-select">
                        <option value="">Semua</option>
                        <option v-for="l in options.locations" :key="l.id" :value="l.id">{{ l.name }}</option>
                    </select>
                </div>
                <div class="col-6 col-lg-4">
                    <label class="form-label small text-muted">Dari</label>
                    <input v-model="f.date_from" type="date" class="form-control" />
                </div>
                <div class="col-6 col-lg-4">
                    <label class="form-label small text-muted">Sampai</label>
                    <input v-model="f.date_to" type="date" class="form-control" />
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="table-responsive d-none d-md-block">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Lokasi</th>
                            <th>Barang</th>
                            <th class="text-end">Sistem</th>
                            <th class="text-end">Fisik</th>
                            <th class="text-end">Selisih</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in reviewItems.data" :key="r.id">
                            <td class="text-muted">{{ r.code }}</td>
                            <td class="text-muted">{{ r.opname_date }}</td>
                            <td class="text-muted">{{ r.item?.location?.name ?? '-' }}</td>
                            <td class="fw-semibold">{{ r.item?.code ?? '-' }} — {{ r.item?.name ?? '-' }}</td>
                            <td class="text-end text-muted">{{ r.system_stock }}</td>
                            <td class="text-end text-muted">{{ r.physical_stock }}</td>
                            <td class="text-end fw-semibold" :class="r.difference > 0 ? 'text-success' : 'text-danger'">
                                {{ r.difference }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-primary" @click="approveRow(r.id)">
                                    Approve (Apply)
                                </button>
                            </td>
                        </tr>

                        <tr v-if="reviewItems.data.length === 0">
                            <td colspan="8" class="text-muted p-3">Tidak ada selisih yang perlu di-approve.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-md-none p-3">
                <div v-for="r in reviewItems.data" :key="r.id" class="mini-card">
                    <div class="text-muted small">{{ r.code }} • {{ r.opname_date }}</div>
                    <div class="fw-semibold mt-1">{{ r.item?.code ?? '-' }} — {{ r.item?.name ?? '-' }}</div>
                    <div class="text-muted small mt-1">Lokasi: {{ r.item?.location?.name ?? '-' }}</div>
                    <div class="text-muted small mt-1">Sistem: <b>{{ r.system_stock }}</b> • Fisik: <b>{{
                            r.physical_stock }}</b></div>
                    <div class="text-muted small mt-1">
                        Selisih:
                        <span class="fw-semibold" :class="r.difference > 0 ? 'text-success' : 'text-danger'">{{
                            r.difference }}</span>
                    </div>
                    <button class="btn btn-primary w-100 mt-2" @click="approveRow(r.id)">Approve (Apply)</button>
                </div>

                <div v-if="reviewItems.data.length === 0" class="text-muted">Tidak ada selisih yang perlu di-approve.
                </div>
            </div>

            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Page {{ reviewItems.current_page }} / {{ reviewItems.last_page }} • Total {{ reviewItems.total }}
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <button v-for="(l, idx) in reviewItems.links" :key="idx" class="btn btn-sm"
                        :class="l.active ? 'btn-primary' : 'btn-outline-secondary'" :disabled="!l.url" v-html="l.label"
                        @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })" />
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
    background: rgba(255, 255, 255, 0.85);
    border-radius: 16px;
    padding: 12px;
    margin: 10px 0;
    box-shadow: 0 10px 22px rgba(2, 6, 23, 0.06);
}
</style>
