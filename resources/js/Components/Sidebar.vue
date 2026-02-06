<script setup>
defineProps({
    collapsed: { type: Boolean, default: false },
});
</script>

<template>
    <aside class="sidebar d-none d-lg-flex" :class="{ collapsed }">
        <div class="sidebar-inner">
            <slot />
        </div>
    </aside>
</template>

<style scoped>
.sidebar {
    position: sticky;
    top: 0;
    height: 100vh;
    width: 280px;
    transition: width .2s ease;
    border-right: 1px solid rgba(2, 6, 23, .08);
    background: rgba(255, 255, 255, .85);
    backdrop-filter: blur(10px);
}

.sidebar.collapsed {
    width: 92px;
}

.sidebar-inner {
    height: 100%;
    overflow: auto;
    padding: 12px;
}
</style>
