<div class="flex flex-col items-center justify-center p-4 space-y-4 text-center">
    <div class="p-2 bg-white rounded-lg shadow-sm border border-gray-200">
        {{-- Tampilkan SVG mentah langsung ke HTML --}}
        {!! $qrCodeSvg !!}
    </div>
    
    <div>
        <p class="text-sm font-semibold text-gray-900 dark:text-white">
            Pengundang: <span class="text-primary-600">{{ $tenantName }}</span>
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Gunakan tombol <span class="font-bold">Download QR</span> di tabel jika ingin menyimpan gambar (.png) ke perangkat Anda.
        </p>
    </div>
</div>