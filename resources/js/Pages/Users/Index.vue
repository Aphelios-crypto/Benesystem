<script setup>
import { ref } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    users: Object, // Paginated User Resource
    roles: Array   // Available roles
});

const sidebarOpen = ref(false);
const showLogoutModal = ref(false);

const showModal = ref(false);
const isEditing = ref(false);
const userForm = useForm({
    id: null,
    name: '',
    email: '',
    role: '',
    password: '',
    password_confirmation: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    userForm.reset();
    userForm.clearErrors();
    // Default to first role (likely Employee) if available, or empty
    userForm.role = props.roles.length ? props.roles[props.roles.length - 1] : ''; 
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    userForm.reset();
    userForm.clearErrors();
    userForm.id = user.id;
    userForm.name = user.name;
    userForm.email = user.email;
    // Pre-select first role or empty
    userForm.role = user.roles && user.roles.length ? user.roles[0].name : '';
    userForm.password = '';
    userForm.password_confirmation = '';
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        userForm.put(`/users/${userForm.id}`, {
            onSuccess: () => { showModal.value = false; userForm.reset(); }
        });
    } else {
        userForm.post('/users', {
            onSuccess: () => { showModal.value = false; userForm.reset(); }
        });
    }
};

const confirmDelete = (user) => {
    if (confirm('Are you sure you want to delete this user?')) {
        router.delete(`/users/${user.id}`);
    }
};

const logout = () => {
    showLogoutModal.value = true;
};

const confirmLogout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="User Management" />

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

                <Link v-if="$page.props.auth.user?.roles?.some(r => ['Super Admin', 'Admin'].includes(r.name))" href="/users" class="nav-item active">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0021.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 003.065 7.097A9.716 9.716 0 0012 21.75a9.716 9.716 0 006.685-2.653zm-12.54-1.285A7.486 7.486 0 0112 15a7.486 7.486 0 015.855 2.812A8.224 8.224 0 0112 20.25a8.224 8.224 0 01-5.855-2.438zM15.75 9a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" clip-rule="evenodd" />
                    </svg>
                    User Management
                </Link>

                <Link href="/offices" class="nav-item">
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
                    <h1>User Management</h1>
                </div>
            </header>

            <div class="content-area">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">System Users</h2>
                        <p class="text-sm text-slate-500">Manage user access and accounts</p>
                    </div>
                    <button class="btn-primary" @click="openCreateModal">
                        + Add New User
                    </button>
                </div>

                <!-- Users Table -->
                <div class="table-wrapper" v-if="users && users.data">
                    <table class="emp-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="emp-row">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="user-avatar-sm">{{ user.name ? user.name[0] : 'U' }}</div>
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-700">{{ user.name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-xs font-semibold px-2 py-1 rounded bg-slate-100 text-slate-600 border border-slate-200"
                                          v-if="user.roles && user.roles.length">
                                        {{ user.roles[0].name }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400">No Role</span>
                                </td>
                                <td class="email-cell">{{ user.email }}</td>
                                <td class="text-sm text-slate-500">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                <td>
                                    <div class="flex gap-2">
                                        <button class="btn-icon-edit" @click="openEditModal(user)">
                                            Edit
                                        </button>
                                        <button class="btn-icon-delete" @click="confirmDelete(user)">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="4" class="text-center py-8 text-slate-500">No users found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-12 text-slate-500">
                    Loading users...
                </div>

                <!-- Pagination -->
                <div class="mt-4 flex justify-between items-center" v-if="users.total > 0">
                    <span class="text-sm text-slate-500">Showing {{ users.from }} to {{ users.to }} of {{ users.total }} users</span>
                    <div class="flex gap-2">
                        <Link v-if="users.prev_page_url" :href="users.prev_page_url" class="page-btn">Previous</Link>
                        <Link v-if="users.next_page_url" :href="users.next_page_url" class="page-btn">Next</Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="modal-overlay">
            <div class="modal-content text-left" style="max-width: 450px;">
                <h3 class="text-lg font-bold mb-4 text-slate-800">{{ isEditing ? 'Edit User' : 'Create New User' }}</h3>
                
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                        <input v-model="userForm.name" type="text" class="form-input w-full" required />
                        <span v-if="userForm.errors.name" class="text-red-500 text-xs mt-1">{{ userForm.errors.name }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                        <input v-model="userForm.email" type="email" class="form-input w-full" required />
                        <span v-if="userForm.errors.email" class="text-red-500 text-xs mt-1">{{ userForm.errors.email }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                        <select v-model="userForm.role" class="form-input w-full" required>
                            <option value="" disabled>Select a role</option>
                            <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                        </select>
                        <span v-if="userForm.errors.role" class="text-red-500 text-xs mt-1">{{ userForm.errors.role }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input v-model="userForm.password" type="password" class="form-input w-full" :required="!isEditing" :placeholder="isEditing ? 'Leave blank to keep current password' : ''" />
                        <span v-if="userForm.errors.password" class="text-red-500 text-xs mt-1">{{ userForm.errors.password }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password</label>
                        <input v-model="userForm.password_confirmation" type="password" class="form-input w-full" :required="!isEditing && userForm.password.length > 0" />
                    </div>

                    <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100">
                        <button type="button" class="btn-cancel px-4" @click="showModal = false">Cancel</button>
                        <button type="submit" class="btn-save px-4" :disabled="userForm.processing">
                            {{ isEditing ? 'Save Changes' : 'Create User' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

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

/* ── Layout ── */
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
.logout-btn svg { width: 16px; height: 16px; }
.logout-btn:hover { background: rgba(239,68,68,0.2); color: #f87171; border-color: rgba(239,68,68,0.3); }
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
.user-avatar-sm { width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.75rem; }
.email-cell { color: #3b82f6; font-size: 0.8rem; }
.btn-primary { background: #2563eb; color: white; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer; transition: background 0.2s; }
.btn-primary:hover { background: #1d4ed8; }
.btn-save { background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.875rem; transition: background 0.2s; cursor: pointer; }
.btn-save:hover { background: #1d4ed8; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-icon-edit { color: #2563eb; font-weight: 500; font-size: 0.8rem; background: none; border: none; cursor: pointer; }
.btn-icon-edit:hover { text-decoration: underline; }
.btn-icon-delete { color: #ef4444; font-weight: 500; font-size: 0.8rem; background: none; border: none; cursor: pointer; }
.btn-icon-delete:hover { text-decoration: underline; }
.page-btn { padding: 0.35rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.8rem; color: #475569; background: white; transition: all 0.2s; }
.page-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
/* Modal Styles */
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 200; animation: fadeIn 0.2s ease-out; }
.modal-content { background: white; padding: 2rem; border-radius: 16px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: scaleIn 0.2s ease-out; }
.modal-icon { width: 48px; height: 48px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto; }
.modal-icon svg { width: 24px; height: 24px; }
.modal-actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; }
.modal-actions button { flex: 1; padding: 0.625rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; }
.btn-cancel { background: white; border: 1px solid #e2e8f0; color: #475569; }
.btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }
.btn-confirm { background: #ef4444; border: 1px solid #ef4444; color: white; }
.btn-confirm:hover { background: #dc2626; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.25); }
.form-input { padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem; outline: none; transition: border-color 0.2s; }
.form-input:focus { border-color: #2563eb; ring: 2px solid rgba(37,99,235,0.1); }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes scaleIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
@media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .sidebar.sidebar-open { transform: translateX(0); } .main-content { margin-left: 0; } .hamburger { display: block; } .btn-primary { font-size: 0.8rem; padding: 0.4rem 0.8rem; } }
</style>
