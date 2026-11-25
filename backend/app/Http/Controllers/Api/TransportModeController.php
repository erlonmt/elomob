<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransportModeResource;
use App\Models\TransportMode;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransportModeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TransportModeResource::collection(TransportMode::orderBy('id')->get());
    }
}
