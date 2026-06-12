@extends('layouts.app')

@section('title', 'Input Spesifikasi Aset')

@section('content')
<div class="flex flex-col h-screen overflow-hidden bg-[#f3f3f4]">
    
    {{-- ── FIXED STICKY HEADER (TIDAK IKUT SCROLL) ── --}}
    <div class="bg-white border-b border-[#e7eaec] px-6 py-4 flex justify-between items-center flex-shrink-0 shadow-2xs z-10">
        <div>
            <h2 class="text-xl font-light text-gray-700 mb-0.5">Form Input Spesifikasi Aset Logistik</h2>
            <div class="text-xs text-gray-400">Tugas ID #{{ $assignment->id }} &mdash; Pendaftaran inventaris akurat terintegrasi database PT Wismilak.</div>
        </div>
        <a href="{{ route('master_aset.index') }}" class="bg-white border border-gray-300 text-gray-600 px-3 py-2 rounded text-xs font-semibold no-underline hover:bg-gray-50 transition shadow-3xs flex items-center gap-1">
            <i class="fa fa-arrow-left"></i> Kembali ke Monitoring
        </a>
    </div>

    {{-- Pembacaan Awal Atribut EAV Draf Lama & Berkas Foto (ANTI-NULL SAFE GATE) --}}
    @php
        $oldAttributes = \DB::table('asset_attributes')
            ->where('aset_id', $assignment->aset_id)
            ->pluck('attribute_value', 'attribute_name')
            ->toArray();
            
        $drafFotoLama = \DB::table('dokumentasi_aset')
            ->where('aset_id', $assignment->aset_id)
            ->where('jenis_dokumentasi', 'input_aset')
            ->first();

        $currentJenis = old('jenis', $oldAttributes['jenis'] ?? '');
        $currentKondisi = old('status_kondisi', $drafFotoLama->kondisi ?? '');
        $currentCatatanFoto = old('keterangan_dokumentasi', $drafFotoLama->keterangan ?? '');
    @endphp

    {{-- ── SCROLLABLE CONTAINER AREA (HANYA AREA FORM INI YANG DI-SCROLL) ── --}}
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-5xl mx-auto">
            <form id="smart-asset-form" action="/aset/submit-data/{{ $assignment->id }}" method="POST" enctype="multipart/form-data" class="m-0 space-y-5">
                @csrf

                {{-- ── INSTRUKSI SUPERVISOR ── --}}
                <div class="bg-blue-50 border border-blue-200 rounded p-4 text-xs text-blue-700 shadow-3xs">
                    <div class="font-bold uppercase mb-1"><i class="fa fa-bullhorn mr-1"></i> Instruksi Supervisor:</div>
                    <div class="text-sm font-medium text-blue-800">{{ $assignment->deskripsi }}</div>
                </div>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded p-4 text-xs text-red-600 shadow-3xs">
                        <i class="fa fa-exclamation-circle mr-1"></i> {{ $errors->first() }}
                    </div>
                @endif

                {{-- ── CHECKPOINT KERJA WAJIB ── --}}
                <div class="bg-white border border-gray-200 rounded p-5 shadow-xs">
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-3 flex items-center gap-2 tracking-wide">
                        <i class="fa fa-list-ul text-blue-500"></i> Verifikasi Checkpoint Kerja Wajib <span class="text-red-500">*</span>
                    </h4>
                    <div class="text-[11px] text-gray-400 mb-3">Harap centang semua tugas penunjang di bawah ini yang telah diselesaikan secara fisik di lapangan.</div>

                    <div class="space-y-2" id="wrapper-checkpoint-tim">
                        @forelse($checkpoints as $cp)
                            <div class="flex items-center gap-2 checkpoint-row p-1.5 rounded hover:bg-gray-50/50 transition border border-transparent hover:border-gray-100">
                                <input type="checkbox" name="checked_points[]" value="{{ $cp->id }}" {{ $cp->is_checked ? 'checked' : '' }} onchange="toggleStrikethrough(this)" class="w-4 h-4 text-teal-600 accent-teal-500 cursor-pointer flex-shrink-0 focus:ring-0">
                                <input type="text" name="checkpoint_names[{{ $cp->id }}]" value="{{ $cp->nama_poin }}" readonly class="flex-1 bg-transparent border-0 text-xs focus:outline-none checkpoint-label {{ $cp->is_checked ? 'line-through text-gray-400 font-normal' : 'text-gray-700 font-semibold' }}">
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic bg-gray-50 p-3 rounded text-center">Tidak ada rincian checkpoint khusus dari supervisor.</p>
                        @endforelse
                    </div>

                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <div class="text-[10px] font-bold text-gray-400 uppercase mb-2">Tambah Temuan Checkpoint Baru (Opsional):</div>
                        <div id="wrapper-checkpoint-baru" class="space-y-2"></div>
                        <button type="button" onclick="tambahCheckpointBaru()" class="mt-2 bg-gray-100 text-gray-600 border border-gray-300 px-3 py-1.5 text-xs rounded font-medium cursor-pointer hover:bg-gray-200 transition shadow-3xs">
                            <i class="fa fa-plus mr-1 text-inspiniaPrimary"></i> Tambah Baris Catatan Checkpoint
                        </button>
                    </div>
                </div>

                {{-- ── DATA IDENTIFIKASI UTAMA ASET (AUTO-GET BACKEND REFILL) ── --}}
                <div class="bg-white border border-gray-200 rounded p-5 shadow-xs">
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-4 flex items-center gap-2 tracking-wide">
                        <i class="fa fa-tag text-green-500"></i> Atribut Logistik &amp; Identifikasi Utama
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="font-bold text-gray-600 text-xs uppercase block mb-1">Nomor SAP Aset <span class="text-red-500">*</span></label>
                            <input type="text" name="no_sap" value="{{ old('no_sap', $assignment->aset->no_sap) }}" required placeholder="Contoh: 1000023451" class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-inspiniaPrimary font-mono">
                        </div>

                        <div>
                            <label class="font-bold text-gray-600 text-xs uppercase block mb-1">Brand / Merek <span class="text-red-500">*</span></label>
                            <input type="text" name="brand" value="{{ old('brand', $oldAttributes['brand'] ?? '') }}" required placeholder="Contoh: Dell, HP, Mikrotik" list="saran-brand" class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-inspiniaPrimary">
                            <datalist id="saran-brand">
                                @foreach($saranBrand as $b) <option value="{{ $b }}"> @endforeach
                            </datalist>
                        </div>

                        <div>
                            <label class="font-bold text-gray-600 text-xs uppercase block mb-1">Tipe / Seri Model <span class="text-red-500">*</span></label>
                            <input type="text" name="tipe" value="{{ old('tipe', $oldAttributes['tipe'] ?? '') }}" required placeholder="Contoh: Latitude 3420, RB1100Dx4" list="saran-tipe" class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-inspiniaPrimary">
                            <datalist id="saran-tipe">
                                @foreach($saranTipe as $t) <option value="{{ $t }}"> @endforeach
                            </datalist>
                        </div>

                        <div>
                            <label class="font-bold text-gray-600 text-xs uppercase block mb-1">Pilih Jenis Kategori Perangkat <span class="text-red-500">*</span></label>
                            <select name="jenis" id="form-select-jenis" onchange="renderSmartSpecificationFields()" required class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-xs text-gray-700 focus:outline-none focus:border-inspiniaPrimary font-semibold cursor-pointer">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Notebook" {{ $currentJenis === 'Notebook' ? 'selected' : '' }}>Notebook / Laptop</option>
                                <option value="Komputer" {{ $currentJenis === 'Komputer' ? 'selected' : '' }}>Komputer / PC Desktop</option>
                                <option value="Mikrotik" {{ $currentJenis === 'Mikrotik' ? 'selected' : '' }}>Mikrotik Router</option>
                                <option value="Monitor" {{ $currentJenis === 'Monitor' ? 'selected' : '' }}>Monitor Display</option>
                                <option value="Printer" {{ $currentJenis === 'Printer' ? 'selected' : '' }}>Printer Scanner</option>
                                <option value="Switch Hub" {{ $currentJenis === 'Switch Hub' ? 'selected' : '' }}>Switch Hub Network</option>
                            </select>
                        </div>

                        <div>
                            <label class="font-bold text-gray-600 text-xs uppercase block mb-1">Tahun Pembelian <span class="text-red-500">*</span></label>
                            <input type="date" name="tahun_pembelian" value="{{ old('tahun_pembelian', $oldAttributes['tahun_pembelian'] ?? '') }}" required class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-inspiniaPrimary">
                        </div>

                        <div>
                            <label class="font-bold text-gray-600 text-xs uppercase block mb-1">Supplier / Vendor Pengadaan <span class="text-red-500">*</span></label>
                            <input type="text" name="supplier" value="{{ old('supplier', $oldAttributes['supplier'] ?? '') }}" required placeholder="Nama Vendor Pengadaan" class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-inspiniaPrimary">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-50 text-gray-600">
                        <div><label class="font-bold text-xs uppercase block mb-1">Nama Pengguna Aset <span class="text-red-500">*</span></label><input type="text" name="nama_pengguna" value="{{ old('nama_pengguna', $oldAttributes['nama_pengguna'] ?? '') }}" required placeholder="Nama Karyawan Pemakai" class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-inspiniaPrimary"></div>
                        <div><label class="font-bold text-xs uppercase block mb-1">Lokasi Gedung / Site <span class="text-red-500">*</span></label><input type="text" name="lokasi" value="{{ old('lokasi', $oldAttributes['lokasi'] ?? '') }}" required placeholder="Contoh: Gedung Karangpilang" class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-inspiniaPrimary"></div>
                        <div><label class="font-bold text-xs uppercase block mb-1">Nama Ruangan <span class="text-red-500">*</span></label><input type="text" name="ruang" value="{{ old('ruang', $oldAttributes['ruang'] ?? '') }}" required placeholder="Contoh: Ruang IT Server" class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-inspiniaPrimary"></div>
                        <div><label class="font-bold text-xs uppercase block mb-1">Departemen / Sektor <span class="text-red-500">*</span></label><input type="text" name="departemen" value="{{ old('departemen', $oldAttributes['departemen'] ?? '') }}" required placeholder="Contoh: IT Infrastructure" class="w-full border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none focus:border-inspiniaPrimary"></div>
                    </div>
                </div>

                {{-- ── SMART FORM INPUT SPESIFIKASI DINAMIS ── --}}
                <div id="smart-spec-container" class="bg-white border border-gray-200 rounded p-5 shadow-xs hidden transition-all duration-300">
                    <h4 id="smart-spec-title" class="text-xs font-bold text-blue-700 uppercase mb-4 flex items-center gap-2 tracking-wide"></h4>
                    <div id="smart-spec-fields" class="grid grid-cols-1 md:grid-cols-3 gap-4"></div>
                </div>

                {{-- ── SEKSI DOKUMENTASI FOTO MULTI-UPLOAD ── --}}
                <div class="bg-white border border-gray-200 rounded p-5 shadow-xs">
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-3 flex items-center gap-2 tracking-wide">
                        <i class="fa fa-camera text-amber-500"></i> Lampiran Berkas Dokumentasi Foto Bukti Fisik
                        <span class="text-gray-300 font-normal normal-case">(Maks. 5 file, Format JPG/PNG, Maks. 2MB/Foto)</span>
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50/50 p-4 rounded border border-gray-200">
                        <div class="md:col-span-2">
                            <label class="font-bold text-gray-600 text-xs uppercase block mb-2">Unggah File Foto Kondisi <span class="text-red-500">*</span></label>
                            <div id="drop-zone" onclick="document.getElementById('foto-input').click()" ondrop="handleDrop(event)" ondragover="event.preventDefault(); this.classList.add('border-teal-500','bg-teal-50/30')" ondragleave="this.classList.remove('border-teal-500','bg-teal-50/30')"
                                class="border-2 border-dashed border-gray-300 rounded-lg p-5 text-center cursor-pointer hover:border-inspiniaPrimary hover:bg-gray-50 transition min-h-[90px] flex flex-col items-center justify-center gap-1">
                                <i class="fa fa-cloud-upload text-2xl text-gray-400"></i>
                                <div class="text-xs text-gray-600 font-medium">Klik untuk mencari file atau seret gambar kesini</div>
                                <div class="text-[10px] text-gray-400">Tersisa <span id="sisa-foto">5</span> slot berkas gambar</div>
                            </div>
                            <input type="file" id="foto-input" name="foto_dokumentasi[]" multiple accept="image/jpg,image/jpeg,image/png" onchange="handleFileSelect(this)" class="hidden">
                            
                            <div id="foto-preview-grid" class="grid grid-cols-3 sm:grid-cols-5 gap-2 mt-3 hidden"></div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <div>
                                <label class="font-bold text-gray-600 text-xs uppercase block mb-1">Status Kondisi Fisik Unit <span class="text-red-500">*</span></label>
                                <select name="status_kondisi" id="status_kondisi" required class="w-full bg-white border border-gray-300 rounded px-3 py-2 text-xs text-gray-700 focus:outline-none focus:border-inspiniaPrimary font-medium cursor-pointer">
                                    <option value="">-- Pilih Kondisi --</option>
                                    <option value="baru" {{ $currentKondisi === 'baru' ? 'selected' : '' }}>baru</option>
                                    <option value="baik" {{ $currentKondisi === 'baik' ? 'selected' : '' }}>baik</option>
                                    <option value="rusak_ringan" {{ $currentKondisi === 'rusak_ringan' ? 'selected' : '' }}>rusak_ringan</option>
                                    <option value="rusak_berat" {{ $currentKondisi === 'rusak_berat' ? 'selected' : '' }}>rusak_berat</option>
                                </select>
                            </div>
                            <div>
                                <label class="font-bold text-gray-600 text-xs uppercase block mb-1">Catatan Tambahan Fisik</label>
                                <textarea name="keterangan_dokumentasi" rows="2" placeholder="Contoh: Port nomor 3 kendor, baut bawah hilang 1..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs resize-none focus:outline-none focus:border-inspiniaPrimary">{{ $currentCatatanFoto }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── BUTTONS BAR CONTROLLER ── --}}
                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('master_aset.index') }}" class="px-4 py-2 text-xs text-gray-600 bg-white border border-gray-300 rounded no-underline hover:bg-gray-50 transition font-medium shadow-3xs flex items-center justify-center">
                        Batal
                    </a>
                    <button type="button" onclick="triggerValidationGate()" class="bg-[#1ab394] hover:bg-[#18a689] text-white px-6 py-2 rounded text-xs font-bold cursor-pointer border-0 shadow-sm transition">
                        <i class="fa fa-save mr-1"></i> Ajukan Draf Spesifikasi Aset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── POP-UP INTERAKTIF MODAL CONFIRMATION DIALOG SAFETY GATE ── --}}
