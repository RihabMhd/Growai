<?php

namespace App\Http\Controllers\Api\Order;

use App\Http\Controllers\Controller;
use App\Domain\Orders\Models\OrderStatus;
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

        $orderStatus->auto_send = $request->has('auto_send')
            ? (bool) $request->input('auto_send')
            : !$orderStatus->auto_send;

        $orderStatus->save();

        return response()->json($orderStatus);
    }


    public function saveTemplate(Request $request, $id)
    {
        $validated = $request->validate([
            'templates'         => ['nullable', 'array'],
            'templates.*'       => ['nullable', 'string', 'max:4096'],
            'auto_send'         => ['nullable', 'boolean'],
            'whatsapp_message'  => ['nullable', 'string', 'max:4096'],
        ]);

        $orderStatus = OrderStatus::findOrFail($id);

        if (array_key_exists('templates', $validated)) {
            $orderStatus->templates = $validated['templates'];
        }

        if (array_key_exists('auto_send', $validated)) {
            $orderStatus->auto_send = (bool) $validated['auto_send'];
        }

        if (array_key_exists('whatsapp_message', $validated)) {
            $orderStatus->whatsapp_message = $validated['whatsapp_message'];
        }

        $orderStatus->save();

        return response()->json([
            'success'      => true,
            'order_status' => $orderStatus,
        ]);
    }
}