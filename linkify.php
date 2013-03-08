<?php

$data_dir = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . '2013';
$exceptions = array_map('trim', file(__DIR__ . DIRECTORY_SEPARATOR . 'non_words.txt'));

$words = array();

echo "Getting data files from folder [$data_dir]\n";
$data_files = get_data_files($data_dir);
echo "Total of " . count($data_files) . " files found\n\n";

foreach ($data_files as $data_file) {
	print "Processing file $data_file\n";
	$file_words = get_file_words($data_file, $exceptions);
	print "Found " . count($file_words) . " words.\n\n";

	foreach ($file_words as $word) {
		if (!isset($words[$word])) {
			$words[$word] = array();
		}
		$words[$word][] = get_file_label($data_file);
	}
}

uasort($words, 'compare_by_count');

report_top_words($words, 20);
report_ted($words);

/**
 * Report most common words
 * 
 * @param array $words Data
 * @param integer $limit How many words to report (top 10)
 * 
 * @return void
 */
function report_top_words($words, $limit) {

	print "Top $limit report\n";
	print "---------------------------\n";
	$top = 1;
	foreach ($words as $word => $files) {
		print "$top. $word (shared by " . count($files) . ")\n";
		print "\tShared by: " . implode(', ', $files) . "\n\n";
		if ($top++ >= $limit) {
			break;
		}
	}
}

/**
 * Report if TED abbriviations are commonly shared
 * 
 * @param array $words Data
 * 
 * @return void
 */
function report_ted($words) {

	print "TED report\n";
	print "----------\n";
	$ted = array(
		'technology', 
		'entertainment', 
		'design',
	);
	foreach ($ted as $word) {
		if (!empty($words[$word])) {
			print "$word is reprepsented by " . count($words[$word]) . ": " . implode(', ', $words[$word]) . "\n";
		}
		else {
			print "$word is not represented\n";
		}
	}
}

/**
 * Custom sorting routine
 * 
 * Compare using the count of array elements
 * 
 * @param array $a
 * @param array $b
 * 
 * return integer
 */
function compare_by_count($a, $b) {
	$count_a = count($a);
	$count_b = count($b);

	$result = $count_b > $count_a ? 1 : -1;
	return $result;
}

/**
 * Figure out a nicer label for a file
 * 
 * @param string $file File path
 * 
 * @return string
 */
function get_file_label($file) {
	$result = $file;

	$info = pathinfo($file);
	if (!empty($info['filename'])) {
		$result = preg_replace('/_/', ' ', $info['filename']);
	}

	return $result;
}

/**
 * Get unique words from the specified file
 * 
 * @param string $file Path to file
 * @param array $exclude List of exceptions
 * 
 * @return array
 */
function get_file_words($file, $exclude) {
	$result = array();

	$content = file_get_contents($file);
	if (!empty($content)) {
		$words = explode(' ', $content);

		foreach ($words as $word) {
			$clean_word = clean_word($word);
			if (!empty($clean_word) 
				&& !in_array($clean_word, $exclude) 
				&& !in_array($clean_word, $result)) {
					
				$result[] = $clean_word;
			}
		}
	}

	return $result;
}

/**
 * Clean up words
 * 
 * Trim, remove punctuation and other special characters 
 * from a dirtily trimmed word.
 * 
 * @param string $word Dirty word
 * @return string Clean word
 */
function clean_word($word) {
	$result = trim($word);
	$result = strtolower($result);
	$result = preg_replace("/\W+/", '', $result);
	$result = preg_replace("/^\d+$/", '', $result);
	return $result;
}

/**
 * Given data folder, get a list of data files
 * 
 * @param string $dir Data folder path
 * 
 * @return array
 */
function get_data_files($dir) {
	$result = array();

	if (!file_exists($dir)) { die("Data folder [$dir] does not exists"); }
	if (!is_dir($dir))      { die("Data folder [$dir] is not a folder"); }
	if (!is_readable($dir)) { die("Data folder [$dir] is not readable"); }

	$dh = opendir($dir);
	if (!$dh) { die("Failed to open folder [$dir] for reading"); }
		
	while ($file = readdir($dh)) {
		if (preg_match("/^\./", $file)) { continue; }
		
		$result[] = $dir . DIRECTORY_SEPARATOR . $file;
	}
	closedir($dh);

	return $result;
}
?>
