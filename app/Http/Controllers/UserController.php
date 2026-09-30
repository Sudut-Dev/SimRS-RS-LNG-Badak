<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Daftar pengguna dengan filter, pencarian, dan paginasi.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'aktif');
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();

        return response()->json($users);
    }

    /**
     * Statistik ringkasan pengguna (untuk dashboard card).
     */
    public function stats()
    {
        return response()->json([
            'total'    => User::count(),
            'admin'    => User::where('role', 'admin')->count(),
            'petugas'  => User::where('role', 'petugas')->count(),
            'aktif'    => User::where('is_active', true)->count(),
            'nonaktif' => User::where('is_active', false)->count(),
        ]);
    }

    /**
     * Tambah pengguna baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:admin,petugas',
            'no_hp'    => 'nullable|string|max:15',
            'is_active'=> 'boolean',
        ]);

        $data['password']  = Hash::make($data['password']);
        $data['is_active'] = $data['is_active'] ?? true;

        $user = User::create($data);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan.',
            'data'    => $user,
        ], 201);
    }

    /**
     * Detail satu pengguna.
     */
    public function show(User $user)
    {
        return response()->json($user);
    }

    /**
     * Update data pengguna.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role'      => 'required|in:admin,petugas',
            'no_hp'     => 'nullable|string|max:15',
            'is_active' => 'boolean',
            'password'  => 'nullable|string|min:8|confirmed',
        ]);

        // Cegah admin mengubah role dirinya sendiri
        if ($user->id === auth()->id() && isset($data['role']) && $data['role'] !== auth()->user()->role) {
            return response()->json(['message' => 'Anda tidak dapat mengubah role diri sendiri.'], 422);
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui.',
            'data'    => $user->fresh(),
        ]);
    }

    /**
     * Toggle status aktif/nonaktif user secara langsung.
     */
    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Tidak dapat menonaktifkan akun sendiri.'], 422);
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'message'   => "Akun {$user->name} berhasil {$status}.",
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Ubah role user secara langsung (quick toggle).
     */
    public function changeRole(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Tidak dapat mengubah role diri sendiri.'], 422);
        }

        $data = $request->validate([
            'role' => 'required|in:admin,petugas',
        ]);

        $user->update($data);

        $roleLabel = $data['role'] === 'admin' ? 'Administrator' : 'Petugas';

        return response()->json([
            'message' => "Role {$user->name} diubah menjadi {$roleLabel}.",
            'data'    => $user->fresh(),
        ]);
    }

    /**
     * Reset password pengguna oleh admin.
     */
    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json([
            'message' => "Password {$user->name} berhasil direset.",
        ]);
    }

    /**
     * Hapus pengguna.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Anda tidak dapat menghapus akun Anda sendiri.'], 422);
        }

        $name = $user->name;
        $user->delete();

        return response()->json(['message' => "Pengguna {$name} berhasil dihapus."]);
    }
}
