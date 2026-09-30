<template>
  <div class="app-layout" :class="{ 'sidebar-collapsed': sidebarCollapsed }">

    <!-- Mobile Overlay -->
    <transition name="fade-overlay">
      <div
        v-if="sidebarOpen"
        class="fixed inset-0"
        style="z-index:199;background:rgba(0,0,0,0.55)"
        @click="sidebarOpen = false"
      />
    </transition>

    <!-- Sidebar -->
    <aside class="sidebar" :class="{ open: sidebarOpen }">
      <!-- Logo -->
      <div class="sidebar-logo">
        <div class="sidebar-logo-text">
          <div class="sidebar-logo-title">SIMRS</div>
          <div class="sidebar-logo-subtitle">RS LNG Badak</div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav">
        <div class="sidebar-section-title">Utama</div>
        <div class="nav-item" :class="{ active: $route.name === 'dashboard' }" @click="navigate('dashboard')">
          <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
          Dashboard
        </div>

        <div class="sidebar-section-title">Transaksi</div>
        <div class="nav-item" :class="{ active: $route.name === 'pendaftaran' }" @click="navigate('pendaftaran')">
          <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Pendaftaran
        </div>
        <div class="nav-item" :class="{ active: $route.name === 'pembayaran' }" @click="navigate('pembayaran')">
          <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          Pembayaran
        </div>

        <div class="sidebar-section-title">Data Master</div>
        <div class="nav-item" :class="{ active: $route.name === 'pasien' }" @click="navigate('pasien')">
          <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          Data Pasien
        </div>
        <div class="nav-item" :class="{ active: $route.name === 'jadwal' }" @click="navigate('jadwal')">
          <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Jadwal Dokter
        </div>

        <template v-if="isAdmin">
          <div class="sidebar-section-title">Admin</div>
          <div class="nav-item" :class="{ active: $route.name === 'dokter' }" @click="navigate('dokter')">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Data Dokter
          </div>
          <div class="nav-item" :class="{ active: $route.name === 'poli' }" @click="navigate('poli')">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Data Poli
          </div>
          <div class="nav-item" :class="{ active: $route.name === 'users' }" @click="navigate('users')">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            Manajemen User
          </div>
        </template>
      </nav>

      <!-- User Footer -->
      <div class="sidebar-footer">
        <div class="user-profile">
          <div class="user-avatar">{{ userInitial }}</div>
          <div class="user-info">
            <div class="user-name">{{ currentUser.name }}</div>
            <div class="user-role">{{ currentUser.role === 'admin' ? '⚡ Administrator' : '👤 Petugas' }}</div>
          </div>
          <form method="POST" action="/logout" @submit.prevent="logout" style="margin-left:auto;flex-shrink:0">
            <button type="submit" class="btn btn-ghost btn-icon-sm" title="Keluar" style="color:var(--text-muted)">
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </button>
          </form>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Topbar -->
      <header class="topbar">
        <div class="topbar-left">
          <button class="btn btn-ghost btn-icon" @click="toggleSidebar" title="Toggle Sidebar" id="sidebar-toggle">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <div class="min-w-0">
            <div class="topbar-title truncate">{{ currentPageTitle }}</div>
            <div class="topbar-subtitle">{{ currentDate }}</div>
          </div>
        </div>

        <div class="topbar-right">
          <!-- Dark/Light mode toggle -->
          <button
            class="theme-toggle"
            @click="toggleTheme"
            :title="isDark ? 'Ganti ke Light Mode' : 'Ganti ke Dark Mode'"
            id="theme-toggle-btn"
          >
            <!-- Sun icon (light mode) -->
            <svg v-if="isDark" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
            </svg>
            <!-- Moon icon (dark mode) -->
            <svg v-else width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
          </button>

          <div class="badge" :class="isAdmin ? 'badge-primary' : 'badge-success'">
            {{ isAdmin ? '⚡ Admin' : '👤 Petugas' }}
          </div>
        </div>
      </header>

      <!-- Page -->
      <main class="page-content">
        <router-view />
      </main>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container">
      <div v-for="toast in toasts" :key="toast.id" class="toast" :class="`toast-${toast.type}`">
        <span style="font-size:1.1rem;flex-shrink:0">{{ toastIcon(toast.type) }}</span>
        <span style="flex:1;color:var(--text-primary);word-break:break-word">{{ toast.message }}</span>
        <button class="btn btn-ghost btn-icon-sm" @click="$toast.remove(toast.id)" style="color:var(--text-muted);flex-shrink:0">✕</button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from './composables/useToast';
import axios from 'axios';

const router = useRouter();
const route  = useRoute();
const { toasts } = useToast();

const currentUser      = ref(window.__AUTH_USER__ ?? { name: 'User', role: 'petugas' });
const sidebarCollapsed = ref(false);
const sidebarOpen      = ref(false);
const isDark           = ref(true);

// ---- Theme ----
function applyTheme(dark) {
  const theme = dark ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('simrs-theme', theme);
  isDark.value = dark;
}

function toggleTheme() {
  applyTheme(!isDark.value);
}

// ---- Computed ----
const isAdmin = computed(() => currentUser.value?.role === 'admin');

const userInitial = computed(() =>
  (currentUser.value?.name ?? 'U').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
);

const currentPageTitle = computed(() => route.meta?.title ?? 'Dashboard');

const currentDate = computed(() =>
  new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
);

// ---- Navigation ----
function navigate(name) {
  router.push({ name });
  sidebarOpen.value = false;
}

function toggleSidebar() {
  if (window.innerWidth < 1024) {
    sidebarOpen.value = !sidebarOpen.value;
  } else {
    sidebarCollapsed.value = !sidebarCollapsed.value;
  }
}

// Close sidebar on resize to desktop
function handleResize() {
  if (window.innerWidth >= 1024) {
    sidebarOpen.value = false;
  }
}

function toastIcon(type) {
  return { success: '✅', danger: '❌', warning: '⚠️', info: 'ℹ️' }[type] ?? 'ℹ️';
}

async function logout() {
  try {
    await axios.post('/logout');
    window.location.href = '/login';
  } catch {
    window.location.href = '/login';
  }
}

// ---- Lifecycle ----
onMounted(() => {
  // Restore saved theme
  const saved = localStorage.getItem('simrs-theme') ?? 'dark';
  applyTheme(saved === 'light' ? false : true);

  window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
.fade-overlay-enter-active,
.fade-overlay-leave-active { transition: opacity 0.25s ease; }
.fade-overlay-enter-from,
.fade-overlay-leave-to    { opacity: 0; }
</style>
