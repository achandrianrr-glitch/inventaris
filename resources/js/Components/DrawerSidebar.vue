<script setup>
defineProps({
    open: { type: Boolean, default: false },
});
const emit = defineEmits(["close"]);
</script>

<template>
    <!-- overlay -->
    <div v-if="open" class="drawer-overlay d-lg-none" @click="emit('close')"></div>

    <!-- drawer -->
    <aside class="drawer d-lg-none" :class="{ open }">
        <div class="drawer-header">
            <div class="fw-semibold">Menu</div>
            <button class="btn btn-sm btn-outline-secondary" @click="emit('close')">X</button>
        </div>
        <div class="drawer-body">
            <slot />
        </div>
    </aside>
</template>

<style scoped>
.drawer-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .45);
    z-index: 9998;
}

.drawer {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: min(86vw, 320px);
    background: rgba(255, 255, 255, .96);
    border-right: 1px solid rgba(2, 6, 23, .08);
    box-shadow: 18px 0 40px rgba(0, 0, 0, .18);
    transform: translateX(-105%);
    transition: transform .22s ease;
    z-index: 9999;
    display: flex;
    flex-direction: column;
}

.drawer.open {
    transform: translateX(0);
}

.drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px;
    border-bottom: 1px solid rgba(2, 6, 23, .08);
}

.drawer-body {
    padding: 10px;
    overflow: auto;
}
</style>
