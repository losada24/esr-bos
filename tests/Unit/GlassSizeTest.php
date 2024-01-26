<?php

test('Test Glass 35.50', function () {

  $product = new class {
    use App\Traits\Product;
  };

  
  expect($product->getGlassSize(35.50))->toBe(36);
});

test('Test Glass 35', function () {

  $product = new class {
    use App\Traits\Product;
  };

  expect($product->getGlassSize(36))->toBe(36);
});

test('Test Glass 22.30', function () {

  $product = new class {
    use App\Traits\Product;
  };

  expect($product->getGlassSize(22.30))->toBe(24);
});

test('Test Glass 46.70', function () {

  $product = new class {
    use App\Traits\Product;
  };

  expect($product->getGlassSize(46.70))->toBe(48);
});

test('Test Glass 51', function () {

  $product = new class {
    use App\Traits\Product;
  };

  expect($product->getGlassSize(51))->toBe(52);
});
