<template>
  <div>
    <!-- Filter Bar -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
      <div class="flex items-center gap-3 flex-wrap">
        <input type="date" v-model="filterTanggal" @change="fetchData" class="form-control" style="width:auto" />
        <select class="form-select" style="width:auto" v-model="filterMetode" @change="fetchData">
          <option value="">Semua Metode</option>
          <option value="tunai">Tunai</option>
          <option value="transfer">Transfer</option>
          <option value="bpjs">BPJS</option>
          <option value="asuransi">Asuransi</option>
        </select>
        <div class="search-bar">
          <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input v-model="search" @input="debouncedFetch" placeholder="Cari no. kwitansi, nama pasien..." />
        </div>
      </div>
      <button class="btn btn-success" @click="openCreate">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Input Pembayaran
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-2 gap-4 mb-4">
      <div class="card" style="background:linear-gradient(135deg,var(--bg-surface),var(--bg-elevated))">
        <div class="card-body flex items-center gap-4">
          <div style="width:48px;height:48px;background:var(--success-bg);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;font-size:1.5rem"></div>
          <div>
            <div class="text-muted text-sm">Total Pendapatan Hari Ini</div>
            <div class="money text-2xl" style="color:var(--success)">{{ formatRp(summary.total_pendapatan) }}</div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body flex items-center gap-4">
          <div style="width:48px;height:48px;background:var(--primary-bg);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;font-size:1.5rem"></div>
          <div>
            <div class="text-muted text-sm">Jumlah Transaksi Hari Ini</div>
            <div class="font-extrabold text-2xl">{{ summary.total_transaksi }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Riwayat Pembayaran - {{ formatTanggal(filterTanggal) }}</div>
      </div>
      <div style="position:relative">
        <div v-if="loading" class="loading-overlay"><div class="spinner spinner-lg"></div></div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr><th>No. Kwitansi</th><th>Pasien</th><th>Dokter</th><th>Total Tagihan</th><th>Bayar</th><th>Metode</th><th>Kasir</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-if="!loading && pembayaran.length === 0">
                <td colspan="8"><div class="empty-state"><div class="empty-state-icon"></div><div class="empty-state-title">Belum ada transaksi</div></div></td>
              </tr>
              <tr v-for="p in pembayaran" :key="p.id">
                <td><span class="badge badge-secondary" style="font-family:monospace;font-size:0.75rem">{{ p.no_kwitansi }}</span></td>
                <td>
                  <div class="font-semibold" style="color:var(--text-primary)">{{ p.pendaftaran?.pasien?.nama_pasien }}</div>
                  <div class="text-xs text-muted">{{ p.pendaftaran?.pasien?.no_rm }}</div>
                </td>
                <td class="text-sm">{{ p.pendaftaran?.jadwal_dokter?.dokter?.nama_dokter }}</td>
                <td class="money" style="color:var(--text-primary)">{{ formatRp(p.total_tagihan) }}</td>
                <td class="money text-success">{{ formatRp(p.jumlah_bayar) }}</td>
                <td>
                  <span class="badge" :class="metodeBadge(p.metode_bayar)">{{ p.metode_bayar }}</span>
                </td>
                <td class="text-sm text-muted">{{ p.kasir?.name }}</td>
                <td>
                  <button class="btn btn-outline btn-sm" @click="lihatKwitansi(p)"> Kwitansi</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="card-footer flex items-center justify-between" v-if="pagination.last_page > 1">
          <div class="text-sm text-muted">Hal. {{ pagination.current_page }} / {{ pagination.last_page }}</div>
          <div class="pagination">
            <button class="page-btn" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">‹</button>
            <button v-for="pg in visiblePages" :key="pg" class="page-btn" :class="{ active: pg === pagination.current_page }" @click="goPage(pg)">{{ pg }}</button>
            <button class="page-btn" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">›</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================== MODAL INPUT PEMBAYARAN ===================== -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="modal modal-xl">
        <div class="modal-header">
          <div class="modal-title">💳 Input Pembayaran</div>
          <button class="btn btn-ghost btn-icon" @click="showCreate = false">✕</button>
        </div>
        <div class="modal-body">
          <!-- Cari Pendaftaran -->
          <div class="form-group">
            <label class="form-label">Cari Pasien Selesai Periksa (Hari Ini) <span class="required">*</span></label>
            <input v-model="cariPasienQuery" @input="cariPasienSelesai" class="form-control" placeholder="Ketik nama atau no. antrian pasien yang sudah selesai periksa..." />
            <div v-if="hasilCariPasien.length > 0" class="bg-elevated border rounded-lg mt-2" style="max-height:200px;overflow-y:auto">
              <div v-for="p in hasilCariPasien" :key="p.id" class="flex items-center gap-3 p-3 cursor-pointer" style="border-bottom:1px solid var(--border)" @click="pilihPendaftaran(p)" :style="selectedPendaftaran?.id === p.id ? 'background:var(--primary-bg)' : ''">
                <div>
                  <div class="font-semibold text-sm">{{ p.pasien?.nama_pasien }}</div>
                  <div class="text-xs text-muted">{{ p.no_antrian }} • {{ p.jadwal_dokter?.dokter?.nama_dokter }} • {{ p.jadwal_dokter?.poli?.nama_poli }}</div>
                </div>
                <span class="badge badge-success ml-auto">Selesai</span>
              </div>
            </div>
          </div>

          <!-- Detail setelah pilih pasien -->
          <div v-if="selectedPendaftaran">
            <div class="alert alert-info mb-4">
              <div>
                <div class="font-semibold">{{ selectedPendaftaran.pasien?.nama_pasien }}</div>
                <div class="text-xs">{{ selectedPendaftaran.no_antrian }} • Dokter: {{ selectedPendaftaran.jadwal_dokter?.dokter?.nama_dokter }} • Poli: {{ selectedPendaftaran.jadwal_dokter?.poli?.nama_poli }}</div>
              </div>
            </div>

            <!-- Rincian Biaya -->
            <div class="card mb-4" style="background:var(--bg-elevated)">
              <div class="card-body">
                <div class="font-semibold mb-3"> Rincian Biaya</div>
                <div class="form-grid">
                  <div class="form-group">
                    <label class="form-label">Biaya Konsultasi <span class="required">*</span></label>
                    <input type="number" v-model="payForm.biaya_konsultasi" class="form-control" min="0" @input="hitungTotal" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Biaya Tindakan</label>
                    <input type="number" v-model="payForm.biaya_tindakan" class="form-control" min="0" @input="hitungTotal" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Biaya Obat</label>
                    <input type="number" v-model="payForm.biaya_obat" class="form-control" min="0" @input="hitungTotal" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Biaya Administrasi</label>
                    <input type="number" v-model="payForm.biaya_admin" class="form-control" min="0" @input="hitungTotal" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Diskon</label>
                    <input type="number" v-model="payForm.diskon" class="form-control" min="0" @input="hitungTotal" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Metode Pembayaran <span class="required">*</span></label>
                    <select v-model="payForm.metode_bayar" class="form-select">
                      <option value="tunai">Tunai</option>
                      <option value="transfer">Transfer Bank</option>
                      <option value="bpjs">BPJS</option>
                      <option value="asuransi">Asuransi</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Box Total -->
            <div class="card mb-4" style="border:2px solid var(--primary);background:var(--primary-bg)">
              <div class="card-body">
                <div class="flex items-center justify-between mb-3">
                  <div class="font-semibold text-lg">TOTAL TAGIHAN</div>
                  <div class="money text-2xl" style="color:var(--primary)">{{ formatRp(totalTagihan) }}</div>
                </div>
                <div class="form-group mb-2">
                  <label class="form-label">Jumlah Dibayar <span class="required">*</span></label>
                  <input type="number" v-model="payForm.jumlah_bayar" class="form-control" :class="{ 'is-invalid': jumlahKurang }" :min="totalTagihan" @input="hitungKembalian" style="font-size:1.1rem;font-weight:700" />
                  <div v-if="jumlahKurang" class="invalid-feedback">⚠️ Jumlah bayar ({{ formatRp(payForm.jumlah_bayar) }}) tidak boleh kurang dari total tagihan ({{ formatRp(totalTagihan) }})</div>
                </div>
                <div v-if="!jumlahKurang && payForm.jumlah_bayar > 0" class="flex items-center justify-between">
                  <div class="font-semibold">Kembalian</div>
                  <div class="money font-extrabold text-xl text-success">{{ formatRp(kembalian) }}</div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Catatan</label>
              <input v-model="payForm.catatan" class="form-control" placeholder="Catatan pembayaran (opsional)..." />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showCreate = false">Batal</button>
          <button class="btn btn-success" @click="simpanPembayaran" :disabled="saving || !selectedPendaftaran || jumlahKurang">
            <div class="spinner spinner-sm" v-if="saving"></div>
            ✅ Proses Pembayaran
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Kwitansi -->
    <div v-if="showKwitansi && kwitansiData" class="modal-overlay" @click.self="showKwitansi = false">
      <div class="modal" style="max-width:460px">
        <div class="modal-header">
          <div class="modal-title">🧾 Kwitansi Pembayaran</div>
          <button class="btn btn-ghost btn-icon" @click="showKwitansi = false">✕</button>
        </div>
        <div class="modal-body">
          <div style="background:var(--bg-elevated);border-radius:var(--radius-lg);padding:1.5rem">
            <div class="text-center mb-4">
              <div class="font-extrabold text-lg">RS LNG BADAK</div>
              <div class="text-muted text-sm">Sistem Informasi Manajemen Rumah Sakit</div>
              <hr class="divider" />
            </div>
            <div class="flex justify-between mb-2">
              <span class="text-muted text-sm">No. Kwitansi</span>
              <span class="font-bold" style="font-family:monospace">{{ kwitansiData.no_kwitansi }}</span>
            </div>
            <div class="flex justify-between mb-2">
              <span class="text-muted text-sm">Tanggal</span>
              <span>{{ new Date(kwitansiData.created_at).toLocaleDateString('id-ID') }}</span>
            </div>
            <div class="flex justify-between mb-2">
              <span class="text-muted text-sm">Pasien</span>
              <span class="font-semibold">{{ kwitansiData.pendaftaran?.pasien?.nama_pasien }}</span>
            </div>
            <div class="flex justify-between mb-2">
              <span class="text-muted text-sm">Dokter</span>
              <span>{{ kwitansiData.pendaftaran?.jadwal_dokter?.dokter?.nama_dokter }}</span>
            </div>
            <hr class="divider" />
            <div class="flex justify-between mb-1"><span class="text-muted text-sm">Biaya Konsultasi</span><span>{{ formatRp(kwitansiData.biaya_konsultasi) }}</span></div>
            <div class="flex justify-between mb-1" v-if="kwitansiData.biaya_tindakan > 0"><span class="text-muted text-sm">Biaya Tindakan</span><span>{{ formatRp(kwitansiData.biaya_tindakan) }}</span></div>
            <div class="flex justify-between mb-1" v-if="kwitansiData.biaya_obat > 0"><span class="text-muted text-sm">Biaya Obat</span><span>{{ formatRp(kwitansiData.biaya_obat) }}</span></div>
            <div class="flex justify-between mb-1" v-if="kwitansiData.biaya_admin > 0"><span class="text-muted text-sm">Biaya Administrasi</span><span>{{ formatRp(kwitansiData.biaya_admin) }}</span></div>
            <div class="flex justify-between mb-1 text-success" v-if="kwitansiData.diskon > 0"><span class="text-sm">Diskon</span><span>- {{ formatRp(kwitansiData.diskon) }}</span></div>
            <hr class="divider" />
            <div class="flex justify-between font-extrabold text-lg mb-1">
              <span>TOTAL</span><span class="money" style="color:var(--primary)">{{ formatRp(kwitansiData.total_tagihan) }}</span>
            </div>
            <div class="flex justify-between mb-1"><span class="text-muted text-sm">Dibayar</span><span class="money">{{ formatRp(kwitansiData.jumlah_bayar) }}</span></div>
            <div class="flex justify-between font-bold text-success">
              <span>Kembalian</span><span class="money">{{ formatRp(kwitansiData.kembalian) }}</span>
            </div>
            <hr class="divider" />
            <div class="text-center text-muted text-xs">Kasir: {{ kwitansiData.kasir?.name }} • {{ kwitansiData.metode_bayar?.toUpperCase() }}</div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showKwitansi = false">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useApi } from '../composables/useApi';
import { useToast } from '../composables/useToast';

const { get, post, loading } = useApi();
const { success: toastOk, error: toastErr } = useToast();

const pembayaran    = ref([]);
const pagination    = ref({});
const summary       = ref({ total_pendapatan: 0, total_transaksi: 0 });
const today         = new Date().toISOString().split('T')[0];
const filterTanggal = ref(today);
const filterMetode  = ref('');
const search        = ref('');
const currentPage   = ref(1);
const saving        = ref(false);

const showCreate   = ref(false);
const showKwitansi = ref(false);
const kwitansiData = ref(null);

// Create state
const cariPasienQuery    = ref('');
const hasilCariPasien    = ref([]);
const selectedPendaftaran = ref(null);
const payForm = ref({ pendaftaran_id: null, biaya_konsultasi: 0, biaya_tindakan: 0, biaya_obat: 0, biaya_admin: 5000, diskon: 0, jumlah_bayar: 0, metode_bayar: 'tunai', catatan: '' });

const formatRp     = (v) => 'Rp ' + Number(v ?? 0).toLocaleString('id-ID');
const formatTanggal = (d) => d ? new Date(d).toLocaleDateString('id-ID', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) : '-';
const metodeBadge  = (m) => ({ tunai:'badge-success', transfer:'badge-info', bpjs:'badge-primary', asuransi:'badge-warning' }[m] ?? 'badge-secondary');

const totalTagihan = computed(() => {
  return (Number(payForm.value.biaya_konsultasi) + Number(payForm.value.biaya_tindakan) + Number(payForm.value.biaya_obat) + Number(payForm.value.biaya_admin)) - Number(payForm.value.diskon);
});

const kembalian = computed(() => Number(payForm.value.jumlah_bayar) - totalTagihan.value);
const jumlahKurang = computed(() => payForm.value.jumlah_bayar > 0 && Number(payForm.value.jumlah_bayar) < totalTagihan.value);

const visiblePages = computed(() => {
  const total = pagination.value.last_page ?? 1, cur = pagination.value.current_page ?? 1, pages = [];
  for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) pages.push(i);
  return pages;
});

