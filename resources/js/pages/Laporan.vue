<template>
  <div>

    <!-- ===== TAB HEADER ===== -->
    <div class="lp-tabs mb-5">
      <button v-for="t in tabs" :key="t.key"
              class="lp-tab" :class="{ active: activeTab === t.key }"
              @click="switchTab(t.key)">
        <span>{{ t.icon }}</span>
        {{ t.label }}
      </button>
    </div>

    <!-- ==================== TAB: HARIAN ==================== -->
    <div v-if="activeTab === 'harian'">
      <!-- Filter -->
      <div class="flex items-center gap-3 flex-wrap mb-5">
        <label class="form-label mb-0">Tanggal:</label>
        <input type="date" v-model="tanggal" @change="loadHarian" class="form-control" style="width:auto"/>
        <button class="btn btn-outline btn-sm" @click="tanggal = today; loadHarian()">Hari Ini</button>
        <button class="btn btn-outline btn-sm" @click="exportHarianCSV">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Export CSV
        </button>
        <button class="btn btn-outline btn-sm" @click="printPage">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
          </svg>
          Cetak
        </button>
        <div v-if="harian.loading" class="spinner spinner-sm"></div>
      </div>

      <!-- Ringkasan Cards -->
      <div class="lp-summary-grid mb-5" v-if="harian.data">
        <div class="lp-summary-card" v-for="c in harianCards" :key="c.key">
          <div class="lp-sc-icon" :style="{ background: c.bg }">{{ c.icon }}</div>
          <div>
            <div class="lp-sc-value" :style="c.money ? { color: 'var(--success)' } : {}">
              {{ c.money ? rupiah(harian.data.ringkasan[c.key]) : (harian.data.ringkasan[c.key] ?? 0) }}
            </div>
            <div class="lp-sc-label">{{ c.label }}</div>
          </div>
        </div>
      </div>

      <div class="lp-two-col mb-5" v-if="harian.data">
        <!-- Pembayaran per Metode -->
        <div class="card">
          <div class="card-header"><div class="card-title">Pendapatan per Metode</div></div>
          <div class="card-body">
            <div v-if="harian.data.per_metode.length === 0" class="empty-state-text text-center py-4">Belum ada transaksi</div>
            <div v-for="m in harian.data.per_metode" :key="m.metode_bayar" class="lp-bar-row">
              <div class="lp-bar-info">
                <span class="lp-bar-label">{{ ucFirst(m.metode_bayar) }}</span>
                <span class="badge badge-secondary">{{ m.jumlah }}x</span>
              </div>
              <div class="lp-bar-track">
                <div class="lp-bar-fill"
                     :style="{ width: (m.total / harian.data.ringkasan.total_pendapatan * 100) + '%' }"></div>
              </div>
              <div class="lp-bar-value">{{ rupiah(m.total) }}</div>
            </div>
          </div>
        </div>

        <!-- Pendaftaran per Poli -->
        <div class="card">
          <div class="card-header"><div class="card-title">Pendaftaran per Poli</div></div>
          <div class="card-body">
            <div v-if="harian.data.per_poli.length === 0" class="empty-state-text text-center py-4">Belum ada pendaftaran</div>
            <div v-for="p in harian.data.per_poli" :key="p.id" class="lp-bar-row">
              <div class="lp-bar-info">
                <span class="lp-bar-label">{{ p.nama_poli }}</span>
                <span class="badge badge-primary">{{ p.pendaftaran_count }}</span>
              </div>
              <div class="lp-bar-track">
                <div class="lp-bar-fill" style="background:var(--primary)"
                     :style="{ width: (p.pendaftaran_count / harian.data.ringkasan.total_pendaftaran * 100) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabel Transaksi -->
      <div class="card" v-if="harian.data">
        <div class="card-header">
          <div class="card-title">Detail Transaksi Pembayaran — {{ formatDate(tanggal) }}</div>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>No. Kwitansi</th>
                <th>Pasien</th>
                <th class="lp-hide-sm">Poli / Dokter</th>
                <th>Metode</th>
                <th style="text-align:right">Total</th>
                <th class="lp-hide-sm" style="text-align:right">Kembalian</th>
                <th class="lp-hide-sm">Kasir</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="harian.data.pembayaran.length === 0">
                <td colspan="7">
                  <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <div class="empty-state-text">Belum ada transaksi pada tanggal ini</div>
                  </div>
                </td>
              </tr>
              <tr v-for="p in harian.data.pembayaran" :key="p.id">
                <td><span class="font-mono text-sm" style="color:var(--primary)">{{ p.no_kwitansi }}</span></td>
                <td class="text-sm">{{ p.pendaftaran?.pasien?.nama_pasien ?? '—' }}</td>
                <td class="lp-hide-sm text-sm text-muted">
                  {{ p.pendaftaran?.jadwal_dokter?.poli?.nama_poli ?? '—' }}
                </td>
                <td>
                  <span class="badge badge-secondary">{{ ucFirst(p.metode_bayar) }}</span>
                </td>
                <td class="text-right money text-sm" style="color:var(--success)">{{ rupiah(p.total_tagihan) }}</td>
                <td class="lp-hide-sm text-right text-sm">{{ rupiah(p.kembalian) }}</td>
                <td class="lp-hide-sm text-sm text-muted">{{ p.kasir?.name ?? '—' }}</td>
              </tr>
            </tbody>
            <tfoot v-if="harian.data.pembayaran.length > 0">
              <tr style="background:var(--bg-elevated)">
                <td colspan="4" class="font-semibold" style="color:var(--text-primary)">TOTAL</td>
                <td class="text-right money font-bold" style="color:var(--success)">
                  {{ rupiah(harian.data.ringkasan.total_pendapatan) }}
                </td>
                <td colspan="2" class="lp-hide-sm"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- ==================== TAB: BULANAN ==================== -->
    <div v-if="activeTab === 'bulanan'">
      <!-- Filter -->
      <div class="flex items-center gap-3 flex-wrap mb-5">
        <label class="form-label mb-0">Bulan:</label>
        <input type="month" v-model="bulan" @change="loadBulanan" class="form-control" style="width:auto"/>
        <button class="btn btn-outline btn-sm" @click="bulan = thisMonth; loadBulanan()">Bulan Ini</button>
        <button class="btn btn-outline btn-sm" @click="exportBulananCSV">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Export CSV
        </button>
        <div v-if="bulanan.loading" class="spinner spinner-sm"></div>
      </div>

      <!-- Ringkasan Cards -->
      <div class="lp-summary-grid mb-5" v-if="bulanan.data">
        <div class="lp-summary-card" v-for="c in bulananCards" :key="c.key">
          <div class="lp-sc-icon" :style="{ background: c.bg }">{{ c.icon }}</div>
          <div>
            <div class="lp-sc-value" :style="c.money ? { color: 'var(--success)' } : {}">
              {{ c.money ? rupiah(bulanan.data.ringkasan[c.key]) : (bulanan.data.ringkasan[c.key] ?? 0) }}
            </div>
            <div class="lp-sc-label">{{ c.label }}</div>
          </div>
        </div>
      </div>

      <!-- Chart: Pendapatan per Hari -->
      <div class="card mb-5" v-if="bulanan.data">
        <div class="card-header">
          <div class="card-title">Trend Pendapatan Harian — {{ formatMonth(bulan) }}</div>
        </div>
        <div class="card-body">
          <div class="chart-container" style="height:260px">
            <Line v-if="bulananChartReady" :data="bulananChartData" :options="lineOpts"/>
            <div v-else class="flex items-center justify-center h-full">
              <div class="spinner spinner-lg"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="lp-two-col mb-5" v-if="bulanan.data">
        <!-- Per Metode -->
        <div class="card">
          <div class="card-header"><div class="card-title">Per Metode Bayar</div></div>
          <div class="card-body">
            <div v-for="m in bulanan.data.per_metode" :key="m.metode_bayar" class="lp-bar-row">
              <div class="lp-bar-info">
                <span class="lp-bar-label">{{ ucFirst(m.metode_bayar) }}</span>
                <span class="badge badge-secondary">{{ m.jumlah }}x</span>
              </div>
              <div class="lp-bar-track">
                <div class="lp-bar-fill" :style="{ width: (m.total / bulanan.data.ringkasan.total_pendapatan * 100) + '%' }"></div>
              </div>
              <div class="lp-bar-value">{{ rupiah(m.total) }}</div>
            </div>
          </div>
        </div>

        <!-- Top Dokter -->
        <div class="card">
          <div class="card-header"><div class="card-title">Top Dokter</div></div>
          <div class="card-body">
            <div v-if="bulanan.data.top_dokter.length === 0" class="empty-state-text text-center py-4">Belum ada data</div>
            <div v-for="(d, i) in bulanan.data.top_dokter" :key="d.id" class="lp-rank-row">
              <div class="lp-rank-num" :class="i < 3 ? `lp-rank-${i+1}` : ''">{{ i + 1 }}</div>
              <div class="flex-1 min-w-0">
                <div class="font-semibold text-sm truncate" style="color:var(--text-primary)">{{ d.nama_dokter }}</div>
                <div class="text-xs text-muted">{{ d.spesialisasi }}</div>
              </div>
              <div class="badge badge-primary">{{ d.pendaftaran_count }} pasien</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabel per hari -->
      <div class="card" v-if="bulanan.data">
        <div class="card-header"><div class="card-title">Rincian per Hari</div></div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Jumlah Transaksi</th>
                <th style="text-align:right">Total Pendapatan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="bulanan.data.per_hari.length === 0">
                <td colspan="3"><div class="empty-state"><div class="empty-state-icon"></div><div class="empty-state-text">Belum ada data bulan ini</div></div></td>
              </tr>
              <tr v-for="h in bulanan.data.per_hari" :key="h.tanggal">
                <td class="text-sm">{{ formatDate(h.tanggal) }}</td>
                <td><span class="badge badge-secondary">{{ h.jumlah }}x transaksi</span></td>
                <td class="text-right money text-sm" style="color:var(--success)">{{ rupiah(h.total) }}</td>
              </tr>
            </tbody>
            <tfoot v-if="bulanan.data.per_hari.length > 0">
              <tr style="background:var(--bg-elevated)">
                <td class="font-semibold">TOTAL</td>
                <td><span class="badge badge-secondary">{{ bulanan.data.ringkasan.total_transaksi }}x</span></td>
                <td class="text-right money font-bold" style="color:var(--success)">{{ rupiah(bulanan.data.ringkasan.total_pendapatan) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- ==================== TAB: TAHUNAN ==================== -->
    <div v-if="activeTab === 'tahunan'">
      <!-- Filter -->
      <div class="flex items-center gap-3 flex-wrap mb-5">
        <label class="form-label mb-0">Tahun:</label>
        <select v-model="tahun" @change="loadTahunan" class="form-select" style="width:auto">
          <option v-for="y in tahunList" :key="y" :value="y">{{ y }}</option>
        </select>
        <button class="btn btn-outline btn-sm" @click="exportTahunanCSV">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Export CSV
        </button>
        <div v-if="tahunan.loading" class="spinner spinner-sm"></div>
      </div>

      <!-- Ringkasan Cards -->
      <div class="lp-summary-grid mb-5" v-if="tahunan.data">
        <div class="lp-summary-card" v-for="c in tahunanCards" :key="c.key">
          <div class="lp-sc-icon" :style="{ background: c.bg }">{{ c.icon }}</div>
          <div>
            <div class="lp-sc-value" :style="c.money ? { color: 'var(--success)' } : c.growth ? { color: growthColor } : {}">
              <template v-if="c.growth">
                {{ tahunan.data.ringkasan.growth !== null ? (tahunan.data.ringkasan.growth >= 0 ? '+' : '') + tahunan.data.ringkasan.growth + '%' : 'N/A' }}
              </template>
              <template v-else-if="c.money">{{ rupiah(tahunan.data.ringkasan[c.key]) }}</template>
              <template v-else>{{ tahunan.data.ringkasan[c.key] ?? 0 }}</template>
            </div>
            <div class="lp-sc-label">{{ c.label }}</div>
          </div>
        </div>
      </div>

      <!-- Chart: Trend 12 Bulan -->
      <div class="card mb-5" v-if="tahunan.data">
        <div class="card-header">
          <div class="card-title">Trend Pendapatan & Pendaftaran {{ tahun }}</div>
        </div>
        <div class="card-body">
          <div class="chart-container" style="height:280px">
            <Bar v-if="tahunanChartReady" :data="tahunanChartData" :options="barOpts"/>
            <div v-else class="flex items-center justify-center h-full">
              <div class="spinner spinner-lg"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabel 12 Bulan -->
      <div class="card" v-if="tahunan.data">
        <div class="card-header"><div class="card-title">Rincian per Bulan</div></div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Bulan</th>
                <th style="text-align:right">Pendaftaran</th>
                <th style="text-align:right">Transaksi</th>
                <th style="text-align:right">Pendapatan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in tahunan.data.data" :key="d.bulan"
                  :class="{ 'lp-row-zero': d.pendapatan === 0 }">
                <td class="font-semibold text-sm" style="color:var(--text-primary)">{{ d.bulan }}</td>
                <td class="text-right text-sm">{{ d.pendaftaran }}</td>
                <td class="text-right text-sm">{{ d.transaksi }}</td>
                <td class="text-right money text-sm" :style="{ color: d.pendapatan > 0 ? 'var(--success)' : 'var(--text-muted)' }">
                  {{ d.pendapatan > 0 ? rupiah(d.pendapatan) : '—' }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr style="background:var(--bg-elevated)">
                <td class="font-semibold">TOTAL {{ tahun }}</td>
                <td class="text-right font-bold">{{ tahunan.data.ringkasan.total_pendaftaran }}</td>
                <td class="text-right font-bold">{{ tahunan.data.ringkasan.total_transaksi }}</td>
                <td class="text-right money font-bold" style="color:var(--success)">
                  {{ rupiah(tahunan.data.ringkasan.total_pendapatan) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import { useApi } from '../composables/useApi';
import { useToast } from '../composables/useToast';

const { get } = useApi();
const { error: toastErr } = useToast();

// ── Tabs ──
const tabs = [
  { key: 'harian',  label: 'Laporan Harian',   icon: '' },
  { key: 'bulanan', label: 'Laporan Bulanan',   icon: '' },
  { key: 'tahunan', label: 'Laporan Tahunan',   icon: '' },
];
const activeTab = ref('harian');

function switchTab(t) {
  activeTab.value = t;
  if (t === 'harian'  && !harian.data)  loadHarian();
  if (t === 'bulanan' && !bulanan.data) loadBulanan();
  if (t === 'tahunan' && !tahunan.data) loadTahunan();
}

// ── Date defaults ──
const today     = new Date().toISOString().slice(0, 10);
const thisMonth = new Date().toISOString().slice(0, 7);
const thisYear  = new Date().getFullYear();

const tanggal = ref(today);
const bulan   = ref(thisMonth);
const tahun   = ref(thisYear);
const tahunList = Array.from({ length: 6 }, (_, i) => thisYear - i);

// ── Data stores ──
const harian  = ref({ loading: false, data: null });
const bulanan = ref({ loading: false, data: null });
const tahunan = ref({ loading: false, data: null });

// ── Chart ready flags ──
const bulananChartReady = ref(false);
const tahunanChartReady = ref(false);

// ── Chart theme ──
function chartColors() {
  const dark = document.documentElement.getAttribute('data-theme') !== 'light';
  return {
    tick: dark ? '#94a3b8' : '#64748b',
    grid: dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.07)',
  };
}

const lineOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { color: () => chartColors().grid }, ticks: { color: () => chartColors().tick, font: { size: 10 } } },
    y: { grid: { color: () => chartColors().grid }, ticks: { color: () => chartColors().tick, font: { size: 10 }, callback: v => 'Rp ' + (v / 1000).toFixed(0) + 'K' }, beginAtZero: true },
  },
};

const barOpts = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: 'top', labels: { color: () => chartColors().tick, font: { size: 11 }, boxWidth: 12, padding: 10 } } },
  scales: {
    x: { grid: { display: false }, ticks: { color: () => chartColors().tick, font: { size: 11 } } },
    y: { grid: { color: () => chartColors().grid }, ticks: { color: () => chartColors().tick, font: { size: 11 }, callback: v => 'Rp ' + (v / 1000000).toFixed(1) + 'Jt' }, beginAtZero: true },
  },
};

