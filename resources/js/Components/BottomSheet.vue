<script setup>
defineProps({ open: { type: Boolean, default: false }, title: { type: String, default: "Filter" } });
const emit = defineEmits(["close"]);
</script>

<template>
    <div v-if="open" class="sheet-overlay d-md-none" @click="emit('close')"></div>

    <div class="sheet d-md-none" :class="{ open }">
        <div class="sheet-header">
            <div class="fw-semibold">{{ title }}</div>
            <button class="btn btn-sm btn-outline-secondary btn-touch" @click="emit('close')">Tutup</button>
        </div>
        <div class="sheet-body">
            <slot />
        </div>
    </div>
</template>

<style scoped>
.sheet-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .45);
    z-index: 9998;
}

.sheet {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, .97);
    border-top-left-radius: 18px;
    border-top-right-radius: 18px;
    border-top: 1px solid rgba(2, 6, 23, .08);
    box-shadow: 0 -18px 40px rgba(0, 0, 0, .18);
    transform: translateY(105%);
    transition: transform .22s ease;
    z-index: 9999;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
}

.sheet.open {
    transform: translateY(0);
}

.sheet-header {
    padding: 12px;
    border-bottom: 1px solid rgba(2, 6, 23, .08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sheet-body {
    padding: 12px;
    overflow: auto;
}

.btn-touch {
    min-width: 44px;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>
