<?php

namespace App\Http\Controllers;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\CreateCompany;
use App\Actions\UpdateCompany;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Enum\States;
use App\Http\Resources\CompanyCollection;
use App\Http\Resources\CompanyResource;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inertia::render('Company/Index', [
          'companies' => new CompanyCollection(
            Company::filter($request->only(['text']))
            ->orderBy('name')
            ->paginate()
            ->withQueryString()
          )
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Company/Create', [
          'states' => array_values(States::$USA_STATES),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCompanyRequest $storeCompanyRequest, CreateCompany $createCompany)
    {
        $createCompany->handle($storeCompanyRequest);
        return redirect()->route('company.index')
          ->with('success', 'Company created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        CompanyResource::withoutWrapping();
        return Inertia::render('Company/Edit', [
          'company' => new CompanyResource($company),
          'states' => array_values(States::$USA_STATES),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCompanyRequest $updateCompanyRequest, UpdateCompany $updateCompany, Company $company)
    {
        $updateCompany->handle($updateCompanyRequest, $company);
        return redirect()->route('company.index')
          ->with('success', 'Company updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        $company->delete();
        return redirect()
          ->back()
          ->with('success', 'Company deleted successfully.');
    }

    public function profile()
    {
      $company = Company::where('id', auth()->user()->company_id)->first();
      CompanyResource::withoutWrapping();
      return Inertia::render('Company/Profile', [
        'company' => new CompanyResource($company),
        'states' => array_values(States::$USA_STATES),
      ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(UpdateCompanyRequest $updateCompanyRequest, UpdateCompany $updateUser, Company $company)
    {
        $updateUser->handle($updateCompanyRequest, $company);
        return redirect()->route('company.profile')
          ->with('success', 'Company updated successfully.');
    }
}
