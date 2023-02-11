<?php

class NearestNeighbor {
    private $data;
    private $labels;

    public function __construct($data, $labels) {
        $this->data = $data;
        $this->labels = $labels;
    }

    public function predict($point, $k) {
        $distances = array();
        for ($i = 0; $i < count($this->data); $i++) {
            $distance = 0;
            for ($j = 0; $j < count($point); $j++) {
                $distance += pow($point[$j] - $this->data[$i][$j], 2);
            }
            $distances[$i] = array(
                'index' => $i,
                'distance' => sqrt($distance)
            );
        }

        usort($distances, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        $neighbors = array();
        for ($i = 0; $i < $k; $i++) {
            $neighbors[$i] = $this->labels[$distances[$i]['index']];
        }

        $counts = array_count_values($neighbors);
        arsort($counts);

        return array_keys($counts)[0];
    }
}

$data = array(
    array(1, 2, 3),
    array(4, 5, 6),
    array(7, 8, 9),
    array(10, 11, 12)
);
$labels = array(0, 1, 1, 0);

$nearest_neighbor = new NearestNeighbor($data, $labels);

$point = array(5, 6, 7);
$k = 3;

$prediction = $nearest_neighbor->predict($point, $k);

echo "The prediction for the point [5, 6, 7] is: $prediction";



?>