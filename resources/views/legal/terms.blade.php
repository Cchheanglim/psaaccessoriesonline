@extends('legal.layout')

@section('title', 'Terms and Conditions | PsaOnlineAccessories')
@section('heading', 'Terms and Conditions')
@section('updated', '2 October 2026')

@section('body')
@php($contact = config('mail.from.address'))
<p>
    These terms apply when you browse or buy from PsaOnlineAccessories ("we", "us"). By placing an order or creating an
    account you agree to them. Please also read our <a href="{{ route('privacy') }}">Privacy Policy</a>.
</p>

<h2>Products and prices</h2>
<ul>
    <li>Prices are listed in US dollars, with the riel amount converted at a fixed rate of 1 USD = 4,100 KHR. The rate shown at checkout is the one that applies to your order.</li>
    <li>We describe and photograph products as accurately as we can. Colours can look slightly different on different screens.</li>
    <li>If we list a price in error, we will contact you before dispatching the order and you may cancel it for a full refund of anything you have paid.</li>
</ul>

<h2>Orders</h2>
<p>
    Placing an order is an offer to buy. The contract is formed when we confirm your payment (for bank transfer) or
    dispatch your parcel (for cash on delivery). We may decline or cancel an order, for example if an item is out of
    stock or a payment cannot be confirmed. If we cancel after you have paid, we refund you in full.
</p>

<h2>Payment</h2>
<ul>
    <li><strong>Bakong KHQR, ABA Pay and ACLEDA:</strong> pay the exact total shown, then upload your transfer slip on the order page. We check each slip by hand before your order is packed.</li>
    <li><strong>Cash on delivery:</strong> available in Phnom Penh. Please have the exact amount ready for the courier.</li>
    <li>Card payments are not accepted at this time.</li>
</ul>

<h2>Delivery</h2>
<p>
    We deliver within Phnom Penh for a flat fee of $1.50, shown at checkout. Please make sure your phone number and
    address are correct; the courier will contact you on the number you give. If a delivery fails because the details
    were wrong or nobody was available, we will contact you to rearrange it, and a second delivery fee may apply.
</p>

<h2>Returns and faulty items</h2>
<p>
    If an item arrives damaged, faulty or different from what you ordered, message us within 3 days of delivery with
    your order number and a photo, and we will replace it or refund you. For hygiene reasons, earrings and items that
    have been worn cannot be returned unless they are faulty. These terms do not affect any rights you have under
    Cambodian consumer protection law.
</p>

<h2>Your account</h2>
<p>
    Keep your password private and tell us if you think someone else has used your account. We may suspend accounts
    used for fraud or to abuse the website.
</p>

<h2>Acceptable use</h2>
<p>
    Do not attempt to interfere with the website, access other people's orders or accounts, upload anything other
    than a genuine payment slip, or use automated tools to place orders.
</p>

<h2>Liability</h2>
<p>
    Our total liability for any order is limited to the amount you paid for it, except where the law does not allow
    liability to be limited. We are not responsible for delays caused by events outside our reasonable control.
</p>

<h2>Changes</h2>
<p>
    We may update these terms. The version in force when you place an order applies to that order.
</p>

<h2>Governing law</h2>
<p>These terms are governed by the laws of the Kingdom of Cambodia.</p>

<h2>Contact</h2>
<p>
    Email <a href="mailto:{{ $contact }}">{{ $contact }}</a> or message us on Telegram at
    <a href="https://t.me/psaonline_support" target="_blank" rel="noopener noreferrer">@psaonline_support</a>.
</p>
@endsection
