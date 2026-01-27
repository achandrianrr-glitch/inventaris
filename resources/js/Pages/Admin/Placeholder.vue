<script setup>
defineProps({ title: String })
</script>

<template>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="m-0">{{ title }}</h4>
            <span class="badge text-bg-primary">Setup OK</span>
        </div>

        <div class="card">
            <div class="card-body">
                <p class="mb-2">
                    Project sudah jalan: <strong>Laravel + Inertia Vue + Bootstrap + Tailwind</strong>.
                </p>
                <p class="mb-0 text-muted">
                    welcome.
                </p>
            </div>
        </div>
    </div>
</template>