function hitungTotal() { payForm.value.jumlah_bayar = totalTagihan.value; }
function hitungKembalian() { /* computed handles it */ }

let debounceTimer;
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { currentPage.value = 1; fetchData(); }, 400); }

async function fetchData() {
  const res = await get('/api/pembayaran', { tanggal: filterTanggal.value, metode_bayar: filterMetode.value, search: search.value, page: currentPage.value });
  if (res.success) {
    pembayaran.value = res.data.data?.data ?? [];
    pagination.value = res.data.data ?? {};
    summary.value    = res.data.summary ?? { total_pendapatan: 0, total_transaksi: 0 };
  }
}

function goPage(p) { currentPage.value = p; fetchData(); }

function openCreate() {
  cariPasienQuery.value = ''; hasilCariPasien.value = []; selectedPendaftaran.value = null;
  payForm.value = { pendaftaran_id: null, biaya_konsultasi: 0, biaya_tindakan: 0, biaya_obat: 0, biaya_admin: 5000, diskon: 0, jumlah_bayar: 0, metode_bayar: 'tunai', catatan: '' };
  showCreate.value = true;
}

let cariTimer;
function cariPasienSelesai() {
  clearTimeout(cariTimer);
  if (cariPasienQuery.value.length < 2) { hasilCariPasien.value = []; return; }
  cariTimer = setTimeout(async () => {
    const res = await get('/api/pendaftaran', { tanggal: today, status: 'selesai', search: cariPasienQuery.value });
    if (res.success) hasilCariPasien.value = res.data.data ?? [];
  }, 300);
}

function pilihPendaftaran(p) {
  selectedPendaftaran.value = p;
  hasilCariPasien.value = [];
  cariPasienQuery.value = p.pasien?.nama_pasien;
  payForm.value.pendaftaran_id = p.id;
  payForm.value.biaya_konsultasi = p.jadwal_dokter?.dokter?.biaya_konsultasi ?? 0;
  hitungTotal();
}

async function simpanPembayaran() {
  if (jumlahKurang.value) { toastErr('Jumlah bayar tidak boleh kurang dari total tagihan!'); return; }
  saving.value = true;
  const res = await post('/api/pembayaran', payForm.value);
  saving.value = false;
  if (res.success) {
    toastOk(`✅ ${res.data.message} | Kembalian: ${formatRp(res.data.kembalian)}`);
    showCreate.value = false;
    fetchData();
  } else {
    toastErr(res.error);
  }
}

async function lihatKwitansi(p) {
  const res = await get(`/api/pembayaran/${p.id}`);
  if (res.success) { kwitansiData.value = res.data; showKwitansi.value = true; }
}

onMounted(fetchData);
</script>
