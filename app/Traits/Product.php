<?php

namespace App\Traits;

trait Product {

  public function getCompanyMockup(): int {
    $companyMockup = auth()->user()->company->mockup;
    return $companyMockup ?? 0 ;
  }

  public function getCompanyPromotion(): int {
    $companyMockup = auth()->user()->company->promotion;
    return $companyMockup ?? 0 ;
  }
}
