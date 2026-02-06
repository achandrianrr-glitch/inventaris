<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
  logs: Object,
  admins: Array,
  filters: Object,
  modules: Array,
  actions: Array,
});

const f = ref({
  admin_id: props.filters?.admin_id ?? "",
  module: props.filters?.module ?? "",
  action: props.filters?.action ?? "",
  date_from: props.filters?.date_from ?? "",
  date_to: props.filters?.date_to ?? "",
  search: props.filters?.search ?? "",
});

let t = null;
watch(() => f.value.search, () => {
  clearTimeout(t);
  t = setTimeout(() => apply(), 350);
});
watch(() => [f.value.admin_id, f.value.module, f.value.action, f.value.date_from, f.value.date_to], () => apply());

function apply() {
  router.get("/admin/activity-logs", f.value, { preserveState: true, replace: true, preserveScroll: true });
}

function exportExcel() {
  const p = new URLSearchParams();
  Object.entries(f.value).forEach(([k,v]) => { if (v !== "" && v !== null && v !== undefined) p.set(k, v); });
  window.location.href = "/admin/activity-logs/excel?" + p.toString();
}
</script>

<template>
  <div class="container-fluid">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
      <div>
        <h5 class="mb-1">Log Aktivitas</h5>
        <div class="text-muted small">Tracking aktivitas admin (CRUD, export, dll)</div>
      </div>

      <button class="btn btn-success" @click="exportExcel">
        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
      </button>
    </div>

    <div class="panel p-3 mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-lg-4">
          <label class="form-label small text-muted">Search</label>
          <input v-model="f.search" class="form-control" placeholder="Cari description / ip / module..." />
        </div>

        <div class="col-6 col-lg-2">
          <label class="form-label small text-muted">Admin</label>
          <select v-model="f.admin_id" class="form-select">
            <option value="">Semua</option>
            <option v-for="a in admins" :key="a.id" :value="a.id">{{ a.name }} ({{ a.email }})</option>
          </select>
        </div>

        <div class="col-6 col-lg-2">
          <label class="form-label small text-muted">Module</label>
          <select v-model="f.module" class="form-select">
            <option value="">Semua</option>
            <option v-for="m in modules" :key="m" :value="m">{{ m }}</option>
          </select>
        </div>

        <div class="col-6 col-lg-2">
          <label class="form-label small text-muted">Action</label>
          <select v-model="f.action" class="form-select">
            <option value="">Semua</option>
            <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
          </select>
        </div>

        <div class="col-6 col-lg-2">
          <label class="form-label small text-muted">Date From</label>
          <input v-model="f.date_from" type="date" class="form-control" />
        </div>

        <div class="col-6 col-lg-2">
          <label class="form-label small text-muted">Date To</label>
          <input v-model="f.date_to" type="date" class="form-control" />
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="table-responsive d-none d-md-block">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Waktu</th>
              <th>Admin</th>
              <th>Module</th>
              <th>Action</th>
              <th>Description</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="l in logs.data" :key="l.id">
              <td class="text-muted">{{ l.created_at }}</td>
              <td>
                <div class="fw-semibold">{{ l.admin?.name ?? '-' }}</div>
                <div class="text-muted small">{{ l.admin?.email ?? '' }}</div>
              </td>
              <td><span class="badge text-bg-secondary">{{ l.module }}</span></td>
              <td><span class="badge text-bg-primary">{{ l.action }}</span></td>
              <td class="text-muted">{{ l.description }}</td>
              <td class="text-muted">{{ l.ip_address }}</td>
            </tr>
            <tr v-if="logs.data.length === 0">
              <td colspan="6" class="text-muted p-3">Belum ada log.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile cards -->
      <div class="d-md-none p-3">
        <div v-for="l in logs.data" :key="l.id" class="mini-card">
          <div class="d-flex justify-content-between">
            <div class="fw-semibold">{{ l.admin?.name ?? '-' }}</div>
            <div class="text-muted small">{{ l.created_at }}</div>
          </div>
          <div class="mt-1">
            <span class="badge text-bg-secondary me-1">{{ l.module }}</span>
            <span class="badge text-bg-primary">{{ l.action }}</span>
          </div>
          <div class="text-muted small mt-2">{{ l.description }}</div>
          <div class="text-muted small mt-1">IP: {{ l.ip_address ?? '-' }}</div>
        </div>
      </div>

      <div class="p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">Page {{ logs.current_page }} / {{ logs.last_page }} • Total {{ logs.total }}</div>
        <div class="d-flex flex-wrap gap-1">
          <button
            v-for="(l, idx) in logs.links"
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
  background: rgba(255,255,255,0.9);
  border-radius: 16px;
  padding: 12px;
  margin: 10px 0;
  box-shadow: 0 10px 22px rgba(2,6,23,0.06);
}
</style>
