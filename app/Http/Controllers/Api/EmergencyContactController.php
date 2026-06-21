<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmergencyContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmergencyContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->apiResponse(
            true,
            'Contacts retrieved',
            $request->user()->emergencyContacts
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'relationship' => ['nullable', 'string', 'max:100'],
        ]);

        $contact = $request->user()->emergencyContacts()->create($data);

        return $this->apiResponse(true, 'Contact added', $contact, 201);
    }

    public function destroy(Request $request, EmergencyContact $emergencyContact): JsonResponse
    {
        if ($emergencyContact->rider_id !== $request->user()->id) {
            return $this->apiResponse(false, 'Unauthorized', null, 403);
        }

        $emergencyContact->delete();

        return $this->apiResponse(true, 'Contact removed');
    }
}
