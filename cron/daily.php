<?php
/**
 * Daily maintenance job. Add to cron (e.g. cPanel) to run once a day:
 *
 *   0 9 * * *  /usr/bin/php /home/USER/path/cron/daily.php >/dev/null 2>&1
 *
 * Tasks:
 *   - send birthday greetings (+ discount note)
 *   - refresh customer rankings from accounting + local orders
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Services\BirthdayService;
use App\Services\AccountingService;
use App\Services\RankingService;

$sent = BirthdayService::runForAllTenants();

foreach (Database::all('SELECT id FROM tenants WHERE is_active = 1') as $t) {
    $tid = (int) $t['id'];
    AccountingService::syncSpend($tid);   // no-op until accounting configured
    RankingService::recomputeCustomers($tid);
}

echo "[" . date('Y-m-d H:i') . "] birthday messages: {$sent}\n";
