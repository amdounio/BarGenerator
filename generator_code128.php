#!/usr/bin/env php
<?php
// Get current directory
$rootDir = dirname(__FILE__);

// Create barcodes directory
if (!file_exists($rootDir.'/barcodes')) {
	mkdir($rootDir.'/barcodes', 0777, true);
} else {
	$files = glob($rootDir.'/barcodes/*');
	foreach($files as $file){
		if(is_file($file)) {
			unlink($file);
		}
	}
}

// Read CSV to generate barcodes and format indesign readable csv
ini_set('auto_detect_line_endings', true);
if(file_exists($rootDir.'/labels.csv')){
	$in	= fopen($rootDir.'/labels.csv', 'r');
} else {
	echo 'Il manque le fichier labels.csv !';
}

// Remove data.csv if exist
if(file_exists($rootDir.'/data.csv')){
	unlink($rootDir.'/data.csv');
}

// Write data.csv with barcode link
$out = fopen($rootDir.'/data.csv', 'w');

// Loop on csv lines
$i = 0; $barcodeIndex = 0;
while (($row = fgetcsv($in, 0, ';')) !== false) {
	if($i === 0) {
		$headers = $row;
		$skuIndex = array_search('SKU', $row);
		$barcodeIndex = array_search('@BARCODE', $row);
		$row[] = '@IMAGE';
		fputcsv($out, $row);
	} else {
		// Create associative array
		$line = array_combine($headers, $row);
		if(!empty($line['@BARCODE'])){
			$filePath = '/barcodes/' . $line['@BARCODE'] . '.png';
			
			// Generate barcode image
			file_put_contents($rootDir . $filePath, file_get_contents('https://bcg.milleniumfloor.com/?type=CODE128&code=' . $line['@BARCODE']));

			// Edit $row to add barcode url
			$row[$skuIndex] = trim($row[$skuIndex]);
			$row[$barcodeIndex] = $filePath;
			$row[] = '/images/' . $line['QUALITY'] . '_' . $row[$skuIndex] . '_' . $line['SIZE'] . '.jpg';

			// Write line into readable csv
			fputcsv($out, $row);
		}
	}
	$i++;
}
fclose($in);
fclose($out);

// Encode csv file to UTF-16LE
$csv = file_get_contents($rootDir.'/data.csv');
$data = mb_convert_encoding($csv, 'UTF-16LE', 'UTF-8');
file_put_contents($rootDir.'/data.csv', $data);

ini_set('auto_detect_line_endings', false);
?>