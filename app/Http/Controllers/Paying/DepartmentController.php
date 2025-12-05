<?php

namespace App\Http\Controllers\Paying;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return Department::paginate(100);
    }

    public function show($id)
    {
        return Department::with('partner')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'partner_id'=>'required|exists:partners,id',
            'name'=>'required|string',
            'description'=>'nullable|string',
        ]);

        $department = Department::firstOrNew([
            'id' => $request->input('id')
        ]);

        $department->fill($request->only([
            'partner_id',
            'name',
            'description',
        ]));
        $department->save();

        return $this->show($department->id);
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        return $department->delete();
    }
}
