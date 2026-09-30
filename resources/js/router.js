import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from './pages/Dashboard.vue';
import MasterPasien from './pages/MasterPasien.vue';
import MasterDokter from './pages/MasterDokter.vue';
import MasterPoli from './pages/MasterPoli.vue';
import JadwalDokter from './pages/JadwalDokter.vue';
import Pendaftaran from './pages/Pendaftaran.vue';
import Pembayaran from './pages/Pembayaran.vue';
import Laporan from './pages/Laporan.vue';
import ManajemenUser from './pages/ManajemenUser.vue';

const routes = [
    { path: '/', redirect: '/dashboard' },
    { path: '/dashboard', component: Dashboard, name: 'dashboard', meta: { title: 'Dashboard' } },
    { path: '/master/pasien', component: MasterPasien, name: 'pasien', meta: { title: 'Data Pasien' } },
    { path: '/master/dokter', component: MasterDokter, name: 'dokter', meta: { title: 'Data Dokter', adminOnly: true } },
    { path: '/master/poli', component: MasterPoli, name: 'poli', meta: { title: 'Data Poli', adminOnly: true } },
    { path: '/master/jadwal', component: JadwalDokter, name: 'jadwal', meta: { title: 'Jadwal Dokter' } },
    { path: '/transaksi/pendaftaran', component: Pendaftaran, name: 'pendaftaran', meta: { title: 'Pendaftaran Rawat Jalan' } },
    { path: '/transaksi/pembayaran', component: Pembayaran, name: 'pembayaran', meta: { title: 'Pembayaran & Kasir' } },
    { path: '/laporan', component: Laporan, name: 'laporan', meta: { title: 'Laporan & Analitik' } },
    { path: '/admin/users', component: ManajemenUser, name: 'users', meta: { title: 'Manajemen Pengguna', adminOnly: true } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Update page title
router.afterEach((to) => {
    document.title = (to.meta.title ? to.meta.title + ' - ' : '') + 'SIMRS RS LNG Badak';
});

export default router;
