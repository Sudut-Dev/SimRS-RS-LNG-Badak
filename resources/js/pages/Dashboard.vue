<template>
  <div>
    <!-- Stats Grid -->
    <div class="stat-grid mb-6">
      <div class="stat-card">
        <!-- <div class="stat-icon" style="background:var(--primary-bg)"></div> -->
        <div class="stat-value">{{ fmt(stats.pendaftaran_hari_ini) }}</div>
        <div class="stat-label">Pendaftaran Hari Ini</div>
      </div>
      <div class="stat-card">
        <!-- <div class="stat-icon" style="background:var(--success-bg)"></div> -->
        <div class="stat-value text-success">{{ fmt(stats.pasien_selesai) }}</div>
        <div class="stat-label">Selesai Diperiksa</div>
      </div>
      <div class="stat-card">
        <!-- <div class="stat-icon" style="background:var(--warning-bg)"></div> -->
        <div class="stat-value text-warning">{{ fmt(stats.pasien_menunggu) }}</div>
        <div class="stat-label">Masih Menunggu</div>
      </div>
      <div class="stat-card">
        <!-- <div class="stat-icon" style="background:var(--success-bg)"></div> -->
        <div class="stat-value text-success" style="font-size:1.25rem">{{ rupiah(stats.pendapatan_hari_ini) }}</div>
        <div class="stat-label">Pendapatan Hari Ini</div>
      </div>
      <div class="stat-card">
        <!-- <div class="stat-icon" style="background:var(--info-bg)">👥</div> -->
        <div class="stat-value text-primary">{{ fmt(stats.total_pasien) }}</div>
        <div class="stat-label">Total Pasien Terdaftar</div>
      </div>
      <div class="stat-card">
        <!-- <div class="stat-icon" style="background:var(--primary-bg)"></div> -->
        <div class="stat-value">{{ fmt(stats.total_dokter) }}</div>
        <div class="stat-label">Dokter Aktif</div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="mb-6 dashboard-charts-row">
      <!-- Trend Pendaftaran 7 Hari -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">📈 Trend Pendaftaran (7 Hari)</div>
        </div>
        <div class="card-body">
          <div class="chart-container">
            <Bar v-if="chartPendaftaranReady" :data="chartDataPendaftaran" :options="barOptions" />
            <div v-else class="flex items-center justify-center h-full"><div class="spinner spinner-lg"></div></div>
          </div>
        </div>
      </div>

      <!-- Pendaftaran Per Poli -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">🍩 Per Poli Hari Ini</div>
        </div>
        <div class="card-body">
          <div class="chart-container">
            <Doughnut v-if="chartPoliReady" :data="chartDataPoli" :options="doughnutOptions" />
            <div v-else class="empty-state" style="height:100%">
              <div class="empty-state-icon">📊</div>
              <div class="empty-state-text">Belum ada data hari ini</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Antrian & Laporan Row -->
    <div class="dashboard-bottom-row">
      <!-- Antrian Terkini -->
      <div class="card">
        <div class="card-header">
          <div class="card-title"> Antrian Aktif</div>
          <div class="badge badge-warning">{{ antrianTerkini.length }} menunggu</div>
        </div>
        <div class="card-body p-0">
          <div v-if="antrianTerkini.length === 0" class="empty-state">
            <div class="empty-state-icon"></div>
            <div class="empty-state-title">Tidak ada antrian</div>
            <div class="empty-state-text">Semua pasien hari ini sudah selesai</div>
          </div>
          <div v-else style="max-height:280px;overflow-y:auto">
            <div v-for="a in antrianTerkini" :key="a.id" class="flex items-center gap-3 p-3" style="border-bottom:1px solid var(--border)">
              <div style="width:36px;height:36px;background:var(--primary-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.85rem;color:var(--primary);flex-shrink:0">
                {{ a.no_urut }}
              </div>
              <div class="flex-1 overflow-hidden">
                <div class="font-semibold truncate" style="font-size:0.875rem">{{ a.pasien?.nama_pasien }}</div>
                <div class="text-xs text-muted">{{ a.jadwal_dokter?.dokter?.nama_dokter }} • {{ a.jadwal_dokter?.poli?.nama_poli }}</div>
              </div>
              <span class="badge" :class="a.status === 'dipanggil' ? 'badge-primary' : 'badge-warning'">
                <span class="status-dot" :class="a.status"></span>
                {{ a.status }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Pendapatan Bulanan -->
      <div class="card">
        <div class="card-header">
          <div class="card-title"> Pendapatan Bulanan {{ currentYear }}</div>
        </div>
        <div class="card-body">
          <div class="chart-container">
            <Line v-if="chartPendapatanReady" :data="chartDataPendapatan" :options="lineOptions" />
            <div v-else class="flex items-center justify-center h-full"><div class="spinner spinner-lg"></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-charts-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1rem;
}
.dashboard-bottom-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
@media (max-width: 900px) {
  .dashboard-charts-row,
  .dashboard-bottom-row {
    grid-template-columns: 1fr;
  }
}
</style>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale, LinearScale, BarElement, LineElement, PointElement,
  ArcElement, Title, Tooltip, Legend, Filler,
} from 'chart.js';
import { useApi } from '../composables/useApi';
import axios from 'axios';

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Title, Tooltip, Legend, Filler);

