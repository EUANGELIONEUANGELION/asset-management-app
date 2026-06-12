@extends('layouts.app')

@section('title', 'Pemeriksaan Approval Aset')

@section('content')
<div class="flex flex-col h-screen overflow-hidden bg-[#f3f3f4]">

    {{-- ── FIXED STICKY HEADER (TIDAK IKUT SCROLL) ── --}}
    <div class="bg-white border-b border-[#e7eaec] px-6 py-4 flex justify-between items-center flex-shrink-0 shadow-2xs z-10">
        <div>
            <h2 class="text-xl font-light text-gray-700 mb-0.5">Pemeriksaan &amp; Validasi Data Kelayakan</h2>
            <div class="text-xs text-gray-400">Tinjau keselarasan dokumen fisik PT Wismilak lapangan dengan sistem internal.</div>
        </div>
        <a href="{{ route('master_aset.index') }}" class="bg-white border border-gray-300 text-gray-600 px-3 py-2 rounded text-xs font-semibold no-underline hover:bg-gray-50 transition shadow-3xs flex items-center gap-1">
            <i class="fa fa-arrow-left"></i> Kembali ke Monitoring
        </a>
    </div>

    {{-- ── SCROLLABLE CONTAINER AREA (HANYA AREA PENINJAUAN INI YANG BISA DI-SCROLL) ── --}}
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto space-y-5">

            {{-- ── IDENTITAS UTAMA ASET ── --}}
            <div class="bg-white border border-gray-200 rounded p-5 shadow-xs">
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 flex items-center gap-2 tracking-wide">
                    <i class="fa fa-tag text-blue-400"></i> Identitas Pokok Entitas
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs text-gray-600">
                    <div class="bg-gray-50 rounded p-2.5 border border-gray-100 font-mono">
                        <div class="text-[10px] font-bold text-gray-400 uppercase mb-1">No Aset</div>
                        <div class="font-semibold text-gray-700">{{ $assignment->aset->no_aset ?? '-' }}</div>
                    </div>
                    <div class="bg-gray-50 rounded p-2.5 border border-gray-100 font-mono">
                        <div class="text-[10px] font-bold text-gray-400 uppercase mb-1">No SAP</div>
                        <div class="font-semibold text-gray-700">{{ $assignment->aset->no_sap ?? '-' }}</div>
                    </div>
                    <div class="bg-gray-50 rounded p-2.5 border border-gray-100 font-mono">
                        <div class="text-[10px] font-bold text-gray-400 uppercase mb-1">RFID / QR Code</div>
                        <div class="font-semibold text-gray-700">{{ $assignment->aset->rfid_code ?? '-' }}</div>
                    </div>
                    <div class="bg-gray-50 rounded p-2.5 border border-gray-100 flex flex-col justify-center">
                        <div class="text-[10px] font-bold text-gray-400 uppercase mb-1">Status Logik</div>
                        <div>
                            <span class="bg-orange-400 text-white px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                                {{ str_replace('_', ' ', $assignment->aset->status ?? '-') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- QR Server Dynamic Generator Preview --}}
                @if($assignment->aset->rfid_code)
                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center gap-3">
                        <div class="p-1 bg-white border border-gray-200 rounded shadow-3xs inline-block">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($assignment->aset->rfid_code) }}" class="w-14 h-16 block">
                        </div>
                        <div class="text-xs text-gray-500">
                            <div class="font-bold text-gray-600 mb-0.5">Penanda Label QR Code</div>
                            <div class="font-mono text-[11px] text-gray-400">Pemicu Cetak Label Thermal Berhasil Ter-koneksi</div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ── ATRIBUT SPESIFIKASI DINAMIS EAV TIM ── --}}
            @php
                $atribut = DB::table('asset_attributes')
                    ->where('aset_id', $assignment->aset_id)
                    ->whereNotIn('attribute_name', ['catatan_supervisor'])
                    ->get()
                    ->keyBy('attribute_name');
            @endphp
            @if($atribut->count() > 0)
                <div class="bg-white border border-gray-200 rounded p-5 shadow-xs">
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 flex items-center gap-2 tracking-wide">
                        <i class="fa fa-database text-green-400"></i> Detail Atribut Hasil Pengisian Lapangan
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2.5">
                        @foreach($atribut as $key => $attr)
                            <div class="text-xs bg-gray-50 rounded p-2.5 border border-gray-100 transition hover:bg-white hover:border-gray-200">
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wide mb-0.5">{{ str_replace(['spec_', '_'], ['', ' '], $key) }}</div>
                                <div class="text-gray-700 font-semibold">{{ $attr->attribute_value }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── PROGRESS & EVALUASI LIST CHECKPOINT TUGAS (FIXED TEXT MERAH AMAN) ── --}}
            <div class="bg-white border border-gray-200 rounded p-5 shadow-xs">
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 flex items-center gap-2 tracking-wide">
                    <i class="fa fa-list-ul text-blue-400"></i> Evaluasi Checkpoint Hasil Kerja Tim
                </h4>
                @php
                    $totalCp  = $checkpoints->count();
                    $tuntasCp = $checkpoints->where('is_checked', true)->count();
                    $persenCp = $totalCp > 0 ? round(($tuntasCp / $totalCp) * 100) : 0;
                @endphp

                {{-- Penayangan Progress Bar --}}
                <div class="mb-4 bg-gray-50 p-3 rounded border border-gray-100">
                    <div class="flex justify-between text-[10px] text-gray-500 mb-1 font-medium">
                        <span>Penyelesaian Target Checkpoint Lapangan</span>
                        <span class="font-bold {{ $persenCp === 100 ? 'text-green-600' : 'text-orange-500' }}">
                            {{ $tuntasCp }} dari {{ $totalCp }} Selesai ({{ $persenCp }}%)
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full transition-all duration-500 {{ $persenCp === 100 ? 'bg-green-600' : 'bg-orange-400' }}" style="width: {{ $persenCp }}%"></div>
                    </div>
                </div>

                {{-- List Rows Item Kontrol Checkpoint --}}
                <div class="bg-gray-50 p-4 rounded border border-gray-100 space-y-2 max-h-60 overflow-y-auto">
                    @forelse($checkpoints as $cp)
                        <div class="text-xs flex items-center justify-between gap-2 p-1.5 rounded {{ $cp->is_checked ? 'text-green-600' : 'text-red-500 bg-red-50/40' }}">
                            <div class="flex items-center gap-2 min-w-0">
                                <i class="fa {{ $cp->is_checked ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500' }} flex-shrink-0"></i>
                                <span class="{{ $cp->is_checked ? 'line-through text-green-500' : 'font-medium' }} truncate">{{ $cp->nama_poin }}</span>
                            </div>
                            <strong class="text-[10px] uppercase tracking-wide flex-shrink-0 ml-auto">
                                {{ $cp->is_checked ? 'TUNTAS' : 'BELUM' }}
                            </strong>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic m-0">Tidak ada rincian checkpoint khusus dari supervisor.</p>
                    @endforelse
                </div>
            </div>

            {{-- ── PENINJAUAN FOTO BUKTI DOKUMENTASI FISIK LAPANGAN ── --}}
            @if(isset($fotos) && $fotos->count() > 0)
                <div class="bg-white border border-gray-200 rounded p-5 shadow-xs">
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 flex items-center gap-2 tracking-wide">
                        <i class="fa fa-camera text-amber-400"></i> Foto Kondisi Fisik Lampiran Berkas
                        <span class="bg-amber-100 text-amber-700 text-[10px] px-2 py-0.5 rounded font-bold">{{ $fotos->count() }} file</span>
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-gray-50/50 p-3 rounded border border-gray-100">
                        @foreach($fotos as $foto)
                            <a href="{{ asset('storage/' . $foto->url_foto) }}" target="_blank" class="block group relative rounded overflow-hidden shadow-3xs border border-gray-200 bg-white">
                                <img src="{{ asset('storage/' . $foto->url_foto) }}" class="w-full h-28 object-cover group-hover:scale-105 transition duration-300 cursor-zoom-in">
                                <div class="absolute bottom-0 inset-x-0 bg-black/60 text-white text-[9px] py-1 text-center font-bold tracking-wide uppercase">
                                    {{ $foto->kondisi }}
                                </div>
                            </a>
                        @endforeach
                    </div>
                    
                    @php $fotoInfo = $fotos->first(); @endphp
                    @if($fotoInfo && $fotoInfo->keterangan)
                        <div class="mt-3 text-xs text-gray-600 bg-amber-50/50 rounded p-3 border border-amber-200/60 leading-relaxed">
                            <span class="font-bold text-amber-800"><i class="fa fa-commenting"></i> Catatan Fisik Tambahan Tim:</span> "{{ $fotoInfo->keterangan }}"
                        </div>
                    @endif
                </div>
            @endif

            {{-- ── FORM KEPUTUSAN FINAL PERSETUJUAN SUPERVISOR ── --}}
            <div class="bg-white border border-gray-200 rounded p-5 shadow-xs">
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-4 flex items-center gap-2 tracking-wide">
                    <i class="fa fa-gavel text-orange-400"></i> Tindakan Pengesahan Keputusan
                </h4>

                <form action="/aset/verify/{{ $assignment->id }}" method="POST" class="space-y-4 m-0">
                    @csrf
                    <div>
                        <label class="font-bold text-gray-600 text-xs uppercase block mb-1">Tentukan Keputusan Final <span class="text-red-500">*</span></label>
                        <select name="status_keputusan" required id="select-keputusan" onchange="toggleCatatanWajib(this.value)" class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-orange-400 font-semibold cursor-pointer">
                            <option value="acc"> SETUJUI — Status READY, sah masuk inventaris Technical Support</option>
                            <option value="tolak"> TOLAK / REVISI — Kembalikan draf ke form pengisian tim lapangan</option>
                        </select>
                    </div>

                    <div>
                        <label class="font-bold text-gray-600 text-xs uppercase block mb-1">
                            Catatan Koreksi Evaluasi 
                            <span id="label-wajib" class="text-red-500 font-bold hidden">(Wajib diisi untuk draf tolak revisi)</span>
                        </label>
                        <textarea name="catatan" id="textarea-catatan" rows="3" placeholder="Tulis instruksi tambahan pasca-acc atau alasan detail penolakan..." class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-orange-400 resize-none"></textarea>
                    </div>

                    <div class="flex gap-3 pt-2 border-t border-gray-50">
                        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded text-xs font-bold cursor-pointer border-0 shadow-sm transition">
                            <i class="fa fa-gavel mr-1"></i> Simpan Keputusan Final
                        </button>
                        <a href="{{ route('master_aset.index') }}" class="px-4 py-2 text-xs text-gray-600 bg-white border border-gray-300 rounded no-underline hover:bg-gray-50 transition flex items-center justify-center">
                            Batal
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
    // JS CONTROL: Otomatisasi penguncian validasi input saat merubah status keputusan
    function toggleCatatanWajib(val) {
        var label = document.getElementById('label-wajib');
        var textarea = document.getElementById('textarea-catatan');
        if (val === 'tolak') {
            label.classList.remove('hidden');
            textarea.setAttribute('required', 'required');
            textarea.placeholder = 'Wajib menulis alasan penolakan secara rinci agar tim paham poin perbaikannya...';
            textarea.focus();
        } else {
            label.classList.add('hidden');
            textarea.removeAttribute('required');
            textarea.placeholder = 'Tulis catatan persetujuan penunjang (opsional)...';
        }
    }
</script>
@endsection