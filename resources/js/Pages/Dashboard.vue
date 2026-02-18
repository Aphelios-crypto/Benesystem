<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
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
const offices = ref([]);
const officesLoading = ref(false);
const sidebarOpen = ref(false);
const showLogoutModal = ref(false);
const dashboardOpen = ref(true);


// Pagination
const PAGE_SIZE = 20;
const currentPage = ref(1);

// Reset page when search changes
watch(search, () => { currentPage.value = 1; });

// ─── Name helpers ────────────────────────────────────────────────────────────
// API fields: name (first), middle_name, last_name, extension
const getFullName = (e) => {
    const last   = (e.last_name ?? '').trim();
    const first  = (e.name ?? e.first_name ?? '').trim();
    const middle = (e.middle_name ?? '').trim();
    const ext    = (e.extension ?? '').trim();

    if (!last && !first) return e.full_name ?? 'N/A';

    // Format: LAST NAME, First Middle Ext.
    let full = '';
    if (last)   full += last.toUpperCase();
    if (first)  full += (full ? ', ' : '') + first;
    if (middle) full += ' ' + middle;
    if (ext)    full += ' ' + ext;
    return full;
};

const getInitials = (e) => {
    const first = (e.name ?? e.first_name ?? '').trim();
    const last  = (e.last_name ?? '').trim();
    const a = last[0] ?? '';
    const b = first[0] ?? '';
    return (a + b).toUpperCase() || '?';
};

const getPhotoUrl = (e) => e.profile_photo_url ?? e.photo_url ?? e.avatar ?? null;

const getEmployeeEmail    = (e) => e.email ?? '—';
const getEmployeePosition = (e) => e.position ?? e.designation ?? e.employment_type ?? '—';
const getEmployeeOffice   = (e) => e.office ?? e.department ?? e.office_name ?? '—';
const getEmployeeNumber   = (e) => e.employee_number ?? '—';

const getUserName = () => {
    if (!props.authUser) return 'User';
    return props.authUser.name ?? props.authUser.full_name ?? props.authUser.email ?? 'User';
};

// ─── Fetch ───────────────────────────────────────────────────────────────────
const fetchPermanentEmployees = async () => {
    loading.value = true;
    error.value = null;
    try {
        const res  = await fetch('/api-proxy/permanent-employees');
        const data = await res.json();

        if (!res.ok) {
            const msg    = data?.error ?? data?.message ?? `Server error (${res.status})`;
            const detail = data?.body?.message ?? data?.body?.error ?? '';
            error.value  = detail ? `${msg}: ${detail}` : msg;
            permanentEmployees.value = [];
            return;
        }

        permanentEmployees.value = Array.isArray(data)
            ? data
            : (data.data ?? data.users ?? data.employees ?? data.items ?? []);
    } catch (e) {
        error.value = 'Failed to load permanent employees.';
    } finally {
        loading.value = false;
    }
};

const fetchOffices = async () => {
    officesLoading.value = true;
    try {
        const res = await fetch('/api-proxy/offices');
        const data = await res.json();
        if (res.ok) {
            const raw = Array.isArray(data) ? data : (data.data ?? []);
            // Initialize with UI state
            offices.value = raw.map(o => ({
                ...o,
                employees: [],
                isOpen: false,
                loading: false,
                error: null,
                loaded: false
            }));
        }
    } catch (e) {
        console.error('Failed to load offices', e);
    } finally {
        officesLoading.value = false;
    }
};

const toggleOffice = async (office) => {
    office.isOpen = !office.isOpen;
    if (office.isOpen && !office.loaded && !office.loading) {
        // Fetch employees for this office
        office.loading = true;
        office.error = null;
        try {
            const res = await fetch(`/api-proxy/employees/${office.uuid}`);
            const data = await res.json();
            if (!res.ok) throw new Error(data?.message || 'Failed to fetch');
            
            office.employees = Array.isArray(data)
                ? data
                : (data.data ?? data.employees ?? []);
            office.loaded = true;
        } catch (e) {
            office.error = "Failed to load employees.";
        } finally {
            office.loading = false;
        }
    }
};

