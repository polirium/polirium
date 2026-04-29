<?php

use Illuminate\Support\Facades\DB;
use Polirium\Core\Base\Http\Models\Branch\Branch;

if (! function_exists('user_branch')) {
    function user_branch($branch_id = null)
    {
        if ($branch_id) {
            DB::table('user_branches')->where('user_id', auth()->id())->update(['active' => 0]);
            Branch::findOrFail($branch_id)->users()->sync([auth()->id() => ['active' => 1]]);
        }

        return auth()->user()?->branches()->where('active', 1)->first()?->id;
    }
}
