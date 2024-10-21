<?php

namespace App\Http\Controllers;

use App\Events\PusherBroadcast;
use Illuminate\Http\Request;

class PusherController extends Controller
{
    public function index()
    {
        return view('test-chat.index');
    }

    public function broadcast(Request $request)
    {
        $message = $request->get('message');
        if (is_array($message)) {
            $message = json_encode($message);
        }

        broadcast(new PusherBroadcast($message))->toOthers();

        return response()->json([
            'status' => true,
            'message' => $message,
        ], 200);
    }

    public function receive(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => $request->get('message'),
        ], 200);
    }
}
