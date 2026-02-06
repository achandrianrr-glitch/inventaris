<script setup>
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

defineOptions({ layout: AdminLayout });

const page = usePage();
const props = defineProps({
    setting: Object,
    codeHelp: Object,
});

const flashSuccess = computed(() => page.props.flash?.success);
const errors = computed(() => page.props.errors || {});

const form = ref({
    school_name: props.setting?.school_name ?? "",
    city: props.setting?.city ?? "",
    code_format: props.setting?.code_format ?? "INV-{YYYY}-{SEQ4}",
    notification_email: props.setting?.notification_email ?? "",
    notification_wa: props.setting?.notification_wa ?? "",
});

function submit() {
    router.patch("/admin/settings", form.value, { preserveScroll: true });
}
</script>

<template>
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h5 class="mb-1">Pengaturan Sistem</h5>
                <div class="text-muted small">Konfigurasi sekolah, format kode, dan kontak notifikasi</div>
            </div>
            <button class="btn btn-primary" @click="submit">
                <i class="bi bi-save me-1"></i> Simpan
            </button>
        </div>

        <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ flashSuccess }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <div class="panel p-3">
                    <div class="fw-semibold mb-3">Data Sekolah</div>

                    <div class="mb-2">
                        <label class="form-label">Nama Sekolah</label>
                        <input v-model="form.school_name" class="form-control" />
                        <div v-if="errors.school_name" class="text-danger small mt-1">{{ errors.school_name }}</div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Kota</label>
                        <input v-model="form.city" class="form-control" />
                        <div v-if="errors.city" class="text-danger small mt-1">{{ errors.city }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Format Kode Inventaris</label>
                        <input v-model="form.code_format" class="form-control" placeholder="INV-{YYYY}-{SEQ4}" />
                        <div class="text-muted small mt-1">
                            Dipakai untuk auto-generate kode barang/transaksi (kalau kamu aktifkan format).
                        </div>
                        <div v-if="errors.code_format" class="text-danger small mt-1">{{ errors.code_format }}</div>
                    </div>

                    <div class="fw-semibold mb-2">Notifikasi (Opsional)</div>

                    <div class="mb-2">
                        <label class="form-label">Email Notifikasi</label>
                        <input v-model="form.notification_email" class="form-control" placeholder="contoh@gmail.com" />
                        <div v-if="errors.notification_email" class="text-danger small mt-1">{{
                            errors.notification_email }}</div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">WhatsApp Notifikasi</label>
                        <input v-model="form.notification_wa" class="form-control" placeholder="628xxxxxxxxxx" />
                        <div v-if="errors.notification_wa" class="text-danger small mt-1">{{ errors.notification_wa }}
                        </div>
                        <div class="text-muted small mt-1">Format aman: 62 + nomor (tanpa +). Contoh: 6281234567890
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-3">
                        <button class="btn btn-outline-secondary"
                            @click="router.reload({ only: ['setting'] })">Reload</button>
                        <button class="btn btn-primary" @click="submit">Simpan</button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="panel p-3">
                    <div class="fw-semibold mb-2">Bantuan Format Kode</div>
                    <div class="text-muted small mb-2">Placeholder yang bisa dipakai:</div>

                    <ul class="small mb-3">
                        <li v-for="(v, k) in codeHelp.placeholders" :key="k">
                            <code>{{ k }}</code> — <span class="text-muted">{{ v }}</span>
                        </li>
                    </ul>

                    <div class="fw-semibold mb-2">Contoh</div>
                    <div class="small">
                        <div v-for="(ex, fmt) in codeHelp.examples" :key="fmt" class="mb-2">
                            <div><code>{{ fmt }}</code></div>
                            <div class="text-muted">→ {{ ex }}</div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0 small">
                        Format kode akan terasa “berfungsi penuh” kalau di tahap kode auto-generate kamu pakai helper
                        generator.
                        Kalau belum dipakai, setting tetap tersimpan dan dipakai untuk PDF header.
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
