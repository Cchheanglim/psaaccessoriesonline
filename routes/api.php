<?php

// The storefront's JSON API lives in routes/web.php under /api (it uses the session cookie and CSRF).
// The old /api/products and /api/orders/{number}/status routes were removed in the 2026-10 database
// redesign: nothing used them, and the status route showed any order's status without a login.
