<!DOCTYPE html>
<html lang="en" class="h-full" x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
  x-init="document.documentElement.classList.toggle('dark', dark)">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PTK Tracker</title>

  {{-- Vite --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- Vendor CSS --}}
  <link rel="stylesheet" href="{{ asset('vendor/bootstrap5/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/datatables/jquery.dataTables.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}">

  <style>
    [x-cloak] {
      display: none !important
    }
  </style>
</head>
<style>
  /* ===============================
     GLOBAL LINK COLOR (GRAY MODE)
     =============================== */

  /* Default link */
  a {
    color: #374151;
    /* gray-700 */
    text-decoration: none;
  }

  a:hover {
    color: #111827;
    /* gray-900 */
  }

  /* Navbar active link */
  .topnav a.font-semibold {
    color: #111827;
    /* gray-900 */
    border-color: #9ca3af;
    /* gray-400 */
  }

  /* DataTable links (PTK number, dll) */
  table.dataTable a {
    color: #374151;
    /* gray-700 */
    font-weight: 500;
  }

  table.dataTable a:hover {
    color: #111827;
    /* gray-900 */
    text-decoration: underline;
  }

  /* Dark mode */
  .dark a {
    color: #d1d5db;
    /* gray-300 */
  }

  .dark a:hover {
    color: #f9fafb;
    /* gray-50 */
  }

  .dark .topnav a.font-semibold {
    color: #f9fafb;
    border-color: #6b7280;
    /* gray-500 */
  }

  .dark table.dataTable a {
    color: #e5e7eb;
    /* gray-200 */
  }

  .dark table.dataTable a:hover {
    color: #ffffff;
  }
</style>

<body class="h-full bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
  <div class="max-w-7xl mx-auto p-6">

    {{-- ================= HEADER ================= --}}
    <header class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">PTK Tracker</h1>

      {{-- ================= NAVBAR ================= --}}
      <nav class="topnav hidden md:flex space-x-4">

        {{-- ===== UMUM (SEMUA USER LOGIN) ===== --}}
        <a href="{{ route('dashboard') }}"
          class="px-2 py-1 {{ request()->routeIs('dashboard') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
          Dashboard
        </a>

        <a href="{{ route('ptk.index') }}"
          class="px-2 py-1 {{ request()->routeIs('ptk.index') || request()->routeIs('ptk.show') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
          Daftar PTK
        </a>

        <a href="{{ route('ptk.kanban') }}"
          class="px-2 py-1 {{ request()->routeIs('ptk.kanban') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
          Kanban
        </a>

        <a href="{{ route('exports.range.form') }}"
          class="px-2 py-1 {{ request()->routeIs('exports.range.form') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
          Laporan Periode
        </a>

        {{-- ===== ADMIN INPUT (QC / HR / K3 / MTC) ===== --}}
        @hasanyrole('admin_qc_flange|admin_qc_fitting|admin_hr|admin_k3|admin_mtc')
        @can('ptk.create')
          <a href="{{ route('ptk.create') }}"
            class="px-2 py-1 {{ request()->routeIs('ptk.create') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
            New PTK
          </a>
        @endcan

        <a href="{{ route('approval.log') }}"
          class="px-2 py-1 {{ request()->routeIs('approval.log') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
          Approval Log
        </a>
        @endhasanyrole

        {{-- ===== KABAG / MANAGER / DIRECTOR ===== --}}
        @hasanyrole('kabag_qc|kabag_mtc|manager_hr|director')
        @can('menu.queue')
          <a href="{{ route('ptk.queue') }}"
            class="relative px-2 py-1 {{ request()->routeIs('ptk.queue*') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
            Antrian Persetujuan
            @if(isset($approvalQueueCount) && $approvalQueueCount > 0)
              <span
                class="absolute -top-2 -right-3 inline-flex items-center justify-center w-5 h-5 text-xs font-bold leading-none text-white bg-red-600 rounded-full border-2 border-white dark:border-gray-800">
                {{ $approvalQueueCount }}
              </span>
            @endif
          </a>
        @endcan

        <a href="{{ route('settings.categories') }}"
          class="px-2 py-1 {{ request()->routeIs('settings.*') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
          Settings
        </a>
        @endhasanyrole

        {{-- ===== DIRECTOR EXTRA ===== --}}
        @hasrole('director')
        <a href="{{ route('exports.audits.index') }}"
          class="px-2 py-1 {{ request()->routeIs('exports.audits.*') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
          Audit Log
        </a>

        <a href="{{ route('ptk.recycle') }}"
          class="px-2 py-1 {{ request()->routeIs('ptk.recycle') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
          Recycle Bin
        </a>
        @endhasrole

        {{-- ===== AUDITOR SAJA ===== --}}
        @hasrole('auditor')
        <a href="{{ route('exports.audits.index') }}"
          class="px-2 py-1 {{ request()->routeIs('exports.audits.*') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
          Audit Log
        </a>

        <a href="{{ route('approval.log') }}"
          class="px-2 py-1 {{ request()->routeIs('approval.log') ? 'font-semibold border-b-2 border-indigo-600' : '' }}">
          Approval Log
        </a>
        @endhasrole

      </nav>

      {{-- ================= USER & THEME ================= --}}
      <div class="flex items-center space-x-3 relative" x-data="{ open:false }">

        <button class="px-3 py-2 rounded bg-gray-200 dark:bg-gray-800"
          @click="dark=!dark; localStorage.setItem('theme',dark?'dark':'light'); document.documentElement.classList.toggle('dark',dark)">
          <span x-show="!dark">🌙</span>
          <span x-show="dark">☀️</span>
        </button>

        @auth
          <div class="relative">
            <button @click="open=!open"
              class="flex items-center space-x-2 px-3 py-2 bg-gray-200 dark:bg-gray-800 rounded">
              <span class="font-semibold">{{ auth()->user()->name }}</span>
            </button>

            <div x-show="open" x-transition x-cloak
              class="absolute right-0 mt-2 w-44 bg-white dark:bg-gray-800 border rounded shadow z-50">
              <div class="px-4 py-2 text-sm border-b">
                {{ auth()->user()->roles->pluck('name')->join(', ') }}
              </div>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                  Logout
                </button>
              </form>
            </div>
          </div>
        @endauth
      </div>
    </header>

    {{-- ================= FLASH MESSAGE ================= --}}
    @if(session('ok'))
      <div class="p-3 bg-green-100 text-green-800 rounded mb-4">
        {{ session('ok') }}
      </div>
    @endif

    {{-- ================= PAGE CONTENT ================= --}}
    {{ $slot }}

  </div>

  {{-- ================= JS ================= --}}
  <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/bootstrap5/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
  <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
  <script src="{{ asset('vendor/chartjs/chartjs-plugin-datalabels.min.js') }}"></script>
  {{-- Alpine.js is now handled by Vite in resources/js/app.js --}}

  @stack('scripts')
</body>

</html>