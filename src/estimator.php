<?php

function infectionsByRequestedTime($currently_infected, $period, $duration){
    $factor = null;
    if($period == "months"){
        $factor = intdiv(($duration * 30), 3);
    }elseif ($period == "weeks"){
        $factor = intdiv(($duration * 7), 3);
    }else{
        $factor = intdiv($duration, 3);
    }

    return pow(2, $factor) * $currently_infected;
}

function dollarsInFlight($period, $duration, $avgIncome, $population, $infections){
    $daysFactor = null;
    if($period == "months"){
        $daysFactor = 30 * $duration;
    }elseif ($period == "weeks"){
        $daysFactor = 7 * $duration;
    }else{
        $daysFactor = $duration;
    }

    return ($avgIncome * $population * $infections) / $daysFactor;
}

function covid19ImpactEstimator($data)
{
    $currentlyInfected = $data["reportedCases"] * 10;
    $currentlyInfectedSevere = $data["reportedCases"] * 50;

    $infectionsByRequestedTime = infectionsByRequestedTime($currentlyInfected,
        $data["periodType"],
        $data["timeToElapse"]
    );
    $severeInfectionsByRequestedTime = infectionsByRequestedTime($currentlyInfectedSevere,
        $data["periodType"],
        $data["timeToElapse"]
    );

    $severeCasesByRequestedTime = 0.15 * $infectionsByRequestedTime;
    $severeCasesByRequestedTime2 = 0.15 * $severeInfectionsByRequestedTime;

    $hospitalBedsByRequestedTime = ($data["totalHospitalBeds"] * 0.35) - $severeCasesByRequestedTime;
    $severeHospitalBedsByRequestedTime = ($data["totalHospitalBeds"] * 0.35) - $severeCasesByRequestedTime2;

    $casesForICUByRequestedTime = 0.05 * $infectionsByRequestedTime;
    $severeCasesForICUByRequestedTime = 0.05 * $severeInfectionsByRequestedTime;

    $casesForVentilatorsByRequestedTime = 0.02 * $infectionsByRequestedTime;
    $severeCasesForVentilatorsByRequestedTime = 0.02 * $severeInfectionsByRequestedTime;

    $dollarsInFlight = dollarsInFlight( $data["periodType"],
        $data["timeToElapse"],
        $data["region"]["avgDailyIncomeInUSD"],
        $data["region"]["avgDailyIncomePopulation"],
        $infectionsByRequestedTime
    );

    $severeDollarsInFlight = dollarsInFlight( $data["periodType"],
        $data["timeToElapse"],
        $data["region"]["avgDailyIncomeInUSD"],
        $data["region"]["avgDailyIncomePopulation"],
        $severeInfectionsByRequestedTime
    );

    return [
        "data" => $data, // the input data you got

        "impact" => [
            "currentlyInfected" => $currentlyInfected,
            "infectionsByRequestedTime" => floor($infectionsByRequestedTime),
            "severeCasesByRequestedTime" => floor($severeCasesByRequestedTime),
            "hospitalBedsByRequestedTime" => ($hospitalBedsByRequestedTime > 0) ?
                                                floor($hospitalBedsByRequestedTime) :
                                                ceil($hospitalBedsByRequestedTime),
            "casesForICUByRequestedTime" => floor($casesForICUByRequestedTime),
            "casesForVentilatorsByRequestedTime" => floor($casesForVentilatorsByRequestedTime),
            "dollarsInFlight" => floor($dollarsInFlight),
        ], // your best case estimation

        "severeImpact" => [
            "currentlyInfected" =>  $currentlyInfectedSevere,
            "infectionsByRequestedTime" => floor($severeInfectionsByRequestedTime),
            "severeCasesByRequestedTime" => floor($severeCasesByRequestedTime2),
            "hospitalBedsByRequestedTime" => ($severeHospitalBedsByRequestedTime > 0) ?
                                                floor($severeHospitalBedsByRequestedTime) :
                                                ceil($severeHospitalBedsByRequestedTime),
            "casesForICUByRequestedTime" => floor($severeCasesForICUByRequestedTime),
            "casesForVentilatorsByRequestedTime" => floor($severeCasesForVentilatorsByRequestedTime),
            "dollarsInFlight" => floor($severeDollarsInFlight),
        ]// your severe case estimation
    ];
}
