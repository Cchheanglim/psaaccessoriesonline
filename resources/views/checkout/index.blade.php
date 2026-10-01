@extends('layouts.app')

@section('title', 'Checkout | PsaOnlineAccessories')

@section('content')
@php($customer = auth()->user())
<style>
    .checkout-dark { background: #0f1117; color: #e8ebf0; }
    .checkout-dark > div > div:first-child h1 { color: #f4f5f7 !important; }
    .checkout-dark > div > div:first-child p { color: #aeb6c4 !important; }
    .checkout-dark .checkout-card { background: #1e2430; border-color: #303847; color: #e8ebf0; }
    .checkout-dark .checkout-card h1, .checkout-dark .checkout-card h2, .checkout-dark .checkout-card h3,
    .checkout-dark .checkout-card label, .checkout-dark .checkout-card strong { color: #f4f5f7; }
    .checkout-dark .checkout-card p, .checkout-dark .checkout-card .text-stone-500,
    .checkout-dark .checkout-card .text-stone-600 { color: #aeb6c4; }
    .checkout-dark input:not([type="radio"]) { background: #151922; border-color: #394353; color: #f4f5f7; }
    .gateway-option { min-height: 112px; background: #171c25; border: 1px solid #394353; }
    .gateway-option.is-selected { border: 2px solid #f59e0b; background: #25251f; }
    .gateway-option:hover { border-color: #f59e0b; }
    .gateway-option.is-disabled { opacity: .5; cursor: not-allowed; }
    .gateway-option.is-disabled:hover { border-color: #394353; }
</style>
<div class="checkout-dark min-h-screen">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-black text-[#2B1D1D] tracking-tight">Checkout</h1>
        <p class="text-xs sm:text-sm text-stone-500">Delivery anywhere in Phnom Penh for a flat ${{ number_format($deliveryFeeUsd, 2) }}.</p>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-6 rounded-xl border border-rose-400/50 bg-rose-950/40 px-4 py-3 text-xs text-rose-200">
            <p class="font-bold">Please check the highlighted details.</p>
            <ul class="mt-1 list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="checkoutForm" action="{{ route('checkout.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        @csrf
        <!-- Delivery Details -->
        <div class="lg:col-span-7 space-y-6">
            <div class="checkout-card bg-white rounded-3xl border border-[#EFE4D6] p-5 sm:p-6 space-y-4">
                <h2 class="text-base font-extrabold text-[#2B1D1D] flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-[#FFA552] text-[#2B1D1D] flex items-center justify-center text-xs font-black">1</span>
                    <span>Recipient and delivery address</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label for="customer_name" class="block font-bold text-[#2B1D1D] mb-1">Full name *</label>
                        <input id="customer_name" type="text" name="customer_name" required maxlength="120" autocomplete="name" value="{{ old('customer_name', $customer?->name) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EFE4D6] bg-[#FDFBF7] focus:border-[#FFA552] focus:outline-none" />
                    </div>
                    <div>
                        <label for="customer_phone" class="block font-bold text-[#2B1D1D] mb-1">Phone or Telegram number *</label>
                        <input id="customer_phone" type="tel" name="customer_phone" required maxlength="30" autocomplete="tel" pattern="[0-9+\s\-\(\)]+" placeholder="+855 12 345 678" value="{{ old('customer_phone', $customer?->phone) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EFE4D6] bg-[#FDFBF7] focus:border-[#FFA552] focus:outline-none font-mono" />
                    </div>
                </div>

                <div class="text-xs">
                    <label for="delivery_address" class="block font-bold text-[#2B1D1D] mb-1">Phnom Penh address *</label>
                    <input id="delivery_address" type="text" name="delivery_address" required maxlength="300" autocomplete="street-address" placeholder="House number, street, sangkat, khan" value="{{ old('delivery_address', $customer?->address) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EFE4D6] bg-[#FDFBF7] focus:border-[#FFA552] focus:outline-none" />
                </div>

                <div class="text-xs">
                    <label for="delivery_notes" class="block font-bold text-[#2B1D1D] mb-1">Delivery notes (optional)</label>
                    <input id="delivery_notes" type="text" name="delivery_notes" maxlength="200" value="{{ old('delivery_notes') }}" placeholder="For example: leave with security, call when outside" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EFE4D6] bg-[#FDFBF7] focus:border-[#FFA552] focus:outline-none" />
                </div>
            </div>

            <!-- Payment Method Selector -->
            <fieldset class="checkout-card bg-white rounded-3xl border border-[#EFE4D6] p-5 sm:p-6 space-y-4">
                <legend class="sr-only">Payment method</legend>
                <h2 class="text-base font-extrabold text-[#2B1D1D] flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-[#FFA552] text-[#2B1D1D] flex items-center justify-center text-xs font-black">2</span>
                    <span>Payment method</span>
                </h2>

                @php($selectedMethod = old('payment_method', 'bakong_khqr'))
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3">
                    @foreach ([
                        'bakong_khqr' => ['Bakong KHQR', 'Scan with any Cambodian banking app', 'KHQR'],
                        'aba_pay' => ['ABA Pay', 'Pay from the ABA Mobile app', 'ABA'],
                        'acleda_khqr' => ['ACLEDA', 'ToanChet app or ACLEDA KHQR', 'ACLEDA'],
                        'cod' => ['Cash on delivery', 'Pay the courier when your parcel arrives', 'Phnom Penh'],
                    ] as $value => [$name, $detail, $tag])
                        <label class="gateway-option {{ $selectedMethod === $value ? 'is-selected' : '' }} flex flex-col justify-between gap-3 p-4 rounded-2xl cursor-pointer">
                            <input type="radio" name="payment_method" value="{{ $value }}" @checked($selectedMethod === $value) class="sr-only" />
                            <span class="text-[10px] font-bold uppercase text-slate-300">{{ $tag }}</span>
                            <span><strong class="block text-xs">{{ $name }}</strong><span class="block text-[11px] mt-1">{{ $detail }}</span></span>
                        </label>
                    @endforeach
                    <div class="gateway-option is-disabled flex flex-col justify-between gap-3 p-4 rounded-2xl" aria-disabled="true">
                        <span class="text-[10px] font-bold uppercase text-slate-400">Not available yet</span>
                        <span><strong class="block text-xs">Visa / Mastercard</strong><span class="block text-[11px] mt-1">Card payments are not accepted yet</span></span>
                    </div>
                </div>

                <p id="paymentNote" class="text-[11px] text-slate-300"></p>
            </fieldset>
        </div>

        <!-- Summary & Submit -->
        <div class="checkout-card lg:col-span-5 bg-white rounded-3xl border-2 border-[#EFE4D6] p-6 shadow-md space-y-4">
            <h2 class="text-base font-extrabold text-[#2B1D1D] border-b border-[#F9F3EA] pb-3">Order total</h2>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between text-stone-600">
                    <span>Subtotal</span>
                    <span class="font-bold text-[#2B1D1D]">${{ number_format($subtotalUsd, 2) }}</span>
                </div>
                <div class="flex justify-between text-stone-600">
                    <span>Delivery in Phnom Penh</span>
                    <span class="font-bold text-[#FFA552]">${{ number_format($deliveryFeeUsd, 2) }}</span>
                </div>
                <div class="border-t border-[#F9F3EA] pt-2 flex justify-between text-base font-black text-[#2B1D1D]">
                    <span>Total</span>
                    <span>${{ number_format($totalUsd, 2) }}</span>
                </div>
                <div class="flex justify-between text-xs text-[#FFA552] font-black">
                    <span>Total in riel</span>
                    <span>{{ number_format($totalKhr) }} ៛</span>
                </div>
            </div>

            <button id="placeOrderBtn" type="submit" class="btn-press w-full py-4 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-sm shadow-lg flex items-center justify-center gap-2 transition-all">
                <span id="placeOrderLabel">Place order</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>

            <p class="text-[11px] text-stone-500">
                By placing an order you agree to our <a href="{{ route('terms') }}" class="underline">Terms and Conditions</a>
                and acknowledge our <a href="{{ route('privacy') }}" class="underline">Privacy Policy</a>.
            </p>
        </div>
    </form>
</div>
</div>
<script>
    (() => {
        const form = document.getElementById('checkoutForm');
        const radios = [...form.querySelectorAll('input[name="payment_method"]')];
        const note = document.getElementById('paymentNote');
        const labels = {
            bakong_khqr: ['Place order and show KHQR', 'After you place the order we show a KHQR code to scan, then you upload your transfer slip.'],
            aba_pay: ['Place order and pay with ABA', 'After you place the order, pay from the ABA Mobile app and upload your transfer slip.'],
            acleda_khqr: ['Place order and show ACLEDA KHQR', 'After you place the order we show an ACLEDA KHQR code, then you upload your transfer slip.'],
            cod: ['Place order', 'Pay the courier in cash when your parcel is delivered.']
        };

        function updateMethod(radio) {
            radios.forEach(input => input.closest('.gateway-option').classList.toggle('is-selected', input === radio));
            document.getElementById('placeOrderLabel').textContent = labels[radio.value][0];
            note.textContent = labels[radio.value][1];
        }

        radios.forEach(radio => radio.addEventListener('change', () => updateMethod(radio)));
        // Guard against double orders from a double click. The pageshow
        // handler re-enables the button if the browser restores this page
        // from its back/forward cache.
        const button = document.getElementById('placeOrderBtn');
        form.addEventListener('submit', () => {
            button.disabled = true;
            button.classList.add('opacity-60');
        });
        window.addEventListener('pageshow', () => {
            button.disabled = false;
            button.classList.remove('opacity-60');
        });
        updateMethod(radios.find(radio => radio.checked));
    })();
</script>
@endsection
