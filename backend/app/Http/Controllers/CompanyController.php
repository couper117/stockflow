<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Services\CompanyService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

// Thin controller: validate (FormRequest) -> call service -> return envelope.
class CompanyController extends Controller
{
    public function __construct(private CompanyService $companies) {}

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = $this->companies->register($request->validated());

        return ApiResponse::success(new CompanyResource($company), __('companies.created'), 201);
    }
}
