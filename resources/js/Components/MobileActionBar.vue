<script setup>
defineProps({ show: { type: Boolean, default: true } });
</script>

<template>
    <div v-if="show" class="actionbar d-md-none">
        <slot />
    </div>
</template>

<style scoped>
.actionbar {
    position: sticky;
    bottom: 0;
    z-index: 40;
    padding: 10px 12px;
    background: rgba(255, 255, 255, .92);
    border-top: 1px solid rgba(2, 6, 23, .08);
    backdrop-filter: blur(10px);
    display: flex;
    gap: 10px;
    justify-content: space-between;
}
</style>
