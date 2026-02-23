@props(['status'])
@php
  $map = [
    'Not Started' => 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-100',
    'In Progress' => 'bg-amber-200 text-amber-900 dark:bg-amber-700 dark:text-white',
    'Submitted' => 'bg-cyan-200 text-cyan-900 dark:bg-cyan-700 dark:text-white',
    'Waiting Director' => 'bg-purple-200 text-purple-900 dark:bg-purple-700 dark:text-white',
    'Completed' => 'bg-emerald-200 text-emerald-900 dark:bg-emerald-700 dark:text-white',
  ];
  $cls = $map[$status] ?? 'bg-gray-200 text-gray-800';
@endphp
<span class="px-2 py-0.5 text-xs font-semibold rounded {{ $cls }}">{{ $status }}</span>