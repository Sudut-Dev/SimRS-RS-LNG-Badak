<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <div class="search-bar">
          <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input v-model="search" @input="debouncedFetch" placeholder="Cari nama, spesialisasi, kode..." />
        </div>
        <select class="form-select" style="width:auto" v-model="filterStatus" @change="fetchData">
          <option value="">Semua Status</option>
          <option value="aktif">Aktif</option>
          <option value="nonaktif">Nonaktif</option>
        </select>
      </div>
      <button class="btn btn-primary" @click="openCreate">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Dokter
      </button>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">Data Dokter ({{ pagination.total ?? 0 }})</div>
      </div>
      <div style="position:relative">
        <div v-if="loading" class="loading-overlay"><div class="spinner spinner-lg"></div></div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Nama Dokter</th>
                <th>Spesialisasi</th>
                <th>No. SIP</th>
                <th>Biaya Konsultasi</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!loading && dokter.length === 0">
                <td colspan="7">
                  <div class="empty-state"><div class="empty-state-icon">👨‍⚕️</div><div class="empty-state-title">Tidak ada data dokter</div></div>
                </td>
              </tr>
              <tr v-for="d in dokter" :key="d.id">
                <td><span class="badge badge-secondary" style="font-family:monospace">{{ d.kode_dokter }}</span></td>
                <td>
                  <div class="font-semibold" style="color:var(--text-primary)">{{ d.nama_dokter }}</div>
                  <div class="text-xs text-muted">{{ d.no_hp || '-' }}</div>
                </td>
                <td>
                  <span class="badge badge-info">{{ d.spesialisasi }}</span>
                </td>
                <td style="font-size:0.8rem;font-family:monospace">{{ d.no_sip }}</td>
                <td class="money">{{ formatRp(d.biaya_konsultasi) }}</td>
                <td>
                  <span class="badge" :class="d.status === 'aktif' ? 'badge-success' : 'badge-secondary'">
                    <span class="status-dot" :class="d.status"></span>{{ d.status }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <button class="btn btn-outline btn-sm" @click="openEdit(d)">✏️ Edit</button>
                    <button class="btn btn-outline btn-sm" @click="confirmDelete(d)" style="color:var(--danger)">🗑️</button>
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
          <div class="modal-title">{{ isEdit ? '✏️ Edit Dokter' : '➕ Tambah Dokter Baru' }}</div>
          <button class="btn btn-ghost btn-icon" @click="showModal = false">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group col-span-2">
              <label class="form-label">Nama Dokter <span class="required">*</span></label>
              <input v-model="form.nama_dokter" class="form-control" :class="{ 'is-invalid': errors.nama_dokter }" placeholder="Nama lengkap dengan gelar" />
              <div class="invalid-feedback">{{ errors.nama_dokter?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Spesialisasi <span class="required">*</span></label>
              <input v-model="form.spesialisasi" class="form-control" :class="{ 'is-invalid': errors.spesialisasi }" placeholder="cth: Penyakit Dalam, Anak..." />
              <div class="invalid-feedback">{{ errors.spesialisasi?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Nomor SIP <span class="required">*</span></label>
              <input v-model="form.no_sip" class="form-control" :class="{ 'is-invalid': errors.no_sip }" placeholder="Nomor Surat Izin Praktik" />
              <div class="invalid-feedback">{{ errors.no_sip?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">No. HP</label>
              <input v-model="form.no_hp" class="form-control" :class="{ 'is-invalid': errors.no_hp }" placeholder="08xxxxxxxxxx" maxlength="15" />
              <div class="invalid-feedback">{{ errors.no_hp?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Biaya Konsultasi <span class="required">*</span></label>
              <input type="number" v-model="form.biaya_konsultasi" class="form-control" :class="{ 'is-invalid': errors.biaya_konsultasi }" placeholder="0" min="0" />
              <div class="invalid-feedback">{{ errors.biaya_konsultasi?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Status <span class="required">*</span></label>
              <select v-model="form.status" class="form-select">
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showModal = false">Batal</button>
          <button class="btn btn-primary" @click="submit" :disabled="saving">
            <div class="spinner spinner-sm" v-if="saving"></div>
            {{ isEdit ? 'Simpan Perubahan' : 'Tambah Dokter' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm Delete -->
    <div v-if="showDelete" class="modal-overlay" @click.self="showDelete = false">
      <div class="modal" style="max-width:400px">
        <div class="modal-header"><div class="modal-title">⚠️ Hapus Dokter</div></div>
        <div class="modal-body">
          <p class="text-sm" style="color:var(--text-secondary)">Hapus data dokter <strong style="color:var(--text-primary)">{{ deleteTarget?.nama_dokter }}</strong>?</p>
          <div class="alert alert-warning mt-4">Dokter dengan jadwal aktif tidak dapat dihapus.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showDelete = false">Batal</button>
          <button class="btn btn-danger" @click="doDelete" :disabled="saving">
            <div class="spinner spinner-sm" v-if="saving"></div>Hapus
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

const { get, post, put, del, loading, errors } = useApi();
const { success: toastOk, error: toastErr } = useToast();

const dokter       = ref([]);
const pagination   = ref({});
const search       = ref('');
const filterStatus = ref('');
const currentPage  = ref(1);
const showModal    = ref(false);
const showDelete   = ref(false);
const isEdit       = ref(false);
const saving       = ref(false);
const deleteTarget = ref(null);

const defaultForm = () => ({ nama_dokter: '', spesialisasi: '', no_sip: '', no_hp: '', biaya_konsultasi: 0, status: 'aktif' });
const form = ref(defaultForm());

const formatRp = (v) => 'Rp ' + Number(v ?? 0).toLocaleString('id-ID');

const visiblePages = computed(() => {
  const total = pagination.value.last_page ?? 1, cur = pagination.value.current_page ?? 1, pages = [];
  for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) pages.push(i);
  return pages;
});

let debounceTimer;
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { currentPage.value = 1; fetchData(); }, 400); }

async function fetchData() {
  const res = await get('/api/dokter', { search: search.value, status: filterStatus.value, page: currentPage.value });
  if (res.success) { dokter.value = res.data.data; pagination.value = res.data; }
}

function goPage(p) { currentPage.value = p; fetchData(); }

function openCreate() { form.value = defaultForm(); isEdit.value = false; showModal.value = true; }
function openEdit(d) {
  form.value = { nama_dokter: d.nama_dokter, spesialisasi: d.spesialisasi, no_sip: d.no_sip, no_hp: d.no_hp ?? '', biaya_konsultasi: d.biaya_konsultasi, status: d.status, _id: d.id };
  isEdit.value = true; showModal.value = true;
}
function confirmDelete(d) { deleteTarget.value = d; showDelete.value = true; }

async function submit() {
  saving.value = true;
  const res = isEdit.value ? await put(`/api/dokter/${form.value._id}`, form.value) : await post('/api/dokter', form.value);
  saving.value = false;
  if (res.success) { toastOk(res.data.message); showModal.value = false; fetchData(); } else { toastErr(res.error); }
}

async function doDelete() {
  saving.value = true;
  const res = await del(`/api/dokter/${deleteTarget.value.id}`);
  saving.value = false;
  if (res.success) { toastOk(res.data.message); showDelete.value = false; fetchData(); } else { toastErr(res.error); }
}

onMounted(fetchData);
</script>
