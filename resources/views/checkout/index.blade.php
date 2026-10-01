@extends('layouts.app')

@section('title', 'Checkout & Bakong KHQR — PsaOnline')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-black text-[#333333] tracking-tight">Express Checkout</h1>
        <p class="text-xs sm:text-sm text-stone-500">Fast delivery across Phnom Penh with Bakong KHQR verification.</p>
    </div>

    <form action="{{ route('checkout.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        @csrf
        <!-- Delivery Details & Interactive Map -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl border border-[#EDEDED] p-5 sm:p-6 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-[#333333] flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-[#FF5000] text-white flex items-center justify-center text-xs font-black">1</span>
                    <span>Recipient & Delivery Address</span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-[#333333] mb-1">Full Name *</label>
                        <input type="text" name="customer_name" required value="Sophea Chhum" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EDEDED] bg-[#F5F5F5] focus:border-[#FF5000] focus:outline-none" />
                    </div>
                    <div>
                        <label class="block font-bold text-[#333333] mb-1">Telegram / Phone Number *</label>
                        <input type="tel" name="customer_phone" required value="+855 96 554 1234" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EDEDED] bg-[#F5F5F5] focus:border-[#FF5000] focus:outline-none font-mono" />
                    </div>
                </div>

                <div class="text-xs">
                    <label class="block font-bold text-[#333333] mb-1">Phnom Penh Address *</label>
                    <input type="text" name="delivery_address" required value="Toul Kork, St 315, House #14, Phnom Penh" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EDEDED] bg-[#F5F5F5] focus:border-[#FF5000] focus:outline-none" />
                </div>

                <div class="text-xs">
                    <label class="block font-bold text-[#333333] mb-1">Delivery Notes (Optional)</label>
                    <input type="text" name="delivery_notes" placeholder="e.g. Leave with security, call when outside" class="w-full px-3.5 py-2.5 rounded-xl border border-[#EDEDED] bg-[#F5F5F5] focus:border-[#FF5000] focus:outline-none" />
                </div>
            </div>

            <!-- Payment Method Selector -->
            <div class="bg-white rounded-3xl border border-[#EDEDED] p-5 sm:p-6 shadow-sm space-y-4">
                <h2 class="text-base font-extrabold text-[#333333] flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-[#FF5000] text-white flex items-center justify-center text-xs font-black">2</span>
                    <span>Payment Gateway</span>
                </h2>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3.5 rounded-2xl border-2 border-[#FF5000] bg-[#FFF3EC] cursor-pointer">
                        <input type="radio" name="payment_method" value="bakong_khqr" checked class="text-[#FF5000] focus:ring-[#FF5000]" />
                        <div class="flex-1">
                            <div class="text-xs font-black text-[#333333] flex items-center gap-2">
                                <span>Bakong Universal KHQR</span>
                                <span class="bg-red-600 text-white text-[9px] px-1.5 py-0.5 rounded font-bold uppercase">Popular</span>
                            </div>
                            <div class="text-[11px] text-stone-600">Scan via ABA, ACLEDA, Canadia, Wing, or any mobile bank app.</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 rounded-2xl border border-[#EDEDED] hover:border-[#FF5000] bg-white cursor-pointer">
                        <input type="radio" name="payment_method" value="aba_pay" class="text-[#FF5000] focus:ring-[#FF5000]" />
                        <div class="flex-1">
                            <div class="text-xs font-black text-[#333333]">ABA Pay (Instant Redirect)</div>
                            <div class="text-[11px] text-stone-600">Seamless checkout for ABA Mobile app users.</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3.5 rounded-2xl border border-[#EDEDED] hover:border-[#FF5000] bg-white cursor-pointer">
                        <input type="radio" name="payment_method" value="cod" class="text-[#FF5000] focus:ring-[#FF5000]" />
                        <div class="flex-1">
                            <div class="text-xs font-black text-[#333333]">Cash On Delivery (COD)</div>
                            <div class="text-[11px] text-stone-600">Pay cash upon delivery in Phnom Penh.</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Summary & Submit in Cotton Beige Frame -->
        <div class="lg:col-span-5 bg-white rounded-3xl border-2 border-[#EDEDED] p-6 shadow-md space-y-4">
            <h2 class="text-base font-extrabold text-[#333333] border-b border-[#FFF3EC] pb-3">Order Total</h2>
            
            <div class="space-y-2 text-xs">
                <div class="flex justify-between text-stone-600">
                    <span>Subtotal</span>
                    <span class="font-bold text-[#333333]">${{ number_format($subtotalUsd, 2) }}</span>
                </div>
                <div class="flex justify-between text-stone-600">
                    <span>Phnom Penh Express Courier</span>
                    <span class="font-bold text-[#FF5000]">${{ number_format($deliveryFeeUsd, 2) }}</span>
                </div>
                <div class="border-t border-[#FFF3EC] pt-2 flex justify-between text-base font-black text-[#333333]">
                    <span>Total Amount</span>
                    <span>${{ number_format($totalUsd, 2) }}</span>
                </div>
                <div class="flex justify-between text-xs text-[#FF5000] font-black">
                    <span>Equivalent in KHR</span>
                    <span>{{ number_format($totalKhr) }} ៛</span>
                </div>
            </div>

            <button type="submit" class="btn-press w-full py-4 rounded-2xl bg-[#FF5000] hover:bg-[#E64500] text-white font-black text-sm shadow-lg flex items-center justify-center gap-2 transition-all">
                <span>Place Order & Generate KHQR</span>
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </div>
    </form>
</div>
@endsection
