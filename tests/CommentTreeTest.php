<?php
// Checks the tree logic without a database:  php tests/CommentTreeTest.php
require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\DiscussionController;

function check(bool $ok, string $what): void
{
    echo ($ok ? 'PASS ' : 'FAIL ') . $what . PHP_EOL;
    if (!$ok) {
        exit(1);
    }
}

$row = fn ($id, $parent) => ['id' => $id, 'parent_id' => $parent];

// Same rows as the table in the lab handout.
$rows = [$row(1, null), $row(2, 1), $row(3, 2), $row(4, 1), $row(5, null), $row(6, 3), $row(7, 6)];
$tree = DiscussionController::buildTree($rows);
check(array_column($tree, 'id') === [1, 2, 3, 6, 7, 4, 5], 'reading order matches the handout');
check(array_column($tree, 'depth') === [0, 1, 2, 3, 4, 1, 0], 'depths match the handout');

// A 12-deep chain: label says depth 11 but indentation stops at 8.
$chain = [$row(1, null)];
for ($i = 2; $i <= 12; $i++) {
    $chain[] = $row($i, $i - 1);
}
$deep = DiscussionController::buildTree($chain);
check(end($deep)['depth'] === 11 && end($deep)['indent'] === 8, 'indent capped at 8, depth label stays 11');
check(count(DiscussionController::buildTree($chain, 5)) === 5, 'traversal limit stops at 5 nodes');

// Bad data: comments 8 and 9 point at each other, so no root reaches them.
$cyclic = [$row(1, null), $row(8, 9), $row(9, 8)];
check(array_column(DiscussionController::buildTree($cyclic), 'id') === [1], 'cycle is skipped, no infinite loop');

check(DiscussionController::parseId('1') === 1, "parseId accepts '1'");
foreach (['0', '-1', 'abc', '1.5', '01', '99999999999', ''] as $bad) {
    check(DiscussionController::parseId($bad) === null, "parseId rejects '$bad'");
}
echo "All checks passed.\n";
