@extends('layouts.app')

@section('title', 'Main Dashboard')

@section('content')
    <!-- Breadcrumb / Page Heading -->
    <div class="bg-white border-b border-[#e7eaec] px-6 py-5">
        <h2 class="text-xl font-light text-gray-700 mb-1.5">Main Workspace Dashboard</h2>
        <ol class="flex space-x-2 text-xs text-gray-400">
            <li><a href="#" class="hover:underline">Home</a></li>
            <li><span>/</span></li>
            <li class="active text-inspiniaMuted font-semibold"><a>Dashboard Workspace</a></li>
        </ol>
    </div>

    <!-- Main Content Area -->
    <div class="p-6 flex-1">
        <div class="bg-white border border-[#e7eaec] rounded shadow-xs p-6 mb-6">
            <div class="border-b border-gray-100 pb-3 mb-4 flex justify-between items-center">
                <h5 class="font-bold text-sm text-inspiniaMuted uppercase tracking-wider">Initialization Report</h5>
            </div>

            <div class="space-y-4">
                <p class="leading-relaxed text-gray-600">
                    Sistem Otentikasi Terpusat untuk aplikasi **Asset Management** berbasis template **INSPINIA** berhasil diintegrasikan secara penuh menggunakan pangkalan data lokal Anda.
                </p>
                
                <div class="bg-gray-50 border border-gray-200 p-4 rounded text-xs font-mono text-gray-600 space-y-1 shadow-inner">
                    <div>[INFO] Active User Connected: {{ Auth::user()->nama }}</div>
                    <div>[INFO] Authorization Privilege: {{ strtoupper(Auth::user()->role) }}</div>
                    <div>[INFO] Device Verification: Session driver configured to 'file' successfully.</div>
                </div>
            </div>
        </div>
    </div>
@endsection