<script setup>
import { computed, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const flash = computed(() => page.props.flash || {});
const show = ref(false);
const msg = ref("");

watch(flash, () => {
    const s = flash.value?.success;
    if (s) {
        msg.value = s;
        show.value = true;
        setTimeout(() => (show.value = false), 2800);
    }
}, { deep: true });
</script>

<template>
    <Transition name="slide-down">
        <div v-if="show" class="toast-wrap">
            <div class="toast-card">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle"></i>
                    <div class="fw-semibold">Berhasil</div>
                </div>
                <div class="small text-muted mt-1">{{ msg }}</div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.toast-wrap {
    position: fixed;
    top: 12px;
    left: 12px;
    right: 12px;
    z-index: 9999;
    display: flex;
    justify-content: center;
}

.toast-card {
    width: min(520px, 100%);
    border-radius: 16px;
    border: 1px solid rgba(2, 6, 23, .08);
    background: rgba(255, 255, 255, .92);
    backdrop-filter: blur(10px);
    box-shadow: 0 18px 40px rgba(2, 6, 23, .18);
    padding: 10px 12px;
}
</style>
