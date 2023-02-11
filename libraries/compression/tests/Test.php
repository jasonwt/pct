<?php

const N = 256;

class Point {
    public $x, $y;

    public function __construct(array $xy) {
        $this->x = $xy["x"];
        $this->y = $xy["y"];
    }
}

function modulo($a, $b) {
    $r = $a % $b;
    return $r < 0 ? $r + $b : $r;
}

function add($a, $b, $p) {
    return modulo(($a + $b), $p);
}

function sub($a, $b, $p) {
    return modulo(($a - $b + $p), $p);
}

function mul($a, $b, $p) {
    $res = 0;
    for ($i = 0; $i < 32; $i++) {
        if ($b & (1 << $i)) {
            $res = ($res + $a) % $p;
        }
        $a = ($a + $a) % $p;
    }
    return $res;
}

function inv($a, $p) {
    $b = $p;
    $x = 1;
    $y = 0;
    while ($b) {
        $t = intval($a / $b);
        list($a, $b) = [$a - $t * $b, $b];
        list($x, $y) = [$x - $t * $y, $y];
    }
    return modulo($x, $p);
}

function addPoint($a, $b, $p) {
    $x = add(add(mul($b->y - $a->y, inv($b->x - $a->x, $p), $p), $a->x, $p), $b->x, $p);
    $y = add(mul($b->y - $a->y, inv($b->x - $a->x, $p), $p), mul($x - $a->x, $x - $a->x, $p), $p);
    $y = sub(mul($y, inv(2 * $a->y, $p), $p), $b->y, $p);
    return new Point(["x" => $x, "y" => $y]);
}

function mulPoint($a, $k, $p) {
    $res = $a;
    for ($i = 1; $i < $k; $i++) {
        $res = addPoint($res, $a, $p);
    }
    return $res;
}

$n = N;
$g = new Point(["x" => 3, "y" => 10]);
$q = mulPoint($g, $n, 23);

echo "Public key: (" . $q->x . ", " . $q->y . ")\n";

?>
