@extends('layouts.app')

@section('title', 'Privacy Policy | PsaOnline')

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
        <h1>Privacy Policy</h1>
        <p class="meta">Last updated: 2 October 2026</p>
        <p class="intro">This policy explains what information we collect when you shop with PsaOnline, how we use it, and the choices you have.</p>

        <nav class="toc" aria-label="Contents">
            <p>Contents</p>
            <ol>
            <li><a href="#who-we-are">Who we are</a></li>
            <li><a href="#what-we-collect">Information we collect</a></li>
            <li><a href="#how-we-use">How we use your information</a></li>
            <li><a href="#who-we-share">Who we share it with</a></li>
            <li><a href="#other-services">Other services our pages load</a></li>
            <li><a href="#browser-storage">Cookies and browser storage</a></li>
            <li><a href="#retention">How long we keep it</a></li>
            <li><a href="#your-choices">Your choices and rights</a></li>
            <li><a href="#security">Security</a></li>
            <li><a href="#children">Children</a></li>
            <li><a href="#changes">Changes to this policy</a></li>
            <li><a href="#contact">Contact us</a></li>
            </ol>
        </nav>

        <section>
            <h2 id="who-we-are">1. Who we are</h2>
<p>PsaOnline (also called Psa Accessories Online, "we", "us") is an online shop for accessories based in Phnom Penh, Cambodia. This policy explains what personal information we collect when you use our website, why we collect it, and what you can do about it.</p>
<p>By using the site or placing an order, you agree to this policy. If you do not agree, please do not use the site.</p>
        </section>
        <section>
            <h2 id="what-we-collect">2. Information we collect</h2>
<p>We collect only what we need to run the shop.</p>
<h3>Information you give us</h3>
<ul>
<li>Your full name.</li>
<li>Your Telegram username or phone number.</li>
<li>Your password, if you create an account.</li>
<li>Your delivery address, delivery notes, and the map pin you place at checkout.</li>
<li>Your order details, such as items, amounts, and the payment method you choose.</li>
<li>The transfer slip screenshot you upload to confirm a bank or KHQR payment. A slip can show your name, bank, account details, and transaction reference.</li>
<li>Anything you send us when you contact support.</li>
</ul>
<h3>Information from your browser</h3>
<ul>
<li>Your bag, saved items, preferred currency, theme (light or dark), and saved addresses and profile are kept in your own browser storage so the site works as you expect. See section 6.</li>
<li>Like every website, the services that deliver our pages may see your IP address and basic browser details. See section 5.</li>
</ul>
<p>We do not collect payment card numbers. We do not use advertising trackers or analytics tools on the shop.</p>
        </section>
        <section>
            <h2 id="how-we-use">3. How we use your information</h2>
<ul>
<li>To create and manage your account.</li>
<li>To process, confirm, and deliver your orders.</li>
<li>To check that a payment was received, including by reviewing your transfer slip.</li>
<li>To contact you about an order, for example to arrange delivery.</li>
<li>To answer questions, handle returns and refunds, and fix problems.</li>
<li>To keep records we need for accounting and legal reasons.</li>
<li>To keep the site secure and prevent fraud or misuse.</li>
</ul>
<p>We do not sell your personal information.</p>
        </section>
        <section>
            <h2 id="who-we-share">4. Who we share it with</h2>
<p>We share information only when it is needed to complete your order or when the law requires it.</p>
<ul>
<li><strong>Couriers and delivery partners:</strong> your name, phone or Telegram contact, delivery address, and delivery notes, so they can deliver to you.</li>
<li><strong>Banks and payment services:</strong> when you pay with Bakong KHQR, ABA, or another Cambodian banking app, your bank processes the payment under its own privacy rules. We only see what appears on your transfer slip.</li>
<li><strong>Authorities:</strong> if a court, regulator, or other authority legally requires us to.</li>
</ul>
        </section>
        <section>
            <h2 id="other-services">5. Other services our pages load</h2>
<p>To work, our pages load some files from other companies. When your browser loads them, those companies can see your IP address and basic browser details, under their own privacy policies.</p>
<ul>
<li>Tailwind CSS, jsDelivr, and unpkg: code libraries that style the site and power features such as the delivery map.</li>
<li>OpenStreetMap: map tiles shown at checkout.</li>
<li>Unsplash: some product and page images.</li>
<li>QR Server (api.qrserver.com): draws the payment QR code. The QR code content is sent to this service to create the image.</li>
</ul>
<p>Links to Instagram, TikTok, Facebook, X, and Telegram take you to those services. We do not control how they handle your information.</p>
        </section>
        <section>
            <h2 id="browser-storage">6. Cookies and browser storage</h2>
<p>We use your browser's local storage to remember your bag, saved items, currency, theme, and saved addresses and profile. This stays on your device. It is not used to advertise to you or follow you across other websites.</p>
<p>You can clear this data at any time in your browser settings. If you do, your bag and saved items will be emptied.</p>
        </section>
        <section>
            <h2 id="retention">7. How long we keep it</h2>
<p>We keep order and payment records for as long as we need them to fulfil your order, handle returns and disputes, and meet accounting and legal duties. After that we delete the information or remove anything that identifies you.</p>
<p>Transfer slip images are kept only as long as needed to confirm payment and resolve any dispute about it.</p>
<p>If you ask us to delete your account, we will do so, except for records we are required to keep.</p>
        </section>
        <section>
            <h2 id="your-choices">8. Your choices and rights</h2>
<p>You can ask us to:</p>
<ul>
<li>tell you what personal information we hold about you;</li>
<li>correct information that is wrong;</li>
<li>delete your account and personal information, where we are not required to keep it;</li>
<li>stop contacting you, other than about orders in progress.</li>
</ul>
<p>To make a request, contact us using the details in section 12. We may need to confirm it is really you before we act.</p>
        </section>
        <section>
            <h2 id="security">9. Security</h2>
<p>We take reasonable steps to protect your information. Please choose a password you do not use anywhere else, and keep it private. No website or storage system is completely secure, so we cannot promise absolute security.</p>
        </section>
        <section>
            <h2 id="children">10. Children</h2>
<p>The shop is not meant for children under 16. If you are under 16, please ask a parent or guardian to place orders for you. If we learn we hold information about a child without a parent's permission, we will delete it.</p>
        </section>
        <section>
            <h2 id="changes">11. Changes to this policy</h2>
<p>We may update this policy from time to time. The "Last updated" date at the top shows when it last changed. If we make a major change, we will say so on the site.</p>
        </section>
        <section>
            <h2 id="contact">12. Contact us</h2>
<p>Questions or requests about your information?</p>
<p>Message us on Telegram at <a href="https://t.me/psaonline_support" target="_blank" rel="noopener noreferrer">@@psaonline_support</a>.</p>
        </section>

        <p style="margin-top:40px"><a class="btn" href="{{ route('products.index') }}">Back to shop</a></p>
    </div>
</div>
@endsection