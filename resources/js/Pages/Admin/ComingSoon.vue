<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
defineOptions({ layout: AdminLayout });

defineProps({
    title: { type: String, default: "Coming Soon" }
});
</script>

<template>
    <div class="container py-3">
        <div class="card border-0 shadow-sm" style="border-radius:18px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-1">{{ title }}</h5>
                        <div class="text-muted">Halaman ini akan dikerjakan di tahap berikutnya.</div>
                    </div>
                    <a href="/admin" class="btn btn-outline-primary">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</template>
