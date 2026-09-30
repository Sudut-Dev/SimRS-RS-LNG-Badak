<template>
  <div>
    <!-- Filter Bar -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
      <div class="flex items-center gap-3 flex-wrap">
        <input type="date" v-model="filterTanggal" @change="fetchData" class="form-control" style="width:auto" />
        <select class="form-select" style="width:auto" v-model="filterStatus" @change="fetchData">
          <option value="">Semua Status</option>
          <option value="menunggu">Menunggu</option>
          <option value="dipanggil">Dipanggil</option>
          <option value="selesai">Selesai</option>
          <option value="batal">Batal</option>
        </select>
        <div class="search-bar">
          <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input v-model="search" @input="debouncedFetch" placeholder="Cari pasien, no. antrian..." />
        </div>
      </div>
      <button class="btn btn-primary" @click="openCreate">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Daftarkan Pasien
      </button>
    </div>

    <!-- Summary Badges -->
    <div class="flex gap-3 mb-4 flex-wrap">
      <div class="badge badge-warning" style="padding:0.4rem 0.8rem;font-size:0.8rem">⏳ Menunggu: {{ summaryCount('menunggu') }}</div>
      <div class="badge badge-primary" style="padding:0.4rem 0.8rem;font-size:0.8rem">🔔 Dipanggil: {{ summaryCount('dipanggil') }}</div>
      <div class="badge badge-success" style="padding:0.4rem 0.8rem;font-size:0.8rem">✅ Selesai: {{ summaryCount('selesai') }}</div>
      <div class="badge badge-secondary" style="padding:0.4rem 0.8rem;font-size:0.8rem">❌ Batal: {{ summaryCount('batal') }}</div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">Antrian Rawat Jalan - {{ formatTanggal(filterTanggal) }}</div>
        <div class="text-sm text-muted">{{ pagination.total ?? 0 }} pendaftaran</div>
      </div>
      <div style="position:relative">
        <div v-if="loading" class="loading-overlay"><div class="spinner spinner-lg"></div></div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr><th>#</th><th>No. Antrian</th><th>Pasien</th><th>Dokter / Poli</th><th>Keluhan</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-if="!loading && pendaftaran.length === 0">
                <td colspan="7"><div class="empty-state"><div class="empty-state-icon">📋</div><div class="empty-state-title">Belum ada pendaftaran</div></div></td>
              </tr>
              <tr v-for="p in pendaftaran" :key="p.id">
                <td class="font-bold" style="color:var(--text-muted)">{{ p.no_urut }}</td>
                <td><span class="badge badge-secondary" style="font-family:monospace;font-size:0.75rem">{{ p.no_antrian }}</span></td>
                <td>
                  <div class="font-semibold" style="color:var(--text-primary)">{{ p.pasien?.nama_pasien }}</div>
                  <div class="text-xs text-muted">{{ p.pasien?.no_rm }} • {{ p.pasien?.jenis_pembayaran?.toUpperCase() }}</div>
                </td>
                <td>
                  <div class="text-sm">{{ p.jadwal_dokter?.dokter?.nama_dokter }}</div>
                  <div class="text-xs text-muted">{{ p.jadwal_dokter?.poli?.nama_poli }}</div>
                </td>
                <td style="max-width:180px">
                  <div class="truncate text-sm">{{ p.keluhan || '-' }}</div>
                </td>
                <td>
                  <span class="badge" :class="badgeStatus(p.status)">
                    <span class="status-dot" :class="p.status"></span>{{ p.label_status ?? p.status }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <!-- Panggil -->
                    <button v-if="p.status === 'menunggu'" class="btn btn-primary btn-sm" @click="updateStatus(p, 'dipanggil')" title="Panggil Pasien">🔔 Panggil</button>
                    <!-- Selesai -->
                    <button v-if="p.status === 'dipanggil'" class="btn btn-success btn-sm" @click="openPemeriksaan(p)" title="Input Pemeriksaan">📋 Periksa</button>
                    <!-- Batal -->
                    <button v-if="['menunggu'].includes(p.status)" class="btn btn-outline btn-sm" @click="confirmBatal(p)" style="color:var(--danger)" title="Batalkan">❌</button>
                    <!-- Lihat detail -->
                    <button class="btn btn-ghost btn-sm" @click="openDetail(p)" title="Detail">👁️</button>
                  </div>
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

    <!-- ===================== MODAL DAFTAR BARU ===================== -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <div class="modal-title">➕ Daftarkan Pasien Rawat Jalan</div>
          <button class="btn btn-ghost btn-icon" @click="showCreate = false">✕</button>
        </div>
        <div class="modal-body">
          <!-- Step 1: Cari Pasien -->
          <div class="form-group">
            <label class="form-label">Cari Pasien (NIK / Nama / No. RM) <span class="required">*</span></label>
            <div class="flex gap-2">
              <input v-model="cariQuery" class="form-control" :class="{ 'is-invalid': createErrors.pasien_id }" placeholder="Ketik minimal 3 karakter..." @input="cariPasien" />
            </div>
            <div class="invalid-feedback">{{ createErrors.pasien_id?.[0] }}</div>
            <!-- Hasil Cari -->
            <div v-if="hasilCari.length > 0" class="bg-elevated border rounded-lg mt-2" style="max-height:180px;overflow-y:auto">
              <div v-for="ps in hasilCari" :key="ps.id" class="flex items-center gap-3 p-3 cursor-pointer" style="border-bottom:1px solid var(--border);transition:background 0.15s" @click="pilihPasien(ps)" :style="selectedPasien?.id === ps.id ? 'background:var(--primary-bg)' : ''">
                <div>
                  <div class="font-semibold text-sm" style="color:var(--text-primary)">{{ ps.nama_pasien }}</div>
                  <div class="text-xs text-muted">NIK: {{ ps.nik }} • {{ ps.no_rm }} • {{ ps.umur }}</div>
                </div>
                <span class="badge ml-auto" :class="{ 'badge-success': ps.jenis_pembayaran === 'bpjs', 'badge-secondary': ps.jenis_pembayaran === 'umum' }">{{ ps.jenis_pembayaran.toUpperCase() }}</span>
              </div>
            </div>
            <!-- Selected Pasien -->
            <div v-if="selectedPasien" class="alert alert-info mt-2 flex items-center gap-3">
              <span>✅</span>
              <div>
                <div class="font-semibold">{{ selectedPasien.nama_pasien }}</div>
                <div class="text-xs">{{ selectedPasien.no_rm }} • {{ selectedPasien.umur }} • {{ selectedPasien.jenis_pembayaran.toUpperCase() }}</div>
              </div>
              <button class="btn btn-ghost btn-sm ml-auto" @click="selectedPasien = null; cariQuery = ''">✕</button>
            </div>
          </div>

          <!-- Step 2: Pilih Tanggal & Jadwal -->
          <div class="form-group">
            <label class="form-label">Tanggal Periksa <span class="required">*</span></label>
            <input type="date" v-model="createForm.tanggal_periksa" class="form-control" :class="{ 'is-invalid': createErrors.tanggal_periksa }" :min="today" @change="fetchJadwalTersedia" />
            <div class="invalid-feedback">{{ createErrors.tanggal_periksa?.[0] }}</div>
          </div>

          <div v-if="jadwalTersedia.length > 0" class="form-group">
            <label class="form-label">Pilih Jadwal Dokter <span class="required">*</span></label>
            <div class="grid" style="grid-template-columns:repeat(2,1fr);gap:0.5rem">
              <div v-for="j in jadwalTersedia" :key="j.id"
                class="border rounded-lg p-3 cursor-pointer"
                :style="createForm.jadwal_dokter_id === j.id ? 'border-color:var(--primary);background:var(--primary-bg)' : j.kuota_penuh ? 'opacity:0.5;cursor:not-allowed' : 'border-color:var(--border)'"
                @click="!j.kuota_penuh && (createForm.jadwal_dokter_id = j.id)">
                <div class="font-semibold text-sm" style="color:var(--text-primary)">{{ j.dokter?.nama_dokter }}</div>
                <div class="text-xs text-muted">{{ j.poli?.nama_poli }} • {{ j.jam_mulai }}-{{ j.jam_selesai }}</div>
                <div class="mt-2 flex items-center justify-between">
                  <span class="text-xs font-bold" :class="j.kuota_penuh ? 'text-danger' : 'text-success'">
                    {{ j.kuota_penuh ? '❌ Penuh' : `✅ Sisa ${j.sisa_kuota} kuota` }}
                  </span>
                  <span class="money text-xs">{{ formatRp(j.dokter?.biaya_konsultasi) }}</span>
                </div>
              </div>
            </div>
            <div v-if="createErrors.jadwal_dokter_id" class="invalid-feedback d-block">{{ createErrors.jadwal_dokter_id?.[0] }}</div>
          </div>
          <div v-else-if="createForm.tanggal_periksa && !loadingJadwal" class="alert alert-warning">Tidak ada jadwal dokter tersedia untuk tanggal ini.</div>
          <div v-if="loadingJadwal" class="flex items-center gap-2 text-muted text-sm"><div class="spinner spinner-sm"></div>Memuat jadwal...</div>

          <div class="form-group">
            <label class="form-label">Keluhan Utama</label>
            <textarea v-model="createForm.keluhan" class="form-control" rows="2" placeholder="Deskripsikan keluhan pasien..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showCreate = false">Batal</button>
          <button class="btn btn-primary" @click="daftarkanPasien" :disabled="saving || !selectedPasien || !createForm.jadwal_dokter_id">
            <div class="spinner spinner-sm" v-if="saving"></div>
            Daftarkan & Ambil Antrian
          </button>
        </div>
      </div>
    </div>

    <!-- ===================== MODAL PEMERIKSAAN ===================== -->
    <div v-if="showPemeriksaan" class="modal-overlay" @click.self="showPemeriksaan = false">
      <div class="modal modal-xl">
        <div class="modal-header">
          <div class="modal-title">📋 Input Hasil Pemeriksaan</div>
          <button class="btn btn-ghost btn-icon" @click="showPemeriksaan = false">✕</button>
        </div>
        <div class="modal-body">
          <!-- Info Pasien -->
          <div class="alert alert-info mb-4">
            <div>
              <div class="font-semibold">{{ activePendaftaran?.pasien?.nama_pasien }}</div>
              <div class="text-xs">{{ activePendaftaran?.pasien?.no_rm }} • Dokter: {{ activePendaftaran?.jadwal_dokter?.dokter?.nama_dokter }}</div>
            </div>
          </div>

          <div class="form-grid-3">
            <!-- Vital Signs -->
            <div class="form-group">
              <label class="form-label">Tekanan Darah</label>
              <input v-model="pemeriksaanForm.tekanan_darah" class="form-control" placeholder="cth: 120/80" />
            </div>
            <div class="form-group">
              <label class="form-label">Suhu Tubuh (°C)</label>
              <input type="number" v-model="pemeriksaanForm.suhu_tubuh" class="form-control" :class="{ 'is-invalid': pemErrors.suhu_tubuh }" step="0.1" min="30" max="45" placeholder="36.5" />
              <div class="invalid-feedback">{{ pemErrors.suhu_tubuh?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Nadi (per menit)</label>
              <input type="number" v-model="pemeriksaanForm.nadi" class="form-control" min="30" max="250" placeholder="80" />
            </div>
            <div class="form-group">
              <label class="form-label">Berat Badan (kg)</label>
              <input type="number" v-model="pemeriksaanForm.berat_badan" class="form-control" :class="{ 'is-invalid': pemErrors.berat_badan }" step="0.1" min="1" max="300" placeholder="60" />
              <div class="invalid-feedback">{{ pemErrors.berat_badan?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Tinggi Badan (cm)</label>
              <input type="number" v-model="pemeriksaanForm.tinggi_badan" class="form-control" step="0.1" min="30" max="250" placeholder="165" />
            </div>
            <div class="form-group">
              <label class="form-label">IMT (Otomatis)</label>
              <div class="form-control bg-card" style="cursor:default">
                <span v-if="imtValue">{{ imtValue }} <span class="text-xs text-muted">({{ imtKategori }})</span></span>
                <span v-else class="text-muted">-</span>
              </div>
            </div>
            <div class="form-group col-span-3">
              <label class="form-label">Diagnosa <span class="required">*</span></label>
              <textarea v-model="pemeriksaanForm.diagnosa" class="form-control" :class="{ 'is-invalid': pemErrors.diagnosa }" rows="2" placeholder="Diagnosa dokter..."></textarea>
              <div class="invalid-feedback">{{ pemErrors.diagnosa?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Kode ICD-10</label>
              <input v-model="pemeriksaanForm.kode_icd" class="form-control" placeholder="cth: J06.9" maxlength="10" />
            </div>
            <div class="form-group col-span-2">
              <label class="form-label">Tindakan Medis</label>
              <textarea v-model="pemeriksaanForm.tindakan" class="form-control" rows="2" placeholder="Tindakan yang dilakukan..."></textarea>
            </div>
            <div class="form-group col-span-3">
              <label class="form-label">Resep Obat</label>
              <textarea v-model="pemeriksaanForm.resep" class="form-control" rows="2" placeholder="Daftar obat yang diresepkan..."></textarea>
            </div>
            <div class="form-group col-span-3">
              <label class="form-label">Catatan Dokter</label>
              <textarea v-model="pemeriksaanForm.catatan_dokter" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showPemeriksaan = false">Batal</button>
          <button class="btn btn-success" @click="simpanPemeriksaan" :disabled="saving">
            <div class="spinner spinner-sm" v-if="saving"></div>
            Simpan & Selesaikan Pemeriksaan
          </button>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <div v-if="showDetail && detailData" class="modal-overlay" @click.self="showDetail = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <div class="modal-title">👁️ Detail Pendaftaran</div>
          <button class="btn btn-ghost btn-icon" @click="showDetail = false">✕</button>
        </div>
        <div class="modal-body">
          <div class="grid grid-2 gap-4">
            <div>
              <div class="text-xs text-muted mb-1">No. Antrian</div>
              <div class="font-bold" style="font-family:monospace">{{ detailData.no_antrian }}</div>
            </div>
            <div>
              <div class="text-xs text-muted mb-1">Status</div>
              <span class="badge" :class="badgeStatus(detailData.status)">{{ detailData.status }}</span>
            </div>
            <div>
              <div class="text-xs text-muted mb-1">Nama Pasien</div>
              <div class="font-semibold">{{ detailData.pasien?.nama_pasien }}</div>
            </div>
            <div>
              <div class="text-xs text-muted mb-1">No. RM</div>
              <div style="font-family:monospace">{{ detailData.pasien?.no_rm }}</div>
            </div>
            <div>
              <div class="text-xs text-muted mb-1">Dokter</div>
              <div>{{ detailData.jadwal_dokter?.dokter?.nama_dokter }}</div>
            </div>
            <div>
              <div class="text-xs text-muted mb-1">Poliklinik</div>
              <div>{{ detailData.jadwal_dokter?.poli?.nama_poli }}</div>
            </div>
            <div class="col-span-2">
              <div class="text-xs text-muted mb-1">Keluhan</div>
              <div>{{ detailData.keluhan || '-' }}</div>
            </div>
          </div>
          <hr class="divider" />
          <div v-if="detailData.pemeriksaan">
            <div class="font-semibold mb-3">📋 Hasil Pemeriksaan</div>
            <div class="grid grid-3 gap-3">
              <div><div class="text-xs text-muted mb-1">Tekanan Darah</div><div>{{ detailData.pemeriksaan.tekanan_darah || '-' }}</div></div>
              <div><div class="text-xs text-muted mb-1">Suhu</div><div>{{ detailData.pemeriksaan.suhu_tubuh ? detailData.pemeriksaan.suhu_tubuh + '°C' : '-' }}</div></div>
              <div><div class="text-xs text-muted mb-1">Nadi</div><div>{{ detailData.pemeriksaan.nadi ? detailData.pemeriksaan.nadi + '/mnt' : '-' }}</div></div>
            </div>
            <div class="mt-3"><div class="text-xs text-muted mb-1">Diagnosa</div><div>{{ detailData.pemeriksaan.diagnosa }}</div></div>
          </div>
          <div v-else class="text-muted text-sm">Belum ada data pemeriksaan.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showDetail = false">Tutup</button>
        </div>
      </div>
    </div>

    <!-- Confirm Batal -->
    <div v-if="showBatal" class="modal-overlay" @click.self="showBatal = false">
      <div class="modal" style="max-width:400px">
        <div class="modal-header"><div class="modal-title">⚠️ Batalkan Pendaftaran</div></div>
        <div class="modal-body">
          <p class="text-sm" style="color:var(--text-secondary)">Batalkan pendaftaran <strong>{{ batalTarget?.pasien?.nama_pasien }}</strong> ({{ batalTarget?.no_antrian }})?</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showBatal = false">Tidak</button>
          <button class="btn btn-danger" @click="doBatal" :disabled="saving">
            <div class="spinner spinner-sm" v-if="saving"></div>Ya, Batalkan
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useApi } from '../composables/useApi';
import { useToast } from '../composables/useToast';
import axios from 'axios';

const { get, post, patch, del, loading, errors } = useApi();
const { success: toastOk, error: toastErr } = useToast();

const pendaftaran    = ref([]);
const pagination     = ref({});
const today          = new Date().toISOString().split('T')[0];
const filterTanggal  = ref(today);
const filterStatus   = ref('');
const search         = ref('');
const currentPage    = ref(1);
const saving         = ref(false);

// Modals
const showCreate      = ref(false);
const showPemeriksaan = ref(false);
const showDetail      = ref(false);
const showBatal       = ref(false);

// Create form state
const cariQuery      = ref('');
const hasilCari      = ref([]);
const selectedPasien = ref(null);
const jadwalTersedia = ref([]);
const loadingJadwal  = ref(false);
const createForm     = ref({ tanggal_periksa: today, jadwal_dokter_id: null, keluhan: '' });
const createErrors   = ref({});

// Pemeriksaan
const activePendaftaran = ref(null);
const pemErrors         = ref({});
const defaultPem = () => ({ pendaftaran_id: null, tekanan_darah: '', suhu_tubuh: '', nadi: '', berat_badan: '', tinggi_badan: '', diagnosa: '', kode_icd: '', tindakan: '', resep: '', catatan_dokter: '' });
const pemeriksaanForm   = ref(defaultPem());

// Detail & Batal
const detailData   = ref(null);
const batalTarget  = ref(null);

const formatRp     = (v) => 'Rp ' + Number(v ?? 0).toLocaleString('id-ID');
const formatTanggal = (d) => d ? new Date(d).toLocaleDateString('id-ID', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) : '-';

const badgeStatus = (s) => ({ menunggu:'badge-warning', dipanggil:'badge-primary', selesai:'badge-success', batal:'badge-secondary' }[s] ?? 'badge-secondary');

const summaryCount = (status) => pendaftaran.value.filter(p => p.status === status).length;

const imtValue = computed(() => {
  const bb = parseFloat(pemeriksaanForm.value.berat_badan), tb = parseFloat(pemeriksaanForm.value.tinggi_badan);
  if (!bb || !tb) return null;
  return (bb / Math.pow(tb / 100, 2)).toFixed(2);
});

const imtKategori = computed(() => {
  const imt = parseFloat(imtValue.value);
  if (!imt) return '-';
  if (imt < 18.5) return 'Kurus';
  if (imt < 25.0) return 'Normal';
  if (imt < 30.0) return 'Gemuk';
  return 'Obesitas';
});

const visiblePages = computed(() => {
  const total = pagination.value.last_page ?? 1, cur = pagination.value.current_page ?? 1, pages = [];
  for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) pages.push(i);
  return pages;
});

let debounceTimer;
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { currentPage.value = 1; fetchData(); }, 400); }

async function fetchData() {
  const res = await get('/api/pendaftaran', { tanggal: filterTanggal.value, status: filterStatus.value, search: search.value, page: currentPage.value });
  if (res.success) { pendaftaran.value = res.data.data; pagination.value = res.data; }
}

function goPage(p) { currentPage.value = p; fetchData(); }

function openCreate() {
  cariQuery.value = ''; hasilCari.value = []; selectedPasien.value = null;
  jadwalTersedia.value = []; createErrors.value = {};
  createForm.value = { tanggal_periksa: today, jadwal_dokter_id: null, keluhan: '' };
  showCreate.value = true;
}

let cariTimer;
function cariPasien() {
  clearTimeout(cariTimer);
  if (cariQuery.value.length < 3) { hasilCari.value = []; return; }
  cariTimer = setTimeout(async () => {
    const res = await get('/api/pasien-cari', { q: cariQuery.value });
    if (res.success) hasilCari.value = res.data;
  }, 300);
}

function pilihPasien(ps) { selectedPasien.value = ps; hasilCari.value = []; cariQuery.value = ps.nama_pasien; }

async function fetchJadwalTersedia() {
  if (!createForm.value.tanggal_periksa) return;
  loadingJadwal.value = true;
  createForm.value.jadwal_dokter_id = null;
  const res = await get('/api/jadwal-dokter/tersedia', { tanggal: createForm.value.tanggal_periksa });
  loadingJadwal.value = false;
  if (res.success) jadwalTersedia.value = res.data;
}

async function daftarkanPasien() {
  if (!selectedPasien.value || !createForm.value.jadwal_dokter_id) {
    toastErr('Pilih pasien dan jadwal dokter terlebih dahulu.'); return;
  }
  saving.value = true;
  const res = await post('/api/pendaftaran', { pasien_id: selectedPasien.value.id, ...createForm.value });
  saving.value = false;
  if (res.success) {
    toastOk(`✅ ${res.data.message}`);
    showCreate.value = false;
    fetchData();
  } else {
    createErrors.value = res.error?.errors ?? {};
    toastErr(res.error);
  }
}

async function updateStatus(p, status) {
  const res = await patch(`/api/pendaftaran/${p.id}/status`, { status });
  if (res.success) { toastOk(res.data.message); fetchData(); } else { toastErr(res.error); }
}

function openPemeriksaan(p) {
  activePendaftaran.value = p;
  pemeriksaanForm.value = { ...defaultPem(), pendaftaran_id: p.id };
  pemErrors.value = {};
  showPemeriksaan.value = true;
}

async function simpanPemeriksaan() {
  saving.value = true;
  const res = await post('/api/pemeriksaan', pemeriksaanForm.value);
  saving.value = false;
  if (res.success) {
    toastOk('Pemeriksaan berhasil disimpan. Status pasien: Selesai.');
    showPemeriksaan.value = false;
    fetchData();
  } else {
    pemErrors.value = {};
    toastErr(res.error);
  }
}

async function openDetail(p) {
  const res = await get(`/api/pendaftaran/${p.id}`);
  if (res.success) { detailData.value = res.data; showDetail.value = true; }
}

function confirmBatal(p) { batalTarget.value = p; showBatal.value = true; }

async function doBatal() {
  saving.value = true;
  const res = await del(`/api/pendaftaran/${batalTarget.value.id}`);
  saving.value = false;
  if (res.success) { toastOk(res.data.message); showBatal.value = false; fetchData(); } else { toastErr(res.error); }
}

onMounted(() => {
  fetchData();
  fetchJadwalTersedia();
});
</script>
