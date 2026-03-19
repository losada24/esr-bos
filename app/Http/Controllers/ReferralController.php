<?php

namespace App\Http\Controllers;

use App\Enum\ContactSourceEnum;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReferralController extends Controller
{
    public function search(Request $request)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'type' => [
                'required',
                'string',
                Rule::in(
                    ContactSourceEnum::ESW_REFER->value,
                    ContactSourceEnum::ESR_REFER->value,
                ),
            ],
        ]);

        $term = trim($data['q']);
        if ($term === '') {
            return response()->json(['data' => []]);
        }

        $digits = preg_replace('/\D+/', '', $term) ?? '';
        $like = '%' . $term . '%';

        $referrals = Referral::query()
            ->select('id', 'name', 'phone', 'email', 'type')
            ->where('type', $data['type'])
            ->where(function ($query) use ($like, $digits) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);

                if ($digits !== '') {
                    $query->orWhere('phone', 'like', '%' . $digits . '%');
                } else {
                    $query->orWhere('phone', 'like', $like);
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json(['data' => $referrals]);
    }
}
