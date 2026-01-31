<script setup>
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import axios from "axios";

defineOptions({ layout: AdminLayout });

const page = usePage();
const props = defineProps({
  opnames: Object,
  filters: Object,
  options: Object,
});

const errors = computed(() => page.props.errors || {});
const flashSuccess = computed(() => page.props.flash?.success);

// filter riwayat
const f = ref({
  search: props.filters?.search ?? "",
  location_id: props.filters?.location_id ?? "",
  date_from: props.filters?.date_from ?? "",
  date_to: props.filters?.date_to ?? "",
});

let t = null;
watch(() => f.value.search, () => {
  clearTimeout(t);
  t = setTimeout(() => applyFilters(), 350);
});
watch(() => [f.value.location_id, f.value.date_from, f.value.date_to], () => applyFilters());

function applyFilters() {
  router.get("/admin/stock-opnames", f.value, { preserveState: true, replace: true, preserveScroll: true });
}

// ===== create form
const create = ref({
  location_id: props.options?.locations?.[0]?.id ?? "",
  opname_date: new Date().toISOString().slice(0, 10),
  notes: "",
});

const items = ref([]);
const loadingItems = ref(false);

// lines untuk submit
const lines = ref([]);

async function loadItems() {
  if (!create.value.location_id) return;

  loadingItems.value = true;
  items.value = [];
  lines.value = [];

  try {
    const res = await axios.get("/admin/stock-opnames/items", { params: { location_id: create.value.location_id } });
    items.value = res.data || [];

    // init lines dari items
    lines.value = items.value.map(it => ({
      item_id: it.id,
      code: it.code,
      name: it.name,
      system_stock: Number(it.stock_total ?? 0),
      physical_stock: Number(it.stock_total ?? 0), // default sama biar cepat
      difference: 0,
      status: "normal",
    }));

    recalcAll();
  } finally {
    loadingItems.value = false;
  }
}

watch(() => create.value.location_id, () => loadItems(), { immediate: true });

function recalcLine(i) {
  const row = lines.value[i];
  const diff = Number(row.physical_stock) - Number(row.system_stock);
  row.difference = diff;
  row.status = (diff === 0) ? "normal" : "discrepancy";
}
function recalcAll() {
  for (let i = 0; i < lines.value.length; i++) recalcLine(i);
}

const totals = computed(() => {
  const total = lines.value.length;
  const discrepancy = lines.value.filter(x => x.status === "discrepancy").length;
  const normal = total - discrepancy;
  return { total, normal, discrepancy };
});

function submit() {
  const payload = {
    location_id: create.value.location_id,
    opname_date: create.value.opname_date,
    notes: create.value.notes,
    lines: lines.value.map(x => ({
      item_id: x.item_id,
      physical_stock: Number(x.physical_stock),
    })),
  };

  router.post("/admin/stock-opnames", payload, {
    preserveScroll: true,
    onSuccess: () => {
      // reload items supaya input fresh
      loadItems();
    }
  });
}

function exportCsv() {
  const q = new URLSearchParams({
    location_id: f.value.location_id || "",
    date_from: f.value.date_from || "",
    date_to: f.value.date_to || "",
  }).toString();
  window.location.href = `/admin/stock-opnames/export/csv?${q}`;
}
</script>