const { get } = useApi();

const stats          = ref({ pendaftaran_hari_ini: 0, pasien_selesai: 0, pasien_menunggu: 0, pendapatan_hari_ini: 0, total_pasien: 0, total_dokter: 0 });
const antrianTerkini = ref([]);
const currentYear    = new Date().getFullYear();

// Chart data
const chartDataPendaftaran = ref({ labels: [], datasets: [] });
const chartDataPoli        = ref({ labels: [], datasets: [] });
const chartDataPendapatan  = ref({ labels: [], datasets: [] });
const chartPendaftaranReady = ref(false);
const chartPoliReady       = ref(false);
const chartPendapatanReady = ref(false);

const fmt    = (v) => (v ?? 0).toLocaleString('id-ID');
const rupiah = (v) => 'Rp ' + (v ?? 0).toLocaleString('id-ID');

// Detect current theme for chart colors
function getChartColors() {
  const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
  return {
    tickColor:  isDark ? '#94a3b8' : '#64748b',
    gridColor:  isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.07)',
    legendColor: isDark ? '#94a3b8' : '#64748b',
  };
}

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => `${ctx.raw} pendaftaran` } } },
  scales: {
    x: { grid: { color: () => getChartColors().gridColor }, ticks: { color: () => getChartColors().tickColor, font: { size: 11 } } },
    y: { grid: { color: () => getChartColors().gridColor }, ticks: { color: () => getChartColors().tickColor, font: { size: 11 }, precision: 0 }, beginAtZero: true },
  },
};

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom', labels: { color: () => getChartColors().legendColor, font: { size: 11 }, padding: 12, boxWidth: 12 } },
  },
  cutout: '65%',
};

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => `Rp ${ctx.raw.toLocaleString('id-ID')}` } } },
  scales: {
    x: { grid: { display: false }, ticks: { color: () => getChartColors().tickColor, font: { size: 11 } } },
    y: { grid: { color: () => getChartColors().gridColor }, ticks: { color: () => getChartColors().tickColor, font: { size: 11 }, callback: v => 'Rp ' + (v/1000).toFixed(0) + 'K' }, beginAtZero: true },
  },
};

const COLORS = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316'];

async function fetchDashboard() {
  const res = await get('/api/dashboard');
  if (!res.success) return;

  stats.value          = res.data.stats;
  antrianTerkini.value = res.data.antrianTerkini;

  // Chart: pendaftaran 7 hari
  const p7 = res.data.chartPendaftaran;
  chartDataPendaftaran.value = {
    labels: p7.map(r => r.tanggal),
    datasets: [{
      label: 'Pendaftaran',
      data: p7.map(r => r.total),
      backgroundColor: 'rgba(59,130,246,0.7)',
      borderColor: '#3b82f6',
      borderRadius: 6,
      borderSkipped: false,
    }],
  };
  chartPendaftaranReady.value = true;

  // Chart: per poli
  const pp = res.data.chartPerPoli.filter(r => r.total > 0);
  if (pp.length > 0) {
    chartDataPoli.value = {
      labels: pp.map(r => r.nama),
      datasets: [{
        data: pp.map(r => r.total),
        backgroundColor: COLORS.slice(0, pp.length),
        borderWidth: 0,
      }],
    };
    chartPoliReady.value = true;
  }
}

async function fetchLaporan() {
  const res = await get('/api/laporan-pembayaran', { tahun: currentYear });
  if (!res.success) return;

  const d = res.data.data;
  chartDataPendapatan.value = {
    labels: d.map(r => r.bulan),
    datasets: [{
      label: 'Pendapatan',
      data: d.map(r => r.total),
      borderColor: '#10b981',
      backgroundColor: 'rgba(16,185,129,0.1)',
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#10b981',
      pointRadius: 4,
    }],
  };
  chartPendapatanReady.value = true;
}

onMounted(() => {
  fetchDashboard();
  fetchLaporan();
});
</script>
