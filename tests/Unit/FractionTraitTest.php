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

test('check 31.995', function () {

    $fraction = new class {
        use App\Traits\Fractions;
    };

    expect($fraction->getNumberWithFraction(31.995))->toBe(32);
});

test('check 35.001', function () {

    $fraction = new class {
        use App\Traits\Fractions;
    };

    expect($fraction->getNumberWithFraction(35.001))->toBe('35 1/16');
});

test('check 40.000', function () {

    $fraction = new class {
        use App\Traits\Fractions;
    };

    expect($fraction->getNumberWithFraction(40.000))->toBe(40);
});

test('check decimal part 001', function () {

    $fraction = new class {
        use App\Traits\Fractions;
    };

    expect($fraction->getDecimalPart(35.001))->toBe(0.001);
});

test('Test rounded 0.0009', function () {

    $fraction = new class {
        use App\Traits\Fractions;
    };

    expect(round(0.0009, 3))->toBe(0.001);
});

test('Test rounded 0.0004', function () {

    $fraction = new class {
        use App\Traits\Fractions;
    };

    expect(round(0.0004, 3))->toBe(0.000);
});

test('Test rounded 0.126', function () {

    $fraction = new class {
        use App\Traits\Fractions;
    };

    expect(round(0.126, 3))->toBe(0.126);
});
