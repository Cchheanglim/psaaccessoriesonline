@extends('layouts.app')

@section('title', 'Terms of Service | PsaOnline')

@push('styles')
<style>
.legal{--fg:#2B1D1D;--muted:#5c4d4d;--line:#EFE4D6;--soft:#F9F3EA;--accent:#FFA552;color:var(--fg);line-height:1.7;font-size:16px}
.legal a{color:var(--fg);text-decoration:underline;text-underline-offset:3px;text-decoration-color:var(--accent);text-decoration-thickness:2px}
.legal a:hover{text-decoration-color:var(--fg)}
.legal-wrap{max-width:760px;margin:0 auto;padding:48px 20px 72px}
.legal h1{font-size:34px;line-height:1.2;margin:0 0 8px;letter-spacing:-0.01em;font-weight:800}
.legal .meta{color:var(--muted);font-size:14px;margin:0 0 28px}
.legal .intro{font-size:17px;margin:0 0 28px}
.legal .toc{background:var(--soft);border:1px solid var(--line);border-radius:6px;padding:16px 20px;margin:0 0 36px}
.legal .toc p{margin:0 0 8px;font-weight:700;font-size:14px;text-transform:uppercase;letter-spacing:.06em}
.legal .toc ol{margin:0;padding-left:20px;columns:2;column-gap:32px;font-size:15px;list-style:decimal}
.legal .toc li{margin:2px 0;break-inside:avoid}
.legal h2{font-size:21px;margin:40px 0 8px;line-height:1.3;scroll-margin-top:90px;font-weight:700}
.legal h3{font-size:16px;margin:20px 0 4px;font-weight:700}
.legal p{margin:0 0 14px}
.legal ul{margin:0 0 14px;padding-left:22px;list-style:disc}
.legal li{margin:4px 0}
.legal .btn{display:inline-block;padding:10px 18px;border:2px solid var(--fg);border-radius:4px;background:var(--fg);color:#fff;text-decoration:none;font-weight:700;font-size:14px}
.legal .btn:hover{background:#fff;color:var(--fg)}
@@media (max-width:600px){.legal h1{font-size:28px}.legal .toc ol{columns:1}.legal-wrap{padding-top:32px}}
</style>
@endpush

@section('content')
<div class="legal">
    <div class="legal-wrap">
        <h1>Terms of Service</h1>
        <p class="meta">Last updated: 2 October 2026</p>
        <p class="intro">These are the rules for using our website and buying from us. Please read them before you place an order.</p>

        <nav class="toc" aria-label="Contents">
            <p>Contents</p>
            <ol>
            <li><a href="#about">About these terms</a></li>
            <li><a href="#account">Your account</a></li>
            <li><a href="#products-prices">Products and prices</a></li>
            <li><a href="#orders">Placing an order</a></li>
            <li><a href="#payment">Payment</a></li>
            <li><a href="#delivery">Delivery</a></li>
            <li><a href="#returns">Returns, refunds, and cancellations</a></li>
            <li><a href="#acceptable-use">Acceptable use</a></li>
            <li><a href="#intellectual-property">Intellectual property</a></li>
            <li><a href="#liability">Our responsibility</a></li>
            <li><a href="#governing-law">Governing law</a></li>
            <li><a href="#changes">Changes to these terms</a></li>
            <li><a href="#contact">Contact us</a></li>
            </ol>
        </nav>

        <section>
            <h2 id="about">1. About these terms</h2>
<p>These terms apply to your use of the PsaOnline website and to every order you place with us. PsaOnline (also called Psa Accessories Online, "we", "us") is an online shop for accessories based in Phnom Penh, Cambodia.</p>
<p>By using the site or placing an order, you agree to these terms and to our <a href="{{ route('privacy') }}">Privacy Policy</a>. If you do not agree, please do not use the site.</p>
        </section>
        <section>
            <h2 id="account">2. Your account</h2>
<ul>
<li>You must give accurate information when you register and when you order.</li>
<li>You are responsible for keeping your password private and for what happens under your account.</li>
<li>Tell us straight away if you think someone else is using your account.</li>
<li>We may suspend or close an account that is used for fraud, abuse, or to break these terms.</li>
</ul>
        </section>
        <section>
            <h2 id="products-prices">3. Products and prices</h2>
<ul>
<li>Prices are shown in US dollars (USD) and Cambodian riel (KHR). We convert at a fixed rate of 1 USD = 4,100 KHR.</li>
<li>We try to describe and photograph products accurately. Colors can look slightly different on different screens, and handmade items can vary a little from piece to piece.</li>
<li>Stock is limited. If an item is sold out or a price was listed by mistake, we may cancel that part of your order and refund anything you paid for it.</li>
<li>Prices and products can change at any time, but a change does not affect an order we have already confirmed.</li>
</ul>
        </section>
        <section>
            <h2 id="orders">4. Placing an order</h2>
<p>When you place an order, you are making an offer to buy. We accept it when we confirm it to you: after we verify your payment, or, for cash on delivery, when we confirm the order with you. We may refuse or cancel an order, for example because of stock problems, a pricing error, an address we cannot deliver to, or suspected fraud. If we cancel an order you already paid for, we will refund you in full.</p>
        </section>
        <section>
            <h2 id="payment">5. Payment</h2>
<p>We accept:</p>
<ul>
<li><strong>Bakong KHQR</strong> and other Cambodian mobile banking apps, such as ABA, ACLEDA, Canadia, and Wing. After you pay, upload your transfer slip so we can check the payment. We confirm your order once the payment is verified.</li>
<li><strong>Cash on delivery.</strong> You pay the courier in cash when you receive your order. Please have the exact amount ready.</li>
</ul>
<p>You must pay the full amount shown at checkout, including delivery. If a payment cannot be verified, we may hold or cancel the order.</p>
        </section>
        <section>
            <h2 id="delivery">6. Delivery</h2>
<ul>
<li>We deliver within Phnom Penh. If your address is outside our delivery area, we will tell you.</li>
<li>Delivery costs $1.50. Delivery is free on orders over $15.</li>
<li>Delivery times are estimates, not guarantees. Traffic, weather, and courier availability can cause delays.</li>
<li>Please give a correct address and a phone or Telegram contact that you can answer. If the courier cannot reach you or deliver, we may cancel the order or ask you to pay again for a second delivery attempt.</li>
<li>Check your order when it arrives. Tell us right away about anything that looks wrong.</li>
</ul>
        </section>
        <section>
            <h2 id="returns">7. Returns, refunds, and cancellations</h2>
<!-- EDIT: this section is a starting point. Change the 48 hours and the rules below to match how you really run returns. -->
<h3>Damaged, faulty, or wrong item</h3>
<p>If your item arrives damaged, faulty, or is not what you ordered, message us on Telegram within 48 hours of delivery with your order number and clear photos. We will replace the item or refund you, at our choice and where the item is in stock.</p>
<h3>Changed your mind</h3>
<p>We do not guarantee returns for a change of mind. If you want to ask, contact us within 48 hours of delivery. Items must be unused, in their original condition and packaging. We decide each request, and you pay the delivery cost of sending the item back. For hygiene reasons, we cannot take back earrings, hair accessories, or sunglasses once worn or used.</p>
<h3>Cancelling an order</h3>
<p>You can cancel an order before it is dispatched by messaging us on Telegram. Once an order is out for delivery, it cannot be cancelled.</p>
<h3>How refunds are paid</h3>
<p>We refund to the same account or method you paid with, or by another method we agree with you. For cash on delivery orders, we arrange the refund with you directly. We refund as soon as we reasonably can after we approve it.</p>
<p>Nothing in this section limits your rights under Cambodian consumer law.</p>
        </section>
        <section>
            <h2 id="acceptable-use">8. Acceptable use</h2>
<p>When you use the site, you agree not to:</p>
<ul>
<li>give false information or use someone else's payment details or identity;</li>
<li>upload a fake or altered transfer slip;</li>
<li>try to break into, overload, or interfere with the site or other users' accounts;</li>
<li>copy or scrape the site, or use it for anything unlawful.</li>
</ul>
        </section>
        <section>
            <h2 id="intellectual-property">9. Intellectual property</h2>
<p>The PsaOnline name, logo, site design, text, and our own photos belong to us or our licensors. You may not copy or reuse them without our written permission, except for normal personal use of the site.</p>
        </section>
        <section>
            <h2 id="liability">10. Our responsibility</h2>
<p>We take care to run the shop properly, but the site and products are provided on the terms set out here. To the extent the law allows, we are not responsible for indirect or consequential losses, or for delays or failures caused by things outside our control, such as bank or network outages, courier problems, or severe weather.</p>
<p>Our total responsibility to you for any order will not be more than the amount you paid for that order. Nothing in these terms limits responsibility that cannot be limited by law, or your rights under Cambodian consumer law.</p>
        </section>
        <section>
            <h2 id="governing-law">11. Governing law</h2>
<p>These terms are governed by the laws of the Kingdom of Cambodia. If we cannot settle a dispute by talking it through, it will be decided by the courts of Cambodia.</p>
        </section>
        <section>
            <h2 id="changes">12. Changes to these terms</h2>
<p>We may update these terms from time to time. The "Last updated" date at the top shows when they last changed. Changes apply to orders placed after the update. If you keep using the site after a change, you accept the new terms.</p>
        </section>
        <section>
            <h2 id="contact">13. Contact us</h2>
<p>Questions about these terms or an order?</p>
<p>Message us on Telegram at <a href="https://t.me/psaonline_support" target="_blank" rel="noopener noreferrer">@@psaonline_support</a>.</p>
        </section>

        <p style="margin-top:40px"><a class="btn" href="{{ route('products.index') }}">Back to shop</a></p>
    </div>
</div>
@endsection