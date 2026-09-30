<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <div class="search-bar">
          <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input v-model="search" @input="debouncedFetch" placeholder="Cari nama, NIK, No. RM..." />
        </div>
        <select class="form-select" style="width:auto" v-model="filterBayar" @change="fetchData">
          <option value="">Semua Pembayaran</option>
          <option value="umum">Umum</option>
          <option value="bpjs">BPJS</option>
          <option value="asuransi">Asuransi</option>
        </select>
      </div>
      <button class="btn btn-primary" @click="openCreate">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Pasien
      </button>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Data Pasien ({{ pagination.total ?? 0 }})</div>
      </div>
      <div style="position:relative">
        <div v-if="loading" class="loading-overlay"><div class="spinner spinner-lg"></div></div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>No. RM</th>
                <th>NIK</th>
                <th>Nama Pasien</th>
                <th>Usia / JK</th>
                <th>Pembayaran</th>
                <th>No. HP</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!loading && pasien.length === 0">
                <td colspan="7">
                  <div class="empty-state"><div class="empty-state-icon">👤</div><div class="empty-state-title">Tidak ada data pasien</div></div>
                </td>
              </tr>
              <tr v-for="p in pasien" :key="p.id">
                <td><span class="badge badge-secondary" style="font-family:monospace">{{ p.no_rm }}</span></td>
                <td style="font-family:monospace;font-size:0.8rem">{{ p.nik }}</td>
                <td>
                  <div class="font-semibold" style="color:var(--text-primary)">{{ p.nama_pasien }}</div>
                  <div class="text-xs text-muted">{{ p.tempat_lahir }}, {{ p.tanggal_lahir }}</div>
                </td>
                <td>
                  <div>{{ p.umur }}</div>
                  <span class="badge" :class="p.jenis_kelamin === 'L' ? 'badge-info' : 'badge-primary'">
                    {{ p.jenis_kelamin === 'L' ? '♂ Laki-laki' : '♀ Perempuan' }}
                  </span>
                </td>
                <td>
                  <span class="badge" :class="{ 'badge-success': p.jenis_pembayaran === 'bpjs', 'badge-info': p.jenis_pembayaran === 'asuransi', 'badge-secondary': p.jenis_pembayaran === 'umum' }">
                    {{ p.jenis_pembayaran.toUpperCase() }}
                  </span>
                </td>
                <td>{{ p.no_hp || '-' }}</td>
                <td>
                  <div class="table-actions">
                    <button class="btn btn-outline btn-sm" @click="openEdit(p)" title="Edit">✏️</button>
                    <button class="btn btn-outline btn-sm" @click="confirmDelete(p)" style="color:var(--danger)" title="Hapus">🗑️</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div class="card-footer flex items-center justify-between" v-if="pagination.last_page > 1">
          <div class="text-sm text-muted">Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}</div>
          <div class="pagination">
            <button class="page-btn" :disabled="pagination.current_page <= 1" @click="goPage(pagination.current_page - 1)">‹</button>
            <button v-for="p in visiblePages" :key="p" class="page-btn" :class="{ active: p === pagination.current_page }" @click="goPage(p)">{{ p }}</button>
            <button class="page-btn" :disabled="pagination.current_page >= pagination.last_page" @click="goPage(pagination.current_page + 1)">›</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <div class="modal-title">{{ isEdit ? '✏️ Edit Pasien' : '➕ Tambah Pasien Baru' }}</div>
          <button class="btn btn-ghost btn-icon" @click="showModal = false">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group col-span-2">
              <label class="form-label">NIK <span class="required">*</span></label>
              <input v-model="form.nik" class="form-control" :class="{ 'is-invalid': errors.nik }" maxlength="16" placeholder="16 digit NIK" />
              <div class="invalid-feedback">{{ errors.nik?.[0] }}</div>
              <div v-if="form.nik && form.nik.length !== 16" class="invalid-feedback">NIK harus tepat 16 digit (saat ini: {{ form.nik.length }})</div>
            </div>
            <div class="form-group col-span-2">
              <label class="form-label">Nama Lengkap <span class="required">*</span></label>
              <input v-model="form.nama_pasien" class="form-control" :class="{ 'is-invalid': errors.nama_pasien }" placeholder="Nama sesuai KTP" />
              <div class="invalid-feedback">{{ errors.nama_pasien?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Jenis Kelamin <span class="required">*</span></label>
              <select v-model="form.jenis_kelamin" class="form-select" :class="{ 'is-invalid': errors.jenis_kelamin }">
                <option value="">-- Pilih --</option>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
              <div class="invalid-feedback">{{ errors.jenis_kelamin?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Golongan Darah</label>
              <select v-model="form.golongan_darah" class="form-select">
                <option v-for="g in ['A','B','AB','O','-']" :key="g" :value="g">{{ g }}</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Tempat Lahir <span class="required">*</span></label>
              <input v-model="form.tempat_lahir" class="form-control" :class="{ 'is-invalid': errors.tempat_lahir }" placeholder="Kota tempat lahir" />
              <div class="invalid-feedback">{{ errors.tempat_lahir?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Tanggal Lahir <span class="required">*</span></label>
              <input type="date" v-model="form.tanggal_lahir" class="form-control" :class="{ 'is-invalid': errors.tanggal_lahir }" :max="maxDate" />
              <div class="invalid-feedback">{{ errors.tanggal_lahir?.[0] }}</div>
            </div>
            <div class="form-group col-span-2">
              <label class="form-label">Alamat <span class="required">*</span></label>
              <textarea v-model="form.alamat" class="form-control" :class="{ 'is-invalid': errors.alamat }" rows="2" placeholder="Alamat lengkap"></textarea>
              <div class="invalid-feedback">{{ errors.alamat?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">No. HP</label>
              <input v-model="form.no_hp" class="form-control" :class="{ 'is-invalid': errors.no_hp }" placeholder="08xxxxxxxxxx" maxlength="15" />
              <div class="invalid-feedback">{{ errors.no_hp?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Jenis Pembayaran <span class="required">*</span></label>
              <select v-model="form.jenis_pembayaran" class="form-select" :class="{ 'is-invalid': errors.jenis_pembayaran }">
                <option value="umum">Umum</option>
                <option value="bpjs">BPJS</option>
                <option value="asuransi">Asuransi</option>
              </select>
              <div class="invalid-feedback">{{ errors.jenis_pembayaran?.[0] }}</div>
            </div>
            <div class="form-group col-span-2" v-if="form.jenis_pembayaran === 'bpjs'">
              <label class="form-label">No. BPJS <span class="required">*</span></label>
              <input v-model="form.no_bpjs" class="form-control" :class="{ 'is-invalid': errors.no_bpjs }" placeholder="13 digit no. BPJS" maxlength="13" />
              <div class="invalid-feedback">{{ errors.no_bpjs?.[0] }}</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showModal = false">Batal</button>
          <button class="btn btn-primary" @click="submit" :disabled="saving">
            <div class="spinner spinner-sm" v-if="saving"></div>
            {{ isEdit ? 'Simpan Perubahan' : 'Daftarkan Pasien' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm Delete -->
    <div v-if="showDelete" class="modal-overlay" @click.self="showDelete = false">
      <div class="modal" style="max-width:400px">
        <div class="modal-header">
          <div class="modal-title">⚠️ Konfirmasi Hapus</div>
        </div>
        <div class="modal-body">
          <p class="text-sm" style="color:var(--text-secondary)">Apakah Anda yakin ingin menghapus data pasien <strong style="color:var(--text-primary)">{{ deleteTarget?.nama_pasien }}</strong>?</p>
          <div class="alert alert-warning mt-4">Tindakan ini tidak dapat dibatalkan.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showDelete = false">Batal</button>
          <button class="btn btn-danger" @click="doDelete" :disabled="saving">
            <div class="spinner spinner-sm" v-if="saving"></div>
            Hapus
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue';
import { useApi } from '../composables/useApi';
import { useToast } from '../composables/useToast';

const { get, post, put, del, loading, errors } = useApi();
const { success: toastOk, error: toastErr } = useToast();

const pasien       = ref([]);
const pagination   = ref({});
const search       = ref('');
const filterBayar  = ref('');
const currentPage  = ref(1);
const showModal    = ref(false);
const showDelete   = ref(false);
const isEdit       = ref(false);
const saving       = ref(false);
const deleteTarget = ref(null);
const maxDate      = new Date(Date.now() - 86400000).toISOString().split('T')[0];

const defaultForm = () => ({
  nik: '', nama_pasien: '', jenis_kelamin: '', tempat_lahir: '', tanggal_lahir: '',
  alamat: '', no_hp: '', golongan_darah: 'O', jenis_pembayaran: 'umum', no_bpjs: '',
});

const form = ref(defaultForm());

const visiblePages = computed(() => {
  const total = pagination.value.last_page ?? 1;
  const cur = pagination.value.current_page ?? 1;
  const pages = [];
  for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) pages.push(i);
  return pages;
});

let debounceTimer;
function debouncedFetch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => { currentPage.value = 1; fetchData(); }, 400);
}

async function fetchData() {
  const res = await get('/api/pasien', { search: search.value, jenis_pembayaran: filterBayar.value, page: currentPage.value });
  if (res.success) {
    pasien.value     = res.data.data;
    pagination.value = res.data;
  }
}

function goPage(p) { currentPage.value = p; fetchData(); }

function openCreate() {
  form.value = defaultForm();
  isEdit.value = false;
  showModal.value = true;
}

function openEdit(p) {
  form.value = { nik: p.nik, nama_pasien: p.nama_pasien, jenis_kelamin: p.jenis_kelamin, tempat_lahir: p.tempat_lahir,
    tanggal_lahir: p.tanggal_lahir, alamat: p.alamat, no_hp: p.no_hp ?? '', golongan_darah: p.golongan_darah,
    jenis_pembayaran: p.jenis_pembayaran, no_bpjs: p.no_bpjs ?? '', _id: p.id };
  isEdit.value = true;
  showModal.value = true;
}

function confirmDelete(p) { deleteTarget.value = p; showDelete.value = true; }

async function submit() {
  saving.value = true;
  const res = isEdit.value
    ? await put(`/api/pasien/${form.value._id}`, form.value)
    : await post('/api/pasien', form.value);

  saving.value = false;
  if (res.success) {
    toastOk(res.data.message);
    showModal.value = false;
    fetchData();
  } else {
    toastErr(res.error);
  }
}

async function doDelete() {
  saving.value = true;
  const res = await del(`/api/pasien/${deleteTarget.value.id}`);
  saving.value = false;
  if (res.success) {
    toastOk(res.data.message);
    showDelete.value = false;
    fetchData();
  } else {
    toastErr(res.error);
  }
}

onMounted(fetchData);
</script>
