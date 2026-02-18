<script setup>
import { ref, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    authUser: Object,
});

// State
const activeTab = ref('permanent');
const permanentEmployees = ref([]);
const allEmployees = ref([]);
const loading = ref(false);
const error = ref(null);
const search = ref('');
const officeUuid = ref('');
const sidebarOpen = ref(false);

// Fetch permanent employees
const fetchPermanentEmployees = async () => {
    loading.value = true;
    error.value = null;
    try {
        const res = await fetch('/api-proxy/permanent-employees');
        const data = await res.json();

        if (!res.ok) {
            // Surface the real error from the API
            const msg = data?.error ?? data?.message ?? `Server error (${res.status})`;
            const detail = data?.body?.message ?? data?.body?.error ?? '';
            error.value = detail ? `${msg}: ${detail}` : msg;
            permanentEmployees.value = [];
            return;
        }

        // Handle various response shapes
        if (Array.isArray(data)) {
            permanentEmployees.value = data;
        } else {
            permanentEmployees.value =
                data.data ?? data.users ?? data.employees ?? data.items ?? [];
        }
    } catch (e) {
        error.value = 'Failed to load permanent employees.';
    } finally {
        loading.value = false;
    }
};

// Fetch all employees by office UUID
const fetchAllEmployees = async () => {
    if (!officeUuid.value.trim()) {
        error.value = 'Please enter an Office UUID.';
        return;
    }
    loading.value = true;
    error.value = null;
    try {
        const res = await fetch(`/api-proxy/employees/${officeUuid.value.trim()}`);
        const data = await res.json();

        if (!res.ok) {
            const msg = data?.error ?? data?.message ?? `Server error (${res.status})`;
            const detail = data?.body?.message ?? data?.body?.error ?? '';
            error.value = detail ? `${msg}: ${detail}` : msg;
            allEmployees.value = [];
            return;
        }

        if (Array.isArray(data)) {
            allEmployees.value = data;
        } else {
            allEmployees.value =
                data.data ?? data.employees ?? data.users ?? data.items ?? [];
        }
    } catch (e) {
        error.value = 'Failed to load employees.';
    } finally {
        loading.value = false;
    }
};

const switchTab = (tab) => {
    activeTab.value = tab;
    error.value = null;
    search.value = '';
    if (tab === 'permanent' && permanentEmployees.value.length === 0) {
        fetchPermanentEmployees();
    }
};

const logout = () => {
    router.post('/logout');
};

// Filtered employees
const currentList = computed(() => {
    const list = activeTab.value === 'permanent' ? permanentEmployees.value : allEmployees.value;
    if (!search.value) return list;
    const q = search.value.toLowerCase();
    return list.filter(e => {
        const name = (e.name || e.full_name || `${e.first_name ?? ''} ${e.last_name ?? ''}`).toLowerCase();
        const email = (e.email ?? '').toLowerCase();
        const position = (e.position ?? e.designation ?? '').toLowerCase();
        return name.includes(q) || email.includes(q) || position.includes(q);
    });
});

const getEmployeeName = (e) => e.name ?? e.full_name ?? `${e.first_name ?? ''} ${e.last_name ?? ''}`.trim() ?? 'N/A';
const getEmployeeEmail = (e) => e.email ?? '—';
const getEmployeePosition = (e) => e.position ?? e.designation ?? e.employment_type ?? '—';
const getEmployeeOffice = (e) => e.office ?? e.department ?? e.office_name ?? '—';
const getInitials = (e) => {
    const name = getEmployeeName(e);
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
};
const getUserName = () => {
    if (!props.authUser) return 'User';
    return props.authUser.name ?? props.authUser.full_name ?? props.authUser.email ?? 'User';
};

onMounted(() => {
    fetchPermanentEmployees();
});
</script>

