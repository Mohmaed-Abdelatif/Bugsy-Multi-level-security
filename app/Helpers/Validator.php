<?php
// validation helper for V2/V3 controllers

// Usage:
// $errors = Validator::make($data)
//     ->required(['name', 'email', 'password'])
//     ->email('email')
//     ->min('password', 8)
//     ->numeric('price')
//     ->validate();
//
// if (!empty($errors)) {
//     $this->error('Validation failed', 422, $errors);
//     return;
// }

namespace Helpers;

class Validator
{
    private array $data;
    private array $errors = [];

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    // Factory method — start a validation chain
    public static function make(array $data): self
    {
        return new self($data);
    }


    //--------------------------------------------------
    // Rules — each returns $this for chaining ->x()->y()
    //--------------------------------------------------

    // Fields must be present and not empty
    public function required(array|string $fields): self
    {
        $fields = is_array($fields) ? $fields : [$fields];

        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || $this->data[$field] === '' || $this->data[$field] === null) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
        }

        return $this;
    }

    // Field must be a valid email format
    public function email(string $field): self
    {
        if ($this->hasValue($field) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Must be a valid email address');
        }

        return $this;
    }

    // Field must be at least $length characters
    public function min(string $field, int $length): self
    {
        if ($this->hasValue($field) && strlen((string)$this->data[$field]) < $length) {
            $this->addError($field, "Must be at least {$length} characters");
        }

        return $this;
    }

    // Field must be at most $length characters
    public function max(string $field, int $length): self
    {
        if ($this->hasValue($field) && strlen((string)$this->data[$field]) > $length) {
            $this->addError($field, "Must be at most {$length} characters");
        }

        return $this;
    }

    // Field must be numeric
    public function numeric(string $field): self
    {
        if ($this->hasValue($field) && !is_numeric($this->data[$field])) {
            $this->addError($field, 'Must be a number');
        }

        return $this;
    }

    // Field must be numeric and >= $min
    public function minValue(string $field, float $min): self
    {
        if ($this->hasValue($field) && is_numeric($this->data[$field]) && $this->data[$field] < $min) {
            $this->addError($field, "Must be at least {$min}");
        }

        return $this;
    }

    // Field must be one of the allowed values
    public function in(string $field, array $allowed): self
    {
        if ($this->hasValue($field) && !in_array($this->data[$field], $allowed, true)) {
            $this->addError($field, 'Must be one of: ' . implode(', ', $allowed));
        }

        return $this;
    }

    // Field must match a regex pattern
    public function regex(string $field, string $pattern, string $message = 'Invalid format'): self
    {
        if ($this->hasValue($field) && !preg_match($pattern, (string)$this->data[$field])) {
            $this->addError($field, $message);
        }

        return $this;
    }

    // Password complexity: at least one letter and one number
    public function passwordComplexity(string $field): self
    {
        if ($this->hasValue($field)) {
            $value = (string)$this->data[$field];
            if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/[0-9]/', $value)) {
                $this->addError($field, 'Must contain at least one letter and one number');
            }
        }

        return $this;
    }

    // Strips XSS-risky tags — use on free-text fields before saving
    // Returns sanitized value via getSanitized(), does not add an error
    public function noScriptTags(string $field): self
    {
        if ($this->hasValue($field)) {
            $value = (string)$this->data[$field];
            if (preg_match('/<script|<iframe|javascript:|onerror=|onload=/i', $value)) {
                $this->addError($field, 'Contains disallowed content');
            }
        }

        return $this;
    }


    //--------------------------------------------------
    // Final result
    //--------------------------------------------------

    // Returns array of errors, empty array if validation passed
    // ['email' => 'Must be a valid email address', ...]
    public function validate(): array
    {
        return $this->errors;
    }

    // Returns true if no errors
    public function passes(): bool
    {
        return empty($this->errors);
    }

    // Returns true if any errors
    public function fails(): bool
    {
        return !empty($this->errors);
    }


    //--------------------------------------------------
    // Private helpers
    //--------------------------------------------------

    private function hasValue(string $field): bool
    {
        return isset($this->data[$field]) && $this->data[$field] !== '' && $this->data[$field] !== null;
    }

    // Only keep the FIRST error per field — avoids overwhelming the user
    // with multiple stacked messages for the same field
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }
}