<x-layouts.app>
@php
    // Inject ImageUrlService untuk deteksi Tailscale dan serve compressed images
    $imageService = app(\App\Services\ImageUrlService::class);
    
    // Prepare images array for Alpine.js
    $galleryImages = $ptk->attachments->filter(function($att) {
        return str_starts_with(strtolower($att->mime ?? ''), 'image/');
    })->values()->map(function($att) use ($imageService) {
        return [
            'url' => $imageService->getImageUrl($att->path),
            'caption' => $att->original_name,
        ];
    });
@endphp

  <div class="space-y-6" 
       x-data="{ 
           preview: false, 
           images: {{ $galleryImages->toJson() }},
           idx: 0,
           rotate: 0, 
           scale: 1,
           
           get imgSrc() { return this.images[this.idx]?.url || ''; },
           get imgCaption() { return this.images[this.idx]?.caption || ''; },
           
           next() {
               if(this.images.length === 0) return;
               this.idx = (this.idx + 1) % this.images.length;
               this.resetTransform();
           },
           prev() {
               if(this.images.length === 0) return;
               this.idx = (this.idx - 1 + this.images.length) % this.images.length;
               this.resetTransform();
           },
           resetTransform() {
               this.rotate = 0;
               this.scale = 1;
           },
           openIndex(i) {
               this.idx = i;
               this.resetTransform();
               this.preview = true;
           }
       }"
       @keydown.escape.window="preview=false; resetTransform();"
       @keydown.right.window="if(preview) next()"
       @keydown.left.window="if(preview) prev()"
  >
    {{-- Header & aksi --}}
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">PTK {{ $ptk->number ?? '-' }}</h1>

      <div class="flex flex-wrap gap-2">
        {{-- ✅ Tombol Submit PTK --}}
        @hasanyrole('admin_qc_flange|admin_qc_fitting|admin_hr|admin_k3|admin_mtc|kabag_mtc')
          @if(in_array($ptk->status, ['Not Started', 'In Progress']))
            <form method="POST" action="{{ route('ptk.submit', $ptk) }}" class="inline">
              @csrf
              <button class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
                Submit PTK
              </button>
            </form>
          @endif
        @endhasanyrole

        {{-- Edit PTK --}}
        @can('update', $ptk)
          @if(in_array($ptk->status, ['Not Started', 'In Progress']))
            <a href="{{ route('ptk.edit', $ptk) }}"
               class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              Edit
            </a>
          @endif
        @endcan

        {{-- Preview + Download PDF --}}
        <div class="flex gap-2">
          <!-- Tombol Preview buka di tab baru -->
          <a href="{{ route('exports.pdf.preview', $ptk->id) }}"
             target="_blank"
             rel="noopener"
             class="px-3 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">
            Preview PDF
          </a>

          <!-- Tombol Download (tetap ada) -->
          <a href="{{ route('exports.pdf', $ptk->id) }}"
             class="px-3 py-2 bg-black text-white rounded-lg hover:bg-gray-900">
            Download PDF
          </a>
        </div>

        {{-- Delete PTK --}}
        @can('delete', $ptk)
          @if(in_array($ptk->status, ['Not Started', 'In Progress']))
            <form method="POST" action="{{ route('ptk.destroy', $ptk) }}"
                  onsubmit="return confirm('Yakin hapus PTK ini?')" class="inline">
              @csrf
              @method('DELETE')
              <button class="px-3 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700">
                Delete
              </button>
            </form>
          @endif
        @endcan
      </div>
    </div>

    {{-- Meta ringkas --}}
    @php
      $badge = match($ptk->status) {
        'Completed'   => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100',
        'In Progress' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-100',
        default       => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
      };
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow">
        <div class="text-xs text-gray-500">Judul</div>
        <div class="text-lg font-semibold mb-3">{{ $ptk->title }}</div>

        <div class="text-xs text-gray-500">Status</div>
        <div class="mb-3">
          <span class="px-2 py-1 rounded text-xs font-medium {{ $badge }}">
            {{ $ptk->status }}
          </span>
        </div>

        <div class="text-xs text-gray-500">Kategori / Departemen</div>
        <div class="mb-3">
          {{ $ptk->category->name ?? '-' }}
          @if($ptk->subcategory)
            <span class="text-gray-400">/</span> {{ $ptk->subcategory->name }}
          @endif
          <span class="text-gray-400"> • </span> {{ $ptk->department->name ?? '-' }}
        </div>

        <div class="text-xs text-gray-500">PIC</div>
        <div class="mb-3">{{ $ptk->pic->name ?? '-' }}</div>

        <div class="text-xs text-gray-500">Due / Approved</div>
        <div class="mb-3">
          {{ optional($ptk->due_date)->format('Y-m-d') ?? '-' }} /
          {{ optional($ptk->approved_at)->format('Y-m-d') ?? '-' }}
        </div>

        {{-- Dua tanggal --}}
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-gray-500">Tanggal Form:</span>
            <span class="font-medium">
              {{ optional($ptk->form_date)->format('d M Y') }}
            </span>
          </div>
          <div>
            <span class="text-gray-500">Tanggal Input:</span>
            <span class="font-medium">
              {{ $ptk->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}
            </span>
          </div>
        </div>
      </div>

      {{-- Deskripsi singkat --}}
      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow">
        <div class="text-xs text-gray-500">Deskripsi singkat</div>
        <div class="prose dark:prose-invert max-w-none">
          {!! nl2br(e($ptk->description ?? '—')) !!}
        </div>
      </div>
    </div>

    {{-- Section utama (SWITCH LOGIC: MTC vs STANDARD) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow space-y-6">
      
      @if($ptk->mtcDetail)
        {{-- ========================================================= --}}
        {{-- VIEW MTC (Maintenance) --}}
        {{-- ========================================================= --}}
        
        {{-- 1. Deskripsi Kerusakan --}}
        <h2 class="font-bold text-lg mb-3 text-indigo-600">1. Detail Kerusakan Mesin</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-sm text-gray-500">Kerusakan Mesin:</span>
                <div class="font-medium p-2 bg-gray-50 rounded border">
                    {!! nl2br(e($ptk->mtcDetail->machine_damage_desc ?? '-')) !!}
                </div>
            </div>
            <div>
                <span class="text-sm text-gray-500">Status Stop:</span>
                <div class="font-medium uppercase">{{ $ptk->mtcDetail->machine_stop_status ?? '-' }}</div>
            </div>
        </div>

        {{-- 2. Evaluasi Masalah --}}
        <div class="mt-4">
            <h2 class="font-semibold mb-2">2. Evaluasi Masalah</h2>
            <div class="prose dark:prose-invert max-w-none p-2 bg-gray-50 rounded border">
                {!! nl2br(e($ptk->mtcDetail->problem_evaluation ?? '-')) !!}
            </div>
        </div>

        {{-- 3. Sparepart --}}
        @if($ptk->mtcDetail->spareparts->count() > 0)
            <div class="mt-4">
                <h3 class="font-semibold mb-2">3. Order Sparepart</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="p-2 text-left">Item</th>
                                <th class="p-2 text-left">Qty</th>
                                <th class="p-2 text-left">Status</th>
                                <th class="p-2 text-left">Est Arrival</th>
                                <th class="p-2 text-left">Actual Arrival</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ptk->mtcDetail->spareparts as $sp)
                            <tr class="border-t">
                                <td class="p-2">
                                    <div class="font-medium">{{ $sp->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $sp->spec }}</div>
                                </td>
                                <td class="p-2">{{ $sp->qty }}</td>
                                <td class="p-2">
                                    @php
                                        $spColor = match($sp->status) {
                                            'Received' => 'bg-green-100 text-green-800',
                                            'Shipped'  => 'bg-blue-100 text-blue-800',
                                            'Ordered'  => 'bg-yellow-100 text-yellow-800',
                                            default    => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span class="px-2 py-1 rounded text-xs {{ $spColor }}">{{ $sp->status }}</span>
                                </td>
                                <td class="p-2">{{ $sp->est_arrival_date?->format('d M Y') ?? '-' }}</td>
                                <td class="p-2">{{ $sp->actual_arrival_date?->format('d M Y') ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- TRACKER STATUS SPAREPART (E-Commerce Style) --}}
        @if($ptk->mtcDetail->spareparts->count() > 0)
          @php
              // 1. Hitung frekuensi status
              $counts = [
                  'Requested' => 0,
                  'Ordered'   => 0,
                  'Shipped'   => 0,
                  'Received'  => 0,
              ];

              foreach($ptk->mtcDetail->spareparts as $sp) {
                  $s = $sp->status ?: 'Requested'; 
                  if(isset($counts[$s])) {
                      $counts[$s]++;
                  } else {
                      // Fallback jika ada status aneh, anggap Requested
                      $counts['Requested']++;
                  }
              }

              // 2. Tentukan status "Mayoritas" aka Terbanyak
              // Logic: Ambil nilai max, jika tie, ambil yang urutannya paling "maju" (optimistic) atau "awal"?
              // User request: "mengikuti status yang terbanyak". 
              // Contoh: 2 Ordered, 1 Requested -> Ordered.
              // Tie-breaker: Kita ambil yang paling 'Dominan' (highest count). 
              // Jika count sama (misal 1 Requested, 1 Ordered), kita ambil status yang lebih lanjut (Ordered) 
              // asumsi kalau sudah ada yang ordered berarti proses berjalan.
              
              $finalStatus = 'Requested';
              $maxVal = 0;
              
              // Priority order for tie-breaking: Received > Shipped > Ordered > Requested
              $priority = ['Received', 'Shipped', 'Ordered', 'Requested'];
              
              foreach($priority as $p) {
                  if($counts[$p] >= $maxVal && $counts[$p] > 0) {
                      $maxVal = $counts[$p];
                      $finalStatus = $p;
                  }
              }
              
              // Define steps layout
              $steps = [
                  'Requested' => ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label' => 'Requested'],
                  'Ordered'   => ['icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', 'label' => 'Ordered'],
                  'Shipped'   => ['icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Shipped'], // Truck icon replacement needed? Using generic for now, let's use Truck SVG below
                  'Received'  => ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Received'],
              ];

              // Fix Icons to be accurate Heroicons
              $icons = [
                  'Requested' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />',
                  'Ordered'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />',
                  'Shipped'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />',
                  'Received'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
              ];
          @endphp

          <div class="mt-8 mb-6">
            <div class="flex items-center justify-center w-full max-w-3xl mx-auto gap-2">
              @foreach($steps as $key => $val)
                @php
                  // Logic Active
                  $isActive = ($finalStatus === $key);
                  $colorIcon = $isActive ? 'text-green-600' : 'text-gray-400'; // Darker gray for better visibility
                  $colorText = $isActive ? 'text-green-700 font-bold' : 'text-gray-500 font-medium';
                @endphp

                {{-- Item Status --}}
                <div class="flex flex-col items-center space-y-3 relative group">
                  <div class="{{ $colorIcon }} transition-all duration-300 transform group-hover:scale-110">
                    {{-- Icon Besar (w-16 = 64px) --}}
                    <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      {!! $icons[$key] !!}
                    </svg>
                  </div>
                  <div class="text-base {{ $colorText }}">
                    {{ $val['label'] }}
                  </div>
                </div>

                {{-- Panah (kecuali item terakhir) --}}
                @if(!$loop->last)
                  <div class="flex-1 border-t-2 border-dashed border-gray-300 mx-2 h-0" style="min-width: 20px;"></div>
                  <div class="-ml-3 text-gray-300">
                     <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                     </svg>
                  </div>
                @endif

              @endforeach
            </div>
          </div>
        @endif

        {{-- 4. Koreksi & Perbaikan --}}
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="col-span-1 md:col-span-2">
                <h2 class="font-semibold mb-2">4. Koreksi & Perbaikan</h2>
            </div>
            <div>
                 <span class="text-sm text-gray-500">Tanggal Pemasangan:</span>
                 <div class="font-medium">{{ $ptk->mtcDetail->installation_date?->format('d M Y') ?? '-' }}</div>
            </div>
             <div>
                 <span class="text-sm text-gray-500">Perbaikan Oleh:</span>
                 <div class="font-medium">{{ $ptk->mtcDetail->repaired_by ?? '-' }}</div>
            </div>
            <div class="col-span-1 md:col-span-2">
                <span class="text-sm text-gray-500">Catatan Teknis:</span>
                <div class="prose dark:prose-invert max-w-none p-2 bg-gray-50 rounded border">
                    {!! nl2br(e($ptk->mtcDetail->technical_notes ?? '-')) !!}
                </div>
            </div>
        </div>

        {{-- 5. Hasil Uji Coba --}}
        <div class="mt-4 border-t pt-4">
             <h2 class="font-semibold mb-2">5. Hasil Uji Coba</h2>
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="text-sm text-gray-500">Status Setelah Perbaikan:</span>
                    <div class="font-medium uppercase">{{ $ptk->mtcDetail->machine_status_after ?? '-' }}</div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">Running Hours:</span>
                    <div class="font-medium">{{ $ptk->mtcDetail->trial_hours ?? '-' }} jam</div>
                </div>
                <div class="col-span-1 md:col-span-2">
                    <span class="text-sm text-gray-500">Hasil Pengamatan:</span>
                    <div class="prose dark:prose-invert max-w-none p-2 bg-gray-50 rounded border">
                        {!! nl2br(e($ptk->mtcDetail->trial_result ?? '-')) !!}
                    </div>
                </div>
             </div>
        </div>

      @else
        {{-- ========================================================= --}}
        {{-- VIEW STANDARD (Umum) --}}
        {{-- ========================================================= --}}
        @foreach ([
          '1. Deskripsi Ketidaksesuaian' => $ptk->desc_nc,
          '2. Evaluasi Masalah (Analisis)' => $ptk->evaluation,
          '3a. Tindakan Koreksi dan Tindakan Korektif' => $ptk->action_correction,
          '4. Hasil Uji Coba' => $ptk->action_corrective,
        ] as $title => $content)
          <div>
            <h2 class="font-semibold mb-2">{{ $title }}</h2>
            <div class="prose dark:prose-invert max-w-none p-3 bg-gray-50 rounded border">
              {!! nl2br(e($content ?? '—')) !!}
            </div>
          </div>
        @endforeach
      @endif
    </div>

    {{-- Lampiran --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
      <h2 class="font-semibold mb-3">Lampiran</h2>

      @if($ptk->attachments->count())
        <ul class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          @php $imgCounter = 0; @endphp
          @foreach($ptk->attachments as $att)
            @php
              // Gunakan imageService untuk URL yang optimal (compressed untuk Tailscale)
              $url   = $isImg = str_starts_with(strtolower($att->mime ?? ''), 'image/') 
                       ? $imageService->getImageUrl($att->path) 
                       : asset(Storage::url($att->path));
              $mime  = strtolower($att->mime ?? '');
              $isImg = str_starts_with($mime, 'image/');
              
              $clickAction = '';
              if($isImg) {
                  // Jika ini image, set index untuk dibuka
                  $clickAction = "openIndex($imgCounter)";
                  $imgCounter++; 
              }
              
              try { $exists = Storage::disk('public')->exists($att->path); } 
              catch (\Throwable $e) { $exists = true; }
            @endphp

            <li class="group">
              @if($isImg && $exists)
                {{-- Tombol untuk preview gambar dengan index --}}
                <button
                  type="button"
                  class="block w-full aspect-[4/3] overflow-hidden rounded-lg ring-1 ring-gray-200 dark:ring-gray-700"
                  x-on:click="{{ $clickAction }}"
                >
                  <img
                    src="{{ $url }}"
                    alt="{{ $att->original_name }}"
                    loading="lazy"
                    onerror="this.onerror=null;this.src='{{ asset('/storage/placeholders/image-missing.png') }}';"
                    class="w-full h-full object-contain bg-gray-50 group-hover:scale-105 transition"
                  />
                </button>

              @elseif($isImg && ! $exists)
                <div class="flex items-center justify-center w-full aspect-[4/3] rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 bg-gray-50 text-gray-500">
                  <span class="text-xs">File tdk ditemukan</span>
                </div>
              @else
                <a
                  href="{{ $url }}"
                  target="_blank"
                  rel="noopener"
                  class="flex items-center justify-center w-full aspect-[4/3] rounded-lg ring-1 ring-gray-200 dark:ring-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
                >
                  <span class="text-xs">
                    {{ strtoupper(pathinfo($att->original_name, PATHINFO_EXTENSION)) }}
                  </span>
                </a>
              @endif

              <div class="mt-1 text-xs truncate text-gray-700 dark:text-gray-200" title="{{ $att->original_name }}">
                {{ $att->original_name }}
              </div>
            </li>
          @endforeach
        </ul>
      @else
        <div class="text-sm text-gray-500">Tidak ada lampiran</div>
      @endif
    </div>

    {{-- Modal preview (Gallery Mode) --}}
    <div
      x-show="preview"
      x-transition.opacity
      x-cloak
      x-on:click.self="preview=false; resetTransform()"
      class="fixed inset-0 z-[9990]"
      style="z-index: 9990; position: fixed; inset: 0;"
      role="dialog"
      aria-modal="true"
    >
        {{-- Overlay gelap (Transparan 50%) --}}
        <div class="fixed inset-0 z-[9990]" style="background-color: rgba(0, 0, 0, 0.5); pointer-events: auto;"></div>

        {{-- Navigasi Previous (Fixed Left Center) --}}
        <button 
            type="button"
            class="fixed left-4 top-1/2 -translate-y-1/2 z-[10050] p-3 text-white hover:bg-black/20 rounded-full transition-all outline-none focus:outline-none"
            style="position: fixed; top: 50%; left: 20px; transform: translateY(-50%); z-index: 10050; color: white;"
            x-show="images.length > 1"
            x-on:click="prev()"
            title="Previous"
        >
            <svg class="w-16 h-16 drop-shadow-md" style="width: 4rem; height: 4rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.5));" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
        </button>

        {{-- Navigasi Next (Fixed Right Center) --}}
        <button 
            type="button"
            class="fixed right-4 top-1/2 -translate-y-1/2 z-[10050] p-3 text-white hover:bg-black/20 rounded-full transition-all outline-none focus:outline-none"
            style="position: fixed; top: 50%; right: 20px; transform: translateY(-50%); z-index: 10050; color: white;"
            x-show="images.length > 1"
            x-on:click="next()"
            title="Next"
        >
            <svg class="w-16 h-16 drop-shadow-md" style="width: 4rem; height: 4rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.5));" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
        </button>

        {{-- Container Gambar (Centered) --}}
        <div class="fixed inset-0 z-[10000] flex items-center justify-center pointer-events-none p-4"
             style="position: fixed; inset: 0; z-index: 10000; display: flex; align-items: center; justify-content: center; pointer-events: none;">
            <div class="relative pointer-events-auto transition-transform duration-200 ease-out"
                 :style="`transform: rotate(${rotate}deg) scale(${scale});`">
              <img
                :src="imgSrc"
                :alt="imgCaption"
                class="block object-contain select-none"
                style="max-width: 90vw; max-height: 85vh; user-select: none;"
              />
            </div>
        </div>

        {{-- Caption (Fixed Bottom Left) --}}
         <div class="fixed bottom-8 left-8 z-[10010] max-w-xl pointer-events-auto" 
              style="position: fixed; bottom: 30px; left: 30px; z-index: 10010;"
              x-show="images.length > 0">
             <div class="bg-black/50 backdrop-blur-md text-white px-5 py-3 rounded-xl border border-white/10 shadow-lg flex items-center gap-3"
                  style="background-color: rgba(0,0,0,0.6); padding: 12px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px; color: white;">
                  <span class="font-bold text-yellow-400 text-lg" style="color: #facc15; font-weight: bold;" x-text="`${idx + 1} / ${images.length}`"></span>
                  <div class="h-4 w-px bg-white/30" style="width: 1px; height: 16px; background-color: rgba(255,255,255,0.3);"></div>
                  <span class="text-sm font-medium tracking-wide" x-text="imgCaption"></span>
             </div>
         </div>

        {{-- PANEL KONTROL (Fixed Bottom Right - Unified) - INLINE STYLES FOR RELIABILITY --}}
        <div class="fixed bottom-8 right-8 z-[10050] pointer-events-auto" 
             style="position: fixed; bottom: 30px; right: 30px; z-index: 10050; pointer-events: auto;">
            <div class="bg-white text-gray-900 p-4 rounded-xl shadow-2xl border border-gray-200 w-64 flex flex-col gap-3"
                 style="background-color: #ffffff; color: #111827; padding: 16px; border-radius: 12px; width: 256px; display: flex; flex-direction: column; gap: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
               
               {{-- Row 1: Rotate --}}
               <div class="flex gap-2" style="display: flex; gap: 8px;">
                   <button class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg text-xs font-semibold uppercase tracking-wider transition-colors"
                           style="flex: 1; padding: 8px; background-color: #f3f4f6; border-radius: 8px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; cursor: pointer;"
                           x-on:click="rotate = (rotate - 90) % 360">
                       ⟲ Left
                   </button>
                   <button class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg text-xs font-semibold uppercase tracking-wider transition-colors"
                           style="flex: 1; padding: 8px; background-color: #f3f4f6; border-radius: 8px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; cursor: pointer;"
                           x-on:click="rotate = (rotate + 90) % 360">
                       Right ⟳
                   </button>
               </div>
               
               {{-- Row 2: Reset --}}
               <button class="w-full py-2 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-lg text-xs font-semibold uppercase tracking-wider transition-colors"
                        style="width: 100%; padding: 8px; background-color: #f3f4f6; border-radius: 8px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; cursor: pointer;"
                        x-on:click="resetTransform()">
                    Reset View
               </button>

               {{-- Row 3: Zoom --}}
                <div class="pt-2 border-t border-gray-100" style="padding-top: 8px; border-top: 1px solid #f3f4f6;">
                    <div class="flex justify-between items-center text-xs text-gray-500 mb-2" style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #6b7280; margin-bottom: 8px;">
                        <span class="font-medium">Zoom Level</span>
                        <span class="font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded" style="color: #2563eb; background-color: #eff6ff; padding: 2px 8px; border-radius: 4px; font-weight: bold;" x-text="Math.round(scale*100) + '%'"></span>
                    </div>
                    <input type="range" min="0.5" max="5" step="0.1" x-model.number="scale" 
                           class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
                           style="width: 100%; height: 8px; background-color: #e5e7eb; border-radius: 8px; cursor: pointer;">
                </div>
                
                {{-- Row 4: Close (Integrated) --}}
                <button class="w-full mt-2 py-2.5 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white rounded-lg text-sm font-bold shadow transition-colors flex items-center justify-center gap-2"
                        style="width: 100%; margin-top: 8px; padding: 10px; background-color: #dc2626; color: white; border-radius: 8px; font-size: 0.875rem; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;"
                        x-on:click="preview=false; resetTransform()">
                    <svg class="w-4 h-4" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                    CLOSE
                </button>
            </div>
        </div>
    </div>
  </div>
</x-layouts.app>
```
