<?php

declare(strict_types=1);

function buildSqlDump(PDO $pdo, array $tables): string
{
    $lines = [];
    $lines[] = 'SET FOREIGN_KEY_CHECKS = 0;';
    $lines[] = '';

    // Drop in child-to-parent order for safe restore.
    $dropOrder = array_reverse($tables);
    foreach ($dropOrder as $table) {
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $table) ?? '';
        if ($tableName === '') {
            continue;
        }
        $lines[] = "DROP TABLE IF EXISTS `{$tableName}`;";
    }
    $lines[] = '';

    foreach ($tables as $table) {
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $table) ?? '';
        if ($tableName === '') {
            continue;
        }

        $stmt = $pdo->query("SHOW CREATE TABLE `{$tableName}`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !isset($row['Create Table'])) {
            continue;
        }

        $lines[] = $row['Create Table'] . ';';

        $rows = $pdo->query("SELECT * FROM `{$tableName}`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $dataRow) {
            $columns = array_map(static fn($col) => '`' . str_replace('`', '``', (string)$col) . '`', array_keys($dataRow));
            $values = [];
            foreach ($dataRow as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = $pdo->quote((string)$value);
                }
            }

            $lines[] = sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s);',
                $tableName,
                implode(', ', $columns),
                implode(', ', $values)
            );
        }

        $lines[] = '';
    }

    $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
    $lines[] = '';

    return implode(PHP_EOL, $lines);
}

function executeSqlDump(PDO $pdo, string $sql): void
{
    $statements = splitSqlStatements($sql);

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    try {
        foreach ($statements as $statement) {
            $trimmed = trim($statement);
            if ($trimmed === '') {
                continue;
            }
            $pdo->exec($trimmed);
        }
    } finally {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $inSingle = false;
    $inDouble = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if (!$inSingle && !$inDouble) {
            if ($char === '-' && $next === '-') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if ($char === '/' && $next === '*') {
                $i += 2;
                while ($i + 1 < $length && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i++;
                continue;
            }
        }

        if ($char === "'" && !$inDouble) {
            $prev = $i > 0 ? $sql[$i - 1] : '';
            if ($prev !== '\\') {
                $inSingle = !$inSingle;
            }
            $buffer .= $char;
            continue;
        }

        if ($char === '"' && !$inSingle) {
            $prev = $i > 0 ? $sql[$i - 1] : '';
            if ($prev !== '\\') {
                $inDouble = !$inDouble;
            }
            $buffer .= $char;
            continue;
        }

        if ($char === ';' && !$inSingle && !$inDouble) {
            $statements[] = $buffer;
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }

    return $statements;
}