// ── Bulanan chart data ──
const bulananChartData = computed(() => {
  if (!bulanan.value.data) return {};
  const days = bulanan.value.data.per_hari;
  return {
    labels: days.map(d => d.tanggal.slice(8)), // DD
    datasets: [{
      label: 'Pendapatan',
      data: days.map(d => d.total),
      borderColor: 'hsl(217,91%,60%)',
      backgroundColor: 'rgba(59,130,246,0.12)',
      borderWidth: 2,
      pointRadius: 3,
      pointBackgroundColor: 'hsl(217,91%,60%)',
      fill: true,
      tension: 0.4,
    }],
  };
});

// ── Tahunan chart data ──
const tahunanChartData = computed(() => {
  if (!tahunan.value.data) return {};
  const d = tahunan.value.data.data;
  return {
    labels: d.map(x => x.bulan),
    datasets: [
      {
        label: 'Pendapatan',
        data: d.map(x => x.pendapatan),
        backgroundColor: 'rgba(59,130,246,0.75)',
        borderRadius: 5,
        yAxisID: 'y',
      },
      {
        label: 'Pendaftaran',
        data: d.map(x => x.pendaftaran),
        backgroundColor: 'rgba(16,185,129,0.6)',
        borderRadius: 5,
        yAxisID: 'y2',
      },
    ],
  };
});

