<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI;

class AiController extends Controller
{
    public function diagnose(Request $request)
    {
        $image = $request->file('image');
        $path = $image->store('crop_images', 'public');

        $client = OpenAI::client(env('OPENAI_API_KEY'));

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Diagnose this crop disease and suggest treatment.'
                ]
            ],
        ]);

        return response()->json([
            'image' => $path,
            'diagnosis' => $response->choices[0]->message->content
        ]);
    }
}
