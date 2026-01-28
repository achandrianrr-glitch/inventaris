<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    item: Object,
});
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <div class="text-muted small">{{ item.code }}</div>
                <h5 class="mb-0">{{ item.name }}</h5>
            </div>
            <button class="btn btn-outline-secondary" @click="router.get('/admin/items')">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </button>
        </div>

        <div class="panel p-3">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="text-muted small">Kategori</div>
                    <div class="fw-semibold">{{ item.category?.name ?? '-' }}</div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="text-muted small">Merek</div>
                    <div class="fw-semibold">{{ item.brand?.name ?? '-' }}</div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="text-muted small">Lokasi</div>
                    <div class="fw-semibold">{{ item.location?.name ?? '-' }}</div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="text-muted small">Kondisi / Status</div>
                    <div class="d-flex gap-2">
                        <span class="badge text-bg-light">{{ item.condition }}</span>
                        <span class="badge"
                            :class="item.status === 'active' ? 'text-bg-success' : (item.status === 'service' ? 'text-bg-warning' : 'text-bg-secondary')">
                            {{ item.status }}
                        </span>
                    </div>
                </div>

                <div class="col-12">
                    <div class="text-muted small">Spesifikasi</div>
                    <div class="fw-semibold">{{ item.specification || '-' }}</div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="text-muted small">Tahun</div>
                    <div class="fw-semibold">{{ item.purchase_year || '-' }}</div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="text-muted small">Harga</div>
                    <div class="fw-semibold">{{ item.purchase_price ?? '-' }}</div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="text-muted small">Stok</div>
                    <div class="fw-semibold">
                        Total: {{ item.stock_total }} • Tersedia: {{ item.stock_available }} • Dipinjam: {{
                        item.stock_borrowed }} • Rusak: {{ item.stock_damaged }}
                    </div>
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
