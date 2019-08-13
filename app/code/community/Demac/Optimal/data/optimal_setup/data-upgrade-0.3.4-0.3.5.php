<?php

$prefix = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'csv' . DIRECTORY_SEPARATOR;
$csvs = array(
    'webservices' => 'webservices-codes-0.3.3.csv',
    'common' => 'common-codes-0.3.3.csv'
);

$model = Mage::getModel('optimal/errorcode');

foreach ($csvs as $key => $name) {
    $path = $prefix . $name;
    $fh = fopen($path, 'r');

    while (($row = fgetcsv($fh, 999999, ',')) !== false) {
        list($code, $message) = $row;
        $model->setData(array(
            'code' => $code,
            'message' => $message,
            'active' => 0,
            'created_at' => time(),
            'updated_at' => time()
        ))->save();
    }
}

