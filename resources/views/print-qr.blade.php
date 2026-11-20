<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code Tamu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page { margin: 1cm; }
            body { -webkit-print-color-adjust: exact; }
            .print-bg { background-color: white !important; }
        }
        .qr-card { page-break-inside: avoid; transition: all 0.2s; }
        .qr-wrapper svg { width: 100%; height: 100%; transition: all 0.2s; }
        
        #qr-grid {
            --qr-font-family: ui-sans-serif, system-ui, sans-serif;
            --qr-base-font: 14px;
        }
        .qr-text-content {
            font-family: var(--qr-font-family);
        }
        
        .txt-event { font-size: calc(var(--qr-base-font) * 0.65); }
        .txt-guest { font-size: var(--qr-base-font); }
        .txt-tenant { font-size: calc(var(--qr-base-font) * 0.85); }
    </style>
</head>
<body class="bg-gray-200 p-6 print:p-0 print:bg-white">

    <!-- Panel Pengaturan Cetak -->
    <div class="max-w-6xl mx-auto bg-white p-6 shadow-md rounded-lg mb-6 print:hidden">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h2 class="text-2xl font-bold text-gray-800">Pengaturan Cetak QR</h2>
            <div class="space-x-2">
                <button onclick="window.history.back()" class="bg-gray-500 text-white font-bold px-4 py-2 rounded hover:bg-gray-600">Kembali</button>
                <button onclick="window.print()" class="bg-blue-600 text-white font-bold px-4 py-2 rounded hover:bg-blue-700">🖨️ Cetak Sekarang</button>
            </div>
        </div>

        <!-- Grid Input Pengaturan -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Kolom Kertas</label>
                <select id="col-setting" class="w-full border border-gray-300 rounded-md p-2 outline-none" onchange="updateLayout()">
                    <option value="2">2 Kolom</option>
                    <option value="3">3 Kolom</option>
                    <option value="4" selected>4 Kolom</option>
                    <option value="5">5 Kolom</option>
                    <option value="6">6 Kolom</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Ukuran QR (<span id="qr-size-val">120</span>px)</label>
                <input type="range" id="qr-size" min="60" max="250" value="120" class="w-full" oninput="updateLayout()">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Tebal Border (<span id="border-size-val">2</span>px)</label>
                <input type="range" id="border-size" min="0" max="8" value="2" class="w-full" oninput="updateLayout()">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Jenis Font</label>
                <select id="font-family" class="w-full border border-gray-300 rounded-md p-2 outline-none" onchange="updateLayout()">
                    <option value="ui-sans-serif, system-ui, sans-serif">Sans-serif (Modern)</option>
                    <option value="ui-serif, Georgia, Cambria, 'Times New Roman', Times, serif">Serif (Formal)</option>
                    <option value="ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Courier New', monospace">Monospace (Kaku)</option>
                    <option value="Arial, Helvetica, sans-serif">Arial</option>
                    <option value="Verdana, Geneva, Tahoma, sans-serif">Verdana</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Ukuran Teks (<span id="font-size-val">14</span>px)</label>
                <input type="range" id="font-size" min="10" max="24" value="14" class="w-full" oninput="updateLayout()">
            </div>

            <!-- Visibilitas Teks -->
            <div class="col-span-1 md:col-span-5 flex flex-wrap gap-6 mt-2 pt-4 border-t border-gray-200">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="show-event" checked class="form-checkbox h-4 w-4 text-blue-600" onchange="updateLayout()">
                    <span class="text-sm font-bold text-gray-700">Tampilkan Nama Event</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="show-guest" checked class="form-checkbox h-4 w-4 text-blue-600" onchange="updateLayout()">
                    <span class="text-sm font-bold text-gray-700">Tampilkan Nama Tamu</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="show-tenant" checked class="form-checkbox h-4 w-4 text-blue-600" onchange="updateLayout()">
                    <span class="text-sm font-bold text-gray-700">Tampilkan Nama Tenant</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Area Kertas Cetak -->
    <div id="qr-grid" class="max-w-6xl mx-auto grid gap-4 print:gap-2 qr-text-content" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
        @foreach($guests as $guest)
            <div class="qr-card print-bg bg-white border-gray-800 p-4 rounded-xl flex flex-col items-center justify-center text-center shadow-sm print:shadow-none print:border-gray-500" style="border-width: 2px;">
                
                <!-- Nama Event -->
                <p class="txt-event font-bold text-gray-500 mb-2 uppercase tracking-wider w-full break-words leading-tight">{{ $guest->event->name }}</p>
                
                <!-- QR Code -->
                <div class="qr-wrapper mb-2" style="width: 120px; height: 120px;">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->margin(1)->size(200)->generate($guest->qr_code) !!}
                </div>
                
                <!-- Info Tamu -->
                <p class="txt-guest font-extrabold text-gray-900 leading-tight w-full break-words">{{ $guest->name }}</p>
                <p class="txt-tenant text-gray-600 font-semibold mt-1 w-full break-words">{{ $guest->tenant->name }}</p>
                
            </div>
        @endforeach
    </div>

    <script>
        function updateLayout() {
            // Ambil Nilai dari Input
            let cols = document.getElementById('col-setting').value;
            let qrSize = document.getElementById('qr-size').value;
            let borderSize = document.getElementById('border-size').value;
            let fontFamily = document.getElementById('font-family').value;
            let fontSize = document.getElementById('font-size').value;

            // Ambil Nilai Checkbox
            let showEvent = document.getElementById('show-event').checked;
            let showGuest = document.getElementById('show-guest').checked;
            let showTenant = document.getElementById('show-tenant').checked;

            // Update Label Angka di Panel
            document.getElementById('qr-size-val').innerText = qrSize;
            document.getElementById('border-size-val').innerText = borderSize;
            document.getElementById('font-size-val').innerText = fontSize;

            // Update CSS Grid & Tipografi
            let grid = document.getElementById('qr-grid');
            grid.style.gridTemplateColumns = `repeat(${cols}, minmax(0, 1fr))`;
            grid.style.setProperty('--qr-font-family', fontFamily);
            grid.style.setProperty('--qr-base-font', fontSize + 'px');

            // Update Ukuran QR
            document.querySelectorAll('.qr-wrapper').forEach(wrapper => {
                wrapper.style.width = qrSize + 'px';
                wrapper.style.height = qrSize + 'px';
            });

            // Update Ketebalan Border Kartu
            document.querySelectorAll('.qr-card').forEach(card => {
                card.style.borderWidth = borderSize + 'px';
            });

            // Update Visibilitas Teks
            document.querySelectorAll('.txt-event').forEach(el => {
                el.style.display = showEvent ? 'block' : 'none';
            });
            document.querySelectorAll('.txt-guest').forEach(el => {
                el.style.display = showGuest ? 'block' : 'none';
            });
            document.querySelectorAll('.txt-tenant').forEach(el => {
                el.style.display = showTenant ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>