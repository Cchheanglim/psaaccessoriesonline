@php
    $styles = [
        'pending' => ['Cash on delivery', 'bg-stone-100 text-stone-700 border-stone-200'],
        'pending_slip' => ['Awaiting slip', 'bg-amber-50 text-amber-700 border-amber-200'],
        'slip_uploaded' => ['Slip uploaded', 'bg-blue-50 text-blue-700 border-blue-200'],
        'verified' => ['Verified', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'failed' => ['Failed', 'bg-red-50 text-red-700 border-red-200'],
    ];
    [$label, $classes] = $styles[$status] ?? [ucwords(str_replace('_', ' ', $status)), 'bg-stone-100 text-stone-700 border-stone-200'];
@endphp
<span class="px-2 py-0.5 rounded-md font-black text-[10px] border {{ $classes }}">{{ $label }}</span>
