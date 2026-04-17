<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TransactionLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

echo "Checking pagination HTML structure...\n\n";

// Create a simple paginator to see what HTML Laravel generates
$items = collect(range(1, 50));
$currentPage = 2;
$perPage = 10;

$paginator = new LengthAwarePaginator(
    $items->forPage($currentPage, $perPage),
    $items->count(),
    $perPage,
    $currentPage
);

// Set the path and view
$paginator->setPath('/admin/transactions/logs');

echo "Laravel Pagination HTML:\n";
echo $paginator->links() . "\n\n";

echo "CSS classes used in pagination:\n";
$html = $paginator->links()->toHtml();

// Extract CSS classes
preg_match_all('/class="([^"]+)"/', $html, $matches);
$classes = array_unique($matches[1]);

foreach ($classes as $class) {
    echo "- $class\n";
}

echo "\nPagination structure analysis complete!\n";