const switchTab = (tab) => {
    activeTab.value = tab;
    error.value = null;
    search.value = '';
    currentPage.value = 1;
    if (tab === 'permanent' && permanentEmployees.value.length === 0) {
        fetchPermanentEmployees();
    }
};

const logout = () => {
    showLogoutModal.value = true;
};

const confirmLogout = () => {
    router.post('/logout');
};

// ─── Filtered + paginated ────────────────────────────────────────────────────
// ─── Filtered + paginated ────────────────────────────────────────────────────
const filteredList = computed(() => {
    if (activeTab.value === 'permanent') {
        const list = permanentEmployees.value;
        if (!search.value.trim()) return list;
        const q = search.value.toLowerCase();
        return list.filter(e => {
            const name   = getFullName(e).toLowerCase();
            const email  = (e.email ?? '').toLowerCase();
            const empNum = (e.employee_number ?? '').toLowerCase();
            return name.includes(q) || email.includes(q) || empNum.includes(q);
        });
    } else {
        // For 'all' tab, we filter the OFFICES, not employees (unless we want to search loaded ones)
        // For now, let's filter offices by name
        const list = offices.value;
        if (!search.value.trim()) return list;
        const q = search.value.toLowerCase();
        return list.filter(o => (o.name ?? o.title ?? '').toLowerCase().includes(q));
    }
});

// Pagination only makes sense for Permanent tab now. 
// For All tab, we just show the list of offices (maybe paginated if too many?)
// Let's keep pagination for Permanent tab ONLY.
const totalPages = computed(() => {
    if (activeTab.value !== 'permanent') return 0;
    return Math.max(1, Math.ceil(filteredList.value.length / PAGE_SIZE));
});

const pagedList = computed(() => {
    if (activeTab.value !== 'permanent') return filteredList.value; // Return all filtered offices
    const start = (currentPage.value - 1) * PAGE_SIZE;
    return filteredList.value.slice(start, start + PAGE_SIZE);
});



const pageStart = computed(() => (currentPage.value - 1) * PAGE_SIZE + 1);
const pageEnd   = computed(() => Math.min(currentPage.value * PAGE_SIZE, filteredList.value.length));

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const goToPage = (p) => { currentPage.value = p; };

// Visible page numbers (max 7 shown)
const visiblePages = computed(() => {
    const total = totalPages.value;
    const cur   = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = new Set([1, total, cur]);
    for (let i = cur - 1; i >= 1 && pages.size < 6; i--) pages.add(i);
    for (let i = cur + 1; i <= total && pages.size < 7; i++) pages.add(i);
    return [...pages].sort((a, b) => a - b);
});

onMounted(() => {
    fetchPermanentEmployees();
    fetchOffices();
});
</script>

