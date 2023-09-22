<?php

namespace App\Http\Controllers;

use App\Actions\CreateReferred;
use App\Actions\UpdateReferred;
use App\Http\Requests\StoreReferredRequest;
use App\Http\Requests\UpdateReferredRequest;
use App\Models\Referred;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Contracts\Database\Eloquent\Builder;

class ReferredController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $userId = Auth::user()->id;
        // $isAdmin = User::find($userId)->hasRole('admin');
        
        return Inertia::render('Referred/Index', [
            'referrals' => Referred::with(['user', 'referralsStatusUpdate' => function (Builder $query) {
                    $query->orderBy('created_at', 'asc');
                  }, 'referralsStatusUpdate.user'])
                  ->whereHas('user', function (Builder $query) {
                    $query->whereNull('deleted_at');
                  })
                  ->filter($request->only(['text', 'status', 'user_id']))
                  ->orderBy('created_at', 'desc')
                  ->paginate()
                  ->withQueryString(),
              'users' => User::orderBy('name')
                ->filter(['text' => $request->input('user_term')])
                ->paginate()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($reference_code)
    {
        $user = User::where('reference_code', $reference_code)->first();
        if (!$user) {
          return redirect()->route('login')
            ->with('error', 'Invalid reference code.');
        }

        return Inertia::render('Referred/Create', [
          'userId' => $user->id,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreReferredRequest $storeReferredRequest, CreateReferred $createReferred)
    {
        $createReferred->handle($storeReferredRequest);
        $user = User::where('id', $storeReferredRequest->user_id)->first();
        return redirect()->route('referred.create', ['reference_code' => $user->reference_code])
          ->with('success', 'Thank you for reaching out to us! Your information has been received and saved successfully. We\'ll be in touch shortly to assist you with your inquiry. Have a great day!');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Referred $referred)
    {
        return Inertia::render('Referred/Edit', [
          'referred' => $referred,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateReferredRequest $updateReferredRequest, UpdateReferred $updateReferred, Referred $referred)
    {
        $updateReferred->handle($updateReferredRequest, $referred);
        return redirect()->route('referred.index')
          ->with('success', 'Referred updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Referred $referred)
    {
        $referred->delete();
        return redirect()
          ->back()
          ->with('success', 'Referred deleted successfully.');
    }
}
