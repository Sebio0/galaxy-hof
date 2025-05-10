<?php

namespace App\Http\Controllers;

use Spatie\RouteAttributes\Attributes\Get;

class RankingController extends Controller
{
    #[Get('/', name: 'ranking.index')]
    public function index(){
        return view('ranking.index');
    }
}
