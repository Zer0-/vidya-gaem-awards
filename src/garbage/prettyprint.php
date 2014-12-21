<?php

//this is used by rint
function pretty_print_var($v){
    $raw_vals = ['string', 'boolean', 'double', 'integer'];
    $t = gettype($v);
    if ($t == 'integer' and $v == 0)
        echo "0";
    else if ($t == 'NULL')
        echo "NULL";
    else if ($t == 'boolean'){
        if ($v)
            echo "true";
        else
            echo "false";
    }
    else if (method_exists($v, '__toString'))
        echo $v;
    else if (!in_array(gettype($v), $raw_vals))
        echo var_dump($v);
    else
        echo $v;
}

//a print function named 'rint' that tries to behave a bit more like Python's print
function rint($var=600000, $var2=600000, $var3=600000){
    if (gettype($var) == 'integer' and $var == 600000){
        echo "\n";
        return;
    }
    pretty_print_var($var);
    if (gettype($var2) != 'integer' or $var2 != 600000){
        echo ' ';
        pretty_print_var($var2);
    }
    if (gettype($var3) != 'integer' or $var3 != 600000){
        echo ' ';
        pretty_print_var($var3);
    }
    echo "\n";
}


