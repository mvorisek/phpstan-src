<?php

namespace Mvorisek\Dev;

use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\TreeNode;
use Hoa\File\Read;

require_once __DIR__ . '/vendor/autoload.php';

$parser = Llk::load(new Read(__DIR__ . '/resources/RegexGrammar.pp'));

$astToArrayFx = function (TreeNode $node) use (&$astToArrayFx): array {
    $res = ['id' => $node->getId()];

    if ($node->getValueToken() !== null) {
        $res['token'] = $node->getValueToken();
    }

    if ($node->getValue() !== null) {
        $res['value'] = $node->getValueValue();
    }

    foreach ($node->getChildren() as $childNode) {
        $res['children'][] = $astToArrayFx($childNode);
    }

    return $res;
};

$regex = '';

$ast = $parser->parse($regex);

print_r($astToArrayFx($ast));
