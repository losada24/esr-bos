<?php

test('Test Balance Model 50x60', function () {
    $singleHuntProduct = new \App\Products\SingleHuntProduct(50, 60, 'WHITE', 'CLEAR', true);
    $balanceData = $singleHuntProduct->getBalancesBySize();

    expect($balanceData[2])->toBe("26-13");
    expect($balanceData[3])->toBe("705512613N2");
});

test('Test Balance Model 53x65', function () {
    $singleHuntProduct = new \App\Products\SingleHuntProduct(53, 65, 'WHITE', 'CLEAR', true);
    $balanceData = $singleHuntProduct->getBalancesBySize();

    expect($balanceData[2])->toBe("29-15");
    expect($balanceData[3])->toBe("705512915N2");
});

test('Test Balance Model 43x67', function () {
    $singleHuntProduct = new \App\Products\SingleHuntProduct(43, 67, 'WHITE', 'CLEAR', true);
    $balanceData = $singleHuntProduct->getBalancesBySize();

    expect($balanceData[2])->toBe("30-12");
    expect($balanceData[3])->toBe("705513012N2");
});

test('Test Balance Model 37x45 1/2', function () {
    $singleHuntProduct = new \App\Products\SingleHuntProduct(37, 45.5, 'WHITE', 'CLEAR', true);
    $balanceData = $singleHuntProduct->getBalancesBySize();

    expect($balanceData[2])->toBe("19-5");
    expect($balanceData[3])->toBe("705511905ND");
});
