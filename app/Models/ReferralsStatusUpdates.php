<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ReferralsStatusUpdates extends Model
{
    use HasFactory;

    protected $table = 'referrals_status_updates';

    protected $fillable = [
      'referral_id',
      'status',
      'notes',
      'user_id'
    ];

    /*public function getCreatedAtAttribute( $value ) {
      //print_r($value);die;
      $createdAt = Carbon::parse($value);
      return $createdAt->format('m/d/Y');
    }*/

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function Referred()
    {
        return $this->belongsTo(User::class, 'referral_id');
    }


}
