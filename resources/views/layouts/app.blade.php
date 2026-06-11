<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INSPINIA | @yield('title', 'Dashboard')</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome & Toastr CSS CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

    <!-- jQuery & Toastr JS CDN -->
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        inspiniaBg: '#f3f3f4',
                        inspiniaPrimary: '#1ab394',
                        inspiniaPrimaryHover: '#18a689',
                        inspiniaMuted: '#676a6c',
                        inspiniaSidebar: '#2f4050',
                        inspiniaDarkBg: '#293846'
                    }
                }
            }
        }
    </script>
    
    <style>
        body { font-family: "open sans", "Helvetica Neue", Helvetica, Arial, sans-serif; }
        #toast-container > .toast-success { background-color: #1ab394 !important; }
        #toast-container > .toast-warning { background-color: #f8ac59 !important; }
    </style>
</head>

<body class="bg-inspiniaBg text-sm text-inspiniaMuted flex min-h-screen relative">

    <!-- ==================== SIDEBAR NAVIGATION (image_f1b7da.png) ==================== -->
    <nav class="w-[220px] bg-inspiniaSidebar min-h-screen flex-shrink-0 flex flex-col justify-between">
        <div class="w-full">
            <!-- Profile Element Box -->
            <div class="p-6 bg-inspiniaDarkBg block">
                <div class="mb-2">
                    <div class="w-12 h-12 rounded-full bg-gray-400 flex items-center justify-center text-white text-lg font-bold shadow-inner">
                        {{ strtoupper(substr(Auth::user()->nama, 0, 2)) }}
                    </div>
                </div>
                <div class="block text-xs">
                    <strong class="font-bold text-gray-200 block mb-0.5">{{ Auth::user()->nama }}</strong>
                    <div class="text-gray-400 block uppercase font-semibold tracking-wider text-[10px] cursor-pointer">
                        {{ Auth::user()->role }} <i class="fa fa-caret-down ml-1 text-gray-500"></i>
                    </div>
                </div>
            </div>
<!-- Side Menu List -->
<ul class="text-gray-300 font-medium">
    <!-- Dashboard -->
    <li class="{{ Request::is('dashboard') ? 'bg-inspiniaDarkBg border-l-4 border-inspiniaPrimary text-white' : 'hover:bg-[#293846] hover:text-white transition duration-150' }}">
        <a href="{{ url('/dashboard') }}" class="px-5 py-3 flex items-center space-x-3 block">
            <i class="fa fa-th-large w-4 text-center"></i>
            <span>Dashboard</span>
        </a>
    </li>
    
    <!-- Master Aset -->
    <li class="{{ Request::is('master-aset*') ? 'bg-inspiniaDarkBg border-l-4 border-l-4 border-inspiniaPrimary text-white' : 'hover:bg-[#293846] hover:text-white transition duration-150' }}">
        <a href="{{ url('/master-aset') }}" class="px-5 py-3 flex items-center space-x-3 block">
            <i class="fa fa-database w-4 text-center"></i>
            <span>Master Aset</span>
        </a>
    </li>

    <!-- Menu Lainnya (Placeholder) -->
    <li class="hover:bg-[#293846] hover:text-white transition duration-150">
        <a href="#" class="px-5 py-3 flex items-center space-x-3 block">
            <i class="fa fa-exchange w-4 text-center"></i>
            <span>Perpindahan</span>
        </a>
    </li>
    <li class="hover:bg-[#293846] hover:text-white transition duration-150">
        <a href="#" class="px-5 py-3 flex items-center space-x-3 block">
            <i class="fa fa-check-square-o w-4 text-center"></i>
            <span>Task Assignment</span>
        </a>
    </li>
    <li class="hover:bg-[#293846] hover:text-white transition duration-150">
        <a href="#" class="px-5 py-3 flex items-center space-x-3 block">
            <i class="fa fa-history w-4 text-center"></i>
            <span>History Log</span>
        </a>
    </li>
