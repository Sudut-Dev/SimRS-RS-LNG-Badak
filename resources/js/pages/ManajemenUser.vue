<template>
  <div>

    <!-- ===== STAT CARDS ===== -->
    <div class="um-stats-grid mb-4">
      <div class="um-stat-card" v-for="s in statCards" :key="s.key"
           :class="{ active: filterStatus === s.filter && filterRole === s.roleFilter }"
           @click="applyStatFilter(s)">
        <div class="um-stat-icon" :style="{ background: s.bg }">
          <span>{{ s.icon }}</span>
        </div>
        <div class="um-stat-info">
          <div class="um-stat-value">{{ stats[s.key] ?? '—' }}</div>
          <div class="um-stat-label">{{ s.label }}</div>
        </div>
      </div>
    </div>

    <!-- ===== TOOLBAR ===== -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
      <div class="flex items-center gap-2 flex-wrap flex-1" style="min-width:0">
        <!-- Search -->
        <div class="search-bar">
          <svg class="search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input v-model="search" @input="debouncedFetch" placeholder="Cari nama, email, no. HP..." />
        </div>

        <!-- Filter Role -->
        <select class="form-select" style="width:auto;min-width:130px" v-model="filterRole" @change="doFetch">
          <option value="">Semua Role</option>
          <option value="admin">⚡ Admin</option>
          <option value="petugas">👤 Petugas</option>
        </select>

        <!-- Filter Status -->
        <select class="form-select" style="width:auto;min-width:130px" v-model="filterStatus" @change="doFetch">
          <option value="">Semua Status</option>
          <option value="aktif">✅ Aktif</option>
          <option value="nonaktif">🚫 Nonaktif</option>
        </select>

        <!-- Reset filter -->
        <button v-if="hasFilter" class="btn btn-ghost btn-sm" @click="clearFilter" title="Reset filter">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"/>
          </svg>
          Reset
        </button>
      </div>

      <!-- Actions -->
      <div class="flex gap-2 flex-wrap">
        <button class="btn btn-outline btn-sm" @click="exportCSV" title="Export CSV">
          <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Export
        </button>
        <button class="btn btn-primary" @click="openCreate" id="btn-tambah-pengguna">
          <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
          </svg>
          Tambah Pengguna
        </button>
      </div>
    </div>

    <!-- ===== TABLE CARD ===== -->
    <div class="card">
      <div class="card-header">
        <div class="um-card-title-group">
          <div class="card-title">
            👥 Manajemen Pengguna
            <span class="badge badge-secondary" style="margin-left:0.4rem">{{ pagination.total ?? 0 }}</span>
          </div>
          <div class="text-xs text-muted" v-if="hasFilter">
            Filter aktif — <a href="#" @click.prevent="clearFilter" style="color:var(--primary)">tampilkan semua</a>
          </div>
        </div>
        <div class="badge badge-warning">🔒 Hanya Admin</div>
      </div>

      <div style="position:relative">
        <div v-if="loading" class="loading-overlay"><div class="spinner spinner-lg"></div></div>

        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Pengguna</th>
                <th class="um-hide-sm">Email</th>
                <th>Role</th>
                <th class="um-hide-md">No. HP</th>
                <th>Status</th>
                <th class="um-hide-md">Bergabung</th>
                <th style="text-align:right;white-space:nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <!-- Empty state -->
              <tr v-if="!loading && users.length === 0">
                <td colspan="7">
                  <div class="empty-state">
                    <div class="empty-state-icon">👤</div>
                    <div class="empty-state-title">Tidak ada pengguna</div>
                    <div class="empty-state-text">
                      {{ hasFilter ? 'Tidak ada hasil untuk filter yang dipilih.' : 'Belum ada pengguna terdaftar.' }}
                    </div>
                    <button v-if="hasFilter" class="btn btn-outline btn-sm mt-2" @click="clearFilter">Reset Filter</button>
                  </div>
                </td>
              </tr>

              <!-- Rows -->
              <tr v-for="u in users" :key="u.id" :class="{ 'um-row-inactive': !u.is_active }">
                <!-- Pengguna -->
                <td>
                  <div class="flex items-center gap-2" style="min-width:0">
                    <div class="um-avatar" :class="u.role === 'admin' ? 'um-avatar-admin' : 'um-avatar-petugas'">
                      {{ initials(u.name) }}
                    </div>
                    <div style="min-width:0">
                      <div class="font-semibold truncate" style="color:var(--text-primary);max-width:150px">
                        {{ u.name }}
                        <span v-if="u.id === currentUserId" class="um-you-badge">Saya</span>
                      </div>
                      <div class="text-xs text-muted um-show-sm truncate" style="max-width:150px">{{ u.email }}</div>
                    </div>
                  </div>
                </td>

                <!-- Email -->
                <td class="um-hide-sm text-sm">
                  <span class="truncate" style="display:block;max-width:180px">{{ u.email }}</span>
                </td>

                <!-- Role -->
                <td>
                  <div class="um-role-cell">
                    <span class="badge" :class="u.role === 'admin' ? 'badge-primary' : 'badge-success'">
                      {{ u.role === 'admin' ? '⚡ Admin' : '👤 Petugas' }}
                    </span>
                    <!-- Quick role toggle (hanya jika bukan diri sendiri) -->
                    <button
                      v-if="u.id !== currentUserId"
                      class="um-quick-btn"
                      @click="quickChangeRole(u)"
                      :title="`Ubah ke ${u.role === 'admin' ? 'Petugas' : 'Admin'}`"
                      :disabled="quickLoading === `role-${u.id}`"
                    >
                      <div v-if="quickLoading === `role-${u.id}`" class="spinner spinner-sm"></div>
                      <svg v-else width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                      </svg>
                    </button>
                  </div>
                </td>

                <!-- No HP -->
                <td class="um-hide-md text-sm">{{ u.no_hp || '—' }}</td>

                <!-- Status -->
                <td>
                  <button
                    class="um-status-toggle"
                    :class="u.is_active ? 'um-status-active' : 'um-status-inactive'"
                    @click="u.id !== currentUserId && quickToggleStatus(u)"
                    :disabled="u.id === currentUserId || quickLoading === `status-${u.id}`"
                    :title="u.id === currentUserId ? 'Tidak bisa menonaktifkan akun sendiri' : (u.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan')"
                  >
                    <div v-if="quickLoading === `status-${u.id}`" class="spinner spinner-sm"></div>
                    <template v-else>
                      <span class="status-dot" :class="u.is_active ? 'aktif' : 'nonaktif'"></span>
                      {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                    </template>
                  </button>
                </td>

                <!-- Bergabung -->
                <td class="um-hide-md text-sm text-muted">
                  {{ fmtDate(u.created_at) }}
                </td>

                <!-- Aksi -->
                <td>
                  <div class="table-actions" style="justify-content:flex-end">
                    <!-- Detail -->
                    <button class="btn btn-ghost btn-icon-sm" @click="openDetail(u)" title="Detail">
                      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                    </button>
                    <!-- Edit -->
                    <button class="btn btn-outline btn-sm" @click="openEdit(u)" :title="`Edit ${u.name}`">
                      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                      <span class="um-hide-sm">Edit</span>
                    </button>
                    <!-- Reset Password -->
                    <button
                      class="btn btn-outline btn-sm"
                      @click="openResetPwd(u)"
                      :title="`Reset password ${u.name}`"
                      style="color:var(--warning)"
                    >
                      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                      </svg>
                      <span class="um-hide-sm">Reset</span>
                    </button>
                    <!-- Hapus -->
                    <button
                      class="btn btn-outline btn-sm"
                      @click="confirmDelete(u)"
                      :disabled="u.id === currentUserId"
                      :title="u.id === currentUserId ? 'Tidak dapat menghapus akun sendiri' : `Hapus ${u.name}`"
                      style="color:var(--danger)"
                    >
                      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                      <span class="um-hide-sm">Hapus</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="card-footer flex items-center justify-between flex-wrap gap-2" v-if="pagination.last_page > 1">
          <div class="text-sm text-muted">
            Hal. {{ pagination.current_page }} / {{ pagination.last_page }}
            &nbsp;·&nbsp; {{ pagination.total }} pengguna
          </div>
          <div class="pagination">
            <button class="page-btn" :disabled="pagination.current_page <= 1"
                    @click="goPage(pagination.current_page - 1)">‹</button>
            <button v-for="p in visiblePages" :key="p" class="page-btn"
                    :class="{ active: p === pagination.current_page }"
                    @click="goPage(p)">{{ p }}</button>
            <button class="page-btn" :disabled="pagination.current_page >= pagination.last_page"
                    @click="goPage(pagination.current_page + 1)">›</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: TAMBAH / EDIT ===== -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal modal-lg">
        <div class="modal-header">
          <div class="modal-title">{{ isEdit ? '✏️ Edit Pengguna' : '➕ Tambah Pengguna Baru' }}</div>
          <button class="btn btn-ghost btn-icon" @click="showModal = false">✕</button>
        </div>
        <div class="modal-body">
          <!-- Preview strip (edit mode) -->
          <div v-if="isEdit" class="um-preview-strip mb-4">
            <div class="um-avatar um-avatar-lg"
                 :class="form.role === 'admin' ? 'um-avatar-admin' : 'um-avatar-petugas'">
              {{ form.name ? initials(form.name) : '?' }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold" style="color:var(--text-primary)">{{ form.name || 'Nama Pengguna' }}</div>
              <div class="text-xs text-muted">{{ form.email || 'email@example.com' }}</div>
            </div>
            <span class="badge" :class="form.role === 'admin' ? 'badge-primary' : 'badge-success'">
              {{ form.role === 'admin' ? '⚡ Admin' : '👤 Petugas' }}
            </span>
          </div>

          <div class="form-grid">
            <!-- Nama -->
            <div class="form-group col-span-2">
              <label class="form-label">Nama Lengkap <span class="required">*</span></label>
              <input v-model="form.name" class="form-control" :class="{ 'is-invalid': errors.name }"
                     placeholder="Nama lengkap pengguna" id="input-name"/>
              <div class="invalid-feedback">{{ errors.name?.[0] }}</div>
            </div>

            <!-- Email -->
            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <input type="email" v-model="form.email" class="form-control"
                     :class="{ 'is-invalid': errors.email }"
                     placeholder="email@simrs.id" id="input-email"/>
              <div class="invalid-feedback">{{ errors.email?.[0] }}</div>
            </div>

            <!-- No HP -->
            <div class="form-group">
              <label class="form-label">No. HP</label>
              <input v-model="form.no_hp" class="form-control" placeholder="08xxxxxxxxxx" maxlength="15"/>
            </div>

            <!-- Role -->
            <div class="form-group">
              <label class="form-label">Role <span class="required">*</span></label>
              <select v-model="form.role" class="form-select" :class="{ 'is-invalid': errors.role }">
                <option value="petugas">👤 Petugas</option>
                <option value="admin">⚡ Admin</option>
              </select>
              <div class="invalid-feedback">{{ errors.role?.[0] }}</div>
              <!-- Role description -->
              <div class="um-role-desc">
                <template v-if="form.role === 'admin'">
                  <strong>Admin</strong> memiliki akses penuh termasuk manajemen master data, pengguna, dan laporan.
                </template>
                <template v-else>
                  <strong>Petugas</strong> dapat melakukan pendaftaran, pembayaran, dan melihat jadwal dokter.
                </template>
              </div>
            </div>

            <!-- Status -->
            <div class="form-group">
              <label class="form-label">Status Akun</label>
              <select v-model="form.is_active" class="form-select">
                <option :value="true">✅ Aktif</option>
                <option :value="false">🚫 Nonaktif</option>
              </select>
            </div>

            <!-- Password -->
            <div class="form-group">
              <label class="form-label">
                Password
                <span v-if="!isEdit" class="required">*</span>
                <span v-else class="text-muted" style="font-weight:400;font-size:0.73rem"> (kosongkan jika tidak diubah)</span>
              </label>
              <div style="position:relative">
                <input :type="showPwd ? 'text' : 'password'" v-model="form.password" class="form-control"
                       :class="{ 'is-invalid': errors.password }"
                       :placeholder="isEdit ? 'Kosongkan jika tidak diubah' : 'Min. 8 karakter'"
                       style="padding-right:2.5rem" id="input-password"/>
                <button type="button" @click="showPwd = !showPwd" class="um-pwd-eye">
                  <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path v-if="!showPwd" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                  </svg>
                </button>
              </div>
              <div class="invalid-feedback">{{ errors.password?.[0] }}</div>
            </div>

            <!-- Konfirmasi Password -->
            <div class="form-group">
              <label class="form-label">Konfirmasi Password</label>
              <input :type="showPwd ? 'text' : 'password'" v-model="form.password_confirmation"
                     class="form-control" :class="{ 'is-invalid': pwdMismatch }"
                     placeholder="Ulangi password"/>
              <div v-if="pwdMismatch" class="invalid-feedback">Password tidak cocok</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showModal = false">Batal</button>
          <button class="btn btn-primary" @click="submit" :disabled="saving || pwdMismatch" id="btn-submit-user">
            <div class="spinner spinner-sm" v-if="saving"></div>
            {{ isEdit ? 'Simpan Perubahan' : 'Buat Akun' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: DETAIL ===== -->
    <div v-if="showDetail && detailUser" class="modal-overlay" @click.self="showDetail = false">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title">👤 Detail Pengguna</div>
          <button class="btn btn-ghost btn-icon" @click="showDetail = false">✕</button>
        </div>
        <div class="modal-body">
          <!-- Profile header -->
          <div class="um-detail-header">
            <div class="um-avatar um-avatar-xl"
                 :class="detailUser.role === 'admin' ? 'um-avatar-admin' : 'um-avatar-petugas'">
              {{ initials(detailUser.name) }}
            </div>
            <div>
              <div class="um-detail-name">{{ detailUser.name }}</div>
              <div class="text-sm text-muted">{{ detailUser.email }}</div>
            </div>
          </div>

          <div class="divider"></div>

          <!-- Info grid -->
          <div class="um-detail-grid">
            <div class="um-detail-item">
              <div class="um-detail-label">Role</div>
              <span class="badge" :class="detailUser.role === 'admin' ? 'badge-primary' : 'badge-success'">
                {{ detailUser.role === 'admin' ? '⚡ Administrator' : '👤 Petugas' }}
              </span>
            </div>
            <div class="um-detail-item">
              <div class="um-detail-label">Status</div>
              <span class="badge" :class="detailUser.is_active ? 'badge-success' : 'badge-danger'">
                <span class="status-dot" :class="detailUser.is_active ? 'aktif' : 'nonaktif'"></span>
                {{ detailUser.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </div>
            <div class="um-detail-item">
              <div class="um-detail-label">No. HP</div>
              <div class="um-detail-value">{{ detailUser.no_hp || '—' }}</div>
            </div>
            <div class="um-detail-item">
              <div class="um-detail-label">Bergabung</div>
              <div class="um-detail-value">{{ fmtDate(detailUser.created_at) }}</div>
            </div>
            <div class="um-detail-item">
              <div class="um-detail-label">Terakhir diperbarui</div>
              <div class="um-detail-value">{{ fmtDate(detailUser.updated_at) }}</div>
            </div>
            <div class="um-detail-item" v-if="detailUser.id === currentUserId">
              <div class="um-detail-label">Akun Saya</div>
              <span class="badge badge-info">✓ Saat ini login</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showDetail = false">Tutup</button>
          <button class="btn btn-warning" @click="openResetPwd(detailUser); showDetail = false"
                  v-if="detailUser.id !== currentUserId">
            🔑 Reset Password
          </button>
          <button class="btn btn-primary" @click="openEdit(detailUser); showDetail = false">
            ✏️ Edit
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: RESET PASSWORD ===== -->
    <div v-if="showResetPwd" class="modal-overlay" @click.self="showResetPwd = false">
      <div class="modal" style="max-width:440px">
        <div class="modal-header">
          <div class="modal-title">🔑 Reset Password</div>
          <button class="btn btn-ghost btn-icon" @click="showResetPwd = false">✕</button>
        </div>
        <div class="modal-body">
          <div class="um-preview-strip mb-4" style="background:var(--warning-bg);border-color:rgba(245,158,11,0.25)">
            <div class="um-avatar" :class="resetTarget?.role === 'admin' ? 'um-avatar-admin' : 'um-avatar-petugas'"
                 style="width:36px;height:36px;font-size:0.8rem">
              {{ resetTarget ? initials(resetTarget.name) : '' }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold" style="font-size:0.875rem;color:var(--text-primary)">{{ resetTarget?.name }}</div>
              <div class="text-xs text-muted">{{ resetTarget?.email }}</div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Password Baru <span class="required">*</span></label>
            <div style="position:relative">
              <input :type="showNewPwd ? 'text' : 'password'" v-model="pwdForm.password"
                     class="form-control" :class="{ 'is-invalid': resetErrors.password }"
                     placeholder="Min. 8 karakter" style="padding-right:2.5rem"/>
              <button type="button" @click="showNewPwd = !showNewPwd" class="um-pwd-eye">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path v-if="!showNewPwd" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
              </button>
            </div>
            <div class="invalid-feedback">{{ resetErrors.password?.[0] }}</div>
          </div>
          <div class="form-group">
            <label class="form-label">Konfirmasi Password Baru <span class="required">*</span></label>
            <input :type="showNewPwd ? 'text' : 'password'" v-model="pwdForm.password_confirmation"
                   class="form-control"
                   :class="{ 'is-invalid': pwdForm.password && pwdForm.password !== pwdForm.password_confirmation }"
                   placeholder="Ulangi password baru"/>
            <div v-if="pwdForm.password && pwdForm.password !== pwdForm.password_confirmation"
                 class="invalid-feedback">Password tidak cocok</div>
          </div>

          <!-- Password strength -->
          <div v-if="pwdForm.password" class="um-pwd-strength">
            <div class="um-pwd-strength-bar">
              <div class="um-pwd-strength-fill" :class="`strength-${pwdStrength.level}`"
                   :style="{ width: pwdStrength.percent + '%' }"></div>
            </div>
            <span class="um-pwd-strength-label" :class="`strength-text-${pwdStrength.level}`">
              {{ pwdStrength.label }}
            </span>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showResetPwd = false">Batal</button>
          <button class="btn btn-warning" @click="doResetPassword"
                  :disabled="savingReset || !pwdForm.password || pwdForm.password !== pwdForm.password_confirmation">
            <div class="spinner spinner-sm" v-if="savingReset"></div>
            🔑 Reset Password
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: HAPUS ===== -->
    <div v-if="showDelete" class="modal-overlay" @click.self="showDelete = false">
      <div class="modal" style="max-width:420px">
        <div class="modal-header">
          <div class="modal-title">🗑️ Hapus Pengguna</div>
          <button class="btn btn-ghost btn-icon" @click="showDelete = false">✕</button>
        </div>
        <div class="modal-body">
          <p class="text-sm" style="color:var(--text-secondary)">
            Anda akan menghapus akun pengguna
            <strong style="color:var(--text-primary)">{{ deleteTarget?.name }}</strong>
            <span class="text-muted">({{ deleteTarget?.email }})</span>.
          </p>
          <div class="alert alert-danger mt-4">
            ⚠️ Tindakan ini <strong>tidak dapat dibatalkan</strong>. Semua data terkait pengguna ini mungkin terpengaruh.
          </div>
          <!-- Type to confirm -->
          <div class="form-group mt-4">
            <label class="form-label">Ketik <strong>{{ deleteTarget?.name }}</strong> untuk mengkonfirmasi</label>
            <input v-model="deleteConfirm" class="form-control" :placeholder="deleteTarget?.name"/>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline" @click="showDelete = false; deleteConfirm = ''">Batal</button>
          <button class="btn btn-danger" @click="doDelete"
                  :disabled="saving || deleteConfirm !== deleteTarget?.name" id="btn-confirm-delete">
            <div class="spinner spinner-sm" v-if="saving"></div>
            Hapus Permanen
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

const { get, post, put, del, loading, errors } = useApi();
const { success: toastOk, error: toastErr, warning: toastWarn } = useToast();

// ---- State ----
const users         = ref([]);
const pagination    = ref({});
const stats         = ref({});
const search        = ref('');
const filterRole    = ref('');
const filterStatus  = ref('');
const currentPage   = ref(1);
const quickLoading  = ref(null);

// Modal state
const showModal    = ref(false);
const showDelete   = ref(false);
const showDetail   = ref(false);
const showResetPwd = ref(false);
const isEdit       = ref(false);
const saving       = ref(false);
const savingReset  = ref(false);
const showPwd      = ref(false);
const showNewPwd   = ref(false);

const deleteTarget  = ref(null);
const deleteConfirm = ref('');
const detailUser    = ref(null);
const resetTarget   = ref(null);
const resetErrors   = ref({});

const currentUserId = window.__AUTH_USER__?.id;

// ---- Stat cards config ----
const statCards = [
  { key: 'total',    label: 'Total Pengguna',  icon: '👥', bg: 'var(--primary-bg)',  filter: '', roleFilter: '' },
  { key: 'admin',    label: 'Administrator',   icon: '⚡', bg: 'var(--primary-bg)',  filter: '', roleFilter: 'admin' },
  { key: 'petugas',  label: 'Petugas',         icon: '👤', bg: 'var(--success-bg)',  filter: '', roleFilter: 'petugas' },
  { key: 'aktif',    label: 'Akun Aktif',      icon: '✅', bg: 'var(--success-bg)',  filter: 'aktif', roleFilter: '' },
  { key: 'nonaktif', label: 'Akun Nonaktif',   icon: '🚫', bg: 'var(--danger-bg)',   filter: 'nonaktif', roleFilter: '' },
];

// ---- Forms ----
const defaultForm = () => ({
  name: '', email: '', no_hp: '', role: 'petugas',
  is_active: true, password: '', password_confirmation: '',
});
const form    = ref(defaultForm());
const pwdForm = ref({ password: '', password_confirmation: '' });

// ---- Computed ----
const hasFilter = computed(() => !!(search.value || filterRole.value || filterStatus.value));

const pwdMismatch = computed(() =>
  !!form.value.password && form.value.password !== form.value.password_confirmation
);

const visiblePages = computed(() => {
  const total = pagination.value.last_page ?? 1;
  const cur   = pagination.value.current_page ?? 1;
  const pages = [];
  for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) pages.push(i);
  return pages;
});

// Password strength meter
const pwdStrength = computed(() => {
  const p = pwdForm.value.password;
  if (!p) return { level: 0, percent: 0, label: '' };
  let score = 0;
  if (p.length >= 8)  score++;
  if (p.length >= 12) score++;
  if (/[A-Z]/.test(p)) score++;
  if (/[0-9]/.test(p)) score++;
  if (/[^A-Za-z0-9]/.test(p)) score++;
  const levels = ['', 'Sangat Lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat Kuat'];
  return { level: score, percent: (score / 5) * 100, label: levels[score] || 'Lemah' };
});

// ---- Helpers ----
function initials(name) {
  return (name ?? 'U').split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}
function fmtDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

// ---- Data fetching ----
let debounceTimer;
function debouncedFetch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => { currentPage.value = 1; fetchData(); }, 400);
}

function doFetch() { currentPage.value = 1; fetchData(); }

async function fetchData() {
  const res = await get('/api/users', {
    search: search.value || undefined,
    role:   filterRole.value || undefined,
    status: filterStatus.value || undefined,
    page:   currentPage.value,
  });
  if (res.success) {
    users.value      = res.data.data;
    pagination.value = res.data;
  }
}

async function fetchStats() {
  const res = await get('/api/users/stats');
  if (res.success) stats.value = res.data;
}

function goPage(p) { currentPage.value = p; fetchData(); }

function clearFilter() {
  search.value = '';
  filterRole.value = '';
  filterStatus.value = '';
  currentPage.value = 1;
  fetchData();
}

function applyStatFilter(s) {
  filterRole.value   = s.roleFilter;
  filterStatus.value = s.filter;
  search.value = '';
  currentPage.value = 1;
  fetchData();
}

// ---- Modal openers ----
function openCreate() {
  form.value = defaultForm();
  isEdit.value = false;
  showPwd.value = false;
  errors.value = {};
  showModal.value = true;
}

function openEdit(u) {
  form.value = {
    name: u.name, email: u.email, no_hp: u.no_hp ?? '',
    role: u.role, is_active: u.is_active,
    password: '', password_confirmation: '',
    _id: u.id,
  };
  isEdit.value    = true;
  showPwd.value   = false;
  errors.value    = {};
  showModal.value = true;
}

function openDetail(u) {
  detailUser.value = u;
  showDetail.value = true;
}

function openResetPwd(u) {
  resetTarget.value = u;
  pwdForm.value     = { password: '', password_confirmation: '' };
  resetErrors.value = {};
  showNewPwd.value  = false;
  showResetPwd.value = true;
}

function confirmDelete(u) {
  deleteTarget.value  = u;
  deleteConfirm.value = '';
  showDelete.value    = true;
}

// ---- CRUD ----
async function submit() {
  if (pwdMismatch.value) { toastErr('Password dan konfirmasi tidak cocok.'); return; }
  saving.value = true;
  const res = isEdit.value
    ? await put(`/api/users/${form.value._id}`, form.value)
    : await post('/api/users', form.value);
  saving.value = false;
  if (res.success) {
    toastOk(res.data.message ?? 'Data berhasil disimpan.');
    showModal.value = false;
    fetchData();
    fetchStats();
  } else {
    toastErr(res.error ?? 'Terjadi kesalahan.');
  }
}

async function doDelete() {
  saving.value = true;
  const res = await del(`/api/users/${deleteTarget.value.id}`);
  saving.value = false;
  if (res.success) {
    toastOk(res.data.message ?? 'Pengguna berhasil dihapus.');
    showDelete.value    = false;
    deleteConfirm.value = '';
    fetchData();
    fetchStats();
  } else {
    toastErr(res.error ?? 'Gagal menghapus pengguna.');
  }
}

async function doResetPassword() {
  if (pwdForm.value.password !== pwdForm.value.password_confirmation) {
    toastErr('Password tidak cocok.'); return;
  }
  savingReset.value = true;
  resetErrors.value = {};
  try {
    const res = await axios.post(`/api/users/${resetTarget.value.id}/reset-password`, pwdForm.value);
    toastOk(res.data.message ?? 'Password berhasil direset.');
    showResetPwd.value = false;
  } catch (err) {
    if (err.response?.status === 422) {
      resetErrors.value = err.response.data.errors ?? {};
    }
    toastErr(err.response?.data?.message ?? 'Gagal reset password.');
  } finally {
    savingReset.value = false;
  }
}

// ---- Quick actions ----
async function quickToggleStatus(u) {
  quickLoading.value = `status-${u.id}`;
  try {
    const res = await axios.patch(`/api/users/${u.id}/toggle-status`);
    u.is_active = res.data.is_active;
    toastOk(res.data.message);
    fetchStats();
  } catch (err) {
    toastErr(err.response?.data?.message ?? 'Gagal mengubah status.');
  } finally {
    quickLoading.value = null;
  }
}

async function quickChangeRole(u) {
  const newRole = u.role === 'admin' ? 'petugas' : 'admin';
  quickLoading.value = `role-${u.id}`;
  try {
    const res = await axios.patch(`/api/users/${u.id}/change-role`, { role: newRole });
    u.role = newRole;
    toastOk(res.data.message);
    fetchStats();
  } catch (err) {
    toastErr(err.response?.data?.message ?? 'Gagal mengubah role.');
  } finally {
    quickLoading.value = null;
  }
}

// ---- Export CSV ----
function exportCSV() {
  const headers = ['Nama', 'Email', 'Role', 'No. HP', 'Status', 'Bergabung'];
  const rows = users.value.map(u => [
    u.name,
    u.email,
    u.role,
    u.no_hp ?? '',
    u.is_active ? 'Aktif' : 'Nonaktif',
    fmtDate(u.created_at),
  ]);
  const csv = [headers, ...rows].map(r => r.map(v => `"${v}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = `pengguna-simrs-${new Date().toISOString().slice(0,10)}.csv`;
  a.click();
  URL.revokeObjectURL(url);
  toastOk('Data berhasil diekspor ke CSV.');
}

// ---- Init ----
onMounted(() => {
  fetchData();
  fetchStats();
});
</script>

<style scoped>
/* ---- Stat Cards ---- */
.um-stats-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.75rem;
}
@media (max-width: 900px) { .um-stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 560px) { .um-stats-grid { grid-template-columns: repeat(2, 1fr); } }

.um-stat-card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 0.875rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: all var(--transition);
}
.um-stat-card:hover,
.um-stat-card.active {
  border-color: var(--primary);
  background: var(--primary-bg);
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}
.um-stat-icon {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.um-stat-value {
  font-family: var(--font-heading);
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1;
}
.um-stat-label {
  font-size: 0.7rem;
  color: var(--text-muted);
  font-weight: 500;
  margin-top: 0.1rem;
}

/* ---- Avatar ---- */
.um-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.7rem;
  color: white;
  flex-shrink: 0;
}
.um-avatar-admin   { background: linear-gradient(135deg, var(--primary), hsl(250, 80%, 65%)); }
.um-avatar-petugas { background: linear-gradient(135deg, var(--success), hsl(160, 70%, 40%)); }
.um-avatar-lg  { width: 44px; height: 44px; font-size: 0.9rem; }
.um-avatar-xl  { width: 56px; height: 56px; font-size: 1.1rem; }

/* ---- Role cell ---- */
.um-role-cell {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}
.um-quick-btn {
  width: 22px;
  height: 22px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  background: var(--bg-elevated);
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all var(--transition);
  flex-shrink: 0;
}
.um-quick-btn:hover { background: var(--primary-bg); color: var(--primary); border-color: var(--primary); }
.um-quick-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* ---- Status toggle ---- */
.um-status-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.2rem 0.55rem;
  border-radius: 100px;
  font-size: 0.71rem;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: all var(--transition);
  white-space: nowrap;
  font-family: var(--font-base);
}
.um-status-active  { background: var(--success-bg); color: var(--success); }
.um-status-inactive { background: var(--danger-bg);  color: var(--danger); }
.um-status-active:hover:not(:disabled)  { background: var(--success); color: #fff; }
.um-status-inactive:hover:not(:disabled) { background: var(--danger);  color: #fff; }
.um-status-toggle:disabled { cursor: not-allowed; opacity: 0.7; }

/* ---- Table row dim for inactive ---- */
.um-row-inactive td { opacity: 0.6; }
.um-row-inactive:hover td { opacity: 1; }

/* ---- "Saya" badge ---- */
.um-you-badge {
  display: inline-block;
  font-size: 0.6rem;
  padding: 0.1rem 0.35rem;
  border-radius: 100px;
  background: var(--primary-bg);
  color: var(--primary);
  font-weight: 700;
  margin-left: 0.25rem;
  vertical-align: middle;
}

/* ---- Card title group ---- */
.um-card-title-group { display: flex; flex-direction: column; gap: 0.15rem; }

/* ---- Detail modal ---- */
.um-detail-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}
.um-detail-name {
  font-family: var(--font-heading);
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
}
.um-detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.um-detail-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}
.um-detail-value {
  font-size: 0.875rem;
  color: var(--text-secondary);
  font-weight: 500;
}

/* ---- Preview strip ---- */
.um-preview-strip {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: var(--radius-md);
  background: var(--bg-elevated);
  border: 1px solid var(--border);
}

/* ---- Role description ---- */
.um-role-desc {
  font-size: 0.76rem;
  color: var(--text-muted);
  margin-top: 0.375rem;
  line-height: 1.5;
}

/* ---- Password eye toggle ---- */
.um-pwd-eye {
  position: absolute;
  right: 0.625rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-muted);
  padding: 0;
  display: flex;
  align-items: center;
}
.um-pwd-eye:hover { color: var(--text-primary); }

/* ---- Password strength ---- */
.um-pwd-strength {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.375rem;
}
.um-pwd-strength-bar {
  flex: 1;
  height: 4px;
  border-radius: 2px;
  background: var(--bg-elevated);
  overflow: hidden;
}
.um-pwd-strength-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.3s ease, background 0.3s ease;
}
.strength-1 { background: var(--danger); }
.strength-2 { background: var(--warning); }
.strength-3 { background: var(--warning); }
.strength-4 { background: var(--success); }
.strength-5 { background: var(--success); }
.um-pwd-strength-label { font-size: 0.72rem; font-weight: 600; white-space: nowrap; }
.strength-text-1 { color: var(--danger); }
.strength-text-2 { color: var(--warning); }
.strength-text-3 { color: var(--warning); }
.strength-text-4 { color: var(--success); }
.strength-text-5 { color: var(--success); }

/* ---- Responsive hide/show ---- */
.um-hide-sm { }
.um-hide-md { }
.um-show-sm { display: none; }

@media (max-width: 767px) {
  .um-hide-md { display: none; }
}
@media (max-width: 560px) {
  .um-hide-sm { display: none; }
  .um-show-sm { display: block; }
  .um-detail-grid { grid-template-columns: 1fr; }
}
</style>
