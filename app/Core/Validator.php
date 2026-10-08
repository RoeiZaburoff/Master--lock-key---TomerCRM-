<?php
declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        foreach ($rules as $field => $ruleString) {
            foreach (explode('|', $ruleString) as $rule) {
                $this->applyRule($field, $data[$field] ?? null, $rule);
            }
        }
        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        if ($rule === 'required') {
            if ($value === null || trim((string) $value) === '') {
                $this->errors[$field][] = "$label is required.";
                return;
            }
        }

        if ($rule === 'email' && !empty($value)) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = "$label must be a valid email address.";
            }
        }

        if ($rule === 'numeric' && !empty($value)) {
            if (!is_numeric($value)) {
                $this->errors[$field][] = "$label must be a number.";
            }
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if (strlen((string) $value) > $max) {
                $this->errors[$field][] = "$label must not exceed $max characters.";
            }
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (float) substr($rule, 4);
            if (is_numeric($value) && (float) $value < $min) {
                $this->errors[$field][] = "$label must be at least $min.";
            }
        }
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(string $field): string
    {
        return $this->errors[$field][0] ?? '';
    }
}
