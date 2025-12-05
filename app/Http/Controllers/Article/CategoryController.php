<?php

namespace App\Http\Controllers\Article;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::paginate(100);
    }

    public function show($id)
    {
        return Category::findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required|string',
            'parent_id'=>'nullable|exists:categories,id',
        ]);

        $category = Category::firstOrNew([
            'id' => $request->input('id')
        ]);

        $category->fill($request->only([
            'name',
            'parent_id',
        ]));
        $category->save();

        return $this->show($category->id);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        return $category->delete();
    }
}
