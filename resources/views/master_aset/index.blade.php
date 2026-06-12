@extends('layouts.app')

@section('title', 'Asset Data Management')

@section('content')

    {{-- ── PAGE HEADER ── --}}
    <div class="bg-white border-b border-[#e7eaec] px-6 py-5 flex justify-between items-center">
        <div>
            <h2 class="text-xl font-light text-gray-700 mb-1">Asset Data Workflow</h2>
            <div class="text-xs text-gray-400">Pendaftaran spesifikasi logistik inventaris terintegrasi sistem pengingat otomatis Fonnte.</div>
        </div>
        @if(Auth::user()->role === 'supervisor' || Auth::user()->role === 'manager')
            <button onclick="openAssignModal()" class="bg-inspiniaPrimary text-white px-3 py-2 rounded text-xs font-semibold hover:bg-inspiniaPrimaryHover transition shadow-sm cursor-pointer border-0 outline-none">
                <i class="fa fa-plus mr-1"></i> Tambah &amp; Tugaskan Tim
            </button>
        @endif
    </div>

    {{-- ── MAIN TABLE ── --}}
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
                            <th class="p-3">Instruksi &amp; Checkpoint Supervisor</th>
                            <th class="p-3">Tim Lapangan</th>
                            <th class="p-3 text-center" style="width:130px;">QR CODE</th>
                            <th class="p-3 text-center" style="width:100px;">Foto</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-600">
                        @forelse($assignments as $task)
                        @php
                            $catatanSvp = DB::table('asset_attributes')
                                ->where('aset_id', $task->aset_id)
                                ->where('attribute_name', 'catatan_supervisor')
                                ->value('attribute_value');

                            $fotos = \App\Models\DokumentasiAset::where('aset_id', $task->aset_id)
                                ->where('jenis_dokumentasi', 'input_aset')
                                ->get();

                            $checkpoints = DB::table('assignment_checkpoints')
                                ->where('assignment_id', $task->id)
                                ->get();
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="p-3 font-mono text-gray-400">#{{ $task->id }}</td>

                            <td class="p-3">
                                <div class="font-medium text-gray-700 mb-2">{{ $task->deskripsi }}</div>

                                @if($checkpoints->count() > 0)
                                    <div class="flex flex-wrap gap-1 max-w-sm">
                                        @foreach($checkpoints as $cp)
                                            <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full font-medium border
                                                {{ $cp->is_checked
                                                    ? 'bg-green-50 text-green-700 border-green-200 line-through'
                                                    : 'bg-gray-100 text-gray-500 border-gray-200' }}">
                                                <i class="fa {{ $cp->is_checked ? 'fa-check-circle text-green-500' : 'fa-circle-o text-gray-300' }} mr-0.5"></i>{{ $cp->nama_poin }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($task->aset?->status === 'pending' && $catatanSvp && $catatanSvp !== 'Disetujui')
                                    <div class="text-[11px] text-red-600 font-semibold mt-2 bg-red-50 p-1.5 rounded border border-red-200 inline-block">
                                        <i class="fa fa-warning"></i> REVISI: "{{ $catatanSvp }}"
                                    </div>
                                @endif
                            </td>

                            <td class="p-3 font-medium text-gray-700">{{ $task->receiver->nama ?? '-' }}</td>

                            {{-- ── VALIDASI DETEKSI PENAYANGAN GAMBAR PREVIEW QR CODE ── --}}
                            <td class="p-3 text-center">
                                @if($task->aset?->status === 'ready' && $task->aset?->rfid_code)
                                    <div class="inline-block p-1 bg-white border border-gray-200 rounded shadow-2xs">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data={{ urlencode($task->aset->rfid_code) }}" class="w-[45px] h-[45px] block mx-auto">
                                    </div>
                                    <div class="text-[9px] font-mono text-gray-400 mt-1 font-bold">{{ $task->aset->rfid_code }}</div>
                                @elseif($task->aset?->status === 'pending_approval')
                                    <span class="text-orange-500 font-medium text-[11px] flex flex-col items-center justify-center gap-0.5">
                                        <i class="fa fa-lock text-sm"></i> Draf Terkunci<br><small class="text-gray-400 font-normal">(Menunggu ACC)</small>
                                    </span>
                                @else
                                    <span class="text-gray-300 italic">Belum Ada<br><small class="text-gray-400 font-normal">(Menunggu Input)</small></span>
                                @endif
                            </td>

                            <td class="p-3 text-center">
                                @if($fotos->count() > 0)
                                    <button onclick="openFotoModal({{ $task->id }})" class="inline-flex flex-col items-center gap-1 cursor-pointer bg-transparent border-0 outline-none">
                                        <div class="w-11 h-11 rounded overflow-hidden border-2 border-gray-200 transition shadow-xs">
                                            <img src="{{ asset('storage/' . $fotos->first()->url_foto) }}" class="w-full h-full object-cover">
                                        </div>
                                        <span class="text-[10px] text-amber-600 font-bold">{{ $fotos->count() }} Foto</span>
                                    </button>
                                @else
                                    <span class="text-gray-300 font-light">—</span>
                                @endif
                            </td>

                            <td class="p-3">
                                @if($task->aset?->status === 'ready')
                                    <span class="bg-green-600 text-white text-[10px] px-2 py-0.5 rounded font-bold uppercase">READY (ACC)</span>
                                @elseif($task->aset?->status === 'pending_approval')
                                    <span class="bg-orange-400 text-white text-[10px] px-2 py-0.5 rounded font-bold uppercase">PENDING ACC</span>
                                @elseif($task->aset?->status === 'pending' && $catatanSvp && $catatanSvp !== 'Disetujui')
                                    <span class="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded font-bold uppercase">SUBMIT REVISI</span>
                                @else
                                    <span class="bg-gray-400 text-white text-[10px] px-2 py-0.5 rounded font-bold uppercase">PROSES INPUT</span>
                                @endif
                            </td>

                            <td class="p-3 text-center" style="width:170px;">
                                <div class="flex flex-col items-center gap-1.5">

                                    {{-- TIM LAPANGAN --}}
                                    @if(Auth::user()->role === 'tim' || Auth::user()->role === 'officer' || Auth::user()->role === 'staff')
                                        @if($task->status === 'pending')
                                            <form action="/aset/confirm/{{ $task->id }}" method="POST" class="w-full m-0">
                                                @csrf
                                                <button type="submit" class="w-full bg-blue-600 text-white px-2.5 py-1.5 rounded text-[11px] font-semibold cursor-pointer border-0 shadow-2xs">
                                                    <i class="fa fa-check mr-1"></i> Confirm Tugas
                                                </button>
                                            </form>
                                        @elseif($task->status === 'process' && $task->aset?->status === 'pending')
                                            <a href="{{ route('master_aset.formInput', $task->id) }}" class="w-full text-center bg-[#1ab394] text-white px-2.5 py-1.5 rounded text-[11px] font-semibold shadow-2xs no-underline block hover:bg-[#18a689] transition">
                                                <i class="fa fa-edit mr-1"></i> Isi Atribut Spesifikasi
                                            </a>
                                        @elseif($task->aset?->status === 'pending_approval')
                                            <span class="text-gray-400 italic text-[11px] font-medium"><i class="fa fa-spinner fa-spin"></i> Review Supervisor</span>
                                        {{-- ── TOMBOL PRINTER HANYA AKAN MUNCUL PASCA STATUS READY (TELAH DI ACC) ── --}}
                                        @elseif($task->aset?->status === 'ready' && $task->aset?->rfid_code)
                                            <button type="button" onclick="printQrLabel('{{ $task->aset->rfid_code }}','{{ $task->aset->no_sap ?? '-' }}','{{ $task->aset->no_aset ?? '-' }}')"
                                                class="w-full bg-gray-700 text-white px-2.5 py-1.5 rounded text-[11px] hover:bg-gray-800 font-semibold cursor-pointer shadow-2xs border-0">
                                                <i class="fa fa-print mr-1"></i> Print QR Label
                                            </button>
                                        @else
                                            <span class="text-gray-300 italic text-[11px]">Draf Terkunci</span>
                                        @endif
                                    @endif

                                    {{-- SUPERVISOR / MANAGER --}}
                                    @if(Auth::user()->role === 'supervisor' || Auth::user()->role === 'manager')
                                        @if($task->aset?->status === 'pending_approval')
                                            <a href="{{ route('master_aset.formApproval', $task->id) }}" class="w-full text-center bg-orange-400 text-white px-2.5 py-1.5 rounded text-[11px] font-bold shadow-2xs no-underline block hover:bg-orange-500 transition">
                                                <i class="fa fa-gavel mr-1"></i> Periksa &amp; ACC
                                            </a>
                                        @elseif($task->aset?->status === 'ready' && $task->aset?->rfid_code)
                                            <button type="button" onclick="printQrLabel('{{ $task->aset->rfid_code }}','{{ $task->aset->no_sap ?? '-' }}','{{ $task->aset->no_aset ?? '-' }}')"
                                                class="w-full bg-gray-700 text-white px-2.5 py-1.5 rounded text-[11px] hover:bg-gray-800 font-semibold cursor-pointer shadow-2xs border-0">
                                                <i class="fa fa-print mr-1"></i> Print Label
                                            </button>
                                        @endif

                                        @if($task->aset?->status !== 'ready')
                                            <div class="flex gap-1 w-full">
                                                <button onclick="openEditModal({{ $task->id }}, '{{ addslashes($task->deskripsi) }}', '{{ $task->assigned_to }}')"
                                                    class="flex-1 bg-gray-100 text-gray-600 px-2 py-1.5 rounded text-[11px] hover:bg-gray-200 border border-gray-300 cursor-pointer font-medium transition">
                                                    <i class="fa fa-pencil"></i> Edit
                                                </button>
                                                <button type="button" onclick="confirmHapus({{ $task->id }})"
                                                    class="flex-1 bg-red-50 text-red-500 px-2 py-1.5 rounded text-[11px] hover:bg-red-100 border border-red-200 cursor-pointer font-medium transition">
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
                            <td colspan="7" class="p-6 text-center text-gray-400 bg-gray-50/50 font-light">Belum ada data instruksi kerja lapangan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── MODAL: Galeri Foto ── --}}
    <div id="foto-modal" class="fixed inset-0 bg-black/75 z-[70] flex items-center justify-center hidden p-4">
        <div class="bg-white rounded shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-200">
            <div class="bg-amber-500 px-4 py-3 text-white flex justify-between items-center">
                <h4 class="font-bold text-xs uppercase m-0"><i class="fa fa-camera mr-2"></i>Galeri Dokumentasi Foto</h4>
                <button onclick="closeFotoModal()" class="text-white text-xl font-light bg-transparent border-0 cursor-pointer">&times;</button>
            </div>
            <div class="p-5">
                <div id="foto-modal-info" class="mb-4 p-3 bg-amber-50 border border-amber-100 rounded text-xs text-gray-600"></div>
                <div id="foto-modal-grid" class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[350px] overflow-y-auto p-1"></div>
            </div>
        </div>
    </div>

    {{-- ── MODAL: Buat / Edit Penugasan ── --}}
    <div id="assign-modal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center hidden backdrop-blur-xs">
        <div class="bg-white rounded shadow-xl w-full max-w-md overflow-hidden border border-gray-200">
            <div class="bg-[#1ab394] px-4 py-3 text-white flex justify-between items-center">
                <h4 class="font-bold text-xs uppercase m-0" id="modal-title-supervisor">
                    <i class="fa fa-paper-plane mr-2"></i>Buat Penugasan Logistik
                </h4>
                <button onclick="closeAssignModal()" class="text-white text-sm font-bold bg-transparent border-0 cursor-pointer">&times;</button>
            </div>
            <form id="supervisor-task-form" action="/aset/store" method="POST" class="p-5 space-y-4 m-0">
                @csrf
                <div id="method-field-placeholder"></div>

                <div>
                    <label class="font-bold text-gray-700 text-xs uppercase block mb-1">Instruksi Kerja Utama</label>
                    <textarea name="deskripsi" id="task-deskripsi" placeholder="Masukkan instruksi penugasan kerja..." required
                        class="w-full min-h-[70px] border border-gray-300 rounded px-3 py-2 text-xs focus:outline-none" rows="2"></textarea>
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

                <div>
                    <label class="font-bold text-gray-700 text-xs uppercase block mb-1">Checkpoint Kerja Wajib</label>
                    <div id="wrapper-input-checkpoints"></div>
                </div>

                <div class="flex justify-end space-x-2 pt-3 border-t border-gray-100">
                    <button type="button" onclick="closeAssignModal()" class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-300 rounded cursor-pointer">Batal</button>
                    <button type="button" onclick="triggerKonfirmWA()" class="px-4 py-1.5 text-xs text-white bg-[#1ab394] rounded hover:bg-[#18a689] font-bold shadow-xs cursor-pointer border-0">
                        <i class="fa fa-paper-plane mr-1"></i> Simpan &amp; Kirim WA
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── MODAL: Konfirmasi Hapus ── --}}
    <div id="modal-hapus" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center hidden backdrop-blur-xs">
        <div class="bg-white rounded shadow-xl w-full max-w-sm overflow-hidden border border-gray-200">
            <div class="bg-red-500 px-4 py-3 text-white flex items-center gap-2">
                <i class="fa fa-exclamation-triangle"></i>
                <h4 class="font-bold text-xs uppercase m-0">Konfirmasi Hapus</h4>
            </div>
            <div class="p-5 text-xs text-gray-600 leading-relaxed">
                <p>⚠️ <strong>PERINGATAN:</strong> Menghapus tugas akan melenyapkan seluruh atribut, checkpoints, dan foto secara permanen!</p>
            </div>
            <div class="flex justify-end gap-2 px-5 pb-4">
                <button type="button" onclick="closeModalHapus()" class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-300 rounded cursor-pointer">Batal</button>
                <form id="form-hapus-target" method="POST" class="m-0 inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-1.5 text-xs text-white bg-red-500 rounded hover:bg-red-600 font-bold border-0 cursor-pointer">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── MODAL: Konfirmasi Kirim WA ── --}}
    <div id="modal-konfirm-wa" class="fixed inset-0 bg-black/60 z-[60] flex items-center justify-center hidden backdrop-blur-xs">
        <div class="bg-white rounded shadow-xl w-full max-w-sm overflow-hidden border border-gray-200">
            <div class="bg-[#1ab394] px-4 py-3 text-white flex items-center gap-2">
                <i class="fa fa-whatsapp"></i>
                <h4 class="font-bold text-xs uppercase m-0">Konfirmasi Tugas</h4>
            </div>
            <div class="p-5 text-xs text-gray-600">
                <p>Apakah Anda setuju menerbitkan tugas ini dan mengirim notifikasi WhatsApp ke Tim Lapangan?</p>
            </div>
            <div class="flex justify-end gap-2 px-5 pb-4">
                <button type="button" onclick="closeModalKonfirmWA()" class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-300 rounded cursor-pointer">Batal</button>
                <button type="button" onclick="submitSupervisorForm()" class="px-4 py-1.5 text-xs text-white bg-[#1ab394] rounded hover:bg-[#18a689] font-bold border-0 cursor-pointer">Ya, Kirim</button>
            </div>
        </div>
    </div>

    {{-- ── DATA DATA JS BINDING ── --}}
    <script>
        var fotoDataPerTask = {!! json_encode($fotoDataPerTask ?? []) !!};
        var saranCheckpointsMaster = {!! json_encode($saranCheckpoints ?? []) !!};
    </script>

    <script>
        // ══════════════════════════════════════════════════════
        // CHECKPOINT TAG SYSTEM
        // ══════════════════════════════════════════════════════
        var checkpointTags = [];

        function renderCheckpointTags() {
            var wrapper = document.getElementById('wrapper-input-checkpoints');
            if (!wrapper) return;

            wrapper.innerHTML = `
                <div class="relative mb-2">
                    <div class="flex items-center gap-1.5">
                        <div class="relative flex-1">
                            <input type="text" id="cp-search-input"
                                placeholder="Ketik lalu Enter, atau pilih dari saran..."
                                autocomplete="off"
                                onkeydown="handleCpKeydown(event)"
                                oninput="showCpSuggestions(this.value)"
                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-xs focus:outline-none focus:border-blue-400">
                            <div id="cp-suggestions"
                                class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded shadow-lg z-[100] hidden max-h-36 overflow-y-auto"></div>
                        </div>
                        <button type="button" onclick="addCpFromInput()"
                            class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 text-xs rounded border-0 cursor-pointer transition whitespace-nowrap">
                            <i class="fa fa-plus mr-1"></i>Tambah
                        </button>
                    </div>
                </div>

                <div id="cp-tags-container"
                    class="flex flex-wrap gap-1.5 min-h-[38px] p-2 bg-gray-50 border border-gray-200 rounded">
                </div>`;

            var container = document.getElementById('cp-tags-container');
            if (checkpointTags.length === 0) {
                container.innerHTML = '<span class="text-[10px] text-gray-300 italic self-center m-auto">Belum ada checkpoint — ketik dan tekan Enter atau klik Tambah</span>';
            } else {
                container.innerHTML = '';
                checkpointTags.forEach(function(tag, idx) {
                    var pill = document.createElement('span');
                    pill.className = 'inline-flex items-center gap-1.5 bg-blue-100 text-blue-800 text-[11px] px-2.5 py-1 rounded-full border border-blue-200 font-medium animate-fadeIn';
                    pill.innerHTML =
                        '<i class="fa fa-check-circle text-blue-400 text-[10px]"></i>' +
                        '<span>' + escapeHtml(tag) + '</span>' +
                        '<input type="hidden" name="checkpoints[]" value="' + escapeHtml(tag) + '">' +
                        '<button type="button" onclick="removeCpTag(' + idx + ')" ' +
                            'class="text-blue-300 hover:text-red-500 bg-transparent border-0 cursor-pointer p-0 leading-none text-base font-bold ml-0.5 transition">' +
                            '&times;' +
                        '</button>';
                    container.appendChild(pill);
                });
            }
        }

        function escapeHtml(str) {
            return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
        }

        function addCpFromInput() {
            var input = document.getElementById('cp-search-input');
            if (!input) return;
            var val = input.value.trim();
            if (!val) return;
            if (!checkpointTags.includes(val)) {
                checkpointTags.push(val);
                renderCheckpointTags();
            }
            input.value = '';
            hideCpSuggestions();
            setTimeout(function() {
                var inp = document.getElementById('cp-search-input');
                if (inp) inp.focus();
            }, 50);
        }

        function handleCpKeydown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addCpFromInput();
            }
        }

        function removeCpTag(idx) {
            checkpointTags.splice(idx, 1);
            renderCheckpointTags();
        }

        function showCpSuggestions(query) {
            var box = document.getElementById('cp-suggestions');
            if (!box || !query || query.length < 1) { hideCpSuggestions(); return; }

            var filtered = saranCheckpointsMaster.filter(function(s) {
                return s.toLowerCase().includes(query.toLowerCase()) && !checkpointTags.includes(s);
            });

            if (filtered.length === 0) { hideCpSuggestions(); return; }

            box.innerHTML = filtered.slice(0, 8).map(function(s) {
                return '<div onclick="selectCpSuggestion(\'' + escapeHtml(s).replace(/'/g, "\\'") + '\')" ' +
                    'class="px-3 py-2 text-xs text-gray-700 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 flex items-center gap-2">' +
                    '<i class="fa fa-plus text-blue-400 text-[10px]"></i>' + escapeHtml(s) +
                    '</div>';
            }).join('');
            box.classList.remove('hidden');
        }

        function selectCpSuggestion(val) {
            if (!checkpointTags.includes(val)) {
                checkpointTags.push(val);
                renderCheckpointTags();
            }
            var input = document.getElementById('cp-search-input');
            if (input) { input.value = ''; input.focus(); }
            hideCpSuggestions();
        }

        function hideCpSuggestions() {
            var box = document.getElementById('cp-suggestions');
            if (box) box.classList.add('hidden');
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#cp-search-input') && !e.target.closest('#cp-suggestions')) {
                hideCpSuggestions();
            }
        });

        // ══════════════════════════════════════════════════════
        // MODAL CONFIGURATION
        // ══════════════════════════════════════════════════════
        function openAssignModal() {
            document.getElementById('supervisor-task-form').action = "/aset/store";
            document.getElementById('method-field-placeholder').innerHTML = '';
            document.getElementById('modal-title-supervisor').innerHTML = '<i class="fa fa-paper-plane mr-2"></i>Buat Penugasan Logistik';
            document.getElementById('task-deskripsi').value = '';
            document.getElementById('task-assigned-to').selectedIndex = 0;

            checkpointTags = [];
            renderCheckpointTags();

            document.getElementById('assign-modal').classList.remove('hidden');
            setTimeout(function() {
                document.getElementById('task-deskripsi').focus();
            }, 100);
        }

        function openEditModal(taskId, deskripsi, assignedTo) {
            document.getElementById('supervisor-task-form').action = '/aset/update/' + taskId;
            document.getElementById('method-field-placeholder').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('modal-title-supervisor').innerHTML = '<i class="fa fa-pencil mr-2"></i>Edit Instruksi &amp; Checkpoint';
            document.getElementById('task-deskripsi').value = deskripsi;
            document.getElementById('task-assigned-to').value = assignedTo;

            checkpointTags = [];
            renderCheckpointTags();
            document.getElementById('assign-modal').classList.remove('hidden');

            var container = document.getElementById('cp-tags-container');
            if (container) {
                container.innerHTML = '<span class="text-[10px] text-gray-400 italic"><i class="fa fa-spinner fa-spin mr-1"></i>Memuat checkpoint...</span>';
            }

            fetch('/api/get-checkpoints/' + taskId)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    checkpointTags = data.map(function(cp) { return cp.nama_poin; });
                    renderCheckpointTags();
                })
                .catch(function() {
                    renderCheckpointTags();
                });
        }

        function closeAssignModal() {
            document.getElementById('assign-modal').classList.add('hidden');
            checkpointTags = [];
        }

        function confirmHapus(taskId) {
            document.getElementById('form-hapus-target').action = '/aset/delete/' + taskId;
            document.getElementById('modal-hapus').classList.remove('hidden');
        }
        function closeModalHapus() { document.getElementById('modal-hapus').classList.add('hidden'); }

        function triggerKonfirmWA() {
            if (!document.getElementById('task-deskripsi').value.trim()) {
                alert('Kolom Instruksi wajib diisi.');
                return;
            }
            if (checkpointTags.length === 0) {
                alert('Tambahkan minimal 1 checkpoint kerja.');
                return;
            }
            document.getElementById('modal-konfirm-wa').classList.remove('hidden');
        }
        function closeModalKonfirmWA() { document.getElementById('modal-konfirm-wa').classList.add('hidden'); }

        function submitSupervisorForm() {
            closeModalKonfirmWA();
            closeAssignModal();
            document.getElementById('supervisor-task-form').submit();
        }

        // ══════════════════════════════════════════════════════
        // GALLERY MULTI-FOTO
        // ══════════════════════════════════════════════════════
        var kondisiLabel = {
            'baru': 'Baru', 'baik': 'Baik',
            'rusak_ringan': 'Rusak Ringan', 'rusak_berat': 'Rusak Berat'
        };

        function openFotoModal(taskId) {
            var data = fotoDataPerTask[taskId];
            if (!data || !data.fotos || data.fotos.length === 0) return;

            document.getElementById('foto-modal-info').innerHTML =
                '<strong>Kondisi:</strong> ' + (kondisiLabel[data.kondisi] || data.kondisi) +
                (data.keterangan ? ' &nbsp;&bull;&nbsp; <strong>Catatan:</strong> ' + data.keterangan : '');

            var grid = document.getElementById('foto-modal-grid');
            grid.innerHTML = '';
            data.fotos.forEach(function(url) {
                var div = document.createElement('div');
                div.innerHTML = '<a href="' + url + '" target="_blank">' +
                    '<img src="' + url + '" class="w-full h-36 object-cover rounded border border-gray-200 hover:border-amber-500 transition shadow-2xs cursor-zoom-in">' +
                    '</a>';
                grid.appendChild(div);
            });
            document.getElementById('foto-modal').classList.remove('hidden');
        }
        function closeFotoModal() { document.getElementById('foto-modal').classList.add('hidden'); }

        // ── THERMAL PRINTER CORE DIVISION (UPDATED FOR TECHNICAL SUPPORT) ──
        function printQrLabel(qrCodeText, sapNumber, assetNumber) {
            var win = window.open('', '_blank', 'width=450,height=450');
            var url = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(qrCodeText);
            win.document.write(
                '<html><head><title>Print QR</title>' +
                '<style>body{font-family:"Courier New",monospace;text-align:center;padding:10px;margin:0}' +
                '.container{border:2px dashed #000;padding:10px;display:inline-block;width:280px}' +
                '.header{font-weight:bold;font-size:11px;border-bottom:1px solid #000;padding-bottom:4px;letter-spacing:0.5px}' +
                '.qr{width:130px;height:130px;margin:8px auto;display:block}' +
                '.meta{font-size:10px;text-align:left;margin-top:6px;font-weight:bold}</style></head>' +
                '<body><div class="container">' +
                '<div class="header">PROPERTY OF TECHNICAL SUPPORT</div>' +
                '<img src="' + url + '" class="qr"/>' +
                '<div class="meta">' +
                '<div>• SYSTEM ID : ' + qrCodeText + '</div>' +
                '<div>• SAP NO   : ' + sapNumber + '</div>' +
                '<div>• ASSET NO : ' + assetNumber + '</div>' +
                '</div></div>' +
                '<script>window.onload=function(){window.print();window.close();}<\/script>' +
                '</body></html>'
            );
            win.document.close();
        }

        // Overlay backdrop auto close listener
        ['modal-hapus','modal-konfirm-wa','assign-modal','foto-modal'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('click', function(e) {
                    if (e.target === this) this.classList.add('hidden');
                });
            }
        });
    </script>

@endsection