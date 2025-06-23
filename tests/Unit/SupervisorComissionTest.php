<?php

test('check 24100', function () {

    $fraction = new class {
        use App\Traits\ComissionSupervisor;
    };

    $comissions = $fraction->ComissionSupervisor(24100);
    $amount = array_sum(array_column($comissions, 'amount'));
    expect($amount)->toBe(72.3);
});

test('check 250000', function () {

  $fraction = new class {
      use App\Traits\ComissionSupervisor;
  };

  $comissions = $fraction->ComissionSupervisor(250000);
  $amount = array_sum(array_column($comissions, 'amount'));
  expect($amount)->toBe(475.0);
});
