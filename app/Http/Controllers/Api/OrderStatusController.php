<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use App\Http\Requests\OrderStatusRequest;

class OrderStatusController extends Controller
{
    public function index(Request $request)
    {
        $orderStatuses = OrderStatus::orderBy('name')->get();
        return response()->json($orderStatuses);
    }

    public function store(OrderStatusRequest $request)
    {
        $orderStatus = OrderStatus::create($request->validated());
        return response()->json($orderStatus, 201);
    }

    public function show(OrderStatus $orderStatus)
    {
        return response()->json($orderStatus);
    }

    public function update(OrderStatusRequest $request, OrderStatus $orderStatus)
    {
        $orderStatus->update($request->validated());
        return response()->json($orderStatus);
    }

    public function destroy(OrderStatus $orderStatus)
    {
        $orderStatus->delete();
        return response()->json(['message' => 'Order status deleted successfully']);
    }
    
    public function toggleAutoSend(Request $request, $id)
    {
        $orderStatus = OrderStatus::findOrFail($id);
        $orderStatus->auto_send = !$orderStatus->auto_send;
        $orderStatus->save();
        return response()->json($orderStatus);
    }
}