<template>
    <Head title="Dashboard — Benesystem" />

    <div class="app-shell">
        <!-- Sidebar -->
        <aside class="sidebar" :class="{ 'sidebar-open': sidebarOpen }">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                    </svg>
                </div>
                <div>
                    <h2 class="sidebar-title">Benesystem</h2>
                    <p class="sidebar-sub">Bayambang LGU</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <button
                    class="nav-item"
                    :class="{ active: activeTab === 'permanent' }"
                    @click="switchTab('permanent')"
                    id="nav-permanent"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" />
                    </svg>
                    Permanent Employees
                </button>

                <button
                    class="nav-item"
                    :class="{ active: activeTab === 'all' }"
                    @click="switchTab('all')"
                    id="nav-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" />
                        <path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 013.57-4.047zM20.226 19.389a8.287 8.287 0 00-1.308-5.135 3.75 3.75 0 013.57 4.047l-.01.121a.563.563 0 01-.373.486l-.115.04c-.567.2-1.156.349-1.764.441z" />
                    </svg>
                    All Employees (by Office)
                </button>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">{{ getUserName().charAt(0).toUpperCase() }}</div>
                    <div class="user-details">
                        <p class="user-name">{{ getUserName() }}</p>
                        <p class="user-role">Administrator</p>
                    </div>
                </div>
                <button class="logout-btn" @click="logout" id="logout-btn" title="Logout">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.5 3.75A1.5 1.5 0 006 5.25v13.5a1.5 1.5 0 001.5 1.5h6a1.5 1.5 0 001.5-1.5V15a.75.75 0 011.5 0v3.75a3 3 0 01-3 3h-6a3 3 0 01-3-3V5.25a3 3 0 013-3h6a3 3 0 013 3V9A.75.75 0 0115 9V5.25a1.5 1.5 0 00-1.5-1.5h-6zm10.72 4.72a.75.75 0 011.06 0l3 3a.75.75 0 010 1.06l-3 3a.75.75 0 11-1.06-1.06l1.72-1.72H9a.75.75 0 010-1.5h10.94l-1.72-1.72a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="overlay" @click="sidebarOpen = false"></div>

        <!-- Main content -->
        <main class="main-content">
            <!-- Top bar -->
            <header class="topbar">
                <button class="hamburger" @click="sidebarOpen = !sidebarOpen" id="hamburger-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 6.75A.75.75 0 013.75 6h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 6.75zM3 12a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 12zm0 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div class="topbar-title">
                    <h1>{{ activeTab === 'permanent' ? 'Permanent Employees' : 'All Employees by Office' }}</h1>
                    <p>{{ activeTab === 'permanent' ? 'Regular government employees' : 'Including OJTs and casuals' }}</p>
                </div>
                <div class="topbar-badge">
                    <span class="badge">{{ currentList.length }} records</span>
                </div>
            </header>

            <!-- Content area -->
            <div class="content-area">

                <!-- Office UUID input (for All tab) -->
                <div v-if="activeTab === 'all'" class="office-search-bar">
                    <div class="office-input-wrapper">
                        <svg class="office-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 2.25a.75.75 0 000 1.5v16.5h-.75a.75.75 0 000 1.5H15v-18a.75.75 0 000-1.5H3zM6.75 19.5v-2.25a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v2.25a.75.75 0 01-.75.75h-3a.75.75 0 01-.75-.75zM6 6.75A.75.75 0 016.75 6h.75a.75.75 0 010 1.5h-.75A.75.75 0 016 6.75zM6.75 9h.75a.75.75 0 010 1.5h-.75a.75.75 0 010-1.5zM6 12.75a.75.75 0 01.75-.75h.75a.75.75 0 010 1.5h-.75a.75.75 0 01-.75-.75zM10.5 6a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zm-.75 3.75A.75.75 0 0110.5 9h.75a.75.75 0 010 1.5h-.75a.75.75 0 01-.75-.75zM10.5 12a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zM16.5 6.75v15h5.25a.75.75 0 000-1.5H21v-12a.75.75 0 000-1.5h-4.5zm1.5 4.5a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75h-.008a.75.75 0 01-.75-.75v-.008zm.75 2.25a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75v-.008a.75.75 0 00-.75-.75h-.008zM18 17.25a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75h-.008a.75.75 0 01-.75-.75v-.008z" clip-rule="evenodd" />
                        </svg>
                        <input
                            v-model="officeUuid"
                            type="text"
                            class="office-input"
                            placeholder="Enter Office UUID (e.g. abc123-...)"
                            id="office-uuid-input"
                            @keyup.enter="fetchAllEmployees"
                            @input="error = null"
                        />
                    </div>
                    <button class="fetch-btn" @click="fetchAllEmployees" :disabled="loading" id="fetch-employees-btn">
                        <svg v-if="!loading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 100 13.5 6.75 6.75 0 000-13.5zM2.25 10.5a8.25 8.25 0 1114.59 5.28l4.69 4.69a.75.75 0 11-1.06 1.06l-4.69-4.69A8.25 8.25 0 012.25 10.5z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="spinner-sm" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.3"/>
                            <path d="M12 2a10 10 0 0110 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                        {{ loading ? 'Loading...' : 'Fetch Employees' }}
                    </button>
                </div>

                <!-- Search bar -->
                <div class="search-bar" v-if="currentList.length > 0 || search">
                    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 100 13.5 6.75 6.75 0 000-13.5zM2.25 10.5a8.25 8.25 0 1114.59 5.28l4.69 4.69a.75.75 0 11-1.06 1.06l-4.69-4.69A8.25 8.25 0 012.25 10.5z" clip-rule="evenodd" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        class="search-input"
                        placeholder="Search by name, email, or position..."
                        id="employee-search"
                    />
                </div>

                <!-- Error -->
                <div v-if="error" class="error-banner">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
                    </svg>
                    {{ error }}
                </div>

                <!-- Loading skeleton -->
                <div v-if="loading && currentList.length === 0" class="skeleton-list">
                    <div v-for="i in 6" :key="i" class="skeleton-row">
                        <div class="skeleton-avatar"></div>
                        <div class="skeleton-lines">
                            <div class="skeleton-line w-40"></div>
                            <div class="skeleton-line w-60"></div>
                        </div>
                        <div class="skeleton-line w-32 ml-auto"></div>
                    </div>
                </div>

                <!-- Prompt for all tab (no UUID entered yet) -->
                <div v-else-if="activeTab === 'all' && allEmployees.length === 0 && !loading && !error" class="empty-state prompt">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 2.25a.75.75 0 000 1.5v16.5h-.75a.75.75 0 000 1.5H15v-18a.75.75 0 000-1.5H3z" clip-rule="evenodd" />
                    </svg>
                    <p>Enter an Office UUID above and click <strong>Fetch Employees</strong>.</p>
                </div>

                <!-- Empty state (search returned nothing or permanent tab is empty) -->
                <div v-else-if="!loading && currentList.length === 0 && !error" class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122z" />
                    </svg>
                    <p>No employees found{{ search ? ' matching your search' : '' }}.</p>
                </div>

                <!-- Employee table -->
                <div v-else-if="currentList.length > 0" class="table-wrapper">
                    <table class="emp-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee</th>
                                <th>Position / Type</th>
                                <th>Office / Department</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(emp, idx) in currentList" :key="emp.id ?? idx" class="emp-row">
                                <td class="row-num">{{ idx + 1 }}</td>
                                <td>
                                    <div class="emp-info">
                                        <div class="emp-avatar">{{ getInitials(emp) }}</div>
                                        <span class="emp-name">{{ getEmployeeName(emp) }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="position-badge">{{ getEmployeePosition(emp) }}</span>
                                </td>
                                <td class="office-cell">{{ getEmployeeOffice(emp) }}</td>
                                <td class="email-cell">{{ getEmployeeEmail(emp) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

* { box-sizing: border-box; margin: 0; padding: 0; }

.app-shell {
    font-family: 'Inter', sans-serif;
    display: flex;
    min-height: 100vh;
    background: #f1f5f9;
}

/* Sidebar */
.sidebar {
    width: 260px;
    background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 100%);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    z-index: 100;
    transition: transform 0.3s ease;
}

.sidebar-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.sidebar-logo {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.sidebar-logo svg { width: 22px; height: 22px; color: white; }

.sidebar-title {
    font-size: 1rem;
    font-weight: 700;
    color: white;
}
.sidebar-sub {
    font-size: 0.7rem;
    color: rgba(255,255,255,0.4);
    margin-top: 1px;
}

.sidebar-nav {
    flex: 1;
    padding: 1rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    border: none;
    background: transparent;
    color: rgba(255,255,255,0.55);
    font-size: 0.875rem;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    width: 100%;
}
.nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }
.nav-item:hover { background: rgba(255,255,255,0.07); color: white; }
.nav-item.active {
    background: rgba(99, 102, 241, 0.25);
    color: #a5b4fc;
    border: 1px solid rgba(99, 102, 241, 0.3);
}

.sidebar-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex: 1;
    min-width: 0;
}

.user-avatar {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
}

.user-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.user-role {
    font-size: 0.7rem;
    color: rgba(255,255,255,0.4);
}

.logout-btn {
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 0.4rem;
    cursor: pointer;
    color: rgba(255,255,255,0.5);
    display: flex;
    align-items: center;
    transition: all 0.2s;
    flex-shrink: 0;
}
.logout-btn svg { width: 16px; height: 16px; }
.logout-btn:hover { background: rgba(239,68,68,0.2); color: #f87171; border-color: rgba(239,68,68,0.3); }

/* Main content */
.main-content {
    flex: 1;
    margin-left: 260px;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Topbar */
.topbar {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    position: sticky;
    top: 0;
    z-index: 50;
}

.hamburger {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    color: #64748b;
    padding: 0.25rem;
}
.hamburger svg { width: 22px; height: 22px; }

.topbar-title { flex: 1; }
.topbar-title h1 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
}
.topbar-title p {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 1px;
}

.badge {
    background: #ede9fe;
    color: #6d28d9;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
}

/* Content */
.content-area {
    flex: 1;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Office search */
.office-search-bar {
    display: flex;
    gap: 0.75rem;
    background: white;
    padding: 1rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.office-input-wrapper {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
}
.office-icon {
    position: absolute;
    left: 12px;
    width: 18px; height: 18px;
    color: #94a3b8;
    pointer-events: none;
}
.office-input {
    width: 100%;
    padding: 0.65rem 1rem 0.65rem 2.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
    font-family: 'Inter', sans-serif;
    color: #0f172a;
    outline: none;
    transition: border-color 0.2s;
}
.office-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

.fetch-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1.25rem;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}
.fetch-btn svg { width: 16px; height: 16px; }
.fetch-btn:hover:not(:disabled) { opacity: 0.9; transform: translateY(-1px); }
.fetch-btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* Search */
.search-bar {
    position: relative;
    display: flex;
    align-items: center;
}
.search-icon {
    position: absolute;
    left: 14px;
    width: 18px; height: 18px;
    color: #94a3b8;
    pointer-events: none;
}
.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.875rem;
    font-family: 'Inter', sans-serif;
    color: #0f172a;
    outline: none;
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.search-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

/* Error */
.error-banner {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1rem;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    color: #dc2626;
    font-size: 0.875rem;
}
.error-banner svg { width: 18px; height: 18px; flex-shrink: 0; }

/* Skeleton */
.skeleton-list { display: flex; flex-direction: column; gap: 0.5rem; }
.skeleton-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: white;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}
.skeleton-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    flex-shrink: 0;
}
.skeleton-lines { flex: 1; display: flex; flex-direction: column; gap: 0.4rem; }
.skeleton-line {
    height: 12px;
    border-radius: 6px;
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}
.w-40 { width: 160px; }
.w-60 { width: 240px; }
.w-32 { width: 128px; }
.ml-auto { margin-left: auto; }

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Empty state */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    color: #94a3b8;
    gap: 1rem;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}