</ul>
        <!-- Sidebar Footer -->
        <div class="p-4 bg-inspiniaDarkBg text-[11px] text-gray-500 text-center border-t border-gray-700/30">
            TS Asset System v1.0
        </div>
    </nav>

    <!-- ==================== MAIN PAGE WRAPPER ==================== -->
    <div id="page-wrapper" class="flex-1 flex flex-col bg-inspiniaBg">
        
        <!-- Top Navbar -->
        <div class="bg-white border-b border-[#e7eaec] px-6 py-3 flex justify-between items-center shadow-xs">
            <div class="flex items-center space-x-4">
                <button class="bg-inspiniaPrimary text-white px-3 py-1.5 rounded hover:bg-inspiniaPrimaryHover transition shadow-sm text-xs">
                    <i class="fa fa-bars"></i>
                </button>
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider hidden sm:block">
                    Database Profile: <span class="text-inspiniaMuted">ts_managementapp</span>
                </div>
            </div>

            <ul class="flex items-center space-x-6 text-xs font-medium">
                <li><span class="text-gray-400">Welcome to TS Asset Management Theme.</span></li>
                <li>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="button" onclick="openLogoutModal()" class="text-inspiniaMuted hover:text-red-500 flex items-center space-x-1 transition duration-150 font-semibold cursor-pointer">
                            <i class="fa fa-sign-out"></i>
                            <span>Log out</span>
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        <!-- Bagian Konten yang Berganti-ganti Di Sini -->
        <main class="flex-1 flex flex-col">
            @yield('content')
        </main>

        <!-- Footer Row -->
        <div class="bg-white border-t border-[#e7eaec] px-6 py-3 flex justify-between text-xs text-gray-500">
            <div><strong>Copyright</strong> TS Framework &copy; 2026</div>
            <div class="text-right">Platform PHP: <strong>8.2.12</strong></div>
        </div>
    </div>

    <!-- ==================== LOGOUT MODAL KUSTOM ==================== -->
    <div id="logout-modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded border border-gray-200 shadow-xl w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300">
            <div class="bg-[#f8ac59] px-4 py-3 flex items-center space-x-2 text-white">
                <i class="fa fa-exclamation-triangle text-lg"></i>
                <h3 class="font-bold text-sm uppercase tracking-wider">Sesi Konfirmasi</h3>
            </div>
            <div class="p-5 text-center">
                <div class="w-12 h-12 rounded-full bg-orange-50 text-[#f8ac59] flex items-center justify-center mx-auto mb-3 text-xl border border-orange-200">
                    <i class="fa fa-sign-out"></i>
                </div>
                <h4 class="text-base font-semibold text-gray-700 mb-1">Apakah Anda Yakin?</h4>
                <p class="text-xs text-gray-500 leading-relaxed">Sesi aktif Anda pada platform Manajemen Aset ini akan segera ditutup.</p>
            </div>
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-100 flex justify-end space-x-2">
                <button type="button" onclick="closeLogoutModal()" class="px-3 py-1.5 bg-white border border-gray-300 rounded text-xs font-semibold text-gray-600 hover:bg-gray-100">Batal</button>
                <button type="button" onclick="executeLogout()" class="px-3 py-1.5 bg-red-500 text-white rounded text-xs font-semibold hover:bg-red-600">Ya, Keluar</button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('logout-modal');
        const modalContent = modal.querySelector('.transform');

        function openLogoutModal() {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 20);
        }

        function closeLogoutModal() {
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function executeLogout() {
            closeLogoutModal();
            toastr.warning("Memutus sesi koneksi Anda...", "Sesi Berakhir");
            setTimeout(() => document.getElementById('logout-form').submit(), 600);
        }

        $(document).ready(function() {
            toastr.options = { "closeButton": true, "progressBar": true, "positionClass": "toast-top-right", "timeOut": "4000" };
            @if(session('toast_success')) toastr.success("{{ session('toast_success') }}"); @endif
            @if(session('toast_warning')) toastr.warning("{{ session('toast_warning') }}"); @endif
        });
    </script>
</body>
</html>