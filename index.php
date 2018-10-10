<?php

define('GOAL_SPLIT_COEFF', 0.2);
define('DRAW_DIFF_COEFF', 0.05);

require_once "data.php";

function limited_float_random($min = 0, $max = 0.1)
{
    return mt_rand(-1, 1) * $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

function limited_int_random($min, $max)
{
    return mt_rand(-1, 1) * $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

function calc_score_range($command)
{
    $average_score_rate = $command['win'] / $command['games'];
    $average_scores = $command['goals']['scored'] * ($average_score_rate + GOAL_SPLIT_COEFF) / $command['win'];

    return array(floor($average_scores), ceil($average_scores));
}

function calc_skip_range($command)
{
    $average_skip_rate = $command['defeat'] / $command['games'];
    $average_skips = $command['goals']['skiped'] * ($average_skip_rate + GOAL_SPLIT_COEFF)  / $command['defeat'];

    return array(floor($average_skips), ceil($average_skip_rate));
}

function calc_attack($command)
{
    return 1 * $command['win'] / $command['games'] + 0.5 * $command['draw'] / $command['games'];
}

function calc_defence($command)
{
    return 1 - (1 * $command['defeat'] / $command['games'] + 0.5 * $command['draw'] / $command['games']);
}

function get_game_points($winner, $loser)
{
    $scores = array_merge(calc_score_range($winner), calc_skip_range($loser));
    shuffle($scores);

    $skips = array_merge(calc_skip_range($loser), calc_score_range($winner));
    shuffle($skips);

    if ($scores[0] === 0) {
        $scores[0]++;
    }

    return array($scores[0], min($skips[0] - 1, $scores));
}

function match($c1, $c2)
{
    global $data;

    $command1 = $data[$c1];

    $defence1 = calc_defence($command1) + limited_float_random();
    $attack1 = calc_attack($command1) + limited_float_random();

    $command2 = $data[$c2];
    $defence2 = calc_defence($command2) + limited_float_random();
    $attack2 = calc_attack($command2) + limited_float_random();

    $win_factor_1 = 0;
    $win_factor_2 = 0;

    if ($attack2 - $defence1 > 0 && abs($attack2 - $defence1) > DRAW_DIFF_COEFF) $win_factor_2++;
    if ($attack1 - $defence2 > 0 && abs($attack1 - $defence2) > DRAW_DIFF_COEFF) $win_factor_1++;

    if ($win_factor_1 > $win_factor_2) {
        return get_game_points($command1, $command2);
    } else if ($win_factor_1 < $win_factor_2) {
        return get_game_points($command2, $command1);
    } else {
        $scores = calc_score_range($command1);
        shuffle($scores);
        return array($scores[0], $scores[0]);
    }
}
