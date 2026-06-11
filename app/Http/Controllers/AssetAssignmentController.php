<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use App\Models\Assignment;
use App\Models\DokumentasiAset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class AssetAssignmentController extends Controller
{
    // ════════════════════════════════════════════════════════════════════════
    // HELPER: Kirim WhatsApp via Fonnte
    // ════════════════════════════════════════════════════════════════════════

    private function kirimWA(User $user, string $pesan): void
    {
        if (!$user->no_telepon) return;

        $nomor = trim(preg_replace('/[^0-9]/', '', $user->no_telepon));
        if (!$nomor) return;

        Http::asForm()
            ->withoutVerifying()
            ->withHeaders(['Authorization' => env('FONNTE_TOKEN', '')])
            ->post('https://api.fonnte.com/send', [
                'target'  => $nomor,
                'message' => $pesan,
            ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    // INDEX — Halaman Asset Data & Riwayat Tugas
    // ════════════════════════════════════════════════════════════════════════

    public function index()
    {
        $assignments = Assignment::with(['aset', 'receiver'])->latest()->get();
        $timUsers    = User::whereIn('role', ['tim', 'officer', 'staff', 'technical support'])->get();

        $saranBrand = DB::table('asset_attributes')
            ->where('attribute_name', 'brand')
            ->distinct()->pluck('attribute_value')->toArray();

        $saranTipe = DB::table('asset_attributes')
            ->where('attribute_name', 'tipe')
            ->distinct()->pluck('attribute_value')->toArray();

        return view('master_aset', compact('assignments', 'timUsers', 'saranBrand', 'saranTipe'));
    }

    // ════════════════════════════════════════════════════════════════════════
    // 1. SUPERVISOR — Buat tugas baru + kirim notif WA
    // ════════════════════════════════════════════════════════════════════════

    public function storeTask(Request $request)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'deskripsi'   => 'required|string',
        ]);

        $deskripsiInput = $request->input('deskripsi');

        DB::transaction(function () use ($request, $deskripsiInput) {
            $aset = Aset::create([
                'no_aset'    => 'AST-' . strtoupper(Str::random(8)),
                'rfid_code'  => null,
                'status'     => 'pending',
                'created_by' => Auth::id(),
            ]);

            Assignment::create([
                'aset_id'     => $aset->id,
                'assigned_by' => Auth::id(),
                'assigned_to' => $request->assigned_to,
                'jenis_tugas' => 'input_baru',
                'status'      => 'pending',
                'deskripsi'   => $deskripsiInput,
                'assigned_at' => now(),
            ]);

            $userTim = User::find($request->assigned_to);
            if ($userTim) {
                $this->kirimWA(
                    $userTim,
                    "🚨 *NOTIFIKASI PENUGASAN BARU* 🚨\n\n"
                    . "Halo *{$userTim->nama}*,\n"
                    . "Supervisor memberikan instruksi kerja baru:\n"
                    . "📝 _\"{$deskripsiInput}\"_\n\n"
                    . "Silakan buka sistem, lakukan pengecekan barang fisik, "
                    . "dan input spesifikasi lengkap beserta dokumentasi foto kondisi barang "
                    . "untuk meng-generate label QR Code."
                );
            }
        });

        return redirect()
            ->route('master_aset.index')
            ->with('toast_success', 'Tugas logistik baru berhasil diterbitkan dan notifikasi WA terkirim!');
    }

    // ════════════════════════════════════════════════════════════════════════
    // SUPERVISOR — Update / Edit penugasan + kirim notif WA
    // ════════════════════════════════════════════════════════════════════════

    public function updateTask(Request $request, $id)
    {
        $request->validate([
            'deskripsi'   => 'required|string',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $assignment = Assignment::findOrFail($id);
        $assignment->update([
            'deskripsi'   => $request->deskripsi,
            'assigned_to' => $request->assigned_to,
        ]);

        $userTim = User::find($request->assigned_to);
        if ($userTim) {
            $this->kirimWA(
                $userTim,
                "📝 *NOTIFIKASI PERUBAHAN TUGAS* 📝\n\n"
                . "Halo *{$userTim->nama}*,\n"
                . "Supervisor telah memperbarui instruksi kerja Anda:\n"
                . "📋 _\"{$request->deskripsi}\"_\n\n"
                . "Silakan buka sistem untuk melihat detail perubahan."
            );
        }

        return redirect()
            ->route('master_aset.index')
            ->with('toast_success', 'Data tugas berhasil diperbarui dan notifikasi WA terkirim!');
    }

    // ════════════════════════════════════════════════════════════════════════
    // SUPERVISOR — Hapus permanen data penugasan
    // ════════════════════════════════════════════════════════════════════════

    public function destroyTask($id)
    {
        DB::transaction(function () use ($id) {
            $assignment = Assignment::findOrFail($id);
            $asetId     = $assignment->aset_id;

            // Hapus file foto dari storage sebelum record dihapus
            $dokList = DokumentasiAset::where('aset_id', $asetId)->get();
            foreach ($dokList as $dok) {
                Storage::disk('public')->delete($dok->url_foto);
            }

            $assignment->delete();
            DB::table('asset_attributes')->where('aset_id', $asetId)->delete();
            DB::table('dokumentasi_aset')->where('aset_id', $asetId)->delete();
            Aset::where('id', $asetId)->delete();
        });

        return redirect()
            ->route('master_aset.index')
            ->with('toast_success', 'Data tugas, draf aset, dan dokumentasi foto berhasil dihapus dari database.');
    }

    // ════════════════════════════════════════════════════════════════════════
    // 2. TIM LAPANGAN — Konfirmasi terima tugas
    // ════════════════════════════════════════════════════════════════════════

    public function confirmTask($id)
    {
        $assignment = Assignment::findOrFail($id);
        $assignment->update([
            'status'       => 'process',
            'confirmed_at' => now(),
        ]);

        return redirect()
            ->route('master_aset.index')
            ->with('toast_success', 'Tugas diterima! Silakan isi spesifikasi dan dokumentasi foto barang.');
    }

    // ════════════════════════════════════════════════════════════════════════
    // 3. TIM LAPANGAN — Submit spesifikasi + dokumentasi foto + generate QR
    // ════════════════════════════════════════════════════════════════════════

    public function submitAssetData(Request $request, $id)
    {
        $request->validate([
            // Spesifikasi utama
            'no_sap'                 => 'required|string',
            'jenis'                  => 'required|string',
            'brand'                  => 'required|string',
            'tipe'                   => 'required|string',
            'tahun_pembelian'        => 'required|date',
            'supplier'               => 'required|string',
            'status_kondisi'         => 'required|string',
            'nama_pengguna'          => 'required|string',
            'lokasi'                 => 'required|string',
            'ruang'                  => 'required|string',
            'departemen'             => 'required|string',

            // Dokumentasi foto
            'kondisi_dokumentasi'    => 'required|in:baru,baik,rusak_ringan,rusak_berat',
            'foto_dokumentasi'       => 'required|array|min:1|max:5',
            'foto_dokumentasi.*'     => 'image|mimes:jpg,jpeg,png|max:2048',
            'keterangan_dokumentasi' => 'nullable|string|max:500',
        ], [
            'foto_dokumentasi.required'      => 'Minimal 1 foto dokumentasi wajib diunggah.',
            'foto_dokumentasi.min'           => 'Minimal 1 foto dokumentasi wajib diunggah.',
            'foto_dokumentasi.max'           => 'Maksimal 5 foto dokumentasi.',
            'foto_dokumentasi.*.image'       => 'File harus berupa gambar.',
            'foto_dokumentasi.*.mimes'       => 'Format foto harus JPG atau PNG.',
            'foto_dokumentasi.*.max'         => 'Ukuran setiap foto maksimal 2MB.',
            'kondisi_dokumentasi.required'   => 'Kondisi fisik barang wajib dipilih.',
        ]);

        DB::transaction(function () use ($request, $id) {
            $assignment = Assignment::findOrFail($id);
            $aset       = Aset::findOrFail($assignment->aset_id);

            // ── Generate QR Code identifier ──────────────────────────────
            $generatedQrCode = strtoupper($request->jenis) . '-' . trim($request->no_sap);

            $aset->update([
                'no_sap'    => $request->no_sap,
                'rfid_code' => $generatedQrCode,
                'status'    => 'pending_approval',
            ]);

            // ── Simpan attribute spesifikasi ─────────────────────────────
            DB::table('asset_attributes')->where('aset_id', $aset->id)->delete();

            $skipKeys = [
                '_token', 'no_sap', 'no_asset_internal',
                'kondisi_dokumentasi', 'keterangan_dokumentasi', 'foto_dokumentasi',
            ];

            foreach ($request->except($skipKeys) as $key => $value) {
                if ($value !== null && $value !== '') {
                    DB::table('asset_attributes')->insert([
                        'aset_id'         => $aset->id,
                        'attribute_name'  => $key,
                        'attribute_value' => $value,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }

            // ── Upload & simpan setiap foto ke dokumentasi_aset ──────────
            // Hapus dulu foto lama jika ada (kasus revisi / submit ulang)
            $fotoLama = DokumentasiAset::where('aset_id', $aset->id)
                ->where('jenis_dokumentasi', 'input_aset')
                ->get();

            foreach ($fotoLama as $lama) {
                Storage::disk('public')->delete($lama->url_foto);
            }

            DokumentasiAset::where('aset_id', $aset->id)
                ->where('jenis_dokumentasi', 'input_aset')
                ->delete();

            foreach ($request->file('foto_dokumentasi') as $foto) {
                // Simpan di storage/app/public/dokumentasi/{aset_id}/
                $path = $foto->store(
                    'dokumentasi/' . $aset->id,
                    'public'
                );

                DokumentasiAset::create([
                    'aset_id'           => $aset->id,
                    'assignment_id'     => $assignment->id,
                    'jenis_dokumentasi' => 'input_aset',
                    'kondisi'           => $request->kondisi_dokumentasi,
                    'keterangan'        => $request->keterangan_dokumentasi,
                    'url_foto'          => $path,
                    'created_by'        => Auth::id(),
                ]);
            }

            // ── Notif WA ke Supervisor ───────────────────────────────────
            $supervisor = User::find($assignment->assigned_by);
            $timUser    = User::find($assignment->assigned_to);
            $namaTim    = $timUser->nama ?? 'Tim Lapangan';
            $jumlahFoto = count($request->file('foto_dokumentasi'));

            $kondisiLabel = match ($request->kondisi_dokumentasi) {
                'baru'         => 'Baru',
                'baik'         => 'Baik',
                'rusak_ringan' => 'Rusak Ringan',
                'rusak_berat'  => 'Rusak Berat',
                default        => $request->kondisi_dokumentasi,
            };

            if ($supervisor) {
                $this->kirimWA(
                    $supervisor,
                    "🔔 *PERMINTAAN VERIFIKASI ASET* 🔔\n\n"
                    . "Halo *{$supervisor->nama}*,\n"
                    . "*{$namaTim}* telah selesai menginput spesifikasi aset dan meminta persetujuan Anda.\n\n"
                    . "📦 *Detail Aset:*\n"
                    . "• Jenis    : {$request->jenis}\n"
                    . "• Brand    : {$request->brand} {$request->tipe}\n"
                    . "• No SAP   : {$request->no_sap}\n"
                    . "• QR Code  : {$generatedQrCode}\n"
                    . "• Kondisi  : {$kondisiLabel}\n"
                    . "• Foto     : {$jumlahFoto} file terlampir\n\n"
                    . "Silakan buka sistem untuk melakukan verifikasi (ACC / Tolak)."
                );
            }
        });

        return redirect()
            ->route('master_aset.index')
            ->with('toast_success', 'Spesifikasi & dokumentasi foto berhasil disimpan. Permintaan verifikasi terkirim ke Supervisor!');
    }

    // ════════════════════════════════════════════════════════════════════════
    // 4. SUPERVISOR — Verifikasi ACC / Tolak + notif WA ke Tim
    // ════════════════════════════════════════════════════════════════════════

    public function verifyApproval(Request $request, $id)
    {
        $request->validate([
            'status_keputusan' => 'required|in:acc,tolak',
            'catatan'          => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $assignment = Assignment::findOrFail($id);
            $aset       = Aset::findOrFail($assignment->aset_id);

            if ($request->status_keputusan === 'acc') {
                $aset->update(['status' => 'ready']);
                $assignment->update(['status' => 'completed']);
            } else {
                $aset->update(['status' => 'pending']);
                $assignment->update([
                    'status'       => 'process',
                    'confirmed_at' => now(),
                ]);
            }

            DB::table('asset_attributes')->updateOrInsert(
                ['aset_id' => $aset->id, 'attribute_name' => 'catatan_supervisor'],
                [
                    'attribute_value' => $request->catatan ?? 'Disetujui',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );

            // Notif WA ke Tim Lapangan
            $userTim = User::find($assignment->assigned_to);
            if ($userTim) {
                if ($request->status_keputusan === 'acc') {
                    $pesan = "✅ *ASET DISETUJUI (ACC)* ✅\n\n"
                           . "Halo *{$userTim->nama}*,\n"
                           . "Data spesifikasi dan dokumentasi foto aset yang Anda input "
                           . "telah *disetujui* oleh Supervisor.\n"
                           . "Aset sekarang berstatus *READY* dan valid masuk inventaris PT Wismilak.";
                } else {
                    $catatan = $request->catatan ?? '-';
                    $pesan   = "❌ *ASET DITOLAK / REVISI DATA* ❌\n\n"
                             . "Halo *{$userTim->nama}*,\n"
                             . "Data spesifikasi aset Anda *ditolak* oleh Supervisor dan perlu diperbaiki.\n\n"
                             . "📋 Alasan: _\"{$catatan}\"_\n\n"
                             . "Silakan buka sistem, perbaiki data dan unggah ulang foto dokumentasi sesuai catatan di atas.";
                }
                $this->kirimWA($userTim, $pesan);
            }
        });

        return redirect()
            ->route('master_aset.index')
            ->with('toast_success', 'Verifikasi berhasil disimpan dan notifikasi WA terkirim ke Tim!');
    }
}