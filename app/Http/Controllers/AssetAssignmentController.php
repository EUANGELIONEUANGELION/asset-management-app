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
    // HELPER: Kirim WhatsApp via Fonnte (Hardcoded Token + Proteksi Timeout Network)
    // ════════════════════════════════════════════════════════════════════════
    private function kirimWA(User $user, string $pesan): void
    {
        if (!$user->no_telepon) return;

        $nomor = trim(preg_replace('/[^0-9]/', '', $user->no_telepon));
        if (!$nomor) return;

        try {
            Http::asForm()
                ->withoutVerifying()
                ->timeout(25)          // Maksimal waktu tunggu respon server Fonnte
                ->connectTimeout(10)   // Maksimal waktu pembentukan jabat tangan jaringan
                ->withHeaders(['Authorization' => 'YsS1rkxGMiUDSs9jefno']) // API KEY Hardcoded Anda
                ->post('https://api.fonnte.com/send', [
                    'target'  => $nomor,
                    'message' => $pesan,
                ]);
        } catch (\Exception $e) {
            // Gagalkan secara senyap agar alur database internal sistem tidak ikut crash jika internet mati/RTO
            \Log::warning("Fonnte WhatsApp Gateway Timeout or Error: " . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // INDEX — Halaman Monitoring Utama
    // ════════════════════════════════════════════════════════════════════════
    public function index()
    {
        $assignments = Assignment::with(['aset', 'receiver'])->latest()->get();
        $timUsers    = User::whereIn('role', ['tim', 'officer', 'staff', 'technical support'])->get();

        $saranBrand       = DB::table('asset_attributes')->where('attribute_name', 'brand')->distinct()->pluck('attribute_value')->toArray();
        $saranTipe        = DB::table('asset_attributes')->where('attribute_name', 'tipe')->distinct()->pluck('attribute_value')->toArray();
        $saranCheckpoints = DB::table('master_checkpoints')->pluck('nama_poin')->toArray();

        // Data foto per task untuk galeri JS popup (DIKEMBALIKAN UTUH)
        $fotoDataPerTask = [];
        foreach ($assignments as $task) {
            $fotos = DokumentasiAset::where('aset_id', $task->aset_id)
                ->where('jenis_dokumentasi', 'input_aset')
                ->get();

            if ($fotos->count() > 0) {
                $fotoDataPerTask[$task->id] = [
                    'kondisi'    => $fotos->first()->kondisi,
                    'keterangan' => $fotos->first()->keterangan,
                    'fotos'      => $fotos->map(fn($f) => asset('storage/' . $f->url_foto))->toArray(),
                ];
            }
        }

        return view('master_aset.index', compact(
            'assignments', 'timUsers',
            'saranBrand', 'saranTipe', 'saranCheckpoints',
            'fotoDataPerTask'
        ));
    }

    // ════════════════════════════════════════════════════════════════════════
    // TIM LAPANGAN — Halaman Form Input Spesifikasi (Full Page Terpisah)
    // ════════════════════════════════════════════════════════════════════════
public function formInput($id)
{
    // Pastikan di-load dengan relasi 'aset.dokumentasi' agar tidak null
    $assignment = Assignment::with(['aset.dokumentasi'])->findOrFail($id);
    
    $checkpoints = DB::table('assignment_checkpoints')
        ->where('assignment_id', $id)
        ->get();

    $saranBrand = DB::table('asset_attributes')->where('attribute_name', 'brand')->distinct()->pluck('attribute_value')->toArray();
    $saranTipe  = DB::table('asset_attributes')->where('attribute_name', 'tipe')->distinct()->pluck('attribute_value')->toArray();

    return view('master_aset.form_input', compact('assignment', 'checkpoints', 'saranBrand', 'saranTipe'));
}

    // ════════════════════════════════════════════════════════════════════════
    // SUPERVISOR — Halaman Tinjauan Validasi Approval (Full Page Terpisah)
    // ════════════════════════════════════════════════════════════════════════
    public function formApproval($id)
    {
        $assignment  = Assignment::with('aset')->findOrFail($id);
        $checkpoints = DB::table('assignment_checkpoints')
            ->where('assignment_id', $id)
            ->get();
            
        $fotos = DokumentasiAset::where('assignment_id', $id)
            ->orWhere('aset_id', $assignment->aset_id)
            ->where('jenis_dokumentasi', 'input_aset')
            ->get();

        return view('master_aset.form_approval', compact('assignment', 'checkpoints', 'fotos'));
    }

    // ════════════════════════════════════════════════════════════════════════
    // 1. SUPERVISOR — Membuat Instruksi Tugas & Checkpoint
    // ════════════════════════════════════════════════════════════════════════
    public function storeTask(Request $request)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'deskripsi'   => 'required|string',
            'checkpoints' => 'required|array|min:1',
        ]);

        $deskripsiInput = $request->input('deskripsi');

        DB::transaction(function () use ($request, $deskripsiInput) {
            $aset = Aset::create([
                'no_aset'    => 'AST-' . strtoupper(Str::random(8)),
                'rfid_code'  => null,
                'status'     => 'pending',
                'created_by' => Auth::id(),
            ]);

            $assignment = Assignment::create([
                'aset_id'     => $aset->id,
                'assigned_by' => Auth::id(),
                'assigned_to' => $request->assigned_to,
                'jenis_tugas' => 'input_baru',
                'status'      => 'pending',
                'deskripsi'   => $deskripsiInput,
                'assigned_at' => now(),
            ]);

            $poinTeksWA = "";
            foreach ($request->checkpoints as $poin) {
                $cleanPoin = trim($poin);
                if ($cleanPoin === '') continue;

                DB::table('master_checkpoints')->updateOrInsert(['nama_poin' => $cleanPoin]);
                DB::table('assignment_checkpoints')->insert([
                    'assignment_id' => $assignment->id,
                    'nama_poin'     => $cleanPoin,
                    'is_checked'    => false,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $poinTeksWA .= "• {$cleanPoin}\n";
            }

            $userTim = User::find($request->assigned_to);
            if ($userTim) {
                $this->kirimWA(
                    $userTim,
                    "🚨 *NOTIFIKASI PENUGASAN BARU* 🚨\n\nHalo *{$userTim->nama}*,\nSupervisor memberikan instruksi kerja baru:\n📝 _\"{$deskripsiInput}\"_\n\n*📋 DAFTAR CHECKPOINT TUGAS WAJIB:*\n{$poinTeksWA}\nSilakan masuk sistem untuk merespon tugas."
                );
            }
        });

        return redirect()->route('master_aset.index')
            ->with('toast_success', 'Tugas logistik baru berhasil diterbitkan!');
    }

    // ════════════════════════════════════════════════════════════════════════
    // SUPERVISOR — Edit Instruksi & Checkpoint
    // ════════════════════════════════════════════════════════════════════════
    public function updateTask(Request $request, $id)
    {
        $request->validate([
            'deskripsi'   => 'required|string',
            'assigned_to' => 'required|exists:users,id',
            'checkpoints' => 'required|array|min:1',
        ]);

        DB::transaction(function () use ($request, $id) {
            $assignment = Assignment::findOrFail($id);
            $assignment->update([
                'deskripsi'   => $request->deskripsi,
                'assigned_to' => $request->assigned_to,
            ]);

            DB::table('assignment_checkpoints')->where('assignment_id', $id)->delete();

            $poinTeksWA = "";
            foreach ($request->checkpoints as $poin) {
                $cleanPoin = trim($poin);
                if ($cleanPoin === '') continue;

                DB::table('master_checkpoints')->updateOrInsert(['nama_poin' => $cleanPoin]);
                DB::table('assignment_checkpoints')->insert([
                    'assignment_id' => $id,
                    'nama_poin'     => $cleanPoin,
                    'is_checked'    => false,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $poinTeksWA .= "• {$cleanPoin}\n";
            }

            $userTim = User::find($request->assigned_to);
            if ($userTim) {
                $this->kirimWA(
                    $userTim,
                    "📝 *NOTIFIKASI PERUBAHAN TUGAS* 📝\n\nHalo *{$userTim->nama}*,\nSupervisor memperbarui instruksi kerja Anda:\n📋 _\"{$request->deskripsi}\"_\n\n*📌 REVISI CHECKPOINT:*\n{$poinTeksWA}"
                );
            }
        });

        return redirect()->route('master_aset.index')
            ->with('toast_success', 'Data penugasan sukses diperbarui!');
    }

    // ════════════════════════════════════════════════════════════════════════
    // SUPERVISOR — Hapus Tugas (Cascading)
    // ════════════════════════════════════════════════════════════════════════
    public function destroyTask($id)
    {
        DB::transaction(function () use ($id) {
            $assignment = Assignment::findOrFail($id);
            $asetId     = $assignment->aset_id;

            $dokList = DokumentasiAset::where('aset_id', $asetId)->get();
            foreach ($dokList as $dok) {
                Storage::disk('public')->delete($dok->url_foto);
            }

            DB::table('assignment_checkpoints')->where('assignment_id', $id)->delete();
            $assignment->delete();
            DB::table('asset_attributes')->where('aset_id', $asetId)->delete();
            DB::table('dokumentasi_aset')->where('aset_id', $asetId)->delete();
            Aset::where('id', $asetId)->delete();
        });

        return redirect()->route('master_aset.index')
            ->with('toast_success', 'Data tugas logistik berhasil dihapus.');
    }

    // ════════════════════════════════════════════════════════════════════════
    // 2. TIM LAPANGAN — Konfirmasi Penerimaan Tugas
    // ════════════════════════════════════════════════════════════════════════
    public function confirmTask($id)
    {
        $assignment = Assignment::findOrFail($id);
        $assignment->update([
            'status'       => 'process',
            'confirmed_at' => now(),
        ]);

        return redirect()->route('master_aset.index')
            ->with('toast_success', 'Tugas berhasil diterima!');
    }

    // ════════════════════════════════════════════════════════════════════════
    // 3. TIM LAPANGAN — Submit Data Spesifikasi + Checkpoint + Foto (UTOH & FIXED)
    // ════════════════════════════════════════════════════════════════════════
    public function submitAssetData(Request $request, $id)
    {
        // PERBAIKAN TOTAL: Memvalidasi 'status_kondisi' dari form driven Anda untuk menghindari error required
        $request->validate([
            'no_sap'                 => 'required|string',
            'jenis'                  => 'required|string',
            'brand'                  => 'required|string',
            'tipe'                   => 'required|string',
            'tahun_pembelian'        => 'required|date',
            'supplier'               => 'required|string',
            'status_kondisi'         => 'required|string|in:baru,baik,rusak_ringan,rusak_berat',
            'nama_pengguna'          => 'required|string',
            'lokasi'                 => 'required|string',
            'ruang'                  => 'required|string',
            'departemen'             => 'required|string',
            'checked_points'         => 'required|array|min:1',
            'foto_dokumentasi'       => 'required|array|min:1|max:5',
            'foto_dokumentasi.*'     => 'image|mimes:jpg,jpeg,png|max:2048',
            'keterangan_dokumentasi' => 'nullable|string|max:500',
        ], [
            'status_kondisi.required'   => 'Status kondisi fisik unit wajib dipilih.',
            'checked_points.required'   => 'Anda harus menyelesaikan dan mencentang bukti kerja minimal 1 poin tugas.',
            'foto_dokumentasi.required' => 'Minimal 1 foto dokumentasi wajib diunggah.',
        ]);

        DB::transaction(function () use ($request, $id) {
            $assignment = Assignment::findOrFail($id);
            $aset       = Aset::findOrFail($assignment->aset_id);

            $generatedQrCode = strtoupper($request->jenis) . '-' . trim($request->no_sap);

            $aset->update([
                'no_sap'    => $request->no_sap,
                'rfid_code' => $generatedQrCode,
                'status'    => 'pending_approval',
            ]);

            // ── UPDATE NAMA CHECKPOINT YANG DIEDIT TIM (DIKEMBALIKAN UTUH) ──
            if ($request->has('checkpoint_names')) {
                foreach ($request->checkpoint_names as $cpId => $namaBaru) {
                    $namaBaru = trim($namaBaru);
                    if ($namaBaru === '') continue;

                    DB::table('assignment_checkpoints')
                        ->where('id', $cpId)
                        ->where('assignment_id', $id)
                        ->update([
                            'nama_poin'  => $namaBaru,
                            'updated_at' => now(),
                        ]);
                }
            }

            // ── TAMBAH CHECKPOINT BARU DARI TIM (DIKEMBALIKAN UTUH) ──
            if ($request->has('new_checkpoints')) {
                foreach ($request->new_checkpoints as $idx => $namaPoin) {
                    $namaPoin = trim($namaPoin);
                    if ($namaPoin === '') continue;

                    DB::table('master_checkpoints')->updateOrInsert(['nama_poin' => $namaPoin]);

                    $isChecked = isset($request->new_checked_points[$idx]) ? true : false;

                    DB::table('assignment_checkpoints')->insert([
                        'assignment_id' => $id,
                        'nama_poin'     => $namaPoin,
                        'is_checked'    => $isChecked,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }

            // ── UPDATE STATUS CENTANG CHECKPOINT LAMA (DIKEMBALIKAN UTUH) ──
            DB::table('assignment_checkpoints')
                ->where('assignment_id', $id)
                ->update(['is_checked' => false]);

            if ($request->has('checked_points') && count($request->checked_points) > 0) {
                DB::table('assignment_checkpoints')
                    ->where('assignment_id', $id)
                    ->whereIn('id', $request->checked_points)
                    ->update(['is_checked' => true, 'updated_at' => now()]);
            }

            // ── SIMPAN ATRIBUT EAV (DIKEMBALIKAN UTUH) ──
            DB::table('asset_attributes')->where('aset_id', $aset->id)->delete();

            $skipKeys = [
                '_token', 'no_sap', 'checked_points', 'new_checked_points',
                'new_checkpoints', 'checkpoint_names', 'keterangan_dokumentasi', 'foto_dokumentasi'
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

            // ── HAPUS FOTO LAMA & UPLOAD BARU (DIKEMBALIKAN UTUH) ──
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
                $path = $foto->store('dokumentasi/' . $aset->id, 'public');
                DokumentasiAset::create([
                    'aset_id'           => $aset->id,
                    'assignment_id'     => $id,
                    'jenis_dokumentasi' => 'input_aset',
                    'kondisi'           => $request->status_kondisi, // Menggunakan key status_kondisi yang dikirim form
                    'keterangan'        => $request->keterangan_dokumentasi,
                    'url_foto'          => $path,
                    'created_by'        => Auth::id(),
                ]);
            }

            // ── NOTIFIKASI WA KE SUPERVISOR ──
            $checkpointsRealtime  = DB::table('assignment_checkpoints')->where('assignment_id', $id)->get();
            $checklistLaporanTeks = "";
            foreach ($checkpointsRealtime as $cr) {
                $simbol = $cr->is_checked ? "✅" : "❌";
                $checklistLaporanTeks .= "{$simbol} {$cr->nama_poin}\n";
            }

            $supervisor = User::find($assignment->assigned_by);
            if ($supervisor) {
                $this->kirimWA(
                    $supervisor,
                    "🔔 *PERMINTAAN VERIFIKASI* 🔔\n\nHalo *{$supervisor->nama}*,\nTim lapangan telah menyelesaikan input spesifikasi barang.\n\n*📊 REKAP CHECKPOINT:*\n{$checklistLaporanTeks}\n📦 No SAP: {$request->no_sap}\n📦 QR Code: {$generatedQrCode}\n\nSilakan periksa halaman verifikasi."
                );
            }
        });

        return redirect()->route('master_aset.index')
            ->with('toast_success', 'Data spesifikasi berhasil diajukan ke Supervisor!');
    }

    // ════════════════════════════════════════════════════════════════════════
    // 4. SUPERVISOR — ACC atau Tolak
    // ════════════════════════════════════════════════════════════════════════
   public function verifyApproval(Request $request, $id)
    {
        $request->validate([
            'status_keputusan' => 'required|in:acc,tolak',
            'catatan'          => 'nullable|string',
        ]);

        if ($request->status_keputusan === 'tolak' && empty(trim($request->catatan ?? ''))) {
            return back()->withErrors(['catatan' => 'Catatan wajib diisi jika status ditolak.']);
        }

        DB::transaction(function () use ($request, $id) {
            $assignment = Assignment::findOrFail($id);
            $aset       = Aset::findOrFail($assignment->aset_id);

            if ($request->status_keputusan === 'acc') {
                // ── CORE UPDATE: Ambil kategori perangkat dari attributes EAV untuk kombinasi string QR
                $kategori = DB::table('asset_attributes')
                    ->where('aset_id', $aset->id)
                    ->where('attribute_name', 'jenis')
                    ->value('attribute_value') ?? 'AST';

                // Bersihkan spasi dan gabungkan dengan No SAP menjadi string QR Code resmi
                $generatedQrCode = strtoupper(str_replace(' ', '', $kategori)) . '-' . trim($aset->no_sap);

                // Update status aset menjadi ready sekalian daftarkan nomor QR Code barunya
                $aset->update([
                    'status'    => 'ready',
                    'rfid_code' => $generatedQrCode
                ]);

                $assignment->update(['status' => 'completed']);
            } else {
                // Jika ditolak supervisor, status kembali pending dan pastikan rfid_code dikunci tetap null
                $aset->update([
                    'status'    => 'pending',
                    'rfid_code' => null
                ]);

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
            $userTim = User::find($assignment->assigned_to);
            if ($userTim) {
                if ($request->status_keputusan === 'acc') {
                    // Beritahu tim lapangan via WA bahwa QR Code resmi mereka sudah terbit dan siap cetak
                    $pesan = "✅ *ASET DISETUJUI (ACC)* ✅\n\nHalo *{$userTim->nama}*,\nSpesifikasi logistik barang dinyatakan valid.\n\n📦 *LABEL QR CODE RESMI:* `{$aset->rfid_code}`\n\nAset kini berstatus *READY* di inventaris. Silakan cetak label thermal sticker pada halaman monitoring.";
                } else {
                    $catatan = $request->catatan ?? '-';
                    $pesan   = "❌ *ASET PERLU REVISI* ❌\n\nHalo *{$userTim->nama}*,\nPengajuan dikembangkan supervisor.\n⚠️ Alasan: _\"{$catatan}\"_\n\nBuka form input Anda untuk submit ulang data perbaikan.";
                }
                $this->kirimWA($userTim, $pesan);
            }
        });

        return redirect()->route('master_aset.index')
            ->with('toast_success', 'Keputusan pemeriksaan aset berhasil disimpan!');
    }
    // ════════════════════════════════════════════════════════════════════════
    // API — Ambil Checkpoint via AJAX
    // ════════════════════════════════════════════════════════════════════════
    public function getCheckpoints($assignment_id)
    {
        $checkpoints = DB::table('assignment_checkpoints')
            ->where('assignment_id', $assignment_id)
            ->get(['id', 'nama_poin', 'is_checked']);

        return response()->json($checkpoints);
    }
    
}