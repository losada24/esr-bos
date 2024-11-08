<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Permit extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'user_id',
    'order_id',
    'permit_number',
    'reylos_reviewed',
    // 'eng_received',
    'eng_reviewed',
    'submitted',
    'permit_fee_paid',
    'pick_up_permit',
    'approval_date',
    'closed_permit',
    'additional_process',
    'permit_id',
    'notes',
  ];

  protected $appends = [
    'days_to_submit',
    'days_to_reviewed',
  ];

  protected $dates = [
    'reylos_reviewed',
    'eng_received',
    'eng_reviewed',
    'submitted',
    'permit_fee_paid',
    'pick_up_permit',
    'approval_date',
  ];

  public function getDaysToSubmitAttribute() {
    $reylosReviewed = Carbon::createFromFormat('Y-m-d H:i:s', $this->getRawOriginal('reylos_reviewed'));
    $submitted = $this->submitted != null ? Carbon::createFromFormat('Y-m-d H:i:s', $this->getRawOriginal('submitted')) : Carbon::now();

    return $submitted->diffInDays($reylosReviewed);
  }

  public function getDaysToReviewedAttribute() {
    $reylosReviewed = Carbon::createFromFormat('Y-m-d H:i:s', $this->getRawOriginal('reylos_reviewed'));
    $reviewed = $this->eng_reviewed != null ? Carbon::createFromFormat('Y-m-d H:i:s', $this->getRawOriginal('eng_reviewed')) : Carbon::now();

    return $reviewed->diffInDays($reylosReviewed);
  }

  public function getReylosReviewedAttribute( $value ) {
    if ($value != null) {
        try {
          $entryDate = Carbon::createFromFormat('Y-m-d H:i:s', $value);
        } catch (\Exception $e) {
          $entryDate = Carbon::createFromFormat('Y-m-d', $value);
        }
        
        return $entryDate->format('m/d/Y');
    }
  }

  public function getEngReceivedAttribute( $value ) {
    if ($value != null) {      
      try {
        $entryDate = Carbon::createFromFormat('Y-m-d H:i:s', $value);
      } catch (\Exception $e) {
        $entryDate = Carbon::createFromFormat('Y-m-d', $value);
      }
      
      return $entryDate->format('m/d/Y');
    }
  }

  public function getEngReviewedAttribute( $value ) {
    if ($value != null) {
        try {
          $entryDate = Carbon::createFromFormat('Y-m-d H:i:s', $value);
        } catch (\Exception $e) {
          $entryDate = Carbon::createFromFormat('Y-m-d', $value);
        }
        
        return $entryDate->format('m/d/Y');
    }
  }

  public function getSubmittedAttribute( $value ) {
    if ($value != null) {
      try {
        $entryDate = Carbon::createFromFormat('Y-m-d H:i:s', $value);
      } catch (\Exception $e) {
        $entryDate = Carbon::createFromFormat('Y-m-d', $value);
      }
      
      return $entryDate->format('m/d/Y');
    }
  }

  public function getDrawingProjectAttribute( $value ) {
    if ($value != null) {
      try {
        $entryDate = Carbon::createFromFormat('Y-m-d H:i:s', $value);
      } catch (\Exception $e) {
        $entryDate = Carbon::createFromFormat('Y-m-d', $value);
      }
      
      return $entryDate->format('m/d/Y');
    }
  }

  public function getPermitFeePaidAttribute( $value ) {
    if ($value != null) {
      try {
        $entryDate = Carbon::createFromFormat('Y-m-d H:i:s', $value);
      } catch (\Exception $e) {
        $entryDate = Carbon::createFromFormat('Y-m-d', $value);
      }
      
      return $entryDate->format('m/d/Y');
    }
  }

  public function getPickUpPermitAttribute( $value ) {
    if ($value != null) {
      try {
        $entryDate = Carbon::createFromFormat('Y-m-d H:i:s', $value);
      } catch (\Exception $e) {
        $entryDate = Carbon::createFromFormat('Y-m-d', $value);
      }
      
      return $entryDate->format('m/d/Y');
    }
  }

  public function scopeFilter($query, array $filters)
  {
      $query->when($filters['search'] ?? null, function ($query, $search) {
        $query->whereHas('order.client', function ($query) use ($search) {
          $query->where(DB::raw("CONCAT(name, ' ', email, ' ',phone)"), 'like', '%'.$search.'%');
        })->orWhereHas('order', function ($query) use ($search) {
          $query->where('address', 'like', '%'.$search.'%');
        });
      })
      ->when($filters['status'] ?? null, function ($query, $status) {
        switch ($status) {
          case 'DELAYED_REVIEW':
              $query->where(function($subquery) {
                  $subquery->where('pick_up_permit', null)
                    ->whereRaw('DATEDIFF(IFNULL(eng_reviewed, CURDATE()), reylos_reviewed) > 5');   
              })/* ->orWhereHas('aditionalProcess', function ($subquery) {
                $subquery->where('pick_up_permit', null)
                  ->whereRaw('DATEDIFF(IFNULL(eng_received, CURDATE()), reylos_reviewed) > 5');   
              })*/;
            break;
          case 'DELAYED_SUBMITED':
            $query->where(function($subquery) {
                $subquery->where('pick_up_permit', null)
                  ->whereRaw('DATEDIFF(IFNULL(submitted, CURDATE()), reylos_reviewed) > 15');   
            })->orWhereHas('aditionalProcess', function ($subquery) {
              $subquery->where('pick_up_permit', null)
                ->whereRaw('DATEDIFF(IFNULL(submitted, CURDATE()), reylos_reviewed) > 15');   
            });
            break;
        }
      });
  }

  /**
   * Get the client that owns the order.
   */
  public function order()
  {
      return $this->belongsTo(Order::class);
  }

  /**
   * Get the product that owns the order.
   */
  public function user()
  {
      return $this->belongsTo(User::class);
  }

  public function permitsSnapshots() 
  {
      return $this->hasMany(PermitSnapshot::class);
  }

  public function aditionalProcess() 
  {
      return $this->hasMany(Permit::class);
  }
}
