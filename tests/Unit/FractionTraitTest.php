<?php

test('check 8.659', function () {

    $fraction = new class {
        use App\Traits\Fractions;
    };

    expect($fraction->getNumberWithFraction(8.659))->toBe('8 11/16');
});

test('check 3.090', function () {

    $fraction = new class {
        use App\Traits\Fractions;
    };

    expect($fraction->getNumberWithFraction(3.090))->toBe('3 1/16');
});

test('check 6.529', function () {

    $fraction = new class {
        use App\Traits\Fractions;
    };

    expect($fraction->getNumberWithFraction(6.529))->toBe('6 1/2');
});


test('check 6.454', function () {

    $fraction = new class {
        use App\Traits\Fractions;
    };

    expect($fraction->getNumberWithFraction(6.454))->toBe('6 7/16');
});
