<?php

/** club_id = 0 — посещаемость группы в саду; иначе — кружок. */
function attendance_group_club_id(): int
{
    return 0;
}

function attendance_sql_is_group(string $alias = 'a'): string
{
    return $alias . '.club_id = 0';
}

function attendance_sql_is_club(string $alias = 'a'): string
{
    return $alias . '.club_id > 0';
}