<template>
  <div class="container-fluid">
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
      <div>
        <h5 class="mb-1">Stock Opname</h5>
        <div class="text-muted small">Pilih lokasi → input stok fisik → selisih otomatis → normal/discrepancy</div>
      </div>

      <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="/admin/stock-opnames/review">
          <i class="bi bi-exclamation-triangle me-1"></i> Review Selisih
        </a>
      </div>
    </div>

    <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ flashSuccess }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Create Opname -->
    <div class="panel p-3 mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-lg-4">
          <label class="form-label">Lokasi</label>
          <select v-model="create.location_id" class="form-select">
            <option v-for="l in options.locations" :key="l.id" :value="l.id">{{ l.name }}</option>
          </select>
          <div v-if="errors.location_id" class="text-danger small mt-1">{{ errors.location_id }}</div>
        </div>

        <div class="col-6 col-lg-3">
          <label class="form-label">Tanggal Opname</label>
          <input v-model="create.opname_date" type="date" class="form-control" />
          <div v-if="errors.opname_date" class="text-danger small mt-1">{{ errors.opname_date }}</div>
        </div>

        <div class="col-6 col-lg-5">
          <label class="form-label">Catatan (opsional)</label>
          <input v-model="create.notes" type="text" class="form-control" placeholder="Misal: Opname rutin bulan ini..." />
          <div v-if="errors.notes" class="text-danger small mt-1">{{ errors.notes }}</div>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mt-3">
        <div class="text-muted small">
          Total item: <b>{{ totals.total }}</b> • Normal: <b>{{ totals.normal }}</b> • Selisih: <b class="text-danger">{{ totals.discrepancy }}</b>
        </div>
        <button class="btn btn-primary" :disabled="loadingItems || lines.length === 0" @click="submit">
          <i class="bi bi-save me-1"></i> Simpan Opname
        </button>
      </div>

      <div v-if="loadingItems" class="text-muted small mt-3">Loading item lokasi...</div>

      <!-- Desktop table -->
      <div v-if="!loadingItems" class="d-none d-md-block mt-3">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>Barang</th>
                <th class="text-end">Stok Sistem</th>
                <th class="text-end">Stok Fisik</th>
                <th class="text-end">Selisih</th>
                <th class="text-end">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, i) in lines" :key="row.item_id">
                <td class="fw-semibold">{{ row.code }} — {{ row.name }}</td>
                <td class="text-end text-muted">{{ row.system_stock }}</td>
                <td class="text-end">
                  <input
                    v-model.number="row.physical_stock"
                    type="number" min="0"
                    class="form-control form-control-sm text-end"
                    @input="recalcLine(i)"
                  />
                  <div v-if="errors[`lines.${i}.physical_stock`]" class="text-danger small mt-1">
                    {{ errors[`lines.${i}.physical_stock`] }}
                  </div>
                </td>
                <td class="text-end fw-semibold" :class="row.difference === 0 ? 'text-muted' : (row.difference > 0 ? 'text-success' : 'text-danger')">
                  {{ row.difference }}
                </td>
                <td class="text-end">
                  <span class="badge" :class="row.status === 'normal' ? 'text-bg-success' : 'text-bg-danger'">
                    {{ row.status }}
                  </span>
                </td>
              </tr>

              <tr v-if="lines.length === 0">
                <td colspan="5" class="text-muted p-3">Tidak ada barang di lokasi ini.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile cards -->
      <div v-if="!loadingItems" class="d-md-none mt-3">
        <div v-for="(row, i) in lines" :key="row.item_id" class="mini-card">
          <div class="fw-semibold">{{ row.code }} — {{ row.name }}</div>
          <div class="text-muted small mt-1">Sistem: <b>{{ row.system_stock }}</b></div>

          <div class="mt-2">
            <label class="form-label small">Stok Fisik</label>
            <input
              v-model.number="row.physical_stock"
              type="number" min="0"
              class="form-control"
              @input="recalcLine(i)"
            />
            <div v-if="errors[`lines.${i}.physical_stock`]" class="text-danger small mt-1">
              {{ errors[`lines.${i}.physical_stock`] }}
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="small">
              Selisih:
              <span class="fw-semibold" :class="row.difference === 0 ? 'text-muted' : (row.difference > 0 ? 'text-success' : 'text-danger')">
                {{ row.difference }}
              </span>
            </div>
            <span class="badge" :class="row.status === 'normal' ? 'text-bg-success' : 'text-bg-danger'">
              {{ row.status }}
            </span>
          </div>
        </div>

        <div v-if="lines.length === 0" class="text-muted p-2">Tidak ada barang di lokasi ini.</div>
      </div>
    </div>

    <!-- Riwayat Opname -->
    <div class="panel p-3">
      <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-2">
        <div class="fw-semibold">Riwayat Opname</div>
        <button class="btn btn-outline-secondary btn-sm" @click="exportCsv">
          <i class="bi bi-download me-1"></i> Export CSV
        </button>
      </div>

      <div class="row g-2 align-items-end mb-2">
        <div class="col-12 col-lg-4">
          <label class="form-label small text-muted">Search</label>
          <input v-model="f.search" class="form-control" placeholder="Cari kode OPN / barang..." />
        </div>
        <div class="col-6 col-lg-3">
          <label class="form-label small text-muted">Lokasi</label>
          <select v-model="f.location_id" class="form-select">
            <option value="">Semua</option>
            <option v-for="l in options.locations" :key="l.id" :value="l.id">{{ l.name }}</option>
          </select>
        </div>
        <div class="col-3 col-lg-2">
          <label class="form-label small text-muted">Dari</label>
          <input v-model="f.date_from" type="date" class="form-control" />
        </div>
        <div class="col-3 col-lg-2">
          <label class="form-label small text-muted">Sampai</label>
          <input v-model="f.date_to" type="date" class="form-control" />
        </div>
      </div>

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
              <th class="text-end">Validasi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="o in opnames.data" :key="o.id">
              <td class="text-muted">{{ o.code }}</td>
              <td class="text-muted">{{ o.opname_date }}</td>
              <td class="text-muted">{{ o.item?.location?.name ?? '-' }}</td>
              <td class="fw-semibold">{{ o.item?.code ?? '-' }} — {{ o.item?.name ?? '-' }}</td>
              <td class="text-end text-muted">{{ o.system_stock }}</td>
              <td class="text-end text-muted">{{ o.physical_stock }}</td>
              <td class="text-end fw-semibold" :class="o.difference === 0 ? 'text-muted' : (o.difference > 0 ? 'text-success' : 'text-danger')">
                {{ o.difference }}
              </td>
              <td class="text-end">
                <span class="badge" :class="o.validation === 'approved' ? 'text-bg-success' : (o.validation === 'review' ? 'text-bg-danger' : 'text-bg-secondary')">
                  {{ o.validation }}
                </span>
              </td>
            </tr>
            <tr v-if="opnames.data.length === 0">
              <td colspan="8" class="text-muted p-3">Belum ada data opname.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
          Page {{ opnames.current_page }} / {{ opnames.last_page }} • Total {{ opnames.total }}
        </div>
        <div class="d-flex flex-wrap gap-1">
          <button
            v-for="(l, idx) in opnames.links"
            :key="idx"
            class="btn btn-sm"
            :class="l.active ? 'btn-primary' : 'btn-outline-secondary'"
            :disabled="!l.url"
            v-html="l.label"
            @click="l.url && router.get(l.url, {}, { preserveState: true, preserveScroll: true })"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.panel{
  border-radius: 18px;
  border: 1px solid rgba(2,6,23,0.08);
  background: rgba(255,255,255,0.85);
  backdrop-filter: blur(10px);
  box-shadow: 0 12px 28px rgba(2,6,23,0.08);
  overflow:hidden;
}
.mini-card{
  border: 1px solid rgba(2,6,23,0.08);
  background: rgba(255,255,255,0.85);
  border-radius: 16px;
  padding: 12px;
  margin: 10px 0;
  box-shadow: 0 10px 22px rgba(2,6,23,0.06);
}
</style>
