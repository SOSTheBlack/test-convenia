<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Imports\EmployeesImport;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;

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
        $file = $request->file('employees');

        if (!$file) {
            return response()->json([
                'message' => 'Arquivo não encontrado'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            Excel::import(new EmployeesImport, $file, null, \Maatwebsite\Excel\Excel::CSV);

            return response()->json([
                'message' => 'Funcionários importados com sucesso'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao importar funcionários',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}


