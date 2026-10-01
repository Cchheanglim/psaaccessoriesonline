@extends('layouts.app')

@section('title', 'Checkout & Bakong KHQR — PsaOnline')

@section('content')
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
    .gateway-info[hidden], .checkout-dialog[hidden] { display: none !important; }
    .checkout-dialog { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgba(3,5,9,.78); }
    .checkout-dialog-card { width: min(100%, 420px); background: #1e2430; color: #f4f5f7; border: 1px solid #394353; border-radius: 18px; padding: 24px; }
</style>
<div class="checkout-dark min-h-screen">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-black text-[#2B1D1D] tracking-tight">Express Checkout</h1>
        <p class="text-xs sm:text-sm text-stone-500">Fast delivery across Phnom Penh with Bakong KHQR verification.</p>
    </div>

    <form id="checkoutForm" action="{{ route('checkout.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        @csrf
        <!-- Delivery Details & Interactive Map -->
        <div class="lg:col-span-7 space-y-6">
            <div class="checkout-card bg-white rounded-3xl border border-[#EFE4D6] p-5 sm:p-6 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-[#2B1D1D] flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-[#FFA552] text-[#2B1D1D] flex items-center justify-center text-xs font-black">1</span>
                    <span>Recipient & Delivery Address</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-[#2B1D1D] mb-1">Full Name *</label>
                        <input type="text" name="customer_name" required value="Sophea Chhum" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EFE4D6] bg-[#FDFBF7] focus:border-[#FFA552] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block font-bold text-[#2B1D1D] mb-1">Telegram / Phone Number *</label>
                        <input type="tel" name="customer_phone" required value="+855 96 554 1234" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EFE4D6] bg-[#FDFBF7] focus:border-[#FFA552] focus:outline-none font-mono" />
                    </div>
                </div>

                <div class="text-xs">
                    <label class="block font-bold text-[#2B1D1D] mb-1">Phnom Penh Address *</label>
                    <input type="text" name="delivery_address" required value="Toul Kork, St 315, House #14, Phnom Penh" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EFE4D6] bg-[#FDFBF7] focus:border-[#FFA552] focus:outline-none" />
                </div>

                <div class="text-xs">
                    <label class="block font-bold text-[#2B1D1D] mb-1">Delivery Notes (Optional)</label>
                    <input type="text" name="delivery_notes" placeholder="e.g. Leave with security, call when outside" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EFE4D6] bg-[#FDFBF7] focus:border-[#FFA552] focus:outline-none" />
                </div>
            </div>

            <!-- Payment Method Selector -->
            <div class="checkout-card bg-white rounded-3xl border border-[#EFE4D6] p-5 sm:p-6 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-[#2B1D1D] flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-[#FFA552] text-[#2B1D1D] flex items-center justify-center text-xs font-black">2</span>
                    <span>Payment Gateway</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3">
                    <label class="gateway-option is-selected flex flex-col justify-between gap-3 p-4 rounded-2xl cursor-pointer">
                        <input type="radio" name="payment_method" value="bakong_khqr" checked class="sr-only" />
                        <span class="text-[10px] font-black uppercase text-red-300">Popular</span>
                        <span><strong class="block text-xs">Bakong Universal KHQR</strong><span class="block text-[11px] mt-1">Scan with Cambodian banking apps</span></span>
                    </label>
                    <label class="gateway-option flex flex-col justify-between gap-3 p-4 rounded-2xl cursor-pointer">
                        <input type="radio" name="payment_method" value="aba_pay" class="sr-only" />
                        <span class="text-[10px] font-bold text-blue-300">Instant</span>
                        <span><strong class="block text-xs">ABA Mobile Pay</strong><span class="block text-[11px] mt-1">ABA App transfer or KHQR</span></span>
                    </label>
                    <label class="gateway-option flex flex-col justify-between gap-3 p-4 rounded-2xl cursor-pointer">
                        <input type="radio" name="payment_method" value="acleda_khqr" class="sr-only" />
                        <span class="text-[10px] font-bold text-emerald-300">0% Fee</span>
                        <span><strong class="block text-xs">ACLEDA Mobile</strong><span class="block text-[11px] mt-1">ToanChet App or ACLEDA KHQR</span></span>
                    </label>
                    <label class="gateway-option flex flex-col justify-between gap-3 p-4 rounded-2xl cursor-pointer">
                        <input type="radio" name="payment_method" value="visa_card" class="sr-only" />
                        <span class="text-[10px] font-bold text-sky-300">3D Secure</span>
                        <span><strong class="block text-xs">Visa / Mastercard</strong><span class="block text-[11px] mt-1">International Credit &amp; Debit Cards</span></span>
                    </label>
                    <label class="gateway-option flex flex-col justify-between gap-3 p-4 rounded-2xl cursor-pointer">
                        <input type="radio" name="payment_method" value="cod" class="sr-only" />
                        <span class="text-[10px] font-bold text-slate-300">Phnom Penh</span>
                        <span><strong class="block text-xs">Cash on Delivery</strong><span class="block text-[11px] mt-1">Pay courier at delivery</span></span>
                    </label>
                </div>

                <div id="visaCardForm" class="gateway-info rounded-xl border border-slate-700 bg-[#151922] p-4 space-y-3" hidden>
                    <p class="text-[11px]">Demo card form only. Values are not submitted or stored.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <label class="sm:col-span-2">Cardholder Name<input id="cardholderName" autocomplete="cc-name" class="block w-full mt-1 rounded-lg px-3 py-2" placeholder="Full name as on card"></label>
                        <label class="sm:col-span-2">Card Number <span id="cardBrandLabel" class="text-amber-400">CARD</span><input id="cardNumber" inputmode="numeric" autocomplete="cc-number" maxlength="23" class="block w-full mt-1 rounded-lg px-3 py-2 font-mono" placeholder="•••• •••• •••• ••••"></label>
                        <label>Expiry Date<input id="cardExpiry" inputmode="numeric" autocomplete="cc-exp" maxlength="5" class="block w-full mt-1 rounded-lg px-3 py-2 font-mono" placeholder="MM/YY"></label>
                        <label>CVV / CVC<input id="cardCvv" type="password" inputmode="numeric" autocomplete="cc-csc" maxlength="3" title="Three-digit security code on the back of the card" class="block w-full mt-1 rounded-lg px-3 py-2 font-mono" placeholder="•••"></label>
                    </div>
                    <div class="flex flex-wrap gap-2 text-[10px] font-bold"><span class="rounded-full border border-slate-600 px-2 py-1">Verified by Visa</span><span class="rounded-full border border-slate-600 px-2 py-1">Mastercard ID Check</span><span class="rounded-full border border-slate-600 px-2 py-1">PCI-DSS Compliant &amp; 256-Bit SSL Encrypted</span></div>
                </div>
                <p id="paymentNote" class="text-[11px] text-slate-300">Your Bakong KHQR payment details will be prepared immediately.</p>
            </div>
        </div>

        <!-- Summary & Submit in Cotton Beige Frame -->
        <div class="checkout-card lg:col-span-5 bg-white rounded-3xl border-2 border-[#EFE4D6] p-6 shadow-md space-y-4">
            <h2 class="text-base font-extrabold text-[#2B1D1D] border-b border-[#F9F3EA] pb-3">Order Total</h2>
            
            <div class="space-y-2 text-xs">
                <div class="flex justify-between text-stone-600">
                    <span>Subtotal</span>
                    <span class="font-bold text-[#2B1D1D]">${{ number_format($subtotalUsd, 2) }}</span>
                </div>
                <div class="flex justify-between text-stone-600">
                    <span>Phnom Penh Express Courier</span>
                    <span class="font-bold text-[#FFA552]">${{ number_format($deliveryFeeUsd, 2) }}</span>
                </div>
                <div class="border-t border-[#F9F3EA] pt-2 flex justify-between text-base font-black text-[#2B1D1D]">
                    <span>Total Amount</span>
                    <span>${{ number_format($totalUsd, 2) }}</span>
                </div>
                <div class="flex justify-between text-xs text-[#FFA552] font-black">
                    <span>Equivalent in KHR</span>
                    <span>{{ number_format($totalKhr) }} ៛</span>
                </div>
            </div>

            <button id="placeOrderBtn" type="submit" class="btn-press w-full py-4 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-sm shadow-lg flex items-center justify-center gap-2 transition-all">
                <span id="placeOrderLabel">Place Order & Generate KHQR</span>
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </div>

        <div id="visaOtpDialog" class="checkout-dialog" hidden>
            <section class="checkout-dialog-card space-y-4" role="dialog" aria-modal="true" aria-labelledby="visaOtpTitle">
                <div class="flex items-start justify-between"><div><p class="text-[10px] font-bold uppercase text-sky-300">3D Secure • Demo</p><h2 id="visaOtpTitle" class="text-lg font-black mt-1">Verify this transaction</h2></div><button type="button" id="closeVisaOtp" class="text-slate-400 text-xl" aria-label="Close">&times;</button></div>
                <p class="text-xs text-slate-300">Demo OTP: <strong class="text-amber-300">123456</strong>. This simulates issuer approval; no card charge occurs.</p>
                <label class="block text-xs">One-time passcode<input id="visaOtpInput" inputmode="numeric" maxlength="6" class="block w-full mt-1 rounded-lg border border-slate-600 bg-[#151922] px-3 py-3 text-center font-mono text-lg text-white" placeholder="000000"></label>
                <p id="visaOtpError" class="text-xs text-rose-300" role="alert"></p>
                <button type="button" id="approveVisaDemo" class="w-full rounded-xl bg-amber-500 px-4 py-3 text-xs font-black text-slate-950">Verify &amp; approve demo payment</button>
            </section>
        </div>
    </form>
</div>
</div>
<script>
    (() => {
        const form = document.getElementById('checkoutForm');
        const radios = [...form.querySelectorAll('input[name="payment_method"]')];
        const visaForm = document.getElementById('visaCardForm');
        const note = document.getElementById('paymentNote');
        const labels = {
            bakong_khqr: ['Place Order & Generate KHQR', 'Your Bakong KHQR payment details will be prepared immediately.'],
            aba_pay: ['Continue to ABA Mobile Pay', 'You will continue to ABA Mobile Pay after placing your order.'],
            acleda_khqr: ['Generate ACLEDA KHQR', 'Your ACLEDA KHQR will be generated immediately. Live bank verification is not connected in this demo.'],
            visa_card: ['Continue to 3D Secure', 'You will be redirected to 3D Secure verification. Demo approval only; no charge occurs.'],
            cod: ['Place Order', 'Pay the courier in cash when your parcel is delivered.']
        };
        const brandOf = number => /^4/.test(number) ? 'VISA' : (/^(5[1-5])/.test(number) || (+number.slice(0, 4) >= 2221 && +number.slice(0, 4) <= 2720) ? 'MASTERCARD' : 'CARD');
        const passesLuhn = number => {
            let sum = 0;
            let doubleDigit = false;
            for (let index = number.length - 1; index >= 0; index -= 1) {
                let digit = Number(number[index]);
                if (doubleDigit) { digit *= 2; if (digit > 9) digit -= 9; }
                sum += digit;
                doubleDigit = !doubleDigit;
            }
            return sum % 10 === 0;
        };

        function updateMethod(radio) {
            radios.forEach(input => input.closest('.gateway-option').classList.toggle('is-selected', input === radio));
            visaForm.hidden = radio.value !== 'visa_card';
            document.getElementById('placeOrderLabel').textContent = labels[radio.value][0];
            note.textContent = labels[radio.value][1];
        }

        radios.forEach(radio => radio.addEventListener('change', () => updateMethod(radio)));
        const numberInput = document.getElementById('cardNumber');
        numberInput.addEventListener('input', () => {
            const digits = numberInput.value.replace(/\D/g, '').slice(0, 16);
            numberInput.value = digits.replace(/(.{4})/g, '$1 ').trim();
            document.getElementById('cardBrandLabel').textContent = brandOf(digits);
        });
        document.getElementById('cardExpiry').addEventListener('input', event => {
            const digits = event.target.value.replace(/\D/g, '').slice(0, 4);
            event.target.value = digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
        });
        document.getElementById('cardCvv').addEventListener('input', event => event.target.value = event.target.value.replace(/\D/g, '').slice(0, 3));

        form.addEventListener('submit', event => {
            const method = radios.find(radio => radio.checked).value;
            if (method !== 'visa_card' || form.dataset.demoOtpPassed === 'true') return;
            event.preventDefault();
            const number = numberInput.value.replace(/\s/g, '');
            const expiry = document.getElementById('cardExpiry').value;
            const [month, year] = expiry.split('/').map(Number);
            const validExpiry = /^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry) && new Date(2000 + year, month, 1) > new Date(new Date().getFullYear(), new Date().getMonth(), 1);
            const validBrand = brandOf(number) !== 'CARD' && number.length >= 15 && number.length <= 16 && passesLuhn(number);
            if (!document.getElementById('cardholderName').value.trim() || !validBrand || !validExpiry || !/^\d{3}$/.test(document.getElementById('cardCvv').value)) {
                alert('Enter a cardholder name, supported card number, valid MM/YY expiry, and 3-digit CVV.');
                return;
            }
            document.getElementById('visaOtpError').textContent = '';
            document.getElementById('visaOtpInput').value = '';
            document.getElementById('visaOtpDialog').hidden = false;
            document.getElementById('visaOtpInput').focus();
        });

        document.getElementById('approveVisaDemo').addEventListener('click', () => {
            if (document.getElementById('visaOtpInput').value !== '123456') {
                document.getElementById('visaOtpError').textContent = 'Use the demo OTP shown above.';
                return;
            }
            form.dataset.demoOtpPassed = 'true';
            document.getElementById('visaOtpDialog').hidden = true;
            note.textContent = '3D Secure simulation passed. Your order will be created without charging a card.';
            form.requestSubmit();
        });
        document.getElementById('closeVisaOtp').addEventListener('click', () => document.getElementById('visaOtpDialog').hidden = true);
        updateMethod(radios.find(radio => radio.checked));
    })();
</script>
@endsection
