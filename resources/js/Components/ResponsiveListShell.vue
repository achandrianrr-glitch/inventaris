<script setup>
defineProps({
    title: String,
    subtitle: String,
});
</script>

<template>
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1">{{ title }}</h5>
            <div class="text-muted small">{{ subtitle }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <slot name="actions" />
        </div>
    </div>

    <div class="panel p-3 mb-3">
        <slot name="filters" />
    </div>

    <div class="panel">
        <div class="d-none d-md-block">
            <slot name="table" />
        </div>
        <div class="d-md-none p-3">
            <slot name="cards" />
        </div>

        <div class="p-3">
            <slot name="pagination" />
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
</style>
