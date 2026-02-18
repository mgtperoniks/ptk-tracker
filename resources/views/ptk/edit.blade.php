{{-- resources/views/ptk/edit.blade.php --}}
<x-layouts.app>
  <h2 class="text-xl font-semibold mb-4">
    Edit PTK {{ $ptk->number ?? '—' }}
  </h2>

  <form method="POST" enctype="multipart/form-data" action="{{ route('ptk.update', $ptk) }}"
    class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @csrf
    @method('PUT')

    {{-- NOMOR PTK (required) --}}
    <div class="md:col-span-2 mb-1">
      <label for="number" class="block text-sm font-medium">
        Nomor PTK <span class="text-red-500">*</span>
      </label>
      <input id="number" type="text" name="number" value="{{ old('number', $ptk->number) }}"
        class="w-full border rounded px-3 py-2" required placeholder="contoh: PTK/QC/2025/10/001">
      <p class="text-xs text-gray-500 mt-1">
        Nomor wajib unik. Jika perlu koreksi format, ubah di sini.
      </p>
      @error('number')
        <div class="text-red-600 text-sm">{{ $message }}</div>
      @enderror
    </div>

    {{-- Judul --}}
    <div>
      <label for="title" class="block text-sm font-medium mb-1">
        Judul
      </label>
      <input id="title" type="text" name="title" class="border p-2 rounded w-full" required maxlength="200"
        value="{{ old('title', $ptk->title) }}">
      @error('title')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Status --}}
    <div>
      <label for="status" class="block text-sm font-medium mb-1">
        Status
      </label>
      <select id="status" name="status" class="border p-2 rounded w-full">
        @foreach(['Not Started', 'In Progress', 'Completed'] as $s)
          <option value="{{ $s }}" @selected(old('status', $ptk->status) === $s)>
            {{ $s }}
          </option>
        @endforeach
      </select>
      @error('status')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Kategori --}}
    <div>
      <label for="cat" class="block text-sm font-medium mb-1">
        Kategori
      </label>
      <select name="category_id" id="cat" class="border p-2 rounded w-full" required>
        @foreach($categories as $c)
          <option value="{{ $c->id }}" @selected(old('category_id', $ptk->category_id) == $c->id)>
            {{ $c->name }}
          </option>
        @endforeach
      </select>
      @error('category_id')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Subkategori (dynamic) --}}
    <div>
      <label for="subcat" class="block text-sm font-medium mb-1">
        Subkategori
      </label>
      <select name="subcategory_id" id="subcat" class="border p-2 rounded w-full">
        <option value="">-- pilih subkategori --</option>
      </select>
      @error('subcategory_id')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Departemen --}}
    <div>
      <label for="department_id" class="block text-sm font-medium mb-1">
        Departemen
      </label>
      <select id="department_id" name="department_id" class="border p-2 rounded w-full" required>
        @foreach($departments as $id => $name)
          <option value="{{ $id }}" @selected(old('department_id', $ptk->department_id) == $id)>
            {{ $name }}
          </option>
        @endforeach
      </select>
      @error('department_id')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- PIC --}}
    <div>
      <label for="pic_user_id" class="block text-sm font-medium mb-1">
        PIC
      </label>
      <select id="pic_user_id" name="pic_user_id" class="border p-2 rounded w-full" required>
        @foreach($picCandidates as $u)
          <option value="{{ $u->id }}" @selected(old('pic_user_id', $ptk->pic_user_id) == $u->id)>
            {{ $u->name }}
          </option>
        @endforeach
      </select>
      @error('pic_user_id')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Due Date --}}
    <div>
      <label for="due_date" class="block text-sm font-medium mb-1">
        Due date
      </label>
      <input id="due_date" type="date" name="due_date" class="border p-2 rounded w-full" required
        value="{{ old('due_date', optional($ptk->due_date)->format('Y-m-d')) }}">
      @error('due_date')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Tanggal Form --}}
    <div class="md:col-span-2">
      <label for="form_date" class="block text-sm font-medium mb-1">
        Tanggal Form (Tanggal PTK Asli)
      </label>
      <input id="form_date" type="date" name="form_date"
        value="{{ old('form_date', optional($ptk->form_date)->format('Y-m-d')) }}" class="border p-2 rounded w-full"
        required>
      @error('form_date')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Deskripsi --}}
    @unlessrole('admin_mtc|kabag_mtc')
    <div class="md:col-span-2">
      <label for="description" class="block text-sm font-medium mb-1">
        Deskripsi
      </label>
      <textarea id="description" name="description" rows="6"
        class="border p-2 rounded w-full">{{ old('description', $ptk->description) }}</textarea>
      @error('description')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- 1. Deskripsi Ketidaksesuaian --}}
    <div class="md:col-span-2">
      <label class="block text-sm font-medium mb-1">
        Deskripsi Ketidaksesuaian
      </label>
      <textarea name="desc_nc" rows="5"
        class="border p-2 rounded w-full">{{ old('desc_nc', $ptk->desc_nc ?? '') }}</textarea>
      @error('desc_nc')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- 2. Evaluasi Masalah --}}
    <div class="md:col-span-2">
      <label class="block text-sm font-medium mb-1">
        Evaluasi Masalah
      </label>
      <textarea name="evaluation" rows="5"
        class="border p-2 rounded w-full">{{ old('evaluation', $ptk->evaluation ?? '') }}</textarea>
      @error('evaluation')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- 3a. Koreksi & Tindakan Korektif --}}
    <div class="md:col-span-2">
      <label class="block text-sm font-medium mb-1">
        3a. Koreksi (perbaikan masalah) dan Tindakan Korektif (akar masalah)
      </label>
      <textarea name="action_correction" rows="5"
        class="border p-2 rounded w-full">{{ old('action_correction', $ptk->action_correction ?? '') }}</textarea>
      @error('action_correction')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- 4. Hasil Uji Coba --}}
    <div class="md:col-span-2">
      <label class="block text-sm font-medium mb-1">
        4. Hasil Uji Coba
      </label>
      <textarea name="action_corrective" rows="5"
        class="border p-2 rounded w-full">{{ old('action_corrective', $ptk->action_corrective ?? '') }}</textarea>
      @error('action_corrective')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>
    @endunlessrole

    {{-- ========================================================= --}}
    {{-- MODUL KHUSUS MTC (Machine) --}}
    {{-- ========================================================= --}}
    @hasanyrole('admin_mtc|kabag_mtc')
    @php
      $dbSpareparts = $ptk->mtcDetail?->spareparts->values()->toArray() ?? [];
      $spData = old('spareparts', $dbSpareparts);

      // Deduplicate in PHP!
      $uniqueSp = [];
      $seenIds = [];
      foreach ($spData as $sp) {
        if (isset($sp['id']) && $sp['id']) {
          if (!in_array($sp['id'], $seenIds)) {
            $seenIds[] = $sp['id'];
            $uniqueSp[] = $sp;
          }
        } else {
          $uniqueSp[] = $sp;
        }
      }

      // Add _key and format dates
      foreach ($uniqueSp as &$row) {
        $row['_key'] = bin2hex(random_bytes(8));
        if (!empty($row['order_date']))
          $row['order_date'] = substr($row['order_date'], 0, 10);
        if (!empty($row['est_arrival_date']))
          $row['est_arrival_date'] = substr($row['est_arrival_date'], 0, 10);
        if (!empty($row['actual_arrival_date']))
          $row['actual_arrival_date'] = substr($row['actual_arrival_date'], 0, 10);
      }
      unset($row);

      // Ensure at least one row
      if (empty($uniqueSp)) {
        $uniqueSp[] = ['name' => '', 'spec' => '', 'qty' => 1, 'supplier' => '', 'status' => 'Requested', '_key' => bin2hex(random_bytes(8))];
      }
    @endphp
    <div class="md:col-span-2 border-t border-b my-4 bg-gray-50 dark:bg-gray-800 p-4 rounded" x-data="{ 
            needsSparepart: {{ $errors->any() ? (old('mtc.needs_sparepart') ? 'true' : 'false') : ($ptk->mtcDetail?->needs_sparepart ? 'true' : 'false') }},
            spList: {{ json_encode(array_values($uniqueSp)) }}
         }">

      <h3 class="font-semibold text-base mb-3 text-indigo-600">B. Spesifik Machine (Maintenance)</h3>

      {{-- 1. Deskripsi Kerusakan --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <label class="block">
          <span class="block text-sm font-medium mb-1">Deskripsi Kerusakan Mesin</span>
          <textarea style="background-color: white;" name="mtc[machine_damage_desc]" rows="5"
            class="w-full border p-2 rounded"
            placeholder="Apa yang rusak? Dampak ke produksi?">{{ old('mtc.machine_damage_desc', $ptk->mtcDetail->machine_damage_desc ?? '') }}</textarea>
        </label>

        <div class="space-y-4">
          <label class="block">
            <span class="block text-sm font-medium mb-1">Status Mesin</span>
            <select style="background-color: white;" name="mtc[machine_stop_status]" class="w-full border p-2 rounded">
              <option value="">-- Pilih --</option>
              <option value="total" @selected(old('mtc.machine_stop_status', $ptk->mtcDetail->machine_stop_status ?? '') == 'total')>Berhenti Total (Breakdown)</option>
              <option value="partial" @selected(old('mtc.machine_stop_status', $ptk->mtcDetail->machine_stop_status ?? '') == 'partial')>Berhenti Parsial (Masih bisa jalan)</option>
            </select>
          </label>
          <label class="block">
            <span class="block text-sm font-medium mb-1">Evaluasi Masalah</span>
            <textarea style="background-color: white;" name="mtc[problem_evaluation]" rows="2"
              class="w-full border p-2 rounded">{{ old('mtc.problem_evaluation', $ptk->mtcDetail->problem_evaluation ?? '') }}</textarea>
          </label>
        </div>
      </div>

      {{-- C. Gate Sparepart --}}
      <div class="mb-4">
        <label class="inline-flex items-center space-x-2 font-medium text-sm text-gray-800 dark:text-gray-100">
          <input type="hidden" name="mtc[needs_sparepart]" value="0">
          <input type="checkbox" name="mtc[needs_sparepart]" value="1" x-model="needsSparepart"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
          <span>Apakah membutuhkan order sparepart?</span>
        </label>
      </div>

      {{-- D. Modul Sparepart --}}
      <div x-show="needsSparepart" x-transition class="mb-4 border p-3 rounded bg-white dark:bg-gray-900">
        <h4 class="font-semibold text-sm mb-2">D. Data Order Sparepart</h4>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left bg-gray-100 dark:bg-gray-700">
                <th class="p-2">Nama Sparepart</th>
                <th class="p-2">Spec</th>
                <th class="p-2 w-16">Qty</th>
                <th class="p-2">Supplier</th>
                <th class="p-2">Tgl Order</th>
                <th class="p-2">Status</th>
                <th class="p-2">Est. Datang</th>
                <th class="p-2">Tgl Datang</th>
                <th class="p-2">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <template x-for="(row, index) in spList" :key="row._key">
                <tr>
                  <td class="p-1">
                    <input type="hidden" :name="'spareparts['+index+'][id]'" x-model="row.id">
                    <input type="text" :name="'spareparts['+index+'][name]'" x-model="row.name"
                      class="w-full border p-1 rounded min-w-[120px]" placeholder="Nama..." required>
                  </td>
                  <td class="p-1">
                    <input type="text" :name="'spareparts['+index+'][spec]'" x-model="row.spec"
                      class="w-full border p-1 rounded min-w-[100px]">
                  </td>
                  <td class="p-1">
                    <input type="number" :name="'spareparts['+index+'][qty]'" x-model="row.qty"
                      class="w-full border p-1 rounded text-center w-16">
                  </td>
                  <td class="p-1">
                    <input type="text" :name="'spareparts['+index+'][supplier]'" x-model="row.supplier"
                      class="w-full border p-1 rounded min-w-[100px]">
                  </td>
                  <td class="p-1">
                    <input type="date" :name="'spareparts['+index+'][order_date]'" x-model="row.order_date"
                      class="w-full border p-1 rounded">
                  </td>
                  <td class="p-1">
                    <select :name="'spareparts['+index+'][status]'" x-model="row.status"
                      class="w-full border p-1 rounded">
                      <option value="Requested">Requested</option>
                      <option value="Ordered">Ordered</option>
                      <option value="Shipped">Shipped</option>
                      <option value="Received">Received</option>
                    </select>
                  </td>
                  <td class="p-1">
                    <input type="date" :name="'spareparts['+index+'][est_arrival_date]'" x-model="row.est_arrival_date"
                      class="w-full border p-1 rounded">
                  </td>
                  <td class="p-1">
                    <input type="date" :name="'spareparts['+index+'][actual_arrival_date]'"
                      x-model="row.actual_arrival_date" class="w-full border p-1 rounded">
                  </td>
                  <td class="p-1 text-center">
                    <button type="button" @click="spList.splice(index, 1)"
                      class="text-red-600 hover:text-red-800">x</button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
        <button type="button"
          @click="spList.push({name:'', spec:'', qty:1, supplier:'', status:'Requested', order_date:'', est_arrival_date:'', actual_arrival_date:'', _key: (Date.now() + Math.random())})"
          class="mt-2 text-sm text-blue-600 hover:underline">+ Tambah Baris</button>
      </div>

      {{-- E. Koreksi & Perbaikan (Sparepart datang) --}}
      <div x-show="spList.length > 0 && needsSparepart" x-transition
        class="mb-4 bg-white dark:bg-gray-900 p-3 rounded border">
        <h4 class="font-semibold text-sm mb-2">E. Koreksi & Perbaikan (Setelah Sparepart Datang)</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="block">
            <span class="block text-sm font-medium mb-1">Tanggal Pemasangan</span>
            <input type="date" name="mtc[installation_date]"
              value="{{ old('mtc.installation_date', optional($ptk->mtcDetail->installation_date)->format('Y-m-d')) }}"
              class="w-full border p-2 rounded">
          </label>
          <label class="block">
            <span class="block text-sm font-medium mb-1">Perbaikan Oleh</span>
            <input type="text" name="mtc[repaired_by]"
              value="{{ old('mtc.repaired_by', $ptk->mtcDetail->repaired_by ?? '') }}" class="w-full border p-2 rounded"
              placeholder="Nama teknisi / vendor">
          </label>
          <label class="block md:col-span-2">
            <span class="block text-sm font-medium mb-1">Catatan Teknis</span>
            <textarea name="mtc[technical_notes]" rows="3"
              class="w-full border p-2 rounded">{{ old('mtc.technical_notes', $ptk->mtcDetail->technical_notes ?? '') }}</textarea>
          </label>
        </div>
      </div>

      {{-- F. Hasil Uji Coba --}}
      <div class="mb-2 bg-white dark:bg-gray-900 p-3 rounded border">
        <h4 class="font-semibold text-sm mb-2">F. Hasil Uji Coba</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="block">
            <span class="block text-sm font-medium mb-1">Status Mesin Setelah Perbaikan</span>
            <select name="mtc[machine_status_after]" class="w-full border p-2 rounded">
              <option value="">-- Pilih --</option>
              <option value="normal" @selected(old('mtc.machine_status_after', $ptk->mtcDetail->machine_status_after ?? '') == 'normal')>Berjalan Normal</option>
              <option value="trouble" @selected(old('mtc.machine_status_after', $ptk->mtcDetail->machine_status_after ?? '') == 'trouble')>Masih Bermasalah</option>
            </select>
          </label>
          <label class="block">
            <span class="block text-sm font-medium mb-1">Jam Uji Coba (Running Hours)</span>
            <input type="number" name="mtc[trial_hours]"
              value="{{ old('mtc.trial_hours', $ptk->mtcDetail->trial_hours ?? '') }}"
              class="w-full border p-2 rounded">
          </label>
          <label class="block md:col-span-2">
            <span class="block text-sm font-medium mb-1">Hasil Pengamatan</span>
            <textarea name="mtc[trial_result]" rows="3"
              class="w-full border p-2 rounded">{{ old('mtc.trial_result', $ptk->mtcDetail->trial_result ?? '') }}</textarea>
          </label>
        </div>
      </div>

    </div>
    @endhasanyrole

    {{-- Lampiran (tambah baru) --}}
    <div class="md:col-span-2">
      <label for="attachments" class="block text-sm font-medium mb-1">
        Lampiran (tambah)
      </label>
      <input id="attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf"
        class="border p-2 rounded w-full">
      @error('attachments.*')
        <small class="text-red-600">{{ $message }}</small>
      @enderror
    </div>

    {{-- Lampiran lama --}}
    @if($ptk->attachments->count())
      <div class="md:col-span-2 mt-4">
        <h3 class="text-sm font-semibold mb-2">
          Lampiran Lama
        </h3>

        <ul class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          @foreach($ptk->attachments as $att)
            @php
              $url = asset(Storage::url($att->path));
              $isImg = str_starts_with(strtolower($att->mime ?? ''), 'image/');
            @endphp

            <li class="relative group">
              {{-- Tombol Hapus: pakai form global di bawah --}}
              <button type="button"
                class="delete-attachment absolute -top-2 -right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs shadow opacity-80 group-hover:opacity-100"
                data-att-delete-url="{{ route('ptk.attachment.delete', $att->id) }}" title="Hapus lampiran">
                ×
              </button>

              {{-- Gambar / File --}}
              @if($isImg)
                <a href="{{ $url }}" target="_blank"
                  class="block w-full aspect-[4/3] overflow-hidden rounded-lg ring-1 ring-gray-200 bg-gray-50">
                  <img src="{{ $url }}" alt="{{ $att->original_name }}" loading="lazy" class="w-full h-full object-contain">
                </a>
              @else
                <a href="{{ $url }}" target="_blank"
                  class="flex items-center justify-center w-full aspect-[4/3] rounded-lg ring-1 ring-gray-200 bg-gray-50">
                  <span class="text-xs">
                    {{ strtoupper(pathinfo($att->original_name, PATHINFO_EXTENSION)) }}
                  </span>
                </a>
              @endif

              <div class="mt-1 text-xs truncate" title="{{ $att->original_name }}">
                {{ $att->original_name }}
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Tombol aksi --}}
    <div class="md:col-span-2">
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
        Simpan
      </button>
      <a href="{{ route('ptk.show', $ptk) }}" class="ml-2 underline">
        Batal
      </a>
    </div>
  </form>

  {{-- Form DELETE global (di luar form edit, agar tidak nested) --}}
  <form id="global-attachment-delete-form" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
  </form>

  @push('scripts')
    <script>
      // Dropdown subkategori dinamis
      async function loadSubcats(catId, selectedId = null) {
        const sel = document.getElementById('subcat');
        if (!sel) return;

        sel.innerHTML = '<option value="">-- pilih subkategori --</option>';
        sel.disabled = true;

        if (!catId) {
          sel.disabled = false;
          return;
        }

        try {
          const res = await fetch(`{{ route('api.subcategories') }}?category_id=${encodeURIComponent(catId)}`);
          if (!res.ok) throw new Error('Network response was not ok');

          const data = await res.json();

          data.forEach(row => {
            const opt = document.createElement('option');
            opt.value = row.id;
            opt.textContent = row.name;
            if (String(selectedId) === String(row.id)) {
              opt.selected = true;
            }
            sel.appendChild(opt);
          });
        } catch (e) {
          console.error(e);
        } finally {
          sel.disabled = false;
        }
      }

      document.addEventListener('DOMContentLoaded', function () {
        // Init subkategori
        const catSel = document.getElementById('cat');
        if (catSel) {
          catSel.addEventListener('change', () => loadSubcats(catSel.value));

          loadSubcats(
            catSel.value,
            @json(old('subcategory_id', $ptk->subcategory_id))
          );
        }

        // Hapus lampiran via form global (bukan AJAX)
        const globalDeleteForm = document.getElementById('global-attachment-delete-form');
        if (!globalDeleteForm) return;

        document.querySelectorAll('.delete-attachment').forEach(btn => {
          btn.addEventListener('click', function () {
            const url = btn.dataset.attDeleteUrl;
            if (!url) return;

            if (!confirm('Hapus lampiran ini?')) return;

            globalDeleteForm.action = url;
            globalDeleteForm.submit();
          });
        });
      });
    </script>
  @endpush
</x-layouts.app>