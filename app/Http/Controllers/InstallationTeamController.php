<?php

namespace App\Http\Controllers;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\CreateCompany;
use App\Actions\CreateInstallationTeam;
use App\Actions\UpdateCompany;
use App\Actions\UpdateInstallationTeam;
use App\Enum\RoleEnum;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Enum\States;
use App\Http\Requests\StoreInstallationTeamRequest;
use App\Http\Requests\UpdateInstallationTeamRequest;
use App\Http\Resources\CompanyCollection;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\InstallationTeamCollection;
use App\Http\Resources\InstallationTeamResource;
use App\Models\InstallationTeam;
use App\Models\TravelCost;
use App\Models\TypeOfHousing;
use App\Models\User;
use Doctrine\DBAL\Types\Type;

class InstallationTeamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inertia::render('InstallationTeam/Index', [
          'installation_teams' => new InstallationTeamCollection(
            InstallationTeam::filter($request->only(['text']))
            ->orderBy('updated_at', 'desc')
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
        return Inertia::render('InstallationTeam/Create', [
          'users' => User::role(RoleEnum::INSTALLER->value)->get(),
          'type_of_housings' => TypeOfHousing::orderBy('name')->get(),
          'travel_costs' => TravelCost::orderBy('name')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInstallationTeamRequest $storeInstallationTeamRequest, CreateInstallationTeam $createInstallationTeam)
    {
        $createInstallationTeam->handle($storeInstallationTeamRequest);
        return redirect()->route('installation_team.index')
          ->with('success', 'Installation Team created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(InstallationTeam $installationTeam)
    {
        InstallationTeamResource::withoutWrapping();
        return Inertia::render('InstallationTeam/Edit', [
          'installation_team' => new InstallationTeamResource($installationTeam),
          'users' => User::role(RoleEnum::INSTALLER->value)->get(),
          'type_of_housings' => TypeOfHousing::orderBy('name')->get(),
          'travel_costs' => TravelCost::orderBy('name')->get()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInstallationTeamRequest $updateInstallationTeamRequest, UpdateInstallationTeam $updateInstallationTeam, InstallationTeam $installationTeam)
    {
        $updateInstallationTeam->handle($updateInstallationTeamRequest, $installationTeam);
        return redirect()->route('installation_team.index')
          ->with('success', 'Installation Team updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(InstallationTeam $installationTeam)
    {
        $installationTeam->delete();
        return redirect()
          ->back()
          ->with('success', 'Installation Team deleted successfully.');
    }

    public function profile()
    {
      /* $company = Company::where('id', auth()->user()->company_id)->first();
      CompanyResource::withoutWrapping();
      return Inertia::render('InstallationTeam/Profile', [
        'company' => new CompanyResource($company),
        'states' => array_values(States::$USA_STATES),
      ]); */
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(/*UpdateCompanyRequest $updateCompanyRequest, UpdateCompany $updateUser, Company $company*/)
    {
        /* $updateUser->handle($updateCompanyRequest, $company);
        return redirect()->route('company.profile')
          ->with('success', 'Company updated successfully.'); */
    }
}
