<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatBotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $userMessage = $request->message;

        $openaiKey = env('OPENAI_API_KEY'); // your OpenAI key in .env

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $openaiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.7,
            ]);

            $data = $response->json();

            if (isset($data['choices'][0]['message']['content'])) {
                $reply = $data['choices'][0]['message']['content'];
            } else {
                $reply = "🤖 No reply from OpenAI.";
            }

            return response()->json(['reply' => $reply]);

        } catch (\Exception $e) {
            return response()->json(['reply' => '❌ Error: ' . $e->getMessage()], 500);
        }
    }
}
