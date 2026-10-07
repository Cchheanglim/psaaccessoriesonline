<?php

namespace Tests;

use App\Models\LoyaltyTier;
use App\Models\OrderStatus;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Lookups kept for one request (role, permission and status ids, tiers) start fresh in every test.
        Role::flushCache();
        Permission::flushCache();
        OrderStatus::flushCache();
        LoyaltyTier::flushCache();
    }
}
