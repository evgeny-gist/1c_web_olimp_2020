<?php
require_once '../functions.php';

$input_content = file_get_contents('input.txt');
$lines = explode("\r", $input_content);
header('Content-Type: application/json');

$config_line = explode(' ', array_shift($lines));
$min_elements = trim($config_line[0]);
$max_rules = trim($config_line[1]);
array_shift($lines); // Пропускаем <<<CSS>>>

$css_rules = [];
$css_pure_rules = [];
$html = '';

$is_css = true;
foreach ($lines as $line) {
    $line = trim($line);
    if ($line == '<<<HTML>>>') {
        $is_css = false;
        continue;
    }
    if ($is_css)
        $css_pure_rules[] = $line;
    else
        $html .= $line;
}

//dd($html);

foreach ($css_pure_rules as $css_pure_rule) { // Формируем правила CSS
    $css_rule = new stdClass();
    $css_rule->selector = trim(explode('{', $css_pure_rule)[0]);
    $css_rule->type = (str_split($css_rule->selector)[0] == '#') ? 'id' : 'class';
    $css_rule->rules = '';
    $is_rules = false;
    foreach (str_split($css_pure_rule) as $char) {
        if ($char == '{') {
            $is_rules = true;
            continue;
        }
        if ($char == '}')
            continue;
        if ($is_rules)
            $css_rule->rules .= $char;

    }
    $css_rule->selector = mb_substr($css_rule->selector, 1);
    $css_rule->rules = explode(';', $css_rule->rules);
    $css_rules[] = $css_rule;
}

dd($css_rules);

$Dom = new DOMDocument();
$Dom->loadHTML($html);
$XPath = new DOMXPath($Dom);

foreach ($css_rules as $css_rule) {
    $xpath_query = "//*[contains(@{$css_rule->type}, '$css_rule->selector')]";
    $elements_to_apply = $XPath->query($xpath_query);
    if ($elements_to_apply->length < $min_elements)
        continue;
    /** @var DOMElement $dom_element */
    foreach ($elements_to_apply as $dom_element) {
        $rules_to_apply = $css_rule->rules;
        $already_rules = $dom_element->getAttribute('style');
        if ($already_rules)
            $rules_to_apply = array_merge($rules_to_apply, explode(';', $already_rules));
        if (count($rules_to_apply) <= $max_rules)
            $dom_element->setAttribute('style', implode('; ', $rules_to_apply));
        else
            $dom_element->removeAttribute('style');
    }
}

//dd($class_h);

$output_content = $Dom->saveHTML();

dd($output_content);

file_put_contents('output.txt', $output_content);