<div id="modal-konfirm-save" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center hidden backdrop-blur-xs">
    <div class="bg-white rounded shadow-xl w-full max-w-sm overflow-hidden border border-gray-200 animate-fadeIn">
        <div class="bg-blue-600 px-4 py-3 text-white flex items-center gap-2">
            <i class="fa fa-file-text-o text-sm"></i>
            <h4 class="font-bold text-xs uppercase tracking-wider mb-0">Konfirmasi Kelayakan Data</h4>
        </div>
        <div class="p-5 text-xs text-gray-600 leading-relaxed space-y-2">
            <p>Apakah Anda menjamin pengisian rincian atribut spesifikasi dan berkas foto dokumentasi lapangan sudah akurat?</p>
            <p class="text-gray-400">Berkas akan dikirim ke Supervisor untuk divalidasi. Label <strong>QR Code resmi</strong> akan diterbitkan otomatis setelah pengajuan ini disetujui (ACC).</p>
        </div>
        <div class="flex justify-end gap-2 px-4 pb-4">
            <button type="button" onclick="closeModalKonfirm()" class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-300 rounded cursor-pointer hover:bg-gray-50">Batal, Periksa Lagi</button>
            <button type="button" onclick="executeFinalFormSubmit()" class="px-4 py-1.5 text-xs text-white bg-blue-600 rounded hover:bg-blue-700 font-bold cursor-pointer border-0 shadow-xs">Ya, Kirim ke Supervisor</button>
        </div>
    </div>
