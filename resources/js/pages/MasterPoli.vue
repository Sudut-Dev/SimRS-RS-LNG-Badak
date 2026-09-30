<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <div class="search-bar">
          <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input v-model="search" @input="debouncedFetch" placeholder="Cari nama poli, kode..." />
        </div>
        <select class="form-select" style="width:auto" v-model="filterStatus" @change="fetchData">
          <option value="">Semua Status</option>
          <option value="aktif">Aktif</option>
          <option value="nonaktif">Nonaktif</option>
        </select>
      </div>
      <button class="btn btn-primary" @click="openCreate">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Poli
      </button>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">Data Poliklinik ({{ pagination.total ?? 0 }})</div>
      </div>
      <div style="position:relative">
        <div v-if="loading" class="loading-overlay"><div class="spinner spinner-lg"></div></div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr><th>Kode Poli</th><th>Nama Poliklinik</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-if="!loading && poli.length === 0">
                <td colspan="5"><div class="empty-state"><div class="empty-state-icon">🏥</div><div class="empty-state-title">Belum ada data poli</div></div></td>
              </tr>
              <tr v-for="p in poli" :key="p.id">
                <td><span class="badge badge-primary" style="font-family:monospace">{{ p.kode_poli }}</span></td>
                <td><div class="font-semibold" style="color:var(--text-primary)">{{ p.nama_poli }}</div></td>
                <td>{{ p.lokasi || '-' }}</td>
                <td>
                  <span class="badge" :class="p.status === 'aktif' ? 'badge-success' : 'badge-secondary'">
                    <span class="status-dot" :class="p.status"></span>{{ p.status }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">
                    <button class="btn btn-outline btn-sm" @click="openEdit(p)">✏️ Edit</button>
                    <button class="btn btn-outline btn-sm" @click="confirmDelete(p)" style="color:var(--danger)">🗑️</button>
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

    <!-- Modal Form -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">{{ isEdit ? '✏️ Edit Poli' : '➕ Tambah Poli Baru' }}</div>
          <button class="btn btn-ghost btn-icon" @click="showModal = false">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Nama Poliklinik <span class="required">*</span></label>
            <input v-model="form.nama_poli" class="form-control" :class="{ 'is-invalid': errors.nama_poli }" placeholder="cth: Poli Umum, Poli Anak..." />
            <div class="invalid-feedback">{{ errors.nama_poli?.[0] }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Lokasi</label>
            <input v-model="form.lokasi" class="form-control" placeholder="cth: Gedung A, Lantai 1" />
          </div>
          <div class="form-group">
            <label class="form-label">Status <span class="required">*</span></label>
            <select v-model="form.status" class="form-select">
              <option value="aktif">Aktif</option>
              <option value="nonaktif">Nonaktif</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showModal = false">Batal</button>
          <button class="btn btn-primary" @click="submit" :disabled="saving">
            <div class="spinner spinner-sm" v-if="saving"></div>
            {{ isEdit ? 'Simpan' : 'Tambah Poli' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Confirm Delete -->
    <div v-if="showDelete" class="modal-overlay" @click.self="showDelete = false">
      <div class="modal" style="max-width:400px">
        <div class="modal-header"><div class="modal-title">⚠️ Hapus Poli</div></div>
        <div class="modal-body">
          <p class="text-sm" style="color:var(--text-secondary)">Hapus poli <strong style="color:var(--text-primary)">{{ deleteTarget?.nama_poli }}</strong>?</p>
          <div class="alert alert-warning mt-4">Poli dengan jadwal dokter aktif tidak dapat dihapus.</div>
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

const poli         = ref([]);
const pagination   = ref({});
const search       = ref('');
const filterStatus = ref('');
const currentPage  = ref(1);
const showModal    = ref(false);
const showDelete   = ref(false);
const isEdit       = ref(false);
const saving       = ref(false);
const deleteTarget = ref(null);

const defaultForm = () => ({ nama_poli: '', lokasi: '', status: 'aktif' });
const form = ref(defaultForm());

const visiblePages = computed(() => {
  const total = pagination.value.last_page ?? 1, cur = pagination.value.current_page ?? 1, pages = [];
  for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) pages.push(i);
  return pages;
});

let debounceTimer;
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { currentPage.value = 1; fetchData(); }, 400); }

async function fetchData() {
  const res = await get('/api/poli', { search: search.value, status: filterStatus.value, page: currentPage.value });
  if (res.success) { poli.value = res.data.data; pagination.value = res.data; }
}

function goPage(p) { currentPage.value = p; fetchData(); }
function openCreate() { form.value = defaultForm(); isEdit.value = false; showModal.value = true; }
function openEdit(p) {
  form.value = { nama_poli: p.nama_poli, lokasi: p.lokasi ?? '', status: p.status, _id: p.id };
  isEdit.value = true; showModal.value = true;
}
function confirmDelete(p) { deleteTarget.value = p; showDelete.value = true; }

async function submit() {
  saving.value = true;
  const res = isEdit.value ? await put(`/api/poli/${form.value._id}`, form.value) : await post('/api/poli', form.value);
  saving.value = false;
  if (res.success) { toastOk(res.data.message); showModal.value = false; fetchData(); } else { toastErr(res.error); }
}

async function doDelete() {
  saving.value = true;
  const res = await del(`/api/poli/${deleteTarget.value.id}`);
  saving.value = false;
  if (res.success) { toastOk(res.data.message); showDelete.value = false; fetchData(); } else { toastErr(res.error); }
}

onMounted(fetchData);
</script>
