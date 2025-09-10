<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ListEmployeesController extends Controller
{
    /**
     * Lista de funcionários paginada
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $employees = Employee::where('user_id', $request->user()->id)->paginate(10);

        return response()->json($employees, Response::HTTP_OK);
    }
}