<template>
    <Head title="Dashboard — Benesystem" />

    <div class="app-shell">
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
                <button class="nav-item justify-between" @click="dashboardOpen = !dashboardOpen">
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
                            <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" />
                        </svg>
                        Dashboard
                    </div>
                    <svg class="chevron" :class="{ 'rotate-180': dashboardOpen }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                        <path fill-rule="evenodd" d="M12.53 16.28a.75.75 0 01-1.06 0l-7.5-7.5a.75.75 0 011.06-1.06L12 14.69l6.97-6.97a.75.75 0 111.06 1.06l-7.5 7.5z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div v-show="dashboardOpen" class="nav-sub-group">
                    <button 
                        class="nav-subitem" 
                        :class="{ active: activeTab === 'permanent' }"
                        @click="switchTab('permanent')"
                    >
                        Permanent Employees
                    </button>
                    <button 
                        class="nav-subitem" 
                        :class="{ active: activeTab === 'all' }"
                        @click="switchTab('all')"
                    >
                        All Employees
                    </button>
                </div>

                <div class="my-2 border-t border-slate-700/50 mx-4"></div>

                <Link v-if="$page.props.auth.user?.roles?.some(r => ['Super Admin', 'Admin'].includes(r.name))" :href="route('users.index')" class="nav-item">
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
                    <span class="badge">{{ filteredList.length }} records</span>
                </div>
            </header>

            <!-- Content area -->
            <div class="content-area">

                <!-- ========================== -->
                <!-- PERMANENT TAB CONTENT      -->
                <!-- ========================== -->
                <div v-if="activeTab === 'permanent'">
                   <!-- Search bar -->
                    <div class="search-bar" v-if="permanentEmployees.length > 0 || search">
                        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 100 13.5 6.75 6.75 0 000-13.5zM2.25 10.5a8.25 8.25 0 1114.59 5.28l4.69 4.69a.75.75 0 11-1.06 1.06l-4.69-4.69A8.25 8.25 0 012.25 10.5z" clip-rule="evenodd" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            class="search-input"
                            placeholder="Search by name or employee no..."
                            id="employee-search"
                        />
                    </div>

                    <!-- Error -->
                    <div v-if="error" class="error-banner mt-4">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
                        </svg>
                        {{ error }}
                    </div>

                    <!-- Loading skeleton -->
                    <div v-if="loading" class="skeleton-list mt-4">
                        <div v-for="i in 8" :key="i" class="skeleton-row">
                            <div class="skeleton-avatar"></div>
                            <div class="skeleton-lines">
                                <div class="skeleton-line w-48"></div>
                                <div class="skeleton-line w-32"></div>
                            </div>
                            <div class="skeleton-line w-24 ml-auto"></div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="!loading && pagedList.length === 0 && !error" class="empty-state mt-4">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122z" />
                        </svg>
                        <p>No employees found{{ search ? ' matching your search' : '' }}.</p>
                    </div>

                    <!-- Employee table -->
                    <div v-else-if="pagedList.length > 0" class="table-wrapper mt-4">
                        <table class="emp-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Full Name</th>
                                    <th>Employee No.</th>
                                    <th>Sex</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(emp, idx) in pagedList" :key="emp.id ?? idx" class="emp-row">
                                    <td class="row-num">{{ pageStart + idx }}</td>
                                    <td>
                                        <div class="emp-info">
                                            <div class="emp-avatar" :class="emp.sex === 'female' ? 'avatar-female' : 'avatar-male'">
                                                <img
                                                    v-if="getPhotoUrl(emp)"
                                                    :src="getPhotoUrl(emp)"
                                                    :alt="getInitials(emp)"
                                                    class="avatar-img"
                                                    @error="$event.target.style.display='none'"
                                                />
                                                <span v-else>{{ getInitials(emp) }}</span>
                                            </div>
                                            <div>
                                                <div class="emp-name">{{ getFullName(emp) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="empnum-badge">{{ getEmployeeNumber(emp) }}</span>
                                    </td>
                                    <td>
                                        <span class="sex-badge" :class="emp.sex === 'female' ? 'sex-female' : 'sex-male'">
                                            {{ emp.sex ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="email-cell">{{ getEmployeeEmail(emp) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="pagination" v-if="totalPages > 1">
                            <div class="page-info">
                                Showing <strong>{{ pageStart }}–{{ pageEnd }}</strong> of <strong>{{ filteredList.length }}</strong> employees
                            </div>
                            <div class="page-controls">
                                <button class="page-btn" @click="prevPage" :disabled="currentPage === 1" id="page-prev">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.72 12.53a.75.75 0 010-1.06l7.5-7.5a.75.75 0 111.06 1.06L9.31 12l6.97 6.97a.75.75 0 11-1.06 1.06l-7.5-7.5z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <template v-for="(p, i) in visiblePages" :key="p">
                                    <span v-if="i > 0 && visiblePages[i] - visiblePages[i-1] > 1" class="page-ellipsis">…</span>
                                    <button
                                        class="page-btn"
                                        :class="{ 'page-active': p === currentPage }"
                                        @click="goToPage(p)"
                                        :id="`page-btn-${p}`"
                                    >{{ p }}</button>
                                </template>

                                <button class="page-btn" @click="nextPage" :disabled="currentPage === totalPages" id="page-next">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.28 11.47a.75.75 0 010 1.06l-7.5 7.5a.75.75 0 01-1.06-1.06L14.69 12 7.72 5.03a.75.75 0 011.06-1.06l7.5 7.5z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================== -->
                <!-- ALL EMPLOYEES (OFFICES)    -->
                <!-- ========================== -->
                <div v-else-if="activeTab === 'all'">
                    <!-- Search Offices -->
                    <div class="search-bar mb-4">
                        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                            <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 100 13.5 6.75 6.75 0 000-13.5zM2.25 10.5a8.25 8.25 0 1114.59 5.28l4.69 4.69a.75.75 0 11-1.06 1.06l-4.69-4.69A8.25 8.25 0 012.25 10.5z" clip-rule="evenodd" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            class="search-input"
                            placeholder="Find an office/division..."
                        />
                    </div>
                    
                     <!-- Office List Accordion -->
                    <div class="office-list">
                        <div v-if="officesLoading" class="text-center py-8 text-slate-400">Loading offices...</div>
                        
                        <div 
                            v-for="office in filteredList" 
                            :key="office.uuid" 
                            class="office-card"
                            :class="{ 'is-open': office.isOpen }"
                        >
                            <div class="office-header" @click="toggleOffice(office)">
                                <div class="office-title-row">
                                    <div class="office-icon-bg">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                            <path fill-rule="evenodd" d="M3 2.25a.75.75 0 000 1.5v16.5h-.75a.75.75 0 000 1.5H15v-18a.75.75 0 000-1.5H3zM6.75 19.5v-2.25a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v2.25a.75.75 0 01-.75.75h-3a.75.75 0 01-.75-.75zM6 6.75A.75.75 0 016.75 6h.75a.75.75 0 010 1.5h-.75A.75.75 0 016 6.75zM6.75 9h.75a.75.75 0 010 1.5h-.75a.75.75 0 010-1.5zM6 12.75a.75.75 0 01.75-.75h.75a.75.75 0 010 1.5h-.75a.75.75 0 01-.75-.75zM10.5 6a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zm-.75 3.75A.75.75 0 0110.5 9h.75a.75.75 0 010 1.5h-.75a.75.75 0 01-.75-.75zM10.5 12a.75.75 0 000 1.5h.75a.75.75 0 000-1.5h-.75zM16.5 6.75v15h5.25a.75.75 0 000-1.5H21v-12a.75.75 0 000-1.5h-4.5zm1.5 4.5a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75h-.008a.75.75 0 01-.75-.75v-.008zm.75 2.25a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75v-.008a.75.75 0 00-.75-.75h-.008zM18 17.25a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75h-.008a.75.75 0 01-.75-.75v-.008z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h3 class="office-name">{{ office.name ?? office.title ?? 'Unnamed Office' }}</h3>
                                </div>
                                <div class="office-meta">
                                    <span v-if="office.employees.length > 0" class="badge-sm">{{ office.employees.length }} Staff</span>
                                    <svg class="chevron" :class="{ 'rotate-180': office.isOpen }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                        <path fill-rule="evenodd" d="M12.53 16.28a.75.75 0 01-1.06 0l-7.5-7.5a.75.75 0 011.06-1.06L12 14.69l6.97-6.97a.75.75 0 111.06 1.06l-7.5 7.5z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>

                            <div v-if="office.isOpen" class="office-body">
                                <div v-if="office.loading" class="p-4 text-center text-sm text-slate-500">Loading employees...</div>
                                <div v-else-if="office.error" class="p-4 text-center text-sm text-red-500">{{ office.error }}</div>
                                <div v-else-if="office.employees.length === 0" class="p-4 text-center text-sm text-slate-500">No employees found in this office.</div>
                                
                                <div v-else class="table-wrapper border-0 shadow-none">
                                    <table class="emp-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>ID</th>
                                                <th>Position</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(emp, i) in office.employees" :key="emp.id ?? i" class="emp-row">
                                                <td class="row-num">{{ i + 1 }}</td>
                                                <td>
                                                    <div class="emp-info">
                                                        <div class="emp-avatar sm-avatar" :class="emp.sex === 'female' ? 'avatar-female' : 'avatar-male'">
                                                            <img
                                                                v-if="getPhotoUrl(emp)"
                                                                :src="getPhotoUrl(emp)"
                                                                class="avatar-img"
                                                                @error="$event.target.style.display='none'"
                                                            />
                                                            <span v-else>{{ getInitials(emp) }}</span>
                                                        </div>
                                                        <span class="emp-name text-sm">{{ getFullName(emp) }}</span>
                                                    </div>
                                                </td>
                                                <td><span class="empnum-badge text-xs">{{ getEmployeeNumber(emp) }}</span></td>
                                                <td class="text-xs text-slate-500">{{ getEmployeePosition(emp) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
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

/* ── Layout ──────────────────────────────────────────── */
.dashboard-layout {
    display: flex;
    min-height: 100vh;
    background: #f1f5f9;
    font-family: 'Inter', sans-serif;
}

/* ── Sidebar ─────────────────────────────────────────── */
.sidebar {
    width: 260px;
    background: linear-gradient(180deg, #0f172a 0%, #1e3a8a 100%);
    color: white;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; bottom: 0; left: 0;
    z-index: 100;
    transition: transform 0.3s ease;
}

.sidebar-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-logo {
    width: 44px; height: 44px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    position: relative;
    background: transparent;
    box-shadow: none;
}
.sidebar-logo img {
    width: 100%; height: 100%;
    object-fit: cover;
    border-radius: 50%;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}
.logo-fallback {
    display: none;
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    border-radius: 8px;
    align-items: center; justify-content: center;
    color: white; font-weight: 700; font-size: 1.2rem;
    position: absolute; inset: 0;
}

.sidebar-title { font-size: 1rem; font-weight: 700; color: white; letter-spacing: 0.01em; }
.sidebar-sub   { font-size: 0.7rem; color: rgba(255,255,255,0.6); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.05em; }

.sidebar-nav {
    flex: 1;
    padding: 1rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: rgba(255,255,255,0.7);
    font-size: 0.875rem;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    width: 100%;
}
.nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }
.nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
.nav-item.active {
    background: rgba(59, 130, 246, 0.2); /* Blue-500/20 */
    color: #93c5fd; /* Blue-300 */
    border: 1px solid rgba(59, 130, 246, 0.3);
    font-weight: 600;
}
.justify-between { justify-content: space-between; }

.nav-sub-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding-left: 1rem;
    margin-top: 0.25rem;
    margin-bottom: 0.5rem;
    position: relative;
}
.nav-sub-group::before {
    content: ''; position: absolute; left: 1.15rem; top: 0; bottom: 0;
    width: 2px; background: rgba(255,255,255,0.1);
}

.nav-subitem {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem 0.5rem 1.25rem;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: rgba(255,255,255,0.6);
    font-size: 0.8rem;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    width: 100%;
}
.nav-subitem:hover { color: white; background: rgba(255,255,255,0.05); }
.nav-subitem.active {
    color: #60a5fa; /* Blue-400 */
    font-weight: 600;
    background: rgba(59, 130, 246, 0.15);
}

.sidebar-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    background: rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-info { display: flex; align-items: center; gap: 0.6rem; flex: 1; min-width: 0; }

.user-avatar {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem; font-weight: 700; color: white;
    flex-shrink: 0;
    border: 2px solid rgba(255,255,255,0.1);
}

.user-name  { font-size: 0.8rem; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-role  { font-size: 0.7rem; color: rgba(255,255,255,0.5); }

.logout-btn {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 0.4rem;
    cursor: pointer;
    color: rgba(255,255,255,0.6);
    display: flex; align-items: center;
    transition: all 0.2s;
    flex-shrink: 0;
}
.logout-btn svg { width: 16px; height: 16px; }
.logout-btn:hover { background: rgba(239,68,68,0.2); color: #f87171; border-color: rgba(239,68,68,0.3); }

/* ── Main ────────────────────────────────────────────── */
.main-content {
    flex: 1;
    margin-left: 260px;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

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
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.hamburger { display: none; background: none; border: none; cursor: pointer; color: #64748b; padding: 0.25rem; }
.hamburger svg { width: 22px; height: 22px; }

.topbar-title { flex: 1; }
.topbar-title h1 { font-size: 1.125rem; font-weight: 700; color: #0f172a; letter-spacing: -0.01em; }
.topbar-title p  { font-size: 0.75rem; color: #64748b; margin-top: 2px; }

.badge {
    background: #dbeafe; /* Blue-100 */
    color: #1e40af;      /* Blue-800 */
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
}

.content-area {
    flex: 1;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* ── Office search ───────────────────────────────────── */
.office-search-bar {
    display: flex;
    gap: 0.75rem;
    background: white;
    padding: 1rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.office-input-wrapper { flex: 1; position: relative; display: flex; align-items: center; }
.office-icon { position: absolute; left: 12px; width: 18px; height: 18px; color: #94a3b8; pointer-events: none; }
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
.office-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

.office-select {
    appearance: none;
    cursor: pointer;
    background-color: white;
}
.office-spinner {
    position: absolute;
    right: 12px;
    width: 20px; height: 20px;
}

/* ── Search ──────────────────────────────────────────── */
.search-bar { position: relative; display: flex; align-items: center; }
.search-icon { position: absolute; left: 14px; width: 18px; height: 18px; color: #94a3b8; pointer-events: none; }
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
.search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

/* ── Error ───────────────────────────────────────────── */
.error-banner {
    display: flex; align-items: center; gap: 0.5rem;
    padding: 0.875rem 1rem;
    background: #fef2f2; border: 1px solid #fecaca;
    border-radius: 10px; color: #dc2626; font-size: 0.875rem;
}
.error-banner svg { width: 18px; height: 18px; flex-shrink: 0; }

/* ── Skeleton ────────────────────────────────────────── */
.skeleton-list { display: flex; flex-direction: column; gap: 0.5rem; }
.skeleton-row {
    display: flex; align-items: center; gap: 1rem;
    padding: 1rem 1.25rem;
    background: white; border-radius: 10px; border: 1px solid #e2e8f0;
}
.skeleton-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    flex-shrink: 0;
}
.skeleton-lines { flex: 1; display: flex; flex-direction: column; gap: 0.4rem; }
.skeleton-line {
    height: 12px; border-radius: 6px;
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}
.w-48 { width: 192px; }
.w-32 { width: 128px; }
.w-24 { width: 96px; }
.ml-auto { margin-left: auto; }

@keyframes shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ── Empty state ─────────────────────────────────────── */
.empty-state {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 4rem 2rem;
    color: #94a3b8; gap: 1rem;
    background: white; border-radius: 12px; border: 1px solid #e2e8f0;
}
.empty-state svg { width: 48px; height: 48px; opacity: 0.4; }
.empty-state p   { font-size: 0.9rem; text-align: center; }
.empty-state strong { color: #3b82f6; }

/* ── Table ───────────────────────────────────────────── */
.table-wrapper {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.emp-table { width: 100%; border-collapse: collapse; }

.emp-table thead tr { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }

.emp-table th {
    padding: 0.875rem 1rem;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.emp-row { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
.emp-row:last-child { border-bottom: none; }
.emp-row:hover { background: #f8fafc; }

.emp-table td {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #334155;
    vertical-align: middle;
}

.row-num { color: #94a3b8; font-size: 0.8rem; width: 48px; }

.emp-info { display: flex; align-items: center; gap: 0.75rem; }

.emp-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    color: white;
    font-size: 0.72rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    letter-spacing: 0.5px;
}
.avatar-male   { background: linear-gradient(135deg, #3b82f6, #0ea5e9); }
.avatar-female { background: linear-gradient(135deg, #ec4899, #f43f5e); }

.avatar-img {
    width: 100%; height: 100%;
    object-fit: cover;
    border-radius: 50%;
    display: block;
}

.emp-name     { font-weight: 600; color: #0f172a; font-size: 0.875rem; }

.empnum-badge {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    background: #f1f5f9;
    color: #475569;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    font-family: 'Courier New', monospace;
    white-space: nowrap;
}

.sex-badge {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: capitalize;
}
.sex-male   { background: #dbeafe; color: #1e40af; }
.sex-female { background: #fce7f3; color: #be185d; }

.email-cell { color: #3b82f6; font-size: 0.8rem; }

/* ── Pagination ──────────────────────────────────────── */
.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1.25rem;
    border-top: 1px solid #f1f5f9;
    background: #fafafa;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.page-info {
    font-size: 0.8rem;
    color: #64748b;
}
.page-info strong { color: #0f172a; }

.page-controls {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.page-btn {
    min-width: 34px; height: 34px;
    padding: 0 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    color: #475569;
    font-size: 0.8rem;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
}
.page-btn svg { width: 14px; height: 14px; }
.page-btn:hover:not(:disabled):not(.page-active) { background: #f8fafc; border-color: #cbd5e1; color: #0f172a; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-btn.page-active {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    border-color: transparent;
    color: white;
    font-weight: 700;
}

.page-ellipsis {
    color: #94a3b8;
    font-size: 0.85rem;
    padding: 0 0.25rem;
    user-select: none;
}

/* ── Spinner ─────────────────────────────────────────── */
.spinner-sm { width: 16px; height: 16px; animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* ── Mobile overlay ──────────────────────────────────── */
.overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 99;
}

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.sidebar-open { transform: translateX(0); }
    .overlay { display: block; }
    .main-content { margin-left: 0; }
    .hamburger { display: flex; }
    .office-search-bar { flex-direction: column; }
    
    /* Mobile Table -> Cards */
    .emp-table, .emp-table tbody, .emp-table tr, .emp-table td {
        display: block;
        width: 100%;
    }
    .emp-table thead { display: none; }
    
    .emp-row {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 1rem;
        padding: 1rem;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .emp-row:hover { background: white; }
    
    .emp-table td {
        padding: 0.25rem 0;
        border: none;
        text-align: left;
    }
    
    /* Row Number */
    .emp-table td:nth-child(1) {
        font-size: 0.7rem;
        color: #94a3b8;
        margin-bottom: 0.5rem;
    }
    
    /* Name & Link */
    .emp-table td:nth-child(2) {
        margin-bottom: 0.75rem;
    }
    
    /* Employee No */
    .emp-table td:nth-child(3) {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f1f5f9;
        padding-top: 0.75rem;
        margin-top: 0.25rem;
    }
    .emp-table td:nth-child(3)::before {
        content: "Employee ID";
        font-size: 0.7rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
    }

    /* Sex Badge */
    .emp-table td:nth-child(4) {
        display: flex !important; /* Force show */
        justify-content: space-between;
        align-items: center;
        padding-top: 0.25rem;
    }
    .emp-table td:nth-child(4)::before {
        content: "Sex";
        font-size: 0.7rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
    }

    /* Email */
    .emp-table td:nth-child(5) {
        display: flex !important; /* Force show */
        justify-content: space-between;
        align-items: center;
        padding-top: 0.25rem;
        word-break: break-all;
    }
    .emp-table td:nth-child(5)::before {
        content: "Email";
        font-size: 0.7rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .pagination { flex-direction: column; gap: 1rem; align-items: center; }
    .page-controls { width: 100%; justify-content: center; }
}

/* ── Office List Accordion ───────────────────────────── */
.office-list {
    display: flex; flex-direction: column; gap: 1rem;
}

.office-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.office-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
    border-color: #cbd5e1;
}
.office-card.is-open {
    border-color: #3b82f6; /* Blue-500 */
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
}

.office-header {
    padding: 1.25rem 1.5rem;
    display: flex; align-items: center; justify-content: space-between;
    cursor: pointer;
    background: white;
    transition: background 0.15s;
    user-select: none;
}
.office-header:hover { background: #fdfeff; }
.office-card.is-open .office-header { background: #f8fafc; border-bottom: 1px solid #f1f5f9; }

.office-title-row {
    display: flex; align-items: center; gap: 1rem; flex: 1;
}

.office-icon-bg {
    width: 42px; height: 42px;
    background: #dbeafe; /* Blue-100 */
    color: #2563eb;      /* Blue-600 */
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.office-name {
    font-size: 1rem; font-weight: 600; color: #1e293b;
    flex: 1;
}

.office-meta { display: flex; align-items: center; gap: 1rem; }

.chevron {
    width: 20px; height: 20px; color: #94a3b8;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.rotate-180 { transform: rotate(180deg); color: #2563eb; }

.badge-sm {
    font-size: 0.75rem; font-weight: 600;
    background: #f1f5f9; color: #64748b;
    padding: 0.35rem 0.85rem; border-radius: 999px;
    letter-spacing: 0.02em;
}

.office-body {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

/* Utilities */
.text-center { text-align: center; }
.py-8 { padding-top: 2rem; padding-bottom: 2rem; }
.p-4 { padding: 1rem; }
.text-slate-400 { color: #94a3b8; }
.text-slate-500 { color: #64748b; }
.text-red-500   { color: #ef4444; }
.text-sm { font-size: 0.875rem; }
.text-xs { font-size: 0.75rem; }
.mb-4 { margin-bottom: 1rem; }
.mt-4 { margin-top: 1rem; }

.sm-avatar { width: 28px; height: 28px; font-size: 0.6rem; }
.border-0 { border: none !important; }
.shadow-none { box-shadow: none !important; }

/* ── Modal ───────────────────────────────────────────── */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    z-index: 200;
    animation: fadeIn 0.2s ease-out;
}

.modal-content {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    width: 90%; max-width: 340px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    text-align: center;
    animation: scaleIn 0.2s ease-out;
}

.modal-icon {
    width: 48px; height: 48px;
    background: #fee2e2; color: #ef4444; /* Red-100, Red-500 */
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem auto;
}
.modal-icon svg { width: 24px; height: 24px; }

.modal-content h3 {
    font-size: 1.125rem; font-weight: 700; color: #0f172a;
    margin-bottom: 0.5rem;
}

.modal-content p {
    font-size: 0.875rem; color: #64748b;
    margin-bottom: 1.5rem; line-height: 1.5;
}

.modal-actions {
    display: flex; gap: 0.75rem;
}

.modal-actions button {
    flex: 1;
    padding: 0.625rem;
    border-radius: 8px;
    font-size: 0.875rem; font-weight: 600;
    cursor: pointer; transition: all 0.2s;
    font-family: inherit;
}

.btn-cancel {
    background: white; border: 1px solid #e2e8f0; color: #475569;
}
.btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; color: #0f172a; }

.btn-confirm {
    background: #ef4444; border: 1px solid #ef4444; color: white;
}
.btn-confirm:hover { background: #dc2626; border-color: #dc2626; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.25); }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes scaleIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
