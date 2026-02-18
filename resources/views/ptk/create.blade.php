{{-- resources/views/ptk/create.blade.php --}}
<x-layouts.app>
  <h2 class="text-xl font-semibold mb-4">New PTK</h2>

  <form method="post" enctype="multipart/form-data" action="{{ route('ptk.store') }}"
    class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @csrf

    {{-- NOMOR PTK (required) --}}
    <div class="md:col-span-2 mb-3">
      <label class="block text-sm font-medium">Nomor PTK <span class="text-red-500">*</span></label>
      <input type="text" name="number" value="{{ old('number', $ptk->number ?? '') }}"
        class="w-full border rounded px-3 py-2" required placeholder="contoh: PTK/QC/2025/10/001">
      <p class="text-xs text-gray-500 mt-1">
        Nomor diisi manual oleh admin saat membuat PTK.
      </p>
      @error('number')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>

    <label for="title">Judul
      <input id="title" type="text" name="title" class="border p-2 rounded w-full" required value="{{ old('title') }}"
        maxlength="200">
      @error('title') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="category_id">Kategori
      <select id="category_id" name="category_id" class="border p-2 rounded w-full" required>
        <option value="">-- pilih --</option>
        @foreach($categories as $c)
          <option value="{{ $c->id }}" @selected(old('category_id') == $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
      @error('category_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="subcat">Subkategori
      <select name="subcategory_id" id="subcat" class="border p-2 rounded w-full"
        data-old="{{ old('subcategory_id') }}">
        <option value="">-- pilih subkategori --</option>
      </select>
      @error('subcategory_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- Departemen: selectable untuk semua role --}}
    <label for="department_id">Departemen
      <select id="department_id" name="department_id" class="border p-2 rounded w-full" required>
        <option value="">-- pilih --</option>
        @foreach($departments as $id => $name)
          <option value="{{ $id }}" @selected(old('department_id') == $id)>{{ $name }}</option>
        @endforeach
      </select>
      @error('department_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- PIC --}}
    <label for="pic_user_id">PIC
      <select id="pic_user_id" name="pic_user_id" class="border p-2 rounded w-full" required>
        <option value="">-- pilih --</option>
        @foreach($picCandidates as $uopt)
          <option value="{{ $uopt->id }}" @selected(old('pic_user_id') == $uopt->id)>{{ $uopt->name }}</option>
        @endforeach
      </select>
      @error('pic_user_id') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label for="due_date">Due date
      <input id="due_date" type="date" name="due_date" class="border p-2 rounded w-full" value="{{ old('due_date') }}"
        required>
      @error('due_date') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- Tanggal Form (tanggal di kertas) --}}
    <label for="form_date">Tanggal Form (Tanggal PTK Asli)
      <input id="form_date" type="date" name="form_date" value="{{ old('form_date', now()->toDateString()) }}"
        class="border p-2 rounded w-full" required>
      @error('form_date') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- Deskripsi umum (opsional) --}}
    @unlessrole('admin_mtc|kabag_mtc')
    <label for="description" class="md:col-span-2">Deskripsi
      <textarea id="description" name="description" rows="6"
        class="border p-2 rounded w-full">{{ old('description') }}</textarea>
      @error('description') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    {{-- 4 bagian tambahan --}}
    <label class="md:col-span-2">Deskripsi Ketidaksesuaian
      <textarea name="desc_nc" rows="5" class="border p-2 rounded w-full">{{ old('desc_nc') }}</textarea>
      @error('desc_nc') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label class="md:col-span-2">Evaluasi Masalah
      <textarea name="evaluation" rows="5" class="border p-2 rounded w-full">{{ old('evaluation') }}</textarea>
      @error('evaluation') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label class="md:col-span-2">3a. Koreksi (perbaikan masalah) dan Tindakan Korektif (akar masalah)
      <textarea name="action_correction" rows="5"
        class="border p-2 rounded w-full">{{ old('action_correction') }}</textarea>
      @error('action_correction') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <label class="md:col-span-2">4. Hasil Uji Coba
      <textarea name="action_corrective" rows="5"
        class="border p-2 rounded w-full">{{ old('action_corrective') }}</textarea>
      @error('action_corrective') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>
    @endunlessrole

    {{-- ========================================================= --}}
    {{-- MODUL KHUSUS MTC (Machine) --}}
    {{-- ========================================================= --}}
    @hasanyrole('admin_mtc|kabag_mtc')
    @php
      $defaultRow = ['name' => '', 'spec' => '', 'qty' => 1, 'supplier' => '', 'order_date' => '', 'status' => 'Requested', 'est_arrival_date' => '', 'actual_arrival_date' => ''];
      $spData = old('spareparts', [$defaultRow]);
      if (!is_array($spData) || empty($spData))
        $spData = [$defaultRow];

      foreach ($spData as &$row) {
        $row['_key'] = bin2hex(random_bytes(8));
        if (!empty($row['order_date']))
          $row['order_date'] = substr($row['order_date'], 0, 10);
        if (!empty($row['est_arrival_date']))
          $row['est_arrival_date'] = substr($row['est_arrival_date'], 0, 10);
        if (!empty($row['actual_arrival_date']))
          $row['actual_arrival_date'] = substr($row['actual_arrival_date'], 0, 10);
      }
      unset($row);
    @endphp
    <div class="md:col-span-2 border-t border-b my-4 bg-gray-50 dark:bg-gray-800 px-4 py-3 rounded" x-data="{ 
            needsSparepart: {{ old('mtc.needs_sparepart', '1') == '1' ? 'true' : 'false' }},
            spList: {{ json_encode(array_values($spData)) }}
         }">

      <h3 class="font-semibold text-base mb-3 text-indigo-600">B. Spesifik Machine (Maintenance)</h3>

      {{-- 1. Deskripsi Kerusakan --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <label class="block">
          <span class="block text-sm font-medium mb-1">Deskripsi Kerusakan Mesin</span>
          <textarea style="background-color: white;" name="mtc[machine_damage_desc]" rows="5"
            class="w-full border p-2 rounded"
            placeholder="Apa yang rusak? Dampak ke produksi?">{{ old('mtc.machine_damage_desc') }}</textarea>
        </label>

        <div class="space-y-4">
          <label class="block">
            <span class="block text-sm font-medium mb-1">Status Mesin</span>
            <select style="background-color: white;" name="mtc[machine_stop_status]" class="w-full border p-2 rounded">
              <option value="">-- Pilih --</option>
              <option value="total" @selected(old('mtc.machine_stop_status') == 'total')>Berhenti Total (Breakdown)
              </option>
              <option value="partial" @selected(old('mtc.machine_stop_status') == 'partial')>Berhenti Parsial (Masih bisa
                jalan)</option>
            </select>
          </label>
          <label class="block">
            <span class="block text-sm font-medium mb-1">Evaluasi Masalah</span>
            <textarea style="background-color: white;" name="mtc[problem_evaluation]" rows="2"
              class="w-full border p-2 rounded">{{ old('mtc.problem_evaluation') }}</textarea>
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
          @click="spList.push({name:'', spec:'', qty:1, supplier:'', order_date:'', status:'Requested', est_arrival_date:'', actual_arrival_date:'', _key: (Date.now() + Math.random())})"
          class="mt-2 text-sm text-blue-600 hover:underline">+ Tambah Baris</button>
      </div>

      {{-- E. Koreksi & Perbaikan (Sparepart datang / Langsung) --}}
      <div class="mb-4 bg-white dark:bg-gray-900 p-3 rounded border">
        <h4 class="font-semibold text-sm mb-2">E. Koreksi & Perbaikan</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="block">
            <span class="block text-sm font-medium mb-1">Tanggal Pemasangan / Perbaikan</span>
            <input type="date" name="mtc[installation_date]" value="{{ old('mtc.installation_date') }}"
              class="w-full border p-2 rounded">
          </label>
          <label class="block">
            <span class="block text-sm font-medium mb-1">Perbaikan Oleh</span>
            <input type="text" name="mtc[repaired_by]" value="{{ old('mtc.repaired_by') }}"
              class="w-full border p-2 rounded" placeholder="Nama teknisi / vendor">
          </label>
          <label class="block md:col-span-2">
            <span class="block text-sm font-medium mb-1">Catatan Teknis</span>
            <textarea name="mtc[technical_notes]" rows="3"
              class="w-full border p-2 rounded">{{ old('mtc.technical_notes') }}</textarea>
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
              <option value="normal" @selected(old('mtc.machine_status_after') == 'normal')>Berjalan Normal</option>
              <option value="trouble" @selected(old('mtc.machine_status_after') == 'trouble')>Masih Bermasalah</option>
            </select>
          </label>
          <label class="block">
            <span class="block text-sm font-medium mb-1">Jam Uji Coba (Running Hours)</span>
            <input type="number" name="mtc[trial_hours]" value="{{ old('mtc.trial_hours') }}"
              class="w-full border p-2 rounded">
          </label>
          <label class="block md:col-span-2">
            <span class="block text-sm font-medium mb-1">Hasil Pengamatan</span>
            <textarea name="mtc[trial_result]" rows="3"
              class="w-full border p-2 rounded">{{ old('mtc.trial_result') }}</textarea>
          </label>
        </div>
      </div>

    </div>
    @endhasanyrole

    <label for="attachments" class="md:col-span-2">Lampiran (multiple)
      <input id="attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf"
        class="border p-2 rounded w-full">
      @error('attachments.*') <small class="text-red-600">{{ $message }}</small> @enderror
    </label>

    <div class="md:col-span-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
      <a href="{{ route('ptk.index') }}" class="ml-2 underline">Batal</a>
    </div>
  </form>

  @push('scripts')
    <script>
      async function loadSubcats(catId, preselectedId = null) {
        const sel = document.getElementById('subcat');
        sel.innerHTML = '<option value="">-- pilih subkategori --</option>';
        sel.disabled = true;

        if (!catId) { sel.disabled = false; return; }

        try {
          const res = await fetch(`{{ route('api.subcategories') }}?category_id=${encodeURIComponent(catId)}`);
          if (!res.ok) throw new Error('Network response was not ok');
          const data = await res.json();

          data.forEach(row => {
            const opt = document.createElement('option');
            opt.value = row.id;
            opt.textContent = row.name;
            if (preselectedId && String(preselectedId) === String(row.id)) opt.selected = true;
            sel.appendChild(opt);
          });
        } catch (e) { console.error(e); }
        finally { sel.disabled = false; }
      }

      document.getElementById('category_id').addEventListener('change', function () {
        loadSubcats(this.value);
      });

      (function preload() {
        const oldCategory = "{{ old('category_id') }}";
        const oldSubcat = document.getElementById('subcat').dataset.old || "";
        if (oldCategory) { loadSubcats(oldCategory, oldSubcat); }
      })();
    </script>
  @endpush
</x-layouts.app>