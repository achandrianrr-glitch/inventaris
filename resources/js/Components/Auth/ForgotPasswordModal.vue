<script setup>
import { ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";

const props = defineProps({
    open: { type: Boolean, default: false },
});
const emit = defineEmits(["close"]);

const page = usePage();
const errors = ref({});

watch(() => page.props.errors, (v) => {
    errors.value = v || {};
}, { deep: true });

const email = ref("");

function submit() {
    // kirim ke route default Breeze: POST /forgot-password (password.email)
    router.post("/forgot-password", { email: email.value }, {
        preserveScroll: true,
        onSuccess: () => {
            // flash success akan muncul di ToastHost
            emit("close");
        },
    });
}

watch(() => props.open, (v) => {
    if (v) {
        // reset state saat dibuka
        email.value = "";
        errors.value = {};
    }
});
</script>

<template>
    <Transition name="fade">
        <div v-if="open" class="modal-backdrop-custom" @click.self="emit('close')">
            <Transition name="scale">
                <div class="modal-card-auth">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold text-white">Lupa Password</div>
                        <button class="btn btn-sm btn-outline-light" style="min-width:44px; min-height:44px"
                            @click="emit('close')">X</button>
                    </div>

                    <div class="text-white-50 small mb-3">
                        Masukkan email Gmail admin. Link reset akan dikirim ke email.
                    </div>

                    <div class="mb-2">
                        <label class="auth-label">Email (@gmail.com)</label>
                        <input v-model="email" class="auth-input" placeholder="contoh@gmail.com"
                            style="padding-left:12px" />
                        <div v-if="errors.email" class="auth-error">{{ errors.email }}</div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-3">
                        <button class="btn btn-outline-light" style="min-width:44px; min-height:44px"
                            @click="emit('close')">Batal</button>
                        <button class="auth-btn" @click="submit">
                            Kirim Link
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<style scoped>
.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 14px;
    z-index: 9999;
}

.modal-card-auth {
    width: min(520px, 100%);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, .16);
    background: rgba(15, 23, 42, .78);
    backdrop-filter: blur(16px);
    box-shadow: 0 22px 70px rgba(0, 0, 0, .45);
    padding: 14px;
}
</style>
