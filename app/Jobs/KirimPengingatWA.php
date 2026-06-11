<?php

namespace App\Jobs;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class KirimPengingatWA implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    /**
     * Helper kirim WA via Fonnte
     */
    private function kirimWA(User $user, string $pesan): void
    {
        if (!$user->no_telepon) return;

        $nomor = trim(preg_replace('/[^0-9]/', '', $user->no_telepon));
        if (!$nomor) return;

        Http::asForm()
            ->withoutVerifying()
            ->withHeaders(['Authorization' => env('FONNTE_TOKEN', 'YsS1rkxGMiUDSs9jefno')])
            ->post('https://api.fonnte.com/send', [
                'target'  => $nomor,
                'message' => $pesan,
            ]);
    }

    public function handle(): void
    {
        $batasWaktu = now()->subMinutes(30);

        // ── KASUS 1: Pending > 30 menit — Tim belum confirm tugas ────────────
        // Status assignment = 'pending', artinya tim belum klik "Confirm Tugas"
        $tugasBelumDikonfirm = Assignment::with(['receiver', 'assigner'])
            ->where('status', 'pending')
            ->where('assigned_at', '<=', $batasWaktu)
            ->get();

        foreach ($tugasBelumDikonfirm as $task) {
            $userTim = $task->receiver;
            if ($userTim) {
                $this->kirimWA($userTim,
                    " *PENGINGAT TUGAS BELUM DIKONFIRMASI* \n\n"
                    . "Halo *{$userTim->nama}*,\n"
                    . "Anda memiliki tugas yang belum dikonfirmasi lebih dari *30 menit*.\n\n"
                    . " Instruksi: _\"{$task->deskripsi}\"_\n\n"
                    . "Silakan segera buka sistem dan klik *Confirm Tugas* untuk mulai mengerjakan."
                );
            }
        }

        // ── KASUS 2: Pending Approval > 30 menit — Supervisor belum ACC/Tolak ─
        // Status aset = 'pending_approval', artinya tim sudah submit tapi supervisor belum verifikasi
        $tugasBelumDiapproval = Assignment::with(['aset', 'assigner'])
            ->whereHas('aset', fn($q) => $q->where('status', 'pending_approval'))
            ->where('updated_at', '<=', $batasWaktu)
            ->get();

        foreach ($tugasBelumDiapproval as $task) {
            $supervisor = User::find($task->assigned_by);
            $userTim    = $task->receiver;
            $namaTim    = $userTim->nama ?? 'Tim Lapangan';

            if ($supervisor) {
                $this->kirimWA($supervisor,
                    " *PENGINGAT VERIFIKASI ASET TERTUNDA* \n\n"
                    . "Halo *{$supervisor->nama}*,\n"
                    . "Ada data aset yang menunggu verifikasi Anda lebih dari *30 menit*.\n\n"
                    . "Disubmit oleh : *{$namaTim}*\n"
                    . "Instruksi     : _\"{$task->deskripsi}\"_\n\n"
                    . "Silakan segera buka sistem dan lakukan *ACC atau Tolak* pada data tersebut."
                );
            }
        }
    }
}