.empty-state svg { width: 48px; height: 48px; opacity: 0.4; }
.empty-state p { font-size: 0.9rem; text-align: center; }
.empty-state strong { color: #6366f1; }

/* Table */
.table-wrapper {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.emp-table {
    width: 100%;
    border-collapse: collapse;
}

.emp-table thead tr {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.emp-table th {
    padding: 0.875rem 1rem;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.emp-row {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}
.emp-row:last-child { border-bottom: none; }
.emp-row:hover { background: #f8fafc; }

.emp-table td {
    padding: 0.875rem 1rem;
    font-size: 0.875rem;
    color: #334155;
    vertical-align: middle;
}

.row-num { color: #94a3b8; font-size: 0.8rem; width: 40px; }

.emp-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.emp-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.emp-name { font-weight: 600; color: #0f172a; }

.position-badge {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    background: #ede9fe;
    color: #6d28d9;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
}

.office-cell { color: #64748b; }
.email-cell { color: #6366f1; font-size: 0.8rem; }

/* Spinner */
.spinner-sm {
    width: 16px; height: 16px;
    animation: spin 1s linear infinite;
}
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Mobile */
.overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 99;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    .sidebar.sidebar-open {
        transform: translateX(0);
    }
    .overlay { display: block; }
    .main-content { margin-left: 0; }
    .hamburger { display: flex; }
    .office-search-bar { flex-direction: column; }
    .emp-table th:nth-child(4),
    .emp-table td:nth-child(4),
    .emp-table th:nth-child(5),
    .emp-table td:nth-child(5) { display: none; }
}
</style>
