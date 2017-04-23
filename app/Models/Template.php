<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Template
{
    public function findTemplateByTemplateView($view)
    {
        $results = DB::table('template')
            ->select('template_view')->first();

        return $results;
    }
}