</div>

{{-- ── CORE SMART JS FIELD CONTROLLER ENGINE ── --}}
<script>
    const VALUES_REFRESH_DATABASE = {!! json_encode($oldAttributes) !!};

    const SCHEMA_FIELDS_DATABASE = {
        "Notebook": [
            { label: "Tipe Processor", name: "spec_processor", type: "text", placeholder: "Contoh: Intel i5-1135G7" },
            { label: "Kapasitas RAM", name: "spec_ram", type: "text", placeholder: "Contoh: 16 GB DDR4" },
            { label: "Kapasitas Penyimpanan / SSD", name: "spec_storage", type: "text", placeholder: "Contoh: 512 GB NVMe" },
            { label: "Model Kartu Grafis / VGA", name: "spec_vga", type: "text", placeholder: "Contoh: Intel Iris Xe Graphics" },
            { label: "Ukuran Layar", name: "spec_ukuran_layar", type: "text", placeholder: "Contoh: 14 Inch" },
            { label: "Sistem Operasi", name: "spec_sistem_operasi", type: "text", placeholder: "Contoh: Windows 11 Pro" },
            { label: "Kapasitas Baterai", name: "spec_baterai", type: "text", placeholder: "Contoh: 3 Cell 42Wh" },
            { label: "Serial Number Pabrik", name: "serial_number", type: "text", placeholder: "Ketik S/N Manufaktur" }
        ],
        "Komputer": [
            { label: "Tipe Processor Desktop", name: "spec_processor", type: "text", placeholder: "Contoh: Core i7-12700" },
            { label: "Kapasitas RAM Desktop", name: "spec_ram", type: "text", placeholder: "Contoh: 32 GB" },
            { label: "Kapasitas Penyimpanan / HDD/SSD", name: "spec_storage", type: "text", placeholder: "Contoh: 1 TB SSD + 2 TB HDD" },
            { label: "Model VGA Dedicated", name: "spec_vga", type: "text", placeholder: "Contoh: NVIDIA RTX 3060" },
            { label: "Kapasitas Power Supply (PSU)", name: "spec_psu", type: "text", placeholder: "Contoh: 550W 80 Plus Gold" },
            { label: "Sistem Operasi Installed", name: "spec_sistem_operasi", type: "text", placeholder: "Contoh: Windows 10 Pro" },
            { label: "Serial Number Unit", name: "serial_number", type: "text", placeholder: "S/N Casing / Motherboard" }
        ],
        "Mikrotik": [
            { label: "Serial Number Perangkat", name: "serial_number", type: "text", placeholder: "Ketik S/N Mikrotik" },
            { label: "IP Address Perangkat", name: "ip_address", type: "text", placeholder: "Contoh: 192.168.88.1" },
            { label: "Mac Address Port Utama", name: "mac_address", type: "text", placeholder: "Contoh: AA:BB:CC:DD:EE:FF" },
            { label: "Jumlah Port LAN (Ethernet)", name: "jumlah_port_lan", type: "number", placeholder: "Contoh: 5, 8, 24" },
            { label: "Jumlah Port SFP / SFP+", name: "jumlah_port_sfp", type: "number", placeholder: "Contoh: 2" },
            { label: "Versi RouterOS Default", name: "versi_routeros", type: "text", placeholder: "Contoh: RouterOS v7.12" }
        ],
        "Monitor": [
            { label: "Ukuran Layar Monitor", name: "spec_ukuran_layar", type: "text", placeholder: "Contoh: 24 Inch" },
            { label: "Resolusi Layar Maksimal", name: "spec_resolusi", type: "text", placeholder: "Contoh: 1920x1080 Full HD" },
            { label: "Refresh Rate Layar", name: "spec_refresh_rate", type: "text", placeholder: "Contoh: 75 Hz / 144 Hz" },
            { label: "Jenis Panel Layar", name: "spec_jenis_panel", type: "text", placeholder: "Contoh: IPS, VA, TN" },
            { label: "Warna Unit Perangkat", name: "spec_warna", type: "text", placeholder: "Contoh: Hitam" },
            { label: "Ketersediaan Port Input", name: "spec_port", type: "text", placeholder: "Contoh: 1x HDMI, 1x DisplayPort" },
            { label: "Serial Number Monitor", name: "serial_number", type: "text", placeholder: "S/N di belakang panel display" }
        ],
        "Printer": [
            { label: "Jenis Printer", name: "jenis_printer", type: "text", placeholder: "Contoh: Inkjet, Laserjet, Dot Matrix" },
            { label: "Warna Cetak Output", name: "warna_monokrom", type: "text", placeholder: "Warna atau Monokrom" },
            { label: "Metode Cetak Label", name: "metode_cetak", type: "text", placeholder: "Thermal / Ink Tank" },
            { label: "Resolusi Maksimal Cetak", name: "resolusi_cetak", type: "text", placeholder: "Contoh: 1200 x 1200 dpi" },
            { label: "Konektivitas Interface", name: "konektivitas", type: "text", placeholder: "Contoh: USB, Wi-Fi, Ethernet" },
            { label: "IP Address Printer Network", name: "ip_address", type: "text", placeholder: "Contoh: 192.168.1.250 (Opsional)" },
            { label: "Serial Number Mesin Printer", name: "serial_number", type: "text", placeholder: "S/N Frame Printer" }
        ],
        "Switch Hub": [
            { label: "Jumlah Port Switch", name: "jumlah_port", type: "number", placeholder: "Contoh: 16, 24, 48" },
            { label: "Kecepatan Transfer Port", name: "kecepatan_port", type: "text", placeholder: "Contoh: 10/100/1000 Mbps" },
            { label: "Jenis Management Switch", name: "managed_unmanaged", type: "text", placeholder: "Managed / Unmanaged" },
            { label: "Dukungan Fitur PoE", name: "poe", type: "text", placeholder: "PoE Aktif / Non-PoE" },
            { label: "IP Address Switch Hub", name: "ip_address", type: "text", placeholder: "Ketik IP Manajemen (jika Managed)" },
            { label: "Serial Number Switch Hub", name: "serial_number", type: "text", placeholder: "S/N Rackmount" }
        ]
    };

    function renderSmartSpecificationFields() {
        const jenisTerpilih = document.getElementById('form-select-jenis').value;
        const container = document.getElementById('smart-spec-container');
        const title = document.getElementById('smart-spec-title');
        const fieldsWrapper = document.getElementById('smart-spec-fields');

        fieldsWrapper.innerHTML = "";

        if (!jenisTerpilih || !SCHEMA_FIELDS_DATABASE[jenisTerpilih]) {
            container.classList.add('hidden');
            return;
        }

        title.innerHTML = `<i class="fa fa-laptop"></i> Spesifikasi Khusus Kategori: ${jenisTerpilih}`;
        container.classList.remove('hidden');

        SCHEMA_FIELDS_DATABASE[jenisTerpilih].forEach(field => {
            const valueLama = VALUES_REFRESH_DATABASE[field.name] ? VALUES_REFRESH_DATABASE[field.name] : "";

            const blockDiv = document.createElement('div');
            blockDiv.className = "flex flex-col";
            blockDiv.innerHTML = `
                <label class="font-semibold text-gray-600 text-xs mb-1">${field.label} <span class="text-red-500">*</span></label>
                <input type="${field.type || 'text'}" name="${field.name}" value="${valueLama}" required placeholder="${field.placeholder}" class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs focus:outline-none focus:border-inspiniaPrimary">
            `;
            fieldsWrapper.appendChild(blockDiv);
        });
    }

    window.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('form-select-jenis').value !== "") {
            renderSmartSpecificationFields();
        }
    });

    function toggleStrikethrough(checkbox) {
        var label = checkbox.closest('.checkpoint-row').querySelector('.checkpoint-label');
        if (checkbox.checked) {
            label.classList.add('line-through', 'text-gray-400', 'font-normal');
            label.classList.remove('text-gray-700', 'font-semibold');
        } else {
            label.classList.remove('line-through', 'text-gray-400', 'font-normal');
            label.classList.add('text-gray-700', 'font-semibold');
        }
    }

    function tambahCheckpointBaru() {
        var wrapper = document.getElementById('wrapper-checkpoint-baru');
        var div = document.createElement('div');
        div.className = 'flex items-center gap-2 checkpoint-row-baru mt-1.5';
        div.innerHTML = `
            <input type="checkbox" name="new_checked_points[]" value="1" checked class="w-4 h-4 accent-green-500 cursor-pointer flex-shrink-0">
            <input type="text" name="new_checkpoints[]" required placeholder="Tulis rincian tugas tambahan wajib..." class="flex-1 border border-gray-300 rounded px-2.5 py-1.5 text-xs focus:outline-none focus:border-inspiniaPrimary text-gray-700 font-semibold bg-white shadow-3xs">
            <button type="button" onclick="this.closest('.checkpoint-row-baru').remove()" class="bg-red-500 text-white px-2.5 py-1.5 text-xs rounded border-0 cursor-pointer hover:bg-red-600 flex-shrink-0 transition"><i class="fa fa-minus"></i></button>
        `;
        wrapper.appendChild(div);
    }

    var selectedFiles = [];
    var MAX_FOTO = 5;

    function handleFileSelect(input) { addFilesToPreview(Array.from(input.files)); }
    function handleDrop(event) {
        event.preventDefault();
        this.classList.remove('border-teal-500','bg-teal-50/30');
        var files = Array.from(event.dataTransfer.files).filter(f => ['image/jpeg','image/png','image/jpg'].includes(f.type));
        addFilesToPreview(files);
    }

    function addFilesToPreview(files) {
        files.forEach(file => {
            if (selectedFiles.length >= MAX_FOTO) return;
            if (file.size > 2 * 1024 * 1024) { alert('Berkas gambar "' + file.name + '" melebihi batas 2MB!'); return; }
            selectedFiles.push(file);
        });
        renderFotoPreview();
        syncFileInput();
    }

    function renderFotoPreview() {
        var grid = document.getElementById('foto-preview-grid');
        document.getElementById('sisa-foto').textContent = MAX_FOTO - selectedFiles.length;
        grid.innerHTML = '';
        if (selectedFiles.length === 0) { grid.classList.add('hidden'); return; }
        grid.classList.remove('hidden');
        selectedFiles.forEach((file, idx) => {
            var reader = new FileReader();
            reader.onload = function(e) {
                var div = document.createElement('div');
                div.className = 'relative group';
                div.innerHTML = `<img src="${e.target.result}" class="w-full h-16 object-cover rounded border border-gray-200 shadow-xs">
                                 <button type="button" onclick="removeFile(${idx})" class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] w-4 h-4 rounded-full flex items-center justify-center font-bold border-0 cursor-pointer shadow-3xs">&times;</button>`;
                grid.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    function removeFile(idx) { selectedFiles.splice(idx, 1); renderFotoPreview(); syncFileInput(); }
    function syncFileInput() {
        try {
            var dt = new DataTransfer();
            selectedFiles.forEach(f => { dt.items.add(f); });
            document.getElementById('foto-input').files = dt.files;
        } catch(e) {}
    }

    function triggerValidationGate() {
        var form = document.getElementById('smart-asset-form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        if (form.querySelectorAll('input[name="checked_points[]"]:checked').length === 0) {
            alert('⚠️ Anda wajib menyelesaikan tugas operasional dan mencentang minimal 1 bukti checkpoint!');
            return;
        }
        if (selectedFiles.length === 0) {
            alert('⚠️ Anda wajib melampirkan minimal 1 berkas foto bukti fisik kondisi barang masuk!');
            return;
        }
        document.getElementById('modal-konfirm-save').classList.remove('hidden');
    }

    function closeModalKonfirm() { document.getElementById('modal-konfirm-save').classList.add('hidden'); }
    function executeFinalFormSubmit() {
        closeModalKonfirm();
        document.getElementById('smart-asset-form').submit();
    }

    document.getElementById('modal-konfirm-save').addEventListener('click', function(e) { if (e.target === this) closeModalKonfirm(); });
</script>
@endsection