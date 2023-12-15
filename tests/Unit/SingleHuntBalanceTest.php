<?php

test('Test Balance Model 50x60', function () {
    $singleHuntProduct = new \App\Products\SingleHuntProduct(50, 60, 'WHITE', 'CLEAR', true);
    $balanceData = $singleHuntProduct->getBalancesBySize();

    expect($balanceData[2])->toBe("26-6");
    expect($balanceData[3])->toBe("705512606ND");
});

test('Test Balance Model 53x65', function () {
    $singleHuntProduct = new \App\Products\SingleHuntProduct(53, 65, 'WHITE', 'CLEAR', true);
    $balanceData = $singleHuntProduct->getBalancesBySize();

    expect($balanceData[2])->toBe("29-6");
    expect($balanceData[3])->toBe("705512906ND");
});
