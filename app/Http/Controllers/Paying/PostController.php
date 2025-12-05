<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        return Post::with('department')->paginate(100);
    }

    public function show($id)
    {
        return Post::with('department')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_id'=>'required|exists:departments,id',
            'name'=>'required|string',
            'description'=>'nullable|string',
        ]);

        $post = Post::firstOrNew([
            'id' => $request->input('id')
        ]);

        $post->fill($request->only([
            'department_id',
            'name',
            'description',
        ]));
        $post->save();

        return $this->show($post->id);
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        return $post->delete();
    }
}
