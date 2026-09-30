<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <select class="form-select" style="width:auto" v-model="filterPoli" @change="fetchData">
          <option value="">Semua Poli</option>
          <option v-for="p in poliList" :key="p.id" :value="p.id">{{ p.nama_poli }}</option>
        </select>
        <select class="form-select" style="width:auto" v-model="filterHari" @change="fetchData">
          <option value="">Semua Hari</option>
          <option v-for="h in hariList" :key="h" :value="h">{{ h }}</option>
        </select>
      </div>
      <button class="btn btn-primary" @click="openCreate">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Jadwal
      </button>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">Jadwal Praktek Dokter ({{ pagination.total ?? 0 }})</div>
        <div class="text-sm text-muted">Menampilkan jadwal reguler mingguan</div>
      </div>
      <div style="position:relative">
        <div v-if="loading" class="loading-overlay"><div class="spinner spinner-lg"></div></div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr><th>Hari</th><th>Dokter</th><th>Poliklinik</th><th>Jam Praktek</th><th>Kuota</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-if="!loading && jadwal.length === 0">
                <td colspan="7"><div class="empty-state"><div class="empty-state-icon">📅</div><div class="empty-state-title">Belum ada jadwal</div></div></td>
              </tr>
              <tr v-for="j in jadwal" :key="j.id">
                <td>
                  <span class="badge badge-primary">{{ j.hari }}</span>
                </td>
                <td>
                  <div class="font-semibold" style="color:var(--text-primary)">{{ j.dokter?.nama_dokter }}</div>
                  <div class="text-xs text-muted">{{ j.dokter?.spesialisasi }}</div>
                </td>
                <td>{{ j.poli?.nama_poli }}</td>
                <td>
                  <span class="badge badge-secondary">{{ j.jam_mulai }} - {{ j.jam_selesai }}</span>
                </td>
                <td>
                  <span class="font-bold">{{ j.kuota }}</span>
                  <span class="text-muted text-xs"> pasien</span>
                </td>
                <td>
                  <span class="badge" :class="j.status === 'aktif' ? 'badge-success' : 'badge-secondary'">
                    <span class="status-dot" :class="j.status"></span>{{ j.status }}
                  </span>
                </td>
                <td>
                  <div class="table-actions" v-if="isAdmin">
                    <button class="btn btn-outline btn-sm" @click="openEdit(j)">✏️</button>
                    <button class="btn btn-outline btn-sm" @click="confirmDelete(j)" style="color:var(--danger)">🗑️</button>
                  </div>
                  <span v-else class="text-muted text-xs">-</span>
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

    <!-- Modal Form (Admin only) -->
    <div v-if="showModal && isAdmin" class="modal-overlay" @click.self="showModal = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <div class="modal-title">{{ isEdit ? '✏️ Edit Jadwal' : '➕ Tambah Jadwal Dokter' }}</div>
          <button class="btn btn-ghost btn-icon" @click="showModal = false">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Dokter <span class="required">*</span></label>
              <select v-model="form.dokter_id" class="form-select" :class="{ 'is-invalid': errors.dokter_id }">
                <option value="">-- Pilih Dokter --</option>
                <option v-for="d in dokterList" :key="d.id" :value="d.id">{{ d.nama_dokter }} ({{ d.spesialisasi }})</option>
              </select>
              <div class="invalid-feedback">{{ errors.dokter_id?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Poliklinik <span class="required">*</span></label>
              <select v-model="form.poli_id" class="form-select" :class="{ 'is-invalid': errors.poli_id }">
                <option value="">-- Pilih Poli --</option>
                <option v-for="p in poliList" :key="p.id" :value="p.id">{{ p.nama_poli }}</option>
              </select>
              <div class="invalid-feedback">{{ errors.poli_id?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Hari <span class="required">*</span></label>
              <select v-model="form.hari" class="form-select" :class="{ 'is-invalid': errors.hari }">
                <option value="">-- Pilih Hari --</option>
                <option v-for="h in hariList" :key="h" :value="h">{{ h }}</option>
              </select>
              <div class="invalid-feedback">{{ errors.hari?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Kuota Pasien <span class="required">*</span></label>
              <input type="number" v-model="form.kuota" class="form-control" :class="{ 'is-invalid': errors.kuota }" min="1" max="100" />
              <div class="invalid-feedback">{{ errors.kuota?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Jam Mulai <span class="required">*</span></label>
              <input type="time" v-model="form.jam_mulai" class="form-control" :class="{ 'is-invalid': errors.jam_mulai }" />
              <div class="invalid-feedback">{{ errors.jam_mulai?.[0] }}</div>
            </div>
            <div class="form-group">
              <label class="form-label">Jam Selesai <span class="required">*</span></label>
              <input type="time" v-model="form.jam_selesai" class="form-control" :class="{ 'is-invalid': errors.jam_selesai }" />
              <div class="invalid-feedback">{{ errors.jam_selesai?.[0] }}</div>
            </div>
            <div class="form-group col-span-2">
              <label class="form-label">Status</label>
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
            {{ isEdit ? 'Simpan' : 'Tambah Jadwal' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm Delete -->
    <div v-if="showDelete" class="modal-overlay" @click.self="showDelete = false">
      <div class="modal" style="max-width:400px">
        <div class="modal-header"><div class="modal-title">⚠️ Hapus Jadwal</div></div>
        <div class="modal-body">
          <p class="text-sm" style="color:var(--text-secondary)">Hapus jadwal <strong>{{ deleteTarget?.dokter?.nama_dokter }}</strong> - {{ deleteTarget?.hari }}?</p>
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

const isAdmin   = computed(() => window.__AUTH_USER__?.role === 'admin');
const jadwal    = ref([]);
const pagination = ref({});
const filterPoli = ref('');
const filterHari = ref('');
const currentPage = ref(1);
const showModal  = ref(false);
const showDelete = ref(false);
const isEdit     = ref(false);
const saving     = ref(false);
const deleteTarget = ref(null);
const dokterList = ref([]);
const poliList   = ref([]);
const hariList   = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];

const defaultForm = () => ({ dokter_id: '', poli_id: '', hari: '', jam_mulai: '08:00', jam_selesai: '12:00', kuota: 20, status: 'aktif' });
const form = ref(defaultForm());

const visiblePages = computed(() => {
  const total = pagination.value.last_page ?? 1, cur = pagination.value.current_page ?? 1, pages = [];
  for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) pages.push(i);
  return pages;
});

async function fetchData() {
  const res = await get('/api/jadwal-dokter', { poli_id: filterPoli.value, hari: filterHari.value, page: currentPage.value });
  if (res.success) { jadwal.value = res.data.data; pagination.value = res.data; }
}

function goPage(p) { currentPage.value = p; fetchData(); }

async function openCreate() {
  form.value = defaultForm(); isEdit.value = false;
  await loadDropdowns();
  showModal.value = true;
}

async function openEdit(j) {
  form.value = { dokter_id: j.dokter_id, poli_id: j.poli_id, hari: j.hari, jam_mulai: j.jam_mulai, jam_selesai: j.jam_selesai, kuota: j.kuota, status: j.status, _id: j.id };
  isEdit.value = true;
  await loadDropdowns();
  showModal.value = true;
}

function confirmDelete(j) { deleteTarget.value = j; showDelete.value = true; }

async function loadDropdowns() {
  const [dRes, pRes] = await Promise.all([get('/api/dokter/dropdown'), get('/api/poli/dropdown')]);
  if (dRes.success) dokterList.value = dRes.data;
  if (pRes.success) poliList.value = pRes.data;
}

async function submit() {
  saving.value = true;
  const res = isEdit.value ? await put(`/api/jadwal-dokter/${form.value._id}`, form.value) : await post('/api/jadwal-dokter', form.value);
  saving.value = false;
  if (res.success) { toastOk(res.data.message); showModal.value = false; fetchData(); } else { toastErr(res.error); }
}

async function doDelete() {
  saving.value = true;
  const res = await del(`/api/jadwal-dokter/${deleteTarget.value.id}`);
  saving.value = false;
  if (res.success) { toastOk(res.data.message); showDelete.value = false; fetchData(); } else { toastErr(res.error); }
}

onMounted(async () => {
  await loadDropdowns();
  fetchData();
});
</script>
