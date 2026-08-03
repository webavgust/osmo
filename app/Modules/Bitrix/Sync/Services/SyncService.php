<?php

namespace App\Modules\Bitrix\Sync\Services;

use Illuminate\Support\Facades\DB;

class SyncService
{
    // РАБОЧАЯ ВЕРСИЯ БЕЗ ПРОВЕРКИ СТРУКТУРЫ
//    public static function generateQuery(string $table)
//    {
//        // Получаем структуру таблицы (MySQL SHOW COLUMNS на локальном bitrix)
//        $colsInfo = DB::connection('bitrix')->select("SHOW COLUMNS FROM `{$table}`");
//        if (empty($colsInfo)) {
//            return '';
//        }
//
//        // Список имен колонок в исходном порядке
//        $columns = array_map(function ($c) { return $c->Field; }, $colsInfo);
//        $colList = implode(', ', $columns);
//
//        // Определение числового типа
//        $isNumericType = function(string $type) {
//            $type = strtolower($type);
//            return (bool) preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|numeric|float|double|real)/', $type);
//        };
//
//        // Собираем выражения для каждой колонки (каждое выражение — строка SQL, возвращающая текстовое представление значения)
//        $parts = [];
//        foreach ($colsInfo as $colInfo) {
//            $col = $colInfo->Field;
//            $type = $colInfo->Type;           // например: int(11), varchar(255), decimal(10,2)
//            $nullable = (strtoupper($colInfo->Null) === 'YES');
//
//            if ($isNumericType($type)) {
//                if ($nullable) {
//                    // nullable numeric: NULL -> 'NULL' (текст в результирующей строке), иначе число (без кавычек)
//                    $expr = "CASE WHEN {$col} IS NULL THEN 'NULL' ELSE cast({$col} AS varchar) END";
//                } else {
//                    // not-null numeric: заменяем NULL на '0' (символы 0)
//                    $expr = "coalesce(cast({$col} AS varchar), '0')";
//                }
//            } else {
//                // заменяем двойные кавычки (") внутри значения на одиночную кавычку (апостроф) через chr(39)
//                $replaced = "replace(cast({$col} AS varchar), '\"', chr(39))";
//
//                if ($nullable) {
//                    // nullable non-numeric: NULL -> 'NULL', иначе "value" (двойные кавычки вокруг)
//                    $expr = "CASE WHEN {$col} IS NULL THEN 'NULL' ELSE concat('\"', {$replaced}, '\"') END";
//                } else {
//                    // not-null non-numeric: всегда "value" (если NULL — пустая строка "")
//                    $expr = "concat('\"', coalesce({$replaced}, ''), '\"')";
//                }
//            }
//
//            $parts[] = $expr;
//        }
//
//        // Собираем массив элементов для array_join
//        $arrayElements = implode(', ', $parts);
//
//        // concat теперь только с тремя аргументами: префикс, array_join(ARRAY[...], ', '), суффикс
//        $concatExpr = "concat('INSERT INTO {$table} ({$colList}) VALUES (', array_join(ARRAY[{$arrayElements}], ', '), ');')";
//
//        // Итоговый запрос: агрегируем все INSERT-строки в одну большую строку, разделённую '\n'
//        $query = "SELECT
//        array_join(
//            array_agg({$concatExpr}),
//            '\\n'
//        ) AS complete_dump
//    FROM {$table};";
//
//        return $query;
//    }




    public static function generateQuery(string $table)
    {
        // Получаем структуру локальной (bitrix) таблицы
        $colsInfo = DB::connection('bitrix')->select("SHOW COLUMNS FROM `{$table}`");
        if (empty($colsInfo)) {
            return '';
        }

        // Имена всех колонок (для проверки структуры)
        $columns = array_map(function ($c) { return $c->Field; }, $colsInfo);

        // Имена колонок ДЛЯ ДАМПА (исключаем JSON по локальному типу)
        $isJsonType = function(string $type) {
            return (bool) preg_match('/^json\b/i', strtolower($type));
        };
        $dumpInfos = array_values(array_filter($colsInfo, function ($c) use ($isJsonType) {
            return !$isJsonType($c->Type);
        }));
        $dumpColumns = array_map(function ($c) { return $c->Field; }, $dumpInfos);

        // ARRAY['col1','col2',...] с экранированием одинарных кавычек для проверки структуры
        $escapedCols = array_map(function ($c) {
            return str_replace("'", "''", $c);
        }, $columns);
        $localColsArray = "ARRAY['" . implode("','", $escapedCols) . "']";

        // Определение числового типа (по локальному типу MySQL)
        $isNumericType = function(string $type) {
            $type = strtolower($type);
            return (bool) preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|numeric|float|double|real)/', $type);
        };

        // Формируем выражения для значений колонок (только не-JSON)
        $parts = [];
        foreach ($dumpInfos as $colInfo) {
            $col = $colInfo->Field;
            $type = $colInfo->Type;
            $nullable = (strtoupper($colInfo->Null) === 'YES');

            if ($isNumericType($type)) {
                // Числа не оборачиваем в кавычки
                if ($nullable) {
                    $expr = "CASE WHEN {$col} IS NULL THEN 'NULL' ELSE cast({$col} AS varchar) END";
                } else {
                    $expr = "coalesce(cast({$col} AS varchar), '0')";
                }
            } else {
                // Прочие типы (строки/даты/булевые и т.п.) — текст + заменяем двойные кавычки на апостроф
                $asText = "replace(cast({$col} AS varchar), '\"', chr(39))";

                if ($nullable) {
                    $expr = "CASE WHEN {$col} IS NULL THEN 'NULL' ELSE concat('\"', {$asText}, '\"') END";
                } else {
                    $expr = "concat('\"', coalesce({$asText}, ''), '\"')";
                }
            }

            $parts[] = $expr;
        }

        $arrayElements = implode(', ', $parts);

        // Колонки для INSERT (только не-JSON)
        $colListDump = implode(', ', $dumpColumns);

        // Выражение, собирающее одну INSERT-строку для записи
        // Если вдруг все колонки оказались JSON и dumpColumns пуст,
        // сгенерируем INSERT без колонок (MySQL: INSERT INTO t() VALUES();)
        if ($colListDump === '') {
            $perRowInsertExpr = "concat('INSERT INTO {$table} () VALUES ();')";
        } else {
            $perRowInsertExpr = "concat('INSERT INTO {$table} ({$colListDump}) VALUES (', array_join(ARRAY[{$arrayElements}], ', '), ');')";
        }

        // Итоговый SQL с проверкой отсутствующих на локали колонок (remote \ local)
        $query = "
WITH
  local_cols AS (SELECT {$localColsArray} AS cols),
  remote_cols AS (
    SELECT column_name, ordinal_position
    FROM information_schema.columns
    WHERE table_name = '{$table}'
    ORDER BY ordinal_position
  ),
  missing AS (
    SELECT array_join(array_agg(column_name ORDER BY ordinal_position), ',') AS missing_cols
    FROM remote_cols
    WHERE NOT contains((SELECT cols FROM local_cols), column_name)
  )
SELECT
  CASE
    WHEN (SELECT missing_cols FROM missing) IS NOT NULL
      THEN concat('не хватает полей на локали: ', (SELECT missing_cols FROM missing))
    ELSE (SELECT array_join(array_agg({$perRowInsertExpr}), '\\n') FROM {$table})
  END AS complete_dump
;";

        return $query;
    }







}
