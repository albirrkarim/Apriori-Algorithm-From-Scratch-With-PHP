<?php

function dd($var){
    echo "<pre>";
    // print_r($var);
    printable($var);

    echo "</pre>";
    die();
}

function printable($var){
    echo "<pre>";
    // print_r($var);
    
    echo "<table border='1'>";

    echo "<tr>";
        
        
    echo "<td>";
    echo "Item";
    echo "</td>";

    echo "<td>";
    echo "Support";
    echo "</td>";

    if(isset($var[0]["confidence"])){

        echo "<td>";
        echo "Confidence";
        echo "</td>";
    }
    
    echo "</tr>";

    foreach($var  as $row){
        echo "<tr>";
        
        
        echo "<td>";
        echo $row["pair"];
        echo "</td>";

        echo "<td>";
        echo $row["support"]."%";
        echo "</td>";

        if(isset($row["confidence"])){
            echo "<td>";
            echo $row["confidence"]."%";
            echo "</td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";

    echo "</pre>";
    die();

}

function train($all)
{
    $minSupport     = 30;
    $minConfidence  = 50;

    // Hanya sampai L3

    // Hitung kombinasi 1
    $result = L1($all);
    // dd($result);

    // Hitung kombinasi 2 
    $result = L2($result, $all);
    // dd($result);
    // $status = checkIsDone($result,$minSupport);

    // Hitung kombinasi 3 
    $result = L3($result, $all, $minSupport,$minConfidence);
    dd($result);
}


$all = [
    "Bawang,Mentega",
    "Bawang,Telur",
    "Telur,Pisang,Bawang,Apel",
    "Bawang,Telur,Tissue,Roti,Mentega",
    "Apel,Pisang,Roti,Telur",
    "Telur,Pisang,Apel,Mentega",
    "Telur,Mentega,Tissue,Roti",
    "Pisang,Bawang,Apel,Mentega",
    "Pisang,Bawang,Telur",
    "Bawang,Apel,Mentega",
];

// $all=[
//     "P,T,S,J",
//     "P,T,S",
//     "P,S",
//     "P,T,J,A"
// ];


train($all);
















function L1($all)
{
    $data = [];
    foreach ($all as $row) {
        $aa = explode(",", $row);

        foreach ($aa as $b) {

            if (isset($data[$b])) {
                $data[$b]["sum"]++;
            } else {
                $data[$b] = [
                    "pair"  => $b,
                    "sum"       => 1,
                ];
            }
        }
    }

    $newOut = [];
    foreach ($data as $dat) {
        if ($dat["sum"] > 0) {
            $pair = $dat["pair"];
            $newOut[$pair] = [
                "pair"  => $pair,
                "support"   => $dat["sum"] / count($all) * 100
            ];
        }
    }
    return $newOut;
}

function L2($data, $dataAll)
{
    $out = [];
    $ex  = [];

    foreach ($data as $i) {
        unset($data[$i["pair"]]);

        foreach ($data as $j) {
            $pair = $i["pair"] . "," . $j["pair"];

            if (array_search($pair, $ex) === false) {

                array_push($ex, $pair);
                $support       = supportCount($pair, $dataAll);
                $confidence    = confidenceCount($pair, $dataAll);

                if ($support > 0) {
                    $out[$pair] = [
                        "pair"          => $pair,
                        "support"       => $support,
                        "confidence"    => $confidence,
                    ];
                }
            }
        }
    }
    return $out;
}

function L3($data, $all, $minSupport,$minConfidence)
{
    $ex = [];
    foreach ($data as $dat) {
        $newData = $data;
        unset($newData[$dat["pair"]]);

        foreach ($newData as $de) {

            $pair = $dat["pair"] . "," . $de["pair"];

            $pair = clearSame($pair, true);

            if (cekSame($pair, $ex) && count(explode(",", $pair)) >= 3) {
                array_push($ex, $pair);
            }
        }
    }
  

    // Membuat 3 kombinasi 
    $ex = normalize($ex);
    
    // Cari yang paling besar
    $max = 0;
    $idx = 0;
    $out = [];

    for ($i = 0; $i < count($ex); $i++) {
        $support       = supportCount($ex[$i], $all);
        $confidence    = confidenceCount($ex[$i], $all);

        // echo $support."<br>";
        if ($support >= $minSupport && $confidence >= $minConfidence) {
            // $idx = $i;
            // $max = $support;
            array_push($out,[
                "pair"          => $ex[$i],
                "support"       => $support,
                "confidence"    => $confidence,
            ]);
        }
    }

    // if ($max >= $minSupport) {
    //     array_push($out, $ex[$idx]);
    // }

    // var_dump($out);



    return $out;
}

function normalize($data)
{
    $ex = [];
    foreach ($data as $dat) {
        $pair = clearSame($dat);
        $arrPair = makePair($pair);
        foreach ($arrPair as $io) {
            if (cekSame($io, $ex)) {
                array_push($ex, $io);
            }
        }
    }
    return $ex;
}

function cekSame($txt, $ex)
{
    $aa  = explode(",", $txt);
    $len = count($aa);
    foreach ($ex as $i) {
        $arr = explode(",", $i);
        if (count($arr) == $len) {
            $check = 0;
            foreach ($aa as $item) {
                if (array_search($item, $arr) === false ? false : true) {
                    $check++;
                }
            }
            if ($check == $len) {
                return false;
            }
        }
    }
    return true;
}

function checkIsDone($data, $minSupport)
{
    $sum = 0;
    foreach ($data as $dat) {
        if ($dat["support"] >= $minSupport) {
            $sum++;
        }
    }
    if ($sum == 1) {
        return true;
    }
    return false;
}

function confidenceCount($pair,$all){
    $x      = explode(",", $pair);
    $len    = count($x);

    $sambung ="";
    for ($i=0;$i<$len-1;$i++){
        $sambung.=$x[$i].",";
    }
    $sambung= rtrim($sambung, ",");

    $allSupport= supportCount($pair,$all);

    $bawah = supportCount($sambung,$all);

    return $allSupport/$bawah*100;
}


function supportCount($pair, $all)
{
    $sum    = 0;
    $x      = explode(",", $pair);
    $len    = count($x);

    foreach ($all as $row) {
        $arr = explode(",", $row);
        $check = 0;
        foreach ($x as $item) {
            if (array_search($item, $arr) === false ? false : true) {
                $check++;
            }
        }

        if ($check == $len) {
            $sum++;
        }
    }

    return $sum / count($all) * 100;
}


function makePair($data)
{
    $out = [];
    $pair = $data[0] . "," . $data[1] . "," . $data[2];
    array_push($out, $pair);

    if (count($data) == 4) {
        $pair = $data[0] . "," . $data[1] . "," . $data[3];
        array_push($out, $pair);
        $pair = $data[0] . "," . $data[2] . "," . $data[3];
        array_push($out, $pair);
        $pair = $data[1] . "," . $data[2] . "," . $data[3];
        array_push($out, $pair);
    }
    return $out;
}

function clearSame($data, $toArray = false)
{
    $arr    = explode(",", $data);
    $out    = [];
    foreach ($arr as $o) {
        if (array_search($o, $out) === false) {
            array_push($out, $o);
        }
    }
    if ($toArray) {
        $text = "";
        foreach ($out as $item) {
            $text .= $item . ",";
        }
        $text = rtrim($text, ",");
        return $text;
    }
    return $out;
}
