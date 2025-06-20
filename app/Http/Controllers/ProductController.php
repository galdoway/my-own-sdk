<?php

namespace App\Http\Controllers;

use App\SDKs\DummyJson\DummyJsonClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function __construct(private readonly DummyJsonCLient $client){}
    /**
     * Display a listing of the resource.
     * @throws ConnectionException
     */
    public function index(): JsonResponse
    {
        $response = $this->client->products()->all();
        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
