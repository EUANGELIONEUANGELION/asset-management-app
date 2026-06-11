@extends('layouts.app')

@section('title', 'Asset Data Management')

@section('content')

    {{-- ── PAGE HEADER ──────────────────────────────────────────────────────── --}}
    <div class="bg-white border-b border-[#e7eaec] px-6 py-5 flex justify-between items-center">
        <div>
            <h2 class="text-xl font-light text-gray-700 mb-1">Asset Data Workflow</h2>
            <div class="text-xs text-gray-400">Pendaftaran spesifikasi logistik inventaris terintegrasi sistem pengingat otomatis Fonnte.</div>
        </div>
        @if(Auth::user()->role === 'supervisor' || Auth::user()->role === 'manager')
            <button onclick="openAssignModal()" class="bg-inspiniaPrimary text-white px-3 py-2 rounded text-xs font-semibold hover:bg-inspiniaPrimaryHover transition shadow-sm cursor-pointer">
                <i class="fa fa-plus mr-1"></i> Tambah & Tugaskan Tim
            </button>
        @endif
    </div>

    {{-- ── MAIN TABLE ───────────────────────────────────────────────────────── --}}
    <div class="p-6 pb-2">
        <div class="bg-white border border-[#e7eaec] rounded shadow-xs p-6">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4">
                <i class="fa fa-tasks mr-2 text-inspiniaPrimary"></i>Alur Kontrol Tugas Lapangan
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-semibold uppercase tracking-wider">
                            <th class="p-3">ID</th>
                            <th class="p-3">Instruksi Supervisor</th>
                            <th class="p-3">Tim Lapangan</th>
                            <th class="p-3 text-center" style="width:130px;">QR CODE</th>
                            <th class="p-3 text-center" style="width:100px;">Foto</th>
                            <th class="p-3">Status Logik Alur</th>
                            <th class="p-3 text-center">Aksi Operasional</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-600">
                        @forelse($assignments as $task)
                        @php
                            $catatanSvp = \DB::table('asset_attributes')
                                ->where('aset_id', $task->aset_id)
                                ->where('attribute_name', 'catatan_supervisor')
                                ->value('attribute_value');

                            $fotos = \App\Models\DokumentasiAset::where('aset_id', $task->aset_id)
                                ->where('jenis_dokumentasi', 'input_aset')
                                ->get();
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="p-3 font-mono text-gray-400">#{{ $task->id }}</td>

                            <td class="p-3 font-medium text-gray-700">
                                {{ $task->deskripsi }}
                                @if($task->aset->status === 'pending' && $catatanSvp && $catatanSvp !== 'Disetujui')
                                    <div class="text-[11px] text-red-600 font-semibold mt-1 bg-red-50 p-1.5 rounded border border-red-200">
                                        <i class="fa fa-warning"></i> DITOLAK SUPERVISOR: "{{ $catatanSvp }}" (Silakan Submit Ulang)
                                    </div>
                                @endif
                            </td>

                            <td class="p-3">{{ $task->receiver->nama ?? '-' }}</td>

                            <td class="p-3 text-center">
                                @if($task->aset && $task->aset->rfid_code)
                                    <div class="inline-block p-1 bg-white border border-gray-200 rounded shadow-2xs">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data={{ urlencode($task->aset->rfid_code) }}"
                                             class="w-[45px] h-[45px] block mx-auto">
                                    </div>
                                    <div class="text-[9px] font-mono text-gray-400 mt-1">{{ $task->aset->rfid_code }}</div>
                                @else
                                    <span class="text-gray-300 italic">Belum Dibuat<br><small>(Menunggu Input Data)</small></span>
                                @endif
                            </td>

                            {{-- ── KOLOM FOTO ── --}}
                            <td class="p-3 text-center">
                                @if($fotos->count() > 0)
                                    <button onclick="openFotoModal({{ $task->id }})"
                                        class="inline-flex flex-col items-center gap-1 cursor-pointer group">
                                        <div class="relative w-12 h-12 rounded overflow-hidden border-2 border-gray-200 group-hover:border-amber-400 transition shadow-sm">
                                            <img src="{{ asset('storage/' . $fotos->first()->url_foto) }}"
                                                 class="w-full h-full object-cover"
                                                 onerror="this.parentElement.innerHTML='<div class=\'w-full h-full bg-gray-100 flex items-center justify-center\'><i class=\'fa fa-image text-gray-300 text-lg\'></i></div>'">
                                        </div>
                                        <span class="text-[10px] text-amber-600 font-bold">
                                            {{ $fotos->count() }} foto
                                        </span>
                                    </button>
                                @else
                                    <span class="text-gray-300 text-lg">—</span>
                                @endif
                            </td>

                            <td class="p-3">
                                @if($task->aset->status === 'ready')
                                    <span class="bg-green-600 text-white text-[10px] px-2 py-0.5 rounded font-bold uppercase">READY (ACC)</span>
                                @elseif($task->aset->status === 'pending_approval')
                                    <span class="bg-orange-400 text-white text-[10px] px-2 py-0.5 rounded font-bold uppercase">MENUNGGU VERIFIKASI</span>
                                @elseif($task->aset->status === 'pending' && $catatanSvp && $catatanSvp !== 'Disetujui')
                                    <span class="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded font-bold uppercase">REVISI DATA</span>
                                @else
                                    <span class="bg-gray-400 text-white text-[10px] px-2 py-0.5 rounded font-bold uppercase">PROSES INPUT</span>
                                @endif
                            </td>

                            <td class="p-3 text-center" style="width: 160px;">
                                <div class="flex flex-col items-center gap-1.5">

                                    {{-- ── AKSI TIM LAPANGAN ── --}}
                                    @if(in_array(Auth::user()->role, ['tim','officer','staff']))
                                        @if($task->status === 'pending')
                                            <form action="/aset/confirm/{{ $task->id }}" method="POST" class="w-full m-0">
                                                @csrf
                                                <button type="submit" class="w-full bg-blue-600 text-white px-2.5 py-1.5 rounded text-[11px] hover:bg-blue-700 font-semibold cursor-pointer">
                                                    <i class="fa fa-check mr-1"></i> Confirm Tugas
                                                </button>
                                            </form>
                                        @elseif($task->status === 'process' && $task->aset->status === 'pending')
                                            <button onclick="openInputModal({{ $task->id }})"
                                                class="w-full bg-[#1ab394] text-white px-2.5 py-1.5 rounded text-[11px] hover:bg-[#18a689] font-medium cursor-pointer">
                                                <i class="fa fa-edit mr-1"></i> Isi & Generate QR
                                            </button>
                                        @elseif($task->aset->status === 'pending_approval')
                                            <span class="text-gray-400 italic text-[11px]">⏳ Review Supervisor</span>
                                        @else
                                            <button type="button"
                                                onclick="printQrLabel('{{ $task->aset->rfid_code }}','{{ $task->aset->no_sap ?? '-' }}','{{ $task->aset->no_aset ?? '-' }}')"
                                                class="w-full bg-gray-700 text-white px-2.5 py-1.5 rounded text-[11px] hover:bg-gray-800 font-semibold cursor-pointer">
                                                <i class="fa fa-print mr-1"></i> Print QR
                                            </button>
                                        @endif
                                    @endif

                                    {{-- ── AKSI SUPERVISOR / MANAGER ── --}}
                                    @if(in_array(Auth::user()->role, ['supervisor','manager']))
                                        @if($task->aset->status === 'pending_approval')
                                            <button onclick="openApprovalModal({{ $task->id }})"
                                                class="w-full bg-orange-400 text-white px-2.5 py-1.5 rounded text-[11px] hover:bg-orange-500 font-semibold cursor-pointer">
                                                <i class="fa fa-gavel mr-1"></i> Periksa & ACC
                                            </button>
                                        @elseif($task->aset->status === 'ready')
                                            <button type="button"
                                                onclick="printQrLabel('{{ $task->aset->rfid_code }}','{{ $task->aset->no_sap ?? '-' }}','{{ $task->aset->no_aset ?? '-' }}')"
                                                class="w-full bg-gray-700 text-white px-2.5 py-1.5 rounded text-[11px] hover:bg-gray-800 font-semibold cursor-pointer">
                                                <i class="fa fa-print mr-1"></i> Print Label
                                            </button>
                                        @endif

                                        @if($task->aset->status !== 'ready')
                                            <div class="flex gap-1 w-full">
                                                <button onclick="openEditModal({{ $task->id }}, '{{ addslashes($task->deskripsi) }}', '{{ $task->assigned_to }}')"
                                                    class="flex-1 bg-gray-100 text-gray-600 px-2 py-1.5 rounded text-[11px] hover:bg-gray-200 border border-gray-300 cursor-pointer">
                                                    <i class="fa fa-pencil"></i> Edit
                                                </button>
                                                <button type="button" onclick="confirmHapus({{ $task->id }})"
                                                    class="flex-1 bg-red-50 text-red-500 px-2 py-1.5 rounded text-[11px] hover:bg-red-100 border border-red-200 cursor-pointer">
                                                    <i class="fa fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        @endif
                                    @endif

                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-gray-400 bg-gray-50/50">Belum ada data instruksi operasional.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    {{-- ════════════════════════════════════════════════════════════════════════
         MODAL: Input Spesifikasi Aset (Tim Lapangan)
         ════════════════════════════════════════════════════════════════════════ --}}
    <div id="input-modal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center hidden backdrop-blur-xs overflow-y-auto p-4">
        <div class="bg-[#f3f3f4] rounded shadow-2xl w-full max-w-7xl overflow-hidden border border-gray-300 my-auto">
            <div class="bg-white px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-base font-normal text-gray-700 m-0">Asset Data Form Input</h3>
                <button onclick="closeInputModal()" class="text-gray-400 hover:text-gray-600 text-xl font-light bg-transparent border-0">&times;</button>
            </div>

            <form id="input-asset-form" method="POST" enctype="multipart/form-data" class="m-0">
                @csrf
                <div class="p-6 flex flex-col lg:flex-row gap-6">

                    {{-- ── KOLOM KIRI 3/4 ── --}}
                    <div class="w-full lg:w-3/4 bg-white border border-gray-200 p-6 rounded shadow-xs">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Jenis Perangkat</label>
                                <select name="jenis" id="form-select-jenis" onchange="toggleSpesifikasiField()" required
                                    class="w-full bg-white border border-gray-300 rounded px-2 py-1.5 text-xs text-gray-700">
                                    <option value="">-- Pilih --</option>
                                    <option value="Notebook">Notebook</option>
                                    <option value="Komputer">Komputer / PC Desktop</option>
                                    <option value="Mikrotik">Mikrotik Router</option>
                                    <option value="Monitor">Monitor</option>
                                    <option value="Printer">Printer</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Brand</label>
                                <input type="text" name="brand" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Tipe</label>
                                <input type="text" name="tipe" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Tahun Pembelian</label>
                                <input type="date" name="tahun_pembelian" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500 block mb-1">Supplier</label>
                                <input type="text" name="supplier" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500 block mb-1">Status Kondisi</label>
                                <select name="status_kondisi" required class="w-full bg-white border border-gray-300 rounded px-2 py-1.5 text-xs text-gray-700">
                                    <option value="Baru (New)">Baru (New)</option>
                                    <option value="Second Operasional">Second Operasional</option>
                                </select>
                            </div>

                            {{-- Spesifikasi tambahan Notebook / PC --}}
                            <div id="custom-spec-block" class="col-span-1 md:col-span-4 grid grid-cols-1 md:grid-cols-3 gap-4 bg-blue-50/50 p-4 rounded border border-blue-100 hidden">
                                <div class="col-span-1 md:col-span-3 text-xs font-bold text-blue-700 uppercase tracking-wide">
                                    <i class="fa fa-laptop"></i> Tambahan Atribut Spesifikasi Notebook / PC
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 block mb-1">Kapasitas RAM</label>
                                    <input type="text" name="spec_ram" placeholder="Contoh: 16 GB" class="w-full bg-white border border-gray-300 rounded px-2 py-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 block mb-1">Kapasitas Storage / SSD</label>
                                    <input type="text" name="spec_storage" placeholder="Contoh: 512 GB SSD" class="w-full bg-white border border-gray-300 rounded px-2 py-1.5 text-xs">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 block mb-1">Tipe Processor</label>
                                    <input type="text" name="spec_processor" placeholder="Contoh: Intel i5" class="w-full bg-white border border-gray-300 rounded px-2 py-1.5 text-xs">
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500 block mb-1">Nama Pengguna</label>
                                <select name="nama_pengguna" required class="w-full bg-white border border-gray-300 rounded px-2 py-1.5 text-xs">
                                    <option value="Divisi IT Logistik">Divisi IT Logistik</option>
                                    <option value="Operasional Gudang">Operasional Gudang</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Lokasi</label>
                                <input type="text" name="lokasi" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Ruang</label>
                                <input type="text" name="ruang" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500 block mb-1">Departemen</label>
                                <input type="text" name="departemen" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                            </div>

                            {{-- ══ SEKSI DOKUMENTASI FOTO ══ --}}
                            <div class="col-span-1 md:col-span-4 mt-2">
                                <div class="bg-amber-50 border border-amber-200 rounded p-4">
                                    <div class="text-xs font-bold text-amber-700 uppercase tracking-wide mb-3">
                                        <i class="fa fa-camera mr-1"></i> Dokumentasi Foto Kondisi Barang
                                        <span class="text-red-500 font-normal normal-case ml-1">(wajib, min. 1 foto)</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                                        {{-- Drop Zone + Preview --}}
                                        <div class="md:col-span-2">
                                            <label class="text-xs text-gray-600 block mb-1">
                                                Upload Foto Barang
                                                <span class="text-gray-400 font-normal">&mdash; maks. 5 foto, JPG/PNG, max 2MB/foto</span>
                                            </label>
                                            <div id="drop-zone"
                                                onclick="document.getElementById('foto-input').click()"
                                                ondragover="event.preventDefault(); this.classList.add('border-amber-400','bg-amber-100')"
                                                ondragleave="this.classList.remove('border-amber-400','bg-amber-100')"
                                                ondrop="handleDrop(event)"
                                                class="border-2 border-dashed border-amber-300 rounded-lg p-5 text-center
                                                       cursor-pointer hover:border-amber-400 hover:bg-amber-50
                                                       transition min-h-[72px] flex flex-col items-center justify-center gap-1">
                                                <i class="fa fa-cloud-upload text-2xl text-amber-400"></i>
                                                <div class="text-xs text-amber-600 font-medium">Klik atau drag &amp; drop foto ke sini</div>
                                                <div class="text-[10px] text-gray-400">Tersisa <span id="sisa-foto">5</span> slot foto</div>
                                            </div>
                                            <input type="file" id="foto-input" name="foto_dokumentasi[]"
                                                accept="image/jpeg,image/png,image/jpg"
                                                multiple class="hidden"
                                                onchange="handleFileSelect(this)">

                                            {{-- Preview Grid --}}
                                            <div id="foto-preview-grid" class="grid grid-cols-3 sm:grid-cols-5 gap-2 mt-3 hidden"></div>
                                        </div>

                                        {{-- Kondisi & Keterangan --}}
                                        <div class="flex flex-col gap-3">
                                            <div>
                                                <label class="text-xs text-gray-600 block mb-1">
                                                    Kondisi Fisik Barang <span class="text-red-500">*</span>
                                                </label>
                                                <select name="kondisi_dokumentasi" id="kondisi_dokumentasi" required
                                                    class="w-full bg-white border border-gray-300 rounded px-2 py-1.5 text-xs text-gray-700">
                                                    <option value="">-- Pilih Kondisi --</option>
                                                    <option value="baru">🟢 Baru</option>
                                                    <option value="baik">🔵 Baik</option>
                                                    <option value="rusak_ringan">🟡 Rusak Ringan</option>
                                                    <option value="rusak_berat">🔴 Rusak Berat</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-xs text-gray-600 block mb-1">Keterangan Tambahan</label>
                                                <textarea name="keterangan_dokumentasi" rows="4"
                                                    placeholder="Contoh: Terdapat goresan kecil di sudut kanan..."
                                                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs resize-none focus:outline-none focus:border-amber-400"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- ── end DOKUMENTASI FOTO ── --}}

                        </div>

                        <div class="flex items-center space-x-2 mt-6 pt-4 border-t border-gray-100">
                            <button type="button" onclick="triggerKonfirmQR()"
                                class="bg-[#1ab394] text-white px-5 py-1.5 rounded text-xs hover:bg-[#18a689] font-bold shadow-xs cursor-pointer">
                                <i class="fa fa-save mr-1"></i> Simpan & Ajukan Verifikasi
                            </button>
                            <span class="text-[10px] text-gray-400">* Pastikan foto kondisi barang sudah diunggah</span>
                        </div>
                    </div>

                    {{-- ── KOLOM KANAN 1/4 ── --}}
                    <div class="w-full lg:w-1/4 flex flex-col space-y-4">
                        <div class="bg-white p-4 border border-gray-200 rounded">
                            <label class="text-xs font-semibold text-gray-600 block mb-1">No SAP Barang (PT Wismilak)</label>
                            <input type="text" name="no_sap" placeholder="Ketik No SAP" required
                                class="w-full border border-gray-300 rounded px-2 py-1.5 font-mono text-xs focus:outline-none">
                        </div>

                        <div class="bg-white p-4 border border-amber-200 rounded text-[11px] text-gray-500 space-y-1.5 leading-relaxed">
                            <div class="font-bold text-amber-700 text-xs uppercase tracking-wide mb-2">
                                <i class="fa fa-info-circle mr-1"></i> Panduan Foto
                            </div>
                            <div>📸 Foto <strong>tampak depan</strong> keseluruhan barang</div>
                            <div>📸 Foto <strong>label / stiker</strong> serial number</div>
                            <div>📸 Foto <strong>kondisi fisik</strong> yang perlu dicatat</div>
                            <div class="text-[10px] text-gray-400 mt-2 pt-2 border-t border-gray-100">
                                Format JPG/PNG · Maks 2MB/foto · Maks 5 foto
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>


    {{-- ════════════════════════════════════════════════════════════════════════
         MODAL: Galeri Foto Dokumentasi
         ════════════════════════════════════════════════════════════════════════ --}}
    <div id="foto-modal" class="fixed inset-0 bg-black/75 z-[70] flex items-center justify-center hidden p-4">
        <div class="bg-white rounded shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-200">
            <div class="bg-amber-500 px-4 py-3 text-white flex justify-between items-center">
                <h4 class="font-bold text-xs uppercase tracking-wide m-0">
                    <i class="fa fa-camera mr-2"></i>Galeri Dokumentasi Foto Aset
                </h4>
                <button onclick="closeFotoModal()" class="text-white text-xl font-light bg-transparent border-0 cursor-pointer">&times;</button>
            </div>
            <div class="p-5">
                <div id="foto-modal-info" class="mb-4 p-3 bg-amber-50 border border-amber-100 rounded text-xs text-gray-600"></div>
                <div id="foto-modal-grid" class="grid grid-cols-2 sm:grid-cols-3 gap-3"></div>
            </div>
        </div>
    </div>

    {{-- Data foto per task untuk JS galeri --}}
    <script>
        var fotoDataPerTask = {
            @foreach($assignments as $task)
            @php
                $fotosJs = \App\Models\DokumentasiAset::where('aset_id', $task->aset_id)
                    ->where('jenis_dokumentasi', 'input_aset')
                    ->get();
            @endphp
            {{ $task->id }}: {
                kondisi: "{{ $fotosJs->first()->kondisi ?? '' }}",
                keterangan: "{{ addslashes($fotosJs->first()->keterangan ?? '') }}",
                fotos: [
                    @foreach($fotosJs as $f)
                    "{{ asset('storage/' . $f->url_foto) }}",
                    @endforeach
                ]
            },
            @endforeach
        };
    </script>


    {{-- ════════════════════════════════════════════════════════════════════════
         MODAL: Assign / Edit Tugas (Supervisor)
         ════════════════════════════════════════════════════════════════════════ --}}
    <div id="assign-modal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center hidden backdrop-blur-xs">
        <div class="bg-white rounded shadow-xl w-full max-w-md overflow-hidden">
            <div class="bg-[#1ab394] px-4 py-3 text-white flex justify-between items-center">
                <h4 class="font-bold text-xs uppercase m-0" id="modal-title-supervisor">
                    <i class="fa fa-paper-plane mr-2"></i>Buat Penugasan Logistik
                </h4>
                <button onclick="closeAssignModal()" class="text-white text-sm font-bold bg-transparent border-0 cursor-pointer">&times;</button>
            </div>
            <form id="supervisor-task-form" method="POST" class="p-5 m-0 space-y-4">
                @csrf
                <div id="method-field-placeholder"></div>
                <div>
                    <label class="font-bold text-gray-700 text-xs uppercase block mb-1">Instruksi Kerja</label>
                    <textarea name="deskripsi" id="task-deskripsi" placeholder="Masukkan instruksi penugasan..." required
                        class="w-full min-h-[80px] border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none" rows="3"></textarea>
                </div>
                <div>
                    <label class="font-bold text-gray-700 text-xs uppercase block mb-1">Pilih Anggota Tim Lapangan</label>
                    <select name="assigned_to" id="task-assigned-to" required
                        class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none">
                        @foreach($timUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end space-x-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeAssignModal()"
                        class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-300 rounded cursor-pointer">Batal</button>
                    <button type="button" onclick="triggerKonfirmWA()"
                        class="px-4 py-1.5 text-xs text-white bg-[#1ab394] rounded hover:bg-[#18a689] font-bold shadow-xs cursor-pointer">
                        <i class="fa fa-paper-plane mr-1"></i> Simpan & Kirim WA
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ════════════════════════════════════════════════════════════════════════
         MODAL: Approval (Supervisor)
         ════════════════════════════════════════════════════════════════════════ --}}
    <div id="approval-modal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center hidden backdrop-blur-xs">
        <div class="bg-white rounded shadow-xl w-full max-w-md overflow-hidden border border-gray-200">
            <div class="bg-[#f8ac59] px-4 py-3 text-white flex justify-between items-center">
                <h4 class="font-bold text-xs uppercase tracking-wider mb-0">
                    <i class="fa fa-gavel mr-2"></i>Keputusan Pengesahan Kelayakan Aset
                </h4>
                <button onclick="closeApprovalModal()" class="text-white hover:text-gray-200 text-sm font-bold bg-transparent border-0 cursor-pointer">&times;</button>
            </div>
            <form id="approval-asset-form" method="POST" class="p-5 m-0 space-y-4">
                @csrf
                <div>
                    <label class="font-bold text-gray-700 text-xs uppercase tracking-wide block mb-1">Keputusan Akhir</label>
                    <select name="status_keputusan" required class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-xs text-gray-700 focus:outline-none">
                        <option value="acc">SETUJUI (Status READY & Valid Masuk Inventaris PT Wismilak)</option>
                        <option value="tolak">TOLAK / REVISI DATA (Kirim Balik ke Tim Lapangan)</option>
                    </select>
                </div>
                <div>
                    <label class="font-bold text-gray-700 text-xs uppercase tracking-wide block mb-1">Catatan Alasan / Perbaikan</label>
                    <textarea name="catatan" placeholder="Tulis catatan..." class="w-full min-h-[60px] border border-gray-300 rounded px-3 py-2 text-xs" rows="2"></textarea>
                </div>
                <div class="flex justify-end space-x-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeApprovalModal()"
                        class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-300 rounded cursor-pointer">Batal</button>
                    <button type="submit"
                        class="px-4 py-1.5 text-xs text-white bg-[#f8ac59] rounded hover:bg-[#e49b4e] cursor-pointer shadow-xs">
                        Simpan Keputusan
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ════════════════════════════════════════════════════════════════════════
         MODAL KONFIRMASI: Hapus Permanen
         ════════════════════════════════════════════════════════════════════════ --}}
    <div id="modal-hapus" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center hidden backdrop-blur-xs">
        <div class="bg-white rounded shadow-xl w-full max-w-sm overflow-hidden border border-gray-200">
            <div class="bg-red-500 px-4 py-3 text-white flex items-center gap-2">
                <i class="fa fa-exclamation-triangle"></i>
                <h4 class="font-bold text-xs uppercase tracking-wider m-0">Konfirmasi Hapus Permanen</h4>
            </div>
            <div class="p-5 space-y-2">
                <p class="text-xs text-gray-600 leading-relaxed">
                    <span class="font-bold text-red-600">⚠️ PERINGATAN:</span> Menghapus tugas ini akan menghapus seluruh data draf spesifikasi aset
                    <strong>dan semua foto dokumentasi</strong> secara <span class="font-bold">permanen</span>.
                </p>
                <p class="text-xs text-gray-500">Apakah Anda yakin ingin melanjutkan?</p>
            </div>
            <div class="flex justify-end gap-2 px-5 pb-4">
                <button type="button" onclick="closeModalHapus()"
                    class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 cursor-pointer">
                    Batal
                </button>
                <form id="form-hapus-target" method="POST" class="m-0 inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-1.5 text-xs text-white bg-red-500 rounded hover:bg-red-600 font-bold cursor-pointer shadow-xs">
                        <i class="fa fa-trash mr-1"></i> Ya, Hapus Permanen
                    </button>
                </form>
            </div>
        </div>
    </div>


    {{-- ════════════════════════════════════════════════════════════════════════
         MODAL KONFIRMASI: Kirim WA
         ════════════════════════════════════════════════════════════════════════ --}}
    <div id="modal-konfirm-wa" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center hidden backdrop-blur-xs">
        <div class="bg-white rounded shadow-xl w-full max-w-sm overflow-hidden border border-gray-200">
            <div class="bg-[#1ab394] px-4 py-3 text-white flex items-center gap-2">
                <i class="fa fa-whatsapp"></i>
                <h4 class="font-bold text-xs uppercase tracking-wider m-0">Konfirmasi Kirim Penugasan</h4>
            </div>
            <div class="p-5">
                <p class="text-xs text-gray-600 leading-relaxed">
                    🔔 Apakah Anda ingin menyimpan data ini sekaligus memicu pengiriman
                    <span class="font-bold text-[#1ab394]">notifikasi WhatsApp</span>
                    ke perangkat HP Tim Lapangan?
                </p>
            </div>
            <div class="flex justify-end gap-2 px-5 pb-4">
                <button type="button" onclick="closeModalKonfirmWA()"
                    class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 cursor-pointer">
                    Batal
                </button>
                <button type="button" onclick="submitSupervisorForm()"
                    class="px-4 py-1.5 text-xs text-white bg-[#1ab394] rounded hover:bg-[#18a689] font-bold cursor-pointer shadow-xs">
                    <i class="fa fa-paper-plane mr-1"></i> Ya, Simpan & Kirim WA
                </button>
            </div>
        </div>
    </div>


    {{-- ════════════════════════════════════════════════════════════════════════
         MODAL KONFIRMASI: Generate QR Code
         ════════════════════════════════════════════════════════════════════════ --}}
    <div id="modal-konfirm-qr" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center hidden backdrop-blur-xs">
        <div class="bg-white rounded shadow-xl w-full max-w-sm overflow-hidden border border-gray-200">
            <div class="bg-blue-600 px-4 py-3 text-white flex items-center gap-2">
                <i class="fa fa-qrcode"></i>
                <h4 class="font-bold text-xs uppercase tracking-wider m-0">Konfirmasi Generate QR Code</h4>
            </div>
            <div class="p-5">
                <p class="text-xs text-gray-600 leading-relaxed">
                    Apakah Anda yakin data spesifikasi sudah <span class="font-bold">benar</span>
                    dan foto dokumentasi sudah <span class="font-bold">diunggah</span>?
                    Tindakan ini akan meng-generate <span class="font-bold text-blue-600">QR Code</span> otomatis untuk aset ini.
                </p>
            </div>
            <div class="flex justify-end gap-2 px-5 pb-4">
                <button type="button" onclick="closeModalKonfirmQR()"
                    class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-300 rounded hover:bg-gray-50 cursor-pointer">
                    Batal, Cek Ulang
                </button>
                <button type="button" onclick="submitAssetForm()"
                    class="px-4 py-1.5 text-xs text-white bg-blue-600 rounded hover:bg-blue-700 font-bold cursor-pointer shadow-xs">
                    <i class="fa fa-save mr-1"></i> Ya, Simpan & Generate QR
                </button>
            </div>
        </div>
    </div>


    {{-- ════════════════════════════════════════════════════════════════════════
         JAVASCRIPT
         ════════════════════════════════════════════════════════════════════════ --}}
    <script>
        // ── MODAL HAPUS ──────────────────────────────────────────────────────
        function confirmHapus(taskId) {
            document.getElementById('form-hapus-target').action = '/aset/delete/' + taskId;
            document.getElementById('modal-hapus').classList.remove('hidden');
        }
        function closeModalHapus() {
            document.getElementById('modal-hapus').classList.add('hidden');
        }

        // ── ASSIGN MODAL (Supervisor) ────────────────────────────────────────
        function openAssignModal() {
            document.getElementById('supervisor-task-form').action = "{{ route('aset.storeTask') }}";
            document.getElementById('method-field-placeholder').innerHTML = '';
            document.getElementById('modal-title-supervisor').innerHTML = '<i class="fa fa-paper-plane mr-2"></i>Buat Penugasan Logistik';
            document.getElementById('task-deskripsi').value = '';
            document.getElementById('assign-modal').classList.remove('hidden');
        }
        function openEditModal(taskId, deskripsi, assignedTo) {
            document.getElementById('supervisor-task-form').action = '/aset/update/' + taskId;
            document.getElementById('method-field-placeholder').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('modal-title-supervisor').innerHTML = '<i class="fa fa-pencil mr-2"></i>Edit Instruksi Penugasan';
            document.getElementById('task-deskripsi').value = deskripsi;
            document.getElementById('task-assigned-to').value = assignedTo;
            document.getElementById('assign-modal').classList.remove('hidden');
        }
        function closeAssignModal() {
            document.getElementById('assign-modal').classList.add('hidden');
        }
        function triggerKonfirmWA() {
            var deskripsi  = document.getElementById('task-deskripsi').value.trim();
            var assignedTo = document.getElementById('task-assigned-to').value;
            if (!deskripsi || !assignedTo) {
                alert('Instruksi kerja dan anggota tim wajib diisi.');
                return;
            }
            document.getElementById('modal-konfirm-wa').classList.remove('hidden');
        }
        function closeModalKonfirmWA() {
            document.getElementById('modal-konfirm-wa').classList.add('hidden');
        }
        function submitSupervisorForm() {
            closeModalKonfirmWA();
            closeAssignModal();
            document.getElementById('supervisor-task-form').submit();
        }

        // ── INPUT MODAL (Tim Lapangan) ───────────────────────────────────────
        function openInputModal(taskId) {
            document.getElementById('input-asset-form').action = '/aset/submit-data/' + taskId;
            document.getElementById('input-modal').classList.remove('hidden');
        }
        function closeInputModal() {
            selectedFiles = [];
            renderFotoPreview();
            document.getElementById('input-modal').classList.add('hidden');
        }
        function triggerKonfirmQR() {
            var form = document.getElementById('input-asset-form');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            if (!document.getElementById('kondisi_dokumentasi').value) {
                alert('Kondisi fisik barang wajib dipilih pada seksi Dokumentasi Foto.');
                document.getElementById('kondisi_dokumentasi').focus();
                return;
            }
            if (selectedFiles.length === 0) {
                alert('Minimal 1 foto dokumentasi kondisi barang wajib diunggah.');
                document.getElementById('drop-zone').scrollIntoView({ behavior: 'smooth' });
                return;
            }
            document.getElementById('modal-konfirm-qr').classList.remove('hidden');
        }
        function closeModalKonfirmQR() {
            document.getElementById('modal-konfirm-qr').classList.add('hidden');
        }
        function submitAssetForm() {
            closeModalKonfirmQR();
            document.getElementById('input-asset-form').submit();
        }

        // ── APPROVAL MODAL ───────────────────────────────────────────────────
        function openApprovalModal(taskId) {
            document.getElementById('approval-asset-form').action = '/aset/verify/' + taskId;
            document.getElementById('approval-modal').classList.remove('hidden');
        }
        function closeApprovalModal() {
            document.getElementById('approval-modal').classList.add('hidden');
        }

        // ── TOGGLE SPEC BLOCK ────────────────────────────────────────────────
        function toggleSpesifikasiField() {
            var jenis = document.getElementById('form-select-jenis').value;
            var block = document.getElementById('custom-spec-block');
            (jenis === 'Notebook' || jenis === 'Komputer')
                ? block.classList.remove('hidden')
                : block.classList.add('hidden');
        }

        // ── PRINT QR LABEL ───────────────────────────────────────────────────
        function printQrLabel(qrCodeText, sapNumber, assetNumber) {
            var win = window.open('', '_blank', 'width=450,height=450');
            var url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(qrCodeText);
            win.document.write('<html><head><title>Print Sticker QR</title><style>'
                + 'body{font-family:"Courier New",monospace;text-align:center;padding:10px;margin:0}'
                + '.container{border:2px dashed #000;padding:10px;display:inline-block;width:280px}'
                + '.header-title{font-weight:bold;font-size:12px;border-bottom:1px solid #000;padding-bottom:4px}'
                + '.qr-img{width:130px;height:130px;margin:8px auto;display:block}'
                + '.meta-info{font-size:10px;text-align:left;margin-top:6px;font-weight:bold}'
                + '</style></head><body>'
                + '<div class="container">'
                + '<div class="header-title">PROPERTY OF WISMILAK</div>'
                + '<img src="' + url + '" class="qr-img"/>'
                + '<div class="meta-info">'
                + '<div>• SYSTEM ID : ' + qrCodeText + '</div>'
                + '<div>• SAP NO   : ' + sapNumber + '</div>'
                + '<div>• ASSET NO : ' + assetNumber + '</div>'
                + '</div></div>'
                + '<script>window.onload=function(){window.print();window.close();}<\/script>'
                + '</body></html>');
            win.document.close();
        }

        // ═══════════════════════════════════════════════════════════════════════
        // DOKUMENTASI FOTO
        // ═══════════════════════════════════════════════════════════════════════
        var selectedFiles = [];
        var MAX_FOTO = 5;

        function handleFileSelect(input) {
            addFilesToPreview(Array.from(input.files));
        }

        function handleDrop(event) {
            event.preventDefault();
            document.getElementById('drop-zone').classList.remove('border-amber-400', 'bg-amber-100');
            var files = Array.from(event.dataTransfer.files)
                .filter(function(f) {
                    return ['image/jpeg','image/png','image/jpg'].includes(f.type);
                });
            addFilesToPreview(files);
        }

        function addFilesToPreview(files) {
            var errors = [];
            files.forEach(function(file) {
                if (selectedFiles.length >= MAX_FOTO) {
                    errors.push('Maksimal ' + MAX_FOTO + ' foto.');
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    errors.push('File "' + file.name + '" melebihi batas 2MB.');
                    return;
                }
                selectedFiles.push(file);
            });
            if (errors.length) alert([...new Set(errors)].join('\n'));
            renderFotoPreview();
            syncFileInput();
        }

        function renderFotoPreview() {
            var grid = document.getElementById('foto-preview-grid');
            var sisa = document.getElementById('sisa-foto');
            sisa.textContent = MAX_FOTO - selectedFiles.length;
            grid.innerHTML = '';
            if (selectedFiles.length === 0) {
                grid.classList.add('hidden');
                return;
            }
            grid.classList.remove('hidden');
            selectedFiles.forEach(function(file, idx) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var div = document.createElement('div');
                    div.className = 'relative group';
                    div.innerHTML =
                        '<img src="' + e.target.result + '" '
                        + 'class="w-full h-16 object-cover rounded border-2 border-gray-200 '
                        + 'group-hover:border-amber-400 transition shadow-xs">'
                        + '<button type="button" onclick="removeFile(' + idx + ')" '
                        + 'class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] '
                        + 'w-4 h-4 rounded-full hidden group-hover:flex items-center '
                        + 'justify-center font-bold leading-none">&times;</button>'
                        + '<div class="text-[9px] text-gray-400 mt-0.5 truncate">' + file.name + '</div>';
                    grid.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        function removeFile(idx) {
            selectedFiles.splice(idx, 1);
            renderFotoPreview();
            syncFileInput();
        }

        function syncFileInput() {
            try {
                var dt = new DataTransfer();
                selectedFiles.forEach(function(f) { dt.items.add(f); });
                document.getElementById('foto-input').files = dt.files;
            } catch(e) {}
        }

        // ── GALERI FOTO (view-only) ──────────────────────────────────────────
        var kondisiLabel = {
            'baru': '🟢 Baru',
            'baik': '🔵 Baik',
            'rusak_ringan': '🟡 Rusak Ringan',
            'rusak_berat': '🔴 Rusak Berat'
        };

        function openFotoModal(taskId) {
            var data = fotoDataPerTask[taskId];
            if (!data || !data.fotos || data.fotos.length === 0) return;

            var info = document.getElementById('foto-modal-info');
            info.innerHTML =
                '<span class="font-semibold">Kondisi:</span> ' + (kondisiLabel[data.kondisi] || data.kondisi)
                + (data.keterangan
                    ? ' &nbsp;&bull;&nbsp; <span class="font-semibold">Catatan:</span> ' + data.keterangan
                    : '')
                + ' &nbsp;&bull;&nbsp; <span class="font-semibold">' + data.fotos.length + ' foto</span>';

            var grid = document.getElementById('foto-modal-grid');
            grid.innerHTML = '';
            data.fotos.forEach(function(url) {
                var div = document.createElement('div');
                div.innerHTML =
                    '<a href="' + url + '" target="_blank" title="Buka ukuran penuh">'
                    + '<img src="' + url + '" '
                    + 'class="w-full h-40 object-cover rounded border-2 border-gray-200 '
                    + 'hover:border-amber-400 transition shadow-xs cursor-zoom-in">'
                    + '</a>';
                grid.appendChild(div);
            });

            document.getElementById('foto-modal').classList.remove('hidden');
        }

        function closeFotoModal() {
            document.getElementById('foto-modal').classList.add('hidden');
        }

        // ── TUTUP MODAL KLIK BACKDROP ────────────────────────────────────────
        ['modal-hapus','modal-konfirm-wa','modal-konfirm-qr',
         'input-modal','assign-modal','approval-modal','foto-modal'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('click', function(e) {
                    if (e.target === this) {
                        if (id === 'input-modal') closeInputModal();
                        else this.classList.add('hidden');
                    }
                });
            }
        });
    </script>

@endsection