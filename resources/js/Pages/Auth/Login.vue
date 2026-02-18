<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const loading = ref(false);
const errors = ref({});

const submit = () => {
    loading.value = true;
    errors.value = {};

    router.post('/login', {
        email: email.value,
        password: password.value,
    }, {
        onError: (errs) => {
            errors.value = errs;
            loading.value = false;
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};
</script>

<template>
    <Head title="Login — Benesystem" />

    <div class="login-wrapper">
        <!-- Background blobs -->
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>

        <div class="login-card">
            <!-- Logo / Header -->
            <div class="login-header">
                <div class="logo-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="logo-icon">
                        <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                    </svg>
                </div>
                <h1 class="login-title">Benesystem</h1>
                <p class="login-subtitle">Bayambang Benefits Management System</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="login-form">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z" />
                            <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z" />
                        </svg>
                        <input
                            id="email"
                            v-model="email"
                            type="email"
                            class="form-input"
                            :class="{ 'input-error': errors.email }"
                            placeholder="you@bayambang.gov.ph"
                            required
                            autofocus
                            autocomplete="email"
                        />
                    </div>
                    <p v-if="errors.email" class="error-msg">{{ errors.email }}</p>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
                        </svg>
                        <input
                            id="password"
                            v-model="password"
                            :type="showPassword ? 'text' : 'password'"
                            class="form-input"
                            :class="{ 'input-error': errors.password }"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        />
                        <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                                <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3.53 2.47a.75.75 0 00-1.06 1.06l18 18a.75.75 0 101.06-1.06l-18-18zM22.676 12.553a11.249 11.249 0 01-2.631 4.31l-3.099-3.099a5.25 5.25 0 00-6.71-6.71L7.759 4.577a11.217 11.217 0 014.242-.827c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113z" />
                                <path d="M15.75 12c0 .18-.013.357-.037.53l-4.244-4.243A3.75 3.75 0 0115.75 12zM12.53 15.713l-4.243-4.244a3.75 3.75 0 004.243 4.243z" />
                                <path d="M6.75 12c0-.619.107-1.213.304-1.764l-3.1-3.1a11.25 11.25 0 00-2.63 4.31c-.12.362-.12.752 0 1.114 1.489 4.467 5.704 7.69 10.675 7.69 1.5 0 2.933-.294 4.242-.827l-2.477-2.477A3.75 3.75 0 016.75 12z" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="errors.password" class="error-msg">{{ errors.password }}</p>
                </div>

                <button
                    type="submit"
                    class="login-btn"
                    :class="{ 'loading': loading }"
                    :disabled="loading"
                    id="login-submit-btn"
                >
                    <span v-if="!loading">Sign In</span>
                    <span v-else class="spinner-wrapper">
                        <svg class="spinner" viewBox="0 0 24 24" fill="none">
                            <circle class="spinner-track" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                            <path class="spinner-arc" d="M12 2a10 10 0 0110 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                        Signing in...
                    </span>
                </button>
            </form>

            <p class="login-footer">
                Municipality of Bayambang &mdash; IHRIS Portal
            </p>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

* { box-sizing: border-box; margin: 0; padding: 0; }

.login-wrapper {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
    position: relative;
    overflow: hidden;
    padding: 1rem;
}

/* Animated background blobs */
.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.15;
    animation: float 8s ease-in-out infinite;
}
.blob-1 {
    width: 500px; height: 500px;
    background: #6366f1;
    top: -150px; left: -150px;
    animation-delay: 0s;
}
.blob-2 {
    width: 400px; height: 400px;
    background: #8b5cf6;
    bottom: -100px; right: -100px;
    animation-delay: 3s;
}
.blob-3 {
    width: 300px; height: 300px;
    background: #06b6d4;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    animation-delay: 1.5s;
}

@keyframes float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-30px) scale(1.05); }
}
.blob-3 {
    animation: float3 8s ease-in-out infinite;
    animation-delay: 1.5s;
}
@keyframes float3 {
    0%, 100% { transform: translate(-50%, -50%) scale(1); }
    50% { transform: translate(-50%, calc(-50% - 20px)) scale(1.05); }
}

/* Card */
.login-card {
    position: relative;
    z-index: 10;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    padding: 2.5rem;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255,255,255,0.1);
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Header */
.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.logo-circle {
    width: 72px;
    height: 72px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    box-shadow: 0 8px 32px rgba(99, 102, 241, 0.4);
}

.logo-icon {
    width: 36px;
    height: 36px;
    color: white;
}

.login-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: white;
    letter-spacing: -0.5px;
}

.login-subtitle {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.5);
    margin-top: 0.25rem;
}

/* Form */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 14px;
    width: 18px;
    height: 18px;
    color: rgba(255, 255, 255, 0.4);
    pointer-events: none;
    flex-shrink: 0;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    color: white;
    font-size: 0.95rem;
    font-family: 'Inter', sans-serif;
    transition: all 0.2s ease;
    outline: none;
}

.form-input::placeholder {
    color: rgba(255, 255, 255, 0.25);
}

.form-input:focus {
    border-color: #6366f1;
    background: rgba(99, 102, 241, 0.1);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.form-input.input-error {
    border-color: #f87171;
    background: rgba(248, 113, 113, 0.08);
}

.toggle-password {
    position: absolute;
    right: 14px;
    background: none;
    border: none;
    cursor: pointer;
    color: rgba(255, 255, 255, 0.4);
    padding: 0;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}
.toggle-password:hover { color: rgba(255,255,255,0.8); }
.toggle-password svg { width: 18px; height: 18px; }

.error-msg {
    font-size: 0.78rem;
    color: #f87171;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* Login button */
.login-btn {
    margin-top: 0.5rem;
    padding: 0.875rem;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 1rem;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
    position: relative;
    overflow: hidden;
}

.login-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
    opacity: 0;
    transition: opacity 0.2s;
}

.login-btn:hover:not(:disabled)::before { opacity: 1; }
.login-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 8px 30px rgba(99, 102, 241, 0.5);
}
.login-btn:active:not(:disabled) { transform: translateY(0); }
.login-btn:disabled { opacity: 0.7; cursor: not-allowed; }

.spinner-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.spinner {
    width: 18px;
    height: 18px;
    animation: spin 1s linear infinite;
}
.spinner-track { opacity: 0.3; }
.spinner-arc { animation: spin 1s linear infinite; }

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Footer */
.login-footer {
    text-align: center;
    margin-top: 1.75rem;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.3);
}
</style>
