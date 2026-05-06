// app/Http/Controllers/Api/Admin/AdminRoomController.php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        $query = Room::with('roomType');

        if (!$admin->isSuperAdmin()) {
            $query->where('facility_id', $admin->facility_id);
        } elseif ($request->facility_id) {
            $query->where('facility_id', $request->facility_id);
        }

        $rooms = $query->get();

        return response()->json([
            'success' => true,
            'data' => $rooms->map(fn($r) => [
                'roomId' => $r->room_id,
                'roomNumber' => $r->room_number,
                'floor' => $r->floor,
                'status' => $r->status,
                'roomTypeName' => $r->roomType->name,
                'isActive' => $r->is_active,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();

        $request->validate([
            'room_type_id' => 'required|uuid|exists:room_types,room_type_id',
            'facility_id' => 'required|uuid|exists:facilities,facility_id',
            'room_number' => 'required|string|max:10',
            'floor' => 'nullable|integer',
        ]);

        if (!$admin->canAccessFacility($request->facility_id)) {
            return response()->json(['success' => false, 'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません']], 403);
        }

        $totalRooms = Room::where('facility_id', $request->facility_id)->count();
        if ($totalRooms >= config('hotel.room_max_count', 100)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'ROOM_LIMIT_EXCEEDED', 'message' => '客室数の上限（100室）に達しています'],
            ], 422);
        }

        $room = Room::create([
            'room_type_id' => $request->room_type_id,
            'facility_id' => $request->facility_id,
            'room_number' => $request->room_number,
            'floor' => $request->floor,
            'status' => 'AVAILABLE',
            'notes' => $request->notes,
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'data' => ['roomId' => $room->room_id]], 201);
    }

    public function update(Request $request, string $roomId): JsonResponse
    {
        $room = Room::findOrFail($roomId);
        $admin = auth('admin')->user();

        if (!$admin->canAccessFacility($room->facility_id)) {
            return response()->json(['success' => false, 'error' => ['code' => 'PERMISSION_DENIED', 'message' => '権限がありません']], 403);
        }

        $room->update(array_filter($request->only(['status', 'notes', 'is_active']), fn($v) => $v !== null));

        return response()->json(['success' => true, 'data' => ['roomId' => $roomId]]);
    }
}