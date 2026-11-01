<?php
function record_probe(mysqli $db, float $ratio, float $threshold, int $now): void {
    $db->begin_transaction();
    try {
        $state = $db->query('SELECT * FROM status WHERE ID=1 FOR UPDATE')->fetch_assoc();
        if (!$state) throw new RuntimeException('Status row missing');
        $down = $ratio <= $threshold;
        if ($down && (int)$state['status'] === 1) {
            $stmt = $db->prepare('INSERT INTO downtime (`down`, total, hostsup) VALUES (?, NULL, ?)');
            $stmt->bind_param('id', $now, $ratio); $stmt->execute();
            $id = $db->insert_id;
            $stmt = $db->prepare('UPDATE status SET status=0, lastdown=? WHERE ID=1');
            $stmt->bind_param('i', $id); $stmt->execute();
        } elseif (!$down && (int)$state['status'] === 0) {
            $id = (int)$state['lastdown'];
            $stmt = $db->prepare('UPDATE downtime SET total=GREATEST(0, ? - `down`) WHERE ID=? AND total IS NULL');
            $stmt->bind_param('ii', $now, $id); $stmt->execute();
            $db->query('UPDATE status SET status=1 WHERE ID=1');
        }
        $db->commit();
    } catch (Throwable $e) { $db->rollback(); throw $e; }
}