const tahunanBarOpts = {
  ...barOpts,
  scales: {
    ...barOpts.scales,
    y2: { position: 'right', grid: { display: false }, ticks: { color: () => chartColors().tick, font: { size: 11 } }, beginAtZero: true },
  },
};

// ── API loaders ──
async function loadHarian() {
  harian.value.loading = true;
  const res = await get('/api/laporan/harian', { tanggal: tanggal.value });
  harian.value.loading = false;
  if (res.success) harian.value.data = res.data;
  else toastErr('Gagal memuat laporan harian.');
}

async function loadBulanan() {
  bulanan.value.loading = true;
  bulananChartReady.value = false;
  const res = await get('/api/laporan/bulanan', { bulan: bulan.value });
  bulanan.value.loading = false;
  if (res.success) {
    bulanan.value.data = res.data;
    await nextTick();
    bulananChartReady.value = true;
  } else toastErr('Gagal memuat laporan bulanan.');
}

async function loadTahunan() {
  tahunan.value.loading = true;
  tahunanChartReady.value = false;
  const res = await get('/api/laporan/tahunan', { tahun: tahun.value });
  tahunan.value.loading = false;
  if (res.success) {
    tahunan.value.data = res.data;
    await nextTick();
    tahunanChartReady.value = true;
  } else toastErr('Gagal memuat laporan tahunan.');
}

