<?php

use Spatie\FastPaginate\Tests\TestCase;

const TOTAL_USERS = 29;

uses(TestCase::class)->in(__DIR__);

// The tests in this directory assert on the exact SQL that gets generated. Every database
// has its own flavour of that, so we only run them on SQLite. What the other databases
// need to prove is that the results are right, which the tests one level up cover.
uses()
    ->beforeEach(function () {
        if ($this->driver() !== 'sqlite') {
            $this->markTestSkipped('These tests assert on SQLite flavoured SQL.');
        }
    })
    ->in('Sql');
