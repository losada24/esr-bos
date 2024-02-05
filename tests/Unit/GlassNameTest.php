<?php

use App\Products\Glass;

test('Glass 3.16 EXPRESS, No privacy, No LOW E', function () {

  $glass = new Glass('EXPRESS', 'CLEAR', 'NONE', 'Clear');

  expect($glass->getGlass316())->toBe("3/16 HS CLEAR +0.09PVB t Clear +3/16 HS CLEAR (EXPRESS)");
});

test('Glass 3.16 EXPRESS, Privacy, No LOW E', function () {

  $glass = new Glass('EXPRESS', 'CLEAR', 'NONE', 'White Interlayer');

  expect($glass->getGlass316())->toBe("3/16 HS CLEAR +0.09PVB t White Interlayer +3/16 HS CLEAR (EXPRESS)");
});

test('Glass 3.16 EXPRESS, No privacy, LOW E Q366', function () {

  $glass = new Glass('EXPRESS', 'CLEAR', 'LOW E Q366', 'Clear');

  expect($glass->getGlass316())->toBe("3/16 HS CLEAR LOW E Q366 +0.09PVB t Clear +3/16 HS CLEAR (EXPRESS)");
});

test('Glass 3.16 EXPRESS, Privacy, LOW E Q366', function () {

  $glass = new Glass('EXPRESS', 'CLEAR', 'LOW E Q366', 'White Interlayer');

  expect($glass->getGlass316())->toBe("3/16 HS CLEAR LOW E Q366 +0.09PVB t White Interlayer +3/16 HS CLEAR (EXPRESS)");
});

test('Glass 1/8 EXPRESS, No privacy, No LOW E', function () {

  $glass = new Glass('EXPRESS', 'CLEAR', 'NONE', 'Clear');

  expect($glass->getGlass18())->toBe("1/8 HS CLEAR +0.09PVB s Clear +1/8 HS CLEAR (EXPRESS)");
});

test('Glass 1/8 EXPRESS, Privacy, No LOW E', function () {

  $glass = new Glass('EXPRESS', 'CLEAR', 'NONE', 'White Interlayer');

  expect($glass->getGlass18())->toBe("1/8 HS CLEAR +0.09PVB s White Interlayer +1/8 HS CLEAR (EXPRESS)");
});

test('Glass 1/8 EXPRESS, No privacy, LOW E Q366', function () {

  $glass = new Glass('EXPRESS', 'CLEAR', 'LOW E Q366', 'Clear');

  expect($glass->getGlass18())->toBe("1/8 HS CLEAR LOW E Q366 +0.09PVB s Clear +1/8 HS CLEAR (EXPRESS)");
});

test('Glass 1/8 EXPRESS, Privacy, LOW E Q366', function () {

  $glass = new Glass('EXPRESS', 'CLEAR', 'LOW E Q366', 'White Interlayer');

  expect($glass->getGlass18())->toBe("1/8 HS CLEAR LOW E Q366 +0.09PVB s White Interlayer +1/8 HS CLEAR (EXPRESS)");
});

test('Glass RUSH, PRIVACY', function () {

  $glass = new Glass('RUSH', 'OBSCURE/PRIVACY', '', '');

  expect($glass->getRushGlass())->toBe("3/16 HS OBSCURE/PRIVACY (RUSH)");
});

test('Glass RUSH, CLEAR', function () {

  $glass = new Glass('RUSH', 'CLEAR', '', '');

  expect($glass->getRushGlass())->toBe("3/16 HS CLEAR (RUSH)");
});
