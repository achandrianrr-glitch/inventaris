<script setup>
import ToastHost from "@/Components/ToastHost.vue";
import TopLoadingBar from "@/Components/TopLoadingBar.vue";
</script>

<template>
    <div class="auth-wrap">
        <div class="auth-grid"></div>
        <div class="auth-blob one"></div>
        <div class="auth-blob two"></div>
        <div class="auth-blob three"></div>

        <TopLoadingBar />
        <ToastHost />

        <slot />
    </div>
</template>