// ── Formatters ──
function rupiah(v) {
  return 'Rp ' + (v ?? 0).toLocaleString('id-ID');
}
function formatDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}
function formatMonth(m) {
  const [y, mo] = m.split('-');
  const names = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  return names[parseInt(mo) - 1] + ' ' + y;
}
function ucFirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

// ── Cards config ──
const harianCards = [
  { key: 'total_pendapatan', label: 'Total Pendapatan',   icon: '', bg: 'var(--success-bg)',  money: true },
  { key: 'total_transaksi',  label: 'Transaksi Bayar',    icon: '', bg: 'var(--primary-bg)',  money: false },
  { key: 'total_pendaftaran',label: 'Total Pendaftaran',  icon: '', bg: 'var(--primary-bg)',  money: false },
  { key: 'pasien_selesai',   label: 'Pasien Selesai',     icon: '', bg: 'var(--success-bg)',  money: false },
  { key: 'pasien_menunggu',  label: 'Masih Menunggu',     icon: '', bg: 'var(--warning-bg)',  money: false },
  { key: 'rata_tagihan',     label: 'Rata-rata Tagihan',  icon: '', bg: 'var(--primary-bg)',  money: true },
];
const bulananCards = [
  { key: 'total_pendapatan',  label: 'Total Pendapatan',  icon: '', bg: 'var(--success-bg)', money: true },
  { key: 'total_transaksi',   label: 'Total Transaksi',   icon: '', bg: 'var(--primary-bg)', money: false },
  { key: 'total_pendaftaran', label: 'Total Pendaftaran', icon: '', bg: 'var(--primary-bg)', money: false },
  { key: 'rata_per_hari',     label: 'Rata-rata/Hari',    icon: '', bg: 'var(--success-bg)', money: true },
];
const tahunanCards = [
  { key: 'total_pendapatan',  label: 'Total Pendapatan',  icon: '', bg: 'var(--success-bg)', money: true },
  { key: 'total_transaksi',   label: 'Total Transaksi',   icon: '', bg: 'var(--primary-bg)', money: false },
  { key: 'total_pendaftaran', label: 'Total Pendaftaran', icon: '', bg: 'var(--primary-bg)', money: false },
  { key: 'total_pasien_baru', label: 'Pasien Baru',       icon: '', bg: 'var(--primary-bg)', money: false },
  { key: 'growth',            label: 'Growth vs Tahun Lalu', icon: '📈', bg: 'var(--primary-bg)', growth: true },
];

