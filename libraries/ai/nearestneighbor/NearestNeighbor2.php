<?php

class NearestNeighbor
{
    private $data;
    private $labels;

    public function __construct($data, $labels)
    {
        $this->data = $data;
        $this->labels = $labels;
    }

    public function euclidean_distance($point1, $point2)
    {
        $distance = 0;
        for ($i = 0; $i < count($point1); $i++) {
            $distance += pow($point1[$i] - $point2[$i], 2);
        }
        return sqrt($distance);
    }

    public function predict($point, $k)
    {
        $distances = array();
        for ($i = 0; $i < count($this->data); $i++) {
            $distance = $this->euclidean_distance($point, $this->data[$i]);
            $distances[$i] = array(
                'index' => $i,
                'distance' => $distance
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
    array(10, 11, 12),
    array(13, 14, 15),
    array(16, 17, 18)
);
$labels = array(0, 1, 1, 0, 1, 0);

$nearest_neighbor = new NearestNeighbor($data, $labels);

$point = array(5, 16, 3);
$k = 3;

$prediction = $nearest_neighbor->predict($point, $k);

echo "The prediction for the point [5, 6, 7] is: $prediction";
