<?php
declare(strict_types=1);

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(mixed $value): string
{
    return '$' . number_format((float) $value, 2);
}

function url(string $path = ''): string
{
    $base = defined('APP_BASE') ? rtrim(APP_BASE, '/') : '';
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $base = defined('APP_BASE') ? rtrim(APP_BASE, '/') : '';
    return $base . '/assets/' . ltrim($path, '/');
}

function format_phone(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 11 && $digits[0] === '1') {
        $digits = substr($digits, 1);
    }
    if (strlen($digits) === 10) {
        return '(' . substr($digits, 0, 3) . ') ' . substr($digits, 3, 3) . '-' . substr($digits, 6);
    }
    return $phone;
}

function phone_digits(string $phone): string
{
    return preg_replace('/\D/', '', $phone);
}

function address_maps_link(string $addr_full, string $display = ''): string
{
    if ($addr_full === '') {
        return h($display !== '' ? $display : $addr_full);
    }
    $enc   = urlencode($addr_full);
    $label = h($display !== '' ? $display : $addr_full);
    return '<a href="javascript:void(0)" class="maps-dropdown-trigger" data-address="' . h($enc) . '">' . $label . '</a>';
}

function week_bounds(string $anchor = 'now'): array
{
    $dt  = new \DateTime($anchor !== '' ? $anchor : 'now');
    $dow = (int) $dt->format('N');
    $mon = (clone $dt)->modify('-' . ($dow - 1) . ' days');
    $sun = (clone $dt)->modify('+' . (7 - $dow) . ' days');
    return [$mon->format('Y-m-d'), $sun->format('Y-m-d')];
}
