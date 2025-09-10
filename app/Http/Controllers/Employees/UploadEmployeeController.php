<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UploadEmployeeController extends Controller
{
    /**
     * Upload de funcionários
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'position' => 'required|string|max:100',
            'salary' => 'required|numeric',
        ]);

        $employee = Employee::create([
            'name' => $request->name,
            'email' => $request->email,
            'position' => $request->position,
            'salary' => $request->salary,
        ]);

        return response()->json([
            'message' => 'Funcionário cadastrado com sucesso',
            'employee' => $employee
        ], Response::HTTP_CREATED);
    }
}
