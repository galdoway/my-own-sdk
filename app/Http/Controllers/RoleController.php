<?php

namespace App\Http\Controllers;

use App\SDKs\DummyJson\DummyJsonClient;
use App\SDKs\KeycloakAdmin\KeycloakAdminClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct(private readonly KeycloakAdminClient  $client){}

    /**
     * Display a listing of the resource.
     * @throws ConnectionException
     */
    public function index(): JsonResponse
    {
        $response = $this->client->roles()->getAllClientRoles('3c5165ae-6898-44e2-816c-24f377781588');
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
