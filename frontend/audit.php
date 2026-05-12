<?php
require_once '../backend/db_connect.php';

$rows = $pdo
    ->query("
        SELECT *
        FROM audit_log
        ORDER BY changed_at DESC
        LIMIT 200
    ")
    ->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Audit Log</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>

<div class="container">

    <div class="topbar">
        <h1>Audit Log</h1>

        <div class="user">
            <a href="dashboard.php">Back</a>
        </div>
    </div>

    <div class="card">

        <table>

            <thead>

                <tr>
                    <th>Time</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Row ID</th>
                    <th>Old Value</th>
                    <th>New Value</th>
                </tr>

            </thead>

            <tbody>

            <?php foreach ($rows as $r): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($r['changed_at']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($r['action_type']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($r['table_name']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars((string)$r['row_id']) ?>
                    </td>

                    <td>
                        <pre style="margin:0;white-space:pre-wrap;">
<?= $r['old_value']
    ? json_encode(
        json_decode($r['old_value'], true),
        JSON_PRETTY_PRINT
      )
    : '-' ?>
                        </pre>
                    </td>

                    <td>
                        <pre style="margin:0;white-space:pre-wrap;">
<?= $r['new_value']
    ? json_encode(
        json_decode($r['new_value'], true),
        JSON_PRETTY_PRINT
      )
    : '-' ?>
                        </pre>
                    </td>

                </tr>

            <?php endforeach; ?>

            <?php if (count($rows) === 0): ?>

                <tr>
                    <td colspan="6">
                        No audit logs found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>