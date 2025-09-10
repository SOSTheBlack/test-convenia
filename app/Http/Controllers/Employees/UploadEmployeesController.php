<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UploadEmployeesController extends Controller
{
    /**
     * Upload de funcionários
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        dd($request->all(), $request->file('employees'), $request);

        return response()->json([
            'message' => 'Funcionário cadastrado com sucesso',
            'employee' => $employee
        ], Response::HTTP_CREATED);
    }
}


