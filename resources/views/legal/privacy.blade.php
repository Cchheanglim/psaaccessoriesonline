@extends('legal.layout')

@section('title', 'Privacy Policy | PsaOnlineAccessories')
@section('heading', 'Privacy Policy')
@section('updated', '2 October 2026')

@section('body')
@php($contact = config('mail.from.address'))
<p>
    This policy explains what personal information PsaOnlineAccessories ("we", "us") collects when you use this
    website, why we collect it, who it is shared with and the choices you have. It applies to this website only.
</p>

<h2>What we collect</h2>
<ul>
    <li><strong>Account details</strong>, if you create an account: your name, email address, optional phone or Telegram number, and a password. Passwords are stored only as a one-way bcrypt hash; we cannot read them.</li>
    <li><strong>Order details</strong>, when you check out: the recipient name, phone or Telegram number, delivery address, any delivery notes, the items you bought and the amounts charged.</li>
    <li><strong>Payment slips</strong>: if you pay by bank transfer, the screenshot or photo of the transfer you upload. These images can show your name, bank and account details.</li>
    <li><strong>Technical data</strong>: your IP address and browser type, recorded with your session so the site can keep you signed in and protect against abuse such as repeated login attempts.</li>
</ul>
<p>We do not collect card numbers. Card payments are not accepted on this website.</p>

<h2>Why we use it</h2>
<ul>
    <li>To process, deliver and support your orders, including contacting you about delivery.</li>
    <li>To confirm bank-transfer payments by checking the slip you upload against the order total.</li>
    <li>To run your account and keep it secure.</li>
</ul>
<p>We do not sell your information and we do not use it for advertising.</p>

<h2>Cookies</h2>
<p>
    We use a small number of cookies that the site needs to work: a session cookie that keeps your shopping bag and
    sign-in, and a security token that protects forms from cross-site request forgery. If you tick "Keep me signed in",
    a longer-lived sign-in cookie is also set. We do not use analytics or advertising cookies. Your currency and theme
    choices are kept in your browser's local storage and never sent to us.
</p>

<h2>Who we share it with</h2>
<ul>
    <li><strong>Hosting and database providers.</strong> The website runs on Render and data is stored in a PostgreSQL database hosted by Supabase. They process data on our behalf to provide those services.</li>
    <li><strong>Couriers.</strong> To deliver your order we give the courier your name, phone number, address and delivery notes.</li>
    <li><strong>QR code generation.</strong> On the payment page, your browser requests the payment QR image from api.qrserver.com. That request includes the order number and the amount due, but not your name, phone or address.</li>
    <li><strong>Fonts and images.</strong> Your browser loads typefaces from Google Fonts and some product photos from images.unsplash.com. Those services receive your IP address as part of the request.</li>
    <li><strong>Authorities</strong>, if we are required to by law.</li>
</ul>

<h2>How long we keep it</h2>
<p>
    We keep order records, including payment slips, for as long as we need them to handle deliveries, returns and our
    own accounting. Account details are kept until you ask us to delete your account. Session records expire on their own.
</p>

<h2>Security</h2>
<p>
    The website is served over HTTPS, the database connection is encrypted, passwords are hashed, payment slips are
    stored privately and shown only to you and our staff, and access to the admin area is restricted to staff accounts.
    No system is perfectly secure, so please tell us straight away if you think your account has been misused.
</p>

<h2>Your choices</h2>
<p>
    You can ask us for a copy of the personal information we hold about you, ask us to correct it, or ask us to delete
    your account and the information linked to it, except where we must keep order records. Contact us at the address
    below and we will reply within 30 days.
</p>

<h2>Children</h2>
<p>This website is not intended for children under 13, and we do not knowingly collect their information.</p>

<h2>Changes to this policy</h2>
<p>If we change this policy we will update the date at the top of this page.</p>

<h2>Contact</h2>
<p>
    Email <a href="mailto:{{ $contact }}">{{ $contact }}</a> or message us on Telegram at
    <a href="https://t.me/psaonline_support" target="_blank" rel="noopener noreferrer">@psaonline_support</a>.
</p>
@endsection
