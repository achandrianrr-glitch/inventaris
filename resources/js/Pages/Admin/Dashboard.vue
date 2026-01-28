<script setup>
import { computed } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    kpi: Object,
    latestItems: Array,
    latestDamages: Array,
    dueSoon: Array,
    notifications: Array,
    unreadCount: Number,
});

const k = computed(() => props.kpi || {});
</script>

<template>
    <div>
        <!-- KPI -->
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-ico"><i class="bi bi-box-seam"></i></div>
                    <div class="kpi-meta">
                        <div class="kpi-val">{{ k.item_types ?? 0 }}</div>
                        <div class="kpi-lbl">Jenis Barang</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-ico"><i class="bi bi-bag-check"></i></div>
                    <div class="kpi-meta">
                        <div class="kpi-val">{{ k.units_borrowed ?? 0 }}</div>
                        <div class="kpi-lbl">Unit Dipinjam</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-ico"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="kpi-meta">
                        <div class="kpi-val">{{ k.units_damaged ?? 0 }}</div>
                        <div class="kpi-lbl">Unit Rusak</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="kpi-card">
                    <div class="kpi-ico"><i class="bi bi-bookmark-star"></i></div>
                    <div class="kpi-meta">
                        <div class="kpi-val">{{ k.brands ?? 0 }}</div>
                        <div class="kpi-lbl">Total Merek</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- quick stats -->
        <div class="row g-3 mt-1">
            <div class="col-12 col-xl-8">
                <div class="panel">
                    <div class="panel-head">
                        <div class="fw-semibold">Barang Terbaru</div>
                        <a class="btn btn-sm btn-outline-primary" href="/admin/items">Lihat Barang</a>
                    </div>

                    <!-- Desktop table -->
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama</th>
                                        <th>Kategori</th>
                                        <th>Merek</th>
                                        <th>Lokasi</th>
                                        <th class="text-end">Stok Tersedia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="it in latestItems" :key="it.id">
                                        <td class="fw-semibold">{{ it.code }}</td>
                                        <td>{{ it.name }}</td>
                                        <td class="text-muted">{{ it.category?.name }}</td>
                                        <td class="text-muted">{{ it.brand?.name }}</td>
                                        <td class="text-muted">{{ it.location?.name }}</td>
                                        <td class="text-end">
                                            <span class="badge text-bg-light">{{ it.stock_available }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="latestItems.length === 0">
                                        <td colspan="6" class="text-muted p-3">Belum ada data barang.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile cards -->
                    <div class="d-md-none">
                        <div v-for="it in latestItems" :key="it.id" class="mini-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="fw-semibold">{{ it.name }}</div>
                                    <div class="text-muted small">{{ it.code }} • {{ it.brand?.name }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="badge text-bg-primary">Stok {{ it.stock_available }}</div>
                                    <div class="text-muted small">{{ it.location?.name }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="latestItems.length === 0" class="text-muted p-2">Belum ada data barang.</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="panel">
                    <div class="panel-head">
                        <div class="fw-semibold">Ringkas Sistem</div>
                        <a class="btn btn-sm btn-outline-secondary" href="/admin/reports/inventory">Laporan</a>
                    </div>

                    <div class="p-3">
                        <div class="stat-line">
                            <span class="text-muted">Total Unit</span>
                            <span class="fw-semibold">{{ k.units_total ?? 0 }}</span>
                        </div>
                        <div class="stat-line">
                            <span class="text-muted">Unit Tersedia</span>
                            <span class="fw-semibold">{{ k.units_available ?? 0 }}</span>
                        </div>
                        <div class="stat-line">
                            <span class="text-muted">Peminjam</span>
                            <span class="fw-semibold">{{ k.borrowers ?? 0 }}</span>
                        </div>
                        <div class="stat-line">
                            <span class="text-muted">Stok Menipis (&lt; 5)</span>
                            <span class="fw-semibold">{{ k.low_stock ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <div class="panel mt-3">
                    <div class="panel-head">
                        <div class="fw-semibold">Jatuh Tempo Terdekat</div>
                        <a class="btn btn-sm btn-outline-primary" href="/admin/borrowings">Monitoring</a>
                    </div>

                    <div class="p-2">
                        <div v-for="b in dueSoon" :key="b.id" class="due-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="fw-semibold text-truncate">
                                        {{ b.borrower?.name }} — {{ b.item?.name }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ b.code }} • Qty {{ b.qty }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="badge" :class="b.is_overdue ? 'text-bg-danger' : 'text-bg-warning'">
                                        {{ b.is_overdue ? 'Overdue' : 'Soon' }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ new Date(b.return_due).toLocaleString() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="dueSoon.length === 0" class="text-muted p-2">Tidak ada pinjaman aktif.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- damages -->
        <div class="row g-3 mt-1">
            <div class="col-12">
                <div class="panel">
                    <div class="panel-head">
                        <div class="fw-semibold">Kerusakan Terbaru</div>
                        <a class="btn btn-sm btn-outline-danger" href="/admin/damages">Lihat Kerusakan</a>
                    </div>

                    <div class="table-responsive d-none d-md-block">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Barang</th>
                                    <th>Level</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in latestDamages" :key="d.id">
                                    <td class="fw-semibold">{{ d.code }}</td>
                                    <td>{{ d.item?.name }} <span class="text-muted">({{ d.item?.code }})</span></td>
                                    <td>
                                        <span class="badge text-bg-light">{{ d.damage_level }}</span>
                                    </td>
                                    <td>
                                        <span class="badge"
                                            :class="d.status === 'completed' ? 'text-bg-success' : (d.status === 'in_progress' ? 'text-bg-primary' : 'text-bg-secondary')">
                                            {{ d.status }}
                                        </span>
                                    </td>
                                    <td class="text-muted">{{ d.reported_date }}</td>
                                </tr>
                                <tr v-if="latestDamages.length === 0">
                                    <td colspan="5" class="text-muted p-3">Belum ada laporan kerusakan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-md-none p-2">
                        <div v-for="d in latestDamages" :key="d.id" class="mini-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="fw-semibold">{{ d.item?.name }}</div>
                                    <div class="text-muted small">{{ d.code }} • {{ d.damage_level }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="badge text-bg-secondary">{{ d.status }}</div>
                                    <div class="text-muted small">{{ d.reported_date }}</div>
                                </div>
                            </div>
                        </div>
                        <div v-if="latestDamages.length === 0" class="text-muted p-2">Belum ada laporan kerusakan.</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.kpi-card {
    border-radius: 18px;
    border: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    padding: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 12px 28px rgba(2, 6, 23, 0.08);
    transition: transform 160ms ease, box-shadow 160ms ease;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 18px 45px rgba(2, 6, 23, 0.12);
}

.kpi-ico {
    width: 46px;
    height: 46px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(30, 77, 183, 0.10);
    color: #0f2a6b;
    font-size: 20px;
}

.kpi-val {
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1.1;
}

.kpi-lbl {
    color: rgba(15, 23, 42, 0.65);
    font-size: .9rem;
}

.panel {
    border-radius: 18px;
    border: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    box-shadow: 0 12px 28px rgba(2, 6, 23, 0.08);
    overflow: hidden;
}

.panel-head {
    padding: 12px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(2, 6, 23, 0.08);
}

.mini-card {
    border: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.75);
    border-radius: 16px;
    padding: 12px;
    margin: 10px 0;
    transition: transform 160ms ease;
}

.mini-card:hover {
    transform: translateY(-1px);
}

.stat-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px dashed rgba(2, 6, 23, 0.12);
}

.stat-line:last-child {
    border-bottom: 0;
}

.due-item {
    border: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.75);
    border-radius: 16px;
    padding: 12px;
    margin: 10px;
}
</style>
