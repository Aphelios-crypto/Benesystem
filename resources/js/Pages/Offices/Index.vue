<script setup>
import { ref } from 'vue';
import { Link, Head } from '@inertiajs/vue3';

const props = defineProps({
    offices: Array
});

const sidebarOpen = ref(false);
const showLogoutModal = ref(false);

const logout = () => {
    showLogoutModal.value = true;
};

const confirmLogout = () => {
    // Assuming we have logout route
    // In strict Inertia setup: router.post('/logout')
    // But here we might rely on a form submission or helper
    import('@inertiajs/vue3').then(({ router }) => {
        router.post('/logout');
    });
};
</script>

<template>
    <Head title="Offices Management" />

    <div class="dashboard-layout">
        
        <!-- Sidebar -->
        <aside class="sidebar" :class="{ 'sidebar-open': sidebarOpen }">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="/images/logo.png" alt="Bayambang Logo" @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display='flex'" />
                    <div class="logo-fallback">B</div>
                </div>
                <div>
                    <div class="sidebar-title">Benesystem</div>
                    <div class="sidebar-sub">Bayambang LGU</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <Link href="/dashboard" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                    </svg>
                    Dashboard
                </Link>

                <div class="my-2 border-t border-slate-700/50 mx-4"></div>

                <Link v-if="$page.props.auth.user?.roles?.some(r => ['Super Admin', 'Admin'].includes(r.name))" href="/users" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0021.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 003.065 7.097A9.716 9.716 0 0012 21.75a9.716 9.716 0 006.685-2.653zm-12.54-1.285A7.486 7.486 0 0112 15a7.486 7.486 0 015.855 2.812A8.224 8.224 0 0112 20.25a8.224 8.224 0 01-5.855-2.438zM15.75 9a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" clip-rule="evenodd" />
                    </svg>
                    User Management
                </Link>

                <Link href="/offices" class="nav-item active">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 6.75A.75.75 0 013.75 6h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 6.75zM3 12a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 12zm0 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                    </svg>
                    Offices
                </Link>
            </nav>
            
            <div class="sidebar-footer" v-if="$page.props.auth.user">
                <div class="user-info">
                    <div class="user-avatar text-xs font-bold">
                        {{ $page.props.auth.user.name ? $page.props.auth.user.name[0] : 'U' }}
                    </div>
                    <div style="width: 130px">
                        <div class="user-name">{{ $page.props.auth.user.name }}</div>
                        <div class="user-role">System User</div>
                    </div>
                </div>
                <button class="logout-btn" @click="logout" title="Sign out">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0113.5 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Mobile Header -->
            <header class="topbar sm:hidden">
                <button class="hamburger" @click="sidebarOpen = !sidebarOpen">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div class="topbar-title">
                    <h1>Offices Management</h1>
                </div>
            </header>

            <div class="content-area">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Offices</h2>
                        <p class="text-sm text-slate-500">List of offices from iHRIS</p>
                    </div>
                </div>

                <!-- Offices Table -->
                <div class="table-wrapper">
                    <table class="emp-table" v-if="offices && offices.length">
                        <thead>
                            <tr>
                                <th>Code / ID</th>
                                <th>Office Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="office in offices" :key="office.id" class="emp-row">
                                <td class="font-mono text-xs text-slate-500">{{ office.id || office.code || '-' }}</td>
                                <td class="font-medium text-slate-700">{{ office.name || office.title || '-' }}</td>
                                <td>
                                    <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-700 border border-green-200">
                                        Active
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="text-center py-12 text-slate-500">
                        No offices found or failed to load.
                    </div>
                </div>
            </div>
        </main>

        <!-- Logout Confirmation Modal -->
        <div v-if="showLogoutModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h3>Sign out?</h3>
                <p>Are you sure you want to sign out of Benesystem?</p>
                <div class="modal-actions">
                    <button class="btn-cancel" @click="showLogoutModal = false">Cancel</button>
                    <button class="btn-confirm" @click="confirmLogout">Sign out</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

/* ── Layout (Similar to Users/Index.vue) ── */
.dashboard-layout { display: flex; min-height: 100vh; background: #f1f5f9; font-family: 'Inter', sans-serif; }
.sidebar { width: 260px; background: linear-gradient(180deg, #0f172a 0%, #1e3a8a 100%); color: white; display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; transition: transform 0.3s ease; }
.sidebar-header { display: flex; align-items: center; gap: 0.75rem; padding: 1.5rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
.sidebar-logo { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; position: relative; background: transparent; box-shadow: none; }
.sidebar-logo img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)); }
.logo-fallback { display: none; width: 100%; height: 100%; background: linear-gradient(135deg, #2563eb, #3b82f6); border-radius: 8px; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem; position: absolute; inset: 0; }
.sidebar-title { font-size: 1rem; font-weight: 700; color: white; letter-spacing: 0.01em; }
.sidebar-sub { font-size: 0.7rem; color: rgba(255,255,255,0.6); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.05em; }
.sidebar-nav { flex: 1; padding: 1rem 0.75rem; display: flex; flex-direction: column; gap: 0.35rem; }
.nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 8px; border: none; background: transparent; color: rgba(255,255,255,0.7); font-size: 0.875rem; font-weight: 500; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-align: left; width: 100%; }
.nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }
.nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
.nav-item.active { background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); font-weight: 600; }
.sidebar-footer { padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.1); display: flex; align-items: center; gap: 0.75rem; }
.user-info { display: flex; align-items: center; gap: 0.6rem; flex: 1; min-width: 0; }
.user-avatar { width: 34px; height: 34px; background: linear-gradient(135deg, #2563eb, #0ea5e9); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; color: white; flex-shrink: 0; border: 2px solid rgba(255,255,255,0.1); }
.user-name { font-size: 0.8rem; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-role { font-size: 0.7rem; color: rgba(255,255,255,0.5); }
.logout-btn { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.4rem; cursor: pointer; color: rgba(255,255,255,0.6); display: flex; align-items: center; transition: all 0.2s; flex-shrink: 0; }
.logout-btn:hover { background: rgba(239,68,68,0.2); color: #f87171; border-color: rgba(239,68,68,0.3); }

/* Main Content Styles */
.main-content { flex: 1; margin-left: 260px; display: flex; flex-direction: column; min-height: 100vh; }
.topbar { background: white; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.5rem; display: flex; align-items: center; gap: 1rem; position: sticky; top: 0; z-index: 50; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
.content-area { flex: 1; padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.table-wrapper { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.emp-table { width: 100%; border-collapse: collapse; }
.emp-table thead tr { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.emp-table th { padding: 0.875rem 1rem; text-align: left; font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
.emp-row { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
.emp-row:last-child { border-bottom: none; }
.emp-row:hover { background: #f8fafc; }
.emp-table td { padding: 0.75rem 1rem; font-size: 0.875rem; color: #334155; vertical-align: middle; }

/* Modal Styles */
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 200; animation: fadeIn 0.2s ease-out; }
.modal-content { background: white; padding: 2rem; border-radius: 16px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: scaleIn 0.2s ease-out; }
.modal-icon { width: 48px; height: 48px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto; }
.modal-actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; }
.modal-actions button { flex: 1; padding: 0.625rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; }
.btn-cancel { background: white; border: 1px solid #e2e8f0; color: #475569; }
.btn-confirm { background: #ef4444; border: 1px solid #ef4444; color: white; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes scaleIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
@media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .sidebar.sidebar-open { transform: translateX(0); } .main-content { margin-left: 0; } .hamburger { display: block; } }
</style>
