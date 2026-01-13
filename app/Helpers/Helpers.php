<?php

namespace App\Helpers;

use Carbon\CarbonPeriod;

class Helper
{
    public static function multiplePartition($start, $end)
    {
        $period = CarbonPeriod::create(date("Y-m-d", strtotime($start)), date("Y-m-d", strtotime($end)));
        $partition_date = '';
        foreach ($period as $pdate) {
            if ($pdate->format("Y-m-d") == date("Y-m-d", strtotime($start))) {
                $partition_date .= "p" . $pdate->format("Ymd");
            } else {
                $partition_date .= ",p" . $pdate->format("Ymd");
            }
        }

        $partition = "partition ($partition_date)";
        return $partition;
    }

    // You can also add your TimeZone helper here if needed later
    public static function sTimeZone()
    {
        return '+08:00'; 
    }
}