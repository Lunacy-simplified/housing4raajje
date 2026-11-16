<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** Branch of the signed-in staff member. Staff only ever see their own branch's records. */
    protected function branchId(): int
    {
        return auth()->user()->branchId() ?? abort(403, 'Your account is not assigned to a branch.');
    }

    /** 404 unless the record belongs to the signed-in staff member's branch. */
    protected function inBranch(?int $branchId): void
    {
        abort_unless($branchId === $this->branchId(), 404);
    }
}