const growthColor = computed(() => {
  const g = tahunan.value.data?.ringkasan?.growth;
  if (g === null || g === undefined) return 'var(--text-muted)';
  return g >= 0 ? 'var(--success)' : 'var(--danger)';
});

// ── Export helpers ──
function downloadCSV(rows, filename) {
  const csv = rows.map(r => r.map(v => `"${v}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  a.click();
  URL.revokeObjectURL(a.href);
}

function exportHarianCSV() {
  if (!harian.value.data) return;
  const rows = [
    ['No. Kwitansi', 'Pasien', 'Poli', 'Dokter', 'Metode', 'Total Tagihan', 'Kembalian', 'Kasir'],
    ...harian.value.data.pembayaran.map(p => [
      p.no_kwitansi,
      p.pendaftaran?.pasien?.nama_pasien ?? '',
      p.pendaftaran?.jadwal_dokter?.poli?.nama_poli ?? '',
      p.pendaftaran?.jadwal_dokter?.dokter?.nama_dokter ?? '',
      ucFirst(p.metode_bayar),
      p.total_tagihan,
      p.kembalian,
      p.kasir?.name ?? '',
    ]),
  ];
  downloadCSV(rows, `laporan-harian-${tanggal.value}.csv`);
}

function exportBulananCSV() {
  if (!bulanan.value.data) return;
  const rows = [
    ['Tanggal', 'Jumlah Transaksi', 'Total Pendapatan'],
    ...bulanan.value.data.per_hari.map(h => [h.tanggal, h.jumlah, h.total]),
  ];
  downloadCSV(rows, `laporan-bulanan-${bulan.value}.csv`);
}

function exportTahunanCSV() {
  if (!tahunan.value.data) return;
  const rows = [
    ['Bulan', 'Pendaftaran', 'Transaksi', 'Pendapatan'],
    ...tahunan.value.data.data.map(d => [d.bulan, d.pendaftaran, d.transaksi, d.pendapatan]),
  ];
  downloadCSV(rows, `laporan-tahunan-${tahun.value}.csv`);
}

function printPage() { window.print(); }

onMounted(loadHarian);
</script>

<style scoped>
/* ── Tabs ── */
.lp-tabs {
  display: flex;
  gap: 0.25rem;
  background: var(--bg-elevated);
  padding: 0.3rem;
  border-radius: var(--radius-lg);
  width: fit-content;
  flex-wrap: wrap;
}
.lp-tab {
  padding: 0.5rem 1rem;
  border-radius: calc(var(--radius-lg) - 3px);
  border: none;
  background: transparent;
  color: var(--text-muted);
  font-size: 0.83rem;
  font-weight: 600;
  font-family: var(--font-base);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all var(--transition);
  white-space: nowrap;
}
.lp-tab:hover { color: var(--text-primary); }
.lp-tab.active {
  background: var(--bg-surface);
  color: var(--primary);
  box-shadow: 0 1px 4px rgba(0,0,0,0.15);
}

/* ── Summary Cards ── */
.lp-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 0.75rem;
}
.lp-summary-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 0.875rem;
}
.lp-sc-icon {
  width: 42px; height: 42px;
  border-radius: var(--radius-md);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}
.lp-sc-value {
  font-family: var(--font-heading);
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.1;
}
.lp-sc-label {
  font-size: 0.7rem;
  color: var(--text-muted);
  font-weight: 500;
  margin-top: 0.15rem;
}

/* ── Two columns ── */
.lp-two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
@media (max-width: 860px) { .lp-two-col { grid-template-columns: 1fr; } }

/* ── Bar rows ── */
.lp-bar-row { margin-bottom: 1rem; }
.lp-bar-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.35rem;
}
.lp-bar-label { font-size: 0.82rem; font-weight: 600; color: var(--text-secondary); }
.lp-bar-track {
  height: 7px;
  background: var(--bg-elevated);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.25rem;
}
.lp-bar-fill {
  height: 100%;
  background: var(--success);
  border-radius: 4px;
  transition: width 0.6s ease;
}
.lp-bar-value { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; }

/* ── Rank rows ── */
.lp-rank-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0;
  border-bottom: 1px solid var(--border);
}
.lp-rank-row:last-child { border-bottom: none; }
.lp-rank-num {
  width: 26px; height: 26px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.78rem; font-weight: 800;
  background: var(--bg-elevated);
  color: var(--text-muted);
  flex-shrink: 0;
}
.lp-rank-1 { background: hsl(45,95%,60%); color: hsl(30,50%,20%); }
.lp-rank-2 { background: hsl(210,15%,75%); color: hsl(210,25%,20%); }
.lp-rank-3 { background: hsl(25,60%,55%); color: white; }

/* ── Misc ── */
.lp-row-zero td { opacity: 0.4; }
.lp-hide-sm {}
.font-mono { font-family: 'Courier New', monospace; }

@media (max-width: 640px) {
  .lp-hide-sm { display: none; }
  .lp-tabs { width: 100%; }
  .lp-tab { flex: 1; justify-content: center; }
}

@media print {
  .lp-tabs, button, .btn { display: none !important; }
}
</style>